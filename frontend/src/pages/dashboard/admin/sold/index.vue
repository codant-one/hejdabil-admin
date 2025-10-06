<script setup>

import { useVehiclesStores } from '@/stores/useVehicles'
import { excelParser } from '@/plugins/csv/excelParser'
import { yearValidator, requiredValidator } from '@/@core/utils/validators'
import { themeConfig } from '@themeConfig'
import { formatNumber } from '@/@core/utils/formatters'
import show from "@/components/vehicles/show.vue";
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'

const vehiclesStores = useVehiclesStores()
const emitter = inject("emitter")

const userData = ref(null)
const role = ref(null)
const suppliers = ref([])
const supplier_id = ref(null)

const vehicles = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalVehicles = ref(0)
const isRequestOngoing = ref(true)
const isConfirmDeleteDialogVisible = ref(false)
const isVehicleDetailDialog = ref(false)
const selectedVehicle = ref({})

const year = ref(null)
const gearboxes = ref([])
const gearbox_id = ref(null)
const brands = ref([])
const brand_id = ref(null)
const models = ref([])
const model_id = ref(null)
const modelsByBrand = ref([])
const currencies = ref([])
const currency_id = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = vehicles.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = vehicles.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalVehicles.value } register`
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
    brand_id.value = null
    model_id.value = null
    year.value = null
    gearbox_id.value = null
    supplier_id.value = null
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'sale_date,id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    isSold: 1,
    state_id: 12,
    brand_id: brand_id.value,
    model_id: model_id.value,
    year: year.value,
    gearbox_id: gearbox_id.value,
    supplier_id: supplier_id.value
  }

  isRequestOngoing.value = 
    (searchQuery.value !== '' || year.value !== null)
    ? false 
    : true

  await vehiclesStores.fetchVehicles(data)

  brands.value = vehiclesStores.getBrands
  models.value = vehiclesStores.getModels
  gearboxes.value = vehiclesStores.getGearboxes
  vehicles.value = vehiclesStores.getVehicles
  totalPages.value = vehiclesStores.last_page
  totalVehicles.value = vehiclesStores.vehiclesTotalCount

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  if(role.value === 'SuperAdmin' || role.value === 'Administrator') {
    suppliers.value = vehiclesStores.getSuppliers
  }

  isRequestOngoing.value = false

}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const editVehicle = vehicleData => {
  router.push({ name : 'dashboard-admin-stock-edit-id', params: { id: vehicleData.id } })
}

const showVehicle = async (id) => {
  isVehicleDetailDialog.value = true
  selectedVehicle.value = vehicles.value.filter((element) => element.id === id )[0]
}

const showDeleteDialog = vehicleData => {
  isConfirmDeleteDialogVisible.value = true
  selectedVehicle.value = { ...vehicleData }
}

const getModels = computed(() => {
  return modelsByBrand.value.map((model) => {
    return {
      title: model.name,
      value: model.id
    }
  })
})

const selectBrand = brand => {
    if (brand) {
        let _brand = brands.value.find(item => item.id === brand)
    
        model_id.value = ''
        modelsByBrand.value = models.value.filter(item => item.brand_id === _brand.id)
    }
}

const removeVehicle = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await vehiclesStores.deleteVehicle(selectedVehicle.value.id)
  selectedVehicle.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Fordon borttagen!' : res.data.message,
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

const seeClient = clientData => {
  router.push({ name : 'dashboard-admin-clients-id', params: { id: clientData.id } })
}

const download = async(vehicle) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + vehicle.file);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = vehicle.file.replace('pdfs/', '');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

  } catch (error) {
    console.error('Error:', error);
  }
};

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1, state_id: 12}

  await vehiclesStores.fetchVehicles(data)

  let dataArray = [];
      
  vehiclesStores.getVehicles.forEach(element => {

    const bilinfo =
      (element.model?.brand?.name ?? '') + ' ' +
      (element.model?.name ?? '') +
      (element.year == null ? '' : ', ' + element.year);

    let data = {
      FÖRSÄLJNINGSDATUM: element.sale_date ?? '',
      BILINFO: bilinfo,
      INKÖPSPRIS: formatNumber(element.purchase_price ?? 0) + ' kr',
      KOSTNADER: formatNumber(element.costs.reduce((sum, item) => sum + parseFloat(item.value), 0) ?? 0),
      FÖRSÄLJNINGSPRIS: formatNumber(element.total_sale ?? 0) + ' kr',
      VINST: formatNumber(element.total_sale - element.purchase_price) + ' kr',
      KÖPAREN: element.client_sale?.fullname
    }

    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "vehicles", "csv");

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

        <Toaster />

        <VCard title="Filter">
          <VCardText>
            <VRow>
              <VCol cols="12" md="3">
                <VAutocomplete
                  v-model="brand_id"
                  label="Märke"
                  :items="brands"
                  :item-title="item => item.name"
                  :item-value="item => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"
                  @update:modelValue="selectBrand"
                  :menu-props="{ maxHeight: '300px' }"/>
              </VCol>
              <VCol cols="12" md="3">
                <VAutocomplete
                  v-model="model_id"
                  label="Modell"
                  :items="getModels"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"
                  :menu-props="{ maxHeight: '300px' }"/>
              </VCol>
              <VCol cols="12" md="3">
                <VTextField
                    v-model="year"
                    :rules="[yearValidator]"
                    label="Årsmodell"
                    clearable
                />
              </VCol>
              <VCol cols="12" md="3">
                <VAutocomplete
                  v-model="gearbox_id"
                  label="Biltyp"
                  :items="gearboxes"
                  :item-title="item => item.name"
                  :item-value="item => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"/>
              </VCol>
            </VRow>
          </VCardText>
          <VDivider />
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

            <VAutocomplete
                v-if="role === 'SuperAdmin' || role === 'Administrator'"
                v-model="supplier_id"
                placeholder="Leverantörer"
                :items="suppliers"
                :item-title="item => item.full_name"
                :item-value="item => item.id"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"/>

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
            </div>
          </VCardText>

          <VDivider />

          <VTable class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> Försäljningsdatum </th>
                <th scope="col"> Bil info </th>
                <th scope="col" class="text-end"> Inköpspris </th>
                <th scope="col" class="text-end"> Kostnader </th>
                <th scope="col" class="text-end"> Försäljningspris </th>
                <th scope="col" class="text-end"> Vinst </th>
                <th scope="col"> Köparen </th>
                <th scope="col" v-if="role === 'SuperAdmin' || role === 'Administrator'"> LEVERANTÖR </th>
                <th scope="col"> SKAPAD AV </th>  
                <th scope="col" v-if="$can('edit', 'stock') || $can('delete', 'stock')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="vehicle in vehicles"
                :key="vehicle.id"
                style="height: 3rem;">
                <td> {{ vehicle.sale_date }}</td>
                <td class="text-wrap cursor-pointer"  @click="showVehicle(vehicle.id)">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      v-if="vehicle.model?.brand?.logo"
                      size="38"
                      variant="tonal"
                      rounded
                      :image="themeConfig.settings.urlStorage + vehicle.model.brand.logo"
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
                      <span v-if="vehicle.model_id" class="font-weight-medium cursor-pointer text-primary">
                        {{ vehicle.model.brand.name }} {{ vehicle.model.name }}{{ vehicle.year === null ? '' :  ', ' + vehicle.year}}
                      </span>
                      <span class="text-sm text-disabled">
                        {{ vehicle.reg_num }}
                      </span>
                    </div>
                  </div>
                </td>                
                <td class="text-end"> {{ formatNumber(vehicle.purchase_price ?? 0) }} kr</td>
                <td class="text-end"> {{ formatNumber(vehicle.costs.reduce((sum, item) => sum + parseFloat(item.value), 0) ?? 0) }} kr </td>                
                <td class="text-end"> {{ formatNumber(vehicle.total_sale ?? 0) }} kr</td>
                <td class="text-end"> {{ formatNumber(vehicle.total_sale - vehicle.purchase_price) }} kr</td>
                <td class="text-wrap">
                  <div class="d-flex flex-column">
                    <span v-if="vehicle.client_sale.client_id !== null" class="font-weight-medium cursor-pointer text-primary" @click="seeClient(vehicle.client_sale.client)">
                      {{ vehicle.client_sale.fullname }} 
                    </span>
                    <span v-else class="font-weight-medium  text-primary">
                      {{ vehicle.client_sale.fullname }} 
                    </span>
                    <span class="text-sm text-disabled">{{ vehicle.client_sale.phone }}</span>
                  </div>
                </td>           
                <td class="text-wrap" v-if="role === 'SuperAdmin' || role === 'Administrator'">
                  <span class="font-weight-medium"  v-if="vehicle.supplier">
                    {{ vehicle.supplier.user.name }} {{ vehicle.supplier.user.last_name ?? '' }} 
                  </span>
                </td>
                <td class="text-wrap">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      :variant="vehicle.user.avatar ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="vehicle.user.avatar"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + vehicle.user.avatar"
                      />
                        <span v-else>{{ avatarText(vehicle.user.name) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ vehicle.user.name }} {{ vehicle.user.last_name ?? '' }} 
                      </span>
                      <span class="text-sm text-disabled">{{ vehicle.user.email }}</span>
                    </div>
                  </div>
                </td>     
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'stock') || $can('delete', 'stock')">      
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
                      <VListItem v-if="$can('edit', 'stock')" @click="showVehicle(vehicle.id)">
                        <template #prepend>
                          <VIcon icon="tabler-eye" />
                        </template>
                        <VListItemTitle>Visa</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('edit', 'stock')" @click="download(vehicle)">
                        <template #prepend>
                          <VIcon icon="mdi-cloud-download-outline" />
                        </template>
                        <VListItemTitle>Ladda ner</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('delete','stock')" @click="showDeleteDialog(vehicle)">
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
            <tfoot v-show="!vehicles.length">
              <tr>
                <td
                  colspan="10"
                  class="text-center">
                  Uppgifter ej tillgängliga
                </td>
              </tr>
            </tfoot>
          </VTable>
        
          <VDivider />

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

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Ta bort lagerfordon">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort fordon <strong>{{ selectedVehicle.reg_num }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeVehicle">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
    <show 
      v-model:isDrawerOpen="isVehicleDetailDialog"
      :vehicle="selectedVehicle"/>
  </section>
</template>

<style scope>

  .justify-content-center {
    justify-content: center !important;
  }

  .v-input--disabled svg rect {
    fill: #28C76F !important;
  }

  .v-input--disabled {
    pointer-events: visible !important;
    cursor: no-drop !important;
  }

  .search {
      width: 100%;
  }

  @media(min-width: 991px){
      .search {
          width: 20rem;
      }
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: sold
</route>