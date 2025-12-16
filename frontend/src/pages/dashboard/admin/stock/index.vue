<script setup>

import { useDisplay } from 'vuetify'
import { useVehiclesStores } from '@/stores/useVehicles'
import { useCarInfoStores } from '@/stores/useCarInfo'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { formatNumber } from '@/@core/utils/formatters'
import { yearValidator, requiredValidator } from '@/@core/utils/validators'
import { avatarText } from '@/@core/utils/formatters'
import show from "@/components/vehicles/show.vue";
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

import eyeIcon from "@/assets/images/icons/figma/eye.svg";
import editIcon from "@/assets/images/icons/figma/edit.svg";
import wasteIcon from "@/assets/images/icons/figma/waste.svg";

const vehiclesStores = useVehiclesStores()
const carInfoStores = useCarInfoStores()
const emitter = inject("emitter")

const { width: windowWidth } = useWindowSize();
const sectionEl = ref(null);
const hasLoaded = ref(false);

const filtreraMobile = ref(false);

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
const isConfirmCreateDialogVisible = ref(false)
const isVehicleDetailDialog = ref(false)
const selectedVehicle = ref({})
const state_id = ref(null)
const year = ref(null)

const gearboxes = ref([])
const gearbox_id = ref(null)
const brands = ref([])
const brand_id = ref(null)
const models = ref([])
const model_id = ref(null)
const modelsByBrand = ref([])

const plate = ref(null)
const chassis = ref(null)
const year_api = ref(null)
const generation = ref(null)
const refForm = ref()

const states = ref ([
  { id: 10, name: "På lager" },
  { id: 11, name: "På annons" },
  { id: 13, name: "Förmedlingsbil" }
])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const { mdAndDown } = useDisplay()
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end")

const isFilterDialogVisible = ref(false)

// 👉 Column visibility state
const isColumnsDialogVisible = ref(false)
const visibleColumns = ref([])
const didInitVisibleColumns = ref(false)

const columnOptions = [
  { id: 'purchase_date', label: 'Inköpsdatum' },
  { id: 'info', label: 'Bilinfo' },
  { id: 'reg_num', label: 'Regnr' },
  { id: 'purchase_price', label: 'Inköpspris' },
  { id: 'mileage', label: 'Miltal' },
  { id: 'comments', label: 'Anteckningar' },
  { id: 'state', label: 'Status' },
  { id: 'vat', label: 'VAT' },
  { id: 'control_inspection', label: 'Besiktigas' },
  { id: 'seller', label: 'Säljaren' },
  { id: 'supplier', label: 'Leverantör' },
  { id: 'created_by', label: 'Skapad av' },
]

const availableColumnOptions = computed(() => {
  const canSeeSupplier = role.value === 'SuperAdmin' || role.value === 'Administrator'
  return canSeeSupplier
    ? columnOptions
    : columnOptions.filter(opt => opt.id !== 'supplier')
})

const defaultColumns = computed(() => availableColumnOptions.value.slice(0, 5).map(opt => opt.id))

watch(
  () => role.value,
  () => {
    if (!didInitVisibleColumns.value && role.value) {
      const saved = localStorage.getItem('stock_visible_columns')
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
      localStorage.setItem('stock_visible_columns', JSON.stringify(val))
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

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = vehicles.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    vehicles.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalVehicles.value} resultat`;
  //return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalVehicles.value } register`
})

watch(() => plate.value, (val) => {
  plate.value = val === null ? null : val.toUpperCase()
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
    state_id.value = null
    brand_id.value = null
    model_id.value = null
    year.value = null
    gearbox_id.value = null
    supplier_id.value = null
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'purchase_date,id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    isSold: 0,
    state_id: state_id.value,
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

  vehicles.value.forEach(vehicle => {
    vehicle.checked = false;
  });

  hasLoaded.value = true;
  isRequestOngoing.value = false

}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const showDeleteDialog = vehicleData => {
  isConfirmDeleteDialogVisible.value = true
  selectedVehicle.value = { ...vehicleData }
}

const showVehicle = async (id) => {
  isVehicleDetailDialog.value = true
  selectedVehicle.value = vehicles.value.filter((element) => element.id === id )[0]
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

const editVehicle = vehicleData => {
  router.push({ name : 'dashboard-admin-stock-edit-id', params: { id: vehicleData.id } })
}

const sellVehicle = vehicleData => {
  router.push({ name : 'dashboard-admin-sold-id', params: { id: vehicleData.id } })
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

const onSubmit = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {

      isConfirmCreateDialogVisible.value = false
      isRequestOngoing.value = true

      const carRes = await carInfoStores.getLicensePlate(plate.value)

      if (carRes.success) {
        chassis.value = carRes.result.chassis
        year_api.value = carRes.result.model_year
        generation.value = carRes.result.generation
      }

      let formData = new FormData()

      formData.append('reg_num', plate.value)
      formData.append('chassis', chassis.value)
      formData.append('year', year_api.value)
      formData.append('generation', generation.value)

      vehiclesStores.addVehicle(formData)
        .then((res) => {
          router.push({ name : 'dashboard-admin-stock-edit-id', params: { id: res.data.data.vehicle.id } })  
        })
        .catch((err) => {
          //console.log('err', err)
          plate.value = null
            advisor.value = {
                type: 'error',
                message: err.message,
                show: true
            }

            setTimeout(() => {
              advisor.value = {
                type: '',
                message: '',
                show: false
              }
            }, 3000)

            isRequestOngoing.value = false
        })
    }
  })
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

  let data = { limit: -1 }

  await vehiclesStores.fetchVehicles(data)

  let dataArray = [];
      
  vehiclesStores.getVehicles.forEach(element => {

    const bilinfo =
      (element.model?.brand?.name ?? '') + ' ' +
      (element.model?.name ?? '') +
      (element.year == null ? '' : ', ' + element.year);

    let data = {
      INKÖPSDATUM: element.purchase_date ?? '',
      BILINFO: bilinfo,
      REGNR: element.reg_num,
      INKÖPSPRIS: formatNumber(element.purchase_price ?? 0) + ' kr',
      MILTAL: element.mileage === null ? '' : element.mileage + ' Mil',
      ANTECKNINGAR:  element.comments ?? '',
      STATUS: element.state.name,
      VAT: element.iva_purchase?.name,
      BESIKTIGAS: element.control_inspection ?? ''
    }

    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "vehicles", "csv");

  isRequestOngoing.value = false

}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
};

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
          <h2>I lager <span v-if="hasLoaded">({{ totalVehicles }})</span></h2>
        </div>

        <div class="d-flex gap-4">
          <VBtn
            class="btn-light w-auto"
            block
            @click="downloadCSV">
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

          <VBtn
            v-if="$can('create', 'stock')"
            class="btn-gradient"
            block
            @click="isConfirmCreateDialogVisible = true">
              <VIcon icon="custom-plus" size="24" />
              Lägg till en bil
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search" style="width: 480px !important">
          <VTextField
            v-model="searchQuery"
            placeholder="Sök"
            clearable
          />
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
          class="btn-white-2"
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
            density="compact"
            variant="outlined"
            :items="[10, 20, 30, 50]"/>
        </div>
      </VCardText>

      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="vehicles.length"
        class="pt-2 px-4 pb-6 text-no-wrap"
        style="border-radius: 0 !important"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col" v-if="isColVisible('purchase_date')"> Inköpsdatum </th>
            <th scope="col" v-if="isColVisible('info')"> Bilinfo</th>
            <th scope="col" v-if="isColVisible('reg_num')"> Regnr </th>
            <th scope="col" class="text-end" v-if="isColVisible('purchase_price')"> Inköpspris </th>
            <th scope="col" class="text-end" v-if="isColVisible('mileage')"> Miltal </th>
            <th scope="col" v-if="isColVisible('comments')"> Anteckningar </th>
            <th scope="col" v-if="isColVisible('state')"> Status </th>
            <th scope="col" v-if="isColVisible('vat')"> VAT </th>
            <th scope="col" v-if="isColVisible('control_inspection')"> Besiktigas </th>
            <th scope="col" v-if="isColVisible('seller')"> Säljaren </th>
            <th scope="col" v-if="(role === 'SuperAdmin' || role === 'Administrator') && isColVisible('supplier')"> LEVERANTÖR </th>
            <th scope="col" v-if="isColVisible('created_by')"> SKAPAD AV </th>  
            <th scope="col" v-if="$can('edit', 'stock') || $can('delete', 'stock')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody>
          <tr 
            v-for="vehicle in vehicles"
            :key="vehicle.id"
            style="height: 3rem;">
            <td v-if="isColVisible('purchase_date')"> {{ vehicle.purchase_date }} </td>
            <td class="cursor-pointer" v-if="isColVisible('info')" @click="showVehicle(vehicle.id)">
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
                  <span v-if="vehicle.model_id" class="font-weight-medium cursor-pointer" style="color: #008C91;">
                    {{ vehicle.model.brand.name }} {{ vehicle.model.name }}{{ vehicle.year === null ? '' :  ', ' + vehicle.year}}
                  </span>
                  <span class="text-sm text-disabled">
                    {{ vehicle.color }}
                  </span>
                </div>
              </div>
            </td>   
            <td v-if="isColVisible('reg_num')"> {{ vehicle.reg_num }} </td>             
            <td class="text-end" v-if="isColVisible('purchase_price')"> {{ formatNumber(vehicle.purchase_price ?? 0) }} kr </td>
            <td class="text-end" v-if="isColVisible('mileage')"> {{ vehicle.mileage === null ? '' : vehicle.mileage + ' Mil' }}</td>
            <td class="cursor-pointer" v-if="isColVisible('comments')">
              <VTooltip location="bottom">
                <template #activator="{ props }">
                  <span v-bind="props" v-if="vehicle.comments">
                    {{ truncateText(vehicle.comments) }}
                  </span>
                </template>
                <span>{{ vehicle.comments }}</span>
              </VTooltip>
            </td>
            <td v-if="isColVisible('state')"> {{ vehicle.state.name }} </td>
            <td v-if="isColVisible('vat')"> {{ vehicle.iva_purchase?.name }} </td>
            <td v-if="isColVisible('control_inspection')"> {{ vehicle.control_inspection }} </td>
            <td class="text-wrap" v-if="isColVisible('seller')">
              <div class="d-flex flex-column">
                <span v-if="vehicle.client_purchase?.client_id !== null" class="font-weight-medium cursor-pointer text-primary" @click="seeClient(vehicle.client_purchase?.client)">
                  {{ vehicle.client_purchase?.fullname }} 
                </span>
                <span v-else class="font-weight-medium  text-primary">
                  {{ vehicle.client_purchase?.fullname }} 
                </span>
                <span class="text-sm text-disabled">{{ vehicle.client_purchase?.phone }}</span>
              </div>
            </td> 
            <td class="text-wrap" v-if="(role === 'SuperAdmin' || role === 'Administrator') && isColVisible('supplier')">
              <span class="font-weight-medium"  v-if="vehicle.supplier">
                {{ vehicle.supplier.user.name }} {{ vehicle.supplier.user.last_name ?? '' }} 
              </span>
            </td>
            <td class="text-wrap" v-if="isColVisible('created_by')">
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
                      <img :src="eyeIcon" alt="Visa" class="mr-2" width="24" height="24" />
                    </template>
                    <VListItemTitle>Visa</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit', 'stock')" @click="sellVehicle(vehicle)">
                    <template #prepend>
                      <VIcon icon="mdi-car-cog" class="mr-2" size="24" />
                    </template>
                    <VListItemTitle>Sälj bil</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit', 'stock')" @click="editVehicle(vehicle)">
                    <template #prepend>
                      <img :src="editIcon" alt="Redigera" class="mr-2" width="24" height="24" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('edit', 'stock')" @click="download(vehicle)">
                    <template #prepend>
                      <VIcon icon="mdi-cloud-download-outline" class="mr-2" size="24" />
                    </template>
                    <VListItemTitle>Ladda ner</VListItemTitle>
                  </VListItem>
                  <VListItem v-if="$can('delete','stock')" @click="showDeleteDialog(vehicle)">
                    <template #prepend>
                      <img :src="wasteIcon" alt="Ta bort" class="mr-2" width="24" height="24" />
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
          icon="custom-steering-wheel"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Ditt fordonslager är tomt</div>
          <div class="empty-state-text">
            Registrera de fordon du har till salu för att enkelt hantera ditt lager och koppla dem till fakturor.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'stock')"
          @click="isConfirmCreateDialogVisible = true"
        >
          Lägg till fordon
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="vehicles.length && windowWidth < 1024"
      >
        <VExpansionPanel v-for="vehicle in vehicles" :key="vehicle.id" style="background-color: #F6F6F6 !important; border-radius: 12px !important; margin-bottom: 12px !important;">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <div class="d-flex align-center w-100">
                <VAvatar
                  v-if="vehicle.model?.brand?.logo"
                  size="40"
                  variant="tonal"
                  style="border-radius: 50% !important;"
                  class="me-3"
                  :image="themeConfig.settings.urlStorage + vehicle.model.brand.logo"
                />
                <VAvatar
                    v-else
                    size="40"
                    variant="tonal"
                    style="border-radius: 50% !important;"
                    color="secondary"
                    class="me-3"
                >
                  <VIcon size="24" icon="mdi-image-outline" />               
                </VAvatar>

                <div class="d-flex flex-column">
                    <span style="color: #009875; font-weight: 600;">Reg. Nr. {{ vehicle.reg_num }}</span>
                    <span class="text-body-2 text-medium-emphasis">
                        {{ vehicle.model?.brand?.name }} {{ vehicle.model?.name }} {{ vehicle.year ? `, ${vehicle.year}` : '' }}
                    </span>
                </div>
            </div>
          </VExpansionPanelTitle>
          
          <VExpansionPanelText>
             <div class="mb-4">
                <div class="text-caption text-medium-emphasis">Inköpspris</div>
                <div class="text-body-1">{{ formatNumber(vehicle.purchase_price ?? 0) }} kr</div>
             </div>
             
             <div class="mb-6">
                <div class="text-caption text-medium-emphasis">Miltal</div>
                <div class="text-body-1">{{ vehicle.mileage ? vehicle.mileage + ' Mil' : '-' }}</div>
             </div>

             <div class="d-flex gap-3">
                <VBtn class="btn-light flex-grow-1" @click="showVehicle(vehicle.id)">
                    <VIcon icon="custom-eye" size="24" class="me-2"/>
                    Se detaljer
                </VBtn>
                
                 <VMenu>
                    <template #activator="{ props }">
                      <VBtn v-bind="props" variant="outlined" color="secondary" class="px-0" style="min-width: 40px; width: 40px; border-radius: 50%; border-color: #A8AAAE;">
                        <VIcon icon="tabler-dots-vertical" size="24" color="secondary"/>
                      </VBtn>
                    </template>
                    <VList>
                      <VListItem v-if="$can('edit', 'stock')" @click="showVehicle(vehicle.id)">
                        <template #prepend>
                          <VIcon icon="tabler-eye" />
                        </template>
                        <VListItemTitle>Visa</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('edit', 'stock')" @click="sellVehicle(vehicle)">
                        <template #prepend>
                          <VIcon icon="mdi-car-cog" />
                        </template>
                        <VListItemTitle>Sälj bil</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="$can('edit', 'stock')" @click="editVehicle(vehicle)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
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
          prev-icon="custom-chevron-left"/>
      
      </VCardText>
    </VCard>

    <!-- Filter Dialog -->
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
          <VRow>
            <VCol cols="12" md="12" v-if="role === 'SuperAdmin' || role === 'Administrator'">
              <VAutocomplete
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
            <VCol cols="12" md="12">
              <VAutocomplete
                v-model="state_id"
                placeholder="Status"
                :items="states"
                :item-title="item => item.name"
                :item-value="item => item.id"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"/>
            </VCol>
            <VCol cols="12" md="12">
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
            <VCol cols="12" md="12">
              <VAutocomplete
                v-model="model_id"
                label="Modell"
                :items="getModels"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"
                :menu-props="{ maxHeight: '300px' }"/>
            </VCol>
            <VCol cols="12" md="12">
              <VTextField
                  v-model="year"
                  :rules="[yearValidator]"
                  label="Årsmodell"
                  clearable
              />
            </VCol>
            <VCol cols="12" md="12">
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

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="fetchData(true); isFilterDialogVisible = false">
              Rensa filter
          </VBtn>
          <VBtn class="btn-gradient" @click="isFilterDialogVisible = false">
              Visa resultat
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

  <!-- 👉 Columns -->
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
              <VBtn class="btn-blue" size="small" @click="selectDefaultColumns">Standard (5)</VBtn>
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

  <!-- 👉 Confirm Create -->
  <VDialog
    v-model="isConfirmCreateDialogVisible"
    persistent
    class="action-dialog" >
    <!-- Dialog close btn -->
      
    <VBtn
      icon
      class="btn-white close-btn"
      @click="isConfirmCreateDialogVisible = !isConfirmCreateDialogVisible"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <!-- Dialog Content -->
    <VForm
      ref="refForm"
      @submit.prevent="onSubmit">
      <VCard flat class="card-form">
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-lager" class="action-icon" />
          <div class="dialog-title">
            Lägg till en bil
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          <VLabel
              class="mb-1 text-body-2 text-high-emphasis"
              text="Reg. nummer"
            />
          <VTextField
              v-model="plate"
              :rules="[requiredValidator]"
              placeholder="ABC12X"
              style="text-transform: uppercase !important"
          />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmCreateDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" type="submit">
              Fortsätt
          </VBtn>
        </VCardText>
      </VCard>
    </VForm>
  </VDialog>

  <!-- 👉 Confirm Delete -->
  <VDialog
    v-model="isConfirmDeleteDialogVisible"
    persistent
    class="action-dialog" >
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
          Är du säker på att du vill radera fordonet?
        </div>
      </VCardText>
      <VCardText class="dialog-text">
        Är du säker att du vill ta bort fordon <strong>{{ selectedVehicle.reg_num }}</strong>?.
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn
          class="btn-light"
          @click="isConfirmDeleteDialogVisible = false">
            Avbryt
        </VBtn>
        <VBtn class="btn-gradient" @click="removeVehicle">
            Ja, radera
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
        <VListItem class="form pt-0" v-if="role === 'SuperAdmin' || role === 'Administrator'">
          <VAutocomplete
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
        <VListItem class="form">
          <VAutocomplete
            v-model="state_id"
            placeholder="Status"
            :items="states"
            :item-title="item => item.name"
            :item-value="item => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"/>
        </VListItem>
        <VListItem class="form pt-6">
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
        </VListItem>
        <VListItem class="form">
          <VAutocomplete
            v-model="model_id"
            label="Modell"
            :items="getModels"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            :menu-props="{ maxHeight: '300px' }"/>
        </VListItem>
        <VListItem class="form">
          <VTextField
            v-model="year"
            :rules="[yearValidator]"
            label="Årsmodell"
            clearable
          />
        </VListItem>
        <VListItem class="form">
          <VAutocomplete
            v-model="gearbox_id"
            label="Biltyp"
            :items="gearboxes"
            :item-title="item => item.name"
            :item-value="item => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"/>
        </VListItem>
        <VListItem class="form mt-5">
          <VBtn class="btn-gradient w-100" @click="filtreraMobile = false">
              Visa resultat
          </VBtn>
        </VListItem>
      </VList>
    </VCard>
  </VDialog>

  <show 
    v-model:isDrawerOpen="isVehicleDetailDialog"
    :vehicle="selectedVehicle"/>
  </section>
</template>

<style scope>
  .card-form {
    .v-list {
      padding: 28px 24px 40px !important;

      .v-list-item {
        margin-bottom: 0px;
        padding: 0px !important;
        gap: 0px !important;

        .v-input--density-compact {
          --v-input-control-height: 48px !important;
        }

        .v-select .v-field,
        .v-autocomplete .v-field {
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
            padding-top: 16px;
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
            @media (max-width: 991px) {
              top: 12px !important;
            }
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
    subject: stock
</route>