<script setup>

import { themeConfig } from '@themeConfig'
import { useClientsStores } from '@/stores/useClients'
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { useClipboard } from '@vueuse/core';

import Toaster from "@/components/common/Toaster.vue";
import CustomerBioPanel from '@/views/apps/ecommerce/customer/view/CustomerBioPanel.vue'
import CustomerBillingTable from '@/views/apps/ecommerce/customer/view/CustomerBillingTable.vue'

const route = useRoute()
const clientsStores = useClientsStores()

const client = ref(null)
const online = ref(null)

const isRequestOngoing = ref(true)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

watchEffect(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  if(Number(route.params.id) && route.name === 'dashboard-admin-clients-id') {
    client.value = await clientsStores.showClient(Number(route.params.id))
  }

  isRequestOngoing.value = false
}

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
}

const showLoading = function(value) {  
  isRequestOngoing.value = value
}

</script>

<template>
  <div>
    <v-row>
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
            <Toaster />
        </v-col>
    </v-row>
    
    <!-- 👉 Header  -->
    <div v-if="client" class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
        <div>
            <div class="d-flex gap-2 align-center mb-2 flex-wrap">
            <h4 class="text-h4 font-weight-medium">
                Client ID #{{ route.params.id }}
            </h4>
            </div>
            <div>
            <span class="text-body-1" v-if="online">
                {{  format(parseISO(online), 'MMMM d, yyyy, H:mm', { locale: es }).replace(/(^|\s)\S/g, (char) => char.toUpperCase()) }}
                <span class="text-xs">
                    (Last Connection)
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
                Back
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
                :is-supplier="false" />
        </VCol>
        <VCol
            cols="12"
            md="7"
            lg="8">

            <CustomerBillingTable 
                :client_id="Number(route.params.id)"
                @alert="showAlert"
                @loading="showLoading"
            />
        </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
    meta:
      action: view
      subject: clients
</route>