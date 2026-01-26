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

const { width: windowWidth } = useWindowSize();

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
const filtreraMobile = ref(false);
const isFilterDialogVisible = ref(false);
const skapaMobile = ref(false);

const selectedAgreementForAction = ref({});
const isMobileActionDialogVisible = ref(false);

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
  await loadData()
})

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
  isFilterDialogVisible.value = false;

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

  //refVForm.value?.validate().then(({ valid: isValid }) => {
  var isValid = true;

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

  // })
}

const updateType = (newType) => {
  // Si ya está seleccionado, desmarcarlo (poner null)
  if (agreement_type_id_select.value === newType) {
    newType = null;
  }

  agreement_type_id_select.value = newType;
  filtreraMobile.value = false;
};

const setSkapa = (newSkapa) => {
  // Si ya está seleccionado, desmarcarlo (poner null)
  if (agreement_type_id.value === newSkapa) {
    newSkapa = null;
  }

  agreement_type_id.value = newSkapa;
  skapaMobile.value = false;

  addAgreements();
};


const openLink = function (agreementData) {
  window.open(themeConfig.settings.urlStorage + agreementData.file)
}

// Navigate to agreement tracker (timeline)
const goToTracker = (agreementData) => {
  router.push(`/dashboard/admin/agreements/${agreementData.id}/sparare`)
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
};

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

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
        <div class="d-flex align-center w-100 w-md-auto font-blauer">
          <h2>Avtal <span v-if="hasLoaded">({{ totalAgreements }})</span></h2>
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
            v-if="$can('create','agreements') && $vuetify.display.mdAndDown"
            class="btn-gradient"
            block
            @click="skapaMobile = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Skapa
          </VBtn>

          <VMenu v-if="!$vuetify.display.mdAndDown">
            <template #activator="{ props }">
              <VBtn
                v-if="$can('create','agreements') && !$vuetify.display.mdAndDown"
                class="btn-gradient"
                block
                v-bind="props"
              >
                <VIcon icon="custom-plus" size="24" />
                Skapa
              </VBtn>
            </template>
            <VList>
              <VListItem @click="setSkapa(1)">
                <VListItemTitle>Försäljningsavtal</VListItemTitle>
              </VListItem>

              <VListItem @click="setSkapa(2)">
                <VListItemTitle>Inköpsavtal</VListItemTitle>
              </VListItem>

              <VListItem @click="setSkapa(3)">
                <VListItemTitle>Förmedlingsavtal</VListItemTitle>
              </VListItem>

              <VListItem @click="setSkapa(4)">
                <VListItemTitle>Prisförslag</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.smAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-2'">
          <AppAutocomplete
              v-if="role === 'SuperAdmin' || role === 'Administrator'"
              v-model="supplier_id"
              prepend-icon="custom-profile"
              placeholder="Leverantörer"
              :items="suppliers"
              :item-title="item => item.full_name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              style="width: 200px"
              :menu-props="{ maxHeight: '300px' }"
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
            <VListItem @click="updateType(1)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="agreement_type_id_select === 1"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Försäljningsavtal</VListItemTitle>
            </VListItem>

            <VListItem @click="updateType(2)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="agreement_type_id_select === 2"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Inköpsavtal</VListItemTitle>
            </VListItem>

            <VListItem @click="updateType(3)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="agreement_type_id_select === 3"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Förmedlingsavtal</VListItemTitle>
            </VListItem>

            <VListItem @click="updateType(4)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="agreement_type_id_select === 4"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Prisförslag</VListItemTitle>
            </VListItem>
          </VList>
        </VMenu>

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
        v-show="agreements.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col" class="text-center"> Reg. Nr </th>
            <th scope="col" class="text-center"> Inbytesfordon Reg. Nr </th>
            <th scope="col" class="text-center"> Kredit / Leasing </th>
            <th scope="col" class="text-center"> Typ </th>
            <th scope="col" v-if="role === 'SuperAdmin' || role === 'Administrator'"> LEVERANTÖR </th>
            <th scope="col" class="text-center"> Skapad </th>
            <th scope="col" class="text-center"> 
              Signera status                            
              <VTooltip location="bottom" max-width="200"> 
                <template #activator="{ props }">
                  <span v-bind="props" class="cursor-pointer">
                    <VIcon icon="custom-circle-help" size="20" />
                  </span>
                </template>
                Klicka för att se hur signeringsprocessen fortskrider.
              </VTooltip>
            </th>
            <th scope="col"> Skapad Av </th>
            <th scope="col" v-if="$can('edit', 'agreements') || $can('delete', 'agreements')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody>
          <tr 
            v-for="agreement in agreements"
            :key="agreement.id"
            style="height: 3rem;">
            <td class="text-center" @click="goToTracker(agreement)">
              <span class="font-weight-medium cursor-pointer text-aqua">
                {{ agreement.agreement_type_id === 4 ?
                  agreement.offer.reg_num : 
                  (agreement.agreement_type_id === 3 ? 
                    agreement.commission?.vehicle.reg_num   :
                    agreement.vehicle_client?.vehicle.reg_num 
                  )                    
                }} 
              </span> 
            </td>
            <td class="text-center"> {{ agreement.vehicle_interchange?.reg_num }} </td>                
            <td class="text-center"> {{ formatNumber(agreement.installment_amount ?? 0) }} kr </td>
            <td class="text-center"> {{ agreement.agreement_type.name  }}</td> 
            <td class="text-wrap" v-if="role === 'SuperAdmin' || role === 'Administrator'">
              <span v-if="agreement.supplier">
                {{ agreement.supplier.user.name }}
                {{ agreement.supplier.user.last_name ?? "" }}
              </span>
            </td>         
            <td class="text-center">  
              {{ new Date(agreement.created_at).toLocaleString('sv-SE', { 
                  year: 'numeric', 
                  month: '2-digit', 
                  day: '2-digit', 
                  hour: '2-digit', 
                  minute: '2-digit',
                  hour12: false
              }) }}
            </td>           
            <!-- 👉 Status -->
            <td class="text-center text-wrap d-flex justify-center align-center">
              <div
                v-if="agreement.tokens && agreement.tokens.length > 0"
                class="status-chip"
                :class="`status-chip-${resolveStatus(agreement.tokens[0]?.signature_status)?.class}`"
              >
                <VIcon size="16" :icon="resolveStatus(agreement.tokens[0]?.signature_status)?.icon" class="action-icon" />
                {{ resolveStatus(agreement.tokens[0]?.signature_status)?.name }}
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
                  :variant="agreement.user.avatar ? 'outlined' : 'tonal'"
                  size="38"
                >
                  <VImg
                    v-if="agreement.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + agreement.user.avatar"
                  />
                  <span v-else>{{ avatarText(agreement.user.name) }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ agreement.user.name }} {{ agreement.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="agreement.user.email && agreement.user.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(agreement.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ agreement.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ agreement.user.email }}</span>
                  </span>
                </div>
              </div>
            </td> 
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'agreements') || $can('delete', 'agreements')">      
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
                      <VIcon icon="custom-eye" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Spårare</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit','agreements')" @click="openStaticSignatureDialog(agreement)">
                    <template #prepend>
                      <VIcon icon="custom-signature" class="mr-2" />
                    </template>
                    <VListItemTitle>Signera</VListItemTitle>
                  </VListItem>
                  <VListItem
                     v-if="$can('view', 'agreements')"
                     @click="openLink(agreement)">
                    <template #prepend>
                      <VIcon icon="custom-pdf" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa som PDF</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('view','agreements')" @click="send(agreement)">
                    <template #prepend>
                      <VIcon icon="custom-send" class="mr-2" />
                    </template>
                    <VListItemTitle>Sänd PDF</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('view','agreements')" @click="download(agreement)">
                    <template #prepend>
                      <VIcon icon="custom-download" class="mr-2"/>
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit','agreements')" @click="editAgreement(agreement)">
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('delete','agreements')" @click="showDeleteDialog(agreement)">
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
        v-if="!isRequestOngoing && !agreements.length"
        class="empty-state"
        :class="$vuetify.display.smAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.smAndDown ? 80 : 120"
          icon="custom-f-agreement"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Du har inga sparade avtal</div>
          <div class="empty-state-text">
            Här kommer alla dina köpeavtal och servicedokument att listas. Skapa ditt första avtal för att enkelt hantera och spåra dina överenskommelser.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'agreements') && !$vuetify.display.mdAndDown"
          @click="isModalVisible = true"
        >
          Skapa nytt avtal
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'agreements')"
          @click="skapaMobile = true"
        >
          Skapa nytt avtal
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>        
      </div>

      <div v-if="agreements.length && $vuetify.display.smAndDown" class="pb-6 px-6">
        <div v-for="agreement in agreements" :key="agreement.id" class="mobile-card-wrapper mb-4">
          <div class="card-header-type">
            {{ agreement.agreement_type.name }}
          </div>
          <VExpansionPanels class="custom-expansion-panels expansion-panels">
            <VExpansionPanel elevation="0">
              <VExpansionPanelTitle
                collapse-icon="custom-chevron-right"
                expand-icon="custom-chevron-down"
                style="height: 56px !important;"
              >
                <span class="order-id">{{ agreement.id }}</span>
                <div class="order-title-box">
                  <span class="text-aqua">
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
                <div class="mb-6">
                  <div class="expansion-panel-item-label">Skapad av</div>
                  <div class="expansion-panel-item-value">
                    {{ agreement.user.name }} {{ agreement.user.last_name ?? '' }}
                  </div>
                </div>
                
                <div class="mb-6">
                  <div class="expansion-panel-item-label">Status:</div>
                  <div class="expansion-panel-item-value">
                    <div
                      v-if="agreement.tokens && agreement.tokens.length > 0"
                      class="status-chip"
                      :class="`status-chip-${resolveStatus(agreement.tokens[0]?.signature_status)?.class}`"
                    >
                      <VIcon size="16" :icon="resolveStatus(agreement.tokens[0]?.signature_status)?.icon" class="action-icon" />
                      {{ resolveStatus(agreement.tokens[0]?.signature_status)?.name }}
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
                
                <div class="d-flex gap-4">
                  <VBtn class="btn-light flex-1" @click="goToTracker(agreement)"
                  >
                    <VIcon icon="custom-eye" size="24" />
                    Se detaljer
                  </VBtn>
                  
                  <VBtn class="btn-light" icon @click="selectedAgreementForAction = agreement; isMobileActionDialogVisible = true">
                    <VIcon icon="custom-dots-vertical" size="24" />
                  </VBtn>
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
      persistent
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
        <VCard flat class="card-form">
          <VCardText class="dialog-title-box">
              <VIcon size="32" icon="custom-contract" class="action-icon" />
              <div class="dialog-title">Skapa</div>
          </VCardText>
          <VCardText class="dialog-text">
            <VRow>
              <VCol cols="12">
                <AppAutocomplete
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
          <VIcon size="28" icon="custom-sent" class="mr-2" />
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
  
  <!-- 👉 Mobile Skapa Dialog -->
    <VDialog
      v-model="skapaMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem @click="setSkapa(1)">
            <VListItemTitle>Försäljningsavtal</VListItemTitle>
          </VListItem>

          <VListItem @click="setSkapa(2)">
            <VListItemTitle>Inköpsavtal</VListItemTitle>
          </VListItem>

          <VListItem @click="setSkapa(3)">
            <VListItemTitle>Förmedlingsavtal</VListItemTitle>
          </VListItem>

          <VListItem @click="setSkapa(4)">
            <VListItemTitle>Prisförslag</VListItemTitle>
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
          <VListItem @click="updateType(1)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="agreement_type_id_select === 1"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Försäljningsavtal</VListItemTitle>
          </VListItem>

          <VListItem @click="updateType(2)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="agreement_type_id_select === 2"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Inköpsavtal</VListItemTitle>
          </VListItem>

          <VListItem @click="updateType(3)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="agreement_type_id_select === 3"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Förmedlingsavtal</VListItemTitle>
          </VListItem>

          <VListItem @click="updateType(4)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="agreement_type_id_select === 4"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Prisförslag</VListItemTitle>
          </VListItem>
        </VList>
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
            v-if="role === 'SuperAdmin' || role === 'Administrator'"
            v-model="supplier_id"
            prepend-icon="custom-profile"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="item => item.full_name"
            :item-value="item => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            style="width: 200px"
            :menu-props="{ maxHeight: '300px' }"
            class="selector-user selector-truncate"
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
          <VListItem v-if="$can('edit','agreements')" @click="openStaticSignatureDialog(selectedAgreementForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-signature" class="mr-2" />
            </template>
            <VListItemTitle>Signera</VListItemTitle>
          </VListItem>
          <VListItem
              v-if="$can('view', 'agreements')"
              @click="openLink(selectedAgreementForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-pdf" class="mr-2" />
            </template>
            <VListItemTitle>Visa som PDF</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('view','agreements')" @click="send(selectedAgreementForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-send" class="mr-2" />
            </template>
            <VListItemTitle>Sänd PDF</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('view','agreements')" @click="download(selectedAgreementForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-download" class="mr-2"/>
            </template>
            <VListItemTitle>Ladda ner</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('edit','agreements')" @click="editAgreement(selectedAgreementForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('delete','agreements')" @click="showDeleteDialog(selectedAgreementForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-waste" size="24" class="mr-2" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss" scoped>
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
    border-radius: 16px;
    overflow: hidden;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .card-header-type {
    background-color: #E7E7E7;
    padding: 4px;
    font-weight: 600;
    font-size: 12px;
    line-height: 16px;
    letter-spacing: 0;
    color: #5D5D5D;
    text-align: center;
  }

  .custom-expansion-panels {
    /* Remove default margins/shadows if needed */
    margin-top: 0;
    background-color: #f6f6f6 !important;
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
    /*border-color: #E0E0E0;*/
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    border: solid 1px #6e9383 !important;
    background-color: transparent !important;
  }

</style>
<route lang="yaml">
  meta:
    action: view
    subject: agreements
</route>