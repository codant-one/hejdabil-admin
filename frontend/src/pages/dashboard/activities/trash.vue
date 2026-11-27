<script setup>

import { reactive, ref, toRaw } from "vue";
import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'
import { formatDate, formatDateTime, formatDateYMD } from '@/@core/utils/formatters'
import { useActivitiesStore } from '@/stores/useActivities'
import { canWithPlan } from '@/@layouts/plugins/casl'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const { width: windowWidth } = useWindowSize();

const activitiesStore = useActivitiesStore()
const emitter = inject("emitter")

const { mdAndDown } = useDisplay();

const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const sectionEl = ref(null);

const advisor = ref({
  type: '',
  message: '',
  show: false
})


const registers = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRegisters = ref(0)
const isRequestOngoing = ref(true)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmRestoreDialogVisible = ref(false)
const isConfirmAllDeleteDialogVisible = ref(false)
const isDialogOpen = ref(false)
const leaveContext = ref(null); // 'mobile' | 'route' | null
const hasLoaded = ref(false);
const isMobileActionDialogVisible = ref(false);
const selectedRegister = ref({})
const filtreraMobile = ref(false);
const state_id = ref(null)

const userData = ref(null)
const user_id = ref(null)
const role = ref(null)
const suppliers = ref([])
const supplier_id = ref(null)
const users = ref([])
const userId = ref(null)
const module = ref(null)

const modules = reactive([
  { name: 'Användare', id: 'users_admin', checked: false },
  { name: 'Roller', id: 'roles', checked: false },
  { name: 'Mitt team', id: 'users', checked: false },
  { name: 'Leverantör', id: 'suppliers', checked: false },
  { name: 'Admin faktura', id: 'suppliers_invoices', checked: false },
  { name: 'Kunder', id: 'clients', checked: false },
  { name: 'Fakturor', id: 'billings', checked: false },
  { name: 'Fordonslager', id: 'vehicles', checked: false },
  { name: 'Avtal', id: 'agreements', checked: false },
  { name: 'Dokument', id: 'documents', checked: false },
  { name: 'Swish', id: 'payouts', checked: false },
  { name: 'Märke', id: 'brands', checked: false },
  { name: 'Modell', id: 'models', checked: false },
  { name: 'Attribut fakturor', id: 'invoices', checked: false },
  { name: 'Valuta', id: 'currencies', checked: false },
  { name: 'Land', id: 'countries', checked: false },
  { name: 'Planer', id: 'plans', checked: false },
  { name: 'Hjälpkategorier', id: 'help_categories', checked: false },
  { name: 'Hjälpvideor', id: 'help_videos', checked: false }
])


const MODULE_PERMISSION_SUBJECTS = {
  users_admin: ['users-admin'],
  roles: ['roles'],
  users: ['my-team'],
  suppliers: ['suppliers'],
  clients: ['clients'],
  billings: ['billings'],
  currencies: ['currencies'],
  invoices: ['invoices'],
  suppliers_invoices: ['suppliers-invoices'],
  vehicles: ['stock', 'sold'],
  agreements: ['agreements'],
  documents: ['signed-documents'],
  payouts: ['payouts'],
  notes: ['notes'],
  countries: ['countries'],
  plans: ['plans'],
  help_categories: ['help-categories'],
  help_videos: ['help-videos']
}

const activityModuleLabels = {
  users: 'Mitt team',
  users_admin: 'Användare',
  roles: 'Roller',
  agreements: 'Avtal',
  billings: 'Fakturor',
  invoices: 'Attribut fakturor',
  clients: 'Kunder',
  suppliers: 'Leverantör',
  suppliers_invoices: 'Admin faktura',
  documents: 'Dokument',
  notes: 'Mina Värderingar',
  comment_notes: 'Mina Värderingar',
  payouts: 'Swish',
  vehicles: 'Fordonslager',
  brands: 'Märke',
  models: 'Modell',
  currencies: 'Valuta',
  countries: 'Land',
  plans: 'Planer',
  help_categories: 'Hjälpkategorier',
  help_videos: 'Hjälpvideor'
}

const activityModuleSingularLabels = {
  users_admin: 'Användare',
  users: 'Mitt team',
  roles: 'Roller',
  agreements: 'Avtal',
  billings: 'Faktura',
  invoices: 'Attribut faktura',
  clients: 'Kund',
  suppliers: 'Leverantör',
  suppliers_invoices: 'Faktura',
  documents: 'Dokument',
  notes: 'Anteckning',
  comment_notes: 'Kommentar',
  payouts: 'Utbetalning',
  vehicles: 'Fordon',
  brands: 'Märke',
  models: 'Modell',
  currencies: 'Valuta',
  countries: 'Land',
  plans: 'Planer',
  help_categories: 'Hjälpkategorier',
  help_videos: 'Hjälpvideor'
}

const hasModuleAccess = moduleId => {
  const subjects = MODULE_PERMISSION_SUBJECTS[moduleId] ?? []

  if (!subjects.length)
    return true

  return subjects.some(subject => canWithPlan('view', subject))
}

const availableModules = computed(() => modules.filter(item => hasModuleAccess(item.id)))

watch(availableModules, currentModules => {
  if (!module.value)
    return

  const isCurrentModuleAllowed = currentModules.some(item => item.id === module.value)

  if (!isCurrentModuleAllowed)
    module.value = null
}, { immediate: true })

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = registers.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = registers.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalRegisters.value} resultat`;

})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isDialogOpen.value)
        selectedRegister.value = {}
})

watch(searchQuery, () => {
  currentPage.value = 1
}, { flush: 'sync' })

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    user_id.value = userData.value ? userData.value.id : null

    if(cleanFilters === true) {
        searchQuery.value = ''
        rowPerPage.value = 10
        currentPage.value = 1;
        supplier_id.value = null;
        user_id.value = null;
        userId.value = null;
        module.value = null;
    }

    const searchTerm = searchQuery.value?.trim().toLowerCase()
    const matchingModule = searchTerm
        ? availableModules.value.find(item => item.name.toLowerCase().includes(searchTerm))
        : null

    let data = {
        search: searchQuery.value,
        orderByField: 'id',
        orderBy: 'desc',
        limit: rowPerPage.value,
        page: currentPage.value,
        supplier_id: supplier_id.value,
        user_id: userId.value,
        state_id: 2, //Actives
        // user: searchQuery.value ?? null,
        // title: searchQuery.value ?? null,
        action: 'delete',
        module: module.value ?? matchingModule?.id,
    }

    isRequestOngoing.value = searchQuery.value !== '' ? false : true

    role.value = userData.value?.roles?.[0]?.name ?? null

    await activitiesStore.fetchActivities(data)

    registers.value = Array.isArray(activitiesStore.getActivities) ? activitiesStore.getActivities : []
    totalPages.value = activitiesStore.last_page
    totalRegisters.value = activitiesStore.activitiesTotalCount

    if(role.value === 'SuperAdmin' || role.value === 'Administrator') {
        suppliers.value = activitiesStore.getSuppliers
    }

    users.value = activitiesStore.getUsers

    isRequestOngoing.value = false
    hasLoaded.value = true
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const updateStateId = (newModuleId) => {
  const currentModule = modules.find(item => item.id === newModuleId)

  if (!currentModule)
    return

 currentModule.checked = !currentModule.checked

 if (state_id.value === newModuleId) {
    newModuleId = null;
  }

  state_id.value = newModuleId;
  module.value = newModuleId;
  filtreraMobile.value = false;
};

const resolveStatus = deleted_at => {
  
  if (!deleted_at)
    return { class: 'warning', days: 0, text: 'dagar' }

  const deletedDate = new Date(deleted_at)
  const now = new Date()
  const diffInDays = Math.floor((now - deletedDate) / (1000 * 60 * 60 * 24))

  var text = diffInDays > 1 || diffInDays === 0 ? 'dagar' : 'dag'

  if (diffInDays > 5)
    return { class: 'days', days: diffInDays, text: text }
  return { class: 'warning', days: diffInDays, text: text }
}


const showRestoreDialog = registerData => {
  isConfirmRestoreDialogVisible.value = true
  selectedRegister.value = { ...registerData }
}

const restoreRegister = async () => {
  isConfirmRestoreDialogVisible.value = false
  let res = null;
  await activitiesStore.restoreActivity(selectedRegister.value.id)
    .then(r => {
      res = r;
    }).catch(err => {
      res = err;
    });

  selectedRegister.value = {}

  advisor.value = {
    type: res?.data?.success ? 'success' : 'error',
    message: res?.data?.success ? 'Register återställd!' : res?.message,
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
  
const showDeleteDialog = registerData => {
  isConfirmDeleteDialogVisible.value = true
  selectedRegister.value = { ...registerData }
}

const removeRegister = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = null;
  await activitiesStore.deleteActivity(selectedRegister.value.id)
    .then(r => {
      res = r;
    }).catch(err => {
      res = err;
    });

  selectedRegister.value = {}

  advisor.value = {
    type: res?.data?.success ? 'success' : 'error',
    message: res?.data?.success ? 'Register borttagen!' : res?.message,
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

const canBePermanentlyDeleted = registerData => {
  let notForceDelete = [
    'users',
    'users_admin',
    'clients',
  ]
  return !notForceDelete.includes(registerData.entity_type);
}

const showAllDeleteDialog = () => {
  isConfirmAllDeleteDialogVisible.value = true
}

const removeAllRegisters = async () => {
  isConfirmAllDeleteDialogVisible.value = false
//   let res = await activitiesStore.deleteAllActivities()
//   selectedRegister.value = {}

//   advisor.value = {
//     type: res.data.success ? 'success' : 'error',
//     message: res.data.success ? 'Register borttagen!' : res.data.message,
//     show: true
//   }

//   await fetchData()

//   setTimeout(() => {
//     advisor.value = {
//       type: '',
//       message: '',
//       show: false
//     }
//   }, 3000)

  return true
}

const handleMobileDialogUpdate = (val) => {
  if (val === false && isRegisterFormEdited.value) {
    // keep dialog open and show confirm
    isDialogOpen.value = true;
    isConfirmLeaveVisible.value = true;
    leaveContext.value = 'mobile';
    return;
  }
  isDialogOpen.value = val;
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

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
        <div class="align-center font-blauer">
          <h2>Papperskorg <span v-if="hasLoaded">({{ totalRegisters }})</span></h2>

            <div
                class="mb-7 text-wrap subtitle-text text-neutral-3 w-60 mt-4"
            >
                Borttagna objekt sparas här i 30 dagar innan de tas bort permanent. Du kan återställa dem när som helst under den perioden.
            </div>
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>
        
      </VCardTitle>

      <VCardText
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.mdAndDown ? 'p-6' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        

        <VBtn
          v-if="hasLoaded"
          class="btn-white-2 px-3"
          @click="showAllDeleteDialog"
          :class="windowWidth < 1023 ? 'd-none' : 'd-flex'"
        >
          <VIcon icon="custom-clean" size="24" />
          <span class="d-none d-md-block">Töm papperskorgen</span>
        </VBtn>

        <VBtn
          class="btn-white-2 px-3"
          @click="filtreraMobile = true"
          v-if="$vuetify.display.mdAndDown"
        >
          <VIcon icon="custom-filter" size="24" />
          <span class="d-none d-md-block">Filtrera efter</span>
        </VBtn>

        <VMenu v-if="!$vuetify.display.mdAndDown">
          <template #activator="{ props }">
            <VBtn class="btn-white-2 px-2" v-bind="props">
              <VIcon icon="custom-filter" size="24" />
              <span class="d-none d-md-block">Filtrera efter</span>
            </VBtn>
          </template>
          <VList>
            <VListItem 
                v-for="module in availableModules"
                :key="module.id"
                @click="updateStateId(module.id)"
            >
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="module.id === state_id"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>{{ module.name }}</VListItemTitle>
            </VListItem>

            <!-- <VListItem @click="updateStateId(2)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 2"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Aktiv</VListItemTitle>
            </VListItem> -->
          </VList>
        </VMenu>

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
        v-show="registers.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col"> Typ </th>
            <th scope="col"> Mottagare </th>
            <th scope="col"> Borttagen av </th>
            <th scope="col"> Borttagen datum</th>
            <th scope="col" class="text-center"> Status </th>
            <th scope="col" v-if="$can('edit', 'activities') || $can('delete', 'activities')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="registers.length">
          <tr 
            v-for="register in registers"
            :key="register.id"
            style="height: 3rem;">

            <td> {{ activityModuleLabels[register.entity_type] ?? register.entity_type }} </td>
            <td> {{ register.metadata.title ?? register.title }} </td>
            <td> {{ register.user.name + ' ' + register.user.last_name }} </td>
            <td> {{ formatDateTime(register.metadata.deleted_at ?? register.created_at) }} </td>
            <td class="d-flex justify-center align-center"> 
              <div
                class="status-chip text-neutral-3"
                :class="`status-chip-${resolveStatus(register.metadata.deleted_at ?? register.created_at)?.class}`"
              >
                {{resolveStatus(register.metadata.deleted_at ?? register.created_at)?.days + ' ' + resolveStatus(register.metadata.deleted_at ?? register.created_at)?.text }}
              </div>
            </td> 
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'countries') || $can('delete', 'countries')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                      v-if="$can('edit', 'activities')"
                      @click="showRestoreDialog(register)">
                    <template #prepend>
                      <VIcon icon="custom-return" size="24" />
                    </template>
                    <VListItemTitle>Återställ</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','activities') && canBePermanentlyDeleted(register)"
                    @click="showDeleteDialog(register)">
                    <template #prepend>
                      <VIcon icon="custom-waste" size="24" />
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
        v-if="!isRequestOngoing && hasLoaded && !registers.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-info"
        />
        <div class="empty-state-content">
          <div class="empty-state-title"> Papperskorgen är tom</div>
          <div class="empty-state-text">
            Borttagna objekt visas här i 30 dagar innan de tas bort permanent.
          </div>
        </div>
      </div>



      <VCardText
        v-if="registers.length"
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
            Ta bort objekt permanent
          </div>
        </VCardText>
        
        <VCardText class="dialog-text">
          Är du säker att du vill ta bort objekt <strong>{{ selectedRegister.metadata.title ?? selectedRegister.title }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeRegister">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
    
    <!-- 👉 Confirm Restore -->
    <VDialog
      v-model="isConfirmRestoreDialogVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
        
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmRestoreDialogVisible = !isConfirmRestoreDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-return" class="action-icon" />
          <div class="dialog-title">
            Återställ objekt
          </div>
        </VCardText>
        
        <VCardText class="dialog-text">
          Är du säker att du vill återställa objekt <strong>{{ selectedRegister.metadata.title ?? selectedRegister.title }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmRestoreDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="restoreRegister">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm Delete All -->
    <VDialog
      v-model="isConfirmAllDeleteDialogVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
        
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmAllDeleteDialogVisible = !isConfirmAllDeleteDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filled-waste" class="action-icon" />
          <div class="dialog-title">
            Ta bort alla objekt permanent
          </div>
        </VCardText>
        
        <VCardText class="dialog-text">
          Är du säker att du vill ta bort alla objekt i papperskorgen?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmAllDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeAllRegisters">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss">
  .v-select .v-field .v-field__input > input {
    align-self: center !important;
  }

  .supplier-company-logo-avatar {
    .v-img__img {
      object-fit: contain !important;
    }
  }

  .subtitle-text {
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;
  }

  .status-chip-days{
    background-color: #E7E7E7 !important;
    border: 0px;
  }

  .status-chip-warning{
    background-color: #FFEC88 !important;
    border: 0px;
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: trash
</route>