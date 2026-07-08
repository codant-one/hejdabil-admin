<script setup>

import { useDisplay } from "vuetify";
import { nextTick } from 'vue';
import { useMobilePaginationScroll } from '@/@core/composable/useMobilePaginationScroll';
import { requiredValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate';
import { avatarText } from '@/@core/utils/formatters'
import company from "@/assets/images/avatars/company.svg";
import html2pdf from 'html2pdf.js';
import router from '@/router'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";

const { width: windowWidth } = useWindowSize();
const suppliersStores = useSuppliersStores()
const emitter = inject("emitter")
const snackbarKey = ref(0); // Creamos un contador de renderizado
const exporteraMobile = ref(false);

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const suppliers = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalSuppliers = ref(0)
const isRequestOngoing = ref(true)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const isConfirmSwishDialogVisible = ref(false)
const selectedSupplier = ref({})
const deleteInfo = ref({
  can_force_delete: false,
  total_associations: 0,
  associations: {
    clients: 0,
    billings: 0,
    vehicles: 0,
    agreements: 0,
    payouts: 0,
    documents: 0,
    notes: 0,
  },
})
const hasLoaded = ref(false);
const isDeleteInfoLoading = ref(false)
const csr_url = ref(null)
const pem_url = ref(null)
const payout_number = ref(null)
const pemFile = ref([])
const is_payout = ref(false)
const swishStep = ref(1)
const state_id = ref(null)
const refForm = ref(null)
const isFormValid = ref(false)

const selectedSupplierForAction = ref({});
const isMobileActionDialogVisible = ref(false);

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const sectionEl = ref(null);

useMobilePaginationScroll({
  targetRef: sectionEl,
  currentPage,
  isRequestOngoing,
  enabled: mdAndDown,
});

const states = ref ([
  { id: 2, name: "Aktiv" },
  { id: 1, name: "Inaktiv" }
])

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = suppliers.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = suppliers.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalSuppliers.value} resultat`;

  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalSuppliers.value } register`
})

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

  await suppliersStores.fetchSuppliers(data)

  suppliers.value = suppliersStores.getSuppliers
  totalPages.value = suppliersStores.last_page
  totalSuppliers.value = suppliersStores.suppliersTotalCount

  isRequestOngoing.value = false
  hasLoaded.value = true;

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

const editSupplier = supplierData => {
  router.push({ name : 'dashboard-admin-suppliers-edit-id', params: { id: supplierData.id } })
}

const resendInvitation = async supplierData => {
  isRequestOngoing.value = true

  try {
    const res = await suppliersStores.resendInvitation(supplierData.id)

    advisor.value = {
      type: res.data.success ? 'success' : 'error',
      message: res.data.message ?? 'Inbjudan har skickats igen med ett nytt lösenord.',
      show: true,
    }
  } catch (err) {
    advisor.value = {
      type: 'error',
      message: err.response?.data?.message || err.message,
      show: true,
    }
  } finally {
    isRequestOngoing.value = false

    setTimeout(() => {
      advisor.value = {
        type: '',
        message: '',
        show: false,
      }
    }, 3000)
  }
}

const showDeleteDialog = async supplierData => {
  selectedSupplier.value = { ...supplierData }

  deleteInfo.value = {
    can_force_delete: false,
    total_associations: 0,
    associations: {
      clients: 0,
      billings: 0,
      vehicles: 0,
      agreements: 0,
      payouts: 0,
      documents: 0,
      notes: 0,
    },
  }

  isDeleteInfoLoading.value = true
  isRequestOngoing.value = true
  try {
    const res = await suppliersStores.getDeletionInfo(supplierData.id)
    deleteInfo.value = res.data?.data ?? deleteInfo.value
    isConfirmDeleteDialogVisible.value = true
  } catch (err) {
      advisor.value = {
        type: 'error',
        message: err.response?.data?.message || err.message,
        show: true,
      }

      setTimeout(() => {
        advisor.value = {
          type: '',
          message: '',
          show: false,
        }
      }, 3000)
  } finally {
    isDeleteInfoLoading.value = false
    isRequestOngoing.value = false
  }
}

const showSwishDialog = supplierData => {
  isConfirmSwishDialogVisible.value = true
  selectedSupplier.value = { ...supplierData }
  payout_number.value = supplierData.payout_number || null
  csr_url.value = supplierData.csr_url || null
  pem_url.value = supplierData.pem_url || null
  is_payout.value = supplierData.is_payout === 0 ? false : true
  pemFile.value = []
  swishStep.value = 1

  nextTick(() => {
    refForm.value?.resetValidation()
  })
}

const closeSwishDialog = () => {
  isConfirmSwishDialogVisible.value = false
  swishStep.value = 1
}

const swishHasSteps = computed(() => !!csr_url.value)

const pemFileRules = computed(() => {
  if (!swishHasSteps.value || !is_payout.value)
    return []

  return [
    value => {
      const hasNewFile = Array.isArray(value) && value.length > 0
      const hasExistingPem = !!pem_url.value
      return hasNewFile || hasExistingPem || 'Ladda upp en PEM-fil för att aktivera Swish-utbetalningar.'
    },
  ]
})

const openStorageFileUrl = filePath => {
  if (!filePath) return ''
  if (filePath.startsWith('http://') || filePath.startsWith('https://')) return filePath
  return `${themeConfig.settings.urlStorage}${filePath}`
}

const goToSwishStepTwo = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      swishStep.value = 2
    }
  })
}

const showActivateDialog = supplierData => {
  isConfirmActiveDialogVisible.value = true
  selectedSupplier.value = { ...supplierData }
}

const seeSupplier = supplierData => {
  router.push({ name : 'dashboard-admin-suppliers-id', params: { id: supplierData.id } })
}

const removeSupplier = async () => {
  isConfirmDeleteDialogVisible.value = false
  isRequestOngoing.value = true
  let res = await suppliersStores.deleteSupplier(selectedSupplier.value.id)
  selectedSupplier.value = {}

  isRequestOngoing.value = false
  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? (res.data.message ?? 'Leverantör borttagen!') : res.data.message,
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

const formatOrgNumber = () => {

    let numbers = payout_number.value.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    payout_number.value = numbers
}

const swish = () => {

  refForm.value?.validate().then(({ valid }) => {
    if(valid) {
      isConfirmSwishDialogVisible.value = false
      isRequestOngoing.value = true
      
      let formData = new FormData()
      formData.append('payout_number', payout_number.value)
      formData.append('is_payout', is_payout.value ? 1 : 0)
      if (pemFile.value && pemFile.value.length > 0) {
        formData.append('file', pemFile.value[0])
      }
      
      suppliersStores.swish(selectedSupplier.value.id, formData)
        .then(async (res) => {
            if (res.data.success) {
              selectedSupplier.value = {}
              payout_number.value = null
              is_payout.value = false
              pemFile.value = []
              
              await fetchData()

              advisor.value = {
                type: 'success',
                message: res.data.data.supplier.is_payout === 1 ? 'Swish aktiverad!' : 'Swish avaktiverad!',
                show: true
              }

              setTimeout(() => {
                advisor.value = {
                  type: '',
                  message: '',
                  show: false
                }
              }, 3000)

            }
            isRequestOngoing.value = false
        })
        .catch((err) => {

          advisor.value = {
            type: 'error',
            message: err.response?.data?.message || err.message,
            show: true
          }

          setTimeout(() => {
            advisor.value = {
              type: '',
              message: '',
              show: false
            }
          }, 3000)

          isRequestOngoing.value = false
        })
    }
  })
}

const activateSupplier = async () => {
  isConfirmActiveDialogVisible.value = false
  let res = await suppliersStores.activateSupplier(selectedSupplier.value.id)
  selectedSupplier.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Leverantör aktiverad!' : res.data.message,
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

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
};

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

    await suppliersStores.fetchSuppliers(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }

    const rows = suppliersStores.getSuppliers.map(element => ({
      id: element.id,
      fullname: element.user.name + ' ' + (element.user.last_name ?? ''),
      email: element.user.email,
      company: element.user.user_detail.company ?? "",
      swish: element.payout_number ?? "",
      phone: element.user.user_detail.phone ?? "",
      landline: element.user.user_detail.landline ?? "",
      sender: element.sms_sender ?? '',
      organizationNumber: element.user.user_detail.organization_number ?? "",
      clients: element.client_count,
      creator: (element.creator.name ?? '') + ' ' + (element.creator.last_name ?? ''),
      status: element.state.name
    }))

    //const includeSupplierColumn = role.value === 'SuperAdmin' || role.value === 'Administrator'
    const columnWidth = '18.4%'

    const { headerMarkup } = await buildPdfTopHeader({
      //company: company.value,
      title: 'Leverantörer',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      ${(() => {
        const companyLines = [item.company, item.organizationNumber].filter(Boolean)
        const companyMarkup = companyLines.map(line => escapeHtml(line)).join('<br />')

        const contactLines = [item.fullname, item.email].filter(Boolean)
        const contactMarkup = contactLines.map(line => escapeHtml(line)).join('<br />')

        const phoneLines = [item.phone, item.landline].filter(Boolean)
        const phoneMarkup = phoneLines.map(line => escapeHtml(line)).join('<br />')

        return `
      <tr style="height: 48px;">
        <td style="width: 8%; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.id)}</td>
        <td style="width: 23%; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${companyMarkup}</td>
        <td style="width: 23%; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${contactMarkup}</td>
        <td style="width: 15%; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.swish)}</td>
        <td style="width: 15%; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${phoneMarkup}</td>
        <td style="width: 15%; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.sender)}</td>
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
                      <td style="text-align: center; width: 8%; padding: 0 12px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Id</td>
                      <td style="text-align: center; width: 23%; padding: 0 12px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Företag</td>
                      <td style="text-align: center; width: 23%; padding: 0 12px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Kontakt</td>
                      <td style="text-align: center; width: 15%; padding: 0 12px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Swish</td>
                      <td style="text-align: center; width: 15%; padding: 0 12px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Mobilnummer/Telefon</td>
                      <td style="text-align: center; width: 15%; padding: 0 12px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Sender</td>
                      
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
        filename: 'suppliers.pdf',
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

  await suppliersStores.fetchSuppliers(data)

  let dataArray = [];
      
  suppliersStores.getSuppliers.forEach(element => {

    let data = {
      ID: element.id,
      KONTAKT: element.user.name + ' ' + (element.user.last_name ?? ''),
      E_POST: element.user.email,
      FÖRETAG: element.user.user_detail.company ?? '',
      ORGANISATIONSNUMMER: element.user.user_detail.organization_number ?? '',
      SWISH: element.payout_number ?? '',
      SENDER: element.sms_sender ?? '',
      REGISTRERADE_KUNDER:  element.client_count,
      SKAPAD_AV: (element.creator.name ?? '') + ' ' + (element.creator.last_name ?? ''),
      STATUS: element.state.name
    }

    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "suppliers", "csv");

  isRequestOngoing.value = false

}

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

  //Permite recibir notificaciones para ser mostradas en el VSnackbar
  var alertMessage = sessionStorage.getItem('alertMessage');
  if (alertMessage) {
    alertMessage = JSON.parse(alertMessage);
    advisor.value = {
      type: alertMessage.type,
      message: alertMessage.message,
      show: true
    }

    sessionStorage.removeItem('alertMessage');
  }
});

onUnmounted (() => {
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
          <h2>Leverantörer <span v-if="hasLoaded">({{ totalSuppliers }})</span></h2>
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
            v-if="$can('create', 'clients') && windowWidth >= 1024"
            class="btn-gradient"
            block
            :to="{ name: 'dashboard-admin-suppliers-add' }"
          >
            <VIcon icon="custom-plus" size="24" />
            Skapa leverantör
          </VBtn>

          <VBtn
            v-if="windowWidth < 1024 && $can('create', 'suppliers')"
            class="btn-gradient"
            block
            :to="{ name: 'dashboard-admin-suppliers-add' }"
          >
            <VIcon icon="custom-plus" size="24" />
            Skapa leverantör
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.mdAndDown ? 'p-6' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search" :style="windowWidth < 1024 ? '' : 'width: 480px !important'">
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
        v-show="suppliers.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col"> # ID </th>
            <th scope="col"> Företag </th>
            <th scope="col"> Kontakt </th>
            <th scope="col" class="text-center"> Status </th>
            <th scope="col" class="text-center"> Swish </th>
            <th scope="col" class="text-center"> Sender </th>
            <th scope="col" class="text-center"> # Kunder </th>
            <th scope="col"> Skapad Av </th>
            <th scope="col" v-if="$can('edit', 'suppliers') || $can('delete', 'suppliers')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody>
          <tr 
            v-for="supplier in suppliers"
            :key="supplier.id"
            style="height: 3rem;">

            <td> {{ supplier.id }} </td>
            <td style="width: 1%; white-space: nowrap">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  variant="outlined"
                  size="38"
                  class="supplier-company-logo-avatar"
                >
                  <VImg
                    v-if="supplier.user.user_detail.logo"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + supplier.user.user_detail.logo"
                  />
                  <VImg
                    v-else
                    style="border-radius: 50%"
                    :src="company"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium text-aqua">
                    {{ supplier.user.name }} {{ supplier.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="supplier.user.user_detail.organization_number && supplier.user.user_detail.organization_number.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          Org.nr {{ truncateText(supplier.user.user_detail.organization_number, 20) }}
                        </span>
                      </template>
                      <span>Org.nr: {{ supplier.user.user_detail.organization_number }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>Org.nr: {{ supplier.user.user_detail.organization_number }}</span>
                  </span>
                </div>
              </div>
            </td>
            <td style="width: 1%; white-space: nowrap">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  variant="outlined"
                  size="38"
                >
                  <VImg
                    v-if="supplier.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + supplier.user.avatar"
                  />
                  <PresetAvatarImage
                    v-else
                    :avatar-id="supplier.user?.user_detail?.avatar_id"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ supplier.user.name }} {{ supplier.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="supplier.user.email && supplier.user.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(supplier.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ supplier.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ supplier.user.email }}</span>
                  </span>
                </div>
              </div>
            </td>
            <td class="d-flex justify-center align-center"> 
              <div
                class="status-chip"
                :class="`status-chip-${resolveStatus(supplier.state.id)?.class}`"
              >
                {{ supplier.state.name }}
              </div>
            </td>
            <td class="text-center">
              <span v-if="supplier.is_payout === 1">
                {{ supplier.payout_number ?? '' }}
              </span>
            </td>
            <td class="text-center">
              {{ supplier.sms_sender ?? '' }}
            </td>
            <td class="text-center">
              {{ supplier.client_count }}
            </td>
            <td style="width: 1%; white-space: nowrap">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  variant="outlined"
                  size="38"
                >
                  <VImg
                    v-if="supplier.creator.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + supplier.creator.avatar"
                  />
                  <PresetAvatarImage
                    v-else
                    :avatar-id="supplier.creator?.user_detail?.avatar_id"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ supplier.creator.name }} {{ supplier.creator.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="supplier.creator.email && supplier.creator.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(supplier.creator.email, 20) }}
                        </span>
                      </template>
                      <span>{{ supplier.creator.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ supplier.creator.email }}</span>
                  </span>
                </div>
              </div>
            </td> 
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'suppliers') || $can('delete', 'suppliers')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>

                <VList>
                  <VListItem 
                    v-if="$can('view', 'suppliers')"
                    @click="seeSupplier(supplier)">
                    <template #prepend>
                      <VIcon icon="custom-eye" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if="$can('edit', 'suppliers') && supplier.state_id === 2"
                      @click="editSupplier(supplier)">
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('view', 'suppliers') && supplier.state_id !== 1 && supplier.user.full_profile === 1"
                    @click="showSwishDialog(supplier)">
                    <template #prepend>
                      <VIcon icon="custom-swish" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Swish</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'suppliers') && supplier.state_id === 2 && supplier.user.full_profile === 0"
                    @click="resendInvitation(supplier)">
                    <template #prepend>
                      <VIcon icon="tabler-mail-forward" />
                    </template>
                    <VListItemTitle>Skicka om inbjudan</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','suppliers') && supplier.state_id === 2"
                    @click="showDeleteDialog(supplier)">
                    <template #prepend>
                      <VIcon icon="custom-waste" size="24" />
                    </template>
                    <VListItemTitle>Ta bort</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('delete','suppliers') && supplier.state_id === 1"
                    @click="showActivateDialog(supplier)">
                    <template #prepend>
                      <VIcon icon="tabler-rosette-discount-check" />
                    </template>
                    <VListItemTitle>Aktivera</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </td>
          </tr>
        </tbody>
        <!-- 👉 table footer  -->
        <tfoot v-show="!suppliers.length">
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
        v-if="!isRequestOngoing && hasLoaded && !suppliers.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-supplier"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga leverantörer än</div>
          <div class="empty-state-text">
            Skapa din första leverantör för att hålla koll på varifrån dina fordon kommer.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'suppliers') && !$vuetify.display.mdAndDown"
          :to="{ name: 'dashboard-admin-suppliers-add' }"
        >
          Skapa leverantör
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'suppliers')"
          :to="{ name: 'dashboard-admin-suppliers-add' }"
        >
          Skapa leverantör
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="suppliers.length && $vuetify.display.mdAndDown"
      >
        <VExpansionPanel v-for="supplier in suppliers" :key="supplier.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <div class="d-flex align-center w-100">
              <VAvatar
                variant="outlined"
                size="32"
                class="me-3 supplier-company-logo-avatar"
                contain
              >
                <VImg
                  v-if="supplier.user.user_detail.logo"
                  style="border-radius: 50%"
                  :src="themeConfig.settings.urlStorage + supplier.user.user_detail.logo"
                  contain
                />
                <VImg
                  v-else
                  style="border-radius: 50%"
                  :src="company"
                  contain
                />
              </VAvatar>
              <div class="d-flex flex-column gap-1">
                <span class="text-aqua">
                  {{ supplier.user.user_detail.company }}
                </span>
                <span class="text-neutral-3">
                  Org.nr {{ supplier.user.user_detail.organization_number }}
                </span>
              </div>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Kontakt:</div>
              <div class="expansion-panel-item-value d-flex flex-column gap-2">
                <span>{{ supplier.user.name }} {{ supplier.user.last_name ?? '' }}</span>
                <span>{{ supplier.user.email }}</span>
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Status:</div>
              <div class="expansion-panel-item-value">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(supplier.state.id)?.class}`"
                >
                  {{ supplier.state.name }}
                </div>
              </div>
            </div>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Swish:</div>
                <div class="expansion-panel-item-value">
                  {{ supplier.payout_number ?? "---" }}
                </div>
              </div>
            </div>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Sender:</div>
                <div class="expansion-panel-item-value">
                  {{ supplier.sms_sender ?? "---" }}
                </div>
              </div>
            </div>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Kunder:</div>
                <div class="expansion-panel-item-value">
                  {{ supplier.client_count ?? "" }}
                </div>
              </div>
            </div>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Skapad Av:</div>
                <div class="expansion-panel-item-value">
                  {{ supplier.creator.name }} {{ supplier.creator.last_name ?? '' }} 
                </div>
              </div>
            </div>
            <div class="d-flex gap-4">
              <VBtn class="btn-light flex-1" @click="seeSupplier(supplier)"
              >
                <VIcon icon="custom-eye" size="24" />
                Se detaljer
              </VBtn>
              
              <VBtn class="btn-light" icon @click="selectedSupplierForAction = supplier; isMobileActionDialogVisible = true">
                <VIcon icon="custom-dots-vertical" size="24" />
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>
    
      <VCardText
        v-if="suppliers.length"
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
            Ta bort leverantör
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Är du säker att du vill ta bort leverantör <strong>{{ selectedSupplier.user?.name }} {{ selectedSupplier.user?.last_name ?? '' }}</strong>?
        </VCardText>

        <VCardText class="dialog-text mt-2">
          Leverantören tas bort från aktiva register.
        </VCardText>

        <VCardText v-if="deleteInfo.total_associations > 0" class="dialog-text mt-4">
          <div>Associerade poster:</div>
          <div v-if="deleteInfo.associations?.clients">Kunder: {{ deleteInfo.associations.clients }}</div>
          <div v-if="deleteInfo.associations?.billings">Faktureringar: {{ deleteInfo.associations.billings }}</div>
          <div v-if="deleteInfo.associations?.vehicles">Fordon: {{ deleteInfo.associations.vehicles }}</div>
          <div v-if="deleteInfo.associations?.agreements">Avtal: {{ deleteInfo.associations.agreements }}</div>
          <div v-if="deleteInfo.associations?.payouts">Utbetalningar: {{ deleteInfo.associations.payouts }}</div>
          <div v-if="deleteInfo.associations?.documents">Dokument: {{ deleteInfo.associations.documents }}</div>
          <div v-if="deleteInfo.associations?.notes">Noteringar: {{ deleteInfo.associations.notes }}</div>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmDeleteDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeSupplier"> Ja, radera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm activate swish -->
    <VDialog
      v-model="isConfirmSwishDialogVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
        
      <VBtn
        icon
        class="btn-white close-btn"
        @click="closeSwishDialog"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-swish" class="action-icon" />
          <div class="dialog-title">
            Swish
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Swish för leverantören <strong>{{ selectedSupplier.user?.name }} {{ selectedSupplier.user?.last_name ?? '' }}</strong>
        </VCardText>
        
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="swish">
          <template v-if="!swishHasSteps">
            <VCardText class="d-flex flex-column gap-2">
              <VTextField
                v-model="payout_number"
                label="Utbetalningsnummer"
                :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                minLength="11"
                maxlength="11"
                @input="formatOrgNumber()"
              />
              <VCheckbox
                v-model="is_payout"
                label="Aktivera Swish utbetalningar"
              />
            </VCardText>

            <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
              <VBtn class="btn-light" @click="closeSwishDialog">
                Avbryt
              </VBtn>
              <VBtn class="btn-gradient" type="submit"> Acceptera </VBtn>
            </VCardText>
          </template>

          <template v-else>
            <VCardText class="pt-4 pb-0 d-flex align-center gap-2">
              <VChip :color="swishStep === 1 ? 'primary' : 'default'" variant="tonal" size="small">1</VChip>
              <VIcon icon="tabler-arrow-right" size="16" />
              <VChip :color="swishStep === 2 ? 'primary' : 'default'" variant="tonal" size="small">2</VChip>
            </VCardText>

            <VCardText v-if="swishStep === 1" class="d-flex flex-column gap-2">
              <VTextField
                v-model="payout_number"
                label="Utbetalningsnummer"
                :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                minLength="11"
                maxlength="11"
                @input="formatOrgNumber()"
              />
              <VCheckbox
                v-model="is_payout"
                label="Aktivera Swish utbetalningar"
              />
            </VCardText>

            <VCardText v-if="swishStep === 2" class="d-flex flex-column gap-3">
              <div>
                <div class="text-body-2 mb-1">CSR-fil</div>
                <VBtn
                  v-if="csr_url"
                  color="secondary"
                  variant="tonal"
                  :href="openStorageFileUrl(csr_url)"
                  target="_blank"
                >
                  Ladda ner CSR
                </VBtn>
                <div v-else class="text-disabled">Ingen CSR-fil tillgänglig.</div>
              </div>

              <div>
                <div class="text-body-2 mb-1">PEM-fil</div>
                <VBtn
                  v-if="pem_url"
                  color="secondary"
                  variant="tonal"
                  :href="openStorageFileUrl(pem_url)"
                  target="_blank"
                >
                  Ladda ner PEM
                </VBtn>
                <div v-else class="text-disabled">Ingen PEM-fil uppladdad än.</div>
              </div>

              <VFileInput
                v-model="pemFile"
                label="Ladda upp PEM-fil"
                accept=".pem"
                prepend-icon="tabler-file"
                :rules="pemFileRules"
              />
            </VCardText>

            <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
              <VBtn class="btn-ghost"
                v-if="swishStep === 2"
                @click="swishStep = 1"
              >
                <VIcon icon="custom-return" size="24" />
                Tillbaka
              </VBtn>

              <VBtn class="btn-light" @click="closeSwishDialog">
                Avbryt
              </VBtn>

              <VBtn v-if="swishStep === 1" 
                class="btn-gradient" 
                @click="goToSwishStepTwo"
              >
                Nästa
              </VBtn>

              <VBtn v-else
                class="btn-gradient" 
                type="submit"
              > 
                Spara 
              </VBtn>
            </VCardText>
          </template>
        </VForm>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm activate supplier -->
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
          <VIcon size="32" icon="tabler-rosette-discount-check" class="action-icon" />
          <div class="dialog-title">
            Aktivera leverantör
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Är du säker att du vill aktivera leverantören <strong>{{ selectedSupplier.user.name }} {{ selectedSupplier.user.last_name ?? '' }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmActiveDialogVisible = false">
            Avbryt
          </VBtn>

          <VBtn 
            class="btn-gradient" 
            @click="activateSupplier"
          >
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
              v-if="$can('edit', 'suppliers') && selectedSupplierForAction.state_id === 2"
              @click="editSupplier(selectedSupplierForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" class="mr-2" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem 
            v-if="$can('view', 'suppliers') && selectedSupplierForAction.state_id !== 1 && selectedSupplierForAction.user.full_profile === 1"
            @click="showSwishDialog(selectedSupplierForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-swish" size="24" class="mr-2" />
            </template>
            <VListItemTitle>Swish</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'suppliers') && selectedSupplierForAction.state_id === 2 && selectedSupplierForAction.user.full_profile === 0"
            @click="resendInvitation(selectedSupplierForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="tabler-mail-forward" />
            </template>
            <VListItemTitle>Skicka om inbjudan</VListItemTitle>
          </VListItem>
          <VListItem 
            v-if="$can('delete','suppliers') && selectedSupplierForAction.state_id === 2"
            @click="showDeleteDialog(selectedSupplierForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete','suppliers') && selectedSupplierForAction.state_id === 1"
            @click="showActivateDialog(selectedSupplierForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="tabler-rosette-discount-check" />
            </template>
            <VListItemTitle>Aktivera</VListItemTitle>
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
    subject: suppliers
</route>