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
import showMobile from "@/components/vehicles/showMobile.vue"
import router from '@/router'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import Toaster from "@/components/common/Toaster.vue";
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
const isMobile = ref(false)
const selectedVehicle = ref({})
const isFilterDialogVisible = ref(false);
const filtreraMobile = ref(false);

// 👉 Column visibility state
const isColumnsDialogVisible = ref(false)
const visibleColumns = ref([])
const didInitVisibleColumns = ref(false)

const columnOptions = [
  { id: 'sale_date', label: 'Försäljningsdatum' },
  { id: 'info', label: 'Bilinfo' },
  { id: 'reg_num', label: 'Reg nr' },
  { id: 'purchase_price', label: 'Inköpspris' },  
  { id: 'sale_price', label: 'Försäljningspris' },
  { id: 'profit', label: 'Vinst' },
  { id: 'costs', label: 'Kostnader' },
  { id: 'buyer', label: 'Köparen' },
  { id: 'supplier', label: 'Leverantör' },
  { id: 'created_by', label: 'Skapad av' },
]

const year = ref(null)
const gearboxes = ref([])
const gearbox_id = ref(null)
const brands = ref([])
const brand_id = ref(null)
const models = ref([])
const model_id = ref(null)
const modelsByBrand = ref([])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const availableColumnOptions = computed(() => {
  const canSeeSupplier = role.value === 'SuperAdmin' || role.value === 'Administrator'
  return canSeeSupplier
    ? columnOptions
    : columnOptions.filter(opt => opt.id !== 'supplier')
})

const defaultColumns = computed(() => availableColumnOptions.value.slice(0, 6).map(opt => opt.id))

watch(
  () => role.value,
  () => {
    if (!didInitVisibleColumns.value && role.value) {
      const saved = localStorage.getItem('sold_visible_columns')
      const allowed = new Set(availableColumnOptions.value.map(o => o.id))
      const initial = saved ? JSON.parse(saved).filter((id) => allowed.has(id)) : defaultColumns.value
      visibleColumns.value = initial
      didInitVisibleColumns.value = true
    }
  },
  { immediate: true }
)

watch(
  () => visibleColumns.value,
  (val) => {
    if (didInitVisibleColumns.value) {
      localStorage.setItem('sold_visible_columns', JSON.stringify(val))
    }
  },
  { deep: true }
)

const isColVisible = (id) => visibleColumns.value.includes(id)

const selectAllColumns = () => {
  visibleColumns.value = availableColumnOptions.value.map(o => o.id)
}

const selectDefaultColumns = () => {
  visibleColumns.value = defaultColumns.value
}

const clearColumns = () => {
  visibleColumns.value = []
}

const sectionEl = ref(null);
const hasLoaded = ref(false);

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

// 👉 Computing pagination data
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

  hasLoaded.value = true;
  isRequestOngoing.value = false

}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const showVehicle = async (id, mobile = false) => {
  isVehicleDetailDialog.value = true
  isMobile.value = mobile
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

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
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
      KOSTNADER: formatNumber((element.tasks ?? []).reduce((sum, item) => sum + parseFloat(item.cost), 0)),
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
function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});
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

    <Toaster />

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
        <div class="align-center font-blauer">
          <h2>Sålda fordon <span v-if="hasLoaded">({{ vehicles.length }})</span></h2>
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

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <VBtn 
          class="btn-white-2 px-3"
          :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"
          @click="isFilterDialogVisible = true"
        >
          <VIcon icon="custom-filter" size="24" />
          <span :class="windowWidth < 1024 ? 'd-none' : 'd-flex'">Filtrera efter</span>
        </VBtn>

        <VBtn 
          class="btn-white-2 px-3"
          :class="windowWidth >= 1024 ? 'd-none' : 'd-flex'"
          @click="filtreraMobile = true"
        >
          <VIcon icon="custom-filter" size="24" />
        </VBtn>

        <VBtn
          class="btn-white-2"
          :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"
          @click="isColumnsDialogVisible = true">
          <VIcon icon="custom-column" size="24" />
          <span>Kolumner</span>
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
        v-show="vehicles.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th class="text-center" scope="col" v-if="isColVisible('sale_date')"> Försäljningsdatum </th>
            <th scope="col" v-if="isColVisible('info')"> Bilinfo </th>
            <th class="text-center" scope="col" v-if="isColVisible('reg_num')"> Reg nr </th>
            <th class="text-center" scope="col" v-if="isColVisible('purchase_price')"> Inköpspris </th>
            <th class="text-center" scope="col" v-if="isColVisible('sale_price')"> Försäljningspris </th>
            <th class="text-center" scope="col" v-if="isColVisible('profit')"> Vinst </th>
            <th class="text-center" scope="col" v-if="isColVisible('costs')"> Kostnader </th>
            <th scope="col" class="text-start" v-if="isColVisible('buyer')"> Köparen </th>
            <th scope="col" v-if="(role === 'SuperAdmin' || role === 'Administrator') && isColVisible('supplier')"> Leverantör </th>
            <th scope="col" v-if="isColVisible('created_by')"> Skapad av </th>  
            <th scope="col" v-if="$can('edit', 'stock') || $can('delete', 'stock')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="vehicles.length">
          <tr 
            v-for="vehicle in vehicles"
            :key="vehicle.id"
            style="height: 3rem;">
            <td class="text-center" v-if="isColVisible('sale_date')"> {{ vehicle.sale_date }}</td>
            <td class="cursor-pointer" v-if="isColVisible('info')" @click="showVehicle(vehicle.id, false)">
              <div class="d-flex align-center gap-x-3"> 
                <VAvatar
                  v-if="vehicle.model?.brand?.logo"
                  size="24"
                  variant="tonal"
                  :image="themeConfig.settings.urlStorage + vehicle.model.brand.logo"
                />
                <VAvatar
                    v-else
                    size="24"
                    color="#D9D9D9"
                >              
                </VAvatar>
                <div class="d-flex flex-column">
                <VTooltip 
                    v-if="vehicle.car_name && vehicle.car_name.length > 25"
                    location="bottom"
                    max-width="300">
                    <template #activator="{ props }">
                      <span v-bind="props" class="font-weight-medium cursor-pointer text-aqua">
                        {{ truncateText(vehicle.car_name, 25) }}
                      </span>
                    </template>
                    <span>{{ vehicle.car_name }}</span>
                  </VTooltip>
                  <template v-else>
                    <span v-if="vehicle.model_id" class="font-weight-medium cursor-pointer text-aqua">
                      {{ vehicle.car_name}}
                    </span>
                  </template>                  
                </div>
              </div>
            </td>                
            <td class="text-center" v-if="isColVisible('reg_num')"> {{ vehicle.reg_num }} </td>             
            <td class="text-center" v-if="isColVisible('purchase_price')"> {{ formatNumber(vehicle.purchase_price ?? 0) }} kr</td>
            
            <td class="text-center" v-if="isColVisible('sale_price')"> {{ formatNumber(vehicle.total_sale ?? 0) }} kr</td>
            <td class="text-center" v-if="isColVisible('profit')">
              <span v-if="vehicle.purchase_price === null"> 0.00 kr </span>
              <span v-else>
                {{ formatNumber(vehicle.total_sale - vehicle.purchase_price - (vehicle.tasks ?? []).filter(t => t.is_cost == 1).reduce((sum, item) => sum + parseFloat(item.cost), 0)) }} kr
              </span>              
            </td>         
            <td class="text-center" v-if="isColVisible('costs')"> {{ formatNumber((vehicle.tasks ?? []).filter(t => t.is_cost == 1).reduce((sum, item) => sum + parseFloat(item.cost), 0)) }} kr </td>                
            <td style="width: 1%; white-space: nowrap" v-if="isColVisible('buyer')">
              <div v-if="vehicle.client_sale" class="d-flex flex-column">
                <span v-if="vehicle.client_sale.client_id !== null" class="font-weight-medium cursor-pointer text-aqua" @click="seeClient(vehicle.client_sale.client)">
                  {{ vehicle.client_sale.fullname }} 
                </span>
                <span v-else class="font-weight-medium text-aqua">
                  {{ vehicle.client_sale.fullname }} 
                </span>
                <span class="text-sm text-disabled">{{ vehicle.client_sale.phone }}</span>
              </div>
              <span v-else class="text-sm text-disabled">—</span>
            </td> 
            <td style="width: 1%; white-space: nowrap" v-if="(role === 'SuperAdmin' || role === 'Administrator') && isColVisible('supplier')">
              <span v-if="vehicle.supplier">
                {{ vehicle.supplier.user.name }}
                {{ vehicle.supplier.user.last_name ?? "" }}
              </span>
            </td>            
            <td style="width: 1%; white-space: nowrap" v-if="isColVisible('created_by')">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  :variant="vehicle.user.avatar ? 'outlined' : 'tonal'"
                  size="38"
                >
                  <VImg
                    v-if="vehicle.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + vehicle.user.avatar"
                  />
                  <span v-else>{{ avatarText(vehicle.user.name) }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ vehicle.user.name }} {{ vehicle.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="vehicle.user.email && vehicle.user.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(vehicle.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ vehicle.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ vehicle.user.email }}</span>
                  </span>
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
                  <VListItem v-if="$can('edit', 'stock')" @click="showVehicle(vehicle.id, false)">
                    <template #prepend>
                      <img :src="eyeIcon" alt="See Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit', 'stock')" @click="download(vehicle)" class="d-none">
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
      </VTable>

      <div
        v-if="!isRequestOngoing && hasLoaded && !vehicles.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-suv"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga sålda fordon än</div>
          <div class="empty-state-text">
             När du markerar ett fordon som sålt från ditt lager kommer det att visas i den här listan för din historik.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          @click="router.push({ name : 'dashboard-admin-stock' })"
        >
          Gå till I lager
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="vehicles.length && $vuetify.display.mdAndDown"
      >
        <VExpansionPanel v-for="vehicle in vehicles" :key="vehicle.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
             <div class="d-flex align-center w-100">
                <VAvatar
                  v-if="vehicle.model?.brand?.logo"
                  size="32"
                  variant="tonal"
                  class="me-3"
                  :image="themeConfig.settings.urlStorage + vehicle.model.brand.logo"
                />
                <VAvatar
                    v-else
                    size="32"
                    color="#D9D9D9"
                    class="me-3"
                >            
                </VAvatar>

                <div class="d-flex flex-column gap-1">
                    <span class="text-aqua">Reg. nr. {{ vehicle.reg_num }}</span>
                    <span class="text-neutral-3">
                        {{ [vehicle.model?.brand?.name, vehicle.model?.name].filter(Boolean).join(' ') }}{{ vehicle.year ? `, ${vehicle.year}` : '' }}
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
              <VBtn class="btn-light flex-fill"  @click="showVehicle(vehicle.id, true)">
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
                  <VListItem v-if="$can('edit', 'stock')" @click="download(vehicle)" class="d-none">
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
          :total-visible="4"
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
          Detta raderar permanent posten för det sålda fordonet <strong>"{{ selectedVehicle.reg_num }}"</strong> från 
          din försäljningshistorik. Åtgärden kan inte ångras.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmDeleteDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeVehicle"> Ja, radera posten</VBtn>
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

      <VCard flat class="card-form">
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filter" class="action-icon" />
          <div class="dialog-title">
            Filtrera
          </div>
        </VCardText>
        
        <VCardText class="pt-0">
          <VRow class="pt-3">
            <VCol 
              cols="12" md="12" 
              v-if="role === 'SuperAdmin' || role === 'Administrator'"
              class="pb-0">
              <AppAutocomplete
                prepend-icon="custom-profile"
                v-model="supplier_id"
                placeholder="Leverantörer"
                :items="suppliers"
                :item-title="item => item.full_name"
                :item-value="item => item.id"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"
                class="selector-user selector-truncate"
              />
            </VCol>
            <VCol cols="12" md="12" class="pb-0">
              <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Märke" />
              <AppAutocomplete
                v-model="brand_id"
                :items="brands"
                :item-title="item => item.name"
                :item-value="item => item.id"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"
                @update:modelValue="selectBrand"
                :menu-props="{ maxHeight: '300px' }"/>
            </VCol>
            <VCol cols="12" md="12" class="pb-0">
              <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modell" />
              <AppAutocomplete
                v-model="model_id"
                :items="getModels"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"
                :menu-props="{ maxHeight: '300px' }"/>
            </VCol>
            <VCol cols="12" md="12" class="pb-0">
              <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Årsmodell" />
              <VTextField
                v-model="year"
                :rules="[yearValidator]"
                clearable
            />
            </VCol>
            <VCol cols="12" md="12">
              <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Biltyp" />
              <AppAutocomplete
                v-model="gearbox_id"
                :items="gearboxes"
                :item-title="item => item.name"
                :item-value="item => item.id"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"/>
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0">
          <VBtn class="btn-light" @click="isFilterDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="isFilterDialogVisible = false">
            Stäng
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile Filter Dialog -->
    <VDialog
      v-model="filtreraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard class="card-form">
        <VList>
          <VListItem class="form py-0" v-if="role === 'SuperAdmin' || role === 'Administrator'">
            <AppAutocomplete
              prepend-icon="custom-profile"
              v-model="supplier_id"
              placeholder="Leverantörer"
              :items="suppliers"
              :item-title="item => item.full_name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              class="selector-user selector-truncate"
            />
          </VListItem>
          <VListItem class="form pt-6">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Märke" />
            <AppAutocomplete
              v-model="brand_id"
              :items="brands"
              :item-title="item => item.name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              @update:modelValue="selectBrand"
              :menu-props="{ maxHeight: '300px' }"/>
          </VListItem>
          <VListItem class="form">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modell" />
            <AppAutocomplete
              v-model="model_id"
              :items="getModels"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              :menu-props="{ maxHeight: '300px' }"/>
          </VListItem>
          <VListItem class="form">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Årsmodell" />
            <VTextField
              v-model="year"
              :rules="[yearValidator]"
              clearable
            />
          </VListItem>
          <VListItem class="form">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Biltyp" />
            <AppAutocomplete
              v-model="gearbox_id"
              :items="gearboxes"
              :item-title="item => item.name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"/>
          </VListItem>
          <VListItem class="form mt-5">
            <VBtn class="btn-gradient w-100" @click="filtreraMobile = false">
                Tillämpa
            </VBtn>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Columns Dialog -->
    <VDialog
      v-model="isColumnsDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isColumnsDialogVisible = !isColumnsDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-column" class="action-icon" />
          <div class="dialog-title">
            Välj kolumner
          </div>
        </VCardText>
        <VCardText class="pt-0">
          <VRow>
            <VCol cols="12">
              <div class="d-flex gap-2 flex-wrap">
                <VBtn class="btn-gradient" size="small" @click="selectAllColumns">Alla</VBtn>
                <VBtn class="btn-blue" size="small" @click="selectDefaultColumns">Standard (6)</VBtn>
                <VBtn class="btn-light" size="small" @click="clearColumns">Rensa</VBtn>
              </div>
            </VCol>
            <VCol cols="12">
              <VCheckbox
                v-for="opt in availableColumnOptions"
                :key="opt.id"
                :label="opt.label"
                :value="opt.id"
                v-model="visibleColumns"
                density="comfortable"
                hide-details
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0">
          <VBtn class="btn-light" @click="isColumnsDialogVisible = false">Stäng</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <show 
      v-if="!isMobile"
      v-model:isDrawerOpen="isVehicleDetailDialog"
      :vehicle="selectedVehicle"/>

    <showMobile 
      v-else
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
<style lang="scss">
  .card-form {
    .v-list {
      padding: 28px 24px 40px !important;

      .v-list-item {
        margin-bottom: 0px;
        padding: 4px 0 !important;
        gap: 0px !important;

        .v-input--density-compact {
          --v-input-control-height: 48px !important;
        }

        .v-select .v-field,
        .v-autocomplete .v-field {

          .v-select__selection, .v-autocomplete__selection {
            align-items: center;
          }

          .v-field__input > input {
            top: 0px;
            left: 0px;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }

        .selector-user {
          .v-input__control {
            padding-top: 0 !important;
          }
          .v-input__prepend, .v-input__append {
            padding-top: 12px !important;
          }
        }

        .v-text-field {
          .v-input__control {
            padding-top: 0;
            input {
              min-height: 48px;
              padding: 12px 16px;
            }
          }
        }
      }
    }
    & .v-input {
      & .v-input__control {
        .v-field {
          background-color: #f6f6f6;
          .v-field-label {
            top: 12px !important;
          }
        }
      }
    }
  }

  .dialog-bottom-full-width {
    .v-card {
      border-radius: 24px 24px 0 0 !important;
    }
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: sold
</route>