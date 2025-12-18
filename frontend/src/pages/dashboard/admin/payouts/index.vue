<script setup>

import { usePayoutsStores } from '@/stores/usePayouts'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { avatarText } from '@/@core/utils/formatters'
import { useAuthStores } from '@/stores/useAuth'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import AddNewPayoutDialog from './AddNewPayoutDialog.vue'
import PayoutDetailDialog from './PayoutDetailDialog.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const authStores = useAuthStores()
const payoutsStores = usePayoutsStores()
const ability = useAppAbility()
const emitter = inject("emitter")

const payouts = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalPayouts = ref(0)
const isRequestOngoing = ref(true)
const isAddNewPayoutDrawerVisible = ref(false)
const isPayoutDetailDialogVisible = ref(false)
const selectedPayoutId = ref(null)
const isConfirmDeleteDialogVisible = ref(false)
const selectedPayout = ref({})

const userData = ref(null)
const role = ref(null)
const payer_alias = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = payouts.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = payouts.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalPayouts.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewPayoutDrawerVisible.value)
        selectedPayout.value = {}
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await payoutsStores.fetchPayouts(data)

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  const { user_data, userAbilities } = await authStores.me(userData.value)

  localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

  ability.update(userAbilities)

  localStorage.setItem('user_data', JSON.stringify(user_data))

  if(role.value === 'Supplier') {
    payer_alias.value = user_data.supplier.payout_number
  }
    
  payouts.value = payoutsStores.getPayouts
  totalPages.value = payoutsStores.last_page
  totalPayouts.value = payoutsStores.payoutsTotalCount

  isRequestOngoing.value = false
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const showDeleteDialog = payoutData => {
  isConfirmDeleteDialogVisible.value = true
  selectedPayout.value = { ...payoutData }
}

const seePayout = payoutData => {
  selectedPayoutId.value = payoutData.id
  isPayoutDetailDialogVisible.value = true
}

const handlePayoutUpdated = updatedPayout => {
  // Actualizar el payout en la lista local
  const index = payouts.value.findIndex(p => p.id === updatedPayout.id)
  if (index !== -1) {
    payouts.value[index] = { ...payouts.value[index], ...updatedPayout }
  }
  
  advisor.value = {
    type: 'success',
    message: 'Status uppdaterad!',
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

const removePayout = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await payoutsStores.deletePayout(selectedPayout.value.id)
  selectedPayout.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Betalning raderad!' : res.data.message,
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

const submitForm = async (payoutData) => {
  isRequestOngoing.value = true

  submitCreate(payoutData.data)
}


const submitCreate = payoutData => {
  payoutData.payer_alias = payer_alias.value

  payoutsStores.addPayout(payoutData)
    .then((res) => {
        if (res.data.success) {
            advisor.value = {
                type: 'success',
                message: 'Betalning skapad!',
                show: true
            }

            fetchData()
            
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
        // Intentamos mostrar el mensaje que viene desde el backend (por ejemplo, "Incorrect ssn format")
        const apiMessage = err.response?.data?.message
        const apiCode = err.response?.data?.errorCode

        advisor.value = {
          type: 'error',
          message: apiMessage
            ? (apiCode ? `${apiCode}: ${apiMessage}` : apiMessage)
            : err.message,
            show: true
        }
        
        isRequestOngoing.value = false

        fetchData()

        setTimeout(() => {
            advisor.value = {
                type: '',
                message: '',
                show: false
            }
        }, 3000)
    })
}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await payoutsStores.fetchPayouts(data)

  let dataArray = [];
      
  payoutsStores.getPayouts.forEach(element => {

    let data = {
      ID: element.id,
      USER: element.user.name + ' ' + (element.user.last_name ?? ''),
      AMOUNT: element.amount,
      PAYEE_ALIAS: element.payee_alias,
      STATE: element.state.name
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "payouts", "csv");

  isRequestOngoing.value = false

}

const openPayoutDialog = () => {  
  if (!payer_alias.value) {
    advisor.value = {
      type: 'error',
      message: 'Det går inte att skapa betalning: konfigurerat betalningsnummer saknas',
      show: true
    }
    setTimeout(() => {
      advisor.value = { type: '', message: '', show: false }
    }, 3000)
    return
  }
  
  isAddNewPayoutDrawerVisible.value = true
}
</script>

<template>
  <section>
    <VRow>
      <LoadingOverlay :is-loading="isRequestOngoing" />

      <!-- Payout Detail Dialog -->
      <PayoutDetailDialog
        v-model:isDialogVisible="isPayoutDetailDialogVisible"
        :payout-id="selectedPayoutId"
        @payout-updated="handlePayoutUpdated"
      />

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
                v-if="$can('create','payouts')"
                prepend-icon="tabler-plus"
                class="w-100 w-md-auto"
                @click="openPayoutDialog">
                  Ny betalning
              </VBtn>
            </div>
          </VCardText>

          <v-divider />

          <v-table class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> SWISH ID </th>
                <th scope="col"> REFERENCE </th>
                <th scope="col"> AMOUNT </th>
                <th scope="col"> PAYEE ALIAS </th>
                <th scope="col"> SKAPAD AV </th>
                <th scope="col" v-if="$can('edit', 'payouts') || $can('delete', 'payouts')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="payout in payouts"
                :key="payout.id"
                style="height: 3rem;">

                <td> {{ payout.id }} </td>
                <td> {{ payout.swish_id ?? ''}} </td>
                <td> {{ payout.reference ?? ''}} </td>
                <td> {{ payout.amount ?? ''}} </td>
                <td> {{ payout.payee_alias ?? ''}} </td>
                <td class="text-wrap">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      :variant="payout.user?.avatar ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="payout.user?.avatar"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + payout.user.avatar"
                      />
                        <span v-else>{{ payout.user ? avatarText(payout.user.name) : '?' }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ payout.user?.name }} {{ payout.user?.last_name ?? '' }} 
                      </span>
                      <span class="text-sm text-disabled">{{ payout.user?.email }}</span>
                    </div>
                  </div>
                </td>
                <!-- 👉 Actions -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'payouts') || $can('delete', 'payouts')">      
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
                        v-if="$can('view','payouts')"
                        @click="seePayout(payout)">
                        <template #prepend>
                          <VIcon icon="tabler-eye" />
                        </template>
                        <VListItemTitle>Visa detaljer</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','payouts')"
                        @click="showDeleteDialog(payout)">
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
            <tfoot v-show="!payouts.length">
              <tr>
                <td
                  colspan="8"
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
              :total-visible="5"
              :length="totalPages"/>
          
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 👉 Add New Payout -->
    <AddNewPayoutDialog
      v-if="payer_alias"
      v-model:isDialogOpen="isAddNewPayoutDrawerVisible"
      :payer_alias="payer_alias"
      @payout-data="submitForm"
    />

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Ta bort klient">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort klienten <strong>{{ selectedPayout.user.name }} {{ selectedPayout.user.last_name ?? '' }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removePayout">
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
    subject: payouts
</route>