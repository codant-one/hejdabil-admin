<script setup>

import { useTheme } from 'vuetify'
import { useWindowSize } from '@vueuse/core'
import { ref, onMounted, nextTick, computed, watchEffect, watch, onBeforeUnmount } from 'vue'
import { useNotificationsStore } from '@/stores/useNotifications'
import { useSignaturesStore } from '@/stores/useSignatures'
import VuePdfEmbed from 'vue-pdf-embed'
import SignaturePad from 'signature_pad'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue"
import logo from "@images/logos/billogg-logo.svg";

const props = defineProps({
  token: {
    type: String,
    required: true,
  },
})

const notificationsStore = useNotificationsStore()
const signaturesStore = useSignaturesStore()

const { width: windowWidth } = useWindowSize()

// --- Cleanup tracking ---
const cleanupHandlers = []
const pendingTimeouts = []
const safeTimeout = (fn, delay) => {
  const id = setTimeout(fn, delay)
  pendingTimeouts.push(id)
  return id
}

// --- Refs de Estado (Ahora más simples) ---
const pdfSource = ref(null)
const signaturePlacement = ref({ x: null, y: null, page: 1, isStatic: false, visible: false, alignment: 'left' })
const isSignatureModalVisible = ref(false)
const signaturePad = ref(null)
const finalState = ref(null)
const isSubmitting = ref(false)
const isAlreadySigned = ref(false)
const signedInfo = ref(null)
const pdfContainer = ref(null)
const computedPlacement = ref({ left: null, top: null, scale: 1 })
const computedStaticPlacement = ref({ left: null, top: null, scale: 1 })
// --- Refs de Elementos del DOM ---
const signatureCanvas = ref(null)
const isRequestOngoing = ref(false)

// --- PDF Viewer Controls ---
const pdfViewportEl = ref(null)
const pdfViewerRef = ref(null)
const pdfDoc = ref(null)
const pdfNumPages = ref(0)
const activePage = ref(1)
const pdfViewportSize = ref({ width: 0, height: 0 })
const pdfPageSize = ref({ width: 0, height: 0 })
const pdfZoom = ref(1)
const minZoom = 0.5
const maxZoom = 3
const zoomStep = 0.25
const isHighQualityPass = ref(false)
const isOverlayVisible = computed(() => isSubmitting.value || (isRequestOngoing.value && !pdfSource.value))
const pdfRenderKey = computed(() => `${pdfSource.value || 'empty'}-${isHighQualityPass.value ? 'hq' : 'lq'}`)

// --- Lógica del Tema (para el lienzo de firma) ---
const { global } = useTheme()

// --- Detección de plataforma ---
const isIOS = computed(() => {
  if (typeof navigator === 'undefined') return false
  return /iPad|iPhone|iPod/.test(navigator.userAgent) || 
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
})

const isAndroid = computed(() => {
  if (typeof navigator === 'undefined') return false
  return /Android/i.test(navigator.userAgent)
})

const deviceMemoryGb = computed(() => {
  if (typeof navigator === 'undefined') return 4
  const memory = Number(navigator.deviceMemory || 4)
  return Number.isFinite(memory) ? memory : 4
})

const isMobile = computed(() => windowWidth.value < 1024)
const isLowMemoryMobile = computed(() => isMobile.value && deviceMemoryGb.value <= 4)

// --- PDF Computed Properties ---
const pdfQuality = computed(() => {
  const dpr = typeof window !== 'undefined' ? (window.devicePixelRatio || 1) : 1
  const numPages = Number(pdfNumPages.value || 1)

  // Render progresivo para PDFs multipágina: primero rápido, luego nítido
  if (numPages > 1) {
    if (!isHighQualityPass.value) {
      if (isIOS.value) return 1
      if (isAndroid.value) return isLowMemoryMobile.value ? 1.05 : 1.1
      if (isMobile.value) return 1.08
      return 1
    }

    if (isIOS.value) return Math.min(1.65, Math.max(1.35, dpr * 0.8))
    if (isAndroid.value) {
      if (isLowMemoryMobile.value) return Math.min(1.85, Math.max(1.5, dpr * 0.9))
      return Math.min(2.1, Math.max(1.7, dpr * 1.05))
    }
    if (isMobile.value) return Math.min(1.9, Math.max(1.55, dpr * 0.95))
    return Math.min(2.05, Math.max(1.7, dpr))
  }

  // Para una sola página mantener alta nitidez sin disparar consumo
  if (isIOS.value) return Math.min(1.9, Math.max(1.45, dpr))
  if (isAndroid.value) {
    if (isLowMemoryMobile.value) return Math.min(2.05, Math.max(1.65, dpr * 0.95))
    return Math.min(2.35, Math.max(1.85, dpr * 1.1))
  }
  return Math.min(2.25, Math.max(1.75, dpr * 1.05))
})

const pdfPageAspect = computed(() => {
  const w = Number(pdfPageSize.value?.width || 0)
  const h = Number(pdfPageSize.value?.height || 0)
  if (!w || !h) return 1
  return w / h
})

const pdfFitPageWidth = computed(() => {
  const mobile = isMobile.value
  const vw = Math.max(100, Number(pdfViewportSize.value?.width || 0) - (mobile ? 8 : 24))
  const vh = Math.max(100, Number(pdfViewportSize.value?.height || 0) - 24)
  const aspect = pdfPageAspect.value

  // Mantener misma escala visual para una o múltiples páginas
  const heightBasedWidth = vh * aspect
  return Math.min(vw, heightBasedWidth)
})

const pdfVisualWidth = computed(() => {
  const mobile = isMobile.value
  const viewportWidth = Number(pdfViewportSize.value?.width || 0)
  const fitWidth = pdfFitPageWidth.value
  
  // If viewport is not ready, return safe default
  if (viewportWidth < 100) return 700
  
  // En mobile, usar el 100% del ancho sin márgenes para evitar scroll horizontal
  if (mobile) {
    return viewportWidth
  }
  
  // Calculate final width for desktop
  const calculatedWidth = Math.round(fitWidth * pdfZoom.value)
  const margin = 40
  return Math.max(300, Math.min(calculatedWidth, viewportWidth - margin))
})

// iOS Safari tiene un budget total de canvas (~256-450 megapixels según dispositivo)
// Ajustar el límite según la cantidad de páginas para repartir el budget
const MAX_CANVAS_DIM = computed(() => {
  const numPages = Math.max(1, Number(pdfNumPages.value || 1))
  if (isIOS.value) {
    // ~256 megapixels de budget total. A más páginas, menor dimensión por canvas.
    if (numPages <= 1) return 4096
    if (numPages <= 3) return 3072
    return 2560
  }
  if (isMobile.value) return 4096
  return 16384
})

const pdfRenderScale = computed(() => {
  const pageW = Number(pdfPageSize.value?.width || 0)
  const pageH = Number(pdfPageSize.value?.height || 0)
  if (!pageW || !pageH) return 1
  const dpr = typeof window !== 'undefined' ? (window.devicePixelRatio || 1) : 1
  // vue-pdf-embed ya multiplica internamente por dpr.
  // pdfQuality es el multiplicador adicional sobre dpr para supersampling.
  // Con :width pasado, vue-pdf-embed calcula el viewport scale internamente,
  // así que NO multiplicamos por visualScale aquí (sería doble).
  let scale = pdfQuality.value
  // Limitar para no exceder dimensiones máximas de canvas del navegador
  // Canvas final = pageW * (pdfVisualWidth/pageW) * dpr * scale = pdfVisualWidth * dpr * scale
  const maxDim = Math.max(pageW, pageH)
  const visualScale = pdfVisualWidth.value / pageW
  const canvasLargestSide = maxDim * visualScale * dpr * scale
  if (canvasLargestSide > MAX_CANVAS_DIM.value) {
    scale = MAX_CANVAS_DIM.value / (maxDim * visualScale * dpr)
  }
  return Math.max(0.5, scale)
})

// --- PDF Zoom Functions ---
const zoomIn = () => {
  if (pdfZoom.value < maxZoom) pdfZoom.value = Math.min(pdfZoom.value + zoomStep, maxZoom)
}

const zoomOut = () => {
  if (pdfZoom.value > minZoom) pdfZoom.value = Math.max(pdfZoom.value - zoomStep, minZoom)
}

const resetZoom = () => {
  pdfZoom.value = 1
}

let pdfResizeObserver
const updatePdfViewportSize = () => {
  const el = pdfViewportEl.value
  if (!el) return
  pdfViewportSize.value = { width: el.clientWidth, height: el.clientHeight }
}

const initPdfResizeObserver = () => {
  const el = pdfViewportEl.value
  if (!el || typeof ResizeObserver === 'undefined') return
  if (pdfResizeObserver) pdfResizeObserver.disconnect()
  pdfResizeObserver = new ResizeObserver(() => {
    try { updatePdfViewportSize() } catch (e) { /* ignore resize errors */ }
  })
  pdfResizeObserver.observe(el)
  updatePdfViewportSize()
}

const handlePdfLoaded = async (doc) => {
  try {
    const pages = Number(doc?.numPages || 0)
    pdfDoc.value = doc
    pdfNumPages.value = pages

    if (pages > 1 && !isHighQualityPass.value) {
      // Segunda pasada breve para subir nitidez tras la primera pintura
      safeTimeout(() => {
        isHighQualityPass.value = true
      }, 140)
    } else if (pages <= 1 && !isHighQualityPass.value) {
      isHighQualityPass.value = true
    }

    const page = await doc.getPage(1)
    const viewport = page.getViewport({ scale: 1 })
    pdfPageSize.value = { width: viewport.width, height: viewport.height }
    
    await nextTick()
    
    // Calcular posición del placeholder después de que el PDF se haya cargado
    if (!isAlreadySigned.value) {
      safeTimeout(() => calculatePlaceholderPosition(false), 300)
      safeTimeout(() => calculatePlaceholderPosition(true), 1500)
    }
    
    // Forzar actualización del tamaño del viewport
    updatePdfViewportSize()
  } catch (e) {
    console.error('Error loading PDF:', e)
  }
}

const scrollToPage = async (page) => {
  const nextPage = Math.max(1, Math.min(Number(page || 1), Number(pdfNumPages.value || 1)))
  activePage.value = nextPage

  await nextTick()

  const container = pdfContainer.value
  if (!container) return

  const canvases = Array.from(container.querySelectorAll('canvas'))
  const target = canvases[nextPage - 1]
  if (!target) return

  target.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

watchEffect(async () => {
  if (!pdfSource.value || !pdfViewportEl.value) return
  await nextTick()
  initPdfResizeObserver()
})

// Watch zoom changes to trigger recalculation
watch(pdfZoom, () => {
  if (!isAlreadySigned.value && signaturePlacement.value.visible) {
    safeTimeout(() => calculatePlaceholderPosition(false), 100)
  }
})

// Watch pdfPageSize to recalculate position when PDF loads
watch(pdfPageSize, () => {
  if (!isAlreadySigned.value && signaturePlacement.value.visible) {
    safeTimeout(() => calculatePlaceholderPosition(false), 100)
  }
}, { deep: true })

onBeforeUnmount(() => {
  if (pdfResizeObserver) pdfResizeObserver.disconnect()
  // Liberar blob URL
  revokePdfSource()
  // Cancelar todos los timeouts pendientes
  pendingTimeouts.forEach(id => clearTimeout(id))
  // Remover event listeners
  cleanupHandlers.forEach(fn => { try { fn() } catch(e) { /* ignore */ } })
})

// Recargar la página al crear otro acuerdo
function reloadPage() {
  window.location.reload();
}

// --- Funciones del Componente (Refactorizadas) ---

// Registrar la visita a la página de firma
const logPageView = async () => {
  try {
    await signaturesStore.logView(props.token)
  } catch (error) {
    // No interrumpir el flujo si falla el registro de vista
    console.warn('No se pudo registrar la visita:', error)
  }
}

const checkTokenStatus = async () => {
  try {
    const response = await signaturesStore.checkStatus(props.token)

    if (response.data.status === 'signed') {
      // El documento ya está firmado
      isAlreadySigned.value = true

      signedInfo.value = {
        signedAt: response.data.signed_date_formatted,
        file_id: response.data.agreement_id || response.data.document_id || 'unknown',
        message: response.data.message
      }

      finalState.value = {
        type: 'success',
        title: response.data.message,
        message: response.data.message
      }

      return true
    } else if (response.data.status === 'expired') {
      finalState.value = {
        type: 'error',
        title: 'Länk utgången',
        message: 'Denna signeringslänk har löpt ut.',
      }

      return false
    } else if (response.data.status === 'failed') {
      finalState.value = {
        type: 'error',
        title: 'Signeringen misslyckades',
        message: 'Ett fel uppstod under signeringsprocessen. Vänligen kontakta support.',
      }

      return false
    } else if (response.data.status === 'delivery_issues') {
      finalState.value = {
        type: 'warning',
        title: 'Problem med e-postleverans',
        message: 'Det fanns problem med att leverera signeringsförfrågan. Vänligen kontakta avsändaren.',
      }
      
      return false
    }
    
    // Estados válidos para continuar: sent, delivered, reviewed
    return ['sent', 'delivered', 'reviewed'].includes(response.data.status)
  } catch (error) {
    console.error("Kunde inte verifiera token status:", error)

    finalState.value = {
      type: 'error',
      title: 'Ogiltig länk',
      message: error.message || 'Denna signeringslänk är ogiltig.',
    }
    
    return false
  }
}

// Liberar blob URL anterior si existe
const revokePdfSource = () => {
  if (pdfSource.value) {
    try { URL.revokeObjectURL(pdfSource.value) } catch (e) { /* ignore */ }
    pdfSource.value = null
  }
}

// Cargar el PDF según el estado
const loadPdf = async () => {
  try {
    isHighQualityPass.value = false
    let pdfResponse
    
    if (isAlreadySigned.value) {
      pdfResponse = await signaturesStore.getSignedPdf(props.token)
    } else {
      pdfResponse = await signaturesStore.getUnsignedPdf(props.token)
    }
    
    // Liberar blob anterior antes de crear uno nuevo
    revokePdfSource()
    pdfSource.value = URL.createObjectURL(pdfResponse.data)
  } catch (error) {
    console.error("Kunde inte ladda PDF:", error)
    throw error
  }
}

// Cargar los detalles de posicionamiento de la firma (solo si no está firmado)
const loadSignatureDetails = async () => {
  try {
    const response = await signaturesStore.getDetails(props.token)
    
    const { placement_x, placement_y, placement_page, signature_alignment } = response.data

    if (placement_x !== null && placement_y !== null) {
      signaturePlacement.value = {
        x: placement_x,
        y: placement_y,
        page: placement_page || 1,
        isStatic: false,
        visible: true,
        alignment: signature_alignment || 'left',
      }
    } else {
      signaturePlacement.value = {
        x: null, 
        y: null,
        page: 1,
        isStatic: true,
        visible: true,
        alignment: signature_alignment || 'left',
      }
    }
  } catch (error) {
    console.error("Kunde inte ladda signeringsdetaljer:", error)
    throw error
  }
}

// Calcula la posición del placeholder considerando múltiples páginas
// y desplaza el scroll del contenedor para poner el placeholder en vista
let placeholderRetries = 0
const MAX_PLACEHOLDER_RETRIES = 10

const calculatePlaceholderPosition = (shouldScroll = false) => {
  if (!signaturePlacement.value.visible) {
    return
  }

  const container = pdfContainer.value
  if (!container) {
    if (placeholderRetries < MAX_PLACEHOLDER_RETRIES) {
      placeholderRetries++
      safeTimeout(() => calculatePlaceholderPosition(shouldScroll), 200)
    }
    return
  }

  const pages = Array.from(container.querySelectorAll('canvas, svg, img'))
  if (pages.length === 0) {
    if (placeholderRetries < MAX_PLACEHOLDER_RETRIES) {
      placeholderRetries++
      safeTimeout(() => calculatePlaceholderPosition(shouldScroll), 200)
    }
    return
  }
  placeholderRetries = 0

  // Para posición estática, usar la última página
  const targetPageIndex = signaturePlacement.value.isStatic 
    ? pages.length - 1 
    : Math.min(Math.max(0, (signaturePlacement.value.page || 1) - 1), pages.length - 1)
  
  const targetPage = pages[targetPageIndex]
  
  if (!targetPage) {
    console.warn('Target page not found:', targetPageIndex, 'of', pages.length)
    return
  }

  const pageRect = targetPage.getBoundingClientRect()
  const containerRect = container.getBoundingClientRect()

  // Obtener el estilo computado para calcular el border
  const computedStyle = window.getComputedStyle(targetPage)
  const borderLeft = parseFloat(computedStyle.borderLeftWidth) || 0
  const borderTop = parseFloat(computedStyle.borderTopWidth) || 0

  // Usar clientWidth/clientHeight que excluyen el border
  // Esto da las dimensiones reales del contenido renderizado del PDF
  const contentWidth = targetPage.clientWidth
  const contentHeight = targetPage.clientHeight
  
  // Para mobile, necesitamos ajustar el cálculo considerando el padding del contenedor
  const mobile = isMobile.value
  const containerPaddingLeft = 0
  const containerPaddingTop = 0
  
  // Detectar si el PDF es horizontal (landscape) o vertical (portrait)
  const isLandscape = contentWidth > contentHeight

  if (signaturePlacement.value.isStatic) {
    // Convertir porcentajes a píxeles locales (inversa del cálculo del admin)
    // Usando las dimensiones del contenido (sin border)
    const x_percent = parseFloat(signaturePlacement.value.x)
    const y_percent = parseFloat(signaturePlacement.value.y)
    
    const localX = (x_percent / 100) * contentWidth
    const localY = (y_percent / 100) * contentHeight

    // Calcular posición absoluta incluyendo el offset del border
    const absoluteX = (pageRect.left - containerRect.left) + localX + borderLeft + (container.scrollLeft || 0) - containerPaddingLeft
    const absoluteY = (pageRect.top - containerRect.top) + localY + borderTop + (container.scrollTop || 0) - containerPaddingTop

    // Escalar el botón según el zoom del PDF y la orientación
    // En mobile con PDF horizontal (landscape), usar scale más pequeño
    let baseScale = 1
    if (mobile) {
      baseScale = isLandscape ? 0.4 : 0.7  // 40% para horizontal, 70% para vertical
    }
    const scale = pdfZoom.value * baseScale
    computedStaticPlacement.value = { left: absoluteX, top: absoluteY, scale }
  } else {
    // Convertir porcentajes a píxeles locales (inversa del cálculo del admin)
    // Usando las dimensiones del contenido (sin border)
    const x_percent = parseFloat(signaturePlacement.value.x)
    const y_percent = parseFloat(signaturePlacement.value.y)
    
    const localX = (x_percent / 100) * contentWidth
    const localY = (y_percent / 100) * contentHeight

    // Calcular posición absoluta incluyendo el offset del border
    const absoluteX = (pageRect.left - containerRect.left) + localX + borderLeft + (container.scrollLeft || 0) - containerPaddingLeft
    const absoluteY = (pageRect.top - containerRect.top) + localY + borderTop + (container.scrollTop || 0) - containerPaddingTop

    // Escalar el botón según el zoom del PDF y la orientación
    // En mobile con PDF horizontal (landscape), usar scale más pequeño
    let baseScale = 1
    if (mobile) {
      baseScale = isLandscape ? 0.4 : 0.7  // 40% para horizontal, 70% para vertical
    }
    const scale = pdfZoom.value * baseScale
    computedPlacement.value = { left: absoluteX, top: absoluteY, scale }
  }

  // Desplazar la VENTANA para que el placeholder quede visible
  if (shouldScroll) {
    safeTimeout(() => {
      const placeholder = document.querySelector('.signature-placeholder-admin')
      if (placeholder) {
        const rect = placeholder.getBoundingClientRect()
        const offsetFactor = windowWidth.value <= 768 ? 0.5 : 0.35
        const scrollY = window.scrollY + rect.top - (window.innerHeight * offsetFactor)
        window.scrollTo({ top: scrollY, behavior: 'smooth' })
      }
    }, 500)
  }
}

// Carga tanto el PDF como los detalles de la firma
const loadSignatureData = async () => {
  isRequestOngoing.value = true
  
  try {
    // Primero verificar el estado del token
    const canProceed = await checkTokenStatus()
    
    if (!canProceed && !isAlreadySigned.value) {
      isRequestOngoing.value = false
      return
    }
    
    // Registrar la visita a la página (solo si no está firmado)
    if (!isAlreadySigned.value) {
      logPageView()
    }

    if (!isAlreadySigned.value) {
      // Cargar PDF y detalles en paralelo para reducir tiempo inicial
      await Promise.all([
        loadPdf(),
        loadSignatureDetails(),
      ])
    } else {
      await loadPdf()
    }
    
    // Inicializar el ResizeObserver después de cargar el PDF
    await nextTick()
    safeTimeout(() => {
      initPdfResizeObserver()
    }, 100)
    
    // Si ya está firmado, solo esperar a que se renderice el PDF
    if (isAlreadySigned.value) {
      safeTimeout(() => {
        updatePdfViewportSize()
      }, 300)
    }
    
    // Solo preparar interacciones de firma si no está firmado
    if (!isAlreadySigned.value) {
      // Esperar a que el PDF se renderice y calcular posición
      await nextTick()
      // Primer cálculo sin scroll - esperar más para que el PDF se renderice completamente
      safeTimeout(() => {
        calculatePlaceholderPosition(false)
      }, 500)
      
      // Segundo cálculo para asegurar que el PDF está completamente renderizado
      safeTimeout(() => {
        calculatePlaceholderPosition(false)
      }, 1000)
      
      // Después de que el video termine (aprox 3 segundos), hacer scroll al placeholder
      safeTimeout(() => {
        calculatePlaceholderPosition(true)
      }, 3500)
      
      // Recalcular al redimensionar y al hacer scroll con debounce
      let resizeTimeout
      const recalculateHandler = () => {
        clearTimeout(resizeTimeout)
        resizeTimeout = setTimeout(() => {
          calculatePlaceholderPosition(false)
        }, 100)
      }
      
      window.addEventListener('resize', recalculateHandler)
      window.addEventListener('scroll', recalculateHandler, { passive: true })
      
      // También recalcular cuando el contenedor hace scroll
      if (pdfContainer.value) {
        pdfContainer.value.addEventListener('scroll', recalculateHandler, { passive: true })
      }
      
      // Registrar handlers para cleanup
      cleanupHandlers.push(
        () => window.removeEventListener('resize', recalculateHandler),
        () => window.removeEventListener('scroll', recalculateHandler),
        () => pdfContainer.value?.removeEventListener('scroll', recalculateHandler)
      )
    }
    
  } catch (error) {
    console.error("Ett fel uppstod:", error)
    if (!finalState.value) {
      finalState.value = {
        type: 'error',
        title: 'Fel',
        message: 'Kunde inte ladda dokumentet.',
      }
    }
  } finally {
    isRequestOngoing.value = false
  }
}

// Abrir el diálogo de firma
const openSignatureModal = () => {
  if (isAlreadySigned.value) return // No permitir firmar si ya está firmado
  
  isSignatureModalVisible.value = true
  nextTick(() => {
    const canvas = signatureCanvas.value
    if (canvas) {
      signaturePad.value = new SignaturePad(canvas, {
        backgroundColor: 'rgba(0, 0, 0, 0)',
        penColor: global.name.value === 'dark' ? 'rgb(220, 220, 240)' : 'rgb(0, 0, 0)',
      })
      const ratio = Math.max(window.devicePixelRatio || 1, 1)
      canvas.width = canvas.offsetWidth * ratio
      canvas.height = canvas.offsetHeight * ratio
      canvas.getContext("2d").scale(ratio, ratio)
      signaturePad.value.clear()
    }
  })
}

// Cerrar el diálogo de firma
const closeSignatureModal = (accepted) => {
  isSignatureModalVisible.value = false
  if (accepted && signaturePad.value && !signaturePad.value.isEmpty()) {
    submitFinalSignature(signaturePad.value.toDataURL('image/png'))
  }
}

// Enviar la firma al backend
const submitFinalSignature = async (signatureImage) => {
  isRequestOngoing.value = true
  if (isSubmitting.value) return
  isSubmitting.value = true
  
  try {
    // Solo enviamos la imagen de la firma
    // El backend usa las coordenadas ya guardadas en la base de datos
    const payload = {
      signature: signatureImage,
    }
    const response = await signaturesStore.submitSignature(props.token, payload)

    // Liberar el blob del PDF sin firmar
    revokePdfSource()

    // Cargar el PDF firmado para mostrar y descargar
    try {
      const pdfResponse = await signaturesStore.getSignedPdf(props.token)
      pdfSource.value = URL.createObjectURL(pdfResponse.data)
    } catch (pdfError) {
      console.error("No se pudo cargar el PDF firmado:", pdfError)
    }

    // Guardar información del documento firmado
    signedInfo.value = {
      signedAt: new Date().toLocaleString('sv-SE'),
      file_id: response.data.agreement_id || response.data.document_id || 'unknown',
      documentId: response.data.document_id,
      message: response.data.message
    }

    finalState.value = {
      type: 'success',
      title: 'Signering slutförd!',
      message: response.data.message
    }
    isAlreadySigned.value = true

    // Enviar notificación de firma completada
    try {
      const fileId = signedInfo.value?.file_id
      const route = response.data.is_agreement ?
        `/dashboard/admin/agreements?file_id=${fileId}` :
        `/dashboard/admin/documents?file_id=${fileId}`

      await notificationsStore.send({
        title: response.data.is_agreement ? 'Avtal signerat' : 'Dokument signerat',
        subtitle: response.data.is_agreement ? 'Ett avtal har signerats framgångsrikt' : 'Ett dokument har signerats framgångsrikt',
        text: response.data.is_agreement ? `Avtalet har signerats korrekt. Avtals ID: ${response.data.order_id || 'N/A'}` : `Dokumentet har signerats korrekt. Dokument ID: ${response.data.order_id || 'N/A'}`,
        color: 'primary',
        icon: response.data.is_agreement ? 'custom-contract' : 'custom-signature',
        route: route || null,
        notification_id: fileId?.toString() || null,
        signed_by: response.data.signed_by || 'Användare',
        user_id: response.data.user_id || null // ID del usuario que recibirá la notificación
      })
    } catch (notificationError) {
      // No interrumpir el flujo si falla la notificación
      console.error('Error al enviar notificación:', notificationError)
    }
  } catch (error) {
    // Determinar si es un error 500 u otro tipo de error
    const isServerError = error.response?.status >= 500
    
    finalState.value = {
      type: 'error',
      title: isServerError ? 'Serverfel' : 'Ett fel uppstod',
      message: error.response?.data?.message || (isServerError 
        ? 'Ett oväntat fel uppstod på servern. Vänligen försök igen senare.' 
        : 'Kunde inte slutföra signeringen.'),
    }
    
    // Log adicional para errores 500
    if (isServerError) {
      console.error('Error 500 durante la firma:', error)
    }
  } finally {
    isSubmitting.value = false
    isRequestOngoing.value = false
  }
}

// Descargar el PDF firmado
const downloadSignedPdf = () => {
  if (pdfSource.value) {
    const link = document.createElement('a')
    link.href = pdfSource.value
    // Usar documentId si está disponible, de lo contrario usar agreementId
    const fileId = signedInfo.value?.documentId || signedInfo.value?.agreementId || 'dokument'
    link.download = `dokument-${fileId}-signerat.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

onMounted(loadSignatureData);
</script>

<template>
  <section>
    <LoadingOverlay :is-loading="isOverlayVisible" />

    <!-- Visor de PDF cuando ya está firmado -->
    <div v-if="isAlreadySigned" class="signing-card placement-modal-card">
      <div class="placement-content bg-page">
        <div class="placement-body">
          <!-- Sidebar izquierda con controles -->
          <div class="placement-sidebar">
            <div class="d-flex align-center flex-0" :class="windowWidth < 1024 ? 'justify-center' : ''">
              <img :src="logo" width="121" height="40" alt="Billogg" />
            </div>  
            <VAlert
              color="success"
              class="alert-no-shrink custom-alert mt-4"
              style="flex: none;"
            >
              <VAlertTitle>{{ finalState.title }}</VAlertTitle>
            </VAlert>

            <div class="d-flex gap-2 mt-4">
              <VIcon icon="custom-help" size="24" />
              <span class="text-help">Tryck för att visa dokumentet.</span>
            </div>  

            <VBtn
              :class="windowWidth < 1024 ? 'd-none' : ''"
              class="btn-gradient mt-auto"
              @click="downloadSignedPdf"
            >
              Ladda ner
            </VBtn>
          </div>

          <!-- Divisor vertical -->
          <VDivider vertical class="ps-5 placement-divider" :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

          <!-- Área del PDF -->
          <div ref="pdfViewportEl" class="placement-viewer">
            <div
              ref="pdfContainer"
              class="pdf-container-admin"
            >
              <div v-if="pdfSource" class="pdf-host" :style="{ width: pdfVisualWidth + 'px' }">
                <VuePdfEmbed 
                  :key="`signed-${pdfRenderKey}`"
                  :source="pdfSource"
                  :width="pdfVisualWidth"
                  :scale="pdfRenderScale"
                  @loaded="handlePdfLoaded"
                />
              </div>
            </div>
          </div>

            <VBtn
              :class="windowWidth >= 1024 ? 'd-none' : ''"
              class="btn-gradient"
              @click="downloadSignedPdf"
            >
              Ladda ner
            </VBtn>
        </div>
      </div>
    </div>

    <!-- Estado de error (enlace inválido, expirado, etc.) -->
    <div v-if="!isRequestOngoing && finalState && finalState.type === 'error' && !isAlreadySigned">
      <VCard class="signing-card pa-4 d-flex flex-column" style="min-height: 100vh;">
        <div class="d-flex align-center flex-0" :class="windowWidth < 1024 ? 'justify-center' : ''">
          <img :src="logo" width="121" height="40" alt="Billogg" />
        </div>

        <div 
          class="empty-state my-auto"
          :class="$vuetify.display.smAndDown ? 'px-6 py-0' : 'pa-4'">
          <VIcon
            :size="$vuetify.display.smAndDown ? 80 : 120"
            icon="custom-f-cancel"
          />
          <div class="empty-state-content">
            <div class="empty-state-title">{{ finalState.title }}</div>
            <div class="empty-state-text">
              {{ finalState.message }}
            </div>
          </div>
          <VBtn
            class="btn-ghost"
            @click="reloadPage"
          >
            Försök att signera igen
            <VIcon icon="custom-arrow-right" size="24" />
          </VBtn>
        </div>
      </VCard>
    </div>

    <!-- Visor de PDF para firma (cuando no está firmado) -->
    <div v-if="(!isRequestOngoing || pdfSource) && !finalState && !isAlreadySigned" class="signing-card placement-modal-card">
      <div class="placement-content bg-page">
        <div class="placement-body">
          <!-- Sidebar izquierda con controles -->
          <div class="placement-sidebar">
            <div class="d-flex align-center flex-0" :class="windowWidth < 1024 ? 'justify-center' : ''">
              <img :src="logo" width="121" height="40" alt="Billogg" />
            </div>
            
            <!-- Controles de zoom -->
            <div 
              class="sidebar-block justify-between gap-2"
              :class="windowWidth < 1024 ? 'd-none' : 'd-flex flex-column align-items-center'">
              <div class="sidebar-actions w-100">
                <VBtn 
                  class="btn-ghost mx-auto" 
                  @click="zoomOut" 
                  :disabled="pdfZoom <= minZoom"
                >
                  <VIcon size="16" icon="mdi-minus" />
                </VBtn>
                <div class="zoom-level">{{ Math.round(pdfZoom * 100) }}%</div>
                <VBtn 
                  class="btn-ghost mx-auto" 
                  @click="zoomIn" 
                  :disabled="pdfZoom >= maxZoom"
                >
                  <VIcon size="16" icon="mdi-plus" />
                </VBtn>
              </div>
              <VBtn class="btn-light w-100" @click="resetZoom">Justera</VBtn>
            </div>

            <!-- Miniaturas de páginas (solo desktop - v-if evita crear canvases en mobile) -->
            <div
              v-if="pdfSource && pdfNumPages >= 1 && !isMobile"
              class="my-4 flex-grow-1 overflow-auto placement-thumbnails-scroll"
            >
              <div class="d-flex flex-column justify-center align-center gap-2">
                <div
                  v-for="page in pdfNumPages"
                  :key="page"
                  class="thumbnail-page-item"
                  :class="{ 'is-active': page === activePage }"
                  @click="scrollToPage(page)"
                >
                  <div class="thumbnail-number">{{ page }}</div>
                  <VuePdfEmbed
                    :source="pdfSource"
                    :page="page"
                    :width="146"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Divisor vertical -->
          <VDivider vertical class="ps-5 placement-divider" :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

          <!-- Área del PDF -->
          <div ref="pdfViewportEl" class="placement-viewer">
            <div
              ref="pdfContainer"
              class="pdf-container-admin"
            >
              <div v-if="pdfSource" class="pdf-host" :style="{ width: pdfVisualWidth + 'px' }">
                <VuePdfEmbed
                  :key="`unsigned-${pdfRenderKey}`"
                  ref="pdfViewerRef"
                  :source="pdfSource"
                  :width="pdfVisualWidth"
                  :scale="pdfRenderScale"
                  @loaded="handlePdfLoaded"
                />
              </div>
              
              <!-- Placeholder de firma clickeable (posición dinámica) -->
              <div 
                v-if="signaturePlacement.visible && !signaturePlacement.isStatic && computedPlacement.left !== null"
                class="signature-placeholder-admin"
                :style="{
                  left: computedPlacement.left + 'px', 
                  top: computedPlacement.top + 'px',
                  transform: `scale(${computedPlacement.scale})`,
                  transformOrigin: 'top left'
                }"
                @click.stop="openSignatureModal"
              >
                <span class="signature-placeholder-content btn-light">
                  <VIcon size="16" icon="custom-pencil" />
                  <span>Signera här</span>
                </span>
              </div>
              
              <!-- Placeholder de firma estática -->
              <div 
                v-if="signaturePlacement.visible && signaturePlacement.isStatic && computedStaticPlacement.left !== null"
                class="signature-placeholder-admin"
                :style="{
                  left: computedStaticPlacement.left + 'px', 
                  top: computedStaticPlacement.top + 'px',
                  transform: `scale(${computedStaticPlacement.scale})`,
                  transformOrigin: 'top left'
                }"
                @click.stop="openSignatureModal"
              >
                <span class="signature-placeholder-content btn-light">
                  <VIcon size="16" icon="custom-pencil" />
                  <span>Signera här</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Diálogo para firmar (el lienzo) -->
    <VDialog 
      v-model="isSignatureModalVisible"
      max-width="500"
      persistent
      class="signature-dialog"
    >
      <VCard>
        <VCardText class="without-padding v-card-custom-title">
          <VIcon icon="custom-signature" class="mr-4" size="32"></VIcon>
          Rita din signatur
        </VCardText>
        <VCardText class="without-padding">
          <div class="signature-pad-wrapper">
            <canvas ref="signatureCanvas"></canvas>
          </div>
        </VCardText>
        <VCardText class="d-flex justify-end gap-4 btn-box">
          <VBtn class="btn-light" @click="closeSignatureModal(false)">Avbryt</VBtn>
          <VBtn 
            class="btn-gradient" 
            @click="closeSignatureModal(true)" 
            :disabled="isSubmitting" 
            :loading="isSubmitting"
          >
            Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss">
  .signature-dialog {
    max-width: 500px;
    border-radius: 16px;
    gap: 32px;
    padding: 24px;

    .v-overlay__content {

      .v-dialog-close-btn {
        top: 16px !important;
        right: 24px;
        transform: none !important;
        height: 16px !important;
        width: 16px !important;
        padding: 0px !important;
      }

      .v-card {
        .v-card-text {
          padding: 24px 24px 24px !important;

          &.without-padding {
            padding: 24px 24px 0px !important;
          }

          &.v-card-custom-title {
            font-weight: 600;
            font-size: 24px;
            line-height: 100%;
            color: #5d5d5d;
          }

          @media (max-width: 991px) {
            &.btn-box {
              flex-direction: column-reverse;
              gap: 8px;
            }
          }
        }
      }
  }
  }
</style>
<style scoped>
  
.page-section {
  min-height: 100vh;
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
}

.signing-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
}

.signing-header {
  display: flex;
  align-items: center;
  justify-content: start;
  padding: 16px 24px;

  position: sticky;
  top: 0;
  z-index: 100;
  background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
}

.signing-card {
  width: 100%;
  display: flex;
  flex-direction: column;
  background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
  border-radius: 8px;
  box-shadow: none;
  overflow: visible;

  &.placement-modal-card {
    max-width: 100%;
  }
}

.placement-modal-card {
  display: flex;
  flex-direction: column;
  height: 100vh;
  height: 100dvh; /* iOS Safari: respeta la barra dinámica del navegador */
  overflow: hidden;
  background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);

  @media (max-width: 1023px) {
    height: auto;
    min-height: 100vh;
    min-height: 100dvh;
    overflow: visible;
  }
}

.placement-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;

  @media (max-width: 1023px) {
    overflow: visible;
  }
}

.placement-body {
  flex: 1;
  display: flex;
  overflow: hidden;
  padding: 24px;
}

.placement-sidebar {
  width: 250px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
}

.sidebar-block {
  border-radius: 12px;
  padding: 12px 0;
}

.sidebar-actions {
  display: flex;
  align-items: center;
  gap: 4px;
  border: solid 1px #6e9383;
  background: linear-gradient(
    90deg,
    #57f287 0%,
    #00eeb0 50%,
    #00ffff 100%
  ) !important;
  border-radius: 56px;
}

.zoom-level {
  min-width: 28px;
  text-align: center;
  font-weight: 500;
  font-size: 14px;
  color: #333;
}

.placement-viewer {
  flex: 1;
  min-width: 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: auto;
  overflow-x: hidden;

  /* Custom scrollbar */
  &::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }

  &::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
  }

  &::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #57f287 0%, #00eeb0 50%, #00ffff 100%);
    border-radius: 10px;
    transition: background 0.3s ease;
  }

  &::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #3ed671 0%, #00d59a 50%, #00e6e6 100%);
  }

  @media (max-width: 1023px) {
    overflow: visible;
    overflow-x: visible;
  }
}

.placement-divider {
  border-color: #BDD2C8 !important;
}

.placement-thumbnails-scroll {
  /* Custom scrollbar for thumbnails */
  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
  }

  &::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #57f287 0%, #00eeb0 50%, #00ffff 100%);
    border-radius: 10px;
    transition: background 0.3s ease;
  }

  &::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #3ed671 0%, #00d59a 50%, #00e6e6 100%);
  }
}

.thumbnail-page-item {
  cursor: pointer;
  position: relative;
  width: 150px;
  flex-shrink: 0;
  border: 2px solid #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
  background: #fff;
  transition: all 0.2s ease;

  .thumbnail-number {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0, 0, 0, 0.7);
    color: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    z-index: 1;
  }

  :deep(.vue-pdf-embed) {
    width: 100% !important;

    > div {
      width: 100% !important;
      box-shadow: none !important;
      margin: 0 !important;
    }

    canvas,
    img,
    svg {
      width: 100% !important;
      height: auto !important;
      display: block;
    }
  }

  &.is-active {
    border-color: #4285f4;
    box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
  }

  &:hover {
    border-color: #4285f4;
    box-shadow: 0 2px 8px rgba(66, 133, 244, 0.3);
  }
}

.bg-page {
  background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
}

.alert-no-shrink {
  flex-shrink: 0;
}

/* Estilos del contenedor PDF (similar al modal de placement) */
:deep(.pdf-container-admin) {
  position: relative;
  width: 100%;
  min-height: 100%;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  background-color: transparent;
}

.pdf-host {
  margin: auto auto 0 auto;
  max-width: 100%;
  min-width: 200px;
}

:deep(.pdf-host .vue-pdf-embed > div) {
  width: 100% !important;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

:deep(.pdf-host .vue-pdf-embed canvas) {
  width: 100% !important;
  height: auto !important;
  display: block;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  background: #fff;
  margin-bottom: 5px;
}

/* Placeholder de firma (similar al modal de placement) */
:deep(.signature-placeholder-admin) {
  position: absolute;
  z-index: 1000;
  cursor: pointer;
  display: flex;
  justify-content: start;
}

:deep(.signature-placeholder-content) {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: solid 1px #6e9383;
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 56px;
  padding: 8px 16px;
  color: #6e9383;
  font-weight: 500;
  font-size: 15px;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.2s ease;

  &:hover {
    border-color: #416054;
    color: #416054;
  }
}

.signing-card-title {
  font-size: 1rem;
  font-weight: 500;
  color: #333;
}

/* Posicionamiento para la firma estática */
.static-signature-position.align-left {
  bottom: 12%;
  left: 25%;
  top: auto;
  transform: translate(-50%, -50%);
}

.static-signature-position.align-right {
  bottom: 15%;
  left: 75%;
  top: auto;
  transform: translate(-50%, -50%);
}

.signature-pad-wrapper {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 4px;
}

canvas {
  width: 100%;
  height: 200px;
  display: block;
}

/* Responsive */
@media (max-width: 1023px) {
  .text-help {
    font-weight: 400;
    font-size: 14px;
    line-height: 24px;
    letter-spacing: 0;
    color: #454545;
  }

  .signing-header {
    justify-content: center;
  }

  .signing-card {
    max-height: none;
    max-width: 100%;
  }

  .placement-body {
    flex-direction: column;
    padding: 12px;
    overflow: visible;
    gap: 16px;
  }

  .placement-sidebar {
    width: 100%;
    flex-direction: column;
  }

  .placement-viewer {
    padding: 0;
    overflow: visible;
  }

  /* Asegurar que el PDF se escale correctamente en mobile */
  :deep(.pdf-container-admin) {
    padding: 0;
    overflow-x: hidden;
  }

  .pdf-host {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
  }

  :deep(.pdf-host .vue-pdf-embed > div) {
    gap: 8px;
  }

  :deep(.pdf-host .vue-pdf-embed canvas) {
    box-shadow: none;
  }

  .static-signature-position.align-left {
    bottom: 10%;
  }

  .static-signature-position.align-right {
    bottom: 12%;
  }
}
</style>

<route lang="yaml">
  meta:
    layout: blank
    redirectIfLoggedIn: false
    public: true    # opcional, claro indicador de que es pública
</route>