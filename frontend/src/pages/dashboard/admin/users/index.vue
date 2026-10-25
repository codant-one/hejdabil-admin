<script setup>

import create from './create.vue' 
import show from './show.vue' 
import password from './password.vue' 
import edit from './edit.vue'
import destroy from './destroy.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

import { avatarText } from '@/@core/utils/formatters'

import { useUsersStores } from '@/stores/useUsers'
import { useRolesStores } from '@/stores/useRoles'

import { ref, toRaw } from "vue";
import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js';
import { excelParser } from '@/plugins/csv/excelParser'

const { width: windowWidth } = useWindowSize();

const usersStores = useUsersStores()
const rolesStores = useRolesStores()
const emitter = inject("emitter")

const users = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalUsers = ref(0)

const selectedRows = ref([])
const role_name = ref(null)

const isUserDeleteDialog = ref(false)
const isUserDetailDialog = ref(false)
const isUserEditDialog = ref(false)
const isUserPasswordDialog = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const isMobileActionDialogVisible = ref(false);
const isConfirmLeaveVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | null
const hasLoaded = ref(false);
const state_id = ref(null)

const selectedUser = ref({})
const rolesList = ref([])
const roleUsers = ref([]) 

const IdsUserOnline = ref([])
const userOnline = ref([])

const isRequestOngoing = ref(true)

const COMPANY_STORAGE_KEY = 'clients_company_snapshot';
const filtreraMobile = ref(false);

const exporteraMobile = ref(false);

const readCachedCompany = () => {
  try {
    const cached = localStorage.getItem(COMPANY_STORAGE_KEY);
    if (!cached) return {};

    const parsed = JSON.parse(cached);
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch {
    return {};
  }
};

const company = ref(readCachedCompany())

const setCompany = (value) => {
  const normalized = value && typeof value === 'object' ? { ...value } : {};
  company.value = normalized;
  localStorage.setItem(COMPANY_STORAGE_KEY, JSON.stringify(normalized));
};

const sectionEl = ref(null);

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const advisor = ref({
  type: '',
  message: '',
  show: false
})

let interval = null

const onlineList = () => {
  return new Promise((resolve, reject) => {

    let params = {
      ids: IdsUserOnline.value.join(',')
    }

    //VIEW LOGGED IN USERS
    usersStores.getUsersOnline(params)
      .then(response => {
        userOnline.value = response
        resolve()
      }).catch(error => {})

  })
}

const searchRoles = () => {
  rolesStores.allRoles().then(response => {
    rolesList.value = response.roles.filter(role => role !== 'SuperAdmin' && role !== 'Supplier' && role !== 'User');
  }).catch(error => { })
};

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = users.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = users.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalUsers.value} resultat`;
  
  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalUsers.value } användare`
})


// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

watchEffect(fetchData)

// 👉 Fetch users
async function fetchData() {
  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'asc',
    limit: rowPerPage.value,
    page: currentPage.value,
    role_name: role_name.value
  }

  await usersStores.fetchUsers(data)

  users.value = usersStores.getUsers
  totalPages.value = usersStores.last_page
  totalUsers.value = usersStores.usersTotalCount

  IdsUserOnline.value = []
    
  users.value.forEach(element => {
    IdsUserOnline.value.push(element.id)
  })

  searchRoles()
  onlineList()

  isRequestOngoing.value = false
  hasLoaded.value = true
}

// show dialogs
const showUserDetailDialog = function(user){
  isUserDetailDialog.value = true
  selectedUser.value = { ...user }
  
  user.roles.forEach(function(ro) {
    roleUsers.value.push(ro.name)
  })

  selectedUser.value.assignedRoles = roleUsers
}

const showUserPasswordDialog = function(user){
  isUserPasswordDialog.value = true
  selectedUser.value.id = user.id
  selectedUser.value.email = user.email
}

const showUserEditDialog = function(user){
  isUserEditDialog.value = true
  selectedUser.value = { ...user }

  user.roles.forEach(function(ro) {
    roleUsers.value.push(ro.name)
  })

  selectedUser.value.assignedRoles = roleUsers
}

const showUserDeleteDialog = function(user){
  isUserDeleteDialog.value =true
  selectedUser.value = { ...user }
}

const showActivateDialog = supplierData => {
  isConfirmActiveDialogVisible.value = true
  selectedUser.value = { ...supplierData }
}

const updateRolName = (newRole) => {
  // Si ya está seleccionado, desmarcarlo (poner null)
  if (role_name.value === newRole) {
    newRole = null;
  }
  
  role_name.value = newRole;
  filtreraMobile.value = false;
};

const resolveStatus = deleted_at => {
  if (deleted_at === null)
    return { class: 'success' }
  if (deleted_at !== null)
    return { class: 'error' }
}

const activateUser = async () => {
  isConfirmActiveDialogVisible.value = false
  let res = await usersStores.activateUser(selectedUser.value.id)
  selectedUser.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Användare aktiverad!' : res.data.message,
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

const online = id =>{

  let uo = userOnline.value.find(user => user.id == id )
  let current = new Date()
  let online = new Date(2000, 0, 1, 12, 0, 0)

  if(uo && uo.online!=null)
    online = new Date(uo.online)

  let gapSeconds = Math.abs((online.getTime() - current.getTime()) / 1000)

  if(gapSeconds>120) {
    return 'error'
  } else {
    return 'success'
  }  
}

onMounted(()=>{
  interval = setInterval(()=>{
    onlineList()
  }, 60000)
})

onUnmounted(()=>{
  clearInterval(interval)
})

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
}


const downloadPDF = async () => {
  exporteraMobile.value = false
  isRequestOngoing.value = true
  const pdfFontFamily = "'Gelion Regular', 'DM Sans', sans-serif"

  const escapeHtml = value => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')

  let pdfContainer = null

  try {
    const data = {
      limit: -1 ,
      orderByField: "id",
      orderBy: "desc"
    }

    await usersStores.fetchUsers(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }
    
    const rows = usersStores.getUsers.map(element => ({
      id: element.id,
      name: element.name,
      last_name: element.last_name,
      email: element.email,
      roles: element.roles.map(e => e['name']).join(','),
      phone: element.user_detail.personal_phone ?? '',
      state: element.deleted_at === null ? 'Aktiv' : 'Inaktiv'
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Användare',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      ${(() => {
        return `
      <tr style="height: 48px;">
        <td style="width: 10%; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.id)}</td>
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.name)} ${escapeHtml(item.last_name)}</td>
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.email)}</td>
        <td style="width: 40%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.roles)}</td>
        <td style="width: 40%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.phone)}</td>
        <td style="width: 40%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.state)}</td>
      </tr>
    `
      })()}
    `).join('')

    pdfContainer = document.createElement('div')
    pdfContainer.innerHTML = `
      <div style="font-family: ${pdfFontFamily} !important; color: #454545; background-color: #FFFFFF; letter-spacing: 0; width: 100%;">
        <table style="width: 100%; border-spacing: 0; border-collapse: separate; font-size: 12px; font-weight: 400;">
          <tbody>
            <tr>
              <td>
                ${headerMarkup}

                <table style="width: 100%; table-layout: fixed; border-spacing: 0; border-collapse: separate; margin-top: 10px; font-family: ${pdfFontFamily} !important; font-size: 12px;">
                  <thead>
                    <tr style="height: 48px;">
                      <td style="text-align: center; width: 10%; padding: 0 12px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Id</td>
                      <td style="text-align: center; width: 50%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Namn</td>
                      <td style="text-align: center; width: 50%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">E-post</td>
                      <td style="text-align: center; width: 40%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Roll</td>
                      <td style="text-align: center; width: 40%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Telefon</td>
                      <td style="text-align: center; width: 40%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Status</td>
                    </tr>
                  </thead>
                  <tbody>
                    ${rowsMarkup}
                  </tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    `
    
    document.body.appendChild(pdfContainer)

    await html2pdf()
      .set({
        margin: [12, 10, 12, 10],
        filename: 'users.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#FFFFFF' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] },
      })
      .from(pdfContainer)
      .save()
  } finally {
    if (pdfContainer?.parentNode)
      pdfContainer.parentNode.removeChild(pdfContainer)

    isRequestOngoing.value = false
  }

}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await usersStores.fetchUsers(data)

  let dataArray = []
  
  usersStores.getUsers.forEach(element => {
    let data = {
      NAMN: element.name,
      EFTERNAMN: (element.last_name ?? ''),
      E_POST: element.email,
      ROLL: element.roles.map(e => e['name']).join(','),
      TELEFON: element.user_detail.personal_phone ?? ''
    }
        
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "användare", "csv")

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
          <h2>Användare</h2>
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

        <div class="d-flex gap-4">
          <VMenu v-if="windowWidth >= 1024">
            <template #activator="{ props }">
              <VBtn
                id="payout-export-button"
                class="btn-light w-auto"
                block
                v-bind="props"
              >
                <VIcon icon="custom-export" size="24" />
                Exportera
              </VBtn>
            </template>

            <VList>
              <VListItem @click="downloadPDF">
                <VListItemTitle>Exportera PDF</VListItemTitle>
              </VListItem>
              <VListItem @click="downloadCSV">
                <VListItemTitle>Exportera Excel</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>

          <VBtn
            v-if="windowWidth < 1024"
            id="payout-export-button"
            class="btn-light w-auto"
            block
            @click="exporteraMobile = true"
          >
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

          <create
          :rolesList="rolesList"
          :type="'button'"
          @close="roleUsers = []"
          @data="fetchData"
          @alert="showAlert"/>

        </div>
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
              v-for="rol in rolesList"

              @click="updateRolName(rol)"
            >
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="role_name === rol"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                    tabindex="-1"
                /></VListItemAction>
              </template>
              <VListItemTitle>{{ rol }}</VListItemTitle>
            </VListItem>
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

      <!-- SECTION Table -->
      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="users.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 Table head -->
        <thead>
          <tr>
            <th scope="col"> #ID </th>
            <th scope="col"> Namn </th>
            <th scope="col"> E-Post </th>
            <th scope="col"> Roll </th>
            <th scope="col"> Status </th>
            <th scope="col"> Telefon </th>
            <th scope="col" v-if="$can('view', 'users') || $can('edit', 'users') || $can('delete','users')"> </th>
          </tr>
        </thead>

        <!-- 👉 Table Body -->
        <tbody v-show="users.length">
          <tr
            v-for="user in users"
            :key="user.id"
            style="height: 3rem;"
          >
            <!-- 👉 Id -->
            <td>
              #{{ user.id }}
            </td>

            <!-- 👉 name -->
            <td>
              <div class="d-flex align-center">
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  bordered
                  :color="online(user.id)"
                >
                  <VAvatar
                    variant="tonal"
                    size="38"
                  >
                    <VImg
                      v-if="user.avatar"
                      style="border-radius: 50%;"
                      :src="themeConfig.settings.urlStorage + user.avatar"
                    />
                    <span v-else>{{ avatarText(user.name) }}</span>
                  </VAvatar>
                </VBadge>
                <div class="ml-3 d-flex flex-column">
                  {{ user.name }}  {{ user.last_name ?? '' }}
                </div>
              </div>
            </td>

            <!-- 👉 correo -->
            <td>
              {{ user.email }}
            </td>

            <!-- 👉 roles -->
            <td>
              <ul>
                <li v-for="(value, key) in user.roles">
                  {{ value.name }}
                </li>
              </ul>
            </td>

            <td> 
              <div
                class="status-chip"
                :class="`status-chip-${resolveStatus(user.deleted_at)?.class}`"
              >
                {{ user.deleted_at ? 'Inaktiv' : 'Aktiv' }}
              </div>
            </td>

            <!-- 👉 phone -->
              <td>
              {{ user.user_detail?.personal_phone ?? '----' }}
            </td>
            <!-- 👉 Actions -->
            <td style="width: 3rem;">
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                      v-if="$can('view', 'users')"
                      @click="showUserDetailDialog(user)">
                    <template #prepend>
                      <VIcon icon="custom-eye" size="24" />
                    </template>
                    <VListItemTitle>Visa</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if="$can('edit', 'users') && user.deleted_at === null"
                      @click="showUserPasswordDialog(user)">
                    <template #prepend>
                      <VIcon icon="custom-password-outlined" size="24" />
                    </template>
                    <VListItemTitle>Ändra lösenord</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if="$can('edit', 'users') && user.deleted_at === null"
                      @click="showUserEditDialog(user)">
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','users') && user.deleted_at === null"
                    @click="showUserDeleteDialog(user)">
                    <template #prepend>
                      <VIcon icon="custom-waste" size="24" />
                    </template>
                    <VListItemTitle>Ta bort</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('delete','users') && user.deleted_at !== null"
                    @click="showActivateDialog(user)">
                    <template #prepend>
                      <VIcon icon="custom-check-mark" size="24" />
                    </template>
                    <VListItemTitle>Aktivera</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </td>
          </tr>
        </tbody>
      </VTable>

      <div
        v-if="!isRequestOngoing && hasLoaded && !users.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-user"
        />
        <div class="empty-state-content">
          <div class="empty-state-title"> Inga användare har skapats ännu</div>
          <div class="empty-state-text">
            Här visas alla användare som du har skapat.
          </div>
        </div>

        <create
          :rolesList="rolesList"
          :type="'link'"
          @close="roleUsers = []"
          @data="fetchData"
          @alert="showAlert"/>
      </div>
      <!-- !SECTION -->

      <!-- SECTION Mobile Table -->
      <VExpansionPanels
          class="expansion-panels pb-6 px-6"
          v-if="users.length && windowWidth < 1024"
        >
          <VExpansionPanel v-for="user in users" :key="user.id">
            <VExpansionPanelTitle
              collapse-icon="custom-chevron-right"
              expand-icon="custom-chevron-down"
            >
              <div class="d-flex align-center gap-x-3">
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  bordered
                  :color="online(user.id)"
                >
                  <VAvatar
                    variant="tonal"
                    size="38"
                  >
                    <VImg
                      v-if="user.avatar"
                      style="border-radius: 50%;"
                      :src="themeConfig.settings.urlStorage + user.avatar"
                    />
                    <span v-else>{{ avatarText(user.name) }}</span>
                  </VAvatar>
                </VBadge>
                <div class="ml-3 d-flex flex-column">
                  {{ user.name }}  {{ user.last_name ?? '' }}
                </div>
              </div>
            </VExpansionPanelTitle>
            
            <VExpansionPanelText>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Status</div>
                <div class="expansion-panel-item-value">
                  <div
                    class="status-chip"
                    :class="`status-chip-${resolveStatus(user.deleted_at)?.class}`"
                  >
                    {{ user.deleted_at ? 'Inaktiv' : 'Aktiv' }}
                  </div>
                </div>
              </div>
              <div class="mb-6">
                <div class="expansion-panel-item-label">E-post</div>
                <div class="expansion-panel-item-value">
                    {{ user.email }}
                </div>
              </div>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Roll</div>
                <div class="expansion-panel-item-value">
                    <ul>
                      <li v-for="(value, key) in user.roles">
                        {{ value.name }}
                      </li>
                    </ul>
                </div>
              </div>
              <div class="mb-6">
                <div class="expansion-panel-item-label">Telefon</div>
                <div class="expansion-panel-item-value">
                    {{ user.phone }}
                </div>
              </div>
              

              <div class="d-flex justify-end flex-wrap gap-4">
                <VBtn 
                  v-if="$can('edit', 'users')"
                  class="btn-light" 
                  icon 
                  @click="selectedUser = user; isMobileActionDialogVisible = true"
                >
                  <VIcon icon="custom-dots-vertical" size="24" />
                </VBtn>
              </div>
            </VExpansionPanelText>
          </VExpansionPanel>
      </VExpansionPanels>
      <!-- !SECTION -->

      <!-- SECTION Pagination -->
       <VCardText
        v-if="users.length"
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
      <!-- !SECTION -->


      <show 
        v-model:isDrawerOpen="isUserDetailDialog"
        :rolesList="rolesList"
        :user="selectedUser"
        @close="roleUsers = []"/>

      <password
        v-model:isDrawerOpen="isUserPasswordDialog"
        :user="selectedUser"
        @data="fetchData"
        @alert="showAlert"/>

      <edit
        v-model:isDrawerOpen="isUserEditDialog"
        :rolesList="rolesList"
        :user="selectedUser"
        @data="fetchData"
        @close="roleUsers = []"
        @alert="showAlert"/>

      <destroy 
        v-model:isDrawerOpen="isUserDeleteDialog"
        :user="selectedUser"
        @data="fetchData"
        @alert="showAlert"/>

    </VCard>

    <!-- 👉 Confirm activate user -->
    <VDialog
      v-model="isConfirmActiveDialogVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
        
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmActiveDialogVisible = !isConfirmActiveDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-warning-outlined" class="action-icon" />
          <div class="dialog-title">
            Aktivera användare
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Är du säker att du vill aktivera användaren <strong>{{ selectedUser.name }} {{ selectedUser.last_name ?? '' }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmActiveDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="activateUser">
              Acceptera
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
      <VCard>
        <VList>
          <VListItem 
            v-for="rol in rolesList"

            @click="updateRolName(rol)"
          >
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="role_name === rol"
                  class="ml-3"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
                  tabindex="-1"
              /></VListItemAction>
            </template>
            <VListItemTitle>{{ rol }}</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile Action Dialog -->
    <VDialog
      v-model="isMobileActionDialogVisible"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem
              v-if="$can('view', 'users')"
              @click="showUserDetailDialog(selectedUser); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-eye" size="24" />
            </template>
            <VListItemTitle>Visa</VListItemTitle>
          </VListItem>
          <VListItem
              v-if="$can('edit', 'users') && selectedUser.deleted_at === null"
              @click="showUserPasswordDialog(selectedUser); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-password-outlined" size="24" />
            </template>
            <VListItemTitle>Ändra lösenord</VListItemTitle>
          </VListItem>
          <VListItem
              v-if="$can('edit', 'users')"
              @click="showUserEditDialog(selectedUser); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem 
            v-if="$can('delete','users')"
            @click="showUserDeleteDialog(selectedUser); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete','users') && selectedUser.deleted_at !== null"
            @click="showActivateDialog(selectedUser); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-check-mark" size="24" />
            </template>
            <VListItemTitle>Aktivera</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Export Mobile Dialog -->
    <VDialog
      v-model="exporteraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem @click="downloadPDF">
            <VListItemTitle>Exportera PDF</VListItemTitle>
          </VListItem>

          <VListItem @click="downloadCSV">
            <VListItemTitle>Exportera Excel</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss">
  .v-dialog {
    z-index: 1999 !important;
  }

  .user-list-filter {
    width: 100%;
  }

  @media(min-width: 991px){
    .user-list-filter {
      inline-size: 12rem;
    }
  }

</style>

<route lang="yaml">
  meta:
    action: view
    subject: users
</route>
