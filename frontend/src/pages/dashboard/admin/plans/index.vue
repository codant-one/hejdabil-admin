<script setup>

import { usePlansStores } from '@/stores/usePlans'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import AddNewPlanDrawer from './AddNewPlanDrawer.vue' 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const plansStores = usePlansStores()
const emitter = inject("emitter")

const plans = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalPlans = ref(0)
const isRequestOngoing = ref(true)
const isAddNewPlanDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const selectedPlan = ref({})
const features = ref([])
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

const failedExternalFlags = ref({})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = plans.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = plans.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalPlans.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

  if (!isAddNewPlanDrawerVisible.value)
      selectedPlan.value = {}
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

  await plansStores.fetchPlans(data)

  plans.value = plansStores.getPlans
  totalPages.value = plansStores.last_page
  totalPlans.value = plansStores.plansTotalCount
  features.value = plansStores.getFeatures || [];
  

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

const showActivateDialog = planData => {
  isConfirmActiveDialogVisible.value = true
  selectedPlan.value = { ...planData }
}

const editPlan = planData => {
    isAddNewPlanDrawerVisible.value = true
    selectedPlan.value = { ...planData }
}

const showDeleteDialog = planData => {
  isConfirmDeleteDialogVisible.value = true
  selectedPlan.value = { ...planData }
}

const removePlan = async () => {
  isConfirmDeleteDialogVisible.value = false  

  plansStores.deletePlan(selectedPlan.value.id)
    .then((res) => {
        if (res.data.success) {
                advisor.value = {
                type: 'success',
                message: 'Plan raderad!',
                show: true
            }
            fetchData()
        }
        isRequestOngoing.value = false
    })
    .catch((err) => {
        advisor.value = {
            type: 'error',
            message: err.message,
            show: true
        }
        isRequestOngoing.value = false
    })

  selectedPlan.value = {}

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const submitForm = async (country, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    country.data.append('_method', 'PUT')
    submitUpdate(country)
    return
  }

  submitCreate(country.data)
}


const submitCreate = planData => {

  plansStores.addPlan(planData)
    .then((res) => {
        if (res.data.success) {
            advisor.value = {
                type: 'success',
                message: 'Plan skapad! ',
                show: true
            }
            fetchData()
        }
        isRequestOngoing.value = false
    })
    .catch((err) => {
        advisor.value = {
            type: 'error',
            message: err.message,
            show: true
        }
        isRequestOngoing.value = false
    })

  setTimeout(() => {
      advisor.value = {
          type: '',
          message: '',
          show: false
      }
  }, 3000)
}

const submitUpdate = planData => {

  plansStores.updatePlan(planData)
    .then((res) => {
        if (res.data.success) {
                advisor.value = {
                type: 'success',
                message: 'Plan uppdaterad!',
                show: true
            }
            fetchData()
        }
        isRequestOngoing.value = false
    })
    .catch((err) => {
        advisor.value = {
            type: 'error',
            message: err.message,
            show: true
        }
        isRequestOngoing.value = false
    })

  setTimeout(() => {
      advisor.value = {
          type: '',
          message: '',
          show: false
      }
  }, 3000)
}

const updateState = async () => {
  isConfirmActiveDialogVisible.value = false

  plansStores.updateState(selectedPlan.value.id)
    .then((res) => {
        if (res.data.success) {
                advisor.value = {
                type: 'success',
                message: 'Plan uppdaterad!',
                show: true
            }
            fetchData()
        }
        isRequestOngoing.value = false
    })
    .catch((err) => {
        advisor.value = {
            type: 'error',
            message: err.message,
            show: true
        }
        isRequestOngoing.value = false
    })

  selectedPlan.value = {}

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const findPlan = plan => {
  if (!plan || !Array.isArray(plans.value)) return null

  const normalizeText = value =>
    String(value ?? '')
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")

  if (typeof plan === 'object') {
    return plans.value.find(item => item.id === plan.id) || null
  }

  return plans.value.find(item => String(item.id) === String(plan))
      || plans.value.find(item => normalizeText(item.name) === normalizeText(plan))

}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await plansStores.fetchPlans(data)

  let dataArray = [];
      
  plansStores.getPlans.forEach(element => {

    let data = {
      ID: element.id,
      NAMNET: element.name,
      MÅNADSPRIS: element.price_month,
      ÅRSPRIS: element.price_annual,
      STATU: element.state.name
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "plans", "csv");

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
                v-if="$can('create','plans')"
                prepend-icon="tabler-plus"
                class="w-100 w-md-auto"
                @click="isAddNewPlanDrawerVisible = true">
                  Ny Plan
              </VBtn>
            </div>
          </VCardText>

          <v-divider />

          <v-table class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> Namnet </th>
                <th scope="col"> Månadspris </th>
                <th scope="col"> Årspris</th>
                <th scope="col"> Status </th>
                <th scope="col" v-if="$can('edit', 'plans') || $can('delete', 'plans')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="plan in plans"
                :key="plan.id"
                style="height: 3rem;">

                <td> {{ plan.id }} </td>
                <td class="text-wrap w-100">
                  <div class="d-flex align-center gap-x-3">
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ plan.name }}
                      </span>
                    </div>
                  </div>
                </td>
                <td> {{ plan.price_month }} </td>
                <td> {{ plan.price_annual }} </td>
                <td class="text-wrap">
                  <VChip
                    label
                    :color="resolveStatus(plan.state.id)?.color"
                  >
                    {{ plan.state.name }}
                  </VChip>
                </td> 
                <!-- 👉 Actions -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'plans') || $can('delete', 'plans')">      
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
                        v-if="$can('delete','plans') && plan.state_id === 1"
                        @click="showActivateDialog(plan)">
                        <template #prepend>
                          <VIcon icon="tabler-rosette-discount-check" />
                        </template>
                        <VListItemTitle>Aktivera</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('delete','plans') && plan.state_id === 2"
                        @click="showActivateDialog(plan)">
                        <template #prepend>
                          <VIcon icon="mdi-close-circle-outline" />
                        </template>
                        <VListItemTitle>Inaktivera</VListItemTitle>
                      </VListItem>
                      <VListItem
                         v-if="$can('edit', 'plans')"
                         @click="editPlan(plan)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','plans')"
                        @click="showDeleteDialog(plan)">
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
            <tfoot v-show="!plans.length">
              <tr>
                <td
                  colspan="4"
                  class="text-center">
                  Uppgifter ej tillgängliga
                </td>
              </tr>
            </tfoot>
          </v-table>
        
          <v-divider />

          <VCardText class="d-block d-md-flex text-center align-center flex-wrap gap-4 py-3">
            <span class="text-sm text-disabled">
              {{ paginationData }}
            </span>

            <VSpacer class="d-none d-md-block"/>

            <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="4"
              :length="totalPages"/>
          
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    <!-- 👉 Add New Plan -->
    <AddNewPlanDrawer
      v-model:isDrawerOpen="isAddNewPlanDrawerVisible"
      :plan="selectedPlan"
      :features="features"
      @plan-data="submitForm"/>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent 
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Ta bort land">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort plan <strong>{{ selectedPlan.name }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removePlan">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isConfirmActiveDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmActiveDialogVisible = !isConfirmActiveDialogVisible" />

      <!-- Dialog Content -->
      <VCard :title="selectedPlan.state_id === 1 ? 'Aktivera plan' : 'Inaktivera plan'">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill {{ selectedPlan.state_id === 1 ? 'aktivera' : 'inaktivera' }} plan  <strong>{{ selectedPlan.name }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmActiveDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="updateState">
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
    subject: plans
</route>