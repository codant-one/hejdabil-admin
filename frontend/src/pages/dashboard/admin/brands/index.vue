<script setup>

import { themeConfig } from '@themeConfig'
import { useBrandsStores } from '@/stores/useBrands'
import { excelParser } from '@/plugins/csv/excelParser'
import AddNewBrandDrawer from './AddNewBrandDrawer.vue' 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const brandsStores = useBrandsStores()
const emitter = inject("emitter")

const brands = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalBrands = ref(0)
const isRequestOngoing = ref(true)
const isAddNewBrandDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const selectedBrand = ref({})

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = brands.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = brands.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalBrands.value } märke`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewBrandDrawerVisible.value)
        selectedBrand.value = {}
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await brandsStores.fetchBrands(data)

  brands.value = brandsStores.getBrands
  totalPages.value = brandsStores.last_page
  totalBrands.value = brandsStores.brandsTotalCount

  isRequestOngoing.value = false
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const editBrand = brandData => {
    isAddNewBrandDrawerVisible.value = true
    selectedBrand.value = { ...brandData }
}

const showDeleteDialog = brandData => {
  isConfirmDeleteDialogVisible.value = true
  selectedBrand.value = { ...brandData }
}

const removeBrand = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await brandsStores.deleteBrand(selectedBrand.value.id)
  selectedBrand.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Märke raderat!' : res.data.message,
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

const submitForm = async (brand, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    brand.data.append('_method', 'PUT')
    submitUpdate(brand)
    return
  }

  submitCreate(brand.data)
}

const submitCreate = brandData => {

    brandsStores.addBrand(brandData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Märke skapat!',
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

const submitUpdate = brandData => {

    brandsStores.updateBrand(brandData)
        .then((res) => {
            if (res.data.success) {
                    advisor.value = {
                    type: 'success',
                    message: 'Märke uppdaterat!',
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

const seeUrl = (brand) => {
  window.open(brand.url, '_blank');
}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await brandsStores.fetchBrands(data)

  let dataArray = [];
      
  brandsStores.getBrands.forEach(element => {

    let data = {
      ID: element.id,
      NAMN: element.name,
      HEMSIDA: element.url
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "brands", "csv");

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
              <v-btn
                v-if="$can('create','brands')"
                prepend-icon="tabler-plus"
                 class="w-100 w-md-auto"
                @click="isAddNewBrandDrawerVisible = true">
                  Lägg till märke
              </v-btn>
            </div>
          </VCardText>

          <v-divider />

          <v-table class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> NAMN </th>
                <th scope="col" v-if="$can('edit', 'brands') || $can('delete', 'brands')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="brand in brands"
                :key="brand.id"
                style="height: 3rem;">

                <td> {{ brand.id }} </td>
                <td class="w-100">
                  <div class="d-flex align-center gap-x-4">
                    <VAvatar
                      v-if="brand.logo"
                      size="38"
                      variant="tonal"
                      rounded
                      :image="themeConfig.settings.urlStorage + brand.logo"
                    />
                    <VAvatar
                        v-else
                        size="38"
                        variant="tonal"
                        rounded
                        color="secondary"
                    >
                        <VIcon size="x-large" icon="mdi-image-outline" />                        
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="text-body-1 font-weight-medium text-high-emphasis">{{ brand.name }}</span>
                      <span class="text-body-2 cursor-pointer" @click="seeUrl(brand)">{{ brand.url }}</span>
                    </div>
                  </div>
                </td>
                <!-- 👉 Actions -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'brands') || $can('delete', 'brands')">      
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
                         v-if="$can('edit', 'brands')"
                         @click="editBrand(brand)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','brands')"
                        @click="showDeleteDialog(brand)">
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
            <tfoot v-show="!brands.length">
              <tr>
                <td
                  colspan="3"
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
    <!-- 👉 Add New Brand -->
    <AddNewBrandDrawer
      v-model:isDrawerOpen="isAddNewBrandDrawerVisible"
      :brand="selectedBrand"
      @brand-data="submitForm"/>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Ta bort märke">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort märke <strong>{{ selectedBrand.name }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeBrand">
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
    subject: brands
</route>