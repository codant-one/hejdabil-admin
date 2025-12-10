<script setup>

import { useDisplay } from "vuetify";
import { useAgreementsStores } from '@/stores/useAgreements'
import { requiredValidator, emailValidator } from '@/@core/utils/validators'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { formatNumber } from '@/@core/utils/formatters'
import { avatarText } from '@/@core/utils/formatters'
import router from '@/router'
import VuePdfEmbed from 'vue-pdf-embed'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

import editIcon from "@/assets/images/icons/figma/edit.svg";
import wasteIcon from "@/assets/images/icons/figma/waste.svg";

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
const hasLoaded = ref(false)
const isSignatureDialogVisible = ref(false) 
const signatureEmail = ref('')              
const refSignatureForm = ref()              
const isConfirmSendMailVisible = ref(false)
const emailDefault = ref(true)
const selectedTags = ref([])
const existingTags = ref([])
const isValid = ref(false)
const selectedAgreement = ref({})
const isStaticSignatureFlow = ref(false)

const agreementTypes = ref([])
const agreement_type_id_select = ref(null)

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
const suppliers = ref([])
const supplier_id = ref(null)

const sectionEl = ref(null);
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = agreements.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = agreements.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalAgreements.value} resultat`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

onMounted(async () => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
  await loadData()
})

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
    supplier_id.value = null
    agreement_type_id_select.value = null
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'created_at',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value,
    agreement_type_id: agreement_type_id_select.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await agreementsStores.fetchAgreements(data)

  agreements.value = agreementsStores.getAgreements
  totalPages.value = agreementsStores.last_page
  totalAgreements.value = agreementsStores.agreementsTotalCount
  hasLoaded.value = true

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  if(role.value === 'SuperAdmin' || role.value === 'Administrator') {
    suppliers.value = agreementsStores.getSuppliers
  }
  
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

const resolveStatus = state => {
  if (state === 'signed')
    return { color: 'info' }
  if (state === 'pending')
    return { color: 'warning' } 
  if (state === 'sent')
    return { color: 'success' } 
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

const openStaticSignatureDialog = (agreementData) => {
  selectedAgreement.value = { ...agreementData }; // Aseguramos que el agreement está seleccionado
  isStaticSignatureFlow.value = true // ¡Importante! Indicamos que es el flujo estático
  signatureEmail.value = agreementData.agreement_client?.email || ''
  isSignatureDialogVisible.value = true // Abrimos el mismo modal de siempre
}

/**
 * Esta función se ejecuta al hacer clic en "Skicka" dentro del diálogo.
 * Valida el formulario y llama a la acción de Pinia con los datos correctos.
 */
const submitPlacementSignatureRequest  = async () => {
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

const submitStaticSignatureRequest = async () => {
  // 1. Valida el formulario
  const { valid } = await refSignatureForm.value?.validate();
  if (!valid) return;

  // 2. Cierra modal y activa spinner
  isSignatureDialogVisible.value = false;
  isRequestOngoing.value = true;

  try {
    // 3. Prepara un payload más simple, sin coordenadas
    const payload = {
      agreementId: selectedAgreement.value.id,
      email: signatureEmail.value,
    };

    // 4. Llama a una NUEVA acción en el store de Pinia
    const response = await agreementsStores.requestStaticSignature(payload);

    // 5. Muestra mensaje de éxito
    advisor.value = {
      type: 'success',
      message: response.data.message || 'Signeringsförfrågan har skickats!',
      show: true,
    };
    await fetchData();

  } catch (error) {
    // Manejo de errores
    advisor.value = {
      type: 'error',
      message: error.response?.data?.message || 'Ett fel uppstod när begäran skickades.',
      show: true,
    };
  } finally {
    // Limpieza
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
    // Asegúrate de que el nombre aquí coincida con cómo renombraste la función original
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

// Navigate to agreement tracker (timeline)
const goToTracker = (agreementData) => {
  router.push(`/dashboard/admin/agreements/${agreementData.id}/sparare`)
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

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex justify-space-between"
        :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
      >
        <div class="d-flex align-center w-100 w-md-auto font-blauer">
          <h2>Avtal <span v-if="hasLoaded">({{ totalAgreements }})</span></h2>
        </div>

        <div class="d-flex gap-4 title-action-buttons">
          <VBtn
            class="btn-light w-100 w-md-auto"
            @click="downloadCSV">
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

          <VBtn
            v-if="$can('create','agreements') && !$vuetify.display.smAndDown"
            class="btn-gradient w-100 w-md-auto"
            @click="isModalVisible = true">
            <VIcon icon="custom-plus" size="24" />
            Skapa
          </VBtn>

           <VBtn
            v-if="$vuetify.display.smAndDown && $can('create', 'agreements')"
            class="btn-gradient w-100 w-md-auto"
            @click="isModalVisible = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Skapa
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.smAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-4"
        :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField
            v-model="searchQuery"
            placeholder="Sök"
            clearable
          />
        </div>

        <VAutocomplete
            v-model="agreement_type_id_select"
            placeholder="Typ av avtal"
            :items="agreementTypes"
            :item-title="item => item.name"
            :item-value="item => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            style="width: 200px"
            :menu-props="{ maxHeight: '300px' }"
        />

        <VAutocomplete
            v-if="role === 'SuperAdmin' || role === 'Administrator'"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="item => item.full_name"
            :item-value="item => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            style="width: 200px"
            :menu-props="{ maxHeight: '300px' }"
        />

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
                <th scope="col" v-if="role === 'SuperAdmin' || role === 'Administrator'"> LEVERANTÖR </th>
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
                <td class="text-wrap">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      :variant="agreement.user.avatar ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="agreement.user.avatar"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + agreement.user.avatar"
                      />
                        <span v-else>{{ avatarText(agreement.user.name) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ agreement.user.name }} {{ agreement.user.last_name ?? '' }} 
                      </span>
                      <span class="text-sm text-disabled">{{ agreement.user.email }}</span>
                    </div>
                  </div>
                </td>
                <td class="text-wrap" v-if="role === 'SuperAdmin' || role === 'Administrator'">
                  <span class="font-weight-medium"  v-if="agreement.supplier">
                    {{ agreement.supplier.user.name }} {{ agreement.supplier.user.last_name ?? '' }} 
                  </span>
                </td>
                <td>
                  <VChip
                    label
                    :color="resolveStatus(agreement.token?.signature_status ?? 'pending')?.color"
                  >
                  <VImg
                    v-if="agreement.user.avatar"
                    style="border-radius: 50%;"
                    :src="themeConfig.settings.urlStorage + agreement.user.avatar"
                  />
                    <span v-else>{{ avatarText(agreement.user.name) }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ agreement.user.name }} {{ agreement.user.last_name ?? '' }} 
                  </span>
                  <span class="text-sm text-disabled">{{ agreement.user.email }}</span>
                </div>
              </div>
            </td>
            <td class="text-wrap" v-if="role === 'SuperAdmin' || role === 'Administrator'">
              <span class="font-weight-medium"  v-if="agreement.supplier">
                {{ agreement.supplier.user.name }} {{ agreement.supplier.user.last_name ?? '' }} 
              </span>
            </td>
            <td>
              <span v-if="agreement.agreement_type_id === 4">
                NO APLICA
              </span>
              <VChip
                v-else
                label
                :color="resolveStatus(agreement.token?.signature_status ?? 'pending')?.color"
              >
                {{ agreement.token?.signature_status ?? 'pending' }}
              </VChip>
            </td>
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'billings') || $can('delete', 'billings')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                    v-if="$can('view','agreements')"
                    @click="goToTracker(agreement)">
                    <template #prepend>
                      <VIcon icon="tabler-timeline" class="mr-2" />
                    </template>
                    <VListItemTitle>Spårare</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit','agreements') && agreement.agreement_type_id !== 4" @click="openStaticSignatureDialog(agreement)">
                    <template #prepend>
                      <VIcon icon="mdi-draw" class="mr-2" />
                    </template>
                    <VListItemTitle>Signera</VListItemTitle>
                  </VListItem>
                  <VListItem
                     v-if="$can('view', 'agreements')"
                     @click="openLink(agreement)">
                    <template #prepend>
                      <VIcon icon="mdi-file-pdf-box" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa som PDF</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('view','agreements')" @click="send(agreement)">
                    <template #prepend>
                      <VIcon icon="mdi-file-pdf-box" class="mr-2" />
                    </template>
                    <VListItemTitle>Sänd PDF</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('view','agreements')" @click="download(agreement)">
                    <template #prepend>
                      <VIcon icon="mdi-cloud-download-outline" class="mr-2"/>
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit','agreements')" @click="editAgreement(agreement)">
                    <template #prepend>
                      <img :src="editIcon" alt="Edit Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('delete','agreements')" @click="showDeleteDialog(agreement)">
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
        v-if="!isRequestOngoing && !agreements.length"
        class="empty-state"
        :class="$vuetify.display.smAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.smAndDown ? 80 : 120"
          icon="custom-f-user"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga avtal hittades</div>
          <div class="empty-state-text">
            Skapa ett nytt avtal för att komma igång.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'agreements')"
          @click="isModalVisible = true"
        >
          Skapa avtal
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <div v-if="agreements.length && $vuetify.display.smAndDown" class="pb-6 px-6">
        <div v-for="agreement in agreements" :key="agreement.id" class="mobile-card-wrapper mb-4">
          <div class="card-header-type">
            {{ agreement.agreement_type.name }}
          </div>
          <VExpansionPanels class="custom-expansion-panels">
            <VExpansionPanel elevation="0">
              <VExpansionPanelTitle
                collapse-icon="tabler-chevron-up"
                expand-icon="tabler-chevron-down"
                class="custom-panel-title"
              >
                <div class="d-flex align-center gap-3 w-100">
                  <VAvatar color="#008C91" size="32" class="white--text">
                    <span class="text-white font-weight-bold">A</span>
                  </VAvatar>
                  <span class="font-weight-medium text-high-emphasis">{{ agreement.id }}</span>
                  <span class="reg-nr-text">
                    Reg. Nr. {{ agreement.agreement_type_id === 4 ?
                        agreement.offer.reg_num : 
                        (agreement.agreement_type_id === 3 ? 
                          agreement.commission?.vehicle.reg_num   :
                          agreement.vehicle_client?.vehicle.reg_num 
                        )                    
                    }}
                  </span>
                </div>
              </VExpansionPanelTitle>
              <VExpansionPanelText>
                <div class="mb-4 mt-2">
                  <div class="text-caption text-disabled mb-1">Skapad Av</div>
                  <div class="font-weight-medium">
                    {{ agreement.user.name }} {{ agreement.user.last_name ?? '' }}
                  </div>
                </div>
                
                <div class="mb-6">
                  <div class="text-caption text-disabled mb-1">Status</div>
                  <div>
                    <span v-if="agreement.agreement_type_id === 4">
                        NO APLICA
                      </span>
                      <VChip
                        v-else
                        label
                        variant="outlined"
                        class="status-chip"
                        :color="resolveStatus(agreement.token?.signature_status ?? 'pending')?.color"
                      >
                        {{ agreement.token?.signature_status ?? 'pending' }}
                      </VChip>
                  </div>
                </div>
                
                <div class="d-flex align-center gap-3">
                  <VBtn
                    variant="outlined"
                    color="#2F3438"
                    class="flex-grow-1 btn-details"
                    rounded="pill"
                    @click="goToTracker(agreement)"
                  >
                    <VIcon icon="custom-eye" class="mr-2" />
                    Se detaljer
                  </VBtn>
                  
                  <VMenu>
                    <template #activator="{ props }">
                      <VBtn
                        v-bind="props"
                        icon
                        variant="outlined"
                        color="#2F3438"
                        class="btn-actions"
                      >
                        <VIcon icon="custom-dots-vertical" />
                      </VBtn>
                    </template>
                    <VList>
                      <VListItem
                        v-if="$can('view','agreements')"
                        @click="goToTracker(agreement)">
                        <template #prepend>
                          <VIcon icon="tabler-timeline" class="mr-2" />
                        </template>
                        <VListItemTitle>Spårare</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('edit','agreements')" @click="openStaticSignatureDialog(agreement)">
                        <template #prepend>
                          <VIcon icon="mdi-draw" class="mr-2" />
                        </template>
                        <VListItemTitle>Signera</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('view', 'agreements')"
                         @click="openLink(agreement)">
                        <template #prepend>
                          <VIcon icon="mdi-file-pdf-box" class="mr-2" />
                        </template>
                        <VListItemTitle>Visa som PDF</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('view','agreements')" @click="send(agreement)">
                        <template #prepend>
                          <VIcon icon="mdi-file-pdf-box" class="mr-2" />
                        </template>
                        <VListItemTitle>Sänd PDF</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('view','agreements')" @click="download(agreement)">
                        <template #prepend>
                          <VIcon icon="mdi-cloud-download-outline" class="mr-2"/>
                        </template>
                        <VListItemTitle>Ladda ner</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('edit','agreements')" @click="editAgreement(agreement)">
                        <template #prepend>
                          <img :src="editIcon" alt="Edit Icon" class="mr-2" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('delete','agreements')" @click="showDeleteDialog(agreement)">
                        <template #prepend>
                          <img :src="wasteIcon" alt="Delete Icon" class="mr-2" />
                        </template>
                        <VListItemTitle>Ta bort</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </div>
              </VExpansionPanelText>
            </VExpansionPanel>
          </VExpansionPanels>
        </div>
      </div>

      <VCardText
        v-if="agreements.length"
        class="d-block d-md-flex align-center flex-wrap gap-4 pt-0 px-6 pb-16"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer class="d-none d-md-block"/>
        
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

    <!-- Dialogs (Keep existing dialogs but check classes) -->
    <!-- Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="action-dialog" >
      
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
            Ta bort avtal
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker att du vill ta bort avtal <strong>#{{ selectedAgreement.agreement_id }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeAgreement">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Signature Dialog -->
    <VDialog
      v-model="isSignatureDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isSignatureDialogVisible = !isSignatureDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
           <div class="dialog-title">Skicka signeringsförfrågan</div>
        </VCardText>
        
        <VForm
          ref="refSignatureForm"
          @submit.prevent="handleSignatureSubmit"
        >
          <VCardText class="dialog-text">
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
        </VForm>
      </VCard>
    </VDialog>

    <!-- Modal Select type Contract -->
    <VDialog
      v-model="isModalVisible"
      max-width="500"
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="handleCloseModal"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
    
      <VForm
        ref="refVForm"
        @submit.prevent="addAgreements"
      >
        <VCard>
          <VCardText class="dialog-title-box">
             <div class="dialog-title">Skapa</div>
          </VCardText>
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

          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn
              class="btn-light"
              @click="handleCloseModal"
            >
              Avbryt
            </VBtn>
            <VBtn class="btn-gradient" type="submit" >
              Bekräfta
            </VBtn>
          </VCardText>
        </VCard>
      </VForm>
    </VDialog>

    <!-- Confirm send -->
    <VDialog
      v-model="isConfirmSendMailVisible"
      persistent
      class="action-dialog" >
      
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmSendMailVisible = !isConfirmSendMailVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
           <div class="dialog-title">Skicka avtal via e-post</div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill skicka avtal till följande e-postadresser?
        </VCardText>
        <VCardText class="d-flex flex-column gap-2">
          <VCheckbox
            v-model="emailDefault"
            :label="selectedAgreement.agreement_client?.email"
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

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmSendMailVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="sendMails">
              Skicka
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Placement Modal (Keep as is mostly, but check if it needs styling) -->
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

  </section>
</template>

<style lang="scss" scoped>
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

  /* PDF Placement Styles */
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

  /* Mobile Card Styles */
  .mobile-card-wrapper {
    border-radius: 12px;
    overflow: hidden;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .card-header-type {
    background-color: #F6F6F6;
    padding: 8px 16px;
    font-size: 0.875rem;
    color: #6D788D;
    text-align: center;
    font-weight: 500;
  }

  .custom-expansion-panels {
    /* Remove default margins/shadows if needed */
    margin-top: 0;
  }

  :deep(.custom-expansion-panels .v-expansion-panel) {
    background-color: transparent !important;
  }

  :deep(.custom-expansion-panels .v-expansion-panel-title) {
    padding: 16px;
    min-height: unset;
  }

  :deep(.custom-expansion-panels .v-expansion-panel-title__overlay) {
    background-color: transparent;
  }

  .reg-nr-text {
    color: #008C91;
    font-weight: 500;
  }

  :deep(.custom-expansion-panels .v-expansion-panel-title__icon .v-icon) {
    color: #008C91 !important;
  }

  .btn-details {
    border-color: #E0E0E0;
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
  }

  .btn-actions {
    border-color: #E0E0E0;
    border-radius: 50%;
    width: 40px;
    height: 40px;
  }

  .status-chip {
    border-radius: 16px;
    padding: 0 12px;
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: agreements
</route>