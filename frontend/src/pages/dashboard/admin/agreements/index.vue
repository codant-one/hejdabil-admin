<script setup>

import { useAgreementsStores } from '@/stores/useAgreements'
import { requiredValidator } from '@/@core/utils/validators'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { formatNumber } from '@/@core/utils/formatters'
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'

const agreementsStores = useAgreementsStores()
const emitter = inject("emitter")

const agreements = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalAgreements = ref(0)
const isRequestOngoing = ref(true)
const isConfirmDeleteDialogVisible = ref(false)
const selectedAgreement = ref({})
const state_id = ref(null)

const agreementTypes = ref([])

// Modal select type contract
const isModalVisible = ref(false)
const agreement_type_id = ref(null) 
const refVForm = ref()

const userData = ref(null)
const role = ref(null)
const supplier = ref([])


const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = agreements.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = agreements.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalAgreements.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

onMounted(async () => {

  await loadData()
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
    orderByField: 'agreement_id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    state_id: state_id.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await agreementsStores.fetchAgreements(data)

  agreements.value = agreementsStores.getAgreements
  totalPages.value = agreementsStores.last_page
  totalAgreements.value = agreementsStores.agreementsTotalCount

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name
  
  isRequestOngoing.value = false

}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const loadData = async () => {
  await agreementsStores.info()

  agreementTypes.value = agreementsStores.agreementTypes
}

const editAgreement = agreementData => {
  router.push({ name : 'dashboard-admin-agreement-edit-id', params: { id: agreementData.id } })
}

const showDeleteDialog = agreementData => {
  isConfirmDeleteDialogVisible.value = true
  selectedAgreement.value = { ...agreementData }
}


const removeAgreement = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await agreementsStores.deleteAgreement(selectedAgreement.value.id)
  selectedAgreement.value = {}

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

const download = async(agreement) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + agreement.file);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = agreement.file.replace('pdfs/', '');
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

  await agreementsStores.fetchAgreements(data)

  let dataArray = [];
      
  agreementsStores.getAgreements.forEach(element => {

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
    .exportDataFromJSON(dataArray, "agreements", "csv");

  isRequestOngoing.value = false

}

const handleCloseModal = () => {
  isModalVisible.value = false
}

const addAgreements = () => {

  refVForm.value?.validate().then(({ valid: isValid }) => {

    if (isValid) {

      if(agreement_type_id.value === 1)
        router.push({ name : 'dashboard-admin-agreements-sales-agreements' })
    }

  })
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

        <Toaster />

        <VCard title="">
          <VCardText class="d-flex align-center flex-wrap gap-4">
            <div class="d-flex align-center w-100 w-md-auto">
              <span class="text-no-wrap me-3">Visa:</span>
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

            <VSpacer class="d-md-block"/>

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
                v-if="$can('create','agreements')"
                class="w-100 w-md-auto"
                prepend-icon="tabler-plus"
                @click="isModalVisible = true">
                Skapa
              </VBtn>
            </div>
          </VCardText>

          <VDivider />

          <VTable class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> REG. NR </th>
                <th scope="col"> INBYTESFORDON REG. NR </th>
                <th scope="col" class="text-end"> KREDIT / LEASING </th>
                <th scope="col"> TYP </th>
                <th scope="col"> SKAPAD </th>
                <th scope="col"> SKAPAD AV </th>
                <th scope="col"> SIGNERA STATUS </th>
                <th scope="col" v-if="$can('edit', 'agreements') || $can('delete', 'agreements')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="agreement in agreements"
                :key="agreement.id"
                style="height: 3rem;">
                <td> {{ agreement.vehicle_client.vehicle.reg_num }}</td>
                <td> {{ agreement.vehicle_interchange?.reg_num }} </td>                
                <td class="text-end"> {{ formatNumber(agreement.installment_amount ?? 0) }} kr </td>
                <td> {{ agreement.agreement_type.name  }}</td>          
                <td>  
                  {{ new Date(agreement.created_at).toLocaleString('sv-SE', { 
                      year: 'numeric', 
                      month: '2-digit', 
                      day: '2-digit', 
                      hour: '2-digit', 
                      minute: '2-digit',
                      hour12: false
                  }) }}
                </td>
                <td> 
                  {{ agreement.supplier ? 
                    agreement.supplier.user.name + ' ' + agreement.supplier.user.last_name : 
                    userData.name + ' ' + userData.last_name
                   }}
                </td>
                <td></td>
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'billing') || $can('delete', 'billing')">      
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
                      <VListItem >
                        <template #prepend>
                          <VIcon icon="mdi-draw" />
                        </template>
                        <VListItemTitle>Signera</VListItemTitle>
                      </VListItem>
                      <VListItem >
                        <template #prepend>
                          <VIcon icon="mdi-file-pdf-box" />
                        </template>
                        <VListItemTitle>Kontantkvitto</VListItemTitle>
                      </VListItem>
                      <VListItem>
                        <template #prepend>
                          <VIcon icon="mdi-file-pdf-box" />
                        </template>
                        <VListItemTitle>Sänd PDF</VListItemTitle>
                      </VListItem>
                      <VListItem @click="download(agreement)">
                        <template #prepend>
                          <VIcon icon="mdi-cloud-download-outline"/>
                        </template>
                        <VListItemTitle>Ladda ner</VListItemTitle>
                      </VListItem>
                      <VListItem>
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem >
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
            <tfoot v-show="!agreements.length">
              <tr>
                <td
                  colspan="8"
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
          Är du säker att du vill ta bort leverantör <strong>{{ selectedAgreement.user.name }} {{ selectedAgreement.user.last_name ?? '' }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeAgreement">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!--Modal Select type Contract-->
    <VDialog
      v-model="isModalVisible"
      max-width="500"
    >
      <VBtn
        icon
        variant="text"
        color="default"
        size="small"
        @click="handleCloseModal"
        style="position: absolute; top: 10px; right: 10px; z-index: 1;"
      >
        <VIcon icon="tabler-x" />
      </VBtn>
    
      <VForm
        ref="refVForm"
        @submit.prevent="addAgreements"
      >
        <!-- Dialog Content -->
        <VCard title="Skapa">
          <VCardText>
            <VRow>
              <VCol cols="12">
                <VAutocomplete
                  v-model="agreement_type_id"
                  :items="agreementTypes"
                  item-title="name"      
                  item-value="id"
                  label="Välj typ"
                  placeholder="Välj typ"
                  :rules="[requiredValidator]"
                  clearable
                />
              </VCol>
            </VRow>
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="handleCloseModal"
            >
              Avbryt
            </VBtn>
            <VBtn type="submit" >
              Bekräfta
            </VBtn>
          </VCardText>
        </VCard>
      </VForm>
    </VDialog>
    <!--End Modal Select type Contract-->  

  </section>
</template>

<style scope>
  .search {
      width: 100%;
  }

  @media(min-width: 991px){
      .search {
          width: 20rem;
      }
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: agreements
</route>