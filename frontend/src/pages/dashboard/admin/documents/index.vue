<script setup>
import { inject } from 'vue'
import { useSignableDocumentsStores } from '@/stores/useSignableDocuments'
import { requiredValidator, emailValidator } from '@/@core/utils/validators'
import { themeConfig } from '@themeConfig'
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'
import VuePdfEmbed from 'vue-pdf-embed'
import axios from '@/plugins/axios'

const documentsStores = useSignableDocumentsStores()
const emitter = inject("emitter")

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
const isValid = ref(false)
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

const openStaticSignatureDialog = (documentData) => {
  selectedDocument.value = { ...documentData };
  isStaticSignatureFlow.value = true
  signatureEmail.value = ''
  isSignatureDialogVisible.value = true
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
      throw new Error("No se pudo encontrar la referencia del contenedor del PDF.");
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
  <section>
    <VRow>
      <VDialog
        v-model="isRequestOngoing"
        width="auto"
        persistent>
        <VProgressCircular
          indeterminate
          color="primary"
          class="mb-0"/>
      </VDialog>

      <VCol cols="12">
        <VAlert
          v-if="advisor.show"
          :type="advisor.type"
          class="mb-6">
          {{ advisor.message }}
        </VAlert>

        <Toaster />

        <VCard title="">
          <VCardText class="d-flex align-center flex-wrap gap-4">
            <div class="d-flex align-center w-100 w-md-auto">
              <span class="text-no-wrap me-3">Visa:</span>
              <VSelect
                v-model="rowPerPage"
                density="compact"
                variant="outlined"
                class="w-100"
                :items="[10, 20, 30, 50]"/>
            </div>

            <VSpacer class="d-md-block"/>

            <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto">           
              <!-- 👉 Search  -->
              <div class="search">
                <VTextField
                  v-model="searchQuery"
                  placeholder="Sök"
                  density="compact"
                  clearable
                />
              </div>

              <!-- 👉 Add document button -->
              <VBtn
                v-if="$can('create','documents')"
                class="w-100 w-md-auto"
                prepend-icon="tabler-plus"
                @click="openUploadModal">
                Skapa
              </VBtn>
            </div>
          </VCardText>

          <VDivider />

          <VTable class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> TITEL </th>
                <th scope="col"> BESKRIVNING </th>
                <th scope="col"> SKAPAD </th>
                <th scope="col"> SIGNERA STATUS </th>
                <th scope="col" v-if="$can('edit', 'documents') || $can('delete', 'documents')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="document in documents"
                :key="document.id"
                style="height: 3rem;">
                <td> 
                  {{ document.title }}
                </td>
                <td> 
                  {{ document.description || '-' }}
                </td>
                <td>  
                  {{ new Date(document.created_at).toLocaleString('sv-SE', { 
                      year: 'numeric', 
                      month: '2-digit', 
                      day: '2-digit', 
                      hour: '2-digit', 
                      minute: '2-digit',
                      hour12: false
                  }) }}
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
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'documents') || $can('delete', 'documents')">      
                  <VMenu>
                    <template #activator="{ props }">
                      <VBtn v-bind="props" icon variant="text" color="default" size="x-small">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                          <path d="M12.52 20.924c-.87 .262 -1.93 -.152 -2.195 -1.241a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.088 .264 1.502 1.323 1.242 2.192"></path>
                          <path d="M19 16v6"></path>
                          <path d="M22 19l-3 3l-3 -3"></path>
                          <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                        </svg>
                      </VBtn>
                    </template>
                    <VList>
                      <VListItem v-if="$can('edit','documents')" @click="startPlacementProcess(document)">
                        <template #prepend>
                          <VIcon icon="mdi-draw" />
                        </template>
                        <VListItemTitle>Signera</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('view', 'documents')"
                         @click="openLink(document)">
                        <template #prepend>
                          <VIcon icon="mdi-file-pdf-box" />
                        </template>
                        <VListItemTitle>Visa som PDF</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('view','documents')" @click="download(document)">
                        <template #prepend>
                          <VIcon icon="mdi-cloud-download-outline"/>
                        </template>
                        <VListItemTitle>Ladda ner</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('delete','documents')" @click="showDeleteDialog(document)">
                        <template #prepend>
                          <VIcon icon="tabler-trash" />
                        </template>
                        <VListItemTitle>Ta bort</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </td>
              </tr> 
            </tbody>
            <!-- 👉 table footer  -->
            <tfoot v-show="!documents.length">
              <tr>
                <td
                  colspan="5"
                  class="text-center">
                  Uppgifter ej tillgängliga
                </td>
              </tr>
            </tfoot>
          </VTable>
        
          <VDivider />

          <VCardText class="d-block d-md-flex text-center align-center flex-wrap gap-4 py-3">
            <span class="text-sm text-disabled">
              {{ paginationData }}
            </span>

            <VSpacer class="d-none d-md-block"/>
            
            <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="5"
              :length="totalPages"/>
          
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <VCard title="Ta bort dokument">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort dokumentet <strong>{{ selectedDocument.title }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeDocument">
              Acceptera
          </VBtn>
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

<style scoped>
  .search {
      width: 100%;
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
  @media(min-width: 991px){
      .search {
          width: 20rem;
      }
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: documents
</route>
