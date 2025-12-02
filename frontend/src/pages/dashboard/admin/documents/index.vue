<script setup>

import { inject } from 'vue'
import { useDisplay } from "vuetify";
import { useSignableDocumentsStores } from '@/stores/useSignableDocuments'
import { requiredValidator, emailValidator } from '@/@core/utils/validators'
import { themeConfig } from '@themeConfig'
import { avatarText } from "@/@core/utils/formatters";
import Toaster from "@/components/common/Toaster.vue";
import VuePdfEmbed from 'vue-pdf-embed'
import axios from '@/plugins/axios'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

import eyeIcon from "@/assets/images/icons/figma/eye.svg";
import editIcon from "@/assets/images/icons/figma/edit.svg";
import wasteIcon from "@/assets/images/icons/figma/waste.svg";

const documentsStores = useSignableDocumentsStores()
const emitter = inject("emitter")
const router = useRouter()

const { mdAndDown } = useDisplay();
const sectionEl = ref(null);
const hasLoaded = ref(false);
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

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
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});

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

  await documentsStores.fetchDocuments(data)

  documents.value = documentsStores.getDocuments
  totalPages.value = documentsStores.last_page
  totalDocuments.value = documentsStores.documentsTotalCount
  
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

const goToTracker = (documentData) => {
  router.push(`/dashboard/admin/documents/${documentData.id}/sparare`)
}

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
        class="d-flex justify-space-between"
        :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
      >
        <div class="d-flex align-center w-100 w-md-auto font-blauer">
          <h2>Dokument <span v-if="hasLoaded">({{ documents.length }})</span></h2>
        </div>

        <div class="d-flex gap-4 title-action-buttons">
          <VBtn
            v-if="$can('create', 'signed-documents') && !$vuetify.display.smAndDown"
            class="btn-gradient w-100 w-md-auto"
            @click="openUploadModal"
          >
            <VIcon icon="custom-plus" size="24" />
            Skapa
          </VBtn>

          <VBtn
            v-if="$vuetify.display.smAndDown && $can('create', 'signed-documents')"
            class="btn-gradient w-100 w-md-auto"
            @click="openUploadModal"
          >
            <VIcon icon="custom-plus" size="24" />
            Skapa
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.smAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-4 filter-bar"
        :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VBtn class="btn-white-2" v-if="!$vuetify.display.smAndDown">
          <VIcon icon="custom-filter" size="24" />
          <span class="d-none d-md-block">Filtrera efter</span>
        </VBtn>

        <div
          v-if="!$vuetify.display.smAndDown"
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
        v-if="!$vuetify.display.smAndDown"
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
            <!-- 👉 Acciones -->
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
        :class="$vuetify.display.smAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.smAndDown ? 80 : 120"
          icon="custom-f-user"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Du har inga dokument än</div>
          <div class="empty-state-text">
            Ladda upp dokument här för att hantera signeringar.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'signed-documents') && !$vuetify.display.smAndDown"
          @click="openUploadModal"
        >
          Ladda upp dokument
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.smAndDown && $can('create', 'signed-documents')"
          @click="openUploadModal"
        >
          Ladda upp dokument
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
      max-width="600"
    >
      <VBtn
        icon
        variant="text"
        color="default"
        size="small"
        @click="isUploadModalVisible = false"
        style="position: absolute; top: 10px; right: 10px; z-index: 1;"
      >
        <VIcon icon="tabler-x" />
      </VBtn>
    
      <VForm
        ref="uploadForm"
        @submit.prevent="submitUpload"
      >
        <VCard title="Ladda upp dokument">
          <VCardText>
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
                  accept=".pdf"
                  :rules="[requiredValidator]"
                  @change="handleFileSelect"
                />
              </VCol>
            </VRow>
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="isUploadModalVisible = false"
            >
              Avbryt
            </VBtn>
            <VBtn type="submit">
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
  </section>
</template>

<style lang="scss" scoped>
  .page-section {
    display: flex;
    flex-direction: column;
  }

  .search {
    width: 100% !important;
    .v-field__input {
      background: url(~@/assets/images/icons/figma/searchIcon.svg) no-repeat left
        1rem center !important;
    }
  }

  .dialog-bottom-full-width {
    .v-card {
      border-radius: 24px 24px 0 0 !important;
    }
  }

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
</style>
<route lang="yaml">
  meta:
    action: view
    subject: signed-documents
</route>
