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
const emitter = inject("emitter")

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

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalClients.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewClientDrawerVisible.value)
        selectedClient.value = {}
})

onMounted(async () => {
  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name
  
  if(role.value !== 'Supplier') {
    await suppliersStores.fetchSuppliers({ limit: -1 , state_id: 2})
    suppliers.value = toRaw(suppliersStores.getSuppliers)
  }
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
    supplier_id.value = null
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await clientsStores.fetchClients(data)

  clients.value = clientsStores.getClients
  totalPages.value = clientsStores.last_page
  totalClients.value = clientsStores.clientsTotalCount

  isRequestOngoing.value = false
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
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
    message: res.data.success ? 'Klient raderad!' : res.data.message,
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
                    message: 'Kund skapad! ',
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
                    message: 'Klienten uppdaterad!',
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
      ID: element.order_id,
      KONTAKT: element.fullname,
      E_POST: element.email,
      ORGANISATIONSNUMMER: element.organization_number ?? ''
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
    <VRow>
      <VDialog
        v-model="isRequestOngoing"
        width="auto"
        persistent>
        <VProgressCircular
          indeterminate
          color="primary"
          class="mb-0"/>
      </VDialog>

      <VCol cols="12">
        <VAlert
          v-if="advisor.show"
          :type="advisor.type"
          class="mb-6">
            
          {{ advisor.message }}
        </VAlert>

        <VCard title="">
          <VCardText class="d-flex align-center flex-wrap gap-4">
            <div class="d-flex align-center w-100 w-md-auto">
              <span class="text-no-wrap me-3">Visa:</span>
              <VSelect
                v-model="rowPerPage"
                density="compact"
                variant="outlined"
                class="w-100"
                :items="[10, 20, 30, 50]"/>
            </div>

            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-file-export"
              class="w-100 w-md-auto"
              @click="downloadCSV">
              Exportera
            </VBtn>

            <VSpacer class="d-none d-md-block"/>

            <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto">
              <VAutocomplete
                v-if="role !== 'Supplier'"
                v-model="supplier_id"
                placeholder="Leverantörer"
                :items="suppliers"
                :item-title="item => item.full_name"
                :item-value="item => item.id"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"
                style="width: 200px"
                :menu-props="{ maxHeight: '300px' }"/>

              <!-- 👉 Search  -->
              <div class="search">
                <VTextField
                  v-model="searchQuery"
                  placeholder="Sök"
                  density="compact"
                  clearable
                />
              </div>

              <!-- 👉 Add user button -->
              <VBtn
                v-if="$can('create','clients')"
                prepend-icon="tabler-plus"
                class="w-100 w-md-auto"
                @click="isAddNewClientDrawerVisible = true">
                  Ny kund
              </VBtn>
            </div>
          </VCardText>

          <v-divider />

          <v-table class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> KONTAKT </th>
                <th scope="col"> ORGANISATIONSNUMMER </th>
                <th scope="col"> TELEFON </th>
                <th scope="col"> ADRESS </th>
                <th scope="col" v-if="role !== 'Supplier'"> LEVERANTÖR </th>
                <th scope="col" v-if="$can('edit', 'clients') || $can('delete', 'clients')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="client in clients"
                :key="client.id"
                style="height: 3rem;">

                <td> {{ client.order_id }} </td>
                <td class="text-wrap">
                  <div class="d-flex flex-column">
                    <span class="font-weight-medium cursor-pointer text-primary" @click="seeClient(client)">
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
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'clients') || $can('delete', 'clients')">      
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
                         v-if="$can('edit', 'clients')"
                         @click="editClient(client)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','clients')"
                        @click="showDeleteDialog(client)">
                        <template #prepend>
                          <VIcon icon="tabler-trash" />
                        </template>
                        <VListItemTitle>Ta bort</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </td>
              </tr>
            </tbody>
            <!-- 👉 table footer  -->
            <tfoot v-show="!clients.length">
              <tr>
                <td
                  :colspan="role === 'Supplier' ? 6 : 7"
                  class="text-center">
                  Uppgifter ej tillgängliga
                </td>
              </tr>
            </tfoot>
          </v-table>
        
          <v-divider />

          <VCardText class="d-block d-md-flex text-center align-center flex-wrap gap-4 py-3">
            <span class="text-sm text-disabled">
              {{ paginationData }}
            </span>

            <VSpacer class="d-none d-md-block"/>

            <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="5"
              :length="totalPages"/>
          
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
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
      <VCard title="Ta bort klient">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort klienten <strong>{{ selectedClient.fullname }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeClient">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style scope>
    .search {
        width: 100% !important;
    }

    @media(min-width: 991px){
        .search {
            width: 20rem !important;
        }
    }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: clients
</route>