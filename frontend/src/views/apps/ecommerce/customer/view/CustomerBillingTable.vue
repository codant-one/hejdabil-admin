<script setup>

import { useBillingsStores } from '@/stores/useBillings'
import { formatNumber } from '@/@core/utils/formatters'
import { themeConfig } from '@themeConfig'
import router from '@/router'

const props = defineProps({
  client_id: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['alert', 'loading'])
const billingsStores = useBillingsStores()

const billings = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalBillings = ref(0)
const isConfirmStateDialogVisible = ref(false)
const isConfirmSendMailVisible = ref(false)
const emailDefault = ref(true)
const selectedTags = ref([])
const existingTags = ref([])
const selectedBilling = ref({})
const isValid = ref(false)
const userData = ref(null)
const role = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const paginationData = computed(() => {
  const firstIndex = billings.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = billings.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalBillings.value } register`
})

watchEffect(fetchData)

async function fetchData() {

    let data = {
        search: searchQuery.value,
        orderByField: 'id',
        orderBy: 'desc',
        limit: rowPerPage.value,
        page: currentPage.value,
        client_id: props.client_id
    }

    await billingsStores.fetchBillings(data)

    billings.value = billingsStores.getBillings
    totalPages.value = billingsStores.last_page
    totalBillings.value = billingsStores.billingsTotalCount

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value.roles[0].name

    billings.value.forEach(billing => {
      billing.checked = false;
      billing.sent = false
    });
}

const updateBilling = billingData => {
  isConfirmStateDialogVisible.value = true
  selectedBilling.value = { ...billingData }
}

const showBilling = billingData => {
  router.push({ name : 'dashboard-admin-billings-id', params: { id: billingData.id } })
}

const editBilling = billingData => {
  router.push({ name : 'dashboard-admin-billings-edit-id', params: { id: billingData.id } })
}

const openLink = function (billingData) {
  window.open(themeConfig.settings.urlStorage + billingData.file)
}

const updateState = async () => {
  isConfirmStateDialogVisible.value = false

  emit('loading', true)

  let res = await billingsStores.updateState(selectedBilling.value.id)

  emit('loading', false)
  selectedBilling.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Fakturan uppdaterad!' : res.data.message,
    show: true
  }

  emit('alert', advisor)

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }

    emit('alert', advisor)
  }, 3000)

  await fetchData()

  return true
}

const printInvoice = async(billing) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + billing.file);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);
    
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = blobUrl;
    
    iframe.onload = () => {
      iframe.contentWindow.print();
    };
    
    document.body.appendChild(iframe);
  } catch (error) {
    console.error('Error:', error);
  }
}

const duplicate = (billing) => {
  router.push({ name : 'dashboard-admin-billings-duplicate-id', params: { id: billing.id } })
}

const credit = (billing) => {
  router.push({ name : 'dashboard-admin-billings-credit-id', params: { id: billing.id } })
}

const send = billingData => {
  isConfirmSendMailVisible.value = true
  selectedBilling.value = { ...billingData }
}

const addTag = (event) => {
  const newTag = event.target.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (newTag && emailRegex.test(newTag)) {
    // no hago nada, sino invalido
  } else {
    isValid.value = true
    selectedTags.value.pop();
  }
};

const sendMails = async () => {

  if(!isValid.value) {
    isConfirmSendMailVisible.value = false
    emit('loading', true)

    let data = {
      id: selectedBilling.value.id,
      emailDefault: emailDefault.value,
      emails: selectedTags.value
    }

    let res = await billingsStores.sendMails(data)
    
    emit('loading', false)

    advisor.value = {
      type: res.data.success ? 'success' : 'error',
      message: res.data.success ? 'Fakturan är skickad!' : res.data.message,
      show: true
    }

    emit('alert', advisor)

    setTimeout(() => {
      selectedTags.value = []
      existingTags.value = []
      emailDefault.value = true 

      advisor.value = {
        type: '',
        message: '',
        show: false
      }

      emit('alert', advisor)
    }, 3000)

    await fetchData()
    
    return true
  }
}
</script>

<template>
    <section>
      <VCard title="Billings">
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

          <VSpacer class="d-none d-md-block"/>

          <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto">
              <!-- 👉 Search  -->
              <div style="width: 20rem;">
                <VTextField
                    v-model="searchQuery"
                    placeholder="Sök"
                    density="compact"
                    clearable
                />
              </div>
          </div>
          </VCardText>

          <VDivider />

          <VTable class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
                <tr>
                  <th scope="col"> # FAKTURA </th>
                  <th scope="col"> KUND </th>
                  <th scope="col"> Summa </th>
                  <th scope="col"> FAKTURADATUM </th>
                  <th scope="col"> FÖRFALLER </th>
                  <th class="text-center" scope="col"> BETALD </th>
                  <th class="text-center" scope="col"> SKICKAD </th>                
                  <th class="text-center" scope="col" v-if="$can('edit', 'billings') || $can('delete', 'billings')"></th>
                </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="billing in billings"
                :key="billing.id"
                style="height: 3rem;">

                <td> {{ billing.invoice_id }} </td>
                <td class="text-wrap">
                    <span class="font-weight-medium cursor-pointer text-primary" @click="showBilling(billing)">
                      {{ billing.client.fullname ?? '' }}
                    </span>
                </td>
                <td class="text-end"> {{ formatNumber(billing.total) ?? '0,00' }} kr</td>
                <td> {{ billing.invoice_date }} </td>
                <td> {{ billing.due_date }} </td>
                <td class="text-center">            
                  <VCheckbox
                    v-model="billing.checked"
                    color="info"
                    class="w-100 text-center d-flex justify-content-center"
                    :disabled="billing.state_id === 7 || billing.state_id === 9"
                    :value="(billing.state_id === 7 || billing.state_id === 9) ? false : true"
                    @click.prevent="updateBilling(billing)"
                  />
                </td>
                <td class="text-center">
                  <VCheckbox
                    v-model="billing.sent"
                    color="info"
                    class="w-100 text-center d-flex justify-content-center"
                    :disabled="billing.is_sent === 1"
                    :value="(billing.is_sent === 1) ? false : true"
                    @click.prevent="send(billing)"
                  />
                </td>
                <!-- 👉 Acciones -->
                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'billings') || $can('delete', 'billings')">      
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
                        v-if="$can('edit', 'billings')"
                        @click="printInvoice(billing)">
                        <template #prepend>
                          <VIcon icon="mdi-printer" />
                        </template>
                        <VListItemTitle>Skriv ut</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('edit', 'billings')"
                        @click="openLink(billing)">
                        <template #prepend>
                          <VIcon icon="mdi-file-pdf-box" />
                        </template>
                        <VListItemTitle>Visa som PDF</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('edit', 'billings')"
                        @click="duplicate(billing)">
                        <template #prepend>
                          <VIcon icon="mdi-content-copy" />
                        </template>
                        <VListItemTitle>Duplicera</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('edit', 'billings') && billing.state_id === 8"
                        @click="sendReminder(billing)">
                        <template #prepend>
                          <VIcon icon="mdi-email-fast" />
                        </template>
                        <VListItemTitle>Påminnelse</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('edit', 'billings')"
                        @click="send(billing)">
                        <template #prepend>
                          <VIcon icon="mdi-email-fast" />
                        </template>
                        <VListItemTitle>Skicka</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('edit', 'billings') && (billing.state_id === 4 || billing.state_id === 8)"
                        @click="editBilling(billing)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','billings') && billing.state_id === 7"
                        @click="credit(billing)">
                        <template #prepend>
                          <VIcon icon="tabler-trash" />
                        </template>
                        <VListItemTitle>Kreditera</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </td>
              </tr>
            </tbody>
            <!-- 👉 table footer  -->
            <tfoot v-show="!billings.length">
                <tr>
                <td
                    :colspan="role === 'Supplier' ? 8 : 9"
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

      <!-- 👉 Confirm send -->
      <VDialog
        v-model="isConfirmSendMailVisible"
        persistent
        class="v-dialog-sm" >
        <!-- Dialog close btn -->
          
        <DialogCloseBtn @click="isConfirmSendMailVisible = !isConfirmSendMailVisible" />

        <!-- Dialog Content -->
        <VCard title="Skicka fakturan via e-post">
          <VDivider class="mt-4"/>
          <VCardText>
            Är du säker på att du vill skicka fakturor till följande e-postadresser?
          </VCardText>
          <VCardText class="d-flex flex-column gap-2">
            <VCheckbox
              v-model="emailDefault"
              :label="selectedBilling.client.email"
            />

            <VCombobox
              v-model="selectedTags"
              :items="existingTags"
              label="Ange e-postadresser för att skicka fakturan"
              multiple
              chips
              deletable-chips
              clearable
              @blur="addTag"
              @keydown.enter.prevent="addTag"
              @input="isValid = false"
            /> 
            <span class="text-xs text-error" v-if="isValid">E-postadressen måste vara en giltig e-postadress</span>
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="isConfirmSendMailVisible = false">
                Avbryt
            </VBtn>
            <VBtn @click="sendMails">
                Skicka
            </VBtn>
          </VCardText>
        </VCard>
      </VDialog>

      <!-- 👉 Update State -->
      <VDialog
        v-model="isConfirmStateDialogVisible"
        persistent
        class="v-dialog-sm" >
        <!-- Dialog close btn -->
          
        <DialogCloseBtn @click="isConfirmStateDialogVisible = !isConfirmStateDialogVisible" />

        <!-- Dialog Content -->
        <VCard title="Uppdatera status">
          <VDivider class="mt-4"/>
          <VCardText>
            Är du säker på att du vill uppdatera fakturans status? <strong>#{{ selectedBilling.invoice_id }}</strong> till betalda?
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="isConfirmStateDialogVisible = false">
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

<style scope>

  .text-center {
      text-align: center !important;
  }

  .justify-content-center {
    justify-content: center !important;
  }

  .v-input--disabled svg rect {
    fill: #28C76F !important;
  }

  .v-input--disabled {
      pointer-events: visible !important;
      cursor: no-drop !important;
  }
</style>