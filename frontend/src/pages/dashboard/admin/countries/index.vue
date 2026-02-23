<script setup>

import { useCountriesStores } from '@/stores/useCountries'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import AddNewCountryDrawer from './AddNewCountryDrawer.vue' 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const countriesStores = useCountriesStores()
const emitter = inject("emitter")

const countries = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalCountries = ref(0)
const isRequestOngoing = ref(true)
const isAddNewCountryDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const selectedCountry = ref({})
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

const failedExternalFlags = ref({})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = countries.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = countries.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalCountries.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewCountryDrawerVisible.value)
        selectedCountry.value = {}
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

  await countriesStores.fetchCountries(data)

  countries.value = countriesStores.getCountries
  totalPages.value = countriesStores.last_page
  totalCountries.value = countriesStores.countriesTotalCount

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

const showActivateDialog = countryData => {
  isConfirmActiveDialogVisible.value = true
  selectedCountry.value = { ...countryData }
}

const editCountry = countryData => {
    isAddNewCountryDrawerVisible.value = true
    selectedCountry.value = {
      ...countryData,
      flag: getFlagCountry(countryData),
    }
}

const showDeleteDialog = countryData => {
  isConfirmDeleteDialogVisible.value = true
  selectedCountry.value = { ...countryData }
}

const removeCountry = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await countriesStores.deleteCountry(selectedCountry.value.id)
  selectedCountry.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Land raderad!' : res.data.message,
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

const submitForm = async (country, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    country.data.append('_method', 'PUT')
    submitUpdate(country)
    return
  }

  submitCreate(country.data)
}


const submitCreate = countryData => {

  countriesStores.addCountry(countryData)
    .then((res) => {
        if (res.data.success) {
            advisor.value = {
                type: 'success',
                message: 'Land skapad! ',
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

const submitUpdate = countryData => {

  countriesStores.updateCountry(countryData)
    .then((res) => {
        if (res.data.success) {
                advisor.value = {
                type: 'success',
                message: 'Land uppdaterad!',
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
  let res = await countriesStores.updateState(selectedCountry.value.id)
  selectedCountry.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Land uppdaterad!' : res.data.message,
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

const findCountry = country => {
  if (!country || !Array.isArray(countries.value)) return null

  const normalizeText = value =>
    String(value ?? '')
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")

  if (typeof country === 'object') {
    return countries.value.find(item => item.id === country.id) || null
  }

  return countries.value.find(item => String(item.id) === String(country))
      || countries.value.find(item => normalizeText(item.name) === normalizeText(country))

}

const getFlagFromDb = selectedCountry => {
  const flag = String(selectedCountry?.flag ?? '').trim()
  if (!flag) return ''

  if (/^https?:\/\//i.test(flag)) return flag

  const basePublicUrl = String(themeConfig.settings.urlStorage ?? '').replace(/\/+$/, '')
  const cleanFlag = flag.replace(/^\/+/, '')

  if (cleanFlag.startsWith('/'))
    return `${basePublicUrl}/${cleanFlag}`

  return `${basePublicUrl}/${cleanFlag}`
}

const getFlagCountry = country => {
  const selectedCountry = findCountry(country)
  if (!selectedCountry) return ''

  const hasExternalError = !!failedExternalFlags.value[selectedCountry.id]

  if (selectedCountry?.iso && !hasExternalError)
    return `https://hatscripts.github.io/circle-flags/flags/${String(selectedCountry.iso).toLowerCase()}.svg`

  return getFlagFromDb(selectedCountry)
}

const onCountryFlagError = country => {
  const selectedCountry = findCountry(country)
  if (!selectedCountry?.id || !selectedCountry?.iso) return

  failedExternalFlags.value = {
    ...failedExternalFlags.value,
    [selectedCountry.id]: true,
  }
}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await countriesStores.fetchCountries(data)

  let dataArray = [];
      
  countriesStores.getCountries.forEach(element => {

    let data = {
      ID: element.id,
      NAMNET: element.name,
      STATU: element.state.name
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "countries", "csv");

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
              <span class="text-no-wrap me-3">Visa</span>
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
                v-if="$can('create','countries')"
                prepend-icon="tabler-plus"
                class="w-100 w-md-auto"
                @click="isAddNewCountryDrawerVisible = true">
                  Ny Land
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
                <th scope="col"> ISO </th>
                <th scope="col"> ISO 3</th>
                <th scope="col"> STATUS </th>
                <th scope="col" v-if="$can('edit', 'countries') || $can('delete', 'countries')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="country in countries"
                :key="country.id"
                style="height: 3rem;">

                <td> {{ country.id }} </td>
                <td class="text-wrap w-100">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      start
                      style="margin-top: -3px;"
                      size="40">
                      <VImg
                        :src="getFlagCountry(country)"
                        cover
                        @error="onCountryFlagError(country)"
                      />
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ country.name }}
                      </span>
                      <span class="text-sm text-disabled">
                        {{ getFlagCountry(country.id) }}
                      </span>
                    </div>
                  </div>
                </td>
                <td> {{ country.iso }} </td>
                <td> {{ country.iso3 }} </td>
                <td class="text-wrap">
                  <VChip
                    label
                    :color="resolveStatus(country.state.id)?.color"
                  >
                    {{ country.state.name }}
                  </VChip>
                </td> 
                <!-- 👉 Actions -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'countries') || $can('delete', 'countries')">      
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
                        v-if="$can('delete','countries') && country.state_id === 1"
                        @click="showActivateDialog(country)">
                        <template #prepend>
                          <VIcon icon="tabler-rosette-discount-check" />
                        </template>
                        <VListItemTitle>Aktivera</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('delete','countries') && country.state_id === 2"
                        @click="showActivateDialog(country)">
                        <template #prepend>
                          <VIcon icon="mdi-close-circle-outline" />
                        </template>
                        <VListItemTitle>Inaktivera</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'countries')"
                         @click="editCountry(country)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','countries')"
                        @click="showDeleteDialog(country)">
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
            <tfoot v-show="!countries.length">
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
              :total-visible="4"
              :length="totalPages"/>
          
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    <!-- 👉 Add New Country -->
    <AddNewCountryDrawer
      v-model:isDrawerOpen="isAddNewCountryDrawerVisible"
      :country="selectedCountry"
      @country-data="submitForm"/>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Ta bort land">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort land <strong>{{ selectedCountry.name }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeCountry">
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
      <VCard :title="selectedCountry.state_id === 1 ? 'Aktivera land' : 'Inaktivera land'">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill {{ selectedCountry.state_id === 1 ? 'aktivera' : 'inaktivera' }} land  <strong>{{ selectedCountry.name }}</strong>?.
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
<route lang="yaml">
  meta:
    action: view
    subject: countries
</route>