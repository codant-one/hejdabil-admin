<script setup>

import { inject } from 'vue'
import { useDisplay } from "vuetify";
import { useSignableDocumentsStores } from '@/stores/useSignableDocuments'
import { useClientsStores } from '@/stores/useClients'
import { useNotificationsStore } from '@/stores/useNotifications'
import { requiredValidator, emailValidator } from '@/@core/utils/validators'
import { themeConfig } from '@themeConfig'
import { avatarText } from "@/@core/utils/formatters";
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
const clients = ref([])
const isFilterDialogVisible = ref(false);

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
const refSignatureForm = ref()              
const isStaticSignatureFlow = ref(false)
const selectedDocument = ref({})
const selectedDocumentForAction = ref({});
const isMobileActionDialogVisible = ref(false);

const isPlacementModalVisible = ref(false)
const placementPdfSource = ref(null)
const isLoadingPlacementPdf = ref(false)
const signaturePlacement = ref({ x: 0, y: 0, page: 1, visible: false, pageWidth: 0, pageHeight: 0 })
const pdfPlacementContainer = ref(null)

const isUploadModalVisible = ref(false)
const uploadForm = ref(null)
const uploadTitle = ref('')
const uploadDescription = ref('')
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

onMounted(async () => {
  await fetchData()
  
  // Escuchar notificaciones y refrescar datos cuando llegue una relacionada con documentos
  notificationsStore.onNotificationReceived((notification) => {    
    // Si la notificación tiene una ruta relacionada con documentos, refrescar
    if (notification.route && notification.route.includes('/documents')) {
      fetchData()
    } else {
      console.warn('⚠️ Route does not match /documents criteria')
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
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'created_at',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value,
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
      BESKRIVNING: element.description ?? '',
      SKAPAD: createdAt,
      SKAPAD_AV: (element.user?.name ?? '') + ' ' + (element.user?.last_name ?? ''),
      SIGNATUR_STATUS: element.tokens && element.tokens.length > 0 ? (element.tokens[0].signature_status ?? '') : 'pending',
      MOTTAGARE: element.tokens && element.tokens.length > 0 ? element.tokens.map(t => t.email).join(', ') : '',
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
const isTrackerPreviewLoading = ref(false)
const trackerPreviewError = ref('')

const trackerEvents = computed(() => {
  if (!trackerDocument.value) return []

  const items = []
  const latestToken = (trackerDocument.value.tokens || []).length
    ? [...trackerDocument.value.tokens].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
    : null

  // Si tenemos historial de token, usar esos registros
  if (latestToken && latestToken.history && latestToken.history.length > 0) {
    const history = [...latestToken.history].sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
    
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
      icon: 'custom-alert'
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
  isTrackerPreviewLoading.value = true
  trackerPreviewError.value = ''
  try {
    const response = await documentsStores.getAdminPreviewPdf(trackerDocument.value.id)
    trackerPreviewPdfSource.value = URL.createObjectURL(response.data)
  } catch (e) {
    trackerPreviewError.value = 'Kunde inte ladda PDF-förhandsvisning.'
  } finally {
    isTrackerPreviewLoading.value = false
  }
}

watch(isTrackerPreviewVisible, val => {
  if (!val && trackerPreviewPdfSource.value) {
    URL.revokeObjectURL(trackerPreviewPdfSource.value)
    trackerPreviewPdfSource.value = null
  }
})

const startPlacementProcess = async (documentData) => {
  selectedDocument.value = { ...documentData };
  isPlacementModalVisible.value = true;
  isLoadingPlacementPdf.value = true;
  signaturePlacement.value.visible = false;

  try {
    const response = await documentsStores.getAdminPreviewPdf(documentData.id)
    placementPdfSource.value = URL.createObjectURL(response.data);
  } catch (error) {
    advisor.value = { type: 'error', message: 'Kunde inte ladda PDF-dokumentet.', show: true };
    isPlacementModalVisible.value = false;
    setTimeout(() => { advisor.value.show = false }, 3000);
  } finally {
    isLoadingPlacementPdf.value = false;
  }
}

const handleAdminPdfClick = (event) => {
  const container = pdfPlacementContainer.value; 
  if (!container) return;

  const pages = Array.from(container.querySelectorAll('canvas, svg, img'))
  const pageEl = event.target.closest('canvas, svg, img') || pages[0]
  if (!pageEl) return

  const pageRect = pageEl.getBoundingClientRect();
  const containerRect = container.getBoundingClientRect();

  // Obtener el estilo computado para calcular el border
  const computedStyle = window.getComputedStyle(pageEl);
  const borderLeft = parseFloat(computedStyle.borderLeftWidth) || 0;
  const borderTop = parseFloat(computedStyle.borderTopWidth) || 0;

  // Usar clientWidth/clientHeight para dimensiones del contenido (excluye borders)
  // Esto es consistente con el cálculo en [token].vue
  const contentWidth = pageEl.clientWidth;
  const contentHeight = pageEl.clientHeight;

  // Coordenadas locales en el sistema CSS (excluyendo el border)
  const localX = event.clientX - pageRect.left - borderLeft;
  const localY = event.clientY - pageRect.top - borderTop;

  // Coordenadas absolutas dentro del contenedor (para mostrar el placeholder)
  const absoluteX = (pageRect.left - containerRect.left) + localX + borderLeft + (container.scrollLeft || 0);
  const absoluteY = (pageRect.top - containerRect.top) + localY + borderTop + (container.scrollTop || 0);

  const pageIndex = Math.max(0, pages.indexOf(pageEl))

  signaturePlacement.value = {
    // Coordenadas locales para calcular porcentaje
    x: localX,
    y: localY,
    // Absolutas para mostrar el placeholder correctamente
    absoluteX,
    absoluteY,
    page: pageIndex + 1,
    visible: true,
    // Dimensiones del área de contenido (clientWidth/clientHeight)
    pageWidth: contentWidth,
    pageHeight: contentHeight,
  }
}

const openSignatureDialog = (documentData) => {
  signatureEmail.value = ''
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

const submitPlacementSignatureRequest = async () => {
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
    }

    const response = await documentsStores.requestSignature(payload)
    
    advisor.value = {
      type: 'success',
      message: response.data.message || 'Signeringsförfrågan har skickats!',
      show: true,
    }

    await fetchData() 
    
  } catch (error) {
    advisor.value = {
      type: 'error',
      message: error.response?.data?.message || 'Ett fel uppstod när begäran skickades.',
      show: true,
    }
    console.error('Error sending signature request:', error?.response || error)
  } finally {
    isRequestOngoing.value = false
    signatureEmail.value = ''
    setTimeout(() => {
      advisor.value = { show: false }
    }, 3000)
  }
}

const submitStaticSignatureRequest = async () => {
  const { valid } = await refSignatureForm.value?.validate();
  if (!valid) return;

  isSignatureDialogVisible.value = false;
  isRequestOngoing.value = true;

  try {
    const payload = {
      documentId: selectedDocument.value.id,
      email: signatureEmail.value,
      alignment: 'left',
    };

    const response = await documentsStores.requestStaticSignature(payload);

    advisor.value = {
      type: 'success',
      message: response.data.message || 'Signeringsförfrågan har skickats!',
      show: true,
    };
    await fetchData();

  } catch (error) {
    advisor.value = {
      type: 'error',
      message: error.response?.data?.message || 'Ett fel uppstod när begäran skickades.',
      show: true,
    };
  } finally {
    isRequestOngoing.value = false;
    signatureEmail.value = '';
    setTimeout(() => {
      advisor.value = { show: false };
    }, 3000);
  }
};

const handleSignatureSubmit = async () => {
  if (isStaticSignatureFlow.value) {
    await submitStaticSignatureRequest();
  } else {
    await submitPlacementSignatureRequest(); 
  }
};

const handlePlacementModalClose = (value) => {
  if (!value) {
    if (placementPdfSource.value) {
      URL.revokeObjectURL(placementPdfSource.value);
    }
    placementPdfSource.value = null;
  }
}

const openUploadModal = () => {
  isUploadModalVisible.value = true
  uploadTitle.value = ''
  uploadDescription.value = ''
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
    formData.append('description', uploadDescription.value || '')
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
      class: 'warning',
      icon: 'custom-alert'
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

        <VBtn 
          class="btn-white-2" 
          v-if="role !== 'Supplier' && role !== 'User'"
          @click="isFilterDialogVisible = true"
        >
          <VIcon icon="custom-filter" size="24" />
          <span :class="windowWidth < 1024 ? 'd-none' : 'd-flex'">Filtrera efter</span>
        </VBtn>

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
            <th scope="col" class="text-center">Titel</th>
            <th scope="col" class="text-center">Beskrivning</th>
            <th scope="col" class="text-center">Skapad</th>
            <th scope="col" v-if="role !== 'Supplier' && role !== 'User'">Leverantör</th>
            <th scope="col">Skapad av</th>
            <th scope="col" class="text-center">Signera status</th>
            <th scope="col" v-if="$can('edit', 'signed-documents') || $can('delete', 'signed-documents')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="documents.length">
          <tr v-for="document in documents" :key="document.id">
            <td>
              <span>{{ document.id }}</span>
            </td>
            <td class="text-center">
              <span>{{ document.title }}</span>
            </td>
            <td class="text-center">
              <span>{{ document.description || '-' }}</span>
            </td>
            <td class="text-center">
              <span>
                    {{ new Date(document.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
              </span>
            </td>
            <td style="width: 1%; white-space: nowrap" v-if="role !== 'Supplier' && role !== 'User'">
              <div class="d-flex align-center gap-x-1" v-if="document.supplier">
                <VAvatar
                  :variant="document.supplier.user.avatar ? 'outlined' : 'tonal'"
                  size="38"
                >
                  <VImg
                    v-if="document.supplier.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + document.supplier.user.avatar"
                  />
                  <span v-else>{{
                    avatarText(document.supplier.user.name)
                  }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ document.supplier.user.name }} {{ document.supplier.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip location="bottom" v-if="document.supplier.user.email && document.supplier.user.email.length > 20">
                      <template #activator="{ props }">
                        <span v-bind="props">
                          {{ truncateText(document.supplier.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ document.supplier.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ document.supplier.user.email }}</span>
                  </span>
                </div>
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
                    <VTooltip location="bottom" v-if="document.user.email && document.user.email.length > 20">
                      <template #activator="{ props }">
                        <span v-bind="props">
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
            <td class="text-center text-wrap d-flex justify-center align-center">
              <div
                v-if="document.tokens && document.tokens.length > 0"
                class="status-chip"
                :class="`status-chip-${resolveStatus(document.tokens[0]?.signature_status)?.class}`"
              >
                <VIcon size="16" :icon="resolveStatus(document.tokens[0]?.signature_status)?.icon" class="action-icon" />
                {{ resolveStatus(document.tokens[0]?.signature_status)?.name }}
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
                    v-if="$can('edit','signed-documents') && (document.tokens?.[0]?.signature_status !== 'sent' && document.tokens?.[0]?.signature_status !== 'signed' && document.tokens?.[0]?.signature_status !== 'delivered')"
                    @click="startPlacementProcess(document)">
                    <template #prepend>
                      <VIcon icon="custom-signature" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Signera</VListItemTitle>
                  </VListItem>                  
                  <VListItem
                    v-if="$can('edit','signed-documents') && document.tokens?.[0]?.signature_status === 'delivered'"
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
                  <VListItem v-if="$can('view','signed-documents') && document.tokens?.[0]?.signature_status === 'signed'"
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
            <span class="order-id">{{ document.id }}</span>
            <div class="order-title-box">
              <span class="title-panel">{{ document.title }}</span>
              <div class="title-organization">
                {{ new Date(document.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
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
                  v-if="document.tokens && document.tokens.length > 0"
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(document.tokens[0]?.signature_status)?.class}`"
                >
                  <VIcon size="16" :icon="resolveStatus(document.tokens[0]?.signature_status)?.icon" class="action-icon" />
                  {{ resolveStatus(document.tokens[0]?.signature_status)?.name }}
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
          :total-visible="5"
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
          Du är på väg att permanent radera "{{ selectedDocument.title }}". All
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
              Skicka PDF som e-post
            </div>
          </VCardText>
          <VCardText class="pb-0">
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
          <VCardText class="card-form">
            <VTextField
              v-model="sendDocumentEmail"
              label="E-post"
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
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Beskrivning" />
                <VTextField
                  v-model="uploadDescription"
                  placeholder="Ange beskrivning (valfritt)"
                />
              </div>
              <div>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Meddelande" />                
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
            <VTextField
              v-model="signatureEmail"
              label="E-postadress"
              placeholder="kund@exempel.com"
              :rules="[requiredValidator, emailValidator]"
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
        <div class="placement-header">
          <RouterLink to="/login">
            <img :src="logo" width="121" height="40" />
          </RouterLink>
        </div>
        <div class="placement-content bg-page">
          <LoadingOverlay :is-loading="isLoadingPlacementPdf" />
          <div 
            v-show="!isLoadingPlacementPdf"
            ref="pdfPlacementContainer" 
            class="pdf-container-admin" 
            @click="handleAdminPdfClick"
          >
            <VuePdfEmbed 
              :source="placementPdfSource"
              class="pdf-embed-fullscreen"
            />

            <div 
              v-if="signaturePlacement.visible"
              class="signature-placeholder-admin"
              :style="{ left: signaturePlacement.absoluteX + 'px', top: signaturePlacement.absoluteY + 'px' }"
            >
              <span class="signature-placeholder-content btn-light">
                 <VIcon size="16" icon="custom-pencil" />
                <span>Signera här</span>
              </span>
            </div>
          </div>

          <div class="d-flex justify-center w-100 gap-4 my-4" v-if="!isLoadingPlacementPdf">
            <VBtn
              class="btn-blue"
              @click="isPlacementModalVisible = false"
            >
              Avbryt
            </VBtn>
            <VBtn
              class="btn-green"
              :disabled="!signaturePlacement.visible"
              @click="openSignatureDialog(selectedDocument)"
            >
              Skicka
            </VBtn>
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
          <VProgressCircular v-if="isTrackerPreviewLoading" indeterminate color="primary" />
          <div v-else class="w-100">
            <VAlert v-if="trackerPreviewError" type="error" class="mb-4">{{ trackerPreviewError }}</VAlert>
            <VuePdfEmbed v-if="trackerPreviewPdfSource && !trackerPreviewError" :source="trackerPreviewPdfSource" style="width:100%;" />
            <VAlert v-else-if="!trackerPreviewError" type="warning">Ingen PDF tillgänglig.</VAlert>
          </div>
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
            v-if="$can('edit', 'signed-documents') && (selectedDocumentForAction.tokens?.[0]?.signature_status !== 'sent' && selectedDocumentForAction.tokens?.[0]?.signature_status !== 'signed' && selectedDocumentForAction.tokens?.[0]?.signature_status !== 'delivered')"
            @click="startPlacementProcess(selectedDocumentForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-signature" size="24" />
            </template>
            <VListItemTitle>Signera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'signed-documents') && selectedDocumentForAction.tokens?.[0]?.signature_status === 'delivered'"
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
            v-if="$can('view', 'signed-documents') && selectedDocumentForAction.tokens?.[0]?.signature_status === 'signed'"
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

  </section>
</template>

<style scope>
  .card-form {
    .v-list {
      padding: 28px 24px 40px !important;

      .v-list-item {
        margin-bottom: 0px;
        padding: 0px !important;
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
            padding-top: 16px;
            input {
              min-height: 48px;
              padding: 12px 16px;
            }
          }
        }
      }
    }
    & .v-input {
      & .v-input__control {
        .v-field {
          background-color: #f6f6f6;
          .v-field-label {
            @media (max-width: 991px) {
              top: 12px !important;
            }
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
    min-height: 100vh;
    overflow-y: auto;
    background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
  }

  .placement-header {
    display: flex;
    align-items: center;
    padding: 16px 24px;
    background: transparent;
  }

  .placement-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 0 24px;
  }

  :deep(.pdf-container-admin) {
    position: relative;
    cursor: crosshair;
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
    background: transparent;
    border-radius: 8px;
    padding: 0;
    overflow: visible;
  }

  :deep(.pdf-container-admin > div) {
    width: 100% !important;
  }

  :deep(.pdf-embed-fullscreen) {
    display: block;
    width: 100%;
    
    canvas {
      width: 100% !important;
      height: auto !important;
      display: block;
    }
  }

  :deep(.pdf-container-admin .vue-pdf-embed > div) {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 0 0 12px 0;
    background: transparent;
  }

  :deep(.pdf-container-admin .vue-pdf-embed canvas) {
    background: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    border-radius: 4px;
  }

  :deep(.signature-placeholder-admin) {
      position: absolute;
      z-index: 10;
      pointer-events: none;
    }

  :deep(.signature-placeholder-content) {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: solid 1px #6e9383;
    background-color: transparent;
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

    @media (max-width: 1023px) {
      padding: 2px 6px;
      font-size: 8px;
      gap: 2px;
      border-radius: 12px;

      .v-icon {
        font-size: 8px !important;
        width: 8px !important;
        height: 8px !important;
      }
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

    /*&:hover {
      border-color: rgba(var(--v-theme-primary), 0.5);
      background-color: rgba(var(--v-theme-primary), 0.04);
    }

    &.has-file {
      border-color: rgb(var(--v-theme-primary));
      background-color: rgba(var(--v-theme-primary), 0.08);
    }*/

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
