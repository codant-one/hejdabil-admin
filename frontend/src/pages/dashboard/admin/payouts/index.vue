<script setup>

import { useDisplay } from "vuetify";
import { useRoute } from 'vue-router'
import { useClientsStores } from '@/stores/useClients'
import { usePayoutsStores } from '@/stores/usePayouts'
import { useConfigsStores } from '@/stores/useConfigs'
import { excelParser } from "@/plugins/csv/excelParser";
import { themeConfig } from '@themeConfig'
import { formatDate, formatDateTime, formatDateYMD } from '@/@core/utils/formatters'
import { useAuthStores } from '@/stores/useAuth'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { formatNumber } from '@/@core/utils/formatters'
import { requiredValidator, emailValidator } from '@/@core/utils/validators'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js'
import AddNewPayoutDialog from './AddNewPayoutDialog.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import ExportDateMenu from '@/components/common/ExportDateMenu.vue'
import html2canvas from 'html2canvas';
import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";

import billogg from "@/assets/images/billogg_img.svg";
import swish from "@/assets/images/swish_img.svg";

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const sectionEl = ref(null);
const hasLoaded = ref(false);

const authStores = useAuthStores()
const clientsStores = useClientsStores()
const payoutsStores = usePayoutsStores()
const configsStores = useConfigsStores()
const ability = useAppAbility()
const emitter = inject("emitter")
const route = useRoute()

const payouts = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalPayouts = ref(0)
const isRequestOngoing = ref(true)
const isAddNewPayoutDrawerVisible = ref(false)
const isPayoutDetailDialogVisible = ref(false)
const isPayoutDetailMobileDialogVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmCancelDialogVisible = ref(false)
const selectedPayout = ref({})
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);
const err = ref(null);

const clients = ref([])
const isConfirmSendPayoutDialogVisible = ref(false)
const sendPayoutEmail = ref('')
const sendPayoutForm = ref(null)
const payoutToSend = ref(null)
const selectedClientId = ref(null)

const suppliers = ref([]);
const supplier_id = ref(null);
const state_id = ref(null);
const filtreraMobile = ref(false);
const isFilterDialogVisible = ref(false);

const selectedPayoutForAction = ref({});
const isMobileActionDialogVisible = ref(false);
const userData = ref(null)
const role = ref(null)
const company = ref({})
const payer_alias = ref(null)
const newlyCreatedPayout = ref(null)
const payoutReceiptRef = ref(null)
const payoutReceiptMobileRef = ref(null)
const date = ref(null)
const selectedExportType = ref(null)
const isExportTypeMenuVisible = ref(false)
const isExportMenuVisible = ref(false)
const isExportingFile = ref(false)
const lastExportSelectionKey = ref(null)

const exporteraMobile = ref(false);

const advisor = ref({
  type: '',
  message: '',
  show: false
})

watch(isExportMenuVisible, isVisible => {
  if (isVisible)
    lastExportSelectionKey.value = null
})

// 👉 Open payout detail when payout_id query param is present
watch(() => route.query.payout_id, async (payoutId) => {
  if (payoutId && hasLoaded.value) {
    const id = parseInt(payoutId)
    let payout = payouts.value.find(p => p.id === id)

    if (!payout) {
      try {
        payout = await payoutsStores.showPayout(id)
      } catch (error) {
        console.error('Payout not found:', error)
        advisor.value = {
          type: 'error',
          message: 'Utbetalningen kunde inte hittas.',
          show: true
        }
        setTimeout(() => { advisor.value.show = false }, 3000)
        return
      }
    }

    if (payout) {
      seePayout(payout, windowWidth.value < 1024)
    }
  }
}, { immediate: true })

watch(hasLoaded, async (loaded) => {
  if (loaded && route.query.payout_id) {
    const id = parseInt(route.query.payout_id)
    let payout = payouts.value.find(p => p.id === id)

    if (!payout) {
      try {
        payout = await payoutsStores.showPayout(id)
      } catch (error) {
        console.error('Payout not found:', error)
        advisor.value = {
          type: 'error',
          message: 'Utbetalningen kunde inte hittas.',
          show: true
        }
        setTimeout(() => { advisor.value.show = false }, 3000)
        return
      }
    }

    if (payout) {
      seePayout(payout, windowWidth.value < 1024)
    }
  }
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = payouts.value.length 
    ? (currentPage.value - 1) * rowPerPage.value + 1 
    : 0
  const 
  lastIndex = payouts.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalPayouts.value} resultat`;
 //return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalPayouts.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewPayoutDrawerVisible.value)
        selectedPayout.value = {}
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
    supplier_id.value = null;
    state_id.value = null;
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value,
    state_id: payoutsStores.getStateId ?? state_id.value,
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true
  isFilterDialogVisible.value = false;

  await payoutsStores.fetchPayouts(data)

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  const { user_data, userAbilities } = await authStores.me(userData.value)

  localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

  ability.update(userAbilities)

  localStorage.setItem('user_data', JSON.stringify(user_data))

  if (role.value === 'Supplier') {
    company.value = user_data.user_detail
    company.value.email = user_data.email
    company.value.billings = user_data.supplier.billings
    company.value.name = user_data.name
    company.value.last_name = user_data.last_name
  } else if (role.value === 'User') {
    company.value = user_data.supplier.boss.user.user_detail
    company.value.email = user_data.supplier.boss.user.email
    company.value.billings = user_data.supplier.boss.billings
    company.value.name = user_data.supplier.boss.user.name
    company.value.last_name = user_data.supplier.boss.user.last_name
  } else {
    await configsStores.getFeature('company')
    await configsStores.getFeature('logo')

    company.value = configsStores.getFeaturedConfig('company')
    company.value.logo = configsStores.getFeaturedConfig('logo').logo
  }

  if(role.value === 'Supplier') {
    payer_alias.value = user_data.supplier.payout_number
  } else if (role.value === 'User') {
    payer_alias.value = user_data.supplier.boss.payout_number
  }

  // Ensure suppliers are loaded once we know the user's role
  await payoutsStores.info();

  if (role.value === 'SuperAdmin' || role.value === 'Administrator') {
    suppliers.value = payoutsStores.suppliers;
  }

  // Fetch clients for send document dialog
  await clientsStores.fetchClients({ limit: -1 })
  clients.value = clientsStores.getClients

  payouts.value = payoutsStores.getPayouts
  totalPages.value = payoutsStores.last_page
  totalPayouts.value = payoutsStores.payoutsTotalCount

  hasLoaded.value = true;
  isRequestOngoing.value = false
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const showDeleteDialog = payoutData => {
  isConfirmDeleteDialogVisible.value = true
  selectedPayout.value = { ...payoutData }
}

const showCancelDialog = payoutData => {
  isConfirmCancelDialogVisible.value = true
  selectedPayout.value = { ...payoutData }
}

const seePayout = (payoutData, isMobile = false) => {
  selectedPayout.value = { ...payoutData }

  if (isMobile) {
    isPayoutDetailMobileDialogVisible.value = true
  } else {
    isPayoutDetailDialogVisible.value = true
  }

  // Capturar imagen si el payout está PAID y no tiene imagen
  if (payoutData.payout_state_id === 4 && !payoutData.image) {
    nextTick(() => {
      setTimeout(() => {
        captureAndSaveReceipt(payoutData);
      }, 500);
    });
  }
}

const closePayoutDetailDialog = () => {
  isPayoutDetailDialogVisible.value = false
  isPayoutDetailMobileDialogVisible.value = false

  // Limpiar query param payout_id al cerrar
  if (route.query.payout_id) {
    const { payout_id, ...rest } = route.query
    history.replaceState(null, '', location.pathname + (Object.keys(rest).length ? '?' + new URLSearchParams(rest).toString() : ''))
  }

  // Retrasar el reset para evitar errores mientras el diálogo se cierra
  setTimeout(() => {
    selectedPayout.value = {}
  }, 300)
}

const editPayout = payoutData => {
  selectedPayout.value = { ...payoutData }
  isAddNewPayoutDrawerVisible.value = true
}

const cancelPayout = async () => {
  isConfirmCancelDialogVisible.value = false
  let res = await payoutsStores.cancelPayout(selectedPayout.value.id)
  selectedPayout.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Betalning avbruten!' : res.data.message,
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

const removePayout = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await payoutsStores.deletePayout(selectedPayout.value.id)
  selectedPayout.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Betalning raderad!' : res.data.message,
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

const showError = () => {
  inteSkapatsDialog.value = false;

  advisor.value.show = true;
  advisor.value.type = "error";
  const responseData = err.value?.response?.data;
  
  if (responseData?.message) {
    advisor.value.message = responseData.message;
  } else if (responseData?.errors) {
    advisor.value.message = Object.values(responseData.errors)
              .flat()
              .join("<br>");
  } else if (err.value && !err.value.success && err.value.message) {
    advisor.value.message = err.value.message;
  } else if (err.value?.message) {
    advisor.value.message = err.value.message;
  } else {
    advisor.value.message = "Ett serverfel uppstod. Försök igen.";
  }

  setTimeout(() => {
    advisor.value.show = false;
    advisor.value.type = "";
    advisor.value.message = "";
  }, 3000);

};

const showErrorMessage = (message) => {

  advisor.value.show = true;
  advisor.value.type = "error";
  advisor.value.message = message;

  setTimeout(() => {
    advisor.value.show = false;
    advisor.value.type = "";
    advisor.value.message = "";
  }, 3000);
}

const submitForm = async (payoutData) => {
  isRequestOngoing.value = true

  // Check if we're editing (selectedPayout has an id)
  if (selectedPayout.value && selectedPayout.value.id) {
    submitUpdate(payoutData.data, selectedPayout.value.id)
  } else {
    submitCreate(payoutData.data)
  }
}

const submitUpdate = (payoutData, payoutId) => {
  payoutData.payer_alias = payer_alias.value

  payoutsStores.updatePayout(payoutId, payoutData)
    .then((res) => {
        if (res.data.success) {
            skapatsDialog.value = true
            newlyCreatedPayout.value = res.data.data.payout
            fetchData()
        }

        isRequestOngoing.value = false
    })
    .catch((error) => {
      err.value = error
      inteSkapatsDialog.value = true
      isRequestOngoing.value = false

      fetchData()

      setTimeout(() => {
          advisor.value = {
              type: '',
              message: '',
              show: false
          }
      }, 3000)
    })
}

const submitCreate = payoutData => {
  payoutData.payer_alias = payer_alias.value

  payoutsStores.addPayout(payoutData)
    .then((res) => {
        if (res.data.success) {
            skapatsDialog.value = true;
            newlyCreatedPayout.value = res.data.data.payout
            fetchData()
        }

        isRequestOngoing.value = false
    })
    .catch((error) => {
      err.value = error;
      inteSkapatsDialog.value = true;
      isRequestOngoing.value = false

      fetchData()

      setTimeout(() => {
          advisor.value = {
              type: '',
              message: '',
              show: false
          }
      }, 3000)
    })
}

const openPayoutDialog = () => {  
  if (!payer_alias.value) {
    advisor.value = {
      type: 'error',
      message: 'Det går inte att skapa betalning: konfigurerat betalningsnummer saknas',
      show: true
    }
    setTimeout(() => {
      advisor.value = { type: '', message: '', show: false }
    }, 3000)
    return
  }
  
  isAddNewPayoutDrawerVisible.value = true
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
};

const updateStateId = (newStateId) => {
  // Si ya está seleccionado, desmarcarlo (poner null)
  if (state_id.value === newStateId) {
    newStateId = null;
  }

  payoutsStores.setStateId(newStateId);
  state_id.value = newStateId;
  filtreraMobile.value = false;
};

const resolveStatus = state_id => {
  if (state_id === 1)
    return { class: 'pending' }
  if (state_id === 2)
    return { class: 'info' }   
  if (state_id === 3)
    return { class: 'error' }
  if (state_id === 4)
    return { class: 'success' }
  if (state_id === 5)
    return { class: 'error' }
  if (state_id === 6)
    return { class: 'error' }   
}

const goToPayouts = () => {

  skapatsDialog.value = false;

  advisor.value = {
    type: 'success',
    message: 'Betalning genomförd!',
    show: true
  }

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)               

};

const viewReceipt = async () => {
  skapatsDialog.value = false;
  
  // Refresh data to ensure the new payout is in the list with updated state
  await fetchData();

  if (newlyCreatedPayout.value) {
    // Find the updated payout in the newly loaded list
    const updatedPayout = payouts.value.find(p => p.id === newlyCreatedPayout.value.id);
    
    if (updatedPayout) {
      selectedPayout.value = updatedPayout;
    } else {
      // If not found in the current list (due to pagination), use the original value
      selectedPayout.value = newlyCreatedPayout.value;
    }

    isPayoutDetailDialogVisible.value = windowWidth.value >= 1024 ? true : false
    isPayoutDetailMobileDialogVisible.value = windowWidth.value >= 1024 ? false : true

    // Capturar imagen del recibo después de que el dialog se muestre
    nextTick(() => {
      setTimeout(() => {
        captureAndSaveReceipt(selectedPayout.value);
      }, 500);
    });
  }
  
  advisor.value = {
    type: 'success',
    message: 'Betalning genomförd!',
    show: true
  }

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000);
};

const captureAndSaveReceipt = async (payout) => {
  // Solo capturar si el payout está en estado PAID (4) y no tiene imagen
  if (payout.payout_state_id !== 4 || payout.image) {
    return;
  }

  try {
    // Acceder al elemento DOM del VCard
    const receiptElement = windowWidth.value >= 1024 
      ? payoutReceiptRef.value?.$el || payoutReceiptRef.value
      : payoutReceiptMobileRef.value?.$el || payoutReceiptMobileRef.value;
    
    if (!receiptElement) {
      console.warn('Receipt element not found');
      return;
    }

    const canvas = await html2canvas(receiptElement, {
      scale: 2,
      useCORS: true,
      allowTaint: true,
      backgroundColor: '#ffffff',
      ignoreElements: (element) => {
        return element.hasAttribute('data-html2canvas-ignore');
      }
    });

    canvas.toBlob(async (blob) => {
      if (!blob) return;

      const formData = new FormData();
      formData.append('image', blob, `receipt_${payout.reference}.png`);

      try {
        await payoutsStores.saveReceiptImage(payout.id, formData);
        // Refresh to get updated payout with image
        await fetchData();
      } catch (error) {
        console.error('Error saving receipt image:', error);
      }
    }, 'image/png');

  } catch (error) {
    console.error('Error capturing receipt:', error);
  }
};

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(async () => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);

  state_id.value = payoutsStores.getStateId ?? state_id.value;
  updateStateId(state_id.value);

  await payoutsStores.info();

  if (role.value === "SuperAdmin" || role.value === "Administrator") {
    suppliers.value = payoutsStores.suppliers;
  }

});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});

const shareReceipt = (payout) => {
  payoutToSend.value = payout
  sendPayoutEmail.value = ''
  selectedClientId.value = null
  isConfirmSendPayoutDialogVisible.value = true

  isPayoutDetailDialogVisible.value = false
  isPayoutDetailMobileDialogVisible.value = false
}

const selectClient = (clientId) => {
  if (clientId) {
    const client = clients.value.find(c => c.id === clientId)
    if (client && client.email) {
      sendPayoutEmail.value = client.email
    }
  } else {
    // Clear email when no client is selected
    sendPayoutEmail.value = ''
    sendPayoutForm.value?.resetValidation()
  }
}

const handleSendPayout = () => {
  sendPayoutForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()
      formData.append('ids', payoutToSend.value.id)
      formData.append('email', sendPayoutEmail.value)

      isConfirmSendPayoutDialogVisible.value = false
      isRequestOngoing.value = true

      payoutsStores.sendPayout(formData)
        .then((res) => {
          if (res.data.success) {
            advisor.value = {
              type: 'success',
              message: 'Utbetalningen har skickats!',
              show: true
            }
          }
          isRequestOngoing.value = false
        })
        .catch((err) => {
          advisor.value = {
            type: 'error',
            message: err.message || 'Ett fel inträffade',
            show: true
          }
          isRequestOngoing.value = false
        })

      setTimeout(() => {
        sendPayoutEmail.value = ''
        payoutToSend.value = null
        selectedClientId.value = null
        advisor.value = {
          type: '',
          message: '',
          show: false
        }
      }, 3000)
    }
  })
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

const downloadCSV = async () => {
  exporteraMobile.value = false
  isRequestOngoing.value = true

  try {
    const data = {
      limit: -1,
      ...getDateRangePayload(),
      orderByField: 'id',
      orderBy: 'desc',
    }

    await payoutsStores.fetchPayouts(data)

    const includeSupplierColumn = role.value === 'SuperAdmin' || role.value === 'Administrator'

    const dataArray = payoutsStores.getPayouts.map(element => {
      const row = {
        Referens: element.message ?? '',
        Datum: formatDateTime(element.created_at),
        Personnummer: element.payee_ssn ?? '',
        Mobilnummer: `+${element.payee_alias ?? ''}`,
        Belopp: `${formatNumber(element.amount ?? 0)} kr`,
        Status: element.state?.name ?? '',
      }

      if (includeSupplierColumn) {
        row.Leverantör = element.supplier
          ? `${element.supplier.user.name} ${element.supplier.user.last_name ?? ''}`.trim()
          : ''
      }

      return row
    })

    excelParser().exportDataFromJSON(dataArray, 'payouts', 'csv')
  } finally {
    isRequestOngoing.value = false
  }
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
      limit: -1,
      ...getDateRangePayload(),
      orderByField: 'id',
      orderBy: 'desc'
    }

    await payoutsStores.fetchPayouts(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }

    const rows = payoutsStores.getPayouts.map(element => ({
      message: element.message,
      date: formatDateTime(element.created_at),
      payeeSSN: element.payee_ssn,
      payeeAlias: element.payee_alias,
      amount: formatNumber(element.amount ?? 0),
      supplier: element.supplier
        ? `${element.supplier.user.name} ${element.supplier.user.last_name ?? ''}`.trim()
        : '',
      state: element.state.name,
    }))

    const includeSupplierColumn = role.value === 'SuperAdmin' || role.value === 'Administrator'
    const columnWidth = includeSupplierColumn ? '14.28%' : '16.66%'

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'SWISH',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      <tr style="height: 48px;">
        <td style="width: ${columnWidth}; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.message)}</td>
        <td style="width: ${columnWidth}; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.date)}</td>
        <td style="width: ${columnWidth}; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.payeeSSN)}</td>
        <td style="width: ${columnWidth}; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">+${escapeHtml(item.payeeAlias)}</td>
        <td style="width: ${columnWidth}; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.amount)} kr</td>
        ${includeSupplierColumn ? `<td style="width: ${columnWidth}; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.supplier)}</td>` : ''}
        <td style="width: ${columnWidth}; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.state)}</td>
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
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 12px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Referens</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 12px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Datum</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 12px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Personnummer</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 12px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Mobilnummer</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 12px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Belopp</td>
                      ${includeSupplierColumn ? `<td style="text-align: center; width: ${columnWidth}; padding: 0 12px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Leverantör</td>` : ''}
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 12px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Status</td>
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
        filename: 'payouts.pdf',
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
  exporteraMobile.value = false
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
          <h2>Swish <span v-if="hasLoaded">({{ totalPayouts }})</span></h2>
        </div>

        <div class="d-flex gap-4">
          <VMenu 
            v-if="windowWidth >= 1024"
            v-model="isExportTypeMenuVisible">
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

          <ExportDateMenu
            v-model="date"
            v-model:menuVisible="isExportMenuVisible"
            :show-activator="false"
            :is-mobile="windowWidth < 1024"
            activator="#payout-export-button"
            @update:modelValue="onDatePickerUpdate"
          />
          
          <VBtn
            v-if="$can('create', 'payouts') && (role === 'Supplier' || role === 'User')"
            class="btn-gradient"
            block
            @click="openPayoutDialog"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny betalning
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-2'">
          <AppAutocomplete
            v-if="(role !== 'Supplier' && role !== 'User') && hasLoaded"
            prepend-icon="custom-profile"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="(item) => item.full_name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate"
          />
        </div>

        <VBtn
          v-if="(role !== 'Supplier' && role !== 'User') && hasLoaded"
          class="btn-white-2 px-3"
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
            <VListItem @click="updateStateId(4)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 4"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Slutförd</VListItemTitle>
            </VListItem>

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
              <VListItemTitle>Väntande</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(3)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 3"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Avbruten</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(5)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 5"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Misslyckad</VListItemTitle>
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
            density="compact"
            variant="outlined"
            :items="[10, 20, 30, 50]"/>
        </div>
      </VCardText>

      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="payouts.length"
        class="pt-2 px-4 pb-6 text-no-wrap"
        style="border-radius: 0 !important"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col" class="text-center"> Referens </th>
            <th scope="col" class="text-center"> Datum </th>            
            <th scope="col" class="text-center"> Personnummer </th>
            <th scope="col" class="text-center"> Mobilnummer </th>
            <th scope="col" class="text-center"> Belopp </th>
            <th scope="col" v-if="(role === 'SuperAdmin' || role === 'Administrator')"> Leverantör </th>
            <th scope="col" class="text-center"> Status </th>
            <th scope="col"> Skapad av </th>
            <th scope="col" v-if="$can('edit', 'payouts') || $can('delete', 'payouts')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody>
          <tr 
            v-for="payout in payouts"
            :key="payout.id"
            style="height: 3rem;">
            <td class="text-center"> {{ payout.message ?? ''}} </td>
            <td class="text-center"> {{ formatDateTime(payout.created_at) }}</td>
            <td class="text-center"> {{ payout.payee_ssn ?? ''}} </td>
            <td class="text-center"> +{{ payout.payee_alias ?? ''}} </td>
            <td class="text-center"> {{ formatNumber(payout.amount ?? 0) }} kr</td>
            <td style="width: 1%; white-space: nowrap" v-if="(role === 'SuperAdmin' || role === 'Administrator')">
              <span v-if="payout.supplier">
                {{ payout.supplier.user.name }}
                {{ payout.supplier.user.last_name ?? "" }}
              </span>
            </td>
            <!-- 😵 Statuses -->
            <td class="text-center text-wrap d-flex justify-center align-center">
              <div
                class="status-chip"
                :class="`status-chip-${resolveStatus(payout.state.id)?.class}`"
              >
                {{ payout.state.name }}
              </div>
            </td>
            <td style="width: 1%; white-space: nowrap">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  variant="outlined"
                  size="38"
                >
                  <VImg
                    v-if="payout.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + payout.user.avatar"
                  />
                  <PresetAvatarImage
                    v-else
                    :avatar-id="payout.user?.user_detail?.avatar_id"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ payout.user.name }} {{ payout.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="payout.user.email && payout.user.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(payout.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ payout.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ payout.user.email }}</span>
                  </span>
                </div>
              </div>
            </td>            
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'payouts') || $can('delete', 'payouts')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                    v-if="$can('view','payouts')"
                    @click="seePayout(payout, false)">
                    <template #prepend>
                      <VIcon icon="custom-eye" size="24" />
                    </template>
                    <VListItemTitle>Visa detaljer</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('view','payouts') && payout.state.id === 4 && (role === 'Supplier' || role === 'User')"
                    @click="shareReceipt(payout)">
                    <template #prepend>
                      <VIcon icon="custom-paper-plane" size="24" />
                    </template>
                    <VListItemTitle>Skicka betalningsbevis</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('view','payouts') && payout.state.id === 1 && (role === 'Supplier' || role === 'User')"
                    @click="editPayout(payout)">
                    <template #prepend>
                      <VIcon icon="custom-check-mark" size="24" />
                    </template>
                    <VListItemTitle>Bekräfta betalning</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('view','payouts') && payout.state.id === 1 && (role === 'Supplier' || role === 'User')"
                    @click="showCancelDialog(payout)">
                    <template #prepend>
                      <VIcon icon="custom-unavailable" size="24" />
                    </template>
                    <VListItemTitle>Avbryt</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','payouts') && (role === 'Supplier' || role === 'User')"
                    @click="showDeleteDialog(payout)"
                    class="d-none">
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
        v-if="!isRequestOngoing && hasLoaded && !payouts.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-payment"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga Swish-transaktioner än</div>
          <div class="empty-state-text">
            Här samlas historiken för alla betalningar som genomförs via Swish. 
            Kom igång med snabba och säkra transaktioner direkt i plattformen.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'payouts') && (role === 'Supplier' || role === 'User')"
          @click="openPayoutDialog"
        >
          Ny betalning
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="payouts.length && $vuetify.display.mdAndDown"
      >
        <VExpansionPanel v-for="payout in payouts" :key="payout.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >            
            <div class="order-title-box">
              <span class="title-panel">
                <strong>{{ payout.message ?? '' }}</strong>
              </span>
              <div class="gap-2 title-organization">
                <span>
                  {{ formatDateYMD(payout.created_at) }}
                </span>
                <VIcon size="16" icon="custom-clock" />
                <span>
                  {{ payout.created_at ? formatDate(payout.created_at, { hour: '2-digit', minute: '2-digit', hour12: false }) : ''}}
                </span>
              </div>
            </div>
            <span class="text-black w-100 text-end align-center me-4 font-weight-bold" style="font-size: 16px;">
                {{ formatNumber(payout.amount ?? 0) }} kr
            </span>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Namn:</div>
              <div class="expansion-panel-item-value">
                {{ payout.fullname }}
              </div>
            </div>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Personnummer:</div>
                <div class="expansion-panel-item-value">
                  {{ payout.payee_ssn ?? "" }}
                </div>
              </div>
              <div>
                <div class="expansion-panel-item-label">Mobilnummer:</div>
                <div class="expansion-panel-item-value">
                  +{{ payout.payee_alias ?? "" }}
                </div>
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Status:</div>
              <div class="expansion-panel-item-value">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(payout.state.id)?.class}`"
                >
                  {{ payout.state.name }}
                </div>
              </div>
            </div>
            <div class="mb-4 row-with-buttons">
              <VBtn
                class="btn-light"
                @click="selectedPayoutForAction = payout; isMobileActionDialogVisible = true"
              >
                Åtgärder
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="payouts.length"
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

    <!-- Payout Detail Dialog -->
    <VDialog
      :model-value="isPayoutDetailDialogVisible"
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="closePayoutDetailDialog"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard ref="payoutReceiptRef" class="payout-receipt-card">
        <VCardText class="dialog-title-box" data-html2canvas-ignore="true">
          <div class="dialog-title">
            Detaljer
          </div>
        </VCardText>

        <div class="dialog-scroll-content">
          <VCardText class="dialog-text pa-4" v-if="selectedPayout.state">
            <div class="bg-alert">
              <div class="d-flex justify-between" data-html2canvas-ignore="true">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(selectedPayout.state.id)?.class}`"
                >
                  {{ selectedPayout.state.name }}
                </div>

                <div v-if="selectedPayout.payout_state_id === 4 && (role === 'Supplier' || role === 'User')" class="d-flex gap-2">
                  <VBtn
                    v-if="selectedPayout.payout_state_id === 4"
                    class="btn-light"
                    style="height: 40px !important;"
                    @click="shareReceipt(selectedPayout)"
                  >
                    <VIcon icon="custom-forward" size="24" />
                    Dela kvitto
                  </VBtn>                
                </div>
              </div>
              <VCardText class="big-icon justify-center d-flex flex-column align-center gap-3">
                <VIcon v-if="selectedPayout.payout_state_id === 4" size="96" icon="custom-f-checkmark" />
                <VIcon v-if="selectedPayout.payout_state_id === 1" size="96" icon="custom-f-info" />
                <VIcon v-if="selectedPayout.payout_state_id === 3 || selectedPayout.payout_state_id === 5" size="96" icon="custom-f-cancel" />
                <span class="text-amount">{{ formatNumber(selectedPayout.amount) }} kr</span>
                <div class="d-flex gap-2 title-organization justify-center align-center">
                    <span class="text-date-swish">
                      {{ formatDateYMD(selectedPayout.created_at) }}
                    </span>
                    <VIcon size="16" icon="custom-clock" />
                    <span class="text-date-swish">
                      {{ selectedPayout.created_at ? formatDate(selectedPayout.created_at, { hour: '2-digit', minute: '2-digit', hour12: false }) : ''}}
                    </span>
                </div>
                <span class="text-reference">{{ selectedPayout.reference }}</span>
              </VCardText>
            </div>      
          </VCardText>

          <VCardText class="dialog-text">
            <span class="mb-2 d-flex justify-between text-neutral-3">
              Namn: <strong class="text-black">{{ selectedPayout.fullname }}</strong>
            </span>
            <VDivider />
            <span class="mb-2 d-flex justify-between mt-2 text-neutral-3">
              Mobilnummer: <strong class="text-black">+{{ selectedPayout.payee_alias }}</strong>
            </span>
            <VDivider />
            <span class="mb-2 d-flex justify-between mt-2 text-neutral-3">
              Personnummer: <strong class="text-black">{{ selectedPayout.payee_ssn }}</strong>
            </span>
            <VDivider v-if="selectedPayout.message" class="mb-2"/>
            <span v-if="selectedPayout.message">
              Meddelande: <br> <strong class="text-black">{{ selectedPayout.message }}</strong>
            </span>
            <VDivider v-if="selectedPayout.error_message" class="mb-2"/>
            <span v-if="selectedPayout.error_message">
              Felinformation: <br> <strong class="text-black">{{ selectedPayout.error_message }} ({{ selectedPayout.error_code }})</strong>
            </span>
          </VCardText>

          <VCardText class="dialog-text my-4 pa-4 d-flex justify-center align-center gap-4">
            <img 
              :src="billogg" 
              alt="Billogg image"
            />
            <VDivider vertical />
            <img 
              :src="swish" 
              alt="Swish image"
            />
          </VCardText>
        </div>
      </VCard>
    </VDialog>

    <!-- 👉 Payout Detail Dialog mobile -->
    <VDialog
        v-model="isPayoutDetailMobileDialogVisible"
        fullscreen
        persistent
        :scrim="false"
        transition="dialog-bottom-transition"
        class="action-dialog dialog-fullscreen">
       <VCard ref="payoutReceiptMobileRef" class="payout-receipt-card">
        <VCardText 
          class="dialog-title-box px-4 pb-0 flex-row" 
          :style="selectedPayout.payout_state_id !== 5 ? 'height: 30px !important;' : 'height: 50px !important;'"
          data-html2canvas-ignore="true">
          <div class="dialog-title d-flex justify-between w-100">
            <VBtn
              class="btn-light"
              @click="closePayoutDetailDialog"
            >
              <VIcon icon="custom-return" size="24" />
              Gå ut
            </VBtn>
            <div v-if="selectedPayout.payout_state_id === 4 && (role === 'Supplier' || role === 'User')" class="d-flex gap-2">
              <VBtn
                v-if="selectedPayout.payout_state_id === 4"
                class="btn-light"
                @click="shareReceipt(selectedPayout)"
              >
                <VIcon icon="custom-forward" size="24" />
                Dela kvitto
              </VBtn>
            </div>
          </div>
        </VCardText>

        <VCardText class="dialog-text pa-4" v-if="selectedPayout.state">
          <div class="bg-alert">
            <div
              class="status-chip"
              :class="`status-chip-${resolveStatus(selectedPayout.state.id)?.class}`"
            >
              {{ selectedPayout.state.name }}
            </div>
            <VCardText class="big-icon justify-center d-flex flex-column align-center gap-3 py-0">
              <VIcon v-if="selectedPayout.payout_state_id === 4" size="96" icon="custom-f-checkmark" />
              <VIcon v-if="selectedPayout.payout_state_id === 1" size="96" icon="custom-f-info" />
              <VIcon v-if="selectedPayout.payout_state_id === 3 || selectedPayout.payout_state_id === 5" size="96" icon="custom-f-cancel" />
              <span class="text-amount">{{ formatNumber(selectedPayout.amount) }} kr</span>
              <div class="d-flex gap-2 title-organization justify-center align-center">
                  <span class="text-date-swish">
                    {{ formatDateYMD(selectedPayout.created_at) }}
                  </span>
                  <VIcon size="16" icon="custom-clock" />
                  <span class="text-date-swish">
                    {{ selectedPayout.created_at ? formatDate(selectedPayout.created_at, { hour: '2-digit', minute: '2-digit', hour12: false }) : ''}}
                  </span>
              </div>
              <span class="text-reference">{{ selectedPayout.reference }}</span>
            </VCardText>
          </div>      
        </VCardText>

        <VCardText class="dialog-text">
          <span class="mb-2 d-flex justify-between text-neutral-3">
            Namn: <strong class="text-black">{{ selectedPayout.fullname }}</strong>
          </span>
          <VDivider />
          <span class="mb-2 d-flex justify-between mt-2 text-neutral-3">
            Mobilnummer: <strong class="text-black">+{{ selectedPayout.payee_alias }}</strong>
          </span>
          <VDivider />
          <span class="mb-2 d-flex justify-between mt-2 text-neutral-3">
            Personnummer: <strong class="text-black">{{ selectedPayout.payee_ssn }}</strong>
          </span>
          <VDivider v-if="selectedPayout.message" class="mb-2"/>
          <span v-if="selectedPayout.message">
            Meddelande: <br> <strong class="text-black">{{ selectedPayout.message }}</strong>
          </span>
          <VDivider v-if="selectedPayout.error_message" class="mb-2"/>
          <span v-if="selectedPayout.error_message">
            Felinformation: <br> <strong class="text-black">{{ selectedPayout.error_message }} ({{ selectedPayout.error_code }})</strong>
          </span>
        </VCardText>

        <VCardText class="dialog-text pa-4 d-flex justify-center align-center gap-4" style="height: 32px !important;">
          <img 
            :src="billogg" 
            alt="Billogg image"
          />
          <VDivider vertical class="my-auto"/>
          <img 
            :src="swish"
            alt="Swish image"
          />
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Send Payout Dialog -->
    <VDialog
      v-model="isConfirmSendPayoutDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmSendPayoutDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VForm
        ref="sendPayoutForm"
        @submit.prevent="handleSendPayout"
      >
        <VCard>
          <VCardText class="dialog-title-box">
            <VIcon size="32" icon="custom-paper-plane" class="action-icon" />
            <div class="dialog-title">
              Skicka PDF via e-post
            </div>
          </VCardText>
          <VCardText class="dialog-text pb-0">
            <AppAutocomplete
              prepend-icon="custom-profile"
              v-model="selectedClientId"
              :items="clients"
              :item-title="item => item.fullname"
              :item-value="item => item.id"
              placeholder="Kunder"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              class="selector-user selector-truncate w-auto"
              @update:modelValue="selectClient"
            />
          </VCardText>
          <VCardText class="dialog-text card-form">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
              <VTextField
              v-model="sendPayoutEmail"
              placeholder="Ange mottagarens e-postadress"
              :rules="[requiredValidator, emailValidator]"
            />
          </VCardText>
          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn
              class="btn-light"
              @click="isConfirmSendPayoutDialogVisible = false"
            >
              Avbryt
            </VBtn>
            <VBtn class="btn-gradient" type="submit">
              Skicka
            </VBtn>
          </VCardText>
        </VCard>
      </VForm>
    </VDialog>

    <!-- 👉 Add New Payout -->
    <AddNewPayoutDialog
      v-if="payer_alias"
      v-model:isDialogOpen="isAddNewPayoutDrawerVisible"
      :payout-data="selectedPayout"
      @show-loading="isRequestOngoing = $event"
      @show-error="showErrorMessage"
      @payout-data="submitForm"
    />

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
            Ta bort betalningar
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker att du vill ta bort betalningen <strong>{{ selectedPayout.reference }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removePayout">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm Cancel -->
    <VDialog
      v-model="isConfirmCancelDialogVisible"
      persistent
      class="action-dialog" >

      <!-- Dialog close btn -->
      <VBtn
      icon
        class="btn-white close-btn"
        @click="isConfirmCancelDialogVisible = !isConfirmCancelDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-unavailable" class="action-icon" />
          <div class="dialog-title">
            Avbryt betalningar
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill avbryta betalningen <strong>{{ selectedPayout.message }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmCancelDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="cancelPayout">
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
            v-if="$can('view', 'payouts')"
            @click="seePayout(selectedPayoutForAction, true); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-eye" size="24" />
            </template>
            <VListItemTitle>Visa detaljer</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view','payouts') && selectedPayoutForAction.state.id === 4 && (role === 'Supplier' || role === 'User')"
            @click="shareReceipt(selectedPayoutForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-paper-plane" size="24" />
            </template>
            <VListItemTitle>Skicka betalningsbevis</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view','payouts') && selectedPayoutForAction.state.id === 1 && (role === 'Supplier' || role === 'User')"
            @click="editPayout(selectedPayoutForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-check-mark" size="24" />
            </template>
            <VListItemTitle>Bekräfta betalning</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view','payouts') && selectedPayoutForAction.state.id === 1 && (role === 'Supplier' || role === 'User')"
            @click="showCancelDialog(selectedPayoutForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-unavailable" size="24" />
            </template>
            <VListItemTitle>Avbryt</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete', 'payouts') && (role === 'Supplier' || role === 'User')"
            class="d-none"
            @click="showDeleteDialog(selectedPayoutForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Dialogs Section -->
    <VDialog
      v-model="skapatsDialog"
      persistent
      class="action-dialog dialog-big-icon"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="skapatsDialog = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-checkmark" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title">Betalningen lyckades!</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Din betalning via Swich har genomförts och skickats till mottagaren. Du hittar kvittot i din betalningshistorik.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="viewReceipt">
            Visa kvitto
          </VBtn>
          <VBtn 
            class="btn-gradient"
            :class="windowWidth < 1024 ? '' : 'w-30'" 
            @click="goToPayouts"
          > Klar 
        </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="inteSkapatsDialog"
      persistent
      class="action-dialog dialog-big-icon"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="inteSkapatsDialog = !inteSkapatsDialog"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-cancel" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title">Betalningen har inte genomförts!</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Din betalning via Swich har inte behandlats korrekt, försök igen.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="showError">
            Stäng
          </VBtn>
        </VCardText>
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
            v-if="(role !== 'Supplier' && role !== 'User')"
            prepend-icon="custom-profile"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="(item) => item.full_name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate mb-3"
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

    <!-- 👉 Mobile Filter Dialog -->
    <VDialog
      v-model="filtreraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem @click="updateStateId(4)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 4"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Slutförd</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(1)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                 :model-value="state_id === 1"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Väntande</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(3)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 3"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Avbruten</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(5)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 5"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Misslyckad</VListItemTitle>
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
          <VListItem @click="openExportDateMenu('pdf')">
            <VListItemTitle>Exportera PDF</VListItemTitle>
          </VListItem>

          <VListItem @click="openExportDateMenu('excel')">
            <VListItemTitle>Exportera Excel</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>
<style>
  .dialog-scroll-content {
    overflow-y: auto;
    overflow-x: hidden;
  }

  .text-amount {
    font-weight: 600;
    font-size: 24px;
    line-height: 100%;
    letter-spacing: 0;
    color: #5D5D5D;
  }

  .text-date-swish {
    font-weight: 400;
    font-size: 12px;
    line-height: 16px;
    letter-spacing: 0;
    color: #878787;   
  }

  .text-reference {
    font-weight: 700;
    font-size: 14px;
    line-height: 16px;
    letter-spacing: 0;
    color: #878787;   
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: payouts
</route>