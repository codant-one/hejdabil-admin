<script setup>

import { formatNumber } from '@/@core/utils/formatters'
import { useSuppliersStores } from '@/stores/useSuppliers'

const { width: windowWidth } = useWindowSize();

const route = useRoute()
const suppliersStores = useSuppliersStores()
const exporteraMobile = ref(false);
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalBillings = ref(0)
const isRequestOngoing = ref(true)
const state_id = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const props = defineProps({
  addresses: {
    type: Object,
    required: false
  },
  customerData: {
    type: Object,
    required: false,
  },
  isSupplier: {
    type: Boolean,
    required: true,
  }
})

const emit = defineEmits([
  'submit',
  'delete',
  'alert',
  'updateBalance'
])

const show = ref([
  true,
  false,
  false,
])

const isEditAddressDialogVisible = ref(false)
const selectedAddress = ref({})
const billings = ref([])

const selectedBillingForAction = ref({});
const isMobileActionDialogVisible = ref(false);

const accountTypes = [
  {
    icon: {
      icon: 'mdi-cash-multiple',
      size: '40',
    },
    title: 'Cuenta Corriente',
    value: '1',
  },
  {
    icon: {
      icon: 'tabler-pig-money',
      size: '40',
    },
    title: 'Cuenta de Ahorros',
    value: '2',
  }
]

const type_account = ref('1')
const document = ref(null)
const icon_type = ref(null)

// 👉 Computing pagination data
const paginationData = computed(() => {
  // const firstIndex = billings.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  // const lastIndex = billings.value.length + (currentPage.value - 1) * rowPerPage.value
  
  return `${totalBillings.value} resultat`;

  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalSuppliers.value } register`
})

watch(() =>  
  props.addresses, (addreses_) => {
    addresses_.value = addreses_
  });

watchEffect(() => {
  if (!isEditAddressDialogVisible.value)
    selectedAddress.value = {}
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

  if(Number(route.params.id)) {
    if(props.isSupplier && props.customerData.id !== null) {
      billings.value =[
        {
          id: 1,
          name: "Maria Test Cast",
          start_date: '2024-01-01',
          end_date: '2024-01-31',
          amount: 30,
            state_id: 7,
            state_name: 'Betald'
        },
        {
          id: 2,
          name: "Maria Test Cast",
          start_date: '2024-02-01',
          end_date: '2024-02-29',
          amount: 30,
          state_id: 7,
          state_name: 'Betald'
        },
        {
          id: 3,
          name: "Maria Test Cast",
          start_date: '2024-03-01',
          end_date: '2024-03-31',
          amount: 30,
          state_id: 7,
          state_name: 'Betald'
        },
        {
          id: 4,
          name: "Maria Test Cast",
          start_date: '2024-04-01',
          end_date: '2024-04-30',
          amount: 30,
          state_id: 4,        
          state_name: 'Obetald'
        },
        {
          id: 5,
          name: "Maria Test Cast",
          start_date: '2024-05-01',
          end_date: '2024-05-31',
          amount: 30,
          state_id: 4,
          state_name: 'Obetald'
        }
      ]

      totalPages.value = 1;//billings.value.length
      totalBillings.value = billings.value.length

      isRequestOngoing.value = false
    }
  }
}

const showDeleteDialog = addressData => {
  emit('delete', addressData)
}

const onSubmit = (address, method) => {
  emit('submit', address, method)
}

const resolveStatus = state_id => {
  if (state_id === 7)
    return { class: 'success' }
  if (state_id === 4)
    return { class: 'error' }
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

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
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

      <div class="d-flex">
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
      </div>

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
          <th scope="col"> Namn</th>
          <th scope="col" class="text-center"> Startdatum </th>
          <th scope="col" class="text-center"> Förfallodatum </th>
          <th scope="col" class="text-center"> Belopp </th>
          <th scope="col" class="text-center"> Status </th>
          <th scope="col"></th>
        </tr>
      </thead>
      <!-- 👉 table body -->
      <tbody>
        <tr 
            v-for="billing in billings"
            :key="billing.id"
            style="height: 3rem;">

            <td> {{ billing.name }} </td>
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
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;">
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>

                <VList>
                  <VListItem 
                    v-if="billing.state_id === 4"
                    @click="">
                    <template #prepend>
                      <VIcon icon="custom-bribery" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Markera som betald</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if="billing.state_id === 7"
                      @click="">
                    <template #prepend>
                      <VIcon icon="custom-money-transfer" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Markera som obetald</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    @click="">
                    <template #prepend>
                      <VIcon icon="custom-pdf" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa som PDF</VListItemTitle>
                  </VListItem>
                  <VListItem
                    @click="">
                    <template #prepend>
                      <VIcon icon="custom-download" />
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    @click="">
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
      v-if="!isRequestOngoing && !billings.length"
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
            class="mt-2"
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <div class="d-flex align-center w-100">
              <div class="d-flex flex-column gap-1">
                <span class="text-aqua">
                  {{ billing.name }}
                </span>
                <span class="text-neutral-3">
                  <div
                    class="status-chip pb-2"
                    :class="`status-chip-${resolveStatus(billing.state_id)?.class}`"
                  >
                    {{ billing.state_name }}
                  </div>
                </span>
              </div>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Startdatum:</div>
                <div class="expansion-panel-item-value">
                  {{ billing.start_date ?? "---" }}
                </div>
              </div>
            </div>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Förfallodatum:</div>
                <div class="expansion-panel-item-value">
                  {{ billing.end_date ?? "---" }}
                </div>
              </div>
            </div>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Belopp:</div>
                <div class="expansion-panel-item-value">
                  {{ formatNumber(billing.amount) ?? "0,00" }} kr
                </div>
              </div>
            </div>
            <div class="d-flex gap-4">
              <VBtn class="btn-light flex-1"
                v-if="billing.state_id === 4"
                @click=""
              >
                <VIcon icon="custom-bribery" size="24" />
                Markera som betald
              </VBtn>

              <VBtn class="btn-light flex-1"
                v-if="billing.state_id === 7"
                @click=""
              >
                <VIcon icon="custom-money-transfer" size="24" />
                Markera som obetald
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
        <VListItem @click="downloadPDF">
          <VListItemTitle>Exportera PDF</VListItemTitle>
        </VListItem>

        <VListItem @click="downloadCSV">
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
              @click="isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-pdf" size="24" class="mr-2" />
            </template>
            <VListItemTitle>Visa som PDF</VListItemTitle>
          </VListItem>
          <VListItem 
            @click="isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-download" size="24" class="mr-2" />
            </template>
            <VListItemTitle>Ladda ner</VListItemTitle>
          </VListItem>
          <VListItem 
            @click="isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
</template>

<style>
  .iconsButton .v-btn--icon.v-btn--density-default {
    width: calc(var(--v-btn-height) + 0px) !important;
    height: 25px !important;
  }
  .facturing.v-card--variant-elevated {
      box-shadow: none !important;
  }
</style>
