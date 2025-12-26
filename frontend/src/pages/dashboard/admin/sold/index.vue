<script setup>

import { useDisplay } from "vuetify";
import { useVehiclesStores } from '@/stores/useVehicles'
import { excelParser } from '@/plugins/csv/excelParser'
import { yearValidator } from '@/@core/utils/validators'
import { themeConfig } from '@themeConfig'
import { formatNumber } from '@/@core/utils/formatters'
import { formatNumberInteger } from '@/@core/utils/formatters'
import { avatarText } from '@/@core/utils/formatters'
import show from "@/components/vehicles/show.vue";
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

import eyeIcon from "@/assets/images/icons/figma/eye.svg";
import downloadIcon from "@/assets/images/icons/figma/download.svg";
import wasteIcon from "@/assets/images/icons/figma/waste.svg";

const { width: windowWidth } = useWindowSize();

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
const isFilterDialogVisible = ref(false);

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

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

// 👉 Computing pagination data
// const paginationData = computed(() => {
//   const firstIndex = vehicles.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
//   const lastIndex = vehicles.value.length + (currentPage.value - 1) * rowPerPage.value

//   return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalVehicles.value } register`
// })

const paginationData = computed(() => {
  const firstIndex = vehicles.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    vehicles.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalVehicles.value} resultat`;
  // return `Visar ${firstIndex} till ${lastIndex} av ${totalClients.value} register`;
});

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
      KOSTNADER: formatNumber(element.tasks.reduce((sum, item) => sum + parseFloat(item.cost), 0) ?? 0),
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
  <section class="page-section" ref="sectionEl">
    <LoadingOverlay :is-loading="isRequestOngoing" />

    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
    >
      {{ advisor.message }}
    </VSnackbar>  

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
        <div class="align-center font-blauer">
          <h2>Sålda fordon</h2>
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

        <div class="d-flex gap-4">
          <VBtn 
            class="btn-light w-auto" 
            block
            @click="downloadCSV">
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-2"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VBtn 
          class="btn-white-2" 
          v-if="role !== 'Supplier' && role !== 'User'"
          @click="isFilterDialogVisible = true"
        >
          <VIcon icon="custom-filter" size="24" />
          <span :class="windowWidth < 1024 ? 'd-none' : 'd-flex'">Filtrera efter</span>
        </VBtn>

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
        class="px-4 pb-6 text-no-wrap">
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col"> Försäljningsdatum </th>
            <th scope="col"> Bil info </th>
            <th scope="col" class="text-end"> Inköpspris </th>
            <th scope="col" class="text-end"> Kostnader </th>
            <th scope="col" class="text-end"> Försäljningspris </th>
            <th scope="col" class="text-end"> Vinst </th>
            <th scope="col" class="text-start"> Köparen </th>
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
            <td class="text-end"> {{ formatNumber(vehicle.tasks.reduce((sum, item) => sum + parseFloat(item.cost), 0) ?? 0) }} kr </td>                
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
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'stock') || $can('delete', 'stock')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>

                <VList>
                  <VListItem v-if="$can('edit', 'stock')" @click="showVehicle(vehicle.id)">
                    <template #prepend>
                      <img :src="eyeIcon" alt="See Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit', 'stock')" @click="download(vehicle)">
                    <template #prepend>
                      <img :src="downloadIcon" alt="See Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('delete','stock')" @click="showDeleteDialog(vehicle)">
                    <template #prepend>
                      <img :src="wasteIcon" alt="Delete Icon" class="mr-2" />
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

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="vehicles.length && $vuetify.display.mdAndDown"
      >
        <VExpansionPanel v-for="vehicle in vehicles" :key="vehicle.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
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
                <span class="text-sm text-disabled">
                  Regnr {{ vehicle.reg_num }}
                </span>
                <span v-if="vehicle.model_id" class="font-weight-medium cursor-pointer text-primary">
                  {{ vehicle.model.brand.name }} {{ vehicle.model.name }}{{ vehicle.year === null ? '' :  ', ' + vehicle.year}}
                </span>
              </div>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Inköpspris:</div>
              <div class="expansion-panel-item-value">
                {{ formatNumber(vehicle.purchase_price ?? 0) }} kr
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Mital:</div>
              <div class="expansion-panel-item-value">
                {{ formatNumberInteger(vehicle.mileage ?? 0) }} Mil
              </div>
            </div>
            <div class="d-flex">
              <VBtn class="btn-light flex-fill"  @click="showVehicle(vehicle.id)">
                <VIcon icon="custom-eye" size="24" />
                Se detaljer
              </VBtn>
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-light ms-4">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>

                <VList>
                  <VListItem v-if="$can('edit', 'stock')" @click="showVehicle(vehicle.id)">
                    <template #prepend>
                      <img :src="eyeIcon" alt="See Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit', 'stock')" @click="download(vehicle)">
                    <template #prepend>
                      <img :src="downloadIcon" alt="See Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('delete','stock')" @click="showDeleteDialog(vehicle)">
                    <template #prepend>
                      <img :src="wasteIcon" alt="Delete Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Ta bort</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
        <div v-if="!vehicles.length" class="text-center py-4">
          Uppgifter ej tillgängliga
        </div>
      </VExpansionPanels>
    

      <VCardText
        v-if="vehicles.length"
        :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
        class="align-center flex-wrap gap-4 pt-0 px-6"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <VPagination
          v-model="currentPage"
          size="small"
          :total-visible="5"
          :length="totalPages"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>
    </VCard>

    

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->

      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filled-waste" class="action-icon" />
          <div class="dialog-title">
            Radera sålt fordon?
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          <!-- Är du säker att du vill ta bort klienten
          <strong>{{ selectedClient.fullname }}</strong
          >? -->
          Detta raderar permanent posten för det sålda fordonet "{{ selectedVehicle.reg_num }}" från 
          din försäljningshistorik. Åtgärden kan inte ångras.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmDeleteDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeClient"> Ja, radera posten</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Filter Dialog -->
    <VDialog
      v-model="isFilterDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isFilterDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filter" class="action-icon" />
          <div class="dialog-title">Filtrera efter</div>
        </VCardText>
        
        <VCardText class="pt-0">
          <AppAutocomplete
            v-if="role !== 'Supplier'"
            prepend-icon="custom-profile"
            v-model="brand_id"
            placeholder="Märke"
            :items="brands"
            :item-title="(item) => item.name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user"
            :menu-props="{ maxHeight: '400px' }"
          />

          <AppAutocomplete
            v-if="role !== 'Supplier'"
            prepend-icon="custom-profile"
            v-model="model_id"
            placeholder="Modell"
            :items="models"
            :item-title="(item) => item.name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user"
            :menu-props="{ maxHeight: '400px' }"
          />

          <AppAutocomplete
            v-if="role !== 'Supplier'"
            prepend-icon="custom-profile"
            v-model="gearbox_id"
            placeholder="Biltyp"
            :items="gearboxes"
            :item-title="(item) => item.name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user"
            :menu-props="{ maxHeight: '400px' }"
          />

          <AppAutocomplete
            v-if="role !== 'Supplier'"
            prepend-icon="custom-profile"
            v-model="year"
            placeholder="Årsmodell"
            :items="vehicles"
            :item-title="(item) => item.year"
            :item-value="(item) => item.year"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user"
            :menu-props="{ maxHeight: '400px' }"
          />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-10">
          <VBtn class="btn-light" @click="isFilterDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="isFilterDialogVisible = false">
            Stäng
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
  .v-input--disabled svg rect {
    fill: #28C76F !important;
  }

  .v-input--disabled {
    pointer-events: visible !important;
    cursor: no-drop !important;
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: sold
</route>