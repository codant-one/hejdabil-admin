<script setup>

import { ref, toRaw } from "vue";
import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'
import { useCurrenciesStores } from '@/stores/useCurrencies'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js';
import { excelParser } from '@/plugins/csv/excelParser'
import AddNewCurrencyDrawer from './AddNewCurrencyDrawer.vue' 
import AddNewCurrencyMobile from './AddNewCurrencyMobile.vue' 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const { width: windowWidth } = useWindowSize();

const currenciesStores = useCurrenciesStores()
const emitter = inject("emitter")

const currencies = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalCurrencies = ref(0)
const isRequestOngoing = ref(true)
const isAddNewCurrencyDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const isDialogOpen = ref(false)
const isCurrencyFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | null
const hasLoaded = ref(false);
const isMobileActionDialogVisible = ref(false);
const selectedCurrency = ref({})
const state_id = ref(null)
const addNewCurrencyDrawerRef = ref(null);
const addNewCurrencyMobileRef = ref(null);
const COMPANY_STORAGE_KEY = 'clients_company_snapshot';
const filtreraMobile = ref(false);

const exporteraMobile = ref(false);

const readCachedCompany = () => {
  try {
    const cached = localStorage.getItem(COMPANY_STORAGE_KEY);
    if (!cached) return {};

    const parsed = JSON.parse(cached);
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch {
    return {};
  }
};

const company = ref(readCachedCompany())

const setCompany = (value) => {
  const normalized = value && typeof value === 'object' ? { ...value } : {};
  company.value = normalized;
  localStorage.setItem(COMPANY_STORAGE_KEY, JSON.stringify(normalized));
};

const sectionEl = ref(null);

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const states = ref ([
  { id: 2, name: "Aktiv" },
  { id: 1, name: "Inaktiv" }
])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = currencies.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = currencies.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalCurrencies.value} resultat`;

  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalCurrencies.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewCurrencyDrawerVisible.value && !isDialogOpen.value)
        selectedCurrency.value = {}
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
    state_id.value = null
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    state_id: state_id.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await currenciesStores.fetchCurrencies(data)

  currencies.value = currenciesStores.getCurrencies
  totalPages.value = currenciesStores.last_page
  totalCurrencies.value = currenciesStores.currenciesTotalCount

  isRequestOngoing.value = false
  hasLoaded.value = true
}

watchEffect(registerEvents)

const updateStateId = (newStateId) => {
  // Si ya está seleccionado, desmarcarlo (poner null)
  if (state_id.value === newStateId) {
    newStateId = null;
  }
  
  state_id.value = newStateId;
  filtreraMobile.value = false;
};

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const resolveStatus = state_id => {
  if (state_id === 2)
    return { class: 'success' }
  if (state_id === 1)
    return { class: 'error' }
}

const showActivateDialog = currencyData => {
  isConfirmActiveDialogVisible.value = true
  selectedCurrency.value = { ...currencyData }
}

const editCurrency = currencyData => {
    isAddNewCurrencyDrawerVisible.value = true
    selectedCurrency.value = { ...currencyData }
}

const editCurrencyMobile = currencyData => {
    isDialogOpen.value = true
    selectedCurrency.value = { ...currencyData }
}

const showDeleteDialog = currencyData => {
  isConfirmDeleteDialogVisible.value = true
  selectedCurrency.value = { ...currencyData }
}

const removeCurrency = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await currenciesStores.deleteCurrency(selectedCurrency.value.id)
  selectedCurrency.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Valuta raderad!' : res.data.message,
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

const submitForm = async (currency, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    currency.data.append('_method', 'PUT')
    submitUpdate(currency)
    return
  }

  submitCreate(currency.data)
}


const submitCreate = currencyData => {

  currenciesStores.addCurrency(currencyData)
    .then((res) => {
        if (res.data.success) {
            advisor.value = {
                type: 'success',
                message: 'Valuta skapad! ',
                show: true
            }

            addNewCurrencyDrawerRef.value?.reallyCloseAndReset?.();
            addNewCurrencyMobileRef.value?.reallyCloseAndReset?.();

            fetchData()
        }
        isRequestOngoing.value = false
    })
    .catch((err) => {
        advisor.value = {
            type: 'error',
            message: err.message,
            show: true
        }

        addNewCurrencyDrawerRef.value?.reallyCloseAndReset?.();
        addNewCurrencyMobileRef.value?.reallyCloseAndReset?.();

        isRequestOngoing.value = false
    })

  setTimeout(() => {
      advisor.value = {
          type: '',
          message: '',
          show: false
      }
  }, 3000)
}

const submitUpdate = currencyData => {

  currenciesStores.updateCurrency(currencyData)
    .then((res) => {
        if (res.data.success) {
                advisor.value = {
                type: 'success',
                message: 'Valuta uppdaterad!',
                show: true
            }

            addNewCurrencyDrawerRef.value?.reallyCloseAndReset?.();
            addNewCurrencyMobileRef.value?.reallyCloseAndReset?.();

            fetchData()
        }
        isRequestOngoing.value = false
    })
    .catch((err) => {
        advisor.value = {
            type: 'error',
            message: err.message,
            show: true
        }

        addNewCurrencyDrawerRef.value?.reallyCloseAndReset?.();
        addNewCurrencyMobileRef.value?.reallyCloseAndReset?.();

        isRequestOngoing.value = false
    })

  setTimeout(() => {
      advisor.value = {
          type: '',
          message: '',
          show: false
      }
  }, 3000)
}

const updateState = async () => {
  isConfirmActiveDialogVisible.value = false
  let res = await currenciesStores.updateState(selectedCurrency.value.id)
  selectedCurrency.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Valuta uppdaterad!' : res.data.message,
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

const downloadPDF = async () => {
  exporteraMobile.value = false
  isRequestOngoing.value = true
  const pdfFontFamily = "'Gelion Regular', 'DM Sans', sans-serif"

  const escapeHtml = value => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')

  let pdfContainer = null

  try {
    const data = {
      limit: -1 ,
      orderByField: "id",
      orderBy: "desc"
    }

    await currenciesStores.fetchCurrencies(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }
    
    const rows = currenciesStores.getCurrencies.map(element => ({
      id: element.id,
      name: element.name,
      code: element.code,
      state: element?.state?.name
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Valuta',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      ${(() => {
        return `
      <tr style="height: 48px;">
        <td style="width: 10%; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.id)}</td>
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.name)}</td>
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.code)}</td>
        <td style="width: 40%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.state)}</td>
      </tr>
    `
      })()}
    `).join('')

    pdfContainer = document.createElement('div')
    pdfContainer.innerHTML = `
      <div style="font-family: ${pdfFontFamily} !important; color: #454545; background-color: #FFFFFF; letter-spacing: 0; width: 100%;">
        <table style="width: 100%; border-spacing: 0; border-collapse: separate; font-size: 12px; font-weight: 400;">
          <tbody>
            <tr>
              <td>
                ${headerMarkup}

                <table style="width: 100%; table-layout: fixed; border-spacing: 0; border-collapse: separate; margin-top: 10px; font-family: ${pdfFontFamily} !important; font-size: 12px;">
                  <thead>
                    <tr style="height: 48px;">
                      <td style="text-align: center; width: 10%; padding: 0 12px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Id</td>
                      <td style="text-align: center; width: 50%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Namn</td>
                      <td style="text-align: center; width: 50%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Kod</td>
                      <td style="text-align: center; width: 40%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Status</td>
                    </tr>
                  </thead>
                  <tbody>
                    ${rowsMarkup}
                  </tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    `
    
    document.body.appendChild(pdfContainer)

    await html2pdf()
      .set({
        margin: [12, 10, 12, 10],
        filename: 'valuta.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#FFFFFF' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] },
      })
      .from(pdfContainer)
      .save()
  } finally {
    if (pdfContainer?.parentNode)
      pdfContainer.parentNode.removeChild(pdfContainer)

    isRequestOngoing.value = false
  }

}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await currenciesStores.fetchCurrencies(data)

  let dataArray = [];
      
  currenciesStores.getCurrencies.forEach(element => {

    let data = {
      ID: element.id,
      NAMNET: element.name,
      KOD: element.code,
      STATU: element.state.name
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "valuta", "csv");

  isRequestOngoing.value = false

}


const handleMobileDialogUpdate = (val) => {
  if (val === false && isCurrencyFormEdited.value) {
    // keep dialog open and show confirm
    isDialogOpen.value = true;
    isConfirmLeaveVisible.value = true;
    leaveContext.value = 'mobile';
    return;
  }
  isDialogOpen.value = val;
};

// Guard route changes if client creation form has unsaved changes
onBeforeRouteLeave((to, from, next) => {
  if (isCurrencyFormEdited.value && (isAddNewCurrencyDrawerVisible.value || isDialogOpen.value)) {
    isConfirmLeaveVisible.value = true;
    nextRoute = next;
    leaveContext.value = 'route';
  } else {
    next();
  }
});

const confirmLeave = () => {
  isConfirmLeaveVisible.value = false;
  if (leaveContext.value === 'route' && nextRoute) {
    isAddNewCurrencyDrawerVisible.value = false;
    isDialogOpen.value = false;
    isCurrencyFormEdited.value = false;
    const go = nextRoute;
    nextRoute = null;
    leaveContext.value = null;
    go();
    return;
  }
  if (leaveContext.value === 'mobile') {
    isDialogOpen.value = false;
    isCurrencyFormEdited.value = false;
  }
  leaveContext.value = null;
};

const cancelLeave = () => {
  isConfirmLeaveVisible.value = false;
  if (leaveContext.value === 'route' && nextRoute) {
    nextRoute(false);
    nextRoute = null;
  }
  leaveContext.value = null;
};

const onCurrencyFormEdited = (val) => {
  isCurrencyFormEdited.value = !!val;
};  

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
        <div class="align-center font-blauer">
          <h2>Valuta</h2>
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

        <div class="d-flex gap-4">
          <VMenu v-if="windowWidth >= 1024">
            <template #activator="{ props }">
              <VBtn
                id="payout-export-button"
                class="btn-light w-auto"
                block
                v-bind="props"
              >
                <VIcon icon="custom-export" size="24" />
                Exportera
              </VBtn>
            </template>

            <VList>
              <VListItem @click="downloadPDF">
                <VListItemTitle>Exportera PDF</VListItemTitle>
              </VListItem>
              <VListItem @click="downloadCSV">
                <VListItemTitle>Exportera Excel</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>

          <VBtn
            v-if="windowWidth < 1024"
            id="payout-export-button"
            class="btn-light w-auto"
            block
            @click="exporteraMobile = true"
          >
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

          <VBtn
            v-if="$can('create', 'currencies') && !$vuetify.display.mdAndDown"
            class="btn-gradient"
            block
            @click="isAddNewCurrencyDrawerVisible = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny Valuta
          </VBtn>
          <VBtn
            v-if="$vuetify.display.mdAndDown && $can('create', 'currencies')"
            class="btn-gradient"
            block
            @click="isDialogOpen = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny Valuta
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.mdAndDown ? 'p-6' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

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
            <VListItem @click="updateStateId(1)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 1"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Inaktiv</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(2)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 2"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Aktiv</VListItemTitle>
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
        v-show="currencies.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col"> #ID </th>
            <th scope="col"> Namnet </th>
            <th scope="col" class="text-center"> Status </th>
            <th scope="col" v-if="$can('edit', 'currencies') || $can('delete', 'currencies')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="currencies.length">
          <tr 
            v-for="currency in currencies"
            :key="currency.id"
            style="height: 3rem;">

            <td> {{ currency.id }} </td>
            <td class="text-wrap w-100">
              <div class="d-flex align-center gap-x-3">
                <VAvatar
                  variant="outlined"
                  size="38"
                  >
                  <VImg
                    style="border-radius: 50%;"
                    :src="currency.flag"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ currency.name }}
                  </span>
                  <span class="text-sm text-disabled">
                    {{ currency.code }}
                  </span>
                </div>
              </div>
            </td>
            <td class="d-flex justify-center align-center"> 
              <div
                class="status-chip"
                :class="`status-chip-${resolveStatus(currency.state.id)?.class}`"
              >
                {{ currency.state.name }}
              </div>
            </td>
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'currencies') || $can('delete', 'currencies')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                    v-if="$can('delete','currencies') && currency.state_id === 1"
                    @click="showActivateDialog(currency)">
                    <template #prepend>
                      <VIcon icon="custom-check-mark" size="24" />
                    </template>
                    <VListItemTitle>Aktivera</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('delete','currencies') && currency.state_id === 2"
                    @click="showActivateDialog(currency)">
                    <template #prepend>
                      <VIcon icon="custom-cancel-2" size="24" />
                    </template>
                    <VListItemTitle>Inaktivera</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if="$can('edit', 'currencies')"
                      @click="editCurrency(currency)">
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','currencies')"
                    @click="showDeleteDialog(currency)">
                    <template #prepend>
                      <VIcon icon="custom-waste" size="24" />
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
        v-if="!isRequestOngoing && hasLoaded && !currencies.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-currency"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga valuta skapade än</div>
          <div class="empty-state-text">
            Här kommer alla dina skapade valutas att listas.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'currencies') && !$vuetify.display.mdAndDown"
          @click="isAddNewCurrencyDrawerVisible = true"
        >
          Skapa ny valuta
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'currencies')"
          @click="isDialogOpen = true"
        >
          Skapa ny valuta
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>
      
      <VExpansionPanels
          class="expansion-panels pb-6 px-6"
          v-if="currencies.length && windowWidth < 1024"
        >
          <VExpansionPanel v-for="currency in currencies" :key="currency.id">
            <VExpansionPanelTitle
              collapse-icon="custom-chevron-right"
              expand-icon="custom-chevron-down"
            >
              <div class="d-flex align-center gap-x-3">
                <VAvatar
                  variant="outlined"
                  size="38"
                  >
                  <VImg
                    style="border-radius: 50%;"
                    :src="currency.flag"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ currency.name }}
                  </span>
                  <span class="text-sm text-disabled">
                    {{ currency.code }}
                  </span>
                </div>
              </div>
            </VExpansionPanelTitle>
            
            <VExpansionPanelText>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Status</div>
                <div class="expansion-panel-item-value">
                  <div
                    class="status-chip"
                    :class="`status-chip-${resolveStatus(currency.state.id)?.class}`"
                  >
                    {{ currency.state.name }}
                  </div>
                </div>
              </div>
              

              <div class="d-flex justify-end flex-wrap gap-4">
                <VBtn 
                  v-if="$can('edit', 'currencies')"
                  class="btn-light" 
                  icon 
                  @click="selectedCurrency = currency; isMobileActionDialogVisible = true"
                >
                  <VIcon icon="custom-dots-vertical" size="24" />
                </VBtn>
              </div>
            </VExpansionPanelText>
          </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="currencies.length"
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

    <!-- 👉 Add New Currency -->
    <AddNewCurrencyDrawer
      ref="addNewCurrencyDrawerRef"
      v-model:isDrawerOpen="isAddNewCurrencyDrawerVisible"
      :currency="selectedCurrency"
      @currency-data="submitForm"
      @edited="onCurrencyFormEdited"/>

    <!-- Confirm leave without saving (parent-level for mobile outside-click and route change) -->
    <VDialog
      v-model="isConfirmLeaveVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="cancelLeave"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
        <VCardText class="dialog-title-box">
          <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
          <div class="dialog-title">Avsluta utan att spara?</div>
        </VCardText>
        <VCardText class="dialog-text">
          <strong>Du har osparade ändringar.</strong> Om du lämnar den här vyn nu kommer informationen du har angett inte att sparas.
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="cancelLeave">Avbryt</VBtn>
          <VBtn class="btn-gradient" @click="confirmLeave">Ja, fortsätt</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
        
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filled-waste" class="action-icon" />
          <div class="dialog-title">
            Ta bort valuta
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Är du säker att du vill ta bort valuta <strong>{{ selectedCurrency.name }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeCurrency">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Add New Client Mobile Dialog -->
    <VDialog
      v-model="isDialogOpen"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
      @update:model-value="handleMobileDialogUpdate"
    >
      <VCard>
        <AddNewCurrencyMobile
          ref="addNewCurrencyMobileRef"
          v-model:isDrawerOpen="isDialogOpen"
          :currency="selectedCurrency"
          @currency-data="submitForm"
          @edited="onCurrencyFormEdited"
        />
      </VCard>
    </VDialog>

    <!-- 👉 Confirm Active/Inactive -->
    <VDialog
      v-model="isConfirmActiveDialogVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
        
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmActiveDialogVisible = !isConfirmActiveDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-warning-outlined" class="action-icon" />
          <div class="dialog-title">
            {{ selectedCurrency.state_id === 1 ? 'Aktivera valuta' : 'Inaktivera valuta' }}
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Är du säker att du vill {{ selectedCurrency.state_id === 1 ? 'aktivera' : 'inaktivera' }} valuta  <strong>{{ selectedCurrency.name }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmActiveDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="updateState">
              Acceptera
          </VBtn>
        </VCardText>
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
          <VListItem @click="updateStateId(1)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 1"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Inaktiv</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(2)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                 :model-value="state_id === 2"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Aktiv</VListItemTitle>
          </VListItem>
        </VList>
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
            v-if="$can('delete','currencies') && selectedCurrency.state_id === 1"
            @click="showActivateDialog(selectedCurrency); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-check-mark" size="24" />
            </template>
            <VListItemTitle>Aktivera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete','currencies') && selectedCurrency.state_id === 2"
            @click="showActivateDialog(selectedCurrency); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-cancel-2" size="24" />
            </template>
            <VListItemTitle>Inaktivera</VListItemTitle>
          </VListItem>
          <VListItem
              v-if="$can('edit', 'currencies')"
              @click="editCurrencyMobile(selectedCurrency); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem 
            v-if="$can('delete','currencies')"
            @click="showDeleteDialog(selectedCurrency); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Export Mobile Dialog -->
    <VDialog
      v-model="exporteraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem @click="downloadPDF">
            <VListItemTitle>Exportera PDF</VListItemTitle>
          </VListItem>

          <VListItem @click="downloadCSV">
            <VListItemTitle>Exportera Excel</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>
<route lang="yaml">
  meta:
    action: view
    subject: currencies
</route>