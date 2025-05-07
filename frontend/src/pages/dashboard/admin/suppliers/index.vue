<script setup>

import { useSuppliersStores } from '@/stores/useSuppliers'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { avatarText } from '@/@core/utils/formatters'
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'

const suppliersStores = useSuppliersStores()
const emitter = inject("emitter")

const suppliers = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalSuppliers = ref(0)
const isRequestOngoing = ref(true)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const selectedSupplier = ref({})
const state_id = ref(null)

const states = ref ([
  { id: 2, name: "Aktiv" },
  { id: 1, name: "Inaktiv" }
])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = suppliers.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = suppliers.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalSuppliers.value } register`
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
    state_id.value = null
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    state_id: state_id.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await suppliersStores.fetchSuppliers(data)

  suppliers.value = suppliersStores.getSuppliers
  totalPages.value = suppliersStores.last_page
  totalSuppliers.value = suppliersStores.suppliersTotalCount

  isRequestOngoing.value = false

}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const resolveStatus = state_id => {
  if (state_id === 2)
    return { color: 'success' }
  if (state_id === 1)
    return { color: 'error' }
}

const editSupplier = supplierData => {
  router.push({ name : 'dashboard-admin-suppliers-edit-id', params: { id: supplierData.id } })
}

const showDeleteDialog = supplierData => {
  isConfirmDeleteDialogVisible.value = true
  selectedSupplier.value = { ...supplierData }
}

const showActivateDialog = supplierData => {
  isConfirmActiveDialogVisible.value = true
  selectedSupplier.value = { ...supplierData }
}

const seeSupplier = supplierData => {
  router.push({ name : 'dashboard-admin-suppliers-id', params: { id: supplierData.id } })
}

const removeSupplier = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await suppliersStores.deleteSupplier(selectedSupplier.value.id)
  selectedSupplier.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Leverantör borttagen!' : res.data.message,
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

const activateSupplier = async () => {
  isConfirmActiveDialogVisible.value = false
  let res = await suppliersStores.activateSupplier(selectedSupplier.value.id)
  selectedSupplier.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Leverantör aktiverad!' : res.data.message,
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

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await suppliersStores.fetchSuppliers(data)

  let dataArray = [];
      
  suppliersStores.getSuppliers.forEach(element => {

    let data = {
      ID: element.id,
      KONTAKT: element.user.name + ' ' + (element.user.last_name ?? ''),
      E_POST: element.user.email,
      FÖRETAG: element.company ?? '',
      ORGANISATIONSNUMMER: element.organization_number ?? '',
      REGISTRERADE_KUNDER:  element.client_count,
      STATU: element.state.name
    }

    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "suppliers", "csv");

  isRequestOngoing.value = false

}
</script>

<template>
  <section>
    <v-row>
      <VDialog
        v-model="isRequestOngoing"
        width="auto"
        persistent>
        <VProgressCircular
          indeterminate
          color="primary"
          class="mb-0"/>
      </VDialog>

      <v-col cols="12">
        <v-alert
          v-if="advisor.show"
          :type="advisor.type"
          class="mb-6">
            
          {{ advisor.message }}
        </v-alert>

        <Toaster />

        <v-card title="">
          <v-card-text class="d-flex flex-wrap py-4 gap-4">
            <div
              class="me-3"
              style="width: 80px;">
              
              <VSelect
                v-model="rowPerPage"
                density="compact"
                variant="outlined"
                :items="[10, 20, 30, 50]"/>
            </div>

            <div class="d-flex align-center">
              <VBtn
                variant="tonal"
                color="secondary"
                prepend-icon="tabler-file-export"
                @click="downloadCSV">
                Exportera
              </VBtn>
            </div>

            <v-spacer />
            <div class="d-flex align-center" style="width: 200px;">
              <VSelect
                  v-model="state_id"
                  placeholder="Status"
                  :items="states"
                  :item-title="item => item.name"
                  :item-value="item => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"/>
            </div>

            <div class="d-flex align-center flex-wrap gap-4">              
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
                v-if="$can('create','suppliers')"
                prepend-icon="tabler-plus"
                :to="{ name: 'dashboard-admin-suppliers-add' }">
                  Skapa leverantör
              </v-btn>
            </div>
          </v-card-text>

          <v-divider />

          <v-table class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> FÖRETAG </th>
                <th scope="col"> KONTAKT </th>
                <th scope="col"> STATUS </th>
                <th scope="col"> # REGISTRERADE KUNDER </th>
                <th scope="col" v-if="$can('edit', 'suppliers') || $can('delete', 'suppliers')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="supplier in suppliers"
                :key="supplier.id"
                style="height: 3rem;">

                <td> {{ supplier.id }} </td>
                <td class="text-wrap">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      :variant="supplier.logo ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="supplier.logo"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + supplier.logo"
                      />
                        <span v-else>{{ avatarText(supplier.company) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium cursor-pointer text-primary" @click="seeSupplier(supplier)">
                        {{ supplier.company }}
                      </span>
                      <span class="text-sm text-disabled">
                        Organisationsnummer: {{ supplier.organization_number }}
                      </span>
                    </div>
                  </div>
                </td>
                <td class="text-wrap">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      :variant="supplier.user.avatar ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="supplier.user.avatar"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + supplier.user.avatar"
                      />
                        <span v-else>{{ avatarText(supplier.user.name) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ supplier.user.name }} {{ supplier.user.last_name ?? '' }} 
                      </span>
                      <span class="text-sm text-disabled">{{ supplier.user.email }}</span>
                    </div>
                  </div>
                </td>
                <td> 
                  <VChip
                    label
                    :color="resolveStatus(supplier.state.id)?.color"
                  >
                    {{ supplier.state.name }}
                  </VChip>
                </td>
                <td class="text-wrap w-15">
                  {{ supplier.client_count }}
                </td>
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'suppliers') || $can('delete', 'suppliers')">      
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
                        v-if="$can('view', 'suppliers')"
                        @click="seeSupplier(supplier)">
                        <template #prepend>
                          <VIcon icon="tabler-eye" />
                        </template>
                        <VListItemTitle>Utsikt</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'suppliers') && supplier.state_id === 2"
                         @click="editSupplier(supplier)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','suppliers') && supplier.state_id === 2"
                        @click="showDeleteDialog(supplier)">
                        <template #prepend>
                          <VIcon icon="tabler-trash" />
                        </template>
                        <VListItemTitle>Radera</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('delete','suppliers') && supplier.state_id === 1"
                        @click="showActivateDialog(supplier)">
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
            <!-- 👉 table footer  -->
            <tfoot v-show="!suppliers.length">
              <tr>
                <td
                  colspan="6"
                  class="text-center">
                  Uppgifter ej tillgängliga
                </td>
              </tr>
            </tfoot>
          </v-table>
        
          <v-divider />

          <VCardText class="d-flex align-center flex-wrap justify-space-between gap-4 py-3 px-5">
            <span class="text-sm text-disabled">
              {{ paginationData }}
            </span>

            <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="5"
              :length="totalPages"/>
          
          </VCardText>
        </v-card>
      </v-col>
    </v-row>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Ta bort leverantör">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort leverantör <strong>{{ selectedSupplier.user.name }} {{ selectedSupplier.user.last_name ?? '' }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeSupplier">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmActiveDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmActiveDialogVisible = !isConfirmActiveDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Aktivera leverantör">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill aktivera leverantören <strong>{{ selectedSupplier.user.name }} {{ selectedSupplier.user.last_name ?? '' }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmActiveDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="activateSupplier">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style scope>
    .search {
        width: 100%;
    }

    @media(min-width: 991px){
        .search {
            width: 30rem;
        }
    }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: suppliers
</route>