<script setup>

import { toRaw } from 'vue'
import { useSuppliersStores } from '@/stores/useSuppliers'
import { useClientsStores } from '@/stores/useClients'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { avatarText, formatNumber } from '@/@core/utils/formatters'
import AddNewClientDrawer from './AddNewClientDrawer.vue' 
import router from '@/router'

const clientsStores = useClientsStores()
const suppliersStores = useSuppliersStores()

const suppliers = ref([])
const clients = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalClients = ref(0)
const isRequestOngoing = ref(true)
const isAddNewClientDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const selectedClient = ref({})

const supplier_id = ref(null)
const userData = ref(null)
const role = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = clients.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = clients.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Mostrando ${ firstIndex } hasta ${ lastIndex } de ${ totalClients.value } registros`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewClientDrawerVisible.value)
        selectedClient.value = {}
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
    page: currentPage.value,
    supplier_id: supplier_id.value
  }

  isRequestOngoing.value = true

  await clientsStores.fetchClients(data)

  clients.value = clientsStores.getClients
  totalPages.value = clientsStores.last_page
  totalClients.value = clientsStores.clientsTotalCount

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  
  if(role.value !== 'Supplier') {
    await suppliersStores.fetchSuppliers({ limit: -1 , state_id: 2})
    suppliers.value = toRaw(suppliersStores.getSuppliers)
  }

  isRequestOngoing.value = false
}

const editClient = clientData => {
    isAddNewClientDrawerVisible.value = true
    selectedClient.value = { ...clientData }
}

const showDeleteDialog = clientData => {
  isConfirmDeleteDialogVisible.value = true
  selectedClient.value = { ...clientData }
}

const seeClient = clientData => {
  router.push({ name : 'dashboard-admin-clients-id', params: { id: clientData.id } })
}

const removeClient = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await clientsStores.deleteClient(selectedClient.value.id)
  selectedClient.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Client deleted!' : res.data.message,
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

const submitForm = async (client, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    client.data.append('_method', 'PUT')
    submitUpdate(client)
    return
  }

  submitCreate(client.data)
}


const submitCreate = clientData => {

    clientsStores.addClient(clientData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Client created! ',
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

const submitUpdate = clientData => {

    clientsStores.updateClient(clientData)
        .then((res) => {
            if (res.data.success) {
                    advisor.value = {
                    type: 'success',
                    message: 'Client updated!',
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

  await clientsStores.fetchClients(data)

  let dataArray = [];
      
  clientsStores.getClients.forEach(element => {

    let data = {
      ID: element.id,
      CONTACT: element.fullname,
      EMAIL: element.email,
      ORGANIZATION_NUMBER: element.organization_number ?? ''
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "clients", "csv");

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
              <VSelect
                v-if="role !== 'Supplier'"
                v-model="supplier_id"
                placeholder="Suppliers"
                :items="suppliers"
                :item-title="item => item.full_name"
                :item-value="item => item.id"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"
                style="width: 300px"
                :menu-props="{ maxHeight: '300px' }"/>

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
                v-if="$can('create','clients')"
                prepend-icon="tabler-plus"
                @click="isAddNewClientDrawerVisible = true">
                  Add client
              </v-btn>
            </div>
          </v-card-text>

          <v-divider />

          <v-table class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> CONTACT </th>
                <th scope="col"> ORGANIZATION NUMBER </th>
                <th scope="col"> PHONE </th>
                <th scope="col"> ADDRESS </th>
                <th scope="col" v-if="role !== 'Supplier'"> SUPPLIER </th>
                <th scope="col" v-if="$can('edit', 'clients') || $can('delete', 'clients')">
                  ACTIONS
                </th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="client in clients"
                :key="client.id"
                style="height: 3.75rem;">

                <td> {{ client.id }} </td>
                <td class="text-wrap">
                  <div class="d-flex flex-column">
                    <span class="font-weight-medium">
                      {{ client.fullname }} 
                    </span>
                    <span class="text-sm text-disabled">{{ client.email }}</span>
                  </div>
                </td>
                <td class="text-wrap">
                  <span class="text-sm text-disabled" v-if="client.organization_number">
                   {{ client.organization_number ?? ''}}
                  </span>
                </td>
                <td class="text-wrap">
                  <span class="text-sm text-disabled">
                    {{ client.phone ?? ''}}
                  </span>
                </td>  
                <td class="text-wrap">
                  <span class="text-sm text-disabled">
                    {{ client.address ?? ''}}
                  </span>
                </td>               
                <td class="text-wrap" v-if="role !== 'Supplier'">
                  <div class="d-flex align-center gap-x-3" v-if="client.supplier">
                    <VAvatar
                      :variant="client.supplier.user.avatar ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="client.supplier.user.avatar"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + client.supplier.user.avatar"
                      />
                        <span v-else>{{ avatarText(client.supplier.user.name) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ client.supplier.user.name }} {{ client.supplier.user.last_name ?? '' }} 
                      </span>
                      <span class="text-sm text-disabled">{{ client.supplier.user.email }}</span>
                    </div>
                  </div>
                </td>
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 5rem;" v-if="$can('edit', 'clients') || $can('delete', 'clients')">      
                  <!-- <VBtn
                    v-if="$can('view', 'clients')"
                    icon
                    variant="text"
                    color="default"
                    size="x-small"
                    @click="seeClient(client)">
                    <VTooltip
                      open-on-focus
                      location="top"
                      activator="parent">
                      View
                    </VTooltip>
                    <VIcon
                      size="28"
                      icon="tabler-eye"
                      class="me-1"
                    />
                  </VBtn>  -->
                  <VBtn
                    v-if="$can('edit', 'clients')"
                    icon
                    size="x-small"
                    color="default"
                    variant="text"
                    @click="editClient(client)">
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
                    v-if="$can('delete','clients')"
                    icon
                    size="x-small"
                    color="default"
                    variant="text"
                    @click="showDeleteDialog(client)">
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
            <tfoot v-show="!clients.length">
              <tr>
                <td
                  :colspan="role === 'Supplier' ? 6 : 7"
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
    <!-- 👉 Add New Client -->
    <AddNewClientDrawer
      v-model:isDrawerOpen="isAddNewClientDrawerVisible"
      :client="selectedClient"
      :suppliers="suppliers"
      @client-data="submitForm"/>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Delete Client">
        <VDivider class="mt-4"/>
        <VCardText>
          Are you sure you want to delete the client <strong>{{ selectedClient.fullname }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Cancel
          </VBtn>
          <VBtn @click="removeClient">
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
            width: 25rem;
        }
    }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: clients
</route>