<script setup>

import { useBillingsStores } from '@/stores/useBillings'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { avatarText, formatNumber } from '@/@core/utils/formatters'
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
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmStateDialogVisible = ref(false)
const selectedBilling = ref({})

const supplier_id = ref(null)
const client_id = ref(null)
const state_id = ref(null)
const userData = ref(null)
const role = ref(null)

const states = ref ([
  { id: 4, name: "Pending" },
  { id: 7, name: "Paid" }
])

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

  isRequestOngoing.value = true

  await billingsStores.fetchBillings(data)

  billings.value = billingsStores.getBillings
  totalPages.value = billingsStores.last_page
  totalBillings.value = billingsStores.billingsTotalCount

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  clients.value = billingsStores.clients

  if(role.value !== 'Supplier') {
    suppliers.value = billingsStores.suppliers
  }

  isRequestOngoing.value = false
}

const resolveInvoiceStatusVariantAndIcon = status => {

  let variant = 'secondary'
  let icon = 'tabler-x'

  switch(status) {
      case 4: 
          variant = 'error'
          icon = 'tabler-clock-hour-3'
      break;
      case 7: 
          variant = 'success'
          icon = 'tabler-check'
      break;
      default:
          variant = 'error'
          icon = 'tabler-clock-hour-3'
      break; 
  }

  return {
      variant: variant,
      icon: icon
  }
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

const showDeleteDialog = billingData => {
  isConfirmDeleteDialogVisible.value = true
  selectedBilling.value = { ...billingData }
}

const removeBilling = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await billingsStores.deleteBilling(selectedBilling.value.id)
  selectedBilling.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Invoice deleted!' : res.data.message,
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
  isConfirmStateDialogVisible.value = false
  let res = await billingsStores.updateState(selectedBilling.value.id)
  selectedBilling.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Invoice deleted!' : res.data.message,
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
      TOTAL: 'KR ' + element.total
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
            Loading
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
              <VCol cols="12" md="4">
                <VSelect
                  v-model="state_id"
                  placeholder="States"
                  :items="states"
                  :item-title="item => item.name"
                  :item-value="item => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"/>
              </VCol>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="client_id"
                  placeholder="Clients"
                  :items="clients"
                  :item-title="item => item.fullname"
                  :item-value="item => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"/>
              </VCol>
              <VCol cols="12" md="4">
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
                v-if="$can('create','billing')"
                prepend-icon="tabler-plus"
                @click="addInvoice">
                  Add invoice
              </VBtn>
            </div>
          </VCardText>

          <VDivider />

          <VTable class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> # INVOICE </th>
                <th scope="col"> STATUS </th>
                <th scope="col"> CLIENT </th>
                <th scope="col" v-if="role !== 'Supplier'"> SUPPLIER </th>
                <th scope="col"> INVOICE DATE </th>
                <th scope="col"> DUE DATE </th>
                <th scope="col"> TOTAL </th>
                <th scope="col" v-if="$can('edit', 'billing') || $can('delete', 'billing')">
                  ACTIONS
                </th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="billing in billings"
                :key="billing.id"
                style="height: 3.75rem;">

                <td> {{ billing.invoice_id }} </td>
                <td>
                  <VTooltip>
                    <template #activator="{ props }">
                        <VAvatar
                            v-bind="props"
                            :size="30"
                            :color="resolveInvoiceStatusVariantAndIcon(billing.state_id).variant"
                            variant="tonal"
                        >
                            <VIcon
                                :size="20"
                                :icon="resolveInvoiceStatusVariantAndIcon(billing.state_id).icon"
                            />
                        </VAvatar>
                    </template>
                    <p class="mb-0"> {{ billing.state.name }} </p>
                    <p class="mb-0"> Total: ${{ formatNumber(billing.total ?? 0) }} </p>
                  </VTooltip>
              </td>
                <td class="text-wrap">
                  <div class="d-flex align-center gap-x-3">
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ billing.client.fullname ?? '' }} 
                      </span>
                      <span class="text-sm text-disabled">{{ billing.client.email }}</span>
                    </div>
                  </div>
                </td>
                <td class="text-wrap" v-if="role !== 'Supplier'">
                  <div class="d-flex align-center gap-x-3" v-if="billing.supplier">
                    <VAvatar
                      :variant="billing.supplier.user.avatar ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="billing.supplier.user.avatar"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + billing.supplier.user.avatar"
                      />
                        <span v-else>{{ avatarText(billing.supplier.user.name) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ billing.supplier.user.name }} {{ billing.supplier.user.last_name ?? '' }} 
                      </span>
                      <span class="text-sm text-disabled">{{ billing.supplier.user.email }}</span>
                    </div>
                  </div>
                </td>
                <td> {{ billing.invoice_date }} </td>
                <td> {{ billing.due_date }} </td>
                <td> KR {{ formatNumber(billing.total) ?? '0.00' }} </td>
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 5rem;" v-if="$can('edit', 'billing') || $can('delete', 'billing')">      
                  <VBtn
                    v-if="$can('edit', 'billing') && billing.state_id === 4"
                    icon
                    size="x-small"
                    color="default"
                    variant="text"
                    @click="updateBilling(billing)">
                    <VTooltip
                      open-on-focus
                      location="top"
                      activator="parent">
                      Pay
                    </VTooltip>
                    <VIcon
                        size="22"
                        icon="tabler-file-dollar" />
                  </VBtn>
                  <VBtn
                    v-if="$can('edit', 'billing')"
                    icon
                    size="x-small"
                    color="default"
                    variant="text"
                    @click="showBilling(billing)">
                    <VTooltip
                      open-on-focus
                      location="top"
                      activator="parent">
                      View
                    </VTooltip>
                    <VIcon
                        size="22"
                        icon="tabler-eye" />
                  </VBtn>

                  <VBtn
                    v-if="$can('delete','billing')"
                    icon
                    size="x-small"
                    color="default"
                    variant="text"
                    @click="showDeleteDialog(billing)">
                    <VTooltip
                      open-on-focus
                      location="top"
                      activator="parent">
                      Delete
                    </VTooltip>   
                    <VIcon
                      size="22"
                      icon="tabler-trash" />
                  </VBtn>
                </td>
              </tr>
            </tbody>
            <!-- 👉 table footer  -->
            <tfoot v-show="!billings.length">
              <tr>
                <td
                  colspan="7"
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

            <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="5"
              :length="totalPages"/>
          
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Delete Billing">
        <VDivider class="mt-4"/>
        <VCardText>
          Are you sure you want to delete the Invoice <strong>#{{ selectedBilling.invoice_id }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Cancel
          </VBtn>
          <VBtn @click="removeBilling">
              Accept
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