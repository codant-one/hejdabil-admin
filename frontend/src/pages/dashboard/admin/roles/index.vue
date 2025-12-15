<script setup>

import tree from './tree.vue'
import create from './create.vue'
import show from './show.vue'
import edit from './edit.vue'
import destroy from './destroy.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

import { useRolesStores } from '@/stores/useRoles'
import { excelParser } from '@/plugins/csv/excelParser'

const rolesStores = useRolesStores()

const roles = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRoles = ref(0)
const selectedRows = ref([])

const isRoleDetailDialog = ref(false)
const isRoleEditDialog = ref(false)
const isRoleDeleteDialog = ref(false)

const selectedRol = ref({})
const permissionsRol = ref([]) 
const readonly = ref(false)

const isRequestOngoing = ref(true)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = roles.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = roles.value.length + (currentPage.value - 1) * rowPerPage.value
  
  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalRoles.value } roller`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

watchEffect(fetchData)


// 👉 Fetch roles
async function fetchData() {
  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value
  }

  await rolesStores.fetchRoles(data)

  roles.value = rolesStores.getRoles
  totalPages.value = rolesStores.last_page
  totalRoles.value = rolesStores.rolesTotalCount

  isRequestOngoing.value = false
}


// show dialogs
const showRoleDetailDialog = function(rol){
  isRoleDetailDialog.value = true
  selectedRol.value = { ...rol }

  rol.permissions.forEach(function(pe) {
    permissionsRol.value.push(pe.name)
  })

  selectedRol.value.assignedPermissions = permissionsRol
  readonly.value= true
}

const showRoleEditDialog = function(rol){
  isRoleEditDialog.value = true
  selectedRol.value = { ...rol }

  rol.permissions.forEach(function(pe) {
    permissionsRol.value.push(pe.name)
  })

  selectedRol.value.assignedPermissions = permissionsRol
  readonly.value= false
}

const showRoledDeleteDialog = function($rol){
  isRoleDeleteDialog.value = true
  selectedRol.value = { ...$rol }
}

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1}

  await rolesStores.fetchRoles(data)

  let dataArray = []

  rolesStores.getRoles.forEach(element => {
    let data = {
      ROLL: element.name
    }
        
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "roles", "csv")

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

        <VCard
          v-if="roles"
          id="rol-list"
        >
          <VCardText class="d-flex align-center flex-wrap gap-4">
            <!-- 👉 Rows per page -->
            <div class="d-flex align-center w-100 w-md-auto">
              <span class="text-no-wrap me-3">Visa</span>
              <VSelect
                v-model="rowPerPage"
                density="compact"
                class="w-100"
                :items="[10, 20, 30, 50]"
              />
            </div>

            <create @data="fetchData" @alert="showAlert"/>

            <tree />

            <VSpacer class="d-none d-md-block"/>

            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-file-export"
              class="w-100 w-md-auto"
              @click="downloadCSV"
            >
              Exportera
            </VBtn>

            <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto">
              <!-- 👉 Search  -->
              <div class="search rol-list-filter">
                <VTextField
                  v-model="searchQuery"
                  placeholder="Sök roll"
                  density="compact"
                  class="w-100 w-md-auto"
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

                <th scope="col">
                  ROLL
                </th>

                <th scope="col" v-if="$can('view', 'roles') || $can('edit','roles') || $can('delete','roles')"> </th>
              </tr>
            </thead>

            <!-- 👉 Table Body -->
            <tbody>
              <tr
                v-for="rol in roles"
                :key="rol.id"
                style="height: 3rem;"
              >

                <!-- 👉 nombre -->
                <td>
                  {{ rol.name }}
                </td>

                <!-- 👉 Actions -->
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
                         v-if="$can('view', 'roles')"
                         @click="showRoleDetailDialog(rol)">
                        <template #prepend>
                          <VIcon icon="tabler-eye" />
                        </template>
                        <VListItemTitle>Visa</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'roles')"
                         @click="showRoleEditDialog(rol)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','roles')"
                        @click="showRoledDeleteDialog(rol)">
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
            <tfoot v-show="!roles.length">
              <tr>
                <td
                  colspan="3"
                  class="text-center text-body-1"
                >
                  Det finns inga roller
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
            v-model:isDrawerOpen="isRoleDetailDialog"
            :role="selectedRol"
            :readonly="readonly"
            @close="permissionsRol = []"
            @readonly="readonly = false"/>
          
          <edit 
            v-model:isDrawerOpen="isRoleEditDialog"
            :role="selectedRol"
            @data="fetchData"
            @close="permissionsRol = []"
            @alert="showAlert"
            @readonly="readonly = true"/>

          <destroy 
            v-model:isDrawerOpen="isRoleDeleteDialog"
            :role="selectedRol"
            @data="fetchData"
            @alert="showAlert"/>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style lang="scss">
  .v-label {
    text-overflow: clip;
  }

  #permisos-lista {
    .n1 strong {
      font-size: 1.7rem;
    }

    .n2 strong {
      font-size: 1.3rem;
    }

    .n3 strong {
      font-size: 1rem;
    }

    .tab {
      margin-block: 4px;
      margin-inline-start: 2rem;
    }
  }

  @media(min-width: 991px){
    #rol-list {
    .rol-list-actions {
      inline-size: 8rem;
    }

    .rol-list-filter {
      inline-size: 12rem;
    }
  }
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: roles
</route>
