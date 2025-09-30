<script setup>

import { useAgreementsStores } from '@/stores/useAgreements'
import { requiredValidator, emailValidator } from '@/@core/utils/validators'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { formatNumber } from '@/@core/utils/formatters'
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'
import VuePdfEmbed from 'vue-pdf-embed'
const agreementsStores = useAgreementsStores()
const emitter = inject("emitter")

const agreements = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalAgreements = ref(0)
const isRequestOngoing = ref(true)
const isConfirmDeleteDialogVisible = ref(false)
const isSignatureDialogVisible = ref(false) 
const signatureEmail = ref('')              
const refSignatureForm = ref()              
const isConfirmSendMailVisible = ref(false)
const emailDefault = ref(true)
const selectedTags = ref([])
const existingTags = ref([])
const isValid = ref(false)
const selectedAgreement = ref({})

const agreementTypes = ref([])

const isPlacementModalVisible = ref(false) // Controla el modal del PDF
const placementPdfSource = ref(null)      // Almacena la URL del PDF a mostrar
const isLoadingPlacementPdf = ref(false)  // Muestra un spinner mientras carga el PDF
const signaturePlacement = ref({ x: 0, y: 0, page: 1, visible: false }) // Coordenadas elegidas
const pdfPlacementContainer = ref(null) // Ref para el contenedor del PDF

// Modal select type contract
const isModalVisible = ref(false)
const agreement_type_id = ref(null) 
const refVForm = ref()

const userData = ref(null)
const role = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = agreements.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = agreements.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalAgreements.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

onMounted(async () => {

  await loadData()
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
    page: currentPage.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await agreementsStores.fetchAgreements(data)

  agreements.value = agreementsStores.getAgreements
  totalPages.value = agreementsStores.last_page
  totalAgreements.value = agreementsStores.agreementsTotalCount

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name
  
  isRequestOngoing.value = false

}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const loadData = async () => {
  await agreementsStores.info()

  agreementTypes.value = agreementsStores.agreementTypes
}

const editAgreement = agreementData => {

  var route = ''

  switch(agreementData.agreement_type_id) {
    case 1: 
      route = 'dashboard-admin-agreements-sales-edit-id'
    break
    case 2:
      route = 'dashboard-admin-agreements-purchase-edit-id'
    break
    case 3:
      route = 'dashboard-admin-agreements-mediation-edit-id'
    break   
    case 4:
      route = 'dashboard-admin-agreements-business-edit-id'
    break 
  }

  router.push({ name : route , params: { id: agreementData.id } })
}

const showDeleteDialog = agreementData => {
  isConfirmDeleteDialogVisible.value = true
  selectedAgreement.value = { ...agreementData }
}

const addTag = (event) => {
  const newTag = event.target.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (newTag && emailRegex.test(newTag)) {
    // no hago nada, sino invalido
  } else {
    isValid.value = true
    selectedTags.value.pop();
  }
};

const send = agreementData => {
  isConfirmSendMailVisible.value = true
  selectedAgreement.value = { ...agreementData }
}

const sendMails = async () => {

  if(!isValid.value) {
    isConfirmSendMailVisible.value = false
    isRequestOngoing.value = true

    let data = {
      id: selectedAgreement.value.id,
      emailDefault: emailDefault.value,
      emails: selectedTags.value
    }

    let res = await agreementsStores.sendMails(data)

    isRequestOngoing.value = false

    advisor.value = {
      type: res.data.success ? 'success' : 'error',
      message: res.data.success ? 'Avtalan är skickad!' : res.data.message,
      show: true
    }

    setTimeout(() => {
      selectedTags.value = []
      existingTags.value = []
      emailDefault.value = true 

      advisor.value = {
        type: '',
        message: '',
        show: false
      }
    }, 3000)

    return true
  }
}

const removeAgreement = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await agreementsStores.deleteAgreement(selectedAgreement.value.id)
  selectedAgreement.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Avtal borttagen!' : res.data.message,
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

const download = async(agreement) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + agreement.file);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = agreement.file.replace('pdfs/', '');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

  } catch (error) {
    console.error('Error:', error);
  }
};

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await agreementsStores.fetchAgreements(data)

  let dataArray = [];
      
  agreementsStores.getAgreements.forEach(element => {

    let data = {
      ID: element.id,
      KONTAKT: element.user.name + ' ' + (element.user.last_name ?? ''),
      E_POST: element.user.email,
      FÖRETAG: element.company ?? '',
      ORGANISATIONSNUMMER: element.organization_number ?? '',
      REGISTRERADE_KUNDER:  element.client_count,
      STATU: element.state.name
    }

    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "agreements", "csv");

  isRequestOngoing.value = false

}

const startPlacementProcess = async (agreementData) => {
  selectedAgreement.value = { ...agreementData };
  isPlacementModalVisible.value = true;
  isLoadingPlacementPdf.value = true;
  signaturePlacement.value.visible = false; // Resetea la posición

  try {
    // Usamos el endpoint que ya teníamos para obtener el PDF
    const response = await axios.get(`/agreements/${agreementData.id}/get-admin-preview-pdf`, {
        responseType: 'blob',
    })
    placementPdfSource.value = URL.createObjectURL(response.data);
  } catch (error) {
    // Si falla (ej. PDF no existe), mostramos un error y cerramos el modal
    advisor.value = { type: 'error', message: 'Kunde inte ladda PDF-dokumentet.', show: true };
    isPlacementModalVisible.value = false;
    setTimeout(() => { advisor.value.show = false }, 3000);
  } finally {
    isLoadingPlacementPdf.value = false;
  }
}

const handleAdminPdfClick = (event) => {
  const container = event.currentTarget; 
  if (!container) return;

  const rect = container.getBoundingClientRect();

  // --- VOLVEMOS A CALCULAR PÍXELES ---
  const x = event.clientX - rect.left;
  const y = event.clientY - rect.top + container.scrollTop;

  signaturePlacement.value = {
    // Guardamos los píxeles, como antes
    x: x,
    y: y,
    page: 1,
    visible: true,
  }
}

const openSignatureDialog = (agreementData) => {
  // Ahora selectedAgreement ya está seteado.
  signatureEmail.value = agreementData.agreement_client?.email || ''
  isSignatureDialogVisible.value = true
}


/**
 * Esta función se ejecuta al hacer clic en "Skicka" dentro del diálogo.
 * Valida el formulario y llama a la acción de Pinia con los datos correctos.
 */
const submitSignatureRequest = async () => {
  // 1. Valida el formulario usando la referencia 'refSignatureForm'
  const { valid } = await refSignatureForm.value?.validate()

  // 2. Si el formulario no es válido (ej: email vacío o incorrecto), no hace nada.
  if (!valid) return

  // 3. Cierra el diálogo y muestra el spinner de carga
  isSignatureDialogVisible.value = false
  isPlacementModalVisible.value = false
  isRequestOngoing.value = true
  
  try {

    const container = pdfPlacementContainer.value;
    if (!container) {
      throw new Error("No se pudo encontrar la referencia del contenedor del PDF.");
    }

    // Convertimos los píxeles guardados a porcentajes
    const x_percent = (signaturePlacement.value.x / container.offsetWidth) * 100;
    const y_percent = (signaturePlacement.value.y / container.scrollHeight) * 100;
    // 4. Prepara el 'payload' que espera nuestra acción de Pinia
    const payload = {
      agreementId: selectedAgreement.value.id,
      email: signatureEmail.value,
      x: x_percent.toFixed(2),
      y: y_percent.toFixed(2),
      page: signaturePlacement.value.page,
    }

    // 5. Llama a la acción de Pinia que modificamos en el paso anterior
    const response = await agreementsStores.requestSignature(payload)
    
    // 6. Muestra el mensaje de éxito
    advisor.value = {
      type: 'success',
      message: response.data.message || 'Signeringsförfrågan har skickats!',
      show: true,
    }

    // 7. Refresca los datos de la tabla para mostrar el nuevo estado de la firma
    await fetchData() 
    
  } catch (error) {
    // Maneja el error como antes
    advisor.value = {
      type: 'error',
      message: error.response?.data?.message || 'Ett fel uppstod när begäran skickades.',
      show: true,
    }
    console.error('Error sending signature request:', error.response)
  } finally {
    // Limpia todo y oculta el spinner
    isRequestOngoing.value = false
    signatureEmail.value = '' // Limpia el email para la próxima vez
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
  }
}

const handleCloseModal = () => {
  isModalVisible.value = false
}

const addAgreements = () => {

  refVForm.value?.validate().then(({ valid: isValid }) => {

    if (isValid) {

      switch(agreement_type_id.value) {
        case 1: 
          router.push({ name : 'dashboard-admin-agreements-sales' })
        break
        case 2:
          router.push({ name : 'dashboard-admin-agreements-purchase' })
        break
        case 3:
          router.push({ name : 'dashboard-admin-agreements-mediation' })
        break   
        case 4:
          router.push({ name : 'dashboard-admin-agreements-business' })
        break 
      }

    }

  })
}

const openLink = function (agreementData) {
  window.open(themeConfig.settings.urlStorage + agreementData.file)
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

            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-file-export"
              class="w-100 w-md-auto"
              @click="downloadCSV">
              Exportera
            </VBtn>

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

              <!-- 👉 Add user button -->
              <VBtn
                v-if="$can('create','agreements')"
                class="w-100 w-md-auto"
                prepend-icon="tabler-plus"
                @click="isModalVisible = true">
                Skapa
              </VBtn>
            </div>
          </VCardText>

          <VDivider />

          <VTable class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> REG. NR </th>
                <th scope="col"> INBYTESFORDON REG. NR </th>
                <th scope="col" class="text-end"> KREDIT / LEASING </th>
                <th scope="col"> TYP </th>
                <th scope="col"> SKAPAD </th>
                <th scope="col"> SKAPAD AV </th>
                <th scope="col"> SIGNERA STATUS </th>
                <th scope="col" v-if="$can('edit', 'agreements') || $can('delete', 'agreements')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="agreement in agreements"
                :key="agreement.id"
                style="height: 3rem;">
                <td> 
                  {{ agreement.agreement_type_id === 4 ?
                    agreement.offer.reg_num : 
                    (agreement.agreement_type_id === 3 ? 
                      agreement.commission?.vehicle.reg_num   :
                      agreement.vehicle_client?.vehicle.reg_num 
                    )                    
                  }} 
                </td>
                <td> {{ agreement.vehicle_interchange?.reg_num }} </td>                
                <td class="text-end"> {{ formatNumber(agreement.installment_amount ?? 0) }} kr </td>
                <td> {{ agreement.agreement_type.name  }}</td>          
                <td>  
                  {{ new Date(agreement.created_at).toLocaleString('sv-SE', { 
                      year: 'numeric', 
                      month: '2-digit', 
                      day: '2-digit', 
                      hour: '2-digit', 
                      minute: '2-digit',
                      hour12: false
                  }) }}
                </td>
                <td> 
                  {{ agreement.supplier ? 
                    agreement.supplier.user.name + ' ' + agreement.supplier.user.last_name : 
                    userData.name + ' ' + userData.last_name
                   }}
                </td>
                <td></td>
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'billing') || $can('delete', 'billing')">      
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
                      <VListItem v-if="$can('edit','agreements')" @click="startPlacementProcess(agreement)">
                        <template #prepend>
                          <VIcon icon="mdi-draw" />
                        </template>
                        <VListItemTitle>Signera</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('view', 'agreements')"
                         @click="openLink(agreement)">
                        <template #prepend>
                          <VIcon icon="mdi-file-pdf-box" />
                        </template>
                        <VListItemTitle>Visa som PDF</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('view','agreements')" @click="send(agreement)">
                        <template #prepend>
                          <VIcon icon="mdi-file-pdf-box" />
                        </template>
                        <VListItemTitle>Sänd PDF</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('view','agreements')" @click="download(agreement)">
                        <template #prepend>
                          <VIcon icon="mdi-cloud-download-outline"/>
                        </template>
                        <VListItemTitle>Ladda ner</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('edit','agreements')" @click="editAgreement(agreement)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('delete','agreements')" @click="showDeleteDialog(agreement)">
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
            <tfoot v-show="!agreements.length">
              <tr>
                <td
                  colspan="8"
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
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Ta bort avtal">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort avtal <strong>#{{ selectedAgreement.agreement_id }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeAgreement">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- ============================================= -->
    <!-- MODAL PARA ENVIAR LA FIRMA -->
    <!-- ============================================= -->
    <VDialog
      v-model="isSignatureDialogVisible"
      persistent
      class="v-dialog-sm"
    >
      <!-- Botón de cierre del diálogo -->
      <DialogCloseBtn @click="isSignatureDialogVisible = !isSignatureDialogVisible" />

      <!-- Contenido del Diálogo -->
      <VCard title="Skicka signeringsförfrågan">
        <!-- Usamos VForm para poder validar el campo de email -->
        <VForm
          ref="refSignatureForm"
          @submit.prevent="submitSignatureRequest"
        >
          <VDivider class="mt-4"/>
          <VCardText>
            Ange e-postadressen dit signeringslänken ska skickas för avtal <strong>#{{ selectedAgreement.agreement_id }}</strong>.
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

    <!--Modal Select type Contract-->
    <VDialog
      v-model="isModalVisible"
      max-width="500"
    >
      <VBtn
        icon
        variant="text"
        color="default"
        size="small"
        @click="handleCloseModal"
        style="position: absolute; top: 10px; right: 10px; z-index: 1;"
      >
        <VIcon icon="tabler-x" />
      </VBtn>
    
      <VForm
        ref="refVForm"
        @submit.prevent="addAgreements"
      >
        <!-- Dialog Content -->
        <VCard title="Skapa">
          <VCardText>
            <VRow>
              <VCol cols="12">
                <VAutocomplete
                  v-model="agreement_type_id"
                  :items="agreementTypes"
                  item-title="name"      
                  item-value="id"
                  label="Välj typ"
                  placeholder="Välj typ"
                  :rules="[requiredValidator]"
                  clearable
                />
              </VCol>
            </VRow>
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="handleCloseModal"
            >
              Avbryt
            </VBtn>
            <VBtn type="submit" >
              Bekräfta
            </VBtn>
          </VCardText>
        </VCard>
      </VForm>
    </VDialog>
    <!--End Modal Select type Contract-->  

    <!-- 👉 Confirm send -->
    <VDialog
      v-model="isConfirmSendMailVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmSendMailVisible = !isConfirmSendMailVisible" />

      <!-- Dialog Content -->
      <VCard title="Skicka avtal via e-post">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker på att du vill skicka avtal till följande e-postadresser?
        </VCardText>
        <VCardText class="d-flex flex-column gap-2">
          <VCheckbox
            v-model="emailDefault"
            :label="selectedAgreement.agreement_client.email"
          />

          <VCombobox
            v-model="selectedTags"
            :items="existingTags"
            label="Ange e-postadresser för att skicka avtal"
            multiple
            chips
            deletable-chips
            clearable
            @blur="addTag"
            @keydown.enter.prevent="addTag"
            @input="isValid = false"
          /> 
          <span class="text-xs text-error" v-if="isValid">E-postadressen måste vara en giltig e-postadress</span>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmSendMailVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="sendMails">
              Skicka
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

      <!-- ======================================================= -->
    <!-- INICIO DEL NUEVO DIÁLOGO DE POSICIONAMIENTO PARA EL ADMIN -->
    <!-- ======================================================= -->
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
          <VToolbarTitle>Placera signatur för Avtal #{{ selectedAgreement.agreement_id }}</VToolbarTitle>
          <VSpacer />
          <VToolbarItems>
            <VBtn
              variant="text"
              :disabled="!signaturePlacement.visible"
              @click="openSignatureDialog(selectedAgreement)"
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
              :style="{ left: signaturePlacement.x + 'px', top: signaturePlacement.y + 'px' }"
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
    <!-- ======================================================= -->
    <!-- FIN DEL DIÁLOGO DE POSICIONAMIENTO -->
    <!-- ======================================================= -->  
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
  max-width: 800px; /* Ancho máximo para pantallas grandes */
  height: 95%;     /* Usa casi todo el alto del VCardText */
  overflow-y: auto; /* Permite hacer scroll si el PDF es muy largo */
  
}

:deep(.pdf-container-admin > div){
  width: 100% !important;
}
/* --- FIN DE NUEVA REGLA --- */

:deep(.signature-placeholder-admin) {
    position: absolute;
    z-index: 10;
    pointer-events: none;
  }

  :deep(.signature-placeholder-content) {
    display: inline-flex; /* Asegura que el tamaño se ajuste al contenido */
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
    subject: agreements
</route>