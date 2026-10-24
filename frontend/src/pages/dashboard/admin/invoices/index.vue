<script setup>

import { ref, toRaw } from "vue";
import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'
import { useTypesStores } from '@/stores/useTypes'
import { useInvoicesStores } from '@/stores/useInvoices'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js';
import { excelParser } from '@/plugins/csv/excelParser'
import AddNewInvoiceDrawer from './AddNewInvoiceDrawer.vue' 
import AddNewInvoiceMobile from './AddNewInvoiceMobile.vue' 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const { width: windowWidth } = useWindowSize();

const invoicesStores = useInvoicesStores()
const typesStores = useTypesStores()
const emitter = inject("emitter")

const types = ref([])
const invoices = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalInvoices = ref(0)
const isRequestOngoing = ref(true)
const isAddNewInvoiceDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isDialogOpen = ref(false)
const isInvoiceFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | null
const hasLoaded = ref(false);
const isMobileActionDialogVisible = ref(false);
const selectedInvoice = ref({})
const addNewInvoiceDrawerRef = ref(null);
const addNewInvoiceMobileRef = ref(null);
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

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = invoices.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = invoices.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalInvoices.value} resultat`;

  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalInvoices.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewInvoiceDrawerVisible.value && !isDialogOpen.value)
        selectedInvoice.value = {}
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

  await invoicesStores.fetchInvoices(data)
  await typesStores.fetchTypes()
  types.value = typesStores.getTypes

  invoices.value = invoicesStores.getInvoices
  totalPages.value = invoicesStores.last_page
  totalInvoices.value = invoicesStores.invoicesTotalCount

  isRequestOngoing.value = false
  hasLoaded.value = true
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const editInvoice = invoiceData => {
    isAddNewInvoiceDrawerVisible.value = true
    selectedInvoice.value = { ...invoiceData }
}

const editInvoiceMobile = invoiceData => {
    isDialogOpen.value = true
    selectedInvoice.value = { ...invoiceData }
}

const showDeleteDialog = invoiceData => {
  isConfirmDeleteDialogVisible.value = true
  selectedInvoice.value = { ...invoiceData }
}

const removeInvoice = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await invoicesStores.deleteInvoice(selectedInvoice.value.id)
  selectedInvoice.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Fakturans attribut raderat!' : res.data.message,
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

const submitForm = async (invoice, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    invoice.data.append('_method', 'PUT')
    submitUpdate(invoice)
    return
  }

  submitCreate(invoice.data)
}


const submitCreate = invoiceData => {

    invoicesStores.addInvoice(invoiceData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Fakturaattribut skapat! ',
                    show: true
                }
                fetchData()
            }

            addNewInvoiceDrawerRef.value?.reallyCloseAndReset?.();
            addNewInvoiceMobileRef.value?.reallyCloseAndReset?.();

            isRequestOngoing.value = false
        })
        .catch((err) => {
            advisor.value = {
                type: 'error',
                message: err.message,
                show: true
            }

            addNewInvoiceDrawerRef.value?.reallyCloseAndReset?.();
            addNewInvoiceMobileRef.value?.reallyCloseAndReset?.();

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

const submitUpdate = invoiceData => {

    invoicesStores.updateInvoice(invoiceData)
        .then((res) => {
            if (res.data.success) {
                    advisor.value = {
                    type: 'success',
                    message: 'Fakturans attribut uppdaterat!',
                    show: true
                }

                addNewInvoiceDrawerRef.value?.reallyCloseAndReset?.();
                addNewInvoiceMobileRef.value?.reallyCloseAndReset?.();

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

            addNewInvoiceDrawerRef.value?.reallyCloseAndReset?.();
            addNewInvoiceMobileRef.value?.reallyCloseAndReset?.();

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

    await invoicesStores.fetchInvoices(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }
    
    const rows = invoicesStores.getInvoices.map(element => ({
      id: element.id,
      name: element.name,
      description: element.description,
      type: element.type.name ?? ''
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Invoices',
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
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.description)}</td>
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.type)}</td>
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
                      <td style="text-align: center; width: 50%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Beskrivning</td>
                      <td style="text-align: center; width: 40%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Typ</td>
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
        filename: 'invoices.pdf',
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

  await invoicesStores.fetchInvoices(data)

  let dataArray = [];
      
  invoicesStores.getInvoices.forEach(element => {

    let data = {
      ID: element.id,
      NAMN: element.name,
      BESKRIVNING: element.description ?? '',
      TYP: element.type.name ?? ''
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "attribut-fakturor", "csv");

  isRequestOngoing.value = false

}


const handleMobileDialogUpdate = (val) => {
  if (val === false && isInvoiceFormEdited.value) {
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
  if (isInvoiceFormEdited.value && (isAddNewInvoiceDrawerVisible.value || isDialogOpen.value)) {
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
    isAddNewInvoiceDrawerVisible.value = false;
    isDialogOpen.value = false;
    isInvoiceFormEdited.value = false;
    const go = nextRoute;
    nextRoute = null;
    leaveContext.value = null;
    go();
    return;
  }
  if (leaveContext.value === 'mobile') {
    isDialogOpen.value = false;
    isInvoiceFormEdited.value = false;
  }
  leaveContext.value = null;
};

const cancelLeave = () => {
  isConfirmLeaveVisible.value = false;
  if (leaveContext.value === 'route' && nextRoute) {
    isAddNewInvoiceDrawerVisible.value = false;
    nextRoute(false);
    nextRoute = null;
  }
  leaveContext.value = null;
};

const onInvoiceFormEdited = (val) => {
  isInvoiceFormEdited.value = !!val;
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
          <h2>Fakturor</h2>
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
            v-if="$can('create', 'invoices') && !$vuetify.display.mdAndDown"
            class="btn-gradient"
            block
            @click="isAddNewInvoiceDrawerVisible = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Lägg till fakturaattribut
          </VBtn>
          <VBtn
            v-if="$vuetify.display.mdAndDown && $can('create', 'invoices')"
            class="btn-gradient"
            block
            @click="isDialogOpen = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Lägg till fakturaattribut
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
        v-show="invoices.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col"> #ID </th>
            <th scope="col"> Namn </th>
            <th scope="col"> Beskrivning </th>
            <th scope="col"> Typ </th>
            <th scope="col" v-if="$can('edit', 'invoices') || $can('delete', 'invoices')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="invoices.length">
          <tr 
            v-for="invoice in invoices"
            :key="invoice.id"
            style="height: 3rem;"
          >

            <td> {{ invoice.id }} </td>
            <td class="text-wrap"> {{ invoice.name }} </td>
            <td class="text-wrap"> {{ invoice.description }}</td>
            <td class="text-wrap"> {{ invoice.type.name }}</td>
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'invoices') || $can('delete', 'invoices')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                      v-if="$can('edit', 'invoices')"
                      @click="editInvoice(invoice)">
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','invoices')"
                    @click="showDeleteDialog(invoice)">
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
        v-if="!isRequestOngoing && hasLoaded && !invoices.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-create-billing"
        />
        <div class="empty-state-content">
          <div class="empty-state-title"> Inga fakturor skapade än</div>
          <div class="empty-state-text">
            Här kommer alla dina skapade fakturor att listas.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'invoices') && !$vuetify.display.mdAndDown"
          @click="isAddNewInvoiceDrawerVisible = true"
        >
          Skapa ny faktura
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'invoices')"
          @click="isAddNewInvoiceDrawerVisible = true"
        >
          Skapa ny faktura
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
          class="expansion-panels pb-6 px-6"
          v-if="invoices.length && windowWidth < 1024"
        >
          <VExpansionPanel v-for="invoice in invoices" :key="invoice.id">
            <VExpansionPanelTitle
              collapse-icon="custom-chevron-right"
              expand-icon="custom-chevron-down"
            >
              <span class="order-id">{{ invoice.id }}</span>
              <div class="order-title-box">
                <span class="title-panel">{{ invoice.name }}</span>
              </div>
            </VExpansionPanelTitle>
            
            <VExpansionPanelText>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Beskrivning</div>
                <div class="expansion-panel-item-value">
                    {{ invoice.description }}
                </div>
              </div>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Typ</div>
                <div class="expansion-panel-item-value">
                    {{ invoice.type.name }}
                </div>
              </div>
              

              <div class="d-flex justify-end flex-wrap gap-4">
                <VBtn 
                  v-if="$can('edit', 'invoices')"
                  class="btn-light" 
                  icon 
                  @click="selectedInvoice = invoice; isMobileActionDialogVisible = true"
                >
                  <VIcon icon="custom-dots-vertical" size="24" />
                </VBtn>
              </div>
            </VExpansionPanelText>
          </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="invoices.length"
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

    <!-- 👉 Add New Invoice -->
    <AddNewInvoiceDrawer
      ref="addNewInvoiceDrawerRef"
      v-model:isDrawerOpen="isAddNewInvoiceDrawerVisible"
      :invoice="selectedInvoice"
      :types="types"
      @invoice-data="submitForm"
      @edited="onInvoiceFormEdited"
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
              Ta bort faktura
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Är du säker att du vill ta bort fakturan <strong>{{ selectedInvoice.name }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeInvoice">
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
        <AddNewInvoiceMobile
          ref="addNewInvoiceMobileRef"
          v-model:isDrawerOpen="isDialogOpen"
          :invoice="selectedInvoice"
          :types="types"
          @invoice-data="submitForm"
          @edited="onInvoiceFormEdited"
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
              v-if="$can('edit', 'invoices')"
              @click="editInvoiceMobile(selectedInvoice); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem 
            v-if="$can('delete','invoices')"
            @click="showDeleteDialog(selectedInvoice); isMobileActionDialogVisible = false;">
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
    subject: invoices
</route>