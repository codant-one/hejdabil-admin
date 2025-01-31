<script setup>

import { useAddressesStores } from '@/stores/useAddresses'
import { useClientsStores } from '@/stores/useClients'
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

import CustomerBioPanel from '@/views/apps/ecommerce/customer/view/CustomerBioPanel.vue'
import CustomerTabAddressAndBilling from '@/views/apps/ecommerce/customer/view/CustomerTabAddressAndBilling.vue'
import CustomerTabOverview from '@/views/apps/ecommerce/customer/view/CustomerTabOverview.vue'
import CustomerTabSecurity from '@/views/apps/ecommerce/customer/view/CustomerTabSecurity.vue'

const route = useRoute()
const clientsStores = useClientsStores()
const addressesStores = useAddressesStores()

const userTab = ref(null)

const client = ref(null)
const online = ref(null)

const isRequestOngoing = ref(true)

const tabs = [
  { title: 'Descripción general' },
  { title: 'Seguridad' },
  { title: 'Envíos' }
]

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
}

const isConfirmDeleteDialogVisible = ref(false)
const selectedAddress = ref({})

watchEffect(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  if(Number(route.params.id)) {
    client.value = await clientsStores.showClient(Number(route.params.id))
    online.value = client.value.user.online
  }

  isRequestOngoing.value = false
}

const showDeleteDialog = addressData => {
  isConfirmDeleteDialogVisible.value = true
  selectedAddress.value = { ...addressData }
}

const onSubmit = (address, method) => {
    if (method === 'update') {
        submitUpdate(address.data)
        return
    }

  submitCreate(address.data)
}

const submitCreate = addressData => {

    addressData.addresses_type_id = Number(addressData.addresses_type_id)
    addressData.client_id = Number(route.params.id)
    addressData.default = (addressData.default) === true ? 1 : 0

    addressesStores.addAddress(addressData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Dirección creada ! ',
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

const submitUpdate = addressData => {

    addressData.addresses_type_id = Number(addressData.addresses_type_id)
    addressData.client_id = Number(route.params.id)
    addressData.default = (addressData.default) === true ? 1 : 0

    addressesStores.updateAddress(addressData)
        .then((res) => {
            if (res.data.success) {
                    advisor.value = {
                    type: 'success',
                    message: 'Dirección actualizada!',
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

const removeAddress = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await addressesStores.deleteAddress(selectedAddress.value.id)
  selectedAddress.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Dirección eliminada!' : res.data.message,
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


</script>

<template>
  <div>
    <VDialog
        v-model="isRequestOngoing"
        width="300"
        persistent>
                    
        <VCard
            color="primary"
            width="300">
                        
            <VCardText class="pt-3">
                Loading

                <VProgressLinear
                    indeterminate
                    color="white"
                    class="mb-0"/>
            </VCardText>
        </VCard>
    </VDialog>

    <v-col cols="12">
        <v-alert
          v-if="advisor.show"
          :type="advisor.type"
          class="mb-6">
            {{ advisor.message }}
        </v-alert>
    </v-col>

    <!-- 👉 Header  -->
    <div v-if="client" class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
        <div>
            <div class="d-flex gap-2 align-center mb-2 flex-wrap">
            <h4 class="text-h4 font-weight-medium">
                Cliente ID #{{ route.params.id }}
            </h4>
            </div>
            <div>
            <span class="text-body-1" v-if="online">
                {{  format(parseISO(online), 'MMMM d, yyyy, H:mm', { locale: es }).replace(/(^|\s)\S/g, (char) => char.toUpperCase()) }}
                <span class="text-xs">
                    (Última Conexión)
                </span>
            </span>
            </div>
        </div>
        <div class="d-flex gap-4">
            <VBtn
                variant="tonal"
                color="secondary"
                class="mb-2"
                :to="{ name: 'dashboard-admin-clients' }"
                >
                Regresar
            </VBtn>
        </div>
    </div>
    <!-- 👉 Customer Profile  -->
    <VRow v-if="client">
        <VCol
            cols="12"
            md="5"
            lg="4"
        >
            <CustomerBioPanel 
                :customer-data="client" 
                :is-supplier="false"/>
        </VCol>
        <VCol
            cols="12"
            md="7"
            lg="8">
            <VTabs
                v-model="userTab"
                class="v-tabs-pill mb-3 disable-tab-transition">
                <VTab
                    v-for="tab in tabs"
                    :key="tab.title">
                    <span>{{ tab.title }}</span>
                </VTab>
            </VTabs>
            <VWindow
                v-model="userTab"
                class="disable-tab-transition"
                :touch="false"
            >
                <VWindowItem>
                    <CustomerTabOverview
                        :customer-data="client"
                        :is-supplier="false"/>
                </VWindowItem>
                <VWindowItem>
                    <CustomerTabSecurity 
                        :user_id="client.user_id"
                        @alert="showAlert" />
                </VWindowItem>
                <VWindowItem>
                    <CustomerTabAddressAndBilling 
                        :addresses="client.addresses"
                        :is-supplier="false"
                        @submit="onSubmit"
                        @delete="showDeleteDialog"/>
                </VWindowItem>
            </VWindow>
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
      <VCard title="Eliminar Dirección">
        <VDivider class="mt-4"/>
        <VCardText>
          Está seguro de eliminar la dirección <strong>{{ selectedAddress.title }}</strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Cancel
          </VBtn>
          <VBtn @click="removeAddress">
              Accept
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<route lang="yaml">
    meta:
      action: ver
      subject: clientes
</route>