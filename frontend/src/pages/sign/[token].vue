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

// --- Lógica del Tema (para el lienzo de firma) ---
const { global } = useTheme()

// --- PDF Computed Properties ---
const pdfQuality = computed(() => {
  const dpr = typeof window !== 'undefined' ? (window.devicePixelRatio || 1) : 1
  return Math.min(3, Math.max(2, dpr))
})

const pdfPageAspect = computed(() => {
  const w = Number(pdfPageSize.value?.width || 0)
  const h = Number(pdfPageSize.value?.height || 0)
  if (!w || !h) return 1
  return w / h
})

const pdfFitPageWidth = computed(() => {
  const isMobile = window.innerWidth < 1024
  const vw = Math.max(100, Number(pdfViewportSize.value?.width || 0) - (isMobile ? 8 : 24))
  const vh = Math.max(100, Number(pdfViewportSize.value?.height || 0) - 24)
  const aspect = pdfPageAspect.value
  const numPages = Number(pdfNumPages.value || 0)
  
  // Para múltiples páginas verticales en mobile, usar todo el ancho disponible
  if (numPages > 1 && isMobile) {
    return vw
  }
  
  // Para múltiples páginas en desktop, solo constrain por el ancho
  if (numPages > 1) {
    return vw
  }
  
  // Para una sola página, calcular basado en altura disponible
  const heightBasedWidth = vh * aspect
  return Math.min(vw, heightBasedWidth)
})

const pdfVisualWidth = computed(() => {
  const isMobile = window.innerWidth < 1024
  const viewportWidth = Number(pdfViewportSize.value?.width || 0)
  const fitWidth = pdfFitPageWidth.value
  
  // If viewport is not ready, return safe default
  if (viewportWidth < 100) return 700
  
  // En mobile, usar el 100% del ancho sin márgenes para evitar scroll horizontal
  if (isMobile) {
    return viewportWidth
  }
  
  // Calculate final width for desktop
  const calculatedWidth = Math.round(fitWidth * pdfZoom.value)
  const margin = 40
  return Math.max(300, Math.min(calculatedWidth, viewportWidth - margin))
})

const pdfRenderScale = computed(() => {
  const pageW = Number(pdfPageSize.value?.width || 0)
  if (!pageW) return 1
  const visualScale = pdfVisualWidth.value / pageW
  return visualScale * pdfQuality.value
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
  pdfResizeObserver = new ResizeObserver(() => updatePdfViewportSize())
  pdfResizeObserver.observe(el)
  updatePdfViewportSize()
}

const handlePdfLoaded = async (doc) => {
  try {
    pdfDoc.value = doc
    pdfNumPages.value = Number(doc?.numPages || 0)

    const page = await doc.getPage(1)
    const viewport = page.getViewport({ scale: 1 })
    pdfPageSize.value = { width: viewport.width, height: viewport.height }
    
    await nextTick()
    
    // Calcular posición del placeholder después de que el PDF se haya cargado
    if (!isAlreadySigned.value) {
      setTimeout(() => calculatePlaceholderPosition(false), 300)
      setTimeout(() => calculatePlaceholderPosition(true), 1500)
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
    setTimeout(() => calculatePlaceholderPosition(false), 100)
  }
})

// Watch pdfPageSize to recalculate position when PDF loads
watch(pdfPageSize, () => {
  if (!isAlreadySigned.value && signaturePlacement.value.visible) {
    setTimeout(() => calculatePlaceholderPosition(false), 100)
  }
}, { deep: true })

onBeforeUnmount(() => {
  if (pdfResizeObserver) pdfResizeObserver.disconnect()
})

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
      return true
    } else if (response.data.status === 'expired') {
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
        title: 'Länk utgången',
        message: 'Denna signeringslänk har löpt ut.',
      }
      return false
    } else if (response.data.status === 'failed') {
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
        title: 'Signeringen misslyckades',
        message: 'Ett fel uppstod under signeringsprocessen. Vänligen kontakta support.',
      }
      return false
    } else if (response.data.status === 'delivery_issues') {
      finalState.value = {
        type: 'warning',
        icon: 'mdi-email-alert-outline',
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
      icon: 'mdi-alert-circle-outline',
      title: 'Ogiltig länk',
      message: error.message || 'Denna signeringslänk är ogiltig.',
    }
    
    return false
  }
}

// Cargar el PDF según el estado
const loadPdf = async () => {
  try {
    let pdfResponse
    
    if (isAlreadySigned.value) {
      // Cargar el PDF firmado
      pdfResponse = await signaturesStore.getSignedPdf(props.token)
    } else {
      // Cargar el PDF sin firmar
      pdfResponse = await signaturesStore.getUnsignedPdf(props.token)
    }
    
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
const calculatePlaceholderPosition = (shouldScroll = false) => {
  if (!signaturePlacement.value.visible) {
    return
  }

  const container = pdfContainer.value
  if (!container) {
    setTimeout(() => calculatePlaceholderPosition(shouldScroll), 200)
    return
  }

  const pages = Array.from(container.querySelectorAll('canvas, svg, img'))
  if (pages.length === 0) {
    setTimeout(() => calculatePlaceholderPosition(shouldScroll), 200)
    return
  }

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
  const isMobile = window.innerWidth < 1024
  const containerPaddingLeft = isMobile ? 0 : 0
  const containerPaddingTop = isMobile ? 0 : 0
  
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
    if (isMobile) {
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
    if (isMobile) {
      baseScale = isLandscape ? 0.4 : 0.7  // 40% para horizontal, 70% para vertical
    }
    const scale = pdfZoom.value * baseScale
    computedPlacement.value = { left: absoluteX, top: absoluteY, scale }
  }

  // Desplazar la VENTANA para que el placeholder quede visible
  if (shouldScroll) {
    setTimeout(() => {
      const placeholder = document.querySelector('.signature-placeholder-admin')
      if (placeholder) {
        const rect = placeholder.getBoundingClientRect()
        const offsetFactor = window.innerWidth <= 768 ? 0.5 : 0.35
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
      await logPageView()
    }
    
    // Cargar el PDF
    await loadPdf()
    
    // Inicializar el ResizeObserver después de cargar el PDF
    await nextTick()
    setTimeout(() => {
      initPdfResizeObserver()
    }, 100)
    
    // Si ya está firmado, solo esperar a que se renderice el PDF
    if (isAlreadySigned.value) {
      setTimeout(() => {
        updatePdfViewportSize()
      }, 300)
    }
    
    // Solo cargar detalles de firma si no está firmado
    if (!isAlreadySigned.value) {
      await loadSignatureDetails()
      
      // Esperar a que el PDF se renderice y calcular posición
      await nextTick()
      // Primer cálculo sin scroll - esperar más para que el PDF se renderice completamente
      setTimeout(() => {
        calculatePlaceholderPosition(false)
      }, 500)
      
      // Segundo cálculo para asegurar que el PDF está completamente renderizado
      setTimeout(() => {
        calculatePlaceholderPosition(false)
      }, 1000)
      
      // Después de que el video termine (aprox 3 segundos), hacer scroll al placeholder
      setTimeout(() => {
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
    }
    
  } catch (error) {
    console.error("Ett fel uppstod:", error)
    if (!finalState.value) {
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
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
    if (pdfSource.value) {
      URL.revokeObjectURL(pdfSource.value)
      pdfSource.value = null
    }

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
      icon: 'mdi-check-circle-outline',
      title: 'Signering slutförd!',
      message: response.data.message,
      downloadUrl: response.data.download_url,
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
      icon: 'mdi-alert-circle-outline',
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
    <LoadingOverlay :is-loading="isRequestOngoing" />

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
              <VAlertTitle>{{ finalState?.title || 'Ditt kontrakt är undertecknat.' }}</VAlertTitle>
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
                  :source="pdfSource"
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
      <VCard class="signing-card pa-4">
        <div class="d-flex align-center flex-0" :class="windowWidth < 1024 ? 'justify-center' : ''">
          <img :src="logo" width="121" height="40" alt="Billogg" />
        </div>
        <VAlert
          color="error"
          class="alert-no-shrink custom-alert mt-4"
        >
          <VAlertTitle>{{ finalState.message }}</VAlertTitle>
        </VAlert>
      </VCard>
    </div>

    <!-- Visor de PDF para firma (cuando no está firmado) -->
    <div v-if="!isRequestOngoing && !finalState && !isAlreadySigned" class="signing-card placement-modal-card">
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

            <!-- Miniaturas de páginas -->
            <div
              v-if="pdfSource && pdfNumPages >= 1"
              class="my-4 flex-grow-1 overflow-auto placement-thumbnails-scroll"
              :class="windowWidth < 1024 ? 'd-none' : ''"
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
                    :width="130"
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
                  :key="`pdf-${pdfSource}`"
                  ref="pdfViewerRef"
                  :source="pdfSource"
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
  background: transparent;
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
  overflow: hidden;
  background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);

  @media (max-width: 1023px) {
    height: auto;
    min-height: 100vh;
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
    > div {
      box-shadow: none !important;
      margin: 0 !important;
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