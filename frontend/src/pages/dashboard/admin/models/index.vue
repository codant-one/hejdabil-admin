<script setup>

import { ref, toRaw } from "vue";
import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'
import { useBrandsStores } from '@/stores/useBrands'
import { useModelsStores } from '@/stores/useModels'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js';
import { excelParser } from '@/plugins/csv/excelParser'
import AddNewModelDrawer from './AddNewModelDrawer.vue' 
import AddNewModelMobile from './AddNewModelMobile.vue' 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const { width: windowWidth } = useWindowSize();

const modelsStores = useModelsStores()
const brandsStores = useBrandsStores()
const emitter = inject("emitter")

const brands = ref([])
const models = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalModels = ref(0)
const isRequestOngoing = ref(true)
const isAddNewModelDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isDialogOpen = ref(false)
const isModelFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | null
const selectedModel = ref({})
const hasLoaded = ref(false);
const isMobileActionDialogVisible = ref(false);
const brand_id = ref(null)
const addNewModelDrawerRef = ref(null);
const addNewModelMobileRef = ref(null);
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

const setCompany = (value) => {
  const normalized = value && typeof value === 'object' ? { ...value } : {};
  company.value = normalized;
  localStorage.setItem(COMPANY_STORAGE_KEY, JSON.stringify(normalized));
};

const sectionEl = ref(null);

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = models.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = models.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalModels.value } modell`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewModelDrawerVisible.value && !isDialogOpen.value)
        selectedModel.value = {}
})

onMounted(async () => {

  await brandsStores.fetchBrands({limit: -1})
  brands.value = brandsStores.getBrands
  
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
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    brand_id: brand_id.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await modelsStores.fetchModels(data)

  models.value = modelsStores.getModels
  totalPages.value = modelsStores.last_page
  totalModels.value = modelsStores.modelsTotalCount

  isRequestOngoing.value = false
  hasLoaded.value = true
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const editModel = modelData => {
    isAddNewModelDrawerVisible.value = true
    selectedModel.value = { ...modelData }
}

const editModelMobile = modelData => {
    isDialogOpen.value = true
    selectedModel.value = { ...modelData }
}

const showDeleteDialog = modelData => {
  isConfirmDeleteDialogVisible.value = true
  selectedModel.value = { ...modelData }
}

const removeModel = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await modelsStores.deleteModel(selectedModel.value.id)
  selectedModel.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Modell raderat!' : res.data.message,
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

const submitForm = async (model, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    model.data.append('_method', 'PUT')
    submitUpdate(model)
    return
  }

  submitCreate(model.data)
}


const submitCreate = modelData => {

    modelsStores.addModel(modelData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Modell skapat! ',
                    show: true
                }

                addNewModelDrawerRef.value?.reallyCloseAndReset?.();
                addNewModelMobileRef.value?.reallyCloseAndReset?.();

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

            addNewModelDrawerRef.value?.reallyCloseAndReset?.();
            addNewModelMobileRef.value?.reallyCloseAndReset?.();

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

const submitUpdate = modelData => {

    modelsStores.updateModel(modelData)
        .then((res) => {
            if (res.data.success) {
                    advisor.value = {
                    type: 'success',
                    message: 'Modell uppdaterat!',
                    show: true
                }

                addNewModelDrawerRef.value?.reallyCloseAndReset?.();
                addNewModelMobileRef.value?.reallyCloseAndReset?.();

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

            addNewModelDrawerRef.value?.reallyCloseAndReset?.();
            addNewModelMobileRef.value?.reallyCloseAndReset?.();

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

const seeUrl = (brand) => {
  window.open(brand.url, '_blank');
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

    await modelsStores.fetchModels(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }
    
    const rows = modelsStores.getModels.map(element => ({
      id: element.id,
      name: element.name,
      brand: element?.brand?.name,
      url: element?.brand?.url
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Modell',
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
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.brand)}</td>
        <td style="width: 40%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.url)}</td>
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
                      <td style="text-align: center; width: 50%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Märke</td>
                      <td style="text-align: center; width: 40%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Hemsida</td>
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
        filename: 'modell.pdf',
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

  await modelsStores.fetchModels(data)

  let dataArray = [];
      
  modelsStores.getModels.forEach(element => {

    let data = {
      ID: element.id,
      NAMN: element.name,
      MÄRKE: element.brand.name ?? ''
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "modell", "csv");

  isRequestOngoing.value = false

}

// Intercept mobile dialog outside-click close
const handleMobileDialogUpdate = (val) => {
  if (val === false && isModelFormEdited.value) {
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
  if (isModelFormEdited.value && (isAddNewModelDrawerVisible.value || isDialogOpen.value)) {
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
    isAddNewModelDrawerVisible.value = false;
    isDialogOpen.value = false;
    isModelFormEdited.value = false;
    const go = nextRoute;
    nextRoute = null;
    leaveContext.value = null;
    go();
    return;
  }
  if (leaveContext.value === 'mobile') {
    isDialogOpen.value = false;
    isModelFormEdited.value = false;
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

const onModelFormEdited = (val) => {
  isModelFormEdited.value = !!val;
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
          <h2>Modell</h2>
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
            v-if="$can('create', 'models') && !$vuetify.display.mdAndDown"
            class="btn-gradient"
            block
            @click="isAddNewModelDrawerVisible = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Lägg till modell
          </VBtn>
          <VBtn
            v-if="$vuetify.display.mdAndDown && $can('create', 'models')"
            class="btn-gradient"
            block
            @click="isDialogOpen = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Lägg till modell
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

        <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-2'">
          <AppAutocomplete
            prepend-icon="custom-filter"
            v-model="brand_id"
            placeholder="Märke"
            :items="brands"
            :item-title="item => item.name"
            :item-value="item => item.id"
            autocomplete="off"
              clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate"
          />
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
        v-show="models.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col"> #ID </th>
            <th scope="col"> Namn </th>
            <th scope="col"> Märke </th>
            <th scope="col" v-if="$can('edit', 'models') || $can('delete', 'models')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="models.length">
          <tr  v-for="model in models" :key="model.id" style="height: 3rem;">
            <td> {{ model.id }} </td>
            <td class="text-wrap"> {{ model.name }} </td>
            <td>
              <div class="d-flex align-center gap-x-4">
                <VAvatar
                  v-if="model.brand.logo"
                  size="38"
                  variant="tonal"
                  rounded
                  :image="themeConfig.settings.urlStorage + model.brand.logo"
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
                  <span class="text-body-1 font-weight-medium text-high-emphasis">{{ model.brand.name }}</span>
                  <span class="text-body-2 cursor-pointer" @click="seeUrl(model.brand)">{{ model.brand.url }}</span>
                </div>
              </div>
            </td>
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'models') || $can('delete', 'models')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                      v-if="$can('edit', 'models')"
                      @click="editModel(model)">
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','models')"
                    @click="showDeleteDialog(model)">
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
        v-if="!isRequestOngoing && hasLoaded && !models.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-user"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Du har inga modell än</div>
          <div class="empty-state-text">
            Lägg till dina modell här.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'models') && !$vuetify.display.mdAndDown"
          @click="isAddNewModelDrawerVisible = true"
        >
          Lägg till modell
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'models')"
          @click="isAddNewModelDrawerVisible = true"
        >
          Lägg till modell
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
          class="expansion-panels pb-6 px-6"
          v-if="models.length && windowWidth < 1024"
        >
          <VExpansionPanel v-for="model in models" :key="model.id">
            <VExpansionPanelTitle
              collapse-icon="custom-chevron-right"
              expand-icon="custom-chevron-down"
            >
              <span class="order-id">{{ model.id }}</span>
              <div class="order-title-box">
                <span class="title-panel">{{ model.name }}</span>
              </div>
            </VExpansionPanelTitle>
            
            <VExpansionPanelText>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Märke</div>
                <div class="expansion-panel-item-value">
                  <div class="d-flex align-center w-100">
                    <VAvatar
                      v-if="model.brand.logo"
                      size="38"
                      variant="tonal"
                      class="me-3"
                      rounded
                      :image="themeConfig.settings.urlStorage + model.brand.logo"
                    />
                    <VAvatar
                        v-else
                        size="38"
                        variant="tonal"
                        class="me-3"
                        rounded
                        color="secondary"
                    >
                      <VIcon size="x-large" icon="custom-picture" />                   
                    </VAvatar>

                    <div class="d-flex flex-column gap-1">
                      <span class="text-aqua">{{ model.brand.name }}</span>
                      <span class="text-neutral-3" @click="seeUrl(model.brand)">
                        {{ model.brand.url }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              

              <div class="d-flex justify-end flex-wrap gap-4">
                <VBtn 
                  v-if="$can('edit', 'model')"
                  class="btn-light" 
                  icon 
                  @click="selectedModel = model; isMobileActionDialogVisible = true"
                >
                  <VIcon icon="custom-dots-vertical" size="24" />
                </VBtn>
              </div>
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>

      <VCardText
        v-if="models.length"
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
    
    <!-- 👉 Add New Model -->
    <AddNewModelDrawer
      ref="addNewModelDrawerRef"
      v-model:isDrawerOpen="isAddNewModelDrawerVisible"
      :model="selectedModel"
      :brands="brands"
      @model-data="submitForm"
      @edited="onModelFormEdited"
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
            Radera modell?
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Du är på väg att permanent radera <strong>{{ selectedModel.name }}</strong>.
        Denna åtgärd kan inte ångras.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeModel">
              Ja, radera modell
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
        <AddNewModelMobile
          ref="addNewModelMobileRef"
          v-model:isDrawerOpen="isDialogOpen"
          :model="selectedModel"
          :brands="brands"
          @model-data="submitForm"
          @edited="onModelFormEdited"
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
            v-if="$can('edit', 'models')"
            @click="editModelMobile(selectedModel); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete', 'models')"
            @click="showDeleteDialog(selectedModel); isMobileActionDialogVisible = false;"
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
    subject: models
</route>