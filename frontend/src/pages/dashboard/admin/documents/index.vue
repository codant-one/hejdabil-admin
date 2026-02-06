<script setup>

import { computed, inject, nextTick, onBeforeUnmount, onMounted, ref, watch, watchEffect } from 'vue'
import { useDisplay } from "vuetify";
import { useSignableDocumentsStores } from '@/stores/useSignableDocuments'
import { useClientsStores } from '@/stores/useClients'
import { useNotificationsStore } from '@/stores/useNotifications'
import { requiredValidator, emailValidator } from '@/@core/utils/validators'
import { themeConfig } from '@themeConfig'
import { avatarText, formatDate, formatDateTime, formatDateYMD } from '@/@core/utils/formatters'
import { excelParser } from '@/plugins/csv/excelParser'
import { useRoute } from 'vue-router'
import logo from "@images/logos/billogg-logo.svg";
import Toaster from "@/components/common/Toaster.vue";
import VuePdfEmbed from 'vue-pdf-embed'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const documentsStores = useSignableDocumentsStores()
const clientsStores = useClientsStores()
const notificationsStore = useNotificationsStore()
const emitter = inject("emitter")
const route = useRoute()

const { mdAndDown } = useDisplay();
const { width: windowWidth } = useWindowSize();
const sectionEl = ref(null);
const hasLoaded = ref(false);
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const userData = ref(null)
const role = ref(null)
const suppliers = ref([])
const supplier_id = ref(null)
const status = ref(null);
const clients = ref([])
const isFilterDialogVisible = ref(false);
const filtreraMobile = ref(false);

const documents = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalDocuments = ref(0)
const isRequestOngoing = ref(true)
const isConfirmDeleteDialogVisible = ref(false)
const isSignatureDialogVisible = ref(false) 
const signatureEmail = ref('')              
const textEmail = ref(null)
const refSignatureForm = ref()
const selectedDocument = ref({})
const selectedDocumentForAction = ref({});
const isMobileActionDialogVisible = ref(false);

const isPlacementModalVisible = ref(false)
const placementPdfSource = ref(null)
const isLoadingPlacementPdf = ref(false)
const signaturePlacement = ref({ x: 0, y: 0, page: 1, visible: false, pageWidth: 0, pageHeight: 0 })
const pdfPlacementContainer = ref(null)
const pdfViewerRef = ref(null)

// PDF doc cache for thumbnails/navigation
const placementPdfDoc = ref(null)
const placementPdfNumPages = ref(0)
const activePlacementPage = ref(1)

// PDF render sizing (fit-to-page) + high quality render (supersample then CSS downscale)
const pdfViewportEl = ref(null)
const pdfViewportSize = ref({ width: 0, height: 0 })
const pdfPageSize = ref({ width: 0, height: 0 })
const pdfZoom = ref(1)
const minZoom = 0.5
const maxZoom = 3
const zoomStep = 0.25

const pdfQuality = computed(() => {
  const dpr = typeof window !== 'undefined' ? (window.devicePixelRatio || 1) : 1
  // Force oversampling even on 1x displays for sharper output.
  // Clamp to avoid huge canvases.
  return Math.min(3, Math.max(2, dpr))
})

const pdfPageAspect = computed(() => {
  const w = Number(pdfPageSize.value?.width || 0)
  const h = Number(pdfPageSize.value?.height || 0)
  if (!w || !h) return 1
  return w / h
})

// Fit full page inside the visible viewport (like Chrome's "fit page")
const pdfFitPageWidth = computed(() => {
  const vw = Math.max(100, Number(pdfViewportSize.value?.width || 0) - 24)
  const vh = Math.max(100, Number(pdfViewportSize.value?.height || 0) - 24)
  const aspect = pdfPageAspect.value
  const numPages = Number(placementPdfNumPages.value || 0)
  
  // Calculate based on height when single page is confirmed
  if (numPages === 1) {
    const heightBasedWidth = vh * aspect
    return Math.min(vw, heightBasedWidth)
  }
  
  // Default: constrain by both width and height (works for loading and multiple pages)
  return Math.min(vw, vh * aspect)
})

const pdfVisualWidth = computed(() => {
  const viewportWidth = Number(pdfViewportSize.value?.width || 0)
  const fitWidth = pdfFitPageWidth.value
  
  // If viewport is not ready, return safe default
  if (viewportWidth < 100) return 700
  
  // Calculate final width
  const calculatedWidth = Math.round(fitWidth * pdfZoom.value)
  return Math.max(300, Math.min(calculatedWidth, viewportWidth - 40))
})

// Render at higher scale, then force canvas to fit host width via CSS
const pdfRenderScale = computed(() => {
  const pageW = Number(pdfPageSize.value?.width || 0)
  if (!pageW) return 1
  const visualScale = pdfVisualWidth.value / pageW
  return visualScale * pdfQuality.value
})

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

const handlePlacementPdfLoaded = async (pdfDoc) => {
  try {
    placementPdfDoc.value = pdfDoc
    placementPdfNumPages.value = Number(pdfDoc?.numPages || 0)

    const page = await pdfDoc.getPage(1)
    const viewport = page.getViewport({ scale: 1 })
    pdfPageSize.value = { width: viewport.width, height: viewport.height }
    
    // Hide loading after data is ready
    await nextTick()
    isLoadingPlacementPdf.value = false
  } catch (e) {
    console.error('Error loading placement PDF:', e)
    isLoadingPlacementPdf.value = false
  }
}

const scrollToPlacementPage = async (page) => {
  const nextPage = Math.max(1, Math.min(Number(page || 1), Number(placementPdfNumPages.value || 1)))
  activePlacementPage.value = nextPage

  await nextTick()

  const container = pdfPlacementContainer.value
  if (!container) return

  const canvases = Array.from(container.querySelectorAll('canvas'))
  const target = canvases[nextPage - 1]
  if (!target) return

  target.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const zoomIn = () => {
  if (pdfZoom.value < maxZoom) pdfZoom.value = Math.min(pdfZoom.value + zoomStep, maxZoom)
}

const zoomOut = () => {
  if (pdfZoom.value > minZoom) pdfZoom.value = Math.max(pdfZoom.value - zoomStep, minZoom)
}

const resetZoom = () => {
  // Return to fit-to-page
  pdfZoom.value = 1
}

watchEffect(async () => {
  if (!isPlacementModalVisible.value) return
  await nextTick()
  initPdfResizeObserver()
})

onBeforeUnmount(() => {
  if (pdfResizeObserver) pdfResizeObserver.disconnect()
})

const isUploadModalVisible = ref(false)
const uploadForm = ref(null)
const uploadTitle = ref('')
const uploadFile = ref(null)
const uploadFileInput = ref(null)
const hiddenFileInput = ref(null)
const fileValidationError = ref('')

// Send document dialog refs
const isConfirmSendDocumentDialogVisible = ref(false)
const sendDocumentEmail = ref('')
const sendDocumentForm = ref(null)
const documentToSend = ref(null)
const selectedClientId = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = documents.value.length 
    ? (currentPage.value - 1) * rowPerPage.value + 1 
    : 0
  const lastIndex = 
    documents.value.length + (currentPage.value - 1) * rowPerPage.value
  return `${totalDocuments.value} resultat`;
  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalDocuments.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

// Computed para detectar si hay documentos activos esperando interacción
const hasActiveDocuments = computed(() => {
  return documents.value.some(doc => 
    ['sent', 'delivered', 'reviewed'].includes(doc.token?.signature_status)
  )
})

// Verificación silenciosa de cambios sin activar spinner
const checkForUpdates = async () => {
  try {
    let data = {
      search: searchQuery.value,
      orderByField: 'created_at',
      orderBy: 'desc',
      limit: rowPerPage.value,
      page: currentPage.value,
      supplier_id: supplier_id.value,
      status: documentsStores.getStatus ?? status.value
    }

    // Hacer la petición silenciosa (sin cambiar isRequestOngoing)
    await documentsStores.fetchDocuments(data)
    
    const newDocuments = documentsStores.getDocuments
    
    // Crear un mapa de documentos viejos por ID con su signature_status
    const oldDocsMap = new Map()
    documents.value.forEach(doc => {
      const oldStatus = doc.token?.signature_status || 'pending'
      const oldHistoryLength = doc.token?.histories?.length || 0
      
      oldDocsMap.set(doc.id, {
        status: oldStatus,
        historyLength: oldHistoryLength
      })
    })
    
    // Verificar cambios en signature_status o historial
    let hasChanges = false
    
    for (const newDoc of newDocuments) {
      const newStatus = newDoc.token?.signature_status || 'pending'
      const newHistoryLength = newDoc.token?.histories?.length || 0
      const oldDoc = oldDocsMap.get(newDoc.id)
      
      // Si es un documento nuevo en la lista
      if (!oldDoc) {
        hasChanges = true
        break
      }
      
      // Si cambió el status y es un status activo (no signed)
      if (oldDoc.status !== newStatus) {
        // Solo nos interesan cambios hacia estados activos o desde estados activos
        if (['sent', 'delivered', 'reviewed'].includes(newStatus) || 
            ['sent', 'delivered', 'reviewed'].includes(oldDoc.status)) {
          hasChanges = true
          break
        }
      }
      
      // Si cambió el historial (nuevo evento como 'reviewed')
      if (oldDoc.historyLength !== newHistoryLength) {
        hasChanges = true
        break
      }
    }
    
    return hasChanges
  } catch (error) {
    console.error('[Documents] Error checking for updates:', error)
    return false
  }
}

onMounted(async () => {
  status.value = documentsStores.getStatus ?? status.value;
  updateStatus(status.value);

  await fetchData()
  
  // Escuchar notificaciones y refrescar datos cuando llegue una relacionada con documentos
  notificationsStore.onNotificationReceived(async (notification) => {
    
    // Si la notificación tiene una ruta relacionada con documentos, refrescar
    if (notification.route && notification.route.includes('/documents')) {
      
      // Si el tracker está abierto, actualizar también el documento actual
      if (isTrackerDialogVisible.value && trackerDocument.value?.id) {
        try {
          const response = await documentsStores.showDocument(trackerDocument.value.id)
          trackerDocument.value = response
        } catch (e) {
          console.error('Failed to refresh tracker document in real-time', e)
        }
      }
    } else {
      console.warn('[Documents] Route does not match /documents criteria:', notification.route)
    }
  })

  // Polling inteligente: solo activo cuando hay documentos esperando firma
  let pollingInterval = null
  
  const startPolling = () => {
    if (pollingInterval) return // Ya está corriendo
    
    pollingInterval = setInterval(async () => {
      // Solo hacer polling si:
      // 1. El tracker está visible O
      // 2. Hay documentos activos esperando interacción
      if (isTrackerDialogVisible.value && trackerDocument.value?.id) {
        try {
          const response = await documentsStores.showDocument(trackerDocument.value.id)
          const currentHistoryLength = trackerDocument.value?.token?.histories?.length || 0
          const newHistoryLength = response?.token?.histories?.length || 0
          
          if (newHistoryLength > currentHistoryLength) {
            trackerDocument.value = response
            // Llamar a fetchData con spinner ya que sabemos que hay cambios
            await fetchData()
          }
        } catch (e) {
          console.error('Failed to poll tracker updates:', e)
        }
      } else if (hasActiveDocuments.value) {
        // Verificar cambios sin spinner
        const hasChanges = await checkForUpdates()
        // Si hay cambios, llamar a fetchData para actualización completa
        if (hasChanges) {
          await fetchData()
        }
      }
    }, 5000) // Poll every 5 seconds
    
    window._trackerPollingInterval = pollingInterval
  }
  
  const stopPolling = () => {
    if (pollingInterval) {
      clearInterval(pollingInterval)
      pollingInterval = null
      window._trackerPollingInterval = null
    }
  }
  
  // Iniciar polling si hay documentos activos
  if (hasActiveDocuments.value) {
    startPolling()
  } else {
    //console.log('[Documents] No active documents on mount')
  }
  
  // Watch para iniciar/detener polling según haya documentos activos
  watch(hasActiveDocuments, (hasActive) => {
    if (hasActive) {
      startPolling()
    } else {
      stopPolling()
    }
  })
  
  // Detener polling cuando la pestaña está oculta, reanudar cuando vuelve
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopPolling()
    } else {
      // Reiniciar polling si es necesario
      if (hasActiveDocuments.value) {
        startPolling()
      }
    }
  })
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {
  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1;
    supplier_id.value = null;
    status.value = null;
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'created_at',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value,
    status: documentsStores.getStatus ?? status.value,
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true
  isFilterDialogVisible.value = false;

  await documentsStores.fetchDocuments(data)

  documents.value = documentsStores.getDocuments
  totalPages.value = documentsStores.last_page
  totalDocuments.value = documentsStores.documentsTotalCount
  
  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value?.roles?.[0]?.name ?? null

  if(role.value === 'SuperAdmin' || role.value === 'Administrator') {
    suppliers.value = documentsStores.getSuppliers
  }

  // Fetch clients for send document dialog
  await clientsStores.fetchClients({ limit: -1 })
  clients.value = clientsStores.getClients

  hasLoaded.value = true
  isRequestOngoing.value = false
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const showDeleteDialog = documentData => {
  isConfirmDeleteDialogVisible.value = true
  selectedDocument.value = { ...documentData }
}

const removeDocument = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await documentsStores.deleteDocument(selectedDocument.value.id)
  selectedDocument.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Dokument borttaget!' : res.data.message,
    show: true
  }

  await fetchData()

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const download = async(document_) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + document_.file);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = document_.file.replace('documents/', '');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

  } catch (error) {
    console.error('Error:', error);
  }
};

const openSendDocumentDialog = (document_) => {
  documentToSend.value = document_
  sendDocumentEmail.value = ''
  selectedClientId.value = null
  isConfirmSendDocumentDialogVisible.value = true
}

const selectClient = (clientId) => {
  if (clientId) {
    const client = clients.value.find(c => c.id === clientId)
    if (client && client.email) {
      sendDocumentEmail.value = client.email
    }
  } else {
    // Clear email when no client is selected
    sendDocumentEmail.value = ''
    sendDocumentForm.value?.resetValidation()
  }
}

const handleSendDocument = () => {
  sendDocumentForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()
      formData.append('ids', documentToSend.value.id)
      formData.append('email', sendDocumentEmail.value)

      isConfirmSendDocumentDialogVisible.value = false
      isRequestOngoing.value = true

      documentsStores.sendDocument(formData)
        .then((res) => {
          if (res.data.success) {
            advisor.value = {
              type: 'success',
              message: 'Dokumentet har skickats!',
              show: true
            }
          }
          isRequestOngoing.value = false
        })
        .catch((err) => {
          advisor.value = {
            type: 'error',
            message: err.message || 'Ett fel inträffade',
            show: true
          }
          isRequestOngoing.value = false
        })

      setTimeout(() => {
        sendDocumentEmail.value = ''
        documentToSend.value = null
        selectedClientId.value = null
        advisor.value = {
          type: '',
          message: '',
          show: false
        }
      }, 3000)
    }
  })
}

const openLink = function (documentData) {
  window.open(themeConfig.settings.urlStorage + documentData.file)
}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await documentsStores.fetchDocuments(data)

  let dataArray = [];

  documentsStores.getDocuments.forEach(element => {

    const createdAt = element.created_at ? new Date(element.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) : ''

    let data = {
      TITEL: element.title ?? '',
      SKAPAD: createdAt,
      SKAPAD_AV: (element.user?.name ?? '') + ' ' + (element.user?.last_name ?? ''),
      SIGNATUR_STATUS: element.token?.signature_status ?? 'pending',
      MOTTAGARE: element.token ? element.token.recipient_email : '',
      FIL: element.file ? element.file.split('/').pop() : ''
    }

    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "documents", "csv");

  isRequestOngoing.value = false

}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + "...";
  }
  return text;
};

const goToTracker = (documentData) => {
  openTracker(documentData)
}

// Tracker Logic
const isTrackerDialogVisible = ref(false)
const trackerDocument = ref(null)
const isTrackerPreviewVisible = ref(false)
const trackerPreviewPdfSource = ref(null)
const trackerPreviewError = ref('')

const trackerEvents = computed(() => {
  if (!trackerDocument.value) return []

  const items = []
  const latestToken = trackerDocument.value.token ?? null

  // Si tenemos historial de token, usar esos registros
  if (latestToken && latestToken.histories && latestToken.histories.length > 0) {
    const history = [...latestToken.histories].sort((a, b) => new Date(a.id) - new Date(b.id))
    
    // Check if there's a 'signed' event in the history
    const hasSignedEvent = history.some(event => event.event_type === 'signed')
    
    history.forEach(event => {
      const eventConfig = getEventConfig(event.event_type, event)
      if (eventConfig) {
        items.push({
          key: event.event_type,
          title: eventConfig.title,
          meta: new Date(event.created_at).toLocaleString('en-GB', { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: false 
          }),
          text: event.description || eventConfig.text,
          color: eventConfig.color,
          bgClass: eventConfig.bgClass,
          icon: eventConfig.icon,
          showFile: event.event_type === 'signed' || (event.event_type === 'created' && !hasSignedEvent),
          ipAddress: event.ip_address,
          userAgent: event.user_agent
        })
      }
    })
  }
  
  // Assign sides strictly alternating
  return items.map((item, index) => ({
    ...item,
    side: index % 2 === 0 ? 'right' : 'left'
  }))
})

const getEventConfig = (eventType, event) => {
  const configs = {
    'created': {
      title: 'Dokument skapat',
      text: trackerDocument.value?.title || 'Dokument',
      color: '#00EEB0',
      bgClass: 'status-info',
      icon: 'custom-star'
    },
    'sent': {
      title: 'Signeringsförfrågan skickad',
      text: `Skickad till ${event.metadata?.email || 'mottagare'}`,
      color: '#1890FF',
      bgClass: 'status-success',
      icon: 'custom-forward'
    },
    'delivered': {
      title: 'E-post levererad',
      text: 'E-postmeddelandet har levererats framgångsrikt',
      color: '#52C41A',
      bgClass: 'status-success',
      icon: 'custom-check-mark-2'
    },
    'delivery_issues': {
      title: 'Leveransproblem',
      text: 'Det uppstod problem med e-postleveransen',
      color: '#FAAD14',
      bgClass: 'status-warning',
      icon: 'custom-risk'
    },
    'reviewed': {
      title: 'Dokument granskat',
      text: 'Kunden har öppnat och granskat dokumentet',
      color: '#1890FF',
      bgClass: 'status-info',
      icon: 'custom-eye'
    },
    'signed': {
      title: 'Dokument signerat',
      text: 'Signeringen är slutförd',
      color: '#52C41A',
      bgClass: 'status-success',
      icon: 'custom-signature'
    },
    'failed': {
      title: 'Signeringen misslyckades',
      text: 'Ett fel inträffade under signeringsprocessen',
      color: '#FF4D4F',
      bgClass: 'status-error',
      icon: 'custom-close'
    }
  }
  return configs[eventType] || null
}

const openTracker = async (doc) => {
  await fetchData()
  trackerDocument.value = doc
  isTrackerDialogVisible.value = true
  
  try {
    const response = await documentsStores.showDocument(doc.id)
    trackerDocument.value = response
  } catch (e) {
    console.error('Failed to refresh document details', e)
  }
}

const openTrackerPreview = async () => {
  if (!trackerDocument.value) return
  isTrackerPreviewVisible.value = true
  isRequestOngoing.value = true
  trackerPreviewError.value = ''
  try {
    const response = await documentsStores.getAdminPreviewPdf(trackerDocument.value.id)
    trackerPreviewPdfSource.value = URL.createObjectURL(response.data)
  } catch (e) {
    trackerPreviewError.value = 'Kunde inte ladda PDF-förhandsvisning.'
  } finally {
    isRequestOngoing.value = false
  }
}

watch(isTrackerPreviewVisible, val => {
  if (!val && trackerPreviewPdfSource.value) {
    URL.revokeObjectURL(trackerPreviewPdfSource.value)
    trackerPreviewPdfSource.value = null
  }
})

const startPlacementProcess = async (documentData) => {
  // Clear previous PDF first
  if (placementPdfSource.value) {
    URL.revokeObjectURL(placementPdfSource.value)
  }
  placementPdfSource.value = null
  placementPdfDoc.value = null
  placementPdfNumPages.value = 0
  
  selectedDocument.value = { ...documentData };
  isPlacementModalVisible.value = true;
  isLoadingPlacementPdf.value = true;
  signaturePlacement.value.visible = false;

  try {
    const response = await documentsStores.getAdminPreviewPdf(documentData.id)
    placementPdfSource.value = URL.createObjectURL(response.data);
    // Loading will be hidden when handlePlacementPdfLoaded is called
  } catch (error) {
    advisor.value = { type: 'error', message: 'Kunde inte ladda PDF-dokumentet.', show: true };
    isPlacementModalVisible.value = false;
    isLoadingPlacementPdf.value = false;
    setTimeout(() => { advisor.value.show = false }, 3000);
  }
}

const handleAdminPdfClick = (event) => {
  const container = pdfPlacementContainer.value; 
  if (!container) return;

  // Verificar que el clic sea directamente en un canvas del PDF
  const clickedElement = event.target;
  if (clickedElement.tagName !== 'CANVAS') {
    // Si no se hizo clic en un canvas, buscar si está dentro de uno
    const pageEl = clickedElement.closest('canvas');
    if (!pageEl) return; // No está dentro de un canvas, salir sin hacer nada
  }

  // Buscar el canvas del PDF donde se hizo clic
  const pages = Array.from(container.querySelectorAll('canvas'))
  const pageEl = event.target.closest('canvas')
  if (!pageEl) return

  const pageRect = pageEl.getBoundingClientRect();
  const containerRect = container.getBoundingClientRect();

  // Dimensiones visibles reales (CSS px). Importante si el canvas se redimensiona por CSS.
  const contentWidth = pageRect.width;
  const contentHeight = pageRect.height;

  // Coordenadas del clic relativas al canvas (CSS px)
  const localX = event.clientX - pageRect.left;
  const localY = event.clientY - pageRect.top;

  // Validar que el clic esté dentro de los límites del canvas
  if (localX < 0 || localX > contentWidth || localY < 0 || localY > contentHeight) {
    return; // Clic fuera de los límites del canvas
  }

  // Detectar si el PDF es horizontal (landscape) o vertical (portrait)
  const isLandscape = contentWidth > contentHeight;
  
  // Calcular scale según orientación (igual que en [token].vue para mobile)
  const isMobile = window.innerWidth < 1024;
  let baseScale = 1;
  if (isMobile) {
    baseScale = isLandscape ? 0.4 : 0.7;  // 40% para horizontal, 70% para vertical
  }

  // Dimensiones base del botón (antes del scale)
  const baseButtonWidth = 150;
  const baseButtonHeight = 40;
  
  // Dimensiones reales del botón después de aplicar el scale
  const buttonWidth = baseButtonWidth * baseScale;
  const buttonHeight = baseButtonHeight * baseScale;
  const margin = 5;

  // Ajustar coordenadas para que el botón no se salga del canvas
  let adjustedLocalX = localX;
  let adjustedLocalY = localY;

  // Ajustar X si el botón se sale por la derecha
  if (localX + buttonWidth > contentWidth - margin) {
    adjustedLocalX = contentWidth - buttonWidth - margin;
  }
  // Ajustar X si el botón se sale por la izquierda
  if (localX < margin) {
    adjustedLocalX = margin;
  }

  // Ajustar Y si el botón se sale por abajo
  if (localY + buttonHeight > contentHeight - margin) {
    adjustedLocalY = contentHeight - buttonHeight - margin;
  }
  // Ajustar Y si el botón se sale por arriba
  if (localY < margin) {
    adjustedLocalY = margin;
  }

  // Coordenadas absolutas dentro del contenedor (para mostrar el placeholder)
  const absoluteX = (pageRect.left - containerRect.left) + adjustedLocalX + (container.scrollLeft || 0);
  const absoluteY = (pageRect.top - containerRect.top) + adjustedLocalY + (container.scrollTop || 0);

  const pageIndex = Math.max(0, pages.indexOf(pageEl))

  signaturePlacement.value = {
    x: adjustedLocalX,
    y: adjustedLocalY,
    absoluteX,
    absoluteY,
    page: pageIndex + 1,
    visible: true,
    pageWidth: contentWidth,
    pageHeight: contentHeight,
    scale: baseScale,
  }
}

const openSignatureDialog = () => {
  signatureEmail.value = ''
  textEmail.value = null
  isSignatureDialogVisible.value = true
}

// Re-send signature request using existing token (same URL)
const openResendSignature = async (documentData) => {
  try {
    isRequestOngoing.value = true
    const response = await documentsStores.resendSignature(documentData.id)
    advisor.value = { type: 'success', message: response.data.message || 'E-postmeddelandet har skickats igen.', show: true }
    await fetchData()
  } catch (error) {
    advisor.value = { type: 'error', message: error.response?.data?.message || 'Det gick inte att vidarebefordra e-postmeddelandet.', show: true }
  } finally {
    isRequestOngoing.value = false
    setTimeout(() => { advisor.value = { show: false } }, 3000)
  }
}

const handleSignatureSubmit = async () => {
  const { valid } = await refSignatureForm.value?.validate()

  if (!valid) return

  isSignatureDialogVisible.value = false
  isPlacementModalVisible.value = false
  isRequestOngoing.value = true
  
  try {
    const container = pdfPlacementContainer.value;
    if (!container) {
      throw new Error("Referensen till PDF-filen kunde inte hittas.");
    }

    // Usar las dimensiones reales de la página capturadas en el click
    const baseWidth = signaturePlacement.value.pageWidth || container.offsetWidth;
    const baseHeight = signaturePlacement.value.pageHeight || container.offsetHeight;

    const x_percent = (signaturePlacement.value.x / baseWidth) * 100;
    const y_percent = (signaturePlacement.value.y / baseHeight) * 100;
    
    const payload = {
      documentId: selectedDocument.value.id,
      email: signatureEmail.value,
      x: x_percent.toFixed(4),
      y: y_percent.toFixed(4),
      page: signaturePlacement.value.page,
      alignment: 'left',
      text: textEmail.value
    }

    const response = await documentsStores.requestSignature(payload)
    
    advisor.value = {
      type: 'success',
      message: response.data.message || 'Signeringsförfrågan har skickats!',
      show: true,
    }
    
  } catch (error) {
    advisor.value = {
      type: 'error',
      message: error.response?.data?.message || 'Ett fel uppstod när begäran skickades.',
      show: true,
    }
    console.error('Error sending signature request:', error?.response || error)
  } finally {
    await fetchData()
    isRequestOngoing.value = false
    signatureEmail.value = ''
    textEmail.value = null
    setTimeout(() => {
      advisor.value = { show: false } 
    }, 3000)
  }
}

const handlePlacementModalClose = (value) => {
  if (!value) {
    if (placementPdfSource.value) {
      URL.revokeObjectURL(placementPdfSource.value);
    }
    placementPdfSource.value = null;
    placementPdfDoc.value = null
    placementPdfNumPages.value = 0
    activePlacementPage.value = 1
  }
}

const openUploadModal = () => {
  isUploadModalVisible.value = true
  uploadTitle.value = ''
  uploadFile.value = null
  fileValidationError.value = ''
  if (uploadFileInput.value) {
    uploadFileInput.value.value = ''
  }
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (file.type !== 'application/pdf') {
      advisor.value = {
        type: 'error',
        message: 'Endast PDF-filer är tillåtna.',
        show: true
      }
      setTimeout(() => {
        advisor.value.show = false
      }, 3000)
      return
    }
    uploadFile.value = file
    fileValidationError.value = ''
  }
}

const handleFileDrop = (event) => {
  const file = event.dataTransfer.files[0]
  if (file) {
    if (file.type !== 'application/pdf') {
      advisor.value = {
        type: 'error',
        message: 'Endast PDF-filer är tillåtna.',
        show: true
      }
      setTimeout(() => {
        advisor.value.show = false
      }, 3000)
      return
    }
    uploadFile.value = file
    fileValidationError.value = ''
  }
}

const submitUpload = async () => {
  const { valid } = await uploadForm.value?.validate()
  
  // Validate file
  fileValidationError.value = ''
  if (!uploadFile.value) {
    fileValidationError.value = 'Detta fält är obligatoriskt.'
  }

  if (!valid || !uploadFile.value) return

  isRequestOngoing.value = true
  isUploadModalVisible.value = false

  try {
    const formData = new FormData()
    formData.append('title', uploadTitle.value)
    formData.append('file', uploadFile.value)

    const response = await documentsStores.addDocument(formData)

    advisor.value = {
      type: 'success',
      message: response.data.message || 'Dokumentet har skapats!',
      show: true,
    }

    await fetchData()

    setTimeout(() => {
      advisor.value = { show: false }
    }, 3000)
  } catch (error) {
    advisor.value = {
      type: 'error',
      message: error.response?.data?.message || 'Ett fel uppstod när dokumentet skapades.',
      show: true,
    }
    setTimeout(() => {
      advisor.value = { show: false }
    }, 3000)
  } finally {
    isRequestOngoing.value = false
  }
}

const updateStatus = (newStatus) => {
  // Si ya está seleccionado, desmarcarlo (poner null)
  if (status.value === newStatus) {
    newStatus = null;
  }

  documentsStores.setStatus(newStatus);
  status.value = newStatus;
  filtreraMobile.value = false;
};

const resolveStatus = state => {
  if (state === 'created')
    return { 
      name: 'Skapad',
      class: 'info',
      icon: 'custom-star'
    }
  if (state === 'sent')
    return { 
      name: 'Skickad',
      class: 'success',
      icon: 'custom-forward'
    }
  if (state === 'signed')
    return { 
      name: 'Signerad',
      class: 'success',
      icon: 'custom-signature'
    }
  if (state === 'pending')
    return { 
      name: 'Skapad',
      class: 'info',
      icon: 'custom-star'
    }
  if (state === 'delivered')
    return { 
      name: 'Levererad',
      class: 'success',
      icon: 'custom-check-mark-2'
    }
  if (state === 'reviewed')
    return { 
      name: 'Granskad',
      class: 'info',
      icon: 'custom-eye'
    }
  if (state === 'delivery_issues')
    return { 
      name: 'Leveransproblem',
      class: 'pending',
      icon: 'custom-risk'
    }
  if (state === 'failed')
    return { 
      name: 'Misslyckades',
      class: 'error',
      icon: 'custom-close'
    }
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

// Watch for route changes to open tracker automatically
watch(() => route.query.file_id, async (fileId) => {
  if (fileId && hasLoaded.value) {
    const documentId = parseInt(fileId)
    let document = documents.value.find(doc => doc.id === documentId)
    
    if (!document) {
      // If not found in current page, try to fetch it directly
      try {
        document = await documentsStores.showDocument(documentId)
      } catch (error) {
        console.error('Document not found:', error)
        advisor.value = {
          type: 'error',
          message: 'Dokumentet kunde inte hittas.',
          show: true
        }
        setTimeout(() => {
          advisor.value.show = false
        }, 3000)
        return
      }
    }
    
    if (document) {
      await goToTracker(document)
    }
  }
}, { immediate: true })

// Watch hasLoaded to trigger file_id check after data is loaded
watch(hasLoaded, async (loaded) => {
  if (loaded && route.query.file_id) {
    const documentId = parseInt(route.query.file_id)
    let document = documents.value.find(doc => doc.id === documentId)
    
    if (!document) {
      try {
        document = await documentsStores.showDocument(documentId)
      } catch (error) {
        console.error('Document not found:', error)
        advisor.value = {
          type: 'error',
          message: 'Dokumentet kunde inte hittas.',
          show: true
        }
        setTimeout(() => {
          advisor.value.show = false
        }, 3000)
        return
      }
    }
    
    if (document) {
      await goToTracker(document)
    }
  }
})

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
  
  // Limpiar listeners de notificaciones
  notificationsStore.offNotificationReceived()
  
  // Limpiar el polling interval
  if (window._trackerPollingInterval) {
    clearInterval(window._trackerPollingInterval)
    window._trackerPollingInterval = null
  }
});
</script>

<template>
  <section class="page-section" ref="sectionEl">
    <LoadingOverlay :is-loading="isRequestOngoing" />

    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
    >
      {{ advisor.message }}
    </VSnackbar>      

    <Toaster />

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
         <div class="align-center font-blauer">
          <h2>Dokument <span v-if="hasLoaded">({{ documents.length }})</span></h2>
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

        <div class="d-flex gap-4">
          <VBtn
            class="btn-light w-auto"
            block
            @click="downloadCSV">
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

          <VBtn
            v-if="$can('create', 'signed-documents')"
            class="btn-gradient"
            block
            @click="openUploadModal"
          >
            <VIcon icon="custom-plus" size="24" />
            Skapa
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-2"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-2'">
          <AppAutocomplete
            v-if="role !== 'Supplier' && hasLoaded"
            prepend-icon="custom-profile"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="(item) => item.full_name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate"
          />
        </div>

        <VBtn
          class="btn-white-2 px-3"
          v-if="role !== 'Supplier' && role !== 'User'"
          @click="isFilterDialogVisible = true"
          :class="windowWidth > 1023 ? 'd-none' : 'd-flex'"
        >
          <VIcon icon="custom-profile" size="24" />
        </VBtn>

        <VBtn
          class="btn-white-2 px-3"
          @click="filtreraMobile = true"
          v-if="$vuetify.display.mdAndDown"
        >
          <VIcon icon="custom-filter" size="24" />
          <span class="d-none d-md-block">Filtrera efter</span>
        </VBtn>

        <VMenu v-if="!$vuetify.display.mdAndDown">
          <template #activator="{ props }">
            <VBtn class="btn-white-2 px-2" v-bind="props">
              <VIcon icon="custom-filter" size="24" />
              <span class="d-none d-md-block">Filtrera efter</span>
            </VBtn>
          </template>
          <VList>
            <VListItem @click="updateStatus('created')">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="status === 'created'"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Skapad</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStatus('delivered')">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="status === 'delivered'"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Levererad</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStatus('delivery_issues')">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="status === 'delivery_issues'"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Leveransproblem</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStatus('reviewed')">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="status === 'reviewed'"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Granskad</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStatus('signed')">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="status === 'signed'"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Signerad</VListItemTitle>
            </VListItem>
          </VList>
        </VMenu>

        <div
          v-if="!$vuetify.display.mdAndDown"
          class="d-flex align-center visa-select"
        >
          <span class="text-no-wrap pr-4">Visa</span>
          <VSelect
            v-model="rowPerPage"
            class="custom-select-hover"
            :items="[10, 20, 30, 50]"
          />
        </div>
      </VCardText>

      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="documents.length"
        class="pt-2 px-4 pb-6 text-no-wrap"
        style="border-radius: 0 !important"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col">#ID</th>
            <th scope="col">Titel</th>
            <th scope="col" class="text-center">Skapad</th>
            <th scope="col" v-if="role !== 'Supplier' && role !== 'User'">Leverantör</th>
            <th scope="col" class="text-center">Signera status</th>
            <th scope="col">Skapad av</th>
            <th scope="col" v-if="$can('edit', 'signed-documents') || $can('delete', 'signed-documents')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="documents.length">
          <tr v-for="document in documents" :key="document.id">
            <td>
              <span>{{ document.order_id }}</span>
            </td>
            <td class="w-100">
              <span>{{ document.title }}</span>
            </td>
            <td class="text-center">
              {{ formatDateTime(document.created_at) }}
            </td>
            <td style="width: 1%; white-space: nowrap" v-if="role !== 'Supplier' && role !== 'User'">
               <span v-if="document.supplier">
                {{ document.supplier.user.name }}
                {{ document.supplier.user.last_name ?? "" }}
              </span>
            </td>
            <td class="text-center text-wrap d-flex justify-center align-center" style="width: 180px;">
              <div
                v-if="document.token"
                class="status-chip"
                :class="`status-chip-${resolveStatus(document.token?.signature_status)?.class}`"
              >
                <VIcon size="16" :icon="resolveStatus(document.token?.signature_status)?.icon" class="action-icon" />
                {{ resolveStatus(document.token?.signature_status)?.name }}
              </div>

              <div
                v-else
                class="status-chip"
                :class="`status-chip-${resolveStatus('pending')?.class}`"
              >
                <VIcon size="16" :icon="resolveStatus('pending')?.icon" class="action-icon" />
                 {{ resolveStatus('pending')?.name }}
              </div>
            </td>
            <td style="width: 1%; white-space: nowrap">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  :variant="document.user.avatar ? 'outlined' : 'tonal'"
                  size="38"
                >
                  <VImg
                    v-if="document.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + document.user.avatar"
                  />
                  <span v-else>{{ avatarText(document.user.name) }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ document.user.name }} {{ document.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="document.user.email && document.user.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(document.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ document.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ document.user.email }}</span>
                  </span>
                </div>
              </div>
            </td>            
            <!-- 👉 Actions -->
            <td
              class="text-center"
              style="width: 3rem"
              v-if="$can('edit', 'signed-documents') || $can('delete', 'signed-documents')"
            >
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                    v-if="$can('view','signed-documents')"
                    @click="goToTracker(document)">
                    <template #prepend>
                      <VIcon icon="custom-eye" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Spårare</VListItemTitle>
                  </VListItem>                  
                  <VListItem
                    v-if="
                      $can('edit','signed-documents') && (
                        document.token?.signature_status !== 'sent' && 
                        document.token?.signature_status !== 'signed' && 
                        document.token?.signature_status !== 'delivered' &&
                        document.token?.signature_status !== 'reviewed'
                      )"
                    @click="startPlacementProcess(document)">
                    <template #prepend>
                      <VIcon icon="custom-signature" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Signera</VListItemTitle>
                  </VListItem>                  
                  <VListItem
                    v-if="$can('edit','signed-documents') && document.token?.signature_status === 'delivered'"
                    @click="openResendSignature(document)">
                    <template #prepend>
                      <VIcon icon="custom-forward" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Vidarebefordra</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('view', 'signed-documents')"
                    @click="openLink(document)">
                    <template #prepend>
                      <VIcon icon="custom-pdf" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa som PDF</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('view','signed-documents')" @click="download(document)">
                    <template #prepend>
                      <VIcon icon="custom-download" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('view','signed-documents') && document.token?.signature_status === 'signed'"
                    @click="openSendDocumentDialog(document)">
                    <template #prepend>
                      <VIcon icon="custom-paper-plane" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Sänd PDF</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('delete', 'signed-documents')"
                    @click="showDeleteDialog(document)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-waste" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Ta bort</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </td>
          </tr>
        </tbody>
      </VTable>
      
      <div
        v-if="!isRequestOngoing && hasLoaded && !documents.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-agreement"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga dokument att signera än</div>
          <div class="empty-state-text">
            Ladda upp externa filer för att skicka dem för digital signering. 
            Följ statusen i realtid och samla alla dina signerade avtal på ett ställe.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'signed-documents')"
          @click="openUploadModal"
        >
          Ladda upp för signering
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="documents.length && $vuetify.display.smAndDown"
      >
        <VExpansionPanel v-for="document in documents" :key="document.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <span class="order-id">{{ document.order_id }}</span>
            <div class="order-title-box">
              <span class="title-panel">{{ document.title }}</span>
              <div class="gap-2 title-organization">
                <span>
                  {{ formatDateYMD(document.created_at) }}
                </span>
                <VIcon size="16" icon="custom-clock" />
                <span>
                  {{ document.created_at ? formatDate(document.created_at, { hour: '2-digit', minute: '2-digit', hour12: false }) : ''}}
                </span>
              </div>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Skapad av:</div>
              <div class="expansion-panel-item-value">
                {{ document.user.name }} {{ document.user.last_name ?? "" }}
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Status:</div>
              <div class="expansion-panel-item-value">
                <div
                  v-if="document.token"
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(document.token?.signature_status)?.class}`"
                >
                  <VIcon size="16" :icon="resolveStatus(document.token?.signature_status)?.icon" class="action-icon" />
                  {{ resolveStatus(document.token?.signature_status)?.name }}
                </div>

                <div
                  v-else
                  class="status-chip"
                  :class="`status-chip-${resolveStatus('pending')?.class}`"
                >
                  <VIcon size="16" :icon="resolveStatus('pending')?.icon" class="action-icon" />
                 {{ resolveStatus('pending')?.name }}
                </div>
              </div>
            </div>
            <div class="mb-4 row-with-buttons">
              <VBtn
                class="btn-light"
                @click="selectedDocumentForAction = document; isMobileActionDialogVisible = true"
              >
                Åtgärder
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="documents.length"
        :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
        class="align-center flex-wrap gap-4 pt-0 px-6"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
        
        <VPagination
          v-model="currentPage"
          size="small"
          :total-visible="4"
          :length="totalPages"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>
    </VCard>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filled-waste" class="action-icon" />
          <div class="dialog-title">
            Är du säker på att du vill radera dokumentet?
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Du är på väg att permanent radera <strong>"{{ selectedDocument.title }}"</strong>. All
          associerad data kommer att försvinna och åtgärden kan inte ångras.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmDeleteDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeDocument"> Ja, radera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Send Document Dialog -->
    <VDialog
      v-model="isConfirmSendDocumentDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmSendDocumentDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VForm
        ref="sendDocumentForm"
        @submit.prevent="handleSendDocument"
      >
        <VCard>
          <VCardText class="dialog-title-box">
            <VIcon size="32" icon="custom-paper-plane" class="action-icon" />
            <div class="dialog-title">
              Skicka PDF via e-post
            </div>
          </VCardText>
          <VCardText class="dialog-text pb-0">
            <AppAutocomplete
              prepend-icon="custom-profile"
              v-model="selectedClientId"
              :items="clients"
              :item-title="item => item.fullname"
              :item-value="item => item.id"
              placeholder="Kunder"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              class="selector-user selector-truncate w-auto"
              @update:modelValue="selectClient"
            />
          </VCardText>
          <VCardText class="dialog-text card-form">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
              <VTextField
              v-model="sendDocumentEmail"
              placeholder="Ange mottagarens e-postadress"
              :rules="[requiredValidator, emailValidator]"
            />
          </VCardText>
          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn
              class="btn-light"
              @click="isConfirmSendDocumentDialogVisible = false"
            >
              Avbryt
            </VBtn>
            <VBtn class="btn-gradient" type="submit">
              Skicka
            </VBtn>
          </VCardText>
        </VCard>
      </VForm>
    </VDialog>

    <!-- 👉 Upload Document Modal -->
    <VDialog
      v-model="isUploadModalVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isUploadModalVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
    
      <VForm
        ref="uploadForm"
        @submit.prevent="submitUpload"
      >
        <VCard flat class="card-form">
          <VCardText class="dialog-title-box">
            <VIcon size="32" icon="custom-icon-pdf" class="action-icon" />
            <div class="dialog-title">
              Ladda upp dokument
            </div>
          </VCardText>
          <VCardText class="dialog-text mb-4">
            You can access all the files in tis folder.
          </VCardText>
          <VCardText class="dialog-text">
            <div class="d-flex flex-column gap-4">
              <div>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Titel*" />
                <VTextField
                  v-model="uploadTitle"
                  placeholder="Ange dokumenttitel"
                  :rules="[requiredValidator]"
                />
              </div>
              <div>               
                <div 
                  class="file-upload-area"
                  :class="{ 'has-file': uploadFile, 'has-error': fileValidationError }"
                  @click="$refs.hiddenFileInput.click()"
                  @dragover.prevent
                  @drop.prevent="handleFileDrop"
                >
                  <input
                    ref="hiddenFileInput"
                    type="file"
                    accept=".pdf"
                    class="d-none"
                    @change="handleFileSelect"
                  />
                  <template v-if="!uploadFile">
                    <VIcon icon="custom-attach" :size="24" color="grey" />
                    <span class="file-upload-text">Bifoga ditt dokument</span>
                  </template>
                  <template v-else>
                    <VIcon icon="mdi-file-pdf-box" :size="32" color="error" class="file-pdf-icon" />
                    <span class="file-upload-text">{{ uploadFile.name }}</span>
                    <span
                      class="file-remove-btn"
                      @click.stop="uploadFile = null"
                    >
                      <VIcon icon="custom-close" size="10" />
                    </span>
                  </template>
                </div>
                <div v-if="fileValidationError" class="file-error-message">
                  {{ fileValidationError }}
                </div>
              </div>
            </div>
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn
              class="btn-light"
              @click="isUploadModalVisible = false"
            >
              Avbryt
            </VBtn>
            <VBtn class="btn-gradient" type="submit">
              Ladda upp
            </VBtn>
          </VCardText>
        </VCard>
      </VForm>
    </VDialog>

    <!-- 👉 Signature Dialog -->
    <VDialog
      v-model="isSignatureDialogVisible"
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
          class="btn-white close-btn"
          @click="isSignatureDialogVisible = !isSignatureDialogVisible"
        >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VForm
        ref="refSignatureForm"
        @submit.prevent="handleSignatureSubmit"
      >
        <VCard flat class="card-form">
          <VCardText class="dialog-title-box">
            <VIcon size="32" icon="custom-sent" class="action-icon" />
            <div class="dialog-title">
              Skicka signeringsförfrågan
            </div>
          </VCardText>
          <VCardText class="dialog-text">
            Ange e-postadressen dit signeringslänken ska skickas för dokumentet <strong>{{ selectedDocument.title }}</strong>.
          </VCardText>           
          <VCardText class="dialog-text mt-4">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-postadress*" />                                            
            <VTextField
              v-model="signatureEmail"
              :rules="[requiredValidator, emailValidator]"
            />
          </VCardText>
          <VCardText class="dialog-text mt-4">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Meddelande" />                   
            <VTextarea
              v-model="textEmail"
              placeholder="Valfritt meddelande som skickas tillsammans med signaturlänken"
              rows="3"
              persistent-placeholder
            />
          </VCardText>
          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn
              class="btn-light"
              @click="isSignatureDialogVisible = false">
                Avbryt
            </VBtn>
            <VBtn class="btn-gradient" type="submit">
                Skicka
            </VBtn>
          </VCardText>
          </VCard>
        </VForm>
      
    </VDialog>

    <!-- 👉 Placement Modal -->
    <VDialog
      v-model="isPlacementModalVisible"
      fullscreen
      :scrim="false"
      transition="dialog-bottom-transition"
      @update:modelValue="handlePlacementModalClose"
    >
      <VCard flat class="placement-modal-card">
        <div class="placement-content bg-page">
          <LoadingOverlay :is-loading="isLoadingPlacementPdf" />
          <div class="placement-body">
            <div class="placement-sidebar" :style="{ opacity: isLoadingPlacementPdf ? 0 : 1 }">
              <div class="d-flex align-center flex-0" :class="windowWidth < 1024 ? 'justify-center' : ''">
                <RouterLink to="/login">
                  <img :src="logo" width="121" height="40" />
                </RouterLink>
              </div>
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
                    :disabled="pdfZoom >= maxZoom">
                    <VIcon size="16" icon="mdi-plus" />
                  </VBtn>
                </div>
                <VBtn class="btn-light w-100" @click="resetZoom">Justera</VBtn>
              </div>

              <!-- Pages thumbnails (like browser left panel) -->
              <div
                v-if="placementPdfSource && placementPdfNumPages >= 1"
                class="my-4 flex-grow-1 overflow-auto placement-thumbnails-scroll"
                :class="windowWidth < 1024 ? 'd-none' : ''"
              >
                <div class="d-flex flex-column justify-center align-center gap-2">
                  <div
                    v-for="page in placementPdfNumPages"
                    :key="page"
                    class="thumbnail-page-item"
                    :class="{ 'is-active': page === activePlacementPage }"
                    @click="scrollToPlacementPage(page)"
                  >
                    <div class="thumbnail-number">{{ page }}</div>
                    <VuePdfEmbed
                      :source="placementPdfSource"
                      :page="page"
                      :width="130"
                    />
                  </div>
                </div>
              </div>

              <div 
                class="gap-2 mt-auto"
                :class="windowWidth >= 1024 ? 'd-flex flex-column' : 'd-none'">
                <VBtn 
                  class="btn-blue" 
                  block
                  @click="isPlacementModalVisible = false">
                  Avbryt
                </VBtn>
                <VBtn 
                  class="btn-green"
                  block 
                  :disabled="!signaturePlacement.visible" 
                  @click="openSignatureDialog()">
                  Skicka
                </VBtn>  
              </div>
            </div>
            <VDivider vertical class="ps-5 placement-divider" :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>
            <!-- PDF -->
            <div ref="pdfViewportEl" class="placement-viewer" :style="{ opacity: isLoadingPlacementPdf ? 0 : 1 }">
              <div
                ref="pdfPlacementContainer"
                class="pdf-container-admin"
              >
                <div v-if="placementPdfSource" class="pdf-host" :style="{ width: pdfVisualWidth + 'px' }">
                  <VuePdfEmbed
                    :key="`pdf-${placementPdfSource}`"
                    ref="pdfViewerRef"
                    :source="placementPdfSource"
                    :scale="pdfRenderScale"
                    @loaded="handlePlacementPdfLoaded"
                    @click="handleAdminPdfClick"
                  />
                </div>
                <div
                  v-if="signaturePlacement.visible"
                  class="signature-placeholder-admin"
                  :style="{
                    left: signaturePlacement.absoluteX + 'px',
                    top: signaturePlacement.absoluteY + 'px',
                    transform: `scale(${signaturePlacement.scale})`,
                    transformOrigin: 'top left'
                  }"
                >
                  <span class="signature-placeholder-content btn-light">
                    <VIcon size="16" icon="custom-pencil" />
                    <span>Signera här</span>
                  </span>
                </div>
              </div>
            </div>
            <div 
              class="gap-2"
               :class="windowWidth < 1024 ? 'd-flex' : 'd-none'">
              <VBtn 
                class="btn-blue" 
                block
                @click="isPlacementModalVisible = false">
                Avbryt
              </VBtn>
              <VBtn 
                class="btn-green"
                block 
                :disabled="!signaturePlacement.visible" 
                @click="openSignatureDialog()">
                Skicka
              </VBtn>  
            </div>
          </div>
        </div>
      </VCard>
    </VDialog>

    <!-- 👉 Tracker Dialog -->
    <VDialog 
      v-model="isTrackerDialogVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
      <VBtn
        icon
          class="btn-white close-btn"
          @click="isTrackerDialogVisible = false"
        >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <div class="dialog-title">
            Signaturprocess
          </div>
        </VCardText>
        
        <VCardText class="tracker-body">
          <div class="snake-timeline">
            <div 
              v-for="(item, index) in trackerEvents" 
              :key="item.key"
              class="snake-item"
              :class="[
                index % 2 === 0 ? 'icon-right' : 'icon-left',
                { 'is-first': index === 0 },
                { 'is-last': index === trackerEvents.length - 1 }
              ]"
            >
              <!-- Curved Border Line -->
              <div class="snake-curve"></div>
              
              <!-- Content Block (centered, covers the line) -->
              <div class="snake-content">
                <span class="snake-date">{{ item.meta }}</span>
                <h4 class="snake-heading">{{ item.title }}</h4>
                <p class="snake-text">{{ item.text }}</p>
                <div 
                  v-if="(item.key === 'created' || item.key === 'signed') && trackerDocument && item.showFile" 
                  class="snake-file-btn" 
                  @click="openTrackerPreview"
                >
                  <VIcon icon="custom-pdf-2" size="14" />
                  <span>{{ item.key === 'created' ? trackerDocument.file?.split('/').pop() : 'Signerad PDF' }}</span>
                </div>
              </div>

              <!-- Status Icon Circle -->
              <div class="snake-icon-wrapper" :class="item.bgClass">
                <VIcon :icon="item.icon" size="16" color="white" />
              </div>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Tracker Preview Dialog -->
    <VDialog 
      v-model="isTrackerPreviewVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
      <VBtn
        icon
          class="btn-white close-btn"
          @click="isTrackerPreviewVisible = !isTrackerPreviewVisible"
        >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-signature" class="action-icon" />
          <div class="dialog-title">
            Förhandsvisa dokument
          </div>
        </VCardText>
        <VDivider />
        <VCardText class="d-flex justify-center" style="min-height:400px;">
          <VAlert 
            v-if="trackerPreviewError" 
            type="error" class="mb-4">
            {{ trackerPreviewError }}
          </VAlert>
          <VuePdfEmbed 
            v-if="trackerPreviewPdfSource && !trackerPreviewError" 
            :source="trackerPreviewPdfSource" 
            :width="windowWidth < 1024 ? 300 : 450"
            />  
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Filter Dialog -->
    <VDialog
      v-model="isFilterDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isFilterDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filter" class="action-icon" />
          <div class="dialog-title">Filtrera efter</div>
        </VCardText>
        
        <VCardText class="pt-0">
          <AppAutocomplete
            v-if="role !== 'Supplier'"
            prepend-icon="custom-profile"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="(item) => item.full_name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user"
            :menu-props="{ maxHeight: '400px' }"
          />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-10">
          <VBtn class="btn-light" @click="isFilterDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="isFilterDialogVisible = false">
            Stäng
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile Action Dialog -->
    <VDialog
      v-model="isMobileActionDialogVisible"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem
            v-if="$can('view', 'signed-documents')"
            @click="goToTracker(selectedDocumentForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-eye" size="24" />
            </template>
            <VListItemTitle>Spårare</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="
              $can('edit', 'signed-documents') && (
                selectedDocumentForAction.token?.signature_status !== 'sent' && 
                selectedDocumentForAction.token?.signature_status !== 'signed' && 
                selectedDocumentForAction.token?.signature_status !== 'delivered' &&
                selectedDocumentForAction.token?.signature_status !== 'reviewed'
              )"
            @click="startPlacementProcess(selectedDocumentForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-signature" size="24" />
            </template>
            <VListItemTitle>Signera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'signed-documents') && selectedDocumentForAction.token?.signature_status === 'delivered'"
            @click="openResendSignature(selectedDocumentForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-forward" size="24" />
            </template>
            <VListItemTitle>Vidarebefordra</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'signed-documents')"
            @click="openLink(selectedDocumentForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-pdf" size="24" />
            </template>
            <VListItemTitle>Visa som PDF</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'signed-documents')"
            @click="download(selectedDocumentForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-download" size="24" />
            </template>
            <VListItemTitle>Ladda ner</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'signed-documents') && selectedDocumentForAction.token?.signature_status === 'signed'"
            @click="openSendDocumentDialog(selectedDocumentForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-paper-plane" size="24" />
            </template>
            <VListItemTitle>Sänd PDF</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete', 'signed-documents')"
            @click="showDeleteDialog(selectedDocumentForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile Filter Dialog -->
    <VDialog
      v-model="filtreraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem @click="updateStatus('created')">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="status === 'created'"
                  class="ml-3"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Skapad</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStatus('delivered')">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="status === 'delivered'"
                  class="ml-3"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Levererad</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStatus('delivery_issues')">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="status === 'delivery_issues'"
                  class="ml-3"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Leveransproblem</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStatus('reviewed')">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="status === 'reviewed'"
                  class="ml-3"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Granskad</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStatus('signed')">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="status === 'signed'"
                  class="ml-3"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Signerad</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss">
  .card-form {
    .v-list {
      padding: 28px 24px 40px !important;

      .v-list-item {
        margin-bottom: 0px;
        padding: 4px 0 !important;
        gap: 0px !important;

        .v-input--density-compact {
          --v-input-control-height: 48px !important;
        }

        .v-select .v-field,
        .v-autocomplete .v-field {

          .v-select__selection, .v-autocomplete__selection {
            align-items: center;
          }

          .v-field__input > input {
            top: 0px;
            left: 0px;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }

        .selector-user {
          .v-input__control {
            padding-top: 0 !important;
          }
          .v-input__prepend, .v-input__append {
            padding-top: 12px !important;
          }
        }

        .v-text-field {
          .v-input__control {
            padding-top: 0;
            input {
              min-height: 48px;
              padding: 12px 16px;
            }
          }
        }
      }
    }
    & .v-input {
      .v-input__control {
        .v-field {
          background-color: #f6f6f6 !important;
          min-height: 48px !important;

          .v-text-field__suffix {
              padding: 12px 16px !important;
          }

          .v-field__input {
            min-height: 48px !important;
            padding: 12px 16px !important;

            input {
              min-height: 48px !important;
            }
          }

          .v-field-label {
            top: 12px !important;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }
      }
    }
  }

  .dialog-bottom-full-width {
    .v-card {
      border-radius: 24px 24px 0 0 !important;
    }
  }
</style>

<style lang="scss" scoped>
  .bottom-sheet-card {
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-height: 75vh;
    overflow-y: auto;
  }

  .placement-modal-card {
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
    background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
  }

  .placement-title {
    font-size: 14px;
    color: #666;
    font-weight: 500;
  }

  .placement-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
  }

  .placement-body {
    flex: 1;
    display: flex;
    overflow: hidden;
    padding: 24px;
  }

  .placement-viewer {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: auto;

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
  }

  .placement-sidebar {
    width: 175px;
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

  .sidebar-actions-col {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .zoom-level {
    min-width: 28px;
    text-align: center;
    font-weight: 500;
    font-size: 14px;
    color: #333;
  }

  :deep(.pdf-container-admin) {
    position: relative;
    width: 100%;
    min-height: 100%;
    overflow: auto;
    display: flex;
    justify-content: center;
    align-items: flex-start;

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

  .placement-sidebar .vue-pdf-embed {
    border: 2px solid transparent;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
    transition: all 0.2s ease;

    &:hover {
      border-color: #4285f4;
      box-shadow: 0 2px 8px rgba(66, 133, 244, 0.3);
    }
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

  .placement-divider {
    border-color: #BDD2C8 !important;
  }

  .placement-sidebar .vue-pdf-embed canvas {
    display: block;
    width: 100% !important;
    height: auto !important;
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

  @media (max-width: 1023px) {
    .placement-body {
      flex-direction: column;
      padding: 12px;
    }

    .placement-sidebar {
      width: 100%;
      flex-direction: column;
    }

    .placement-viewer {
      padding: 10px 0;
    }

    :deep(.pdf-container-admin) {
      padding: 0;
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
  }

  :deep(.signature-placeholder-admin) {
      position: absolute;
      z-index: 1000;
      pointer-events: none;
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

  .bg-page {
    background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
  }

  .tracker-title {
    font-weight: 600;
    color: #333;
    font-size: 1.2rem;
  }

  .tracker-body {
    padding: 24px 32px 32px !important;
    max-height: 80vh;
    overflow-y: auto;
    
    /* Hide scrollbar but keep functionality */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none;  /* IE and Edge */
    &::-webkit-scrollbar {
      display: none; /* Chrome/Safari/Edge */
    }
  }

  /* ===== SNAKE TIMELINE - PROD STYLE (Pixel Perfect Refinement) ===== */
  .snake-timeline {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 600px; 
    margin: 0 auto;
    padding: 0 32px;
  }

  .snake-item {
    position: relative;
    display: flex;
    width: 100%;
    min-height: 140px;
    box-sizing: border-box;
    
    /* SOLUCIÓN DOBLE LÍNEA */
    & + .snake-item {
      margin-top: -2px; 
    }
  }

  /* Variables SASS Ajustadas */
  $curve-width: 2px;
  $curve-color: #E7E7E7;
  $curve-radius: 70px; /* Aumentado para curva más pronunciada/circular */
  $gap-to-center: 12px; /* 12px per Figma */

  /* ===== LA CURVA (SNAKE LINE) ===== */
  .snake-curve {
    position: absolute;
    top: 0;
    bottom: 0;
    width: calc(50% + 1px); 
    border: 0 solid $curve-color;
    pointer-events: none;
    z-index: 1;
  }

  /* --- Ítem: Icono Derecha (Contenido Izquierda) --- */
  .snake-item.icon-right {
    flex-direction: row;
    justify-content: flex-end; 
    padding-right: 10%; 
    text-align: right;

    .snake-curve {
      left: 50%; 
      border-top-width: $curve-width;
      border-right-width: $curve-width;
      border-bottom-width: $curve-width;
      border-radius: 0 $curve-radius $curve-radius 0;
    }

    .snake-content {
      align-items: flex-end; 
      margin-right: $gap-to-center;
    }
  }

  /* --- Ítem: Icono Izquierda (Contenido Derecha) --- */
  .snake-item.icon-left {
    flex-direction: row;
    justify-content: flex-start;
    padding-left: 10%; 
    text-align: left;

    .snake-curve {
      right: 50%; 
      border-top-width: $curve-width;
      border-left-width: $curve-width;
      border-bottom-width: $curve-width;
      border-radius: $curve-radius 0 0 $curve-radius;
    }

    .snake-content {
      align-items: flex-start; 
      margin-left: $gap-to-center;
    }
  }

  /* --- PRIMER ÍTEM (Corrección "Cola") --- */
  .snake-item.is-first {
    .snake-curve {
      top: 50%; 
      height: 50% !important;
      border-top-width: 0; /* IMPORTANTE: Eliminar borde superior */
      border-bottom-width: $curve-width;
    }
    
    &.icon-right .snake-curve {
      border-top-right-radius: 0; /* Eliminar curva superior */
      border-bottom-right-radius: $curve-radius;
      border-radius: 0 0 $curve-radius 0; /* Solo curva abajo-derecha */
    }

    &.icon-left .snake-curve {
      border-top-left-radius: 0; /* Eliminar curva superior */
      border-bottom-left-radius: $curve-radius;
      border-radius: 0 0 0 $curve-radius; /* Solo curva abajo-izquierda */
    }
  }

  /* --- ÚLTIMO ÍTEM (Final) --- */
  .snake-item.is-last {
    .snake-curve {
      top: 0;
      height: 50% !important; 
      border-top-width: $curve-width;
      border-bottom-width: 0; 
    }

    &.icon-right .snake-curve {
      border-radius: 0 $curve-radius 0 0;
    }
    &.icon-left .snake-curve {
      border-radius: $curve-radius 0 0 0;
    }
  }

  /* --- CONTENIDO DE TEXTO --- */
  .snake-content {
    position: relative;
    z-index: 10;
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 100%;
    max-width: 320px; 
    padding: 12px 0;
  }

  /* Typography */
  .snake-date {
    font-weight: 400;
    font-size: 12px;
    line-height: 16px;
    color: #878787;
    margin-bottom: 4px;
    text-transform: uppercase;
  }

  .snake-heading {
    font-weight: 600;
    font-size: 16px;
    line-height: 16px;
    text-align: right;
    color: #454545;
    margin: 0 0 4px 0;
  }

  .snake-text {
    font-weight: 400;
    font-size: 14px;
    line-height: 16px;
    color: #454545;
    margin: 0;
  }

  /* File Badge */
  .snake-file-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #E7E7E7;
    padding: 6px 12px;
    border-radius: 6px;
    margin-top: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;

    span {
      font-weight: 400;
      font-size: 14px;
      line-height: 16px;
      color: #454545;

      max-width: 160px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    &:hover {
      background: #E5E7EB;
      border-color: #D1D5DB;
    }
  }

  /* ===== ESTADO (ICONO) ===== */
  .snake-icon-wrapper {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    z-index: 20;
    top: 48%;
  }

  /* Icon Right */
  .snake-item.icon-right .snake-icon-wrapper {
    right: 0;
    transform: translate(50%, -50%);
  }

  /* Icon Left */
  .snake-item.icon-left .snake-icon-wrapper {
    left: 0;
    transform: translate(-50%, -50%);
  }

  /* Status Colors */
  .snake-icon-wrapper.status-success {
    background-color: #00EEB0;
  }

  .snake-icon-wrapper.status-info {
    background-color: #1890FF;
  }

  .snake-icon-wrapper.status-warning {
    background-color: #FAAD14;
  }

  .snake-icon-wrapper.status-error {
    background-color: #EF4444;
  }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 480px) {
    .tracker-body {
      padding: 16px 12px 24px !important;
    }

    .snake-timeline {
      padding: 0 24px;
    }

    .snake-item {
      min-height: 120px;
    }
    
    .snake-content {
      max-width: none; 
    }

    .snake-item.icon-right .snake-content { margin-right: 20px; }
    .snake-item.icon-left .snake-content { margin-left: 20px; }

    .snake-icon-wrapper {
      width: 32px;
      height: 32px;
      
      .v-icon {
        font-size: 16px !important;
      }
    }
  }

  .file-upload-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 16px;
    border: 1px dashed #E7E7E7;
    border-radius: 8px;
    min-height: 88px !important;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: #F6F6F6;
    position: relative;

    &.has-error {
      border-color: rgb(var(--v-theme-error));
      background-color: rgba(var(--v-theme-error), 0.04);
    }
  }

  .file-upload-text {
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    text-align: center;
    max-width: calc(100% - 40px);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .file-pdf-icon {
    font-size: 32px !important;
    width: 32px !important;
    height: 32px !important;
  }

  .file-remove-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: white;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: rgba(0, 0, 0, 0.2);
    }
  }

  .file-error-message {
    color: rgb(var(--v-theme-error));
    font-size: 12px;
    margin-top: 4px;
    padding-left: 8px;
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: signed-documents
</route>
