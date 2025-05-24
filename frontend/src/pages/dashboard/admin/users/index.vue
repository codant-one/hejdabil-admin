<script setup>

import create from './create.vue' 
import show from './show.vue' 
import password from './password.vue' 
import edit from './edit.vue'
import destroy from './destroy.vue'

import { avatarText } from '@/@core/utils/formatters'

import { useUsersStores } from '@/stores/useUsers'
import { useRolesStores } from '@/stores/useRoles'
import { themeConfig } from '@themeConfig'
import { excelParser } from '@/plugins/csv/excelParser'

const usersStores = useUsersStores()
const rolesStores = useRolesStores()

const users = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalUsers = ref(0)

const selectedRows = ref([])

const isUserDeleteDialog = ref(false)
const isUserDetailDialog = ref(false)
const isUserEditDialog = ref(false)
const isUserPasswordDialog = ref(false)

const selectedUser = ref({})
const rolesList = ref([])
const roleUsers = ref([]) 

const IdsUserOnline = ref([])
const userOnline = ref([])

const isRequestOngoing = ref(true)

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
    rolesList.value = response.roles.filter(role => role !== 'SuperAdmin' && role !== 'Supplier');
  }).catch(error => { })
};

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = users.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = users.value.length + (currentPage.value - 1) * rowPerPage.value
  
  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalUsers.value } användare`
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
    page: currentPage.value
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
      TELEFON: element.user_detail.phone ?? ''
    }
        
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "users", "csv")

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

        <VCard v-if="users" id="rol-list" >
          <VCardText class="d-flex align-center flex-wrap gap-4">
            <!-- 👉 Rows per page -->
             <div class="d-flex align-center w-100 w-md-auto">
              <span class="text-no-wrap me-3">Visa:</span>
              <VSelect
                v-model="rowPerPage"
                density="compact"
                :items="[10, 20, 30, 50]"
              />
            </div>

            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-file-export"
              class="w-100 w-md-auto"
              @click="downloadCSV">
                Exportera
            </VBtn>

            <create
              :rolesList="rolesList"
              @close="roleUsers = []"
              @data="fetchData"
              @alert="showAlert"/>

            <VSpacer class="d-none d-md-block"/>

            <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto">
              <!-- 👉 Select status -->
              <div class="user-list-filter">
                <VSelect
                  v-model="searchQuery"
                  label="Filtrera efter roll"
                  clearable
                  clear-icon="tabler-x"
                  single-line
                  :items="rolesList"
                  class="w-100 w-md-auto"
                />
              </div>
              
              <!-- 👉 Search  -->
              <div class="search rol-list-filter">
                <VTextField
                  v-model="searchQuery"
                  placeholder="Sök användare"
                  density="compact"
                  clearable
                />
              </div>
            </div>
          </VCardText>

          <VDivider />

          <!-- SECTION Table -->
          <VTable class="text-no-wrap rol-list-table">
            <!-- 👉 Table head -->
            <thead class="text-uppercase">
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> NAMN </th>
                <th scope="col"> E-POST </th>
                <th scope="col"> ROLL </th>
                <th scope="col"> TELEFON </th>
                <th scope="col" v-if="$can('view', 'users') || $can('edit', 'users') || $can('delete','users')"> </th>
              </tr>
            </thead>

            <!-- 👉 Table Body -->
            <tbody>
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

                <!-- 👉 phone -->
                  <td>
                  {{ user.user_detail?.phone ?? '----' }}
                </td>
                <!-- 👉 acciones -->
                <td style="width: 3rem;">
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
                         v-if="$can('view', 'users')"
                         @click="showUserDetailDialog(user)">
                        <template #prepend>
                          <VIcon icon="tabler-eye" />
                        </template>
                        <VListItemTitle>Visa</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'users')"
                         @click="showUserPasswordDialog(user)">
                        <template #prepend>
                          <VIcon icon="tabler-key" />
                        </template>
                        <VListItemTitle>Ändra lösenord</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'users')"
                         @click="showUserEditDialog(user)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','users')"
                        @click="showUserDeleteDialog(user)">
                        <template #prepend>
                          <VIcon icon="tabler-trash" />
                        </template>
                        <VListItemTitle>Radera</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </td>
              </tr>
            </tbody>

            <!-- 👉 table footer  -->
            <tfoot v-show="!users.length">
              <tr>
                <td
                  colspan="6"
                  class="text-center text-body-1"
                >
                  Det finns inga användare
                </td>
              </tr>
            </tfoot>
          </VTable>
          <!-- !SECTION -->

          <VDivider />

          <!-- SECTION Pagination -->
          <VCardText class="d-block d-md-flex text-center align-center flex-wrap gap-4 py-3">
            <!-- 👉 Pagination meta -->
            <span class="text-sm text-disabled">
              {{ paginationData }}
            </span>

            <VSpacer class="d-none d-md-block"/>

            <!-- 👉 Pagination -->
            <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="5"
              :length="totalPages"
              @next="selectedRows = []"
              @prev="selectedRows = []"
            />
          </VCardText>

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
      </VCol>
    </VRow>
  </section>
</template>

<style lang="scss">
  .v-dialog {
    z-index: 1999 !important;
  }

  .search {
    width: 100%;
  }

  .user-list-filter {
    width: 100%;
  }

  @media(min-width: 991px){
    .search {
      width: 30rem;
    }

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
