<script setup>

import { ref, toRaw } from "vue";
import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'
import { usePlansStores } from '@/stores/usePlans'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js';
import { excelParser } from '@/plugins/csv/excelParser'
import AddNewPlanDrawer from './AddNewPlanDrawer.vue' 
import AddNewPlanMobile from './AddNewPlanMobile.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const { width: windowWidth } = useWindowSize();

const plansStores = usePlansStores()
const emitter = inject("emitter")

const plans = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalPlans = ref(0)
const isRequestOngoing = ref(true)
const isAddNewPlanDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const isDialogOpen = ref(false)
const isPlanFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | null
const hasLoaded = ref(false);
const isMobileActionDialogVisible = ref(false);
const selectedPlan = ref({})
const features = ref([])
const state_id = ref(null)
const addNewPlanDrawerRef = ref(null);
const addNewPlanMobileRef = ref(null);
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

const failedExternalFlags = ref({})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = plans.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = plans.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalPlans.value} resultat`;

  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalPlans.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

  if (!isAddNewPlanDrawerVisible.value && !isDialogOpen.value)
      selectedPlan.value = {}
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

  await plansStores.fetchPlans(data)

  plans.value = plansStores.getPlans
  totalPages.value = plansStores.last_page
  totalPlans.value = plansStores.plansTotalCount
  features.value = plansStores.getFeatures || [];
  

  isRequestOngoing.value = false
  hasLoaded.value = true
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const resolveStatus = state_id => {
  if (state_id === 2)
    return { class: 'success' }
  if (state_id === 1)
    return { class: 'error' }
}

const showActivateDialog = planData => {
  isConfirmActiveDialogVisible.value = true
  selectedPlan.value = { ...planData }
}

const editPlan = planData => {
    isAddNewPlanDrawerVisible.value = true
    selectedPlan.value = { ...planData }
}

const editPlanMobile = planData => {
    isDialogOpen.value = true
    selectedPlan.value = { ...planData }
}

const showDeleteDialog = planData => {
  isConfirmDeleteDialogVisible.value = true
  selectedPlan.value = { ...planData }
}

const removePlan = async () => {
  isConfirmDeleteDialogVisible.value = false  

  plansStores.deletePlan(selectedPlan.value.id)
    .then((res) => {
        if (res.data.success) {
                advisor.value = {
                type: 'success',
                message: 'Plan raderad!',
                show: true
            }
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
        isRequestOngoing.value = false
    })

  selectedPlan.value = {}

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const submitForm = async (country, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    country.data.append('_method', 'PUT')
    submitUpdate(country)
    return
  }

  submitCreate(country.data)
}


const submitCreate = planData => {

  plansStores.addPlan(planData)
    .then((res) => {
        if (res.data.success) {
            advisor.value = {
                type: 'success',
                message: 'Plan skapad! ',
                show: true
            }

            addNewPlanDrawerRef.value?.reallyCloseAndReset?.();
            addNewPlanMobileRef.value?.reallyCloseAndReset?.();

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

        addNewPlanDrawerRef.value?.reallyCloseAndReset?.();
        addNewPlanMobileRef.value?.reallyCloseAndReset?.();

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

const submitUpdate = planData => {

  plansStores.updatePlan(planData)
    .then((res) => {
        if (res.data.success) {
                advisor.value = {
                type: 'success',
                message: 'Plan uppdaterad!',
                show: true
            }

            addNewPlanDrawerRef.value?.reallyCloseAndReset?.();
            addNewPlanMobileRef.value?.reallyCloseAndReset?.();

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

        addNewPlanDrawerRef.value?.reallyCloseAndReset?.();
        addNewPlanMobileRef.value?.reallyCloseAndReset?.();

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

  plansStores.updateState(selectedPlan.value.id)
    .then((res) => {
        if (res.data.success) {
                advisor.value = {
                type: 'success',
                message: 'Plan uppdaterad!',
                show: true
            }
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
        isRequestOngoing.value = false
    })

  selectedPlan.value = {}

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const findPlan = plan => {
  if (!plan || !Array.isArray(plans.value)) return null

  const normalizeText = value =>
    String(value ?? '')
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")

  if (typeof plan === 'object') {
    return plans.value.find(item => item.id === plan.id) || null
  }

  return plans.value.find(item => String(item.id) === String(plan))
      || plans.value.find(item => normalizeText(item.name) === normalizeText(plan))

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

    await plansStores.fetchPlans(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }
    
    const rows = plansStores.getPlans.map(element => ({
      id: element.id,
      name: element.name,
      price_month: element.price_month,
      price_annual: element.price_annual,
      state: element?.state?.name
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Planer',
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
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.price_month)}</td>
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.price_annual)}</td>
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
                      <td style="text-align: center; width: 50%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Namnet</td>
                      <td style="text-align: center; width: 50%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Månadspris</td>
                      <td style="text-align: center; width: 50%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Årspris</td>
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
        filename: 'plans.pdf',
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

  await plansStores.fetchPlans(data)

  let dataArray = [];
      
  plansStores.getPlans.forEach(element => {

    let data = {
      ID: element.id,
      NAMNET: element.name,
      MÅNADSPRIS: element.price_month,
      ÅRSPRIS: element.price_annual,
      STATUS: element?.state?.name
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "planer", "csv");

  isRequestOngoing.value = false

}

const handleMobileDialogUpdate = (val) => {
  if (val === false && isPlanFormEdited.value) {
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
  if (isPlanFormEdited.value && (isAddNewPlanDrawerVisible.value || isDialogOpen.value)) {
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
    isAddNewPlanDrawerVisible.value = false;
    isDialogOpen.value = false;
    isPlanFormEdited.value = false;
    const go = nextRoute;
    nextRoute = null;
    leaveContext.value = null;
    go();
    return;
  }
  if (leaveContext.value === 'mobile') {
    isDialogOpen.value = false;
    isPlanFormEdited.value = false;
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

const onPlanFormEdited = (val) => {
  isPlanFormEdited.value = !!val;
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
          <h2>Planer</h2>
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
            v-if="$can('create', 'plans') && !$vuetify.display.mdAndDown"
            class="btn-gradient"
            block
            @click="isAddNewPlanDrawerVisible = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny Plan
          </VBtn>
          <VBtn
            v-if="$vuetify.display.mdAndDown && $can('create', 'plans')"
            class="btn-gradient"
            block
            @click="isDialogOpen = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny Plan
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

        <div class="d-flex align-center empty-select" :style="windowWidth < 1024 ? 'width: 96px' : ''">
          <VSelect
            v-model="state_id"
            placeholder="Status"
            class="custom-select-hover"
            :items="states"
            item-title="name"
            item-value="id"
            clearable
          >
            <template #selection="{ item }">
              <div class="activity-mode-option">
                {{ item.raw.name }}
              </div>
            </template>
          </VSelect>
        </div>

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
        v-show="plans.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col"> #ID </th>
            <th scope="col"> Namnet </th>
            <th scope="col"> Månadspris </th>
            <th scope="col"> Årspris</th>
            <th scope="col"> Status </th>
            <th scope="col" v-if="$can('edit', 'plans') || $can('delete', 'plans')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="plans.length">
          <tr 
            v-for="plan in plans"
            :key="plan.id"
            style="height: 3rem;">

            <td> {{ plan.id }} </td>
            <td class="text-wrap w-100">
              <div class="d-flex align-center gap-x-3">
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ plan.name }}
                  </span>
                </div>
              </div>
            </td>
            <td> {{ plan.price_month }} </td>
            <td> {{ plan.price_annual }} </td>
            <td class="text-wrap">
              <div
                class="status-chip"
                :class="`status-chip-${resolveStatus(plan.state.id)?.class}`"
              >
                {{ plan.state.name }}
              </div>
            </td> 
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'plans') || $can('delete', 'plans')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                    v-if="$can('delete','plans') && plan.state_id === 1"
                    @click="showActivateDialog(plan)">
                    <template #prepend>
                      <VIcon icon="custom-check-mark" size="24" />
                    </template>
                    <VListItemTitle>Aktivera</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('delete','plans') && plan.state_id === 2"
                    @click="showActivateDialog(plan)">
                    <template #prepend>
                      <VIcon icon="custom-cancel-2" size="24" />
                    </template>
                    <VListItemTitle>Inaktivera</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if="$can('edit', 'plans')"
                      @click="editPlan(plan)">
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','plans')"
                    @click="showDeleteDialog(plan)">
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
        v-if="!isRequestOngoing && hasLoaded && !plans.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-payment"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga plan skapade än</div>
          <div class="empty-state-text">
            Här kommer alla dina skapade plan att listas.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'plans') && !$vuetify.display.mdAndDown"
          @click="isAddNewPlanDrawerVisible = true"
        >
          Skapa ny plan
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'plans')"
          @click="isAddNewPlanDrawerVisible = true"
        >
          Skapa ny plan
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
          class="expansion-panels pb-6 px-6"
          v-if="plans.length && windowWidth < 1024"
        >
          <VExpansionPanel v-for="plan in plans" :key="plan.id">
            <VExpansionPanelTitle
              collapse-icon="custom-chevron-right"
              expand-icon="custom-chevron-down"
            >
              <span class="order-id">{{ plan.id }}</span>
              <div class="order-title-box">
                <span class="title-panel">{{ plan.name }}</span>
              </div>
            </VExpansionPanelTitle>
            
            <VExpansionPanelText>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Status</div>
                <div class="expansion-panel-item-value">
                  <div
                    class="status-chip"
                    :class="`status-chip-${resolveStatus(plan.state.id)?.class}`"
                  >
                    {{ plan.state.name }}
                  </div>
                </div>
              </div>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Månadspris</div>
                <div class="expansion-panel-item-value">
                    {{ plan.price_month }}
                </div>
              </div>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Årspris</div>
                <div class="expansion-panel-item-value">
                    {{ plan.price_annual }}
                </div>
              </div>
              

              <div class="d-flex justify-end flex-wrap gap-4">
                <VBtn 
                  v-if="$can('edit', 'plans')"
                  class="btn-light" 
                  icon 
                  @click="selectedPlan = plan; isMobileActionDialogVisible = true"
                >
                  <VIcon icon="custom-dots-vertical" size="24" />
                </VBtn>
              </div>
            </VExpansionPanelText>
          </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="plans.length"
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


    <!-- 👉 Add New Plan -->
    <AddNewPlanDrawer
      ref="addNewPlanDrawerRef"
      v-model:isDrawerOpen="isAddNewPlanDrawerVisible"
      :plan="selectedPlan"
      :features="features"
      @plan-data="submitForm"
      @edited="onPlanFormEdited"
    />
    
      
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
              Ta bort plan
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Är du säker att du vill ta bort plan <strong>{{ selectedPlan.name }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removePlan">
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
        <AddNewPlanMobile
          ref="addNewPlanMobileRef"
          v-model:isDrawerOpen="isDialogOpen"
          :plan="selectedPlan"
          :features="features"
          @plan-data="submitForm"
          @edited="onPlanFormEdited"
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
            {{ selectedPlan.state_id === 1 ? 'Aktivera plan' : 'Inaktivera plan' }}
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Är du säker att du vill {{ selectedPlan.state_id === 1 ? 'aktivera' : 'inaktivera' }} plan  <strong>{{ selectedPlan.name }}</strong>?.
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

    <!-- 👉 Mobile Action Dialog -->
    <VDialog
      v-model="isMobileActionDialogVisible"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem
            v-if="$can('delete','plans') && selectedPlan.state_id === 1"
            @click="showActivateDialog(selectedPlan); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-check-mark" size="24" />
            </template>
            <VListItemTitle>Aktivera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete','plans') && selectedPlan.state_id === 2"
            @click="showActivateDialog(selectedPlan); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-cancel-2" size="24" />
            </template>
            <VListItemTitle>Inaktivera</VListItemTitle>
          </VListItem>
          <VListItem
              v-if="$can('edit', 'plans')"
              @click="editPlanMobile(selectedPlan); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem 
            v-if="$can('delete','plans')"
            @click="showDeleteDialog(selectedPlan); isMobileActionDialogVisible = false;">
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

<style lang="scss">
  .v-select .v-field .v-field__input > input {
    align-self: center !important;
  }

  .supplier-company-logo-avatar {
    .v-img__img {
      object-fit: contain !important;
    }
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: plans
</route>