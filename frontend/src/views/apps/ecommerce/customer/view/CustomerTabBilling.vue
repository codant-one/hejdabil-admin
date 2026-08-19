<script setup>

import { formatNumber } from '@/@core/utils/formatters'
import { useSupplierInvoicesStores } from '@/stores/useSupplierInvoices'
import { themeConfig } from '@themeConfig'
import { excelParser } from '@/plugins/csv/excelParser'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";
import refreshAvatar from "@/assets/images/avatars/refresh-2.svg";
import ExportDateMenu from '@/components/common/ExportDateMenu.vue'
import html2pdf from 'html2pdf.js'
import router from "@/router";

const { width: windowWidth } = useWindowSize();

const route = useRoute()
const supplierInvoices = useSupplierInvoicesStores()
const exporteraMobile = ref(false);
const date = ref(null)
const selectedExportType = ref(null)
const isExportTypeMenuVisible = ref(false)
const isExportMenuVisible = ref(false)
const isExportingFile = ref(false)
const lastExportSelectionKey = ref(null)
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalBillings = ref(0)
const state_id = ref(null)
const COMPANY_STORAGE_KEY = 'clients_company_snapshot'

const props = defineProps({
  customerData: {
    type: Object,
    required: false,
  },
  isSupplier: {
    type: Boolean,
    required: true,
  }
})

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const readCachedCompany = () => {
  try {
    const cached = localStorage.getItem(COMPANY_STORAGE_KEY)
    if (!cached) return {}

    const parsed = JSON.parse(cached)
    return parsed && typeof parsed === 'object' ? parsed : {}
  } catch {
    return {}
  }
}

const company = ref(readCachedCompany())

const emit = defineEmits([
  'submit',
  'delete',
  'alert',
  'loading'
])

const isEditAddressDialogVisible = ref(false)
const selectedAddress = ref({})
const billings = ref([])
const selectedBilling = ref({})
const isConfirmKreditera = ref(false)

const selectedBillingForAction = ref({});
const isMobileActionDialogVisible = ref(false);
const isConfirmStateDialogVisible = ref(false);
const replaceFileInput = ref(null)
const billingToReplaceFile = ref(null)

// 👉 Computing pagination data
const paginationData = computed(() => {
  // const firstIndex = billings.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  // const lastIndex = billings.value.length + (currentPage.value - 1) * rowPerPage.value
  
  return `${totalBillings.value} resultat`;

  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalSuppliers.value } register`
})

watchEffect(() => {
  if (!isEditAddressDialogVisible.value)
    selectedAddress.value = {}
})

watch(isExportMenuVisible, isVisible => {
  if (isVisible)
    lastExportSelectionKey.value = null
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

  if(Number(route.params.id)) {
    if(props.isSupplier && props.customerData.id !== null) {
      data.supplier_id = props.customerData.id

      try {

        emit("loading", true);
        
        await supplierInvoices.fetchSupplierInvoices(data)

        emit("loading", false);

        const invoices = Array.isArray(supplierInvoices.getSupplierInvoices)
          ? supplierInvoices.getSupplierInvoices
          : []

        billings.value = invoices.map(invoice => ({
          id: invoice.id,
          user_id: invoice.user_id,
          invoice_id: invoice.invoice_id,
          period: invoice.billing_period,
          name: [invoice.supplier?.user?.name, invoice.supplier?.user?.last_name].filter(Boolean).join(' ') || '-',
          start_date: invoice.invoice_date,
          end_date: invoice.due_date,
          amount: Number(invoice.total ?? 0) + Number(invoice.amount_discount ?? 0),
          state_id: invoice.state_id,
          state_name: invoice.state?.name ?? '-',
          file: invoice.file,
          is_credit: invoice.is_credit,
          user: {
            name: invoice.user?.name ?? '-',
            last_name: invoice.user?.last_name ?? '',
            email: invoice.user?.email ?? '',
            avatar: invoice.user?.avatar ?? null,
            user_detail: {
              avatar_id: invoice.user?.user_detail?.avatar_id ?? null,
            },
          },
        }))

        totalPages.value = supplierInvoices.last_page
        totalBillings.value = supplierInvoices.supplierInvoicesTotalCount
      } finally {
        emit("loading", false);
      }
    }
  }
}

const addInvoice = () => {
  router.push({ 
    name: "dashboard-admin-suppliers-billings-add", 
    query: { supplier_id: Number(route.params.id) } 
  });
};

const editBilling = (billingData) => {
  router.push({
    name: "dashboard-admin-suppliers-billings-edit-id",
    params: { id: billingData.invoice_id },
  });
};

const updateBilling = (billingData) => {
  isConfirmStateDialogVisible.value = true;
  selectedBilling.value = { ...billingData };
};

const updateState = async () => {
  isConfirmStateDialogVisible.value = false;

  try {
    emit("loading", true);
    
    let res = await supplierInvoices.updateState(selectedBilling.value.id);
    
    advisor.value = {
      type: res.data.success ? "success" : "error",
      message: res.data.success ? "Fakturan uppdaterad!" : res.data.message,
      show: true,
    };

    emit('alert', advisor)

    await fetchData()
  } finally {
    emit("loading", false);
  }
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
};

const openBillingPdf = billing => {
  if (!billing?.file)
    return

  window.open(themeConfig.settings.urlStorage + billing.file)
}

const showBilling = (billingData) => {
  router.push({
    name: "dashboard-admin-suppliers-billings-id",
    params: { id: billingData.id },
  });
};

const printBilling = async billing => {
  if (!billing?.file)
    return

  try {
    const response = await fetch(
      themeConfig.settings.urlbase + "proxy-image?url=" + themeConfig.settings.urlStorage + billing.file
    )
    const blob = await response.blob()
    const blobUrl = URL.createObjectURL(blob)

    const iframe = document.createElement('iframe')
    iframe.style.display = 'none'
    iframe.src = blobUrl

    iframe.onload = () => {
      iframe.contentWindow.print()
    }

    document.body.appendChild(iframe)
  } catch (error) {
    console.error('Error:', error)
  }
}

const downloadBillingPdf = async (billing) => {
  if (!billing?.file)
    return


  try {
    const response = await fetch(
      themeConfig.settings.urlbase + "proxy-image?url=" + themeConfig.settings.urlStorage + billing.file
    );
    const blob = await response.blob();

    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = blobUrl;
    a.download = billing.file.split("/").pop();
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  } catch (error) {
    console.error("Error:", error);
  }
}

const replaceFile = billing => {
  if (!billing?.id)
    return

  billingToReplaceFile.value = billing
  replaceFileInput.value?.click()
}

const onReplaceFileSelected = async event => {
  const target = event?.target
  const file = target?.files?.[0]

  if (!file || !billingToReplaceFile.value?.id) {
    if (target)
      target.value = ''

    return
  }

  const isPdfFile = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')

  if (!isPdfFile) {
    advisor.value = {
      type: 'error',
      message: 'Endast PDF-filer är tillåtna',
      show: true,
    }

    emit('alert', advisor)

    target.value = ''
    return
  }

  const formData = new FormData()
  formData.append('file', file)

  try {
    emit('loading', true)

    const response = await supplierInvoices.replaceFile({
      id: billingToReplaceFile.value.id,
      data: formData,
    })

    advisor.value = {
      type: response?.data?.success ? 'success' : 'error',
      message: response?.data?.success ? 'Fakturan har ersatts' : (response?.data?.message || 'Något gick fel'),
      show: true,
    }

    emit('alert', advisor)

    if (response?.data?.success)
      await fetchData()
  } finally {
    emit('loading', false)
    billingToReplaceFile.value = null
    target.value = ''
  }
}

const credit = billing => {
  if (!billing?.id)
    return

  selectedBilling.value = { ...billing }
  isConfirmKreditera.value = true
}

const kreditera = async () => {
  if (!selectedBilling.value?.id)
    return

  emit("loading", true);
  isConfirmKreditera.value = false

  try {
    await supplierInvoices.credit(Number(selectedBilling.value.id))
    selectedBilling.value = {}

    advisor.value.show = true
    advisor.value.type = 'success'
    advisor.value.message = 'Framgångsrik kredit'

    emit('alert', advisor)

    setTimeout(() => {
      advisor.value.show = false
      advisor.value.type = ''
      advisor.value.message = ''
      emit('alert', advisor)
    }, 5000)

    await fetchData()
  } finally {
    emit("loading", false);
  }
}

const resolveStatus = state_id => {
  if (state_id === 4)
    return { class: 'pending' }
  if (state_id === 7)
    return { class: 'success' }
  if (state_id === 8)
    return { class: 'error' }
  if (state_id === 9)
    return { class: 'error' }
}

const toYmd = value => {
  if (!value)
    return null

  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    const year = value.getFullYear()
    const month = `${value.getMonth() + 1}`.padStart(2, '0')
    const day = `${value.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  if (typeof value === 'string') {
    const normalized = value.trim()
    const ymdMatch = normalized.match(/^\d{4}-\d{2}-\d{2}/)
    if (ymdMatch)
      return ymdMatch[0]

    const parsed = new Date(normalized)
    if (!Number.isNaN(parsed.getTime())) {
      const year = parsed.getFullYear()
      const month = `${parsed.getMonth() + 1}`.padStart(2, '0')
      const day = `${parsed.getDate()}`.padStart(2, '0')
      return `${year}-${month}-${day}`
    }
  }

  return null
}

const resolveDateRange = value => {
  if (!value)
    return null

  if (Array.isArray(value)) {
    const from = toYmd(value[0])
    const to = toYmd(value[1] ?? value[0])

    return from && to ? [from, to] : null
  }

  if (typeof value === 'string') {
    const splitByRange = value.split(/\s+-\s+|\s+to\s+|\s+till\s+|\s+a\s+/i)

    if (splitByRange.length >= 2) {
      const from = toYmd(splitByRange[0])
      const to = toYmd(splitByRange[1])

      return from && to ? [from, to] : null
    }

    const single = toYmd(value)

    return single ? [single, single] : null
  }

  if (value instanceof Date) {
    const single = toYmd(value)

    return single ? [single, single] : null
  }

  return null
}

const getDateRangePayload = () => {
  const dateRange = resolveDateRange(date.value)

  if (!dateRange)
    return {}

  return {
    date_from: dateRange[0],
    date_to: dateRange[1],
  }
}

const buildRangeSelectionKey = value => {
  if (!value)
    return null

  const normalize = item => {
    if (!item)
      return ''

    if (item instanceof Date && !Number.isNaN(item.getTime())) {
      const year = item.getFullYear()
      const month = `${item.getMonth() + 1}`.padStart(2, '0')
      const day = `${item.getDate()}`.padStart(2, '0')

      return `${year}-${month}-${day}`
    }

    if (typeof item === 'string')
      return item.trim()

    return String(item)
  }

  if (Array.isArray(value)) {
    const first = normalize(value[0])
    const second = normalize(value[1] ?? value[0])

    return `${first}__${second}`
  }

  if (typeof value === 'string') {
    const chunks = value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i).map(item => item.trim()).filter(Boolean)
    if (chunks.length >= 2)
      return `${chunks[0]}__${chunks[1]}`

    const single = normalize(value)
    return `${single}__${single}`
  }

  const single = normalize(value)
  return `${single}__${single}`
}

const isCompleteRangeSelection = value => {
  if (!value)
    return false

  if (Array.isArray(value))
    return value.length >= 2 && !!value[0] && !!value[1]

  if (typeof value === 'string') {
    const chunks = value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i)
    return chunks.length >= 2 && !!chunks[0]?.trim() && !!chunks[1]?.trim()
  }

  return false
}

const buildExportParams = () => {
  const data = {
    limit: -1,
    ...getDateRangePayload(),
    orderByField: 'id',
    orderBy: 'desc',
    state_id: state_id.value,
  }

  if (Number(route.params.id) && props.isSupplier && props.customerData?.id !== null)
    data.supplier_id = props.customerData.id

  return data
}

const exportPDFAndCloseMenu = async () => {
  if (isExportingFile.value)
    return

  if (!selectedExportType.value)
    return

  isExportingFile.value = true

  try {
    if (selectedExportType.value === 'excel') {
      await downloadCSV()
    } else {
      await downloadPDF()
    }

    isExportMenuVisible.value = false
  } finally {
    selectedExportType.value = null
    isExportingFile.value = false
  }
}

const openExportDateMenu = type => {
  exporteraMobile.value = false
  selectedExportType.value = type
  isExportTypeMenuVisible.value = false

  nextTick(() => {
    isExportMenuVisible.value = true
  })
}

const onDatePickerUpdate = value => {
  if (!selectedExportType.value)
    return

  if (!isCompleteRangeSelection(value))
    return

  const selectionKey = buildRangeSelectionKey(value)
  if (!selectionKey || selectionKey === lastExportSelectionKey.value)
    return

  lastExportSelectionKey.value = selectionKey

  if (!isExportingFile.value)
    exportPDFAndCloseMenu()
}

const downloadPDF = async () => {
  exporteraMobile.value = false
  emit("loading", true);
  const pdfFontFamily = "'Gelion Regular', 'DM Sans', sans-serif"

  const escapeHtml = value => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')

  let pdfContainer = null

  try {
    const data = buildExportParams()

    await supplierInvoices.fetchSupplierInvoices(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }

    const supplierName = supplierInvoices.getSupplierInfo?.supplier_name ?? null

    company.value.company = supplierInvoices.getSupplierInfo?.user.user_detail.company ?? null
    company.value.name = supplierInvoices.getSupplierInfo?.user?.name ?? null
    company.value.last_name = supplierInvoices.getSupplierInfo?.user?.last_name ?? null
    company.value.email = supplierInvoices.getSupplierInfo?.user?.email ?? null

    const supplier = `${supplierName}`
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9-]/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '') || 'supplier-billings'

    const rows = supplierInvoices.getSupplierInvoices.map(element => ({
      invoiceId: element.invoice_id ?? '-',
      period: element.billing_period ?? '-',
      invoiceDate: element.invoice_date ?? '-',
      dueDate: element.due_date ?? '-',
      total: `${formatNumber((Number(element.total ?? 0) + Number(element.amount_discount ?? 0))) ?? '0,00'} kr`,
      state: element.state?.name ?? '-',
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Fakturor',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      <tr style="height: 48px;">
        <td style="width: 16%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.invoiceId)}</td>
        <td style="width: 18%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.period)}</td>
        <td style="width: 16%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.invoiceDate)}</td>
        <td style="width: 16%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.dueDate)}</td>
        <td style="width: 17%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.total)}</td>
        <td style="width: 17%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.state)}</td>
      </tr>
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
                      <td style="text-align: center; width: 16%; padding: 0 8px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Faktura ID</td>
                      <td style="text-align: center; width: 18%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Period</td>
                      <td style="text-align: center; width: 16%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Fakturadatum</td>
                      <td style="text-align: center; width: 16%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Förfaller</td>
                      <td style="text-align: center; width: 17%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Belopp</td>
                      <td style="text-align: center; width: 17%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Status</td>
                      
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
        filename: `${supplier}.pdf`,
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

    emit("loading", false);
  }

}

const downloadCSV = async () => {
  exporteraMobile.value = false
  emit("loading", true);

  let data = buildExportParams()

  await supplierInvoices.fetchSupplierInvoices(data)

  let dataArray = [];
      
  supplierInvoices.getSupplierInvoices.forEach(element => {

    let data = {
      FAKTURA_ID: element.invoice_id ?? '-',
      PERIOD: element.billing_period ?? '-',
      FAKTURADATUM: element.invoice_date ?? '-',
      FÖRFALLER: element.due_date ?? '-',
      BELOPP: `${formatNumber((Number(element.total ?? 0) + Number(element.amount_discount ?? 0))) ?? '0,00'} kr`,
      STATUS: element.state?.name ?? '-',
    }

    dataArray.push(data)
  })

  const supplierName = supplierInvoices.getSupplierInfo?.supplier_name ?? null
  const supplier = `${supplierName}`
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9-]/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '') || 'supplier-billings'

  excelParser()
    .exportDataFromJSON(dataArray, supplier, "csv");

  emit("loading", false)

}


</script>

<template>
  <VCard class="company" v-if="props.isSupplier">
    <VCardText class="d-flex gap-6 justify-space-between px-0"
      :class="[
        windowWidth < 1024 ? 'flex-column' : 'flex-row',
        $vuetify.display.mdAndDown ? 'py-6' : 'py-4'
      ]"
    >
      <div class="title-tabs">
        Fakturahistorik
      </div>

      <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

      <div class="d-flex gap-2">
        <VMenu v-if="windowWidth >= 1024" v-model="isExportTypeMenuVisible">
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
            <VListItem @click="openExportDateMenu('pdf')">
              <VListItemTitle>Exportera PDF</VListItemTitle>
            </VListItem>
            <VListItem @click="openExportDateMenu('excel')">
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
          class="btn-gradient"
          block
          @click="addInvoice"
        >
          <VIcon icon="custom-plus" size="24" />
          Ny faktura
        </VBtn>
      </div>

      <ExportDateMenu
        v-model="date"
        v-model:menuVisible="isExportMenuVisible"
        :show-activator="false"
        :is-mobile="windowWidth < 1024"
        activator="#payout-export-button"
        @update:modelValue="onDatePickerUpdate"
      />

      <VDivider :class="windowWidth >= 1024 ? 'd-none' : 'd-flex'"/>
    </VCardText>

    <VCardText
      class="d-none align-center justify-space-between gap-1 px-0"
      :class="$vuetify.display.mdAndDown ? 'py-6' : 'py-4 gap-2'"
    >
      <!-- 👉 Search  -->
      <div class="search" :style="windowWidth < 1024 ? '' : 'width: 480px !important'">
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
      v-show="billings.length"
      class="px-0 pb-6 text-no-wrap"
    >
      <!-- 👉 table head -->
      <thead>
        <tr>
          <th scope="col" class="text-center"> # Faktura</th>
          <th scope="col" class="text-center"> Period </th>
          <th scope="col" class="text-center"> Fakturadatum </th>
          <th scope="col" class="text-center"> Förfaller </th>
          <th scope="col" class="text-center"> Belopp </th>
          <th scope="col" class="text-center"> Status </th>
          <th scope="col">Skapad av</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <!-- 👉 table body -->
      <tbody>
        <tr 
            v-for="billing in billings"
            :key="billing.id"
            style="height: 3rem;">

            <td class="text-center"> {{ billing.invoice_id }} </td>
            <td class="text-center"> {{ billing.period }} </td>
            <td class="text-center"> {{ billing.start_date }} </td>
            <td class="text-center"> {{ billing.end_date }} </td>
            <td class="text-center"> {{ formatNumber(billing.amount) ?? "0,00" }} kr </td>
            <td class="d-flex justify-center align-center"> 
              <div
                class="status-chip"
                :class="`status-chip-${resolveStatus(billing.state_id)?.class}`"
              >
                {{ billing.state_name }}
              </div>
            </td>
            <td style="width: 1%; white-space: nowrap">
              <div class="d-flex align-center gap-x-1" v-if="billing.user_id !== null">
                <VAvatar
                  variant="outlined"
                  size="38"
                >
                  <VImg
                    v-if="billing.user?.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + billing.user.avatar"
                  />
                  <PresetAvatarImage
                    v-else
                    :avatar-id="billing.user?.user_detail?.avatar_id"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ billing.user?.name ?? '-' }} {{ billing.user?.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="billing.user?.email && billing.user.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(billing.user?.email, 20) }}
                        </span>
                      </template>
                      <span>{{ billing.user?.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ billing.user?.email }}</span>
                  </span>
                </div>
              </div>
              <div class="d-flex align-center gap-x-1" v-else>
                <VAvatar
                  variant="outlined"
                  size="38"
                  class="supplier-company-logo-avatar"
                >
                  <VImg
                    style="border-radius: 50%; width: 24px; height: 24px; flex: 0 0 24px;"
                    :src="refreshAvatar"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium text-aqua">
                    Automatisk fakturering
                  </span>
                  <span class="text-sm text-disabled">Systemgenererad</span>
                </div>
              </div>
            </td>  
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;">
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>

                <VList>
                  <VListItem @click="showBilling(billing)">
                    <template #prepend>
                      <VIcon icon="custom-eye" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Se detaljer</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="billing.state_id === 4 || billing.state_id === 8"
                    @click="updateBilling(billing)">
                    <template #prepend>
                      <VIcon icon="custom-bribery" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Markera som betald</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if="billing.state_id === 7 && billing.is_credit === 0"
                      @click="updateBilling(billing)">
                    <template #prepend>
                      <VIcon icon="custom-money-transfer" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Markera som obetald</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if=" (billing.state_id === 4 || billing.state_id === 8) && billing.user_id !== null"
                      @click="editBilling(billing)">
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    @click="printBilling(billing)">
                    <template #prepend>
                      <VIcon icon="custom-print" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Skriv ut</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    @click="openBillingPdf(billing)">
                    <template #prepend>
                      <VIcon icon="custom-pdf" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa som PDF</VListItemTitle>
                  </VListItem>
                  <VListItem
                    @click="downloadBillingPdf(billing)">
                    <template #prepend>
                      <VIcon icon="custom-download" />
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    @click="replaceFile(billing)">
                    <template #prepend>
                      <VIcon icon="custom-refresh" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Ersätta faktura</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="billing.state_id !== 9 && billing.is_credit === 0"
                    @click="credit(billing)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-cancel-contract" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Kreditera</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </td>
        </tr>
      </tbody>
      <!-- 👉 table footer  -->
      <tfoot v-show="!billings.length">
        <tr>
          <td
            colspan="6"
            class="text-center">
            Uppgifter ej tillgängliga
          </td>
        </tr>
      </tfoot>
    </VTable>

    <div
      v-if="!billings.length"
      class="empty-state"
      :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
    >
      <VIcon
        :size="$vuetify.display.mdAndDown ? 80 : 120"
        icon="custom-order"
      />
      <div class="empty-state-content">
        <div class="empty-state-title">Inga fakturor än</div>
        <div class="empty-state-text">
          Fakturor visas här när det första betalningstillfället 
          har inträffat.
        </div>
      </div>
    </div>

    <VExpansionPanels
        class="expansion-panels pb-6 px-0"
        v-if="billings.length && $vuetify.display.mdAndDown"
      >
        <VExpansionPanel v-for="billing in billings" :key="billing.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          > 
            <span class="order-id">
              {{ billing.invoice_id }}
            </span>
            <div class="d-flex align-center justify-between w-100">
              <div class="order-title-box w-100">
                <span class="title-panel d-flex align-center w-100">
                  {{ billing.period }}
                  <VSpacer />
                  <div
                    class="status-chip-mobile pb-2"
                    :class="`status-chip-${resolveStatus(billing.state_id)?.class}`"
                  >
                    {{ billing.state_name }}
                  </div>
                </span>    
                <div class="title-organization">
                  Belopp
                  <div class="text-black">
                    {{ formatNumber(billing.amount) ?? "0,00" }} kr
                  </div>
                </div>        
              </div>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Fakturadatum:</div>
                <div class="expansion-panel-item-value">
                  {{ billing.start_date ?? "---" }}
                </div>
              </div>
            </div>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Förfaller:</div>
                <div class="expansion-panel-item-value">
                  {{ billing.end_date ?? "---" }}
                </div>
              </div>
            </div>

            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Skapad av:</div>
                <div class="expansion-panel-item-value">
                  <div class="d-flex align-center gap-x-1" v-if="billing.user_id !== null">
                    <VAvatar
                      variant="outlined"
                      size="32"
                    >
                      <VImg
                        v-if="billing.user?.avatar"
                        style="border-radius: 50%"
                        :src="themeConfig.settings.urlStorage + billing.user.avatar"
                      />
                      <PresetAvatarImage
                        v-else
                        :avatar-id="billing.user?.user_detail?.avatar_id"
                      />
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium font-14">
                        {{ billing.user?.name ?? '-' }} {{ billing.user?.last_name ?? "" }}
                      </span>
                      <span class="text-sm text-disabled">
                        <VTooltip 
                          v-if="billing.user?.email && billing.user.email.length > 20"
                          location="bottom">
                          <template #activator="{ props }">
                            <span v-bind="props" class="cursor-pointer">
                              {{ truncateText(billing.user?.email, 20) }}
                            </span>
                          </template>
                          <span>{{ billing.user?.email }}</span>
                        </VTooltip>
                        <span class="text-sm text-disabled"v-else>{{ billing.user?.email }}</span>
                      </span>
                    </div>
                  </div>
                  <div class="d-flex align-center gap-x-1" v-else>
                    <VAvatar
                      variant="outlined"
                      size="32"
                      class="supplier-company-logo-avatar"
                    >
                      <VImg
                        style="border-radius: 50%; width: 24px; height: 24px; flex: 0 0 24px;"
                        :src="refreshAvatar"
                      />
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium text-aqua font-14">
                        Automatisk fakturering
                      </span>
                      <span class="text-sm text-disabled">Systemgenererad</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="d-flex gap-4">
              <VBtn class="btn-light flex-1" @click="showBilling(billing)">
                <VIcon icon="custom-eye" size="24" />
                Se detaljer
              </VBtn>
              
              <VBtn class="btn-light" icon @click="selectedBillingForAction = billing; isMobileActionDialogVisible = true">
                <VIcon icon="custom-dots-vertical" size="24" />
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
    </VExpansionPanels>

    <VCardText
      v-if="billings.length"
      :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
      class="align-center flex-wrap gap-4 p-0"
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

  <!-- 👉 Export Mobile Dialog -->
  <VDialog
    v-model="exporteraMobile"
    transition="dialog-bottom-transition"
    content-class="dialog-bottom-full-width"
  >
    <VCard>
      <VList>
        <VListItem @click="openExportDateMenu('pdf')">
          <VListItemTitle>Exportera PDF</VListItemTitle>
        </VListItem>

        <VListItem @click="openExportDateMenu('excel')">
          <VListItemTitle>Exportera Excel</VListItemTitle>
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
          v-if="selectedBillingForAction.state_id === 4 || selectedBillingForAction.state_id === 8"
          @click="updateBilling(selectedBillingForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-bribery" size="24" />
          </template>
          <VListItemTitle>Markera som betald</VListItemTitle>
        </VListItem>
        <VListItem
          v-if="selectedBillingForAction.state_id === 7 && selectedBillingForAction.is_credit === 0"
          @click="updateBilling(selectedBillingForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-money-transfer" size="24" class="mr-2" />
          </template>
          <VListItemTitle>Markera som obetald</VListItemTitle>
        </VListItem>
        <VListItem
          v-if="(selectedBillingForAction.state_id === 4 || selectedBillingForAction.state_id === 8) && selectedBillingForAction.user_id !== null"
          @click="editBilling(selectedBillingForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-pencil" size="24" class="mr-2" />
          </template>
          <VListItemTitle>Redigera</VListItemTitle>
        </VListItem>
        <VListItem
            @click="printBilling(selectedBillingForAction); isMobileActionDialogVisible = false;">
          <template #prepend>
            <VIcon icon="custom-print" size="24" class="mr-2" />
          </template>
          <VListItemTitle>Skriv ut</VListItemTitle>
        </VListItem>
        <VListItem
            @click="openBillingPdf(selectedBillingForAction); isMobileActionDialogVisible = false;">
          <template #prepend>
            <VIcon icon="custom-pdf" size="24" class="mr-2" />
          </template>
          <VListItemTitle>Visa som PDF</VListItemTitle>
        </VListItem>
        <VListItem 
          @click="downloadBillingPdf(selectedBillingForAction); isMobileActionDialogVisible = false;">
          <template #prepend>
            <VIcon icon="custom-download" size="24" class="mr-2" />
          </template>
          <VListItemTitle>Ladda ner</VListItemTitle>
        </VListItem>
        <VListItem
          @click="replaceFile(selectedBillingForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-refresh" size="24" class="mr-2" />
          </template>
          <VListItemTitle>Ersätta faktura</VListItemTitle>
        </VListItem>
        <VListItem
          v-if="selectedBillingForAction.state_id !== 9 && selectedBillingForAction.is_credit === 0"
          @click="credit(selectedBillingForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-cancel-contract" size="24" class="mr-2" />
          </template>
          <VListItemTitle>Kreditera</VListItemTitle>
        </VListItem>
      </VList>
    </VCard>
  </VDialog>

  <VDialog
    v-model="isConfirmKreditera"
    persistent
    class="action-dialog"
  >
    <VBtn
      icon
      class="btn-white close-btn"
      @click="isConfirmKreditera = !isConfirmKreditera"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <VCard>
      <VCardText class="dialog-title-box">
        <VIcon size="32" icon="custom-cancel-contract" class="action-icon" />
        <div class="dialog-title">
          Kreditera faktura
        </div>
      </VCardText>
      <VCardText class="dialog-text">
        En hel kreditering innebär att du tar bort din fordran på leverantörer till fullo.
        Är du säker på att du vill kreditera fakturan
        <strong>#{{ selectedBilling.invoice_id }}</strong
        >?
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn class="btn-light" @click="isConfirmKreditera = false">
          Avbryt
        </VBtn>
        <VBtn class="btn-gradient" @click="kreditera"> Kreditera </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

      <!-- 👉 Update State -->
    <VDialog
      v-model="isConfirmStateDialogVisible"
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmStateDialogVisible = !isConfirmStateDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-cash-2" class="action-icon" />
          <div class="dialog-title">
            Uppdatera status
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill uppdatera fakturans status
          <strong>#{{ selectedBilling.invoice_id }}</strong> till 
          {{ selectedBilling.state_id === 7 ? 'obetald' : 'betald' }}?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmStateDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="updateState"> Acceptera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <input
      ref="replaceFileInput"
      type="file"
      accept="application/pdf,.pdf"
      style="display: none"
      @change="onReplaceFileSelected"
    >
</template>

<style>
  .iconsButton .v-btn--icon.v-btn--density-default {
    width: calc(var(--v-btn-height) + 0px) !important;
    height: 25px !important;
  }
  .supplier-company-logo-avatar {
    background-color: #E7E7E7 !important;
    border: 1px solid #E7E7E7 !important;
  }
  .facturing.v-card--variant-elevated {
      box-shadow: none !important;
  }
</style>
