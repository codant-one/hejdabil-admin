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

// Modal select type contract
const isModalVisible = ref(false)
const selectedOption = ref(null) 

const selectItems = ref([
  { title: 'Försäljningsavtal', value: 1 },
  { title: 'Inköpsavtal', value: 2 },
  { title: 'Förmedlingsavtal', value: 3 },
  { title: 'Affärsförslag', value: 4 },
])

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

// Función para manejar el guardado/confirmación del modal
const handleConfirmSelection = () => {
  if (selectedOption.value) {
    console.log('Opción seleccionada:', selectedOption.value)
    // Aquí puedes añadir la lógica para usar el valor seleccionado
    // Por ejemplo, emitir un evento, llamar a una API, etc.
    alert(`Has seleccionado: ${selectedOption.value}`) // Ejemplo
  } else {
    alert('Por favor, selecciona una opción.')
    return; // No cerrar el modal si no se seleccionó nada (opcional)
  }
  isModalVisible.value = false // Cierra el modal después de confirmar
}

const handleCloseModal = () => {
  isModalVisible.value = false
  // Opcional: resetear la selección si se cancela
  // selectedOption.value = null 
}

const addAgreements = () => {

   if(selectedOption.value === 1)
    router.push({ name : 'dashboard-admin-agreements-sales-agreements' })
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
                v-if="$can('create','agreements')"
                class="w-100 w-md-auto"
                prepend-icon="tabler-plus"
                @click="isModalVisible = true">
                Skapa
              </VBtn>
            </div>
          </VCardText>

          <VDivider />

          <VTable class="text-no-wrap d-none">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> #ID </th>
                <th scope="col"> xxx </th>
                <th scope="col"> xxx </th>
                <th scope="col"> xxx </th>
                <th scope="col"> xxx </th>
                <th scope="col" v-if="$can('edit', 'agreements') || $can('delete', 'agreements')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              
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

          <VCardText class="d-none  text-center align-center flex-wrap gap-4 py-3">
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
    <!--Modal Select type Contract-->
    <!-- 👉 El Modal (Dialog) -->
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


      <!-- Dialog Content -->
      <VCard title="Skapa">
        <VCardText>
          <VRow>
            <VCol cols="12">
              <VSelect
                v-model="selectedOption"
                :items="selectItems"
                item-title="title"      
                item-value="value"
                label="Välj typ"
                placeholder="Välj typ"
                clearable
                outlined
              />
              <!-- Si selectItems fuera un array de strings: ['Alfa', 'Beta', 'Gamma'] -->
              <!-- No necesitarías item-title ni item-value -->
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="handleCloseModal"
          >
            Avbryt
          </VBtn>
          <VBtn
            @click="addAgreements"
          >
            Bekräfta
          </VBtn>
        </VCardActions>
      </VCard>
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