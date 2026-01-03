<script setup>

import { inject } from 'vue'
import { useDisplay } from "vuetify";
import { useSignableDocumentsStores } from '@/stores/useSignableDocuments'
import { requiredValidator, emailValidator } from '@/@core/utils/validators'
import { themeConfig } from '@themeConfig'
import { avatarText } from "@/@core/utils/formatters";
import { excelParser } from '@/plugins/csv/excelParser'
import { useRoute } from 'vue-router'
import Toaster from "@/components/common/Toaster.vue";
import VuePdfEmbed from 'vue-pdf-embed'
import axios from '@/plugins/axios'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

import eyeIcon from "@/assets/images/icons/figma/eye.svg";
import editIcon from "@/assets/images/icons/figma/edit.svg";
import wasteIcon from "@/assets/images/icons/figma/waste.svg";
import pdfIcon from '@images/icon-pdf-documento.png'

const documentsStores = useSignableDocumentsStores()
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

const isDialogOpen = ref(false);
const isNoteFormEdited = ref(false);
const isNoteEditFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const isFilterDialogVisible = ref(false);
const isConfirmUpdateNoteDialogVisible = ref(false);
const isConfirmUpdateNoteMobileDialogVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | 'noteEdit' | 'noteEditMobile' | null

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

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = documents.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = documents.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalDocuments.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

onMounted(async () => {
  await fetchData()
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {
  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'created_at',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
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

const download = async(document) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + document.file);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = document.file.replace('documents/', '');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

  } catch (error) {
    console.error('Error:', error);
  }
};

const openLink = function (documentData) {
  window.open(themeConfig.settings.urlStorage + documentData.file)
}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await documentsStores.fetchDocuments(data)

  let dataArray = [];

  documentsStores.getDocuments.forEach(element => {

    const createdAt = element.created_at ? new Date(element.created_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : ''

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

  // Created event
  items.push({
    key: 'created',
    title: 'Avtal skapat',
    meta: new Date(trackerDocument.value.created_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
    text: trackerDocument.value.title,
    color: '#00EEB0', // Green
  })

  // Use most recent token for status markers
  const latestToken = (trackerDocument.value.tokens || []).length
    ? [...trackerDocument.value.tokens].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
    : null

  if (latestToken) {
    // Sent event
    items.push({
      key: 'sent',
      title: 'Signeringsförfrågan skickad',
      meta: new Date(latestToken.created_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
      text: `Skickad till ${latestToken.recipient_email}`,
      color: '#1890FF', // Blue
    })

    // Viewed event
    if (latestToken.viewed_at) {
      items.push({
        key: 'viewed',
        title: 'Dokument visat av kunden',
        meta: new Date(latestToken.viewed_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
        text: 'Kunden har öppnat signeringslänken.',
        color: '#FAAD14', // Yellow
      })
    }

    // Signed event
    if (latestToken.signature_status === 'signed' && latestToken.signed_at) {
      items.push({
        key: 'signed',
        title: 'Dokument signerat',
        meta: new Date(latestToken.signed_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
        text: 'Signeringen är slutförd.',
        color: '#00EEB0', // Green
      })
    } else if (latestToken.signature_status === 'sent') {
      items.push({
        key: 'pending',
        title: 'Väntar på signering',
        meta: 'Aktiv begäran',
        text: 'Mottagaren har inte signerat ännu.',
        color: '#FAAD14', // Yellow
      })
    }
  }
  
  // Assign sides strictly alternating
  return items.map((item, index) => ({
      ...item,
      side: index % 2 === 0 ? 'right' : 'left'
  }))
})

const openTracker = async (doc) => {
  trackerDocument.value = doc
  isTrackerDialogVisible.value = true
  
  try {
    const fullDoc = await documentsStores.showDocument(doc.id)
    trackerDocument.value = fullDoc
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
    const response = await axios.get(`/signable-documents/${trackerDocument.value.id}/get-admin-preview-pdf`, { responseType: 'blob' })
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
    const response = await axios.get(`/signable-documents/${documentData.id}/get-admin-preview-pdf`, {
        responseType: 'blob',
    })
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

  // Coordenadas locales dentro de la página (para guardar en porcentaje)
  const localX = event.clientX - pageRect.left;
  const localY = event.clientY - pageRect.top;

  // Coordenadas absolutas dentro del contenedor (para mostrar el placeholder correctamente en páginas > 1)
  const absoluteX = (pageRect.left - containerRect.left) + localX + (container.scrollLeft || 0);
  const absoluteY = (pageRect.top - containerRect.top) + localY + (container.scrollTop || 0);

  const pageIndex = Math.max(0, pages.indexOf(pageEl))

  signaturePlacement.value = {
    // Locales a la página para convertir a porcentaje
    x: localX,
    y: localY,
    // Absolutas para mostrar el placeholder correctamente
    absoluteX,
    absoluteY,
    page: pageIndex + 1,
    visible: true,
    pageWidth: pageRect.width,
    pageHeight: pageRect.height,
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
  }
}

const submitUpload = async () => {
  const { valid } = await uploadForm.value?.validate()
  if (!valid) return

  if (!uploadFile.value) {
    advisor.value = {
      type: 'error',
      message: 'Vänligen välj en PDF-fil.',
      show: true
    }
    setTimeout(() => {
      advisor.value.show = false
    }, 3000)
    return
  }

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
  if (state === 'signed')
    return { color: 'info' }
  if (state === 'pending')
    return { color: 'warning' } 
  if (state === 'sent')
    return { color: 'success' } 
  return { color: 'default' }
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
  
  // Check if we should open create dialog
  if (route.query.action === 'create' && !hasProcessedCreateAction.value) {
    hasProcessedCreateAction.value = true;
    isConfirmCreateDialogVisible.value = true;
  }
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
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
        class="d-flex align-center justify-space-between gap-2 pb-0"
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
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col">TITEL</th>
            <th scope="col">BESKRIVNING</th>
            <th scope="col">SKAPAD</th>
            <th scope="col">SKAPAD AV</th>
            <th scope="col">SIGNERA STATUS</th>
            <th
              scope="col"
              v-if="$can('edit', 'signed-documents') || $can('delete', 'signed-documents')"
            ></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="documents.length">
          <tr v-for="document in documents" :key="document.id">
            <td>
              <span>{{ document.title }}</span>
            </td>
            <td>
              <span>{{ document.description || '-' }}</span>
            </td>
            <td>
              <span>
                  {{ new Date(document.created_at).toLocaleString('sv-SE', { 
                      year: 'numeric', 
                      month: '2-digit', 
                      day: '2-digit', 
                      hour: '2-digit', 
                      minute: '2-digit',
                      hour12: false
                  }) }}
              </span>
            </td>
            <td class="text-wrap">
              <div class="d-flex align-center gap-x-3">
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
                  <span class="text-sm text-disabled">{{
                    document.user.email
                  }}</span>
                </div>
              </div>
            </td>
            <td>
                  <VChip
                    v-if="document.tokens && document.tokens.length > 0"
                    label
                    :color="resolveStatus(document.tokens[0]?.signature_status ?? 'pending')?.color"
                  >
                    {{ document.tokens[0]?.signature_status ?? 'pending' }}
                  </VChip>
                  <VChip
                    v-else
                    label
                    color="default"
                  >
                    Ingen signering
                  </VChip>
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
                      <img :src="eyeIcon" alt="See Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Spårare</VListItemTitle>
                  </VListItem>
                  
                  <VListItem
                    v-if="$can('edit','signed-documents') && (document.tokens?.[0]?.signature_status !== 'sent' && document.tokens?.[0]?.signature_status !== 'signed')"
                    @click="startPlacementProcess(document)">
                    <template #prepend>
                      <img :src="editIcon" alt="Edit Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Signera</VListItemTitle>
                  </VListItem>
                  
                  <VListItem
                    v-if="$can('edit','signed-documents') && document.tokens?.[0]?.signature_status === 'sent'"
                    @click="openResendSignature(document)">
                    <template #prepend>
                      <img :src="editIcon" alt="Edit Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Vidarebefordra</VListItemTitle>
                  </VListItem>

                  <VListItem
                        v-if="$can('view', 'signed-documents')"
                        @click="openLink(document)">
                    <template #prepend>
                        <img :src="eyeIcon" alt="See Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa som PDF</VListItemTitle>
                    </VListItem>
                    <VListItem v-if="$can('view','signed-documents')" @click="download(document)">
                    <template #prepend>
                        <img :src="eyeIcon" alt="See Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                    </VListItem>

                  <VListItem
                    v-if="$can('delete', 'signed-documents')"
                    @click="showDeleteDialog(document)"
                  >
                    <template #prepend>
                      <img :src="wasteIcon" alt="Delete Icon" class="mr-2" />
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
          icon="custom-f-user"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Du har inga kunder än</div>
          <div class="empty-state-text">
            Lägg till dina kunder här för att snabbt skapa fakturor och hålla
            ordning på dina kontakter.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'signed-documents')"
          @click="openUploadModal"
        >
          Lägg till ny kund
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
            <span class="title-panel">{{ document.title }}</span>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Beskrivning:</div>
              <div class="expansion-panel-item-value">
                {{ document.description || '-' }}
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Skapad:</div>
              <div class="expansion-panel-item-value">
                {{ new Date(document.created_at).toLocaleString('sv-SE', { 
                      year: 'numeric', 
                      month: '2-digit', 
                      day: '2-digit', 
                      hour: '2-digit', 
                      minute: '2-digit',
                      hour12: false
                  }) }}
              </div>
            </div>
            <div class="mb-4 row-with-buttons">
              <VBtn
                v-if="$can('delete', 'signed-documents')"
                class="btn-light"
                @click="showDeleteDialog(document)"
              >
                <VIcon icon="custom-waste" size="24" />
                Ta bort
              </VBtn>
            </div>
            <div>
              <VBtn class="btn-light w-100" @click="goToTracker(document)">
                <VIcon icon="custom-eye" size="24" />
                Spårare
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="documents.length"
        class="d-block d-md-flex align-center flex-wrap gap-4 pt-0 px-6 pb-16"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer class="d-none d-md-block" />

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
          <VCardText class="pt-0">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="uploadTitle"
                  label="Titel"
                  placeholder="Ange dokumenttitel"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12">
                <VTextarea
                  v-model="uploadDescription"
                  label="Beskrivning"
                  placeholder="Ange beskrivning (valfritt)"
                  rows="3"
                />
              </VCol>
              <VCol cols="12">
                <VFileInput
                  ref="uploadFileInput"
                  label="PDF-fil"
                  placeholder="PDF-fil"
                  accept=".pdf"
                  prepend-icon=""
                  append-inner-icon="custom-upload"
                  :rules="[requiredValidator]"
                  @change="handleFileSelect"
                />
              </VCol>
            </VRow>
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
      class="v-dialog-sm"
    >
      <DialogCloseBtn @click="isSignatureDialogVisible = !isSignatureDialogVisible" />

      <VCard title="Skicka signeringsförfrågan">
        <VForm
          ref="refSignatureForm"
          @submit.prevent="handleSignatureSubmit"
        >
          <VDivider class="mt-4"/>
          <VCardText>
            Ange e-postadressen dit signeringslänken ska skickas för dokumentet <strong>{{ selectedDocument.title }}</strong>.
          </VCardText>
          <VCardText>
            <VTextField
              v-model="signatureEmail"
              label="E-postadress"
              placeholder="kund@exempel.com"
              :rules="[requiredValidator, emailValidator]"
            />
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="isSignatureDialogVisible = false">
                Avbryt
            </VBtn>
            <VBtn type="submit">
                Skicka
            </VBtn>
          </VCardText>
        </VForm>
      </VCard>
    </VDialog>

    <!-- 👉 Placement Modal -->
    <VDialog
      v-model="isPlacementModalVisible"
      fullscreen
      :scrim="false"
      transition="dialog-bottom-transition"
      @update:modelValue="handlePlacementModalClose"
    >
      <VCard>
        <VToolbar
          dark
          color="primary"
        >
          <VBtn
            icon
            dark
            @click="isPlacementModalVisible = false"
          >
            <VIcon>mdi-close</VIcon>
          </VBtn>
          <VToolbarTitle>Placera signatur för {{ selectedDocument.title }}</VToolbarTitle>
          <VSpacer />
          <VToolbarItems>
            <VBtn
              variant="text"
              :disabled="!signaturePlacement.visible"
              @click="openSignatureDialog(selectedDocument)"
            >
              Bekräfta och skicka
            </VBtn>
          </VToolbarItems>
        </VToolbar>
        
        <VCardText class="pa-0 d-flex justify-center align-center" style="background-color: #525659; height: calc(100vh - 64px);">
          <VProgressCircular
            v-if="isLoadingPlacementPdf"
            indeterminate
            color="white"
          />
          <div 
            v-show="!isLoadingPlacementPdf"
            ref="pdfPlacementContainer" 
            class="pdf-container-admin" 
            @click="handleAdminPdfClick"
          >
            <vue-pdf-embed :source="placementPdfSource" />

            <div 
              v-if="signaturePlacement.visible"
              class="signature-placeholder-admin"
              :style="{ left: signaturePlacement.absoluteX + 'px', top: signaturePlacement.absoluteY + 'px' }"
            >
              <span class="signature-placeholder-content">
                <VIcon icon="mdi-draw" />
                <span>Signera här</span>
              </span>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Tracker Dialog -->
    <VDialog v-model="isTrackerDialogVisible" max-width="600" content-class="tracker-dialog">
      <VCard class="tracker-card">
        <VCardTitle class="d-flex justify-space-between align-center pa-6">
          <span class="tracker-title">Signaturprocess</span>
          <VBtn icon variant="text" @click="isTrackerDialogVisible = false">
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        
        <VCardText class="pa-6 pt-0">
          <div class="snake-timeline-container">
            <template v-for="(item, index) in trackerEvents" :key="item.key">
              <!-- Item -->
              <div 
                class="snake-item" 
                :class="item.side === 'right' ? 'row-right' : 'row-left'"
              >
                 <div class="snake-line-box"></div>
                 <div class="snake-content-wrapper">
                    <div class="snake-meta">{{ item.meta }}</div>
                    <div class="snake-title">{{ item.title }}</div>
                    <div class="snake-text">{{ item.text }}</div>
                    <div v-if="(item.key === 'created' || item.key === 'signed') && trackerDocument" class="snake-file" @click="openTrackerPreview">
                       <img :src="pdfIcon" width="16" class="me-2" />
                       <span>{{ item.key === 'created' ? trackerDocument.file?.split('/').pop() : 'Signerad PDF' }}</span>
                    </div>
                 </div>
                 <div class="snake-dot" :style="{ backgroundColor: item.color }"></div>
              </div>
            </template>
          </div>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Tracker Preview Dialog -->
    <VDialog v-model="isTrackerPreviewVisible" max-width="900">
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          <span>Förhandsvisa dokument</span>
          <VBtn icon variant="text" @click="isTrackerPreviewVisible = false">
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText class="d-flex justify-center" style="min-height:400px;">
          <VProgressCircular v-if="isTrackerPreviewLoading" indeterminate color="primary" />
          <div v-else class="w-100">
            <VAlert v-if="trackerPreviewError" type="error" class="mb-4">{{ trackerPreviewError }}</VAlert>
            <vue-pdf-embed v-if="trackerPreviewPdfSource && !trackerPreviewError" :source="trackerPreviewPdfSource" style="width:100%;" />
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

  :deep(.pdf-container-admin) {
    position: relative;
    cursor: crosshair;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
    width: 90%;
    max-width: 800px;
    height: 95%;
    overflow-y: auto;
  }

  :deep(.pdf-container-admin > div){
    width: 100% !important;
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
      border: 2px dashed #ffc107;
      background-color: rgba(255, 193, 7, 0.2);
      border-radius: 8px;
      padding: 8px 12px;
      color: #ffc107;
      font-weight: 600;
      white-space: nowrap;
    }

  .tracker-card {
    border-radius: 16px !important;
  }

  .tracker-title {
    font-weight: 600;
    color: #454545;
    font-size: 1.25rem;
  }

  .snake-timeline-container {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 350px;
    margin: 0 auto;
    padding: 20px 0;
  }

  .snake-item {
    position: relative;
    display: flex;
    width: 100%;
    padding-bottom: 40px;
    box-sizing: border-box;
    margin-top: -2px; /* Overlap for continuous line */
  }

  .snake-item:first-child {
    margin-top: 0;
  }

  .snake-item:not(:first-child) {
    padding-top: 40px;
  }

  .snake-item:last-child {
    padding-bottom: 0;
  }

  .snake-line-box {
    position: absolute;
    top: 0;
    bottom: 0;
    width: calc(50% + 1px); /* Slight overlap to avoid gap */
    pointer-events: none;
    z-index: 1;
  }

  /* Row Right (Odd items visually, Index 0, 2...) */
  .row-right {
    justify-content: flex-start;
  }

  .row-right .snake-line-box {
    right: 0;
    border-right: 2px solid #E7E7E7;
  }

  /* Row Left (Even items visually, Index 1, 3...) */
  .row-left {
    justify-content: flex-end;
  }

  .row-left .snake-line-box {
    left: 0;
    border-left: 2px solid #E7E7E7;
  }

  /* Bottom Connections (Curve to next) */
  .snake-item:not(:last-child) .snake-line-box {
    border-bottom: 2px solid #E7E7E7;
  }

  .row-right:not(:last-child) .snake-line-box {
    border-bottom-right-radius: 50px;
  }

  .row-left:not(:last-child) .snake-line-box {
    border-bottom-left-radius: 50px;
  }

  /* Top Connections (Curve from prev) */
  .snake-item:not(:first-child) .snake-line-box {
    border-top: 2px solid #E7E7E7;
  }

  .row-right:not(:first-child) .snake-line-box {
    border-top-right-radius: 50px;
  }

  .row-left:not(:first-child) .snake-line-box {
    border-top-left-radius: 50px;
  }

  .snake-content-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
    min-height: 144px;
    box-sizing: border-box;
    background-color: rgb(var(--v-theme-surface));
    border-radius: 20px;
    padding: 20px 22px;
    z-index: 2;
  }

  /* Spacing for content */
  .row-right .snake-content-wrapper {
    margin-right: 40px;
  }

  .row-left .snake-content-wrapper {
    margin-left: 40px;
  }

  .snake-meta {
    font-size: 0.8rem;
    color: #999;
    margin: 0;
  }

  .snake-title {
    font-weight: 600;
    font-size: 1rem;
    color: #333;
    margin: 0;
  }

  .snake-text {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
  }

  .snake-file {
    display: inline-flex;
    align-items: center;
    background: #f5f5f5;
    padding: 4px 8px;
    border-radius: 4px;
    margin-top: 0;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    
    &:hover {
      background: #e0e0e0;
    }
  }

  .snake-dot {
    position: absolute;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    z-index: 3;
    top: 50%;
    transform: translateY(-50%);
  }

  .row-right .snake-dot {
    right: -6px; /* Centered on 2px border */
  }

  .row-left .snake-dot {
    left: -6px; /* Centered on 2px border */
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: signed-documents
</route>
