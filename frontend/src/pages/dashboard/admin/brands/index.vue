<script setup>

import { ref, toRaw } from "vue";
import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'
import { useBrandsStores } from '@/stores/useBrands'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js';
import { excelParser } from '@/plugins/csv/excelParser'
import { openExternalLink } from '@/composables/useExternalLink'
import AddNewBrandDrawer from './AddNewBrandDrawer.vue' 
import AddNewBrandMobile from './AddNewBrandMobile.vue' 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const { width: windowWidth } = useWindowSize();

const brandsStores = useBrandsStores()
const emitter = inject("emitter")

const brands = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalBrands = ref(0)
const isRequestOngoing = ref(true)
const isAddNewBrandDrawerVisible = ref(false)
const isDialogOpen = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isBrandFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | null
const selectedBrand = ref({})
const hasLoaded = ref(false);
const isMobileActionDialogVisible = ref(false);
const addNewBrandDrawerRef = ref(null);
const addNewBrandMobileRef = ref(null);
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
  const firstIndex = brands.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    brands.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalBrands.value} resultat`;
});

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewBrandDrawerVisible.value && !isDialogOpen.value)
        selectedBrand.value = {}
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
    page: currentPage.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await brandsStores.fetchBrands(data)

  brands.value = brandsStores.getBrands
  totalPages.value = brandsStores.last_page
  totalBrands.value = brandsStores.brandsTotalCount

  isRequestOngoing.value = false
  hasLoaded.value = true
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const editBrand = brandData => {
    isAddNewBrandDrawerVisible.value = true
    selectedBrand.value = { ...brandData }
}

const editBrandMobile = (brandData) => {
  isDialogOpen.value = true;
  selectedBrand.value = { ...brandData };
};

const showDeleteDialog = brandData => {
  isConfirmDeleteDialogVisible.value = true
  selectedBrand.value = { ...brandData }
}

const showDeleteBrandDialog = brandData => {
  isConfirmDeleteDialogVisible.value = true
  selectedBrand.value = { ...brandData }
}

const removeBrand = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await brandsStores.deleteBrand(selectedBrand.value.id)
  selectedBrand.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Märke raderat!' : res.data.message,
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

const submitForm = async (brand, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    brand.data.append('_method', 'PUT')
    submitUpdate(brand)
    return
  }

  submitCreate(brand.data)
}

const submitCreate = brandData => {

    brandsStores.addBrand(brandData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Märke skapat!',
                    show: true
                }

                addNewBrandDrawerRef.value?.reallyCloseAndReset?.();
                addNewBrandMobileRef.value?.reallyCloseAndReset?.();
        
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

            addNewBrandDrawerRef.value?.reallyCloseAndReset?.();
            addNewBrandMobileRef.value?.reallyCloseAndReset?.();

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

const submitUpdate = brandData => {

    brandsStores.updateBrand(brandData)
        .then((res) => {
            if (res.data.success) {
                    advisor.value = {
                    type: 'success',
                    message: 'Märke uppdaterat!',
                    show: true
                }

                addNewBrandDrawerRef.value?.reallyCloseAndReset?.();
                addNewBrandMobileRef.value?.reallyCloseAndReset?.();

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

            addNewBrandDrawerRef.value?.reallyCloseAndReset?.();
            addNewBrandMobileRef.value?.reallyCloseAndReset?.();

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

const seeUrl = async (brand) => {
  await openExternalLink(brand.url);
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

    await brandsStores.fetchBrands(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }

    const rows = brandsStores.getBrands.map(element => ({
      id: element.id,
      name: element.name,
      url: element.url
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Märke',
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
        filename: 'märke.pdf',
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

  await brandsStores.fetchBrands(data)

  let dataArray = [];
      
  brandsStores.getBrands.forEach(element => {

    let data = {
      ID: element.id,
      NAMN: element.name,
      HEMSIDA: element.url
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "märke", "csv");

  isRequestOngoing.value = false

}

// Intercept mobile dialog outside-click close
const handleMobileDialogUpdate = (val) => {
  if (val === false && isBrandFormEdited.value) {
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
  if (isBrandFormEdited.value && (isAddNewBrandDrawerVisible.value || isDialogOpen.value)) {
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
    isAddNewBrandDrawerVisible.value = false;
    isDialogOpen.value = false;
    isBrandFormEdited.value = false;
    const go = nextRoute;
    nextRoute = null;
    leaveContext.value = null;
    go();
    return;
  }
  if (leaveContext.value === 'mobile') {
    isDialogOpen.value = false;
    isBrandFormEdited.value = false;
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

const onBrandFormEdited = (val) => {
  isBrandFormEdited.value = !!val;
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
            <h2>Märke</h2>
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
              v-if="$can('create', 'brands') && !$vuetify.display.mdAndDown"
              class="btn-gradient"
              block
              @click="isAddNewBrandDrawerVisible = true"
            >
              <VIcon icon="custom-plus" size="24" />
              Lägg till märke
            </VBtn>
            <VBtn
              v-if="$vuetify.display.mdAndDown && $can('create', 'brands')"
              class="btn-gradient"
              block
              @click="isDialogOpen = true"
            >
              <VIcon icon="custom-plus" size="24" />
              Lägg till märke
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
          v-show="brands.length"
          class="px-4 pb-6 text-no-wrap"
        >
          <!-- 👉 table head -->
          <thead>
            <tr>
              <th scope="col"> #ID </th>
              <th scope="col"> Namn </th>
              <th scope="col" v-if="$can('edit', 'brands') || $can('delete', 'brands')"></th>
            </tr>
          </thead>
          <!-- 👉 table body -->
          <tbody v-show="brands.length">
            <tr v-for="brand in brands" :key="brand.id" style="height: 3rem;">

              <td> {{ brand.id }} </td>
              <td class="w-100">
                <div class="d-flex align-center gap-x-4">
                  <VAvatar
                    v-if="brand.logo"
                    size="38"
                    variant="tonal"
                    rounded
                    :image="themeConfig.settings.urlStorage + brand.logo"
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
                    <span class="text-body-1 font-weight-medium text-high-emphasis">{{ brand.name }}</span>
                    <span class="text-body-2 cursor-pointer" @click="seeUrl(brand)">{{ brand.url }}</span>
                  </div>
                </div>
              </td>
              <!-- 👉 Actions -->
              <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'brands') || $can('delete', 'brands')">      
                <VMenu>
                  <template #activator="{ props }">
                    <VBtn v-bind="props" icon variant="text" class="btn-white">
                      <VIcon icon="custom-dots-vertical" size="22" />
                    </VBtn>
                  </template>
                  <VList>
                    <VListItem
                        v-if="$can('edit', 'brands')"
                        @click="editBrand(brand)">
                      <template #prepend>
                        <VIcon icon="custom-pencil" size="24" />
                      </template>
                      <VListItemTitle>Redigera</VListItemTitle>
                    </VListItem>
                    <VListItem 
                      v-if="$can('delete','brands')"
                      @click="showDeleteDialog(brand)">
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
          v-if="!isRequestOngoing && hasLoaded && !brands.length"
          class="empty-state"
          :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
        >
          <VIcon
            :size="$vuetify.display.mdAndDown ? 80 : 120"
            icon="custom-f-user"
          />
          <div class="empty-state-content">
            <div class="empty-state-title">Du har inga varumärken än</div>
            <div class="empty-state-text">
              Lägg till dina varumärken här.
            </div>
          </div>
          <VBtn
            class="btn-ghost"
            v-if="$can('create', 'brands') && !$vuetify.display.mdAndDown"
            @click="isAddNewBrandDrawerVisible = true"
          >
            Lägg till märke
            <VIcon icon="custom-arrow-right" size="24" />
          </VBtn>

          <VBtn
            class="btn-ghost"
            v-if="$vuetify.display.mdAndDown && $can('create', 'brands')"
            @click="isAddNewBrandDrawerVisible = true"
          >
            Lägg till märke
            <VIcon icon="custom-arrow-right" size="24" />
          </VBtn>
        </div>

        <VExpansionPanels
          class="expansion-panels pb-6 px-6"
          v-if="brands.length && windowWidth < 1024"
        >
          <VExpansionPanel v-for="brand in brands" :key="brand.id">
            <VExpansionPanelTitle
              collapse-icon="custom-chevron-right"
              expand-icon="custom-chevron-down"
            >
              <div class="d-flex align-center w-100">
                  <VAvatar
                    v-if="brand.logo"
                    size="38"
                    variant="tonal"
                    class="me-3"
                    rounded
                    :image="themeConfig.settings.urlStorage + brand.logo"
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
                      <span class="text-aqua">{{ brand.name }}</span>
                      <span class="text-neutral-3" @click="seeUrl(brand)">
                        {{ brand.url }}
                      </span>
                  </div>
              </div>
            </VExpansionPanelTitle>
            
            <VExpansionPanelText>
              <div class="mb-6">
                <div class="expansion-panel-item-label">#ID</div>
                <div class="expansion-panel-item-value">{{ brand.id }}</div>
              </div>

              <div class="d-flex justify-end flex-wrap gap-4">
                <VBtn 
                  v-if="$can('edit', 'brands')"
                  class="btn-light" 
                  icon 
                  @click="selectedBrand = brand; isMobileActionDialogVisible = true"
                >
                  <VIcon icon="custom-dots-vertical" size="24" />
                </VBtn>
              </div>
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>
      
        <VCardText
          v-if="brands.length"
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

    <!-- 👉 Add New Brand -->
    <AddNewBrandDrawer
      ref="addNewBrandDrawerRef"
      v-model:isDrawerOpen="isAddNewBrandDrawerVisible"
      :brand="selectedBrand"
      @brand-data="submitForm"
      @edited="onBrandFormEdited"
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
            Radera märke?
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Du är på väg att permanent radera <strong>{{ selectedBrand.name }}</strong>.
        Denna åtgärd kan inte ångras.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeBrand">
              Ja, radera märke
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
        <AddNewBrandMobile
          ref="addNewBrandMobileRef"
          v-model:isDrawerOpen="isDialogOpen"
          :brand="selectedBrand"
          @brand-data="submitForm"
          @edited="onBrandFormEdited"
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
            v-if="$can('edit', 'brands')"
            @click="editBrandMobile(selectedBrand); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete', 'brands')"
            @click="showDeleteDialog(selectedBrand); isMobileActionDialogVisible = false;"
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
    subject: brands
</route>