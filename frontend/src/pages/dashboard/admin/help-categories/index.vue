<script setup>

import { ref } from "vue";
import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'
import { useHelpCategoriesStores } from '@/stores/useHelpCategories'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import { excelParser } from '@/plugins/csv/excelParser'
import AddNewHelpCategoryDrawer from './AddNewHelpCategoryDrawer.vue' 
import AddNewHelpCategoryMobile from './AddNewHelpCategoryMobile.vue' 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import html2pdf from 'html2pdf.js';

const { width: windowWidth } = useWindowSize();

const helpCategoriesStores = useHelpCategoriesStores()
const emitter = inject("emitter")

const helpCategories = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalHelpCategories = ref(0)
const isRequestOngoing = ref(true)
const isAddNewHelpCategoryDrawerVisible = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const isDialogOpen = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isHelpCategoryFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | null
const selectedHelpCategory = ref({})
const hasLoaded = ref(false);
const isMobileActionDialogVisible = ref(false);
const filtreraMobile = ref(false);
const state_id = ref(null);
const addNewHelpCategoryDrawerRef = ref(null);
const addNewHelpCategoryMobileRef = ref(null);
const COMPANY_STORAGE_KEY = 'clients_company_snapshot';

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

const sectionEl = ref(null);

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const normalizeIconPath = value => {
  if (!value || typeof value !== 'string')
    return null

  const trimmed = value.trim()
  if (!trimmed || trimmed === 'null' || trimmed === 'undefined')
    return null

  return trimmed
}

const getHelpCategoryIconUrl = helpCategory => {
  const normalizedIcon = normalizeIconPath(helpCategory?.icon)
  return normalizedIcon ? `${themeConfig.settings.urlStorage}${normalizedIcon}` : null
}

const hasHelpCategoryIcon = helpCategory => Boolean(getHelpCategoryIconUrl(helpCategory))


// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = helpCategories.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    helpCategories.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalHelpCategories.value} resultat`;
});

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewHelpCategoryDrawerVisible.value && !isDialogOpen.value)
        selectedHelpCategory.value = {}
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

  await helpCategoriesStores.fetchHelpCategories(data)

  helpCategories.value = helpCategoriesStores.getHelpCategories
  totalPages.value = helpCategoriesStores.last_page
  totalHelpCategories.value = helpCategoriesStores.helpCategoriesTotalCount

  isRequestOngoing.value = false
  hasLoaded.value = true
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const updateStateId = newStateId => {
  state_id.value = state_id.value === newStateId ? null : newStateId
  filtreraMobile.value = false
}

const resolveStatus = stateId => {
  if (stateId === 2)
    return { class: 'success' }
  if (stateId === 1)
    return { class: 'error' }
}

const showActivateDialog = helpCategoryData => {
  isConfirmActiveDialogVisible.value = true
  selectedHelpCategory.value = { ...helpCategoryData }
}

const editHelpCategory = helpCategoryData => {
    isAddNewHelpCategoryDrawerVisible.value = true
    selectedHelpCategory.value = { ...helpCategoryData }
}

const editHelpCategoryMobile = (helpCategoryData) => {
  isDialogOpen.value = true;
  selectedHelpCategory.value = { ...helpCategoryData };
};

const showDeleteDialog = helpCategoryData => {
  isConfirmDeleteDialogVisible.value = true
  selectedHelpCategory.value = { ...helpCategoryData }
}

const showDeleteHelpCategoryDialog = helpCategoryData => {
  isConfirmDeleteDialogVisible.value = true
  selectedHelpCategory.value = { ...helpCategoryData }
}

const removeHelpCategory = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await helpCategoriesStores.deleteHelpCategory(selectedHelpCategory.value.id)
  selectedHelpCategory.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Kategori raderad!' : res.data.message,
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

const updateState = async () => {
  isConfirmActiveDialogVisible.value = false
  isRequestOngoing.value = true

  try {
    const res = await helpCategoriesStores.updateState(selectedHelpCategory.value.id)
    if (!res.data.success) {
      advisor.value = {
        type: 'error',
        message: res.data.message || 'Kunde inte uppdatera kategorin.',
        show: true
      }
      return false
    }

    selectedHelpCategory.value = {}
    advisor.value = {
      type: 'success',
      message: 'Kategori uppdaterad!',
      show: true
    }
    await fetchData()
    return true
  } catch (error) {
    advisor.value = {
      type: 'error',
      message: error.response?.data?.message || error.message || 'Kunde inte uppdatera kategorin.',
      show: true
    }
    return false
  } finally {
    isRequestOngoing.value = false
    setTimeout(() => {
      advisor.value = {
        type: '',
        message: '',
        show: false
      }
    }, 3000)
  }
}

const submitForm = async (helpCategory, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    helpCategory.data.append('_method', 'PUT')
    submitUpdate(helpCategory)
    return
  }

  submitCreate(helpCategory.data)
}

const submitCreate = helpCategoryData => {

    helpCategoriesStores.addHelpCategory(helpCategoryData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Kategori skapad!',
                    show: true
                }

                addNewHelpCategoryDrawerRef.value?.reallyCloseAndReset?.();
                addNewHelpCategoryMobileRef.value?.reallyCloseAndReset?.();
        
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

            addNewHelpCategoryDrawerRef.value?.reallyCloseAndReset?.();
            addNewHelpCategoryMobileRef.value?.reallyCloseAndReset?.();

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

const submitUpdate = helpCategoryData => {

    helpCategoriesStores.updateHelpCategory(helpCategoryData)
        .then((res) => {
            if (res.data.success) {
                    advisor.value = {
                    type: 'success',
                    message: 'Kategori uppdaterad!',
                    show: true
                }

                addNewHelpCategoryDrawerRef.value?.reallyCloseAndReset?.();
                addNewHelpCategoryMobileRef.value?.reallyCloseAndReset?.();

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

            addNewHelpCategoryDrawerRef.value?.reallyCloseAndReset?.();
            addNewHelpCategoryMobileRef.value?.reallyCloseAndReset?.();

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

    await helpCategoriesStores.fetchHelpCategories(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }

    const rows = helpCategoriesStores.getHelpCategories.map(element => ({
      id: element.id,
      name: element.name
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Kategorier',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      ${(() => {
        return `
      <tr style="height: 46px;">
        <td style="width: 8%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.id)}</td>
        <td style="width: 92%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: left; vertical-align: middle;">${escapeHtml(item.name)}</td>
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
                    <tr style="height: 46px;">
                      <td style="text-align: center; width: 8%; padding: 0 8px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Id</td>
                      <td style="text-align: left; width: 92%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Namn</td>
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
        filename: 'kategorier.pdf',
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
  exporteraMobile.value = false
  isRequestOngoing.value = true

  let data = { limit: -1 }

  await helpCategoriesStores.fetchHelpCategories(data)

  let dataArray = [];
      
  helpCategoriesStores.getHelpCategories.forEach(element => {

    let data = {
      ID: element.id,
      NAMN: element.name
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "kategorier", "csv");

  isRequestOngoing.value = false

}

// Intercept mobile dialog outside-click close
const handleMobileDialogUpdate = (val) => {
  if (val === false && isHelpCategoryFormEdited.value) {
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
  if (isHelpCategoryFormEdited.value && (isAddNewHelpCategoryDrawerVisible.value || isDialogOpen.value)) {
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
    isAddNewHelpCategoryDrawerVisible.value = false;
    isDialogOpen.value = false;
    isHelpCategoryFormEdited.value = false;
    const go = nextRoute;
    nextRoute = null;
    leaveContext.value = null;
    go();
    return;
  }
  if (leaveContext.value === 'mobile') {
    isDialogOpen.value = false;
    isHelpCategoryFormEdited.value = false;
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

const onHelpCategoryFormEdited = (val) => {
  isHelpCategoryFormEdited.value = !!val;
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
            <h2>Kategorier <span v-if="hasLoaded">({{ totalHelpCategories }})</span></h2>
          </div>

          <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

          <div class="d-flex gap-4">
            <VMenu v-if="windowWidth >= 1024">
              <template #activator="{ props }">
                <VBtn
                  id="payout-export-button"
                  class="btn-light w-auto d-none"
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
              class="btn-light w-auto d-none"
              block
              @click="exporteraMobile = true"
            >
              <VIcon icon="custom-export" size="24" />
              Exportera
            </VBtn>

            <VBtn
              v-if="$can('create', 'help-categories') && !$vuetify.display.mdAndDown"
              class="btn-gradient"
              block
              @click="isAddNewHelpCategoryDrawerVisible = true"
            >
              <VIcon icon="custom-plus" size="24" />
              Lägg till kategori
            </VBtn>
            <VBtn
              v-if="$vuetify.display.mdAndDown && $can('create', 'help-categories')"
              class="btn-gradient"
              block
              @click="isDialogOpen = true"
            >
              <VIcon icon="custom-plus" size="24" />
              Lägg till kategorier
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
            v-if="$vuetify.display.mdAndDown"
            class="btn-white-2 px-3"
            @click="filtreraMobile = true"
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
                    />
                  </VListItemAction>
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
                    />
                  </VListItemAction>
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
          v-show="helpCategories.length"
          class="px-4 pb-6 text-no-wrap"
        >
          <!-- 👉 table head -->
          <thead>
            <tr>
              <th scope="col"> #ID </th>
              <th scope="col"> Namn </th>
              <th scope="col" class="text-center"> Status </th>
              <th scope="col" v-if="$can('edit', 'help-categories') || $can('delete', 'help-categories')"></th>
            </tr>
          </thead>
          <!-- 👉 table body -->
          <tbody v-show="helpCategories.length">
            <tr v-for="HelpCategory in helpCategories" :key="HelpCategory.id" style="height: 3rem;">

              <td> {{ HelpCategory.id }} </td>
              <td class="w-100">
                <div class="d-flex align-center gap-x-4">
                  <VAvatar
                    v-if="hasHelpCategoryIcon(HelpCategory)"
                    size="38"
                    variant="tonal"
                    rounded
                    :image="getHelpCategoryIconUrl(HelpCategory)"
                  />
                  <VAvatar
                      v-else
                      size="38"
                      variant="tonal"
                      rounded
                      color="secondary"
                  >
                      <VIcon size="x-large" icon="custom-picture" />
                  </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="text-body-1 font-weight-medium text-high-emphasis">{{ HelpCategory.name }}</span>
                      <span class="text-body-2">{{ HelpCategory.label }}</span>
                    </div>
                </div>
              </td>
              <td class="d-flex justify-center align-center">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(HelpCategory.state_id)?.class}`"
                >
                  {{ HelpCategory.state?.name || (HelpCategory.state_id === 2 ? 'Aktiv' : 'Inaktiv') }}
                </div>
              </td>
              <!-- 👉 Actions -->
              <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'help-categories') || $can('delete', 'help-categories')">      
                <VMenu>
                  <template #activator="{ props }">
                    <VBtn v-bind="props" icon variant="text" class="btn-white">
                      <VIcon icon="custom-dots-vertical" size="22" />
                    </VBtn>
                  </template>
                  <VList>
                    <VListItem
                      v-if="$can('edit', 'help-categories') && HelpCategory.state_id === 1"
                      @click="showActivateDialog(HelpCategory)"
                    >
                      <template #prepend>
                        <VIcon icon="custom-check-mark" size="24" />
                      </template>
                      <VListItemTitle>Aktivera</VListItemTitle>
                    </VListItem>
                    <VListItem
                      v-if="$can('edit', 'help-categories') && HelpCategory.state_id === 2"
                      @click="showActivateDialog(HelpCategory)"
                    >
                      <template #prepend>
                        <VIcon icon="custom-cancel-2" size="24" />
                      </template>
                      <VListItemTitle>Inaktivera</VListItemTitle>
                    </VListItem>
                    <VListItem
                        v-if="$can('edit', 'help-categories')"
                        @click="editHelpCategory(HelpCategory)">
                      <template #prepend>
                        <VIcon icon="custom-pencil" size="24" />
                      </template>
                      <VListItemTitle>Redigera</VListItemTitle>
                    </VListItem>
                    <VListItem 
                      v-if="$can('delete','help-categories')"
                      @click="showDeleteDialog(HelpCategory)">
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
          <!-- 👉 table footer  -->

        </VTable>

        <div
          v-if="!isRequestOngoing && hasLoaded && !helpCategories.length"
          class="empty-state"
          :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
        >
          <VIcon
            :size="$vuetify.display.mdAndDown ? 80 : 120"
            icon="custom-f-car-2"
          />
          <div class="empty-state-content">
            <div class="empty-state-title">Inga kategorier har lagts till ännu</div>
            <div class="empty-state-text">
              Lägg till dina kategorier för att enkelt kunna organisera och hantera ditt fordonslager.
            </div>
          </div>
          <VBtn
            class="btn-ghost"
            v-if="$can('create', 'help-categories') && !$vuetify.display.mdAndDown"
            @click="isAddNewHelpCategoryDrawerVisible = true"
          >
            Lägg till kategori
            <VIcon icon="custom-arrow-right" size="24" />
          </VBtn>

          <VBtn
            class="btn-ghost"
            v-if="$vuetify.display.mdAndDown && $can('create', 'help-categories')"
            @click="isAddNewHelpCategoryDrawerVisible = true"
          >
            Lägg till kategori
            <VIcon icon="custom-arrow-right" size="24" />
          </VBtn>
        </div>

        <VExpansionPanels
          class="expansion-panels pb-6 px-6"
          v-if="helpCategories.length && windowWidth < 1024"
        >
          <VExpansionPanel v-for="HelpCategory in helpCategories" :key="HelpCategory.id">
            <VExpansionPanelTitle
              collapse-icon="custom-chevron-right"
              expand-icon="custom-chevron-down"
            >
              <div class="d-flex align-center w-100">
                  <VAvatar
                    v-if="hasHelpCategoryIcon(HelpCategory)"
                    size="38"
                    color="#454545"
                    variant="tonal"
                    class="me-3"
                    rounded
                    :image="getHelpCategoryIconUrl(HelpCategory)"
                  />
                  <VAvatar
                      v-else
                      size="38"
                      color="#454545"
                      variant="tonal"
                      class="me-3"
                      rounded
                  >
                      <VIcon size="x-large" icon="custom-picture" />                   
                  </VAvatar>

                  <div class="d-flex flex-column gap-1">
                      <span class="text-aqua">{{ HelpCategory.name }}</span>
                      <span class="text-neutral-3">
                        {{ HelpCategory.label }}
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
                    :class="`status-chip-${resolveStatus(HelpCategory.state_id)?.class}`"
                  >
                    {{ HelpCategory.state?.name || (HelpCategory.state_id === 2 ? 'Aktiv' : 'Inaktiv') }}
                  </div>
                </div>
              </div>

              <div class="d-flex justify-end flex-wrap gap-4">
                <VBtn 
                  v-if="$can('edit', 'help-categories')"
                  class="btn-light" 
                  icon 
                  @click="selectedHelpCategory = HelpCategory; isMobileActionDialogVisible = true"
                >
                  <VIcon icon="custom-dots-vertical" size="24" />
                </VBtn>
              </div>
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>
      
        <VCardText
          v-if="helpCategories.length"
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

    <!-- 👉 Add New HelpCategory -->
    <AddNewHelpCategoryDrawer
      ref="addNewHelpCategoryDrawerRef"
      v-model:isDrawerOpen="isAddNewHelpCategoryDrawerVisible"
      :help-category="selectedHelpCategory"
      @help-category-data="submitForm"
      @edited="onHelpCategoryFormEdited"
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
            Radera kategori?
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Du är på väg att permanent radera <strong>{{ selectedHelpCategory.name }}</strong>.
        Denna åtgärd kan inte ångras.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeHelpCategory">
              Ja, radera kategori
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm Activate / Deactivate -->
    <VDialog
      v-model="isConfirmActiveDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmActiveDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-warning-outlined" class="action-icon" />
          <div class="dialog-title">
            {{ selectedHelpCategory.state_id === 1 ? 'Aktivera kategori' : 'Inaktivera kategori' }}
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker att du vill {{ selectedHelpCategory.state_id === 1 ? 'aktivera' : 'inaktivera' }} kategori
          <strong>{{ selectedHelpCategory.name }}</strong>?
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmActiveDialogVisible = false">
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
                />
              </VListItemAction>
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
                />
              </VListItemAction>
            </template>
            <VListItemTitle>Aktiv</VListItemTitle>
          </VListItem>
        </VList>
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
        <AddNewHelpCategoryMobile
          ref="addNewHelpCategoryMobileRef"
          v-model:isDrawerOpen="isDialogOpen"
          :help-category="selectedHelpCategory"
          @help-category-data="submitForm"
          @edited="onHelpCategoryFormEdited"
        />
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
            v-if="$can('edit', 'help-categories') && selectedHelpCategory.state_id === 1"
            @click="showActivateDialog(selectedHelpCategory); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-check-mark" size="24" />
            </template>
            <VListItemTitle>Aktivera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'help-categories') && selectedHelpCategory.state_id === 2"
            @click="showActivateDialog(selectedHelpCategory); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-cancel-2" size="24" />
            </template>
            <VListItemTitle>Inaktivera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'help-categories')"
            @click="editHelpCategoryMobile(selectedHelpCategory); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete', 'help-categories')"
            @click="showDeleteDialog(selectedHelpCategory); isMobileActionDialogVisible = false;"
          >
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

<style lang="scss" scoped>

  :deep(.clients-pending-mobile-fullscreen) {
    inset: 0 !important;
    margin: 0 !important;
    width: 100vw !important;
    height: 100dvh !important;
    max-width: 100vw !important;
    max-height: 100dvh !important;
    border-radius: 0 !important;
  }

  :deep(.clients-pending-mobile-fullscreen > .v-card) {
    width: 100%;
    height: 100%;
    max-height: 100%;
    border-radius: 0 !important;
    overflow-y: auto;
  }

  .bottom-sheet-card {
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-height: 75vh;
    overflow-y: auto;
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: help-categories
</route>