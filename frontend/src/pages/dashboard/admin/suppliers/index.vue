<script setup>

import { requiredValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { avatarText } from '@/@core/utils/formatters'
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

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
const isConfirmSwishDialogVisible = ref(false)
const selectedSupplier = ref({})
const csr_url = ref(null)
const payout_number = ref(null)
const pemFile = ref([])
const is_payout = ref(false)
const state_id = ref(null)
const refForm = ref(null)
const isFormValid = ref(false)

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

const showSwishDialog = supplierData => {
  isConfirmSwishDialogVisible.value = true
  selectedSupplier.value = { ...supplierData }
  payout_number.value = supplierData.payout_number || null
  csr_url.value = supplierData.csr_url || null
  is_payout.value = supplierData.is_payout === 0 ? false : true
  pemFile.value = []

  nextTick(() => {
    refForm.value?.resetValidation()
  })
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

const formatOrgNumber = () => {

    let numbers = payout_number.value.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    payout_number.value = numbers
}

const swish = () => {

  refForm.value?.validate().then(({ valid }) => {
    if(valid) {
      isConfirmSwishDialogVisible.value = false
      isRequestOngoing.value = true
      
      let formData = new FormData()
      formData.append('payout_number', payout_number.value)
      formData.append('is_payout', is_payout.value ? 1 : 0)
      if (pemFile.value && pemFile.value.length > 0) {
        formData.append('file', pemFile.value[0])
      }
      
      suppliersStores.swish(selectedSupplier.value.id, formData)
        .then(async (res) => {
            if (res.data.success) {
              selectedSupplier.value = {}
              payout_number.value = null
              is_payout.value = false
              pemFile.value = []
              
              await fetchData()

              advisor.value = {
                type: 'success',
                message: res.data.data.supplier.is_payout === 1 ? 'Swish aktiverad!' : 'Swish avaktiverad!',
                show: true
              }

              setTimeout(() => {
                advisor.value = {
                  type: '',
                  message: '',
                  show: false
                }
              }, 3000)

            }
            isRequestOngoing.value = false
        })
        .catch((err) => {

          advisor.value = {
            type: 'error',
            message: err.response?.data?.message || err.message,
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
    <VRow>
      <LoadingOverlay :is-loading="isRequestOngoing" />

      <VCol cols="12">
        <VAlert
          v-if="advisor.show"
          :type="advisor.type"
          class="mb-6">
            
          {{ advisor.message }}
        </VAlert>

        <Toaster />

        <VCard title="">
          <VCardText class="d-flex align-center flex-wrap gap-4">
            <div class="d-flex align-center w-100 w-md-auto">
              <span class="text-no-wrap me-3">Visa</span>
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

            <div class="d-flex align-center w-100 w-md-10">
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

              <!-- 👉 Add user button -->
              <VBtn
                v-if="$can('create','suppliers')"
                class="w-100 w-md-auto"
                prepend-icon="tabler-plus"
                :to="{ name: 'dashboard-admin-suppliers-add' }">
                  Skapa leverantör
              </VBtn>
            </div>
          </VCardText>

          <VDivider />

          <VTable class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> FÖRETAG </th>
                <th scope="col"> KONTAKT </th>
                <th scope="col"> STATUS </th>
                <th scope="col"> SWISH </th>
                <th scope="col"> # KUNDER </th>
                <th scope="col"> SKAPAD AV </th>
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
                      :variant="supplier.user.user_detail.logo ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="supplier.user.user_detail.logo"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + supplier.user.user_detail.logo"
                      />
                        <span v-else>{{ avatarText(supplier.user.user_detail.company) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium cursor-pointer text-primary" @click="seeSupplier(supplier)">
                        {{ supplier.user.user_detail.company }}
                      </span>
                      <span class="text-sm text-disabled">
                        Organisationsnummer: {{ supplier.user.user_detail.organization_number }}
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
                  <span v-if="supplier.is_payout === 1">
                    {{ supplier.payout_number ?? '' }}
                  </span>
                </td>
                <td class="text-wrap w-15">
                  {{ supplier.client_count }}
                </td>
                <td class="text-wrap">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      :variant="supplier.creator.avatar ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="supplier.creator.avatar"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + supplier.creator.avatar"
                      />
                        <span v-else>{{ avatarText(supplier.creator.name) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ supplier.creator.name }} {{ supplier.creator.last_name ?? '' }} 
                      </span>
                      <span class="text-sm text-disabled">{{ supplier.creator.email }}</span>
                    </div>
                  </div>
                </td>
                <!-- 👉 Actions -->
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
                        v-if="$can('view', 'suppliers') && supplier.state_id !== 1"
                        @click="showSwishDialog(supplier)">
                        <template #prepend>
                          <VIcon icon="mdi-payment" />
                        </template>
                        <VListItemTitle>Swish</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('view', 'suppliers')"
                        @click="seeSupplier(supplier)">
                        <template #prepend>
                          <VIcon icon="tabler-eye" />
                        </template>
                        <VListItemTitle>Visa</VListItemTitle>
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
                        <VListItemTitle>Ta bort</VListItemTitle>
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

    <!-- 👉 Confirm activate swish -->
    <VDialog
      v-model="isConfirmSwishDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmSwishDialogVisible = !isConfirmSwishDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Swish">
        <VDivider class="mt-4"/>
        <VCardText class="pb-0">
          Swish för leverantören <strong>{{ selectedSupplier.user?.name }} {{ selectedSupplier.user?.last_name ?? '' }}</strong>
        </VCardText>
        
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="swish">
          <VCardText class="d-flex flex-column gap-2">
            <VTextField
              v-model="payout_number"
              label="Utbetalningsnummer"
              :rules="[requiredValidator, minLengthDigitsValidator(10)]"
              minLength="11"
              maxlength="11"
              @input="formatOrgNumber()"
            />
            <VFileInput
              v-if="csr_url !== null"
              v-model="pemFile"
              label="Ladda upp PEM-fil"
              accept=".pem"
              prepend-icon="tabler-file"
              :rules="[requiredValidator]"
            />
            <VCheckbox
              v-if="csr_url !== null"
              v-model="is_payout"
              label="Aktivera Swish utbetalningar"
            />
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="isConfirmSwishDialogVisible = false">
                Avbryt
            </VBtn>
            <VBtn type="submit">
              Acceptera
            </VBtn>
          </VCardText>
        </VForm>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm activate supplier -->
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
<route lang="yaml">
  meta:
    action: view
    subject: suppliers
</route>