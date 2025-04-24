<script setup>

import { useBillingsStores } from '@/stores/useBillings'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { formatNumber } from '@/@core/utils/formatters'
import router from '@/router'
import Toaster from "@/components/common/Toaster.vue";

const billingsStores = useBillingsStores()

const clients = ref([])
const suppliers = ref([])
const billings = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalBillings = ref(0)
const isRequestOngoing = ref(true)
const isConfirmStateDialogVisible = ref(false)
const isConfirmSendMailVisible = ref(false)
const emailDefault = ref(true)
const selectedTags = ref([])
const existingTags = ref([])
const isValid = ref(false)
const selectedBilling = ref({})

const supplier_id = ref(null)
const client_id = ref(null)
const state_id = ref(null)
const userData = ref(null)
const role = ref(null)
const totalSum = ref(0)
const totalTax = ref(0)
const totalNeto = ref(0)
const totalPending = ref(0)
const totalPaid = ref(0)
const totalExpired = ref(0)
const pendingTax = ref(0)
const paidTax = ref(0)
const expiredTax = ref(0)
const bgColor = ref(null)
const textColor = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = billings.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = billings.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Mostrando ${ firstIndex } hasta ${ lastIndex } de ${ totalBillings.value } registros`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

watchEffect(fetchData)

async function fetchData() {

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value,
    client_id: client_id.value,
    state_id: state_id.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await billingsStores.fetchBillings(data)

  billings.value = billingsStores.getBillings
  totalPages.value = billingsStores.last_page
  totalBillings.value = billingsStores.billingsTotalCount
  totalSum.value = billingsStores.totalSum
  totalTax.value = billingsStores.totalTax
  totalNeto.value = billingsStores.totalNeto
  totalPending.value = billingsStores.totalPending
  totalPaid.value = billingsStores.totalPaid
  totalExpired.value = billingsStores.totalExpired
  pendingTax.value = billingsStores.pendingTax
  paidTax.value = billingsStores.paidTax
  expiredTax.value = billingsStores.expiredTax

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  clients.value = billingsStores.clients

  if(role.value !== 'Supplier') {
    suppliers.value = billingsStores.suppliers
  }

  isRequestOngoing.value = false
}

const addInvoice = () => {
    router.push({ name : 'dashboard-admin-billings-add' })
}

const updateBilling = billingData => {
  isConfirmStateDialogVisible.value = true
  selectedBilling.value = { ...billingData }
}

const showBilling = billingData => {
  router.push({ name : 'dashboard-admin-billings-id', params: { id: billingData.id } })
}

const editBilling = billingData => {
  router.push({ name : 'dashboard-admin-billings-edit-id', params: { id: billingData.id } })
}

const updateStateId = newStateId => {
  state_id.value = newStateId

  switch(newStateId) {
    case 4: 
      bgColor.value = 'bg-light-warning'
      textColor.value = 'text-warning'
    break
    case 7:
      bgColor.value = 'bg-light-info'
      textColor.value = 'text-info'
    break
    case 8:
      bgColor.value = 'bg-light-error'
      textColor.value = 'text-error'
    break   
  }
}

const updateState = async () => {
  isConfirmStateDialogVisible.value = false
  let res = await billingsStores.updateState(selectedBilling.value.id)
  selectedBilling.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Invoice updated!' : res.data.message,
    show: true
  }

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  await fetchData()

  return true
}

const openLink = function (billingData) {
  window.open(themeConfig.settings.urlStorage + billingData.file)
}

const printInvoice = async(billing) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + billing.file);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);
    
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = blobUrl;
    
    iframe.onload = () => {
      iframe.contentWindow.print();
    };
    
    document.body.appendChild(iframe);
  } catch (error) {
    console.error('Error:', error);
  }
}

const duplicate = (billing) => {
  router.push({ name : 'dashboard-admin-billings-duplicate-id', params: { id: billing.id } })
}

const credit = (billing) => {
  router.push({ name : 'dashboard-admin-billings-credit-id', params: { id: billing.id } })
}

const send = billingData => {
  isConfirmSendMailVisible.value = true
  selectedBilling.value = { ...billingData }
}

const addTag = (event) => {
  const newTag = event.target.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (newTag && emailRegex.test(newTag)) {
    if (!selectedTags.value.includes(newTag)) {
      selectedTags.value.push(newTag);

      if (!existingTags.value.includes(newTag)) {
        existingTags.value.push(newTag);
      }

    }
  } else {
    isValid.value = true
    selectedTags.value.pop();
  }
};

const sendMails = async () => {
  isConfirmSendMailVisible.value = false
  isRequestOngoing.value = true

  let data = {
    id: selectedBilling.value.id,
    emailDefault: emailDefault.value,
    emails: selectedTags.value
  }

  let res = await billingsStores.sendMails(data)

  isRequestOngoing.value = false

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Invoice sent!' : res.data.message,
    show: true
  }

  setTimeout(() => {
    selectedTags.value = []
    existingTags.value = []
    emailDefault.value = true 

    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  await fetchData()
  
  return true
}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await billingsStores.fetchBillings(data)

  let dataArray = [];
      
  billingsStores.getBillings.forEach(element => {

    let data = {
      INVOICE_ID: element.invoice_id,
      STATUS: element.state.name,
      CLIENT: element.client.fullname,
      CLIENT_EMAIL: element.client.email,
      SUPPLIER: element.supplier.user.name + ' '+ element.supplier.user.last_name,
      SUPPLIER_EMAIL: element.supplier.user.email,
      INVOICE_DATE: element.invoice_date,
      DUE_DATE: element.due_date,
      TOTAL: element.total + ' kr'
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "billings", "csv");

  isRequestOngoing.value = false

}
</script>

<template>
  <section>
    <Toaster />
    <VRow>
      <VDialog
        v-model="isRequestOngoing"
        width="300"
        persistent>
          
        <VCard
          color="primary"
          width="300">
            
          <VCardText class="pt-3">
           Lastning
            <VProgressLinear
              indeterminate
              color="white"
              class="mb-0"/>
          </VCardText>
        </VCard>
      </VDialog>

      <VCol cols="12">
        <VAlert
          v-if="advisor.show"
          :type="advisor.type"
          class="mb-6">
            
          {{ advisor.message }}
        </VAlert>

        <VCard title="Filters">
          <VCardText>
            <VRow>
              <VCol cols="12" md="8" class="border-e d-flex justify-content-between align-center">

                <div class="d-flex justify-space-between flex-wrap w-100 flex-column flex-md-row">
                  <div
                    v-for="{ title, state_id, tax, value, icon, color } in [
                      { title: 'Pending', state_id: 4, tax: formatNumber(pendingTax ?? '0.00') + ' kr', value: formatNumber(totalPending ?? '0.00') + ' kr', icon: 'mdi-invoice-text-clock', color: 'warning' },
                      { title: 'Paid', state_id: 7, tax: formatNumber(paidTax ?? '0.00') + ' kr', value: formatNumber(totalPaid ?? '0.00') + ' kr', icon: 'mdi-invoice-text-check', color: 'info' },
                      { title: 'Expired', state_id: 8, tax: formatNumber(expiredTax ?? '0.00') + ' kr', value: formatNumber(totalExpired ?? '0.00') + ' kr', icon: 'mdi-invoice-text-remove', color: 'error' },
                    ]"
                    :key="title"
                  >
                    <div class="d-flex cursor-pointer" @click="updateStateId(state_id)">
                      <VAvatar
                        variant="tonal"
                        :color="color"
                        rounded
                        size="65"
                        class="me-2"
                      >
                        <VIcon
                          :icon="icon"
                          size="45"
                        />
                      </VAvatar>
                      <div>
                        <h5 
                          class="text-h5 font-weight-medium"
                          :class="`text-${color}`"
                        >
                          {{ title }}
                        </h5>
                        <h6
                          class="text-h6"
                          :class="`text-${color}`"
                        >
                          {{ value }}
                        </h6>
                        <span 
                          class="text-sm"
                          :class="`text-${color}`">
                          varav moms {{ tax }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="4" class="d-flex flex-column">
                <VSelect
                  v-model="client_id"
                  :items="clients"
                  :item-title="item => item.fullname"
                  :item-value="item => item.id"
                  placeholder="Clients"
                  class="mb-2"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"/>

                <VSelect
                  v-if="role !== 'Supplier'"
                  v-model="supplier_id"
                  placeholder="Suppliers"
                  :items="suppliers"
                  :item-title="item => item.full_name"
                  :item-value="item => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"/>

                  <VTextField
                    v-else
                    v-model="searchQuery"
                    placeholder="Search"
                    density="compact"
                    clearable
                  />
              </VCol>
            </VRow>
          </VCardText>
          <VDivider />
          <VCardText class="d-flex flex-wrap py-4 gap-4">
            <div
              class="me-3"
              style="width: 80px;">
              
              <VSelect
                v-model="rowPerPage"
                density="compact"
                variant="outlined"
                :items="[10, 20, 30, 50]"/>
            </div>

            <div class="d-flex align-center">
              <VBtn
                variant="tonal"
                color="secondary"
                prepend-icon="tabler-file-export"
                @click="downloadCSV">
                Export
              </VBtn>
            </div>

            <VSpacer />

            <div class="d-flex align-center flex-wrap gap-4">

              <!-- 👉 Search  -->
              <div class="search" v-if="role !== 'Supplier'">
                <VTextField
                  v-model="searchQuery"
                  placeholder="Search"
                  density="compact"
                  clearable
                />
              </div>

              <!-- 👉 Add user button -->
              <VBtn
                v-if="$can('create','billing') && clients.length > 0"
                prepend-icon="tabler-plus"
                @click="addInvoice">
                  Add invoice
              </VBtn>
            </div>
          </VCardText>

          <VDivider />

          <VTable class="text-no-wrap" style="border-radius: 0 !important">
            <!-- 👉 table head -->
            <thead :class="bgColor">
              <tr>
                <th scope="col"> <span :class="textColor"> # INVOICE </span> </th>
                <th scope="col"> <span :class="textColor"> CLIENT </span> </th>
                <th scope="col" v-if="role !== 'Supplier'"> <span :class="textColor"> SUPPLIER </span> </th>
                <th scope="col"> <span :class="textColor"> TOTAL </span> </th>
                <th scope="col"> <span :class="textColor"> INVOICE DATE </span> </th>
                <th scope="col"> <span :class="textColor"> DUE DATE </span> </th>
                <th class="text-center" scope="col"> <span :class="textColor"> STATUS </span> </th>
                <th class="text-center" scope="col"> <span :class="textColor"> INVOICE SENT </span> </th>                
                <th class="text-center" scope="col" v-if="$can('edit', 'billing') || $can('delete', 'billing')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="billing in billings"
                :key="billing.id"
                style="height: 3rem;">

                <td> {{ billing.invoice_id }} </td>
                <td class="text-wrap">
                    <span class="font-weight-medium cursor-pointer text-primary" @click="showBilling(billing)">
                      {{ billing.client.fullname ?? '' }}
                    </span>
                </td>                
                <td class="text-wrap" v-if="role !== 'Supplier'">
                  <span class="font-weight-medium"  v-if="billing.supplier">
                    {{ billing.supplier.user.name }} {{ billing.supplier.user.last_name ?? '' }} 
                  </span>
                </td>
                <td> {{ formatNumber(billing.total) ?? '0.00' }} kr</td>
                <td> {{ billing.invoice_date }} </td>
                <td> {{ billing.due_date }} </td>
                <td class="text-center">
                  <span v-if="billing.client.deleted_at !== null">
                    <VChip color="error">
                      Client deleted
                    </VChip>
                  </span>
                  <template v-else>
                    <!-- 
                      4: pendiente  => warning
                      7: pagado => info
                      8: expirado => error
                      9: credito => error
                    -->
                    <VChip
                        v-if="billing.state_id !== 4"
                        label
                        :color="billing.state_id === 7 ? 'info' : 'error'"
                      >
                        {{ billing.state.name }}
                    </VChip>
                    <VCheckbox
                      v-else
                      :label="billing.state.name"
                      color="warning"
                      class="w-100 text-center d-flex justify-content-center"
                      true-icon="tabler-check"
                      @click.prevent="updateBilling(billing)"
                    >
                    <template #label>
                      <VChip
                        label
                        color="warning"
                      >
                        {{ billing.state.name }}
                      </VChip>
                    </template>
                    </VCheckbox>
                  </template>
                </td>
                <td class="text-center"> 
                  <VChip
                    label
                    :color="billing.is_sent === 0 ? 'error' : 'info'"
                  >
                    {{ billing.is_sent === 0 ? 'NOT SENT' : 'SENT' }}
                  </VChip>
                </td>
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'billing') || $can('delete', 'billing')">      
                  <VMenu>
                    <template #activator="{ props }">
                      <VBtn v-bind="props" icon variant="text" color="default" size="x-small">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                          <path d="M12.52 20.924c-.87 .262 -1.93 -.152 -2.195 -1.241a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.088 .264 1.502 1.323 1.242 2.192"></path>
                          <path d="M19 16v6"></path>
                          <path d="M22 19l-3 3l-3 -3"></path>
                          <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                        </svg>
                      </VBtn>
                    </template>
                    <VList>
                      <VListItem
                         v-if="$can('edit', 'billing')"
                         @click="printInvoice(billing)">
                        <template #prepend>
                          <VIcon icon="mdi-printer" />
                        </template>
                        <VListItemTitle>Print</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'billing')"
                         @click="openLink(billing)">
                        <template #prepend>
                          <VIcon icon="mdi-file-pdf-box" />
                        </template>
                        <VListItemTitle>View as PDF</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'billing')"
                         @click="duplicate(billing)">
                        <template #prepend>
                          <VIcon icon="mdi-content-copy" />
                        </template>
                        <VListItemTitle>Duplicate</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'billing')"
                         @click="send(billing)">
                        <template #prepend>
                          <VIcon icon="mdi-email-fast" />
                        </template>
                        <VListItemTitle>Send</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('edit', 'billing') && (billing.state_id === 4 || billing.state_id === 8)"
                        @click="editBilling(billing)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Edit</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','billing') && billing.state_id === 7"
                        @click="credit(billing)">
                        <template #prepend>
                          <VIcon icon="tabler-trash" />
                        </template>
                        <VListItemTitle>Credit</VListItemTitle>
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
                  :colspan="role === 'Supplier' ? 8 : 9"
                  class="text-center">
                  Data not available
                </td>
              </tr>
            </tfoot>
          </VTable>
        
          <VDivider />

          <VCardText class="d-flex align-center flex-wrap justify-space-between gap-4 py-3 px-5">
            <span class="text-sm text-disabled">
              {{ paginationData }}
            </span>

            <span class="text-sm text-disabled">
              <strong class="me-5">NETO: {{ formatNumber(totalNeto ?? 0) }} kr</strong>
              <strong class="me-5">TAX: {{ formatNumber(totalTax ?? 0) }} kr</strong>
              <strong>TOTAL: {{ formatNumber(totalSum ?? 0) }} kr</strong>
            </span>

            <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="5"
              :length="totalPages"/>
          
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 👉 Confirm send -->
    <VDialog
      v-model="isConfirmSendMailVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmSendMailVisible = !isConfirmSendMailVisible" />

      <!-- Dialog Content -->
      <VCard title="Send invoice by email">
        <VDivider class="mt-4"/>
        <VCardText>
          Are you sure you want to send invoices to the following email addresses?.
        </VCardText>
        <VCardText class="d-flex flex-column gap-2">
          <VCheckbox
            v-model="emailDefault"
            :label="selectedBilling.client.email"
          />

          <VCombobox
            v-model="selectedTags"
            :items="existingTags"
            label="Enter emails to send invoice"
            multiple
            chips
            deletable-chips
            clearable
            @blur="addTag"
            @keydown.enter.prevent="addTag"
            @input="isValid = false"
          /> 
          <span class="text-xs text-error" v-if="isValid">Email must be a valid email</span>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmSendMailVisible = false">
              Cancel
          </VBtn>
          <VBtn @click="sendMails">
              Send
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Update State -->
    <VDialog
      v-model="isConfirmStateDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmStateDialogVisible = !isConfirmStateDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Update status">
        <VDivider class="mt-4"/>
        <VCardText>
          Are you sure you want to update the invoice status <strong>#{{ selectedBilling.invoice_id }}</strong> to paid?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmStateDialogVisible = false">
              Cancel
          </VBtn>
          <VBtn @click="updateState">
              Accept
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style scope>
  .search {
    width: 100%;
  }

  .justify-content-center {
    justify-content: center !important;
  }

  @media(min-width: 991px){
    .search {
      width: 30rem;
    }
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: billing
</route>