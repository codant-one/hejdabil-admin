<script setup>

import { useCurrenciesStores } from '@/stores/useCurrencies'
import { excelParser } from '@/plugins/csv/excelParser'
import AddNewCurrencyDrawer from './AddNewCurrencyDrawer.vue' 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const currenciesStores = useCurrenciesStores()
const emitter = inject("emitter")

const currencies = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalCurrencies = ref(0)
const isRequestOngoing = ref(true)
const isAddNewCurrencyDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const selectedCurrency = ref({})
const state_id = ref(null)

const states = ref ([
  { id: 2, name: "Aktiv" },
  { id: 1, name: "Inaktiv" }
])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = currencies.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = currencies.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalCurrencies.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewCurrencyDrawerVisible.value)
        selectedCurrency.value = {}
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

  await currenciesStores.fetchCurrencies(data)

  currencies.value = currenciesStores.getCurrencies
  totalPages.value = currenciesStores.last_page
  totalCurrencies.value = currenciesStores.currenciesTotalCount

  isRequestOngoing.value = false
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const resolveStatus = state_id => {
  if (state_id === 2)
    return { color: 'success' }
  if (state_id === 1)
    return { color: 'error' }
}

const showActivateDialog = currencyData => {
  isConfirmActiveDialogVisible.value = true
  selectedCurrency.value = { ...currencyData }
}

const editCurrency = currencyData => {
    isAddNewCurrencyDrawerVisible.value = true
    selectedCurrency.value = { ...currencyData }
}

const showDeleteDialog = currencyData => {
  isConfirmDeleteDialogVisible.value = true
  selectedCurrency.value = { ...currencyData }
}

const removeCurrency = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await currenciesStores.deleteCurrency(selectedCurrency.value.id)
  selectedCurrency.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Valuta raderad!' : res.data.message,
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

const submitForm = async (currency, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    currency.data.append('_method', 'PUT')
    submitUpdate(currency)
    return
  }

  submitCreate(currency.data)
}


const submitCreate = currencyData => {

  currenciesStores.addCurrency(currencyData)
    .then((res) => {
        if (res.data.success) {
            advisor.value = {
                type: 'success',
                message: 'Valuta skapad! ',
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

const submitUpdate = currencyData => {

  currenciesStores.updateCurrency(currencyData)
    .then((res) => {
        if (res.data.success) {
                advisor.value = {
                type: 'success',
                message: 'Valuta uppdaterad!',
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

const updateState = async () => {
  isConfirmActiveDialogVisible.value = false
  let res = await currenciesStores.updateState(selectedCurrency.value.id)
  selectedCurrency.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Valuta uppdaterad!' : res.data.message,
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

  await currenciesStores.fetchCurrencies(data)

  let dataArray = [];
      
  currenciesStores.getCurrencies.forEach(element => {

    let data = {
      ID: element.id,
      NAMNET: element.name,
      KOD: element.code,
      STATU: element.state.name
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "currencies", "csv");

  isRequestOngoing.value = false

}
</script>

<template>
  <section>
    <VRow>
      <LoadingOverlay :is-loading="isRequestOngoing" />

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

            <div class="d-flex align-center w-100 w-md-10">
              <VSelect
                  v-model="state_id"
                  placeholder="Status"
                  :items="states"
                  :item-title="item => item.name"
                  :item-value="item => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"/>
            </div>

            <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto">

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
                v-if="$can('create','currencies')"
                prepend-icon="tabler-plus"
                class="w-100 w-md-auto"
                @click="isAddNewCurrencyDrawerVisible = true">
                  Ny Valuta
              </VBtn>
            </div>
          </VCardText>

          <v-divider />

          <v-table class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> NAMNET </th>
                <th scope="col"> KOD </th>
                <th scope="col"> STATUS </th>
                <th scope="col" v-if="$can('edit', 'currencies') || $can('delete', 'cliencurrenciests')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="currency in currencies"
                :key="currency.id"
                style="height: 3rem;">

                <td> {{ currency.id }} </td>
                <td class="text-wrap w-100">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      variant="outlined"
                      size="38"
                      >
                      <VImg
                        style="border-radius: 50%;"
                        :src="currency.flag"
                      />
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ currency.name }}
                      </span>
                      <span class="text-sm text-disabled">
                        {{ currency.code }}
                      </span>
                    </div>
                  </div>
                </td>
                <td class="text-wrap">
                  <VChip
                    label
                    :color="resolveStatus(currency.state.id)?.color"
                  >
                    {{ currency.state.name }}
                  </VChip>
                </td> 
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'currencies') || $can('delete', 'currencies')">      
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
                        v-if="$can('delete','currencies') && currency.state_id === 1"
                        @click="showActivateDialog(currency)">
                        <template #prepend>
                          <VIcon icon="tabler-rosette-discount-check" />
                        </template>
                        <VListItemTitle>Aktivera</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('delete','currencies') && currency.state_id === 2"
                        @click="showActivateDialog(currency)">
                        <template #prepend>
                          <VIcon icon="mdi-close-circle-outline" />
                        </template>
                        <VListItemTitle>Inaktivera</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'currencies')"
                         @click="editCurrency(currency)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','currencies')"
                        @click="showDeleteDialog(currency)">
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
            <tfoot v-show="!currencies.length">
              <tr>
                <td
                  colspan="4"
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
    <!-- 👉 Add New Currency -->
    <AddNewCurrencyDrawer
      v-model:isDrawerOpen="isAddNewCurrencyDrawerVisible"
      :currency="selectedCurrency"
      @currency-data="submitForm"/>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Ta bort valuta">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort valuta <strong>{{ selectedCurrency.name }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeCurrency">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isConfirmActiveDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmActiveDialogVisible = !isConfirmActiveDialogVisible" />

      <!-- Dialog Content -->
      <VCard :title="selectedCurrency.state_id === 1 ? 'Aktivera valuta' : 'Inaktivera valuta'">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill {{ selectedCurrency.state_id === 1 ? 'aktivera' : 'inaktivera' }} valuta  <strong>{{ selectedCurrency.name }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmActiveDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="updateState">
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
    subject: currencies
</route>