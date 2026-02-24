<script setup>

import { useDisplay } from 'vuetify'
import { useSuppliersStores } from '@/stores/useSuppliers'
import { excelParser } from '@/plugins/csv/excelParser'
import show from './show.vue' 
import showMobile from './showMobile.vue' 
import password from './password.vue'
import destroy from './destroy.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router'

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const usersStores = useSuppliersStores()

const users = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalUsers = ref(0)

const isMobile = ref(false)
const isUserDeleteDialog = ref(false)
const isUserDetailDialog = ref(false)
const isUserPasswordDialog = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const isMobileActionDialogVisible = ref(false);

const selectedUser = ref({})
const selectedUserForAction = ref({});

const IdsUserOnline = ref([])
const userOnline = ref([])

const isRequestOngoing = ref(true)
const hasLoaded = ref(false);

const permissionsRol = ref([])
const readonly = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const emit = defineEmits([
  'alert'
])

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

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = users.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    users.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalUsers.value} resultat`;
  // return `Visar ${firstIndex} till ${lastIndex} av ${totalClients.value} register`;
});


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
    orderByField: 'order_id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value
  }

  await usersStores.fetchUsers(data)

  users.value = usersStores.getUsers
  totalPages.value = usersStores.users_last_page
  totalUsers.value = usersStores.usersTotalCount

  IdsUserOnline.value = []
    
  users.value.forEach(element => {
    IdsUserOnline.value.push(element.user.id)
  })

  onlineList()

  hasLoaded.value = true;
  isRequestOngoing.value = false
}

// show dialogs
const showUserDetailDialog = function(user, mobile = false){
  isUserDetailDialog.value = true
  isMobile.value = mobile
  selectedUser.value = { ...user }

  permissionsRol.value = []

  selectedUser.value.permissions.forEach(function(pe) {
      permissionsRol.value.push(pe.name)
  })

  selectedUser.value.assignedPermissions = permissionsRol
  readonly.value= true
}

const showUserPasswordDialog = function(user){
  isUserPasswordDialog.value = true
  selectedUser.value.id = user.id
  selectedUser.value.email = user.email
}

const showUserEditDialog = function(user){
  router.push({ name : 'dashboard-admin-suppliers-users-id', params: { id: user.id } })
}

const showUserDeleteDialog = function(user){
  isUserDeleteDialog.value =true
  selectedUser.value = { ...user }
}

const showActivateDialog = supplierData => {
  isConfirmActiveDialogVisible.value = true
  selectedUser.value = { ...supplierData }
}

const createUser = () => {
  router.push({ name : 'dashboard-admin-suppliers-users-create' })
}

const activateUser = async () => {
  isConfirmActiveDialogVisible.value = false
  let res = await usersStores.activateSupplier(selectedUser.value.id)
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

const openMobileActionDialog = user => {
  selectedUserForAction.value = user
  isMobileActionDialogVisible.value = true
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + "...";
  }
  return text;
};

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await usersStores.fetchUsers(data)

  let dataArray = []
  
  usersStores.getUsers.forEach(element => {
    let data = {
      NAMN: element.user.name,
      EFTERNAMN: (element.user.last_name ?? ''),
      E_POST: element.user.email,
      TELEFON: element.user.user_detail.personal_phone ?? ''
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

    <VCard v-if="users" class="card-fill" id="rol-list" >
      <VCardText class="px-0" :class="windowWidth < 1024 ? 'pb-0' : 'd-none'">
        <div class="title-tabs-profile">
          Mitt team
        </div>
      </VCardText>
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'py-6 pa-0' : 'pa-4'
        ]"
      >
        <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto mobile-search-block"> 
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
        <div class="d-flex mobile-actions-block" :class="windowWidth < 1024 ? 'gap-2' : 'gap-4'">
          <VBtn
            class="btn-light w-auto"
            block
            @click="downloadCSV">
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

          <VBtn
            v-if="$can('create', 'users')"
            class="btn-gradient"
            block
            @click="createUser">
              <VIcon v-if="windowWidth >= 1024" icon="custom-plus" size="24" />
              Lägg till medarbetare
          </VBtn>
        </div>
      </VCardTitle>

      <!-- SECTION Table -->
      <VTable 
        v-if="!$vuetify.display.mdAndDown"
        v-show="users.length"
        class="pt-2 px-4 pb-6 text-no-wrap rol-list-table"
        style="border-radius: 0 !important"
      >
        <!-- 👉 Table head -->
        <thead>
          <tr>
            <th scope="col"> #ID </th> 
            <th scope="col"> Namn </th>
            <th class="text-center" scope="col"> E-post </th>
            <th class="text-center" scope="col"> Telefon </th>
            <th scope="col" class="text-center">Adress</th>
            <th scope="col" v-if="$can('view', 'users') || $can('edit', 'users') || $can('delete','users')"> </th>
          </tr>
        </thead>

        <!-- 👉 Table Body -->
        <tbody>
          <tr
            v-for="user in users"
            :key="user.user.id"
            style="height: 3rem;"
          >
            <!-- 👉 Id -->
            <td>
              {{ user.order_id }}
            </td>

            <!-- 👉 name -->
            <td>
              <div
                class="d-flex justify-between align-center font-weight-medium cursor-pointer text-aqua"
                @click="showUserDetailDialog(user.user, false)"
              >
                <span class="flex-grow break-words">
                  {{ user.user.name }}  {{ user.user.last_name ?? '' }}
                </span>

                <VIcon
                  class="flex-shrink-0"
                  icon="custom-arrow-right"
                  size="22"
                />
              </div>
            </td>

            <!-- 👉 correo -->
            <td class="text-center">
              {{ user.user.email }}
            </td>

            <!-- 👉 phone -->
            <td class="text-center">
              {{ user.user.user_detail?.personal_phone ?? '----' }}
            </td>
            <td class="text-center">
              <VTooltip 
                v-if="user.user.user_detail?.personal_address && user.user.user_detail.personal_address.length > 20"
                location="bottom"
                max-width="300">
                <template #activator="{ props }">
                  <span v-bind="props" class="cursor-pointer">
                    {{ truncateText(user.user.user_detail.personal_address, 20) }}
                  </span>
                </template>
                <span>{{ user.user.user_detail.personal_address }}</span>
              </VTooltip>
              <span v-else>{{ user.user.user_detail.personal_address }}</span>
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
                      @click="showUserDetailDialog(user.user, false)">
                    <template #prepend>
                       <VIcon icon="custom-eye" size="24" />
                    </template>
                    <VListItemTitle>Visa</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if="$can('edit', 'users') && user.deleted_at === null"
                      @click="showUserPasswordDialog(user.user)">
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
                    @click="showUserDeleteDialog(user.user)">
                    <template #prepend>
                      <VIcon icon="custom-waste" size="24" />
                    </template>
                    <VListItemTitle>Ta bort</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('delete','users') && user.deleted_at !== null"
                    @click="showActivateDialog(user)">
                    <template #prepend>
                      <VIcon icon="tabler-rosette-discount-check" />
                    </template>
                    <VListItemTitle>Aktivera</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </td>
          </tr>
        </tbody>
      </VTable>

      <VExpansionPanels
        class="expansion-panels pb-6 px-0"
        v-if="users.length && windowWidth < 1024"
      >
        <VExpansionPanel v-for="user in users" :key="user.id" readonly>
          <VExpansionPanelTitle
            class="mobile-user-actions-title"
            hide-actions
          >
            <div class="d-flex align-center justify-space-between w-100">
              <div class="d-flex align-center">
                <span class="order-id">{{  user.order_id }}</span>

                <div class="d-flex flex-column gap-1">
                    <span class="text-aqua">{{ user.user.name }}  {{ user.user.last_name ?? '' }}</span>
                    <span class="text-neutral-3">{{ user.user.email }}</span>
                    <span class="text-neutral-3">{{ user.user.user_detail?.personal_phone ?? '----' }}</span>
                </div>
              </div>

              <VIcon
                icon="custom-dots-vertical"
                size="22"
                class="mobile-actions-icon"
                @click.stop="openMobileActionDialog(user)"
              />
            </div>
          </VExpansionPanelTitle>
        </VExpansionPanel>
      </VExpansionPanels>

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
          <div class="empty-state-title">Inga medarbetare inlagda än</div>
          <div class="empty-state-text">
            Bjud in dina kollegor för att samarbeta kring försäljning, fakturering och lagerhantering. 
            Ge rätt behörigheter till rätt person och jobba effektivare tillsammans.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'users') && !$vuetify.display.mdAndDown"
          @click="createUser"
        >
          Lägg till medarbetare
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'users')"
          @click="createUser"
        >
          Lägg till ny användare
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>
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

      <show 
        v-if="!isMobile"
        v-model:isDrawerOpen="isUserDetailDialog"
        :user="selectedUser"
        :readonly="readonly"
        @close="permissionsRol = []"
        @readonly="readonly = false"/>

      <showMobile 
        v-else
        v-model:isDrawerOpen="isUserDetailDialog"
        :user="selectedUser"
        :readonly="readonly"
        @close="permissionsRol = []"
        @readonly="readonly = false"/>

      <password
        v-model:isDrawerOpen="isUserPasswordDialog"
        :user="selectedUser"
        @data="fetchData"
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
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmActiveDialogVisible = !isConfirmActiveDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Aktivera användare">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill aktivera användaren <strong>{{ selectedUser.name }} {{ selectedUser.last_name ?? '' }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmActiveDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="activateUser">
              Acceptera
          </VBtn>
        </VCardText>
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
              @click="showUserDetailDialog(selectedUserForAction.user, true); isMobileActionDialogVisible = false;">
            <template #prepend>
               <VIcon icon="custom-eye" size="24" />
            </template>
            <VListItemTitle>Visa</VListItemTitle>
          </VListItem>
          <VListItem
              v-if="$can('edit', 'users') && selectedUserForAction.deleted_at === null"
              @click="showUserPasswordDialog(selectedUserForAction.user); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-password-outlined" size="24" />
            </template>
            <VListItemTitle>Ändra lösenord</VListItemTitle>
          </VListItem>
          <VListItem
              v-if="$can('edit', 'users') && selectedUserForAction.deleted_at === null"
              @click="showUserEditDialog(selectedUserForAction)">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem 
            v-if="$can('delete','users') && selectedUserForAction.deleted_at === null"
            @click="showUserDeleteDialog(selectedUserForAction.user); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete','users') && selectedUserForAction.deleted_at !== null"
            @click="showActivateDialog(selectedUserForAction.user)">
            <template #prepend>
              <VIcon icon="tabler-rosette-discount-check" />
            </template>
            <VListItemTitle>Aktivera</VListItemTitle>
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

  @media (max-width: 1023px) {
    .mobile-actions-block {
      order: 1;
    }

    .mobile-search-block {
      order: 2;
    }
  }

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
            background: white !important;
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
      .v-input__prepend {
        padding-top: 12px !important;
      }
      & .v-input__control {
        .v-field {
          background-color: #f6f6f6;
          min-height: 48px !important;

          .v-text-field__suffix {
            padding: 12px 16px !important;
          }

          .v-field__input {
            min-height: 48px !important;
            padding: 12px 16px !important;

            input {
                min-height: 48px !important;
            }
          }

          .v-field-label {
            top: 12px !important;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
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

  .mobile-user-actions-title {
    color: #878787;

    .v-expansion-panel-title__icon {
      .v-icon {
        color: #878787 !important;
      }
    }

    .mobile-actions-icon {
      color: #878787 !important;
    }
  }

</style>

<route lang="yaml">
  meta:
    action: view
    subject: users
</route>
