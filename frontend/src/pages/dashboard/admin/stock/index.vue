<script setup>

import { useRoute } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useVehiclesStores } from '@/stores/useVehicles'
import { useAuthStores } from '@/stores/useAuth';
import { useConfigsStores } from '@/stores/useConfigs';
import { useAppAbility } from '@/plugins/casl/useAppAbility';
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { formatNumber, formatNumberInteger } from '@/@core/utils/formatters'
import { yearValidator, requiredValidator } from '@/@core/utils/validators'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js'
import show from "@/components/vehicles/show.vue";
import showMobile from "@/components/vehicles/showMobile.vue";
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import ExportDateMenu from '@/components/common/ExportDateMenu.vue'
import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";

const vehiclesStores = useVehiclesStores()
const configsStores = useConfigsStores();
const authStores = useAuthStores();
const ability = useAppAbility()
const emitter = inject("emitter")
const route = useRoute()

const { width: windowWidth } = useWindowSize();
const sectionEl = ref(null);
const hasLoaded = ref(false);

const filtreraMobile = ref(false);

const userData = ref(null)
const role = ref(null)
const suppliers = ref([])
const supplier_id = ref(null)

const vehicles = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalVehicles = ref(0)
const isRequestOngoing = ref(true)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmCreateDialogVisible = ref(false)
const isVehicleDetailDialog = ref(false)
const isMobile = ref(false)
const selectedVehicle = ref({})
const state_id = ref(null)
const year = ref(null)

const gearboxes = ref([])
const gearbox_id = ref(null)
const brands = ref([])
const brand_id = ref(null)
const models = ref([])
const model_id = ref(null)
const modelsByBrand = ref([])

const plate = ref(null)
const refForm = ref()

const selectedVehicleForAction = ref({});
const isMobileActionDialogVisible = ref(false);
const hasProcessedCreateAction = ref(false);
const showVehicleExistentDialog = ref(false)

const date = ref(null)
const selectedExportType = ref(null)
const isExportTypeMenuVisible = ref(false)
const isExportMenuVisible = ref(false)
const isExportingFile = ref(false)
const lastExportSelectionKey = ref(null)
const COMPANY_STORAGE_KEY = 'clients_company_snapshot';

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

const states = ref ([
  { id: 10, name: "I lager" },
  { id: 11, name: "På annons" },
  { id: 13, name: "Förmedlingsbil" }
])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const { mdAndDown } = useDisplay()
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end")

const isFilterDialogVisible = ref(false)

// 👉 Column visibility state
const isColumnsDialogVisible = ref(false)
const visibleColumns = ref([])
const didInitVisibleColumns = ref(false)

const columnOptions = [
  { id: 'purchase_date', label: 'Inköpsdatum' },
  { id: 'info', label: 'Bilinfo' },
  { id: 'reg_num', label: 'Reg nr' },
  { id: 'purchase_price', label: 'Inköpspris' },
  { id: 'mileage', label: 'Miltal' },
  { id: 'comments', label: 'Anteckningar' },
  { id: 'state', label: 'Status' },
  { id: 'vat', label: 'VAT' },
  { id: 'control_inspection', label: 'Besiktigas' },
  { id: 'seller', label: 'Säljaren' },
  { id: 'supplier', label: 'Leverantör' },
  { id: 'created_by', label: 'Skapad av' },
]

const availableColumnOptions = computed(() => {
  const canSeeSupplier = role.value === 'SuperAdmin' || role.value === 'Administrator'
  return canSeeSupplier
    ? columnOptions
    : columnOptions.filter(opt => opt.id !== 'supplier')
})

const defaultColumns = computed(() => availableColumnOptions.value.slice(0, 5).map(opt => opt.id))

watch(
  () => role.value,
  () => {
    if (!didInitVisibleColumns.value && role.value) {
      const saved = localStorage.getItem('stock_visible_columns')
      const allowed = new Set(availableColumnOptions.value.map(o => o.id))
      const initial = saved ? JSON.parse(saved).filter((id) => allowed.has(id)) : defaultColumns.value
      visibleColumns.value = initial
      didInitVisibleColumns.value = true
    }
  },
  { immediate: true }
)

watch(
  () => visibleColumns.value,
  (val) => {
    if (didInitVisibleColumns.value) {
      localStorage.setItem('stock_visible_columns', JSON.stringify(val))
    }
  },
  { deep: true }
)

watch(isExportMenuVisible, isVisible => {
  if (isVisible)
    lastExportSelectionKey.value = null
})

const isColVisible = (id) => visibleColumns.value.includes(id)

const selectAllColumns = () => {
  visibleColumns.value = availableColumnOptions.value.map(o => o.id)
}

const selectDefaultColumns = () => {
  visibleColumns.value = defaultColumns.value
}

const clearColumns = () => {
  visibleColumns.value = []
}

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = vehicles.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    vehicles.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalVehicles.value} resultat`;
  //return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalVehicles.value } register`
})

watch(() => plate.value, (val) => {
  plate.value = val === null ? null : val.toUpperCase()
});

// Watch para limpiar query parameter cuando se cierra el modal
watch(
  () => isConfirmCreateDialogVisible.value,
  (isOpen) => {
    if (!isOpen && route.query.action === 'create') {
      router.replace({ name: route.name, query: {} });
    }
  }
);

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
    state_id.value = null
    brand_id.value = null
    model_id.value = null
    year.value = null
    gearbox_id.value = null
    supplier_id.value = null
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'purchase_date,id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    isSold: 0,
    state_id: state_id.value,
    brand_id: brand_id.value,
    model_id: model_id.value,
    year: year.value,
    gearbox_id: gearbox_id.value,
    supplier_id: supplier_id.value
  }

  isRequestOngoing.value = 
    (searchQuery.value !== '' || year.value !== null)
    ? false 
    : true

  await vehiclesStores.fetchVehicles(data)

  brands.value = vehiclesStores.getBrands
  models.value = vehiclesStores.getModels
  gearboxes.value = vehiclesStores.getGearboxes
  vehicles.value = vehiclesStores.getVehicles
  totalPages.value = vehiclesStores.last_page
  totalVehicles.value = vehiclesStores.vehiclesTotalCount

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value?.roles?.[0]?.name ?? null

  if(role.value === 'SuperAdmin' || role.value === 'Administrator') {
    suppliers.value = vehiclesStores.getSuppliers
  }

  vehicles.value.forEach(vehicle => {
    vehicle.checked = false;
  });

  hasLoaded.value = true;
  isRequestOngoing.value = false

}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const showDeleteDialog = vehicleData => {
  isConfirmDeleteDialogVisible.value = true
  selectedVehicle.value = { ...vehicleData }
}

const showVehicle = async (id, mobile = false) => {
  isVehicleDetailDialog.value = true
  isMobile.value = mobile
  selectedVehicle.value = vehicles.value.filter((element) => element.id === id )[0]
}

const removeVehicle = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await vehiclesStores.deleteVehicle(selectedVehicle.value.id)
  selectedVehicle.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Fordon borttagen!' : res.data.message,
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

const editVehicle = vehicleData => {
  router.push({ name : 'dashboard-admin-stock-edit-id', params: { id: vehicleData.id } })
}

const sellVehicle = vehicleData => {
  router.push({ name : 'dashboard-admin-sold-id', params: { id: vehicleData.id } })
}

const getModels = computed(() => {
  return modelsByBrand.value.map((model) => {
    return {
      title: model.name,
      value: model.id
    }
  })
})

const selectBrand = brand => {
    if (brand) {
        let _brand = brands.value.find(item => item.id === brand)
    
        model_id.value = ''
        modelsByBrand.value = models.value.filter(item => item.brand_id === _brand.id)
    }
}

const onSubmit = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {

      isConfirmCreateDialogVisible.value = false
      isRequestOngoing.value = true

      const carRes = await vehiclesStores.findByRegNum({ regnum: plate.value })
      
      if(carRes.success === true && carRes.data.vehicle) {     
        plate.value = null
        showVehicleExistentDialog.value = true
        isRequestOngoing.value = false
      } else {
        vehiclesStores.setRegNum(plate.value)
        vehiclesStores.setCommonInfo(carRes.data.common_info)
        router.push({ name : 'dashboard-admin-stock-add' })
      }
    }
  })
}

const seeClient = clientData => {
  router.push({ name : 'dashboard-admin-clients-id', params: { id: clientData.id } })
}

const openLink = function (vehicleData) {
  window.open(themeConfig.settings.urlStorage + vehicleData.file);
};

const download = async(vehicle) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + vehicle.file);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = vehicle.file.replace('pdfs/', '');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

  } catch (error) {
    console.error('Error:', error);
  }
};

const downloadCSV = async () => {

  isRequestOngoing.value = true

  try {

  const data = {
      limit: -1,
      ...getDateRangePayload(),
      orderByField: 'id',
      orderBy: "desc"
  };

  await vehiclesStores.fetchVehicles(data)

  const includeSupplierColumn = role.value === 'SuperAdmin' || role.value === 'Administrator'
      
  const dataArray = vehiclesStores.getVehicles.map(element => {

    const row = {
      Inköpsdatum: element.purchase_date ?? '',
      Bilinfo: element.car_name,
      Regnr: element.reg_num,
      Inköpspris: formatNumber(element.purchase_price ?? 0) + ' kr',
      Miltal: element.mileage === null ? '' : formatNumberInteger(element.mileage ?? '0,00') + ' Mil',
      Anteckningar:  element.comments ?? '',
      Status: element.state.name,
      VAT: element.iva_purchase?.name,
      Besiktigas: element.control_inspection ?? '',
      Säljaren: element.client_purchase?.fullname ?? ''
    }

    if (includeSupplierColumn) {
        row.Leverantör = element.supplier
          ? `${element.supplier.user.name} ${element.supplier.user.last_name ?? ''}`.trim()
          : ''
      }

      return row
   })

  excelParser().exportDataFromJSON(dataArray, "vehicles", "csv");

  } finally {
    isRequestOngoing.value = false
  }

};

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

const getDateRangePayload = () => {
  if (!date.value)
    return {}

  if (Array.isArray(date.value)) {
    const from = toYmd(date.value[0])
    const to = toYmd(date.value[1] ?? date.value[0])
    if (from && to)
      return { date_from: from, date_to: to }
  }

  if (typeof date.value === 'string') {
    const splitByRange = date.value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i)
    if (splitByRange.length >= 2) {
      const from = toYmd(splitByRange[0])
      const to = toYmd(splitByRange[1])
      if (from && to)
        return { date_from: from, date_to: to }
    }

    const single = toYmd(date.value)
    if (single)
      return { date_from: single, date_to: single }
  }

  if (date.value instanceof Date) {
    const single = toYmd(date.value)
    if (single)
      return { date_from: single, date_to: single }
  }

  return {}
}

const downloadPDF = async () => {
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
      limit: -1,
      ...getDateRangePayload(),
      orderByField: 'id',
      orderBy: 'desc'
    }

    await vehiclesStores.fetchVehicles(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }

    const includeSupplierColumn = role.value === 'SuperAdmin' || role.value === 'Administrator'
    const columnWidth = includeSupplierColumn ? '13%' : '14%'

    const rows = vehiclesStores.getVehicles.map(element => ({
      purchase_date: element.purchase_date,
      info: element.car_name,
      reg_num: element.reg_num,
      purchase_price: `${formatNumber(element.purchase_price ?? 0)} kr`,
      mileage: element.mileage === null ? '' : formatNumberInteger(element.mileage ?? '0,00') + ' Mil',
      seller: element.client_purchase?.fullname ?? '',
      supplier: element.supplier
        ? `${element.supplier.user.name} ${element.supplier.user.last_name ?? ''}`.trim()
        : ''
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'I lager',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      <tr style="height: 44px;">
        <td style="width: ${includeSupplierColumn ? '22%' : '30%'}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: start; vertical-align: middle;">${escapeHtml(item.info)}</td>
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.purchase_date)}</td>
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.reg_num)}</td>        
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.purchase_price)}</td>
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.mileage)}</td>
        ${includeSupplierColumn ? `<td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.supplier)}</td>` : ''}
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.seller)}</td>
      </tr>
    `).join('')

    pdfContainer = document.createElement('div')
    pdfContainer.innerHTML = `
      <div style="font-family: ${pdfFontFamily} !important; color: #454545; background-color: #FFFFFF; letter-spacing: 0; width: 100%;">
        <table style="width: 100%; border-spacing: 0; border-collapse: separate; font-size: 11px; font-weight: 400;">
          <tbody>
            <tr>
              <td>
                ${headerMarkup}

                <table style="width: 100%; table-layout: fixed; border-spacing: 0; border-collapse: separate; margin-top: 10px; font-family: ${pdfFontFamily} !important; font-size: 11px;">
                  <thead>
                    <tr style="height: 44px;">
                      <td style="text-align: start; width: ${includeSupplierColumn ? '22%' : '30%'}; padding: 0 8px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Bilinfo</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Inköpsdatum</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Reg nr</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Inköpspris</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Miltal</td>
                      ${includeSupplierColumn ? `<td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Leverantör</td>` : ''}
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Säljaren</td>
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
        filename: 'vehicles.pdf',
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
  selectedExportType.value = type
  isExportTypeMenuVisible.value = false

  nextTick(() => {
    isExportMenuVisible.value = true
  })
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

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
};

const resolveStatus = state_id => {
  if (state_id === 10)
    return { class: 'pending' }
  if (state_id === 11)
    return { class: 'info' }   
  if (state_id === 13)
    return { class: 'success' }
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(async () => {
  try {
    resizeSectionToRemainingViewport();
    window.addEventListener("resize", resizeSectionToRemainingViewport);
    
    // Check if we should open create dialog
    if (route.query.action === 'create' && !hasProcessedCreateAction.value) {
      hasProcessedCreateAction.value = true;
      isConfirmCreateDialogVisible.value = true;
    }

    userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
    role.value = userData.value?.roles?.[0]?.name ?? null;

    if (!role.value) return;

    if (role.value === "SuperAdmin" || role.value === "Administrator") {
      suppliers.value = vehiclesStores.getSuppliers
    }

    const { user_data, userAbilities } = await authStores.me(userData.value)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

    ability.update(userAbilities)

    localStorage.setItem('user_data', JSON.stringify(user_data))

    if (role.value === 'Supplier') {
      setCompany({
        ...(user_data?.user_detail ?? {}),
        email: user_data?.email ?? '',
        name: user_data?.name ?? '',
        last_name: user_data?.last_name ?? '',
      });
    } else if (role.value === 'User') {
      setCompany({
        ...(user_data?.supplier?.boss?.user?.user_detail ?? {}),
        email: user_data?.supplier?.boss?.user?.email ?? '',
        name: user_data?.supplier?.boss?.user?.name ?? '',
        last_name: user_data?.supplier?.boss?.user?.last_name ?? '',
      });
    } else {
      await configsStores.getFeature('company')
      await configsStores.getFeature('logo')

      const companyConfig = configsStores.getFeaturedConfig('company') ?? {};
      const logoConfig = configsStores.getFeaturedConfig('logo') ?? {};

      setCompany({
        ...companyConfig,
        logo: logoConfig.logo ?? companyConfig.logo ?? '',
      });
    }

  } catch (error) {
    console.error('Failed to load company data:', error);
  }
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

    <Toaster />

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
        <div class="align-center font-blauer">
          <h2>I lager <span v-if="hasLoaded">({{ totalVehicles }})</span></h2>
        </div>

        <div class="d-flex gap-4">
          <VMenu v-model="isExportTypeMenuVisible">
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

          <ExportDateMenu
            v-model="date"
            v-model:menuVisible="isExportMenuVisible"
            :show-activator="false"
            :is-mobile="windowWidth < 1024"
            activator="#payout-export-button"
            @update:modelValue="onDatePickerUpdate"
          />

          <VBtn
            v-if="$can('create', 'stock')"
            class="btn-gradient"
            block
            @click="isConfirmCreateDialogVisible = true">
              <VIcon icon="custom-plus" size="24" />
              Lägg till en bil
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search" style="width: 480px !important">
          <VTextField
            v-model="searchQuery"
            placeholder="Sök"
            clearable
          />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <VBtn 
          class="btn-white-2 px-3"
          :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"
          @click="isFilterDialogVisible = true"
        >
          <VIcon icon="custom-filter" size="24" />
          <span :class="windowWidth < 1024 ? 'd-none' : 'd-flex'">Filtrera efter</span>
        </VBtn>

        <VBtn 
          class="btn-white-2 px-3"
          :class="windowWidth >= 1024 ? 'd-none' : 'd-flex'"
          @click="filtreraMobile = true"
        >
          <VIcon icon="custom-filter" size="24" />
        </VBtn>
        
        <VBtn
          class="btn-white-2"
          :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"
          @click="isColumnsDialogVisible = true">
          <VIcon icon="custom-column" size="24" />
          <span>Kolumner</span>
        </VBtn>

        <div
          v-if="!$vuetify.display.mdAndDown"
          class="d-flex align-center visa-select"
        >
          <span class="text-no-wrap pr-4">Visa</span>
          <VSelect
            v-model="rowPerPage"
            density="compact"
            variant="outlined"
            :items="[10, 20, 30, 50]"/>
        </div>
      </VCardText>

      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="vehicles.length"
        class="pt-2 px-4 pb-6 text-no-wrap"
        style="border-radius: 0 !important"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col" v-if="isColVisible('purchase_date')"> Inköpsdatum </th>
            <th scope="col" v-if="isColVisible('info')"> Bilinfo</th>
            <th class="text-center" scope="col" v-if="isColVisible('reg_num')"> Reg nr </th>
            <th class="text-center" scope="col" v-if="isColVisible('purchase_price')"> Inköpspris </th>
            <th class="text-center" scope="col" v-if="isColVisible('mileage')"> Miltal </th>
            <th class="text-center" scope="col" v-if="isColVisible('comments')"> Anteckningar </th>
            <th class="text-center" scope="col" v-if="isColVisible('state')"> Status </th>
            <th class="text-center" scope="col" v-if="isColVisible('vat')"> VAT </th>
            <th class="text-center" scope="col" v-if="isColVisible('control_inspection')"> Besiktigas </th>
            <th scope="col" v-if="isColVisible('seller')"> Säljaren </th>
            <th scope="col" v-if="(role === 'SuperAdmin' || role === 'Administrator') && isColVisible('supplier')"> Leverantör </th>
            <th scope="col" v-if="isColVisible('created_by')"> Skapad av </th>  
            <th scope="col" v-if="$can('edit', 'stock') || $can('delete', 'stock')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody>
          <tr 
            v-for="vehicle in vehicles"
            :key="vehicle.id"
            style="height: 3rem;">
            <td v-if="isColVisible('purchase_date')"> {{ vehicle.purchase_date }} </td>
            <td class="cursor-pointer" v-if="isColVisible('info')" @click="showVehicle(vehicle.id, false)">
              <div class="d-flex align-center gap-x-3"> 
                <VAvatar
                  v-if="vehicle.model?.brand?.logo"
                  size="24"
                  variant="tonal"
                  :image="themeConfig.settings.urlStorage + vehicle.model.brand.logo"
                />
                <VAvatar
                    v-else
                    size="24"
                    color="#D9D9D9"
                >              
                </VAvatar>
                <div class="d-flex flex-column">
                  <VTooltip 
                    v-if="vehicle.car_name && vehicle.car_name.length > 25"
                    location="bottom"
                    max-width="300">
                    <template #activator="{ props }">
                      <span v-bind="props" class="font-weight-medium cursor-pointer text-aqua">
                        {{ truncateText(vehicle.car_name, 25) }}
                      </span>
                    </template>
                    <span>{{ vehicle.car_name }}</span>
                  </VTooltip>
                  <template v-else>
                    <span v-if="vehicle.model_id" class="font-weight-medium cursor-pointer text-aqua">
                      {{ vehicle.car_name}}
                    </span>
                  </template>                  
                </div>
              </div>
            </td>   
            <td class="text-center" v-if="isColVisible('reg_num')"> {{ vehicle.reg_num }} </td>             
            <td class="text-center" v-if="isColVisible('purchase_price')"> {{ formatNumber(vehicle.purchase_price ?? 0) }} kr </td>
            <td class="text-center" v-if="isColVisible('mileage')"> {{ vehicle.mileage === null ? '' : formatNumberInteger(vehicle.mileage ?? '0,00') + ' Mil' }}</td>
            <td class="text-center" v-if="isColVisible('comments')">
              <VTooltip 
                v-if="vehicle.comments && vehicle.comments.length > 15"
                location="bottom"
                max-width="300">
                <template #activator="{ props }">
                  <span v-bind="props" class="cursor-pointer">
                    {{ truncateText(vehicle.comments) }}
                  </span>
                </template>
                <span>{{ vehicle.comments }}</span>
              </VTooltip>
              <span v-else>{{ vehicle.comments }}</span>
            </td>
            <td class="text-center text-wrap d-flex justify-center align-center" style="width: 120px;" v-if="isColVisible('state')"> 
              <div
                class="status-chip"
                :class="`status-chip-${resolveStatus(vehicle.state.id)?.class}`"
              >
                {{ vehicle.state.name }}
              </div> 
            </td>
            <td class="text-center" v-if="isColVisible('vat')"> {{ vehicle.iva_purchase?.name }} </td>
            <td class="text-center" v-if="isColVisible('control_inspection')"> {{ vehicle.control_inspection }} </td>
            <td style="width: 1%; white-space: nowrap" v-if="isColVisible('seller')">
              <div class="d-flex flex-column">
                <span v-if="vehicle.client_purchase?.client_id !== null  && !vehicle.client_purchase.client.deleted_at" class="font-weight-medium cursor-pointer text-aqua" @click="seeClient(vehicle.client_purchase?.client)">
                  {{ vehicle.client_purchase?.fullname }}     
                </span>
                <span v-else class="font-weight-medium text-aqua">
                  {{ vehicle.client_purchase?.fullname }} 
                </span>
                <span class="text-sm text-disabled">{{ vehicle.client_purchase?.phone }}</span>
              </div>
            </td> 
            <td style="width: 1%; white-space: nowrap" v-if="(role === 'SuperAdmin' || role === 'Administrator') && isColVisible('supplier')">
              <span v-if="vehicle.supplier">
                {{ vehicle.supplier.user.name }}
                {{ vehicle.supplier.user.last_name ?? "" }}
              </span>
            </td>
            <td style="width: 1%; white-space: nowrap" v-if="isColVisible('created_by')">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  variant="outlined"
                  size="38"
                >
                  <VImg
                    v-if="vehicle.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + vehicle.user.avatar"
                  />
                  <PresetAvatarImage
                    v-else
                    :avatar-id="vehicle.user?.user_detail?.avatar_id"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ vehicle.user.name }} {{ vehicle.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="vehicle.user.email && vehicle.user.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(vehicle.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ vehicle.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ vehicle.user.email }}</span>
                  </span>
                </div>
              </div>
            </td>
            <!-- 👉 Actions -->
            <td 
              class="text-center" 
              style="width: 3rem;" 
              v-if="$can('edit', 'stock') || $can('delete', 'stock')"
            >      
              <VMenu>    
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem 
                    v-if="$can('edit', 'stock')" 
                    @click="showVehicle(vehicle.id, false)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-eye" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('edit', 'stock')" 
                    @click="sellVehicle(vehicle)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-boot" class="mr-2" size="24" />
                    </template>
                    <VListItemTitle>Sälj bil</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('edit', 'stock')" 
                    @click="editVehicle(vehicle)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem
                    class="d-none"
                    v-if="$can('view', 'stock')"
                    @click="openLink(vehicle)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-pdf" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa som PDF</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    class="d-none"
                    v-if="$can('edit', 'stock')" 
                    @click="download(vehicle)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-download" class="mr-2" size="24" />
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','stock')" 
                    @click="showDeleteDialog(vehicle)"
                  >
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
        v-if="!isRequestOngoing && hasLoaded && !vehicles.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-steering-wheel"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Ditt fordonslager är tomt</div>
          <div class="empty-state-text">
            Registrera de fordon du har till salu för att enkelt hantera ditt lager och koppla dem till fakturor.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'stock')"
          @click="isConfirmCreateDialogVisible = true"
        >
          Lägg till fordon
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="vehicles.length && windowWidth < 1024"
      >
        <VExpansionPanel v-for="vehicle in vehicles" :key="vehicle.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <div class="d-flex align-center w-100">
                <VAvatar
                  v-if="vehicle.model?.brand?.logo"
                  size="32"
                  variant="tonal"
                  class="me-3"
                  :image="themeConfig.settings.urlStorage + vehicle.model.brand.logo"
                />
                <VAvatar
                    v-else
                    size="32"
                    color="#D9D9D9"
                    class="me-3"
                >            
                </VAvatar>

                <div class="d-flex flex-column gap-1">
                    <span class="text-aqua">Reg. nr. {{ vehicle.reg_num }}</span>
                    <span class="text-neutral-3">
                      {{ [vehicle.model?.brand?.name, vehicle.model?.name].filter(Boolean).join(' ') }}{{ vehicle.year ? `, ${vehicle.year}` : '' }}
                    </span>
                </div>
            </div>
          </VExpansionPanelTitle>
          
          <VExpansionPanelText>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Inköpspris</div>
              <div class="expansion-panel-item-value">{{ formatNumber(vehicle.purchase_price ?? 0) }} kr</div>
            </div>
             
            <div class="mb-6">
              <div class="expansion-panel-item-label">Miltal</div>
              <div class="expansion-panel-item-value">{{ vehicle.mileage ? vehicle.mileage + ' Mil' : '-' }}</div>
            </div>

            <div class="d-flex gap-4">
              <VBtn class="btn-light flex-1" @click="showVehicle(vehicle.id, true)">
                <VIcon icon="custom-eye" size="24" class="me-2"/>
                Se detaljer
              </VBtn>
              <VBtn class="btn-light" icon @click="selectedVehicleForAction = vehicle; isMobileActionDialogVisible = true">
                <VIcon icon="custom-dots-vertical" size="24" />
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>
    
      <VCardText
        v-if="vehicles.length"
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
          prev-icon="custom-chevron-left"/>
      
      </VCardText>
    </VCard>

  <!-- Filter Dialog -->
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

    <VCard flat class="card-form">
      <VCardText class="dialog-title-box">
        <VIcon size="32" icon="custom-filter" class="action-icon" />
        <div class="dialog-title">
          Filtrera
        </div>
      </VCardText>
      
      <VCardText class="pt-0">
        <VRow class="pt-3">
          <VCol 
            cols="12" md="12" 
            v-if="role === 'SuperAdmin' || role === 'Administrator'"
            class="pb-0">
            <AppAutocomplete
              prepend-icon="custom-profile"
              v-model="supplier_id"
              placeholder="Leverantörer"
              :items="suppliers"
              :item-title="item => item.full_name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              class="selector-user selector-truncate"
            />
          </VCol>
          <VCol cols="12" md="12" class="pb-0">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Status" />
            <AppAutocomplete
              v-model="state_id"
              :items="states"
              :item-title="item => item.name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"/>
          </VCol>
          <VCol cols="12" md="12" class="pb-0">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Märke" />
            <AppAutocomplete
              v-model="brand_id"
              :items="brands"
              :item-title="item => item.name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              @update:modelValue="selectBrand"
              :menu-props="{ maxHeight: '300px' }"/>
          </VCol>
          <VCol cols="12" md="12" class="pb-0">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modell" />
            <AppAutocomplete
              v-model="model_id"
              :items="getModels"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              :menu-props="{ maxHeight: '300px' }"/>
          </VCol>
          <VCol cols="12" md="12" class="pb-0">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Årsmodell" />
            <VTextField
                v-model="year"
                :rules="[yearValidator]"
                clearable
            />
          </VCol>
          <VCol cols="12" md="12">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Biltyp" />
            <AppAutocomplete
              v-model="gearbox_id"
              :items="gearboxes"
              :item-title="item => item.name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"/>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0">
        <VBtn
          class="btn-light"
          @click="fetchData(true); isFilterDialogVisible = false">
            Rensa filter
        </VBtn>
        <VBtn class="btn-gradient" @click="isFilterDialogVisible = false">
            Visa resultat
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Columns -->
  <VDialog
    v-model="isColumnsDialogVisible"
    persistent
    class="action-dialog"
  >
    <VBtn
      icon
      class="btn-white close-btn"
      @click="isColumnsDialogVisible = !isColumnsDialogVisible"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>
    <VCard>
      <VCardText class="dialog-title-box">
        <VIcon size="32" icon="custom-column" class="action-icon" />
        <div class="dialog-title">
          Välj kolumner
        </div>
      </VCardText>
      <VCardText class="pt-0">
        <VRow>
          <VCol cols="12">
            <div class="d-flex gap-2 flex-wrap">
              <VBtn class="btn-gradient" size="small" @click="selectAllColumns">Alla</VBtn>
              <VBtn class="btn-blue" size="small" @click="selectDefaultColumns">Standard (5)</VBtn>
              <VBtn class="btn-light" size="small" @click="clearColumns">Rensa</VBtn>
            </div>
          </VCol>
          <VCol cols="12">
            <VCheckbox
              v-for="opt in availableColumnOptions"
              :key="opt.id"
              :label="opt.label"
              :value="opt.id"
              v-model="visibleColumns"
              density="comfortable"
              hide-details
            />
          </VCol>
        </VRow>
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0">
        <VBtn class="btn-light" @click="isColumnsDialogVisible = false">Stäng</VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Confirm Create -->
  <VDialog
    v-model="isConfirmCreateDialogVisible"
    persistent
    class="action-dialog" >
    <!-- Dialog close btn -->
      
    <VBtn
      icon
      class="btn-white close-btn"
      @click="isConfirmCreateDialogVisible = !isConfirmCreateDialogVisible"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <!-- Dialog Content -->
    <VForm
      ref="refForm"
      @submit.prevent="onSubmit">
      <VCard flat class="card-form">
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-lager" class="action-icon" />
          <div class="dialog-title">
            Lägg till en bil
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          <VLabel
              class="mb-1 text-body-2 text-high-emphasis"
              text="Reg. nummer"
            />
          <VTextField
              v-model="plate"
              :rules="[requiredValidator]"
              placeholder="ABC12X"
          />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmCreateDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" type="submit">
              Fortsätt
          </VBtn>
        </VCardText>
      </VCard>
    </VForm>
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
          Radera fordon från lager?
        </div>
      </VCardText>
      <VCardText class="dialog-text">
        Du är på väg att permanent radera <strong>{{ selectedVehicle.reg_num }}</strong> från ditt lager.
        Denna åtgärd kan inte ångras.
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn
          class="btn-light"
          @click="isConfirmDeleteDialogVisible = false">
            Avbryt
        </VBtn>
        <VBtn class="btn-gradient" @click="removeVehicle">
            Ja, radera posten
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
    <VCard class="card-form">
      <VList>
        <VListItem class="form py-0" v-if="role === 'SuperAdmin' || role === 'Administrator'">
          <AppAutocomplete
            prepend-icon="custom-profile"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="item => item.full_name"
            :item-value="item => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate"
          />
        </VListItem>
        <VListItem class="form">
          <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Status" />            
          <AppAutocomplete
            v-model="state_id"
            
            :items="states"
            :item-title="item => item.name"
            :item-value="item => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"/>
        </VListItem>
        <VListItem class="form pt-6">
          <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Märke" />
          <AppAutocomplete
            v-model="brand_id"
            :items="brands"
            :item-title="item => item.name"
            :item-value="item => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            @update:modelValue="selectBrand"
            :menu-props="{ maxHeight: '300px' }"/>
        </VListItem>
        <VListItem class="form">
          <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modell" />
          <AppAutocomplete
            v-model="model_id"
            :items="getModels"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            :menu-props="{ maxHeight: '300px' }"/>
        </VListItem>
        <VListItem class="form">
          <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Årsmodell" />
          <VTextField
            v-model="year"
            :rules="[yearValidator]"
            clearable
          />
        </VListItem>
        <VListItem class="form">
          <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Biltyp" />
          <AppAutocomplete
            v-model="gearbox_id"
            :items="gearboxes"
            :item-title="item => item.name"
            :item-value="item => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"/>
        </VListItem>
        <VListItem class="form mt-5">
          <VBtn class="btn-gradient w-100" @click="filtreraMobile = false">
              Visa resultat
          </VBtn>
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
          v-if="$can('edit', 'stock')"
          @click="sellVehicle(selectedVehicleForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-boot" size="24" />
          </template>
          <VListItemTitle>Sälj bil</VListItemTitle>
        </VListItem>
        <VListItem
          v-if="$can('view', 'stock')"
          @click="editVehicle(selectedVehicleForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-pencil" size="24" />
          </template>
          <VListItemTitle>Redigera</VListItemTitle>
        </VListItem>
        <VListItem
          class="d-none"
          v-if="$can('view', 'stock')"
          @click="openLink(selectedVehicleForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-pdf" size="24" />
          </template>
          <VListItemTitle>Visa som PDF</VListItemTitle>
        </VListItem>
        <VListItem
          v-if="$can('edit', 'stock')"
          class="d-none"
          @click="download(selectedVehicleForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-download" size="24" />
          </template>
          <VListItemTitle>Ladda ner</VListItemTitle>
        </VListItem>
        <VListItem
          v-if="$can('edit', 'stock') && selectedVehicleForAction.state_id !== 9"
          @click="showDeleteDialog(selectedVehicleForAction); isMobileActionDialogVisible = false;"
        >
          <template #prepend>
            <VIcon icon="custom-waste" size="24" />
          </template>
          <VListItemTitle>Ta bort</VListItemTitle>
        </VListItem>
      </VList>
    </VCard>
  </VDialog>

  <!-- 👉 Vehicle Existent Dialog -->
  <VDialog
      v-model="showVehicleExistentDialog"
      persistent
      class="action-dialog dialog-big-icon"
  >
      <VBtn
          icon
          class="btn-white close-btn"
          @click="showVehicleExistentDialog = !showVehicleExistentDialog"
      >
          <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
          <VCardText class="dialog-title-box big-icon justify-center pb-0">
              <VIcon size="90" icon="custom-steering-wheel" />
          </VCardText>
          <VCardText class="dialog-title-box justify-center">
              <div class="dialog-title">Kunde inte skapa fordonet</div>
          </VCardText>
          <VCardText class="dialog-text text-center">
              Fordonsnumret är redan registrerat
          </VCardText>

          <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
              <VBtn class="btn-light" @click="showVehicleExistentDialog = false">
                  Stäng
              </VBtn>
          </VCardText>
      </VCard>
  </VDialog>

  <show 
    v-if="!isMobile"
    v-model:isDrawerOpen="isVehicleDetailDialog"
    :vehicle="selectedVehicle"/>

  <showMobile 
    v-else
    v-model:isDrawerOpen="isVehicleDetailDialog"
    :vehicle="selectedVehicle"/>
  </section>
</template>

<style lang="scss">
  .card-form {
    .v-list {
      padding: 28px 24px 40px !important;

      .v-list-item {
        margin-bottom: 0px;
        padding: 4px 0 !important;
        gap: 0px !important;

        .v-input--density-compact {
          --v-input-control-height: 48px !important;
        }

        .v-select .v-field,
        .v-autocomplete .v-field {

          .v-select__selection, .v-autocomplete__selection {
            align-items: center;
          }

          .v-field__input > input {
            top: 0px;
            left: 0px;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }

        .selector-user {
          .v-input__control {
            background: white !important;
            padding-top: 0 !important;
          }
          .v-input__prepend, .v-input__append {
            padding-top: 12px !important;
          }
        }

        .v-text-field {
          .v-input__control {
            padding-top: 0;
            input {
              min-height: 48px;
              padding: 12px 16px;
            }
          }
        }
      }
    }
    & .v-input {
      .v-input__prepend {
        padding-top: 12px !important;
      }
      & .v-input__control {
        .v-field {
          background-color: #f6f6f6;
          min-height: 48px !important;

          .v-text-field__suffix {
            padding: 12px 16px !important;
          }

          .v-field__input {
            min-height: 48px !important;
            padding: 12px 16px !important;

            input {
                min-height: 48px !important;
            }
          }

          .v-field-label {
            top: 12px !important;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }
      }
    }
  }

  .dialog-bottom-full-width {
    .v-card {
      border-radius: 24px 24px 0 0 !important;
    }
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: stock
</route>