<script setup>

import { useTypesStores } from '@/stores/useTypes'
import { useInvoicesStores } from '@/stores/useInvoices'
import { excelParser } from '@/plugins/csv/excelParser'
import AddNewInvoiceDrawer from './AddNewInvoiceDrawer.vue' 

const invoicesStores = useInvoicesStores()
const typesStores = useTypesStores()

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
const selectedInvoice = ref({})

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = invoices.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = invoices.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Mostrando ${ firstIndex } hasta ${ lastIndex } de ${ totalInvoices.value } registros`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewInvoiceDrawerVisible.value)
        selectedInvoice.value = {}
})

onMounted(async () => {
  
})

watchEffect(fetchData)

async function fetchData() {

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value
  }

  isRequestOngoing.value = true

  await invoicesStores.fetchInvoices(data)
  await typesStores.fetchTypes()
  types.value = typesStores.getTypes

  invoices.value = invoicesStores.getInvoices
  totalPages.value = invoicesStores.last_page
  totalInvoices.value = invoicesStores.invoicesTotalCount

  isRequestOngoing.value = false
}

const editInvoice = invoiceData => {
    isAddNewInvoiceDrawerVisible.value = true
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
    message: res.data.success ? 'Invoice attribute deleted!' : res.data.message,
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
                    message: 'Invoice attribute created! ',
                    show: true
                }
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
                    message: 'Invoice attribute updated!',
                    show: true
                }
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

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await invoicesStores.fetchInvoices(data)

  let dataArray = [];
      
  invoicesStores.getInvoices.forEach(element => {

    let data = {
      ID: element.id,
      NAME_EN: element.name_es,
      NAME_SE: element.name_se,
      DESCRIPTION_EN: element.description_en ?? '',
      DESCRIPTION_SE: element.description_se ?? ''
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "invoices", "csv");

  isRequestOngoing.value = false

}
</script>

<template>
  <section>
    <v-row>
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

      <v-col cols="12">
        <v-alert
          v-if="advisor.show"
          :type="advisor.type"
          class="mb-6">
            
          {{ advisor.message }}
        </v-alert>

        <v-card title="">
          <v-card-text class="d-flex flex-wrap py-4 gap-4">
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

            <v-spacer />

            <div class="d-flex align-center flex-wrap gap-4">

              <!-- 👉 Search  -->
              <div class="search">
                <VTextField
                  v-model="searchQuery"
                  placeholder="Search"
                  density="compact"
                  clearable
                />
              </div>

              <!-- 👉 Add user button -->
              <v-btn
                v-if="$can('create','invoices')"
                prepend-icon="tabler-plus"
                @click="isAddNewInvoiceDrawerVisible = true">
                  Add invoice attribute
              </v-btn>
            </div>
          </v-card-text>

          <v-divider />

          <v-table class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> ENGLISH INFO </th>
                <th scope="col"> SWEDISH INFO </th>
                <th scope="col" v-if="$can('edit', 'invoices') || $can('delete', 'invoices')">
                  ACTIONS
                </th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="invoice in invoices"
                :key="invoice.id"
                style="height: 3.75rem;">

                <td> {{ invoice.id }} </td>
                <td class="text-wrap">
                  <div class="d-flex flex-column">
                    <span class="font-weight-medium">
                      {{ invoice.name_en }} 
                    </span>
                    <span class="text-sm text-disabled">{{ invoice.description_en }}</span>
                    <span class="text-sm text-disabled"> Type: {{ invoice.type.name_en }}</span>
                  </div>
                </td>
                <td class="text-wrap">
                  <div class="d-flex flex-column">
                    <span class="font-weight-medium">
                      {{ invoice.name_se }} 
                    </span>
                    <span class="text-sm text-disabled">{{ invoice.description_se }}</span>
                    <span class="text-sm text-disabled"> Type: {{ invoice.type.name_se }}</span>
                  </div>
                </td>
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 5rem;" v-if="$can('edit', 'invoices') || $can('delete', 'invoices')">      
                  <VBtn
                    v-if="$can('edit', 'invoices')"
                    icon
                    size="x-small"
                    color="default"
                    variant="text"
                    @click="editInvoice(invoice)">
                    <VTooltip
                      open-on-focus
                      location="top"
                      activator="parent">
                      Edit
                    </VTooltip>
                    <VIcon
                        size="22"
                        icon="tabler-edit" />
                  </VBtn>

                  <VBtn
                    v-if="$can('delete','invoices')"
                    icon
                    size="x-small"
                    color="default"
                    variant="text"
                    @click="showDeleteDialog(invoice)">
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
            <tfoot v-show="!invoices.length">
              <tr>
                <td
                  colspan="7"
                  class="text-center">
                  Data not available
                </td>
              </tr>
            </tfoot>
          </v-table>
        
          <v-divider />

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
        </v-card>
      </v-col>
    </v-row>
    <!-- 👉 Add New Invoice -->
    <AddNewInvoiceDrawer
      v-model:isDrawerOpen="isAddNewInvoiceDrawerVisible"
      :invoice="selectedInvoice"
      :types="types"
      @invoice-data="submitForm"/>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Delete Invoice">
        <VDivider class="mt-4"/>
        <VCardText>
          Are you sure you want to delete the Invoice <strong>{{ selectedInvoice.name_en }} / {{ selectedInvoice.name_se }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Cancel
          </VBtn>
          <VBtn @click="removeInvoice">
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
    subject: invoices
</route>