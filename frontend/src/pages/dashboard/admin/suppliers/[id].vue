<script setup>

import { themeConfig } from '@themeConfig'
import { useSuppliersStores } from '@/stores/useSuppliers'
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { useClipboard } from '@vueuse/core';

import Toaster from "@/components/common/Toaster.vue";
import CustomerBioPanel from '@/views/apps/ecommerce/customer/view/CustomerBioPanel.vue'
import CustomerTabOverview from '@/views/apps/ecommerce/customer/view/CustomerTabOverview.vue'
import CustomerTabSecurity from '@/views/apps/ecommerce/customer/view/CustomerTabSecurity.vue'
import CustomerTabAddressAndBilling from '@/views/apps/ecommerce/customer/view/CustomerTabAddressAndBilling.vue'
import CustomerTabCompany from '@/views/apps/ecommerce/customer/view/CustomerTabCompany.vue'

const route = useRoute()
const suppliersStores = useSuppliersStores()
const cp = useClipboard()

const userTab = ref(null)

const supplier = ref(null)
const online = ref(null)

const isRequestOngoing = ref(true)

const tabs = [
    { title: 'Översikt' },
    { title: 'Säkerhet' },
    { title: 'Fakturering' },
    { title: 'Företag' }
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

watchEffect(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  if(Number(route.params.id) && route.name === 'dashboard-admin-suppliers-id') {
    supplier.value = await suppliersStores.showSupplier(Number(route.params.id))
  }

  isRequestOngoing.value = false
}

const handleDownload = async(data) => {
    if(data.icon === 'pdf' || data.icon === 'docx' || data.icon === 'doc') {
        try {
            const link = document.createElement('a');
            link.href = themeConfig.settings.urlStorage + 'documents/' + data.document
            link.target = '_blank'
            document.body.appendChild(link);
            link.click();

            link.parentNode.removeChild(link);

            advisor.value.type = 'success'
            advisor.value.show = true
            advisor.value.message = 'Framgångsrik nedladdning!'

        } catch (error) {

            advisor.value.type = 'error'
            advisor.value.show = true
            advisor.value.message = 'Fel vid nedladdning av dokument:' + error
        }
    } else {
        try {
            const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + 'documents/' + data.document);
            const blob = await response.blob();

            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;

            link.setAttribute('download', 'image.jpg');

            document.body.appendChild(link);
            link.click();

            window.URL.revokeObjectURL(url);

            advisor.value.type = 'success'
            advisor.value.show = true
            advisor.value.message = 'Framgångsrik nedladdning!'

        } catch (error) {

            advisor.value.type = 'error'
            advisor.value.show = true
            advisor.value.message = 'Fel vid nedladdning av bild:' + error
        }
    }

    setTimeout(() => {
        advisor.value.show = false
        advisor.value.type = ''
        advisor.value.message = ''
    }, 5000)
}

const handleCopy = (data) => {
  
    cp.copy(data)

    advisor.value.type = 'success'
    advisor.value.show = true
    advisor.value.message = 'Bankkonto kopierat!'

    setTimeout(() => {
        advisor.value.show = false
        advisor.value.type = ''
        advisor.value.message = ''
    }, 5000)
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
                   Lastning
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
    <div v-if="supplier" class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
        <div>
            <div class="d-flex gap-2 align-center mb-2 flex-wrap">
            <h4 class="text-h4 font-weight-medium">
                Leverantörens ID #{{ route.params.id }}
            </h4>
            </div>
            <div>
            <span class="text-body-1" v-if="online">
                {{  format(parseISO(online), 'MMMM d, yyyy, H:mm', { locale: es }).replace(/(^|\s)\S/g, (char) => char.toUpperCase()) }}
                <span class="text-xs">
                    (Sista anslutningen)
                </span>
            </span>
            </div>
        </div>
        <div class="d-flex gap-4">
            <VBtn
                variant="tonal"
                color="secondary"
                class="mb-2"
                :to="{ name: 'dashboard-admin-suppliers' }"
                >
                Tillbaka
            </VBtn>
        </div>
    </div>
    <!-- 👉 Customer Profile  -->
    <VRow v-if="supplier">
        <VCol
            cols="12"
            md="5"
            lg="4"
        >
            <CustomerBioPanel 
                :customer-data="supplier"
                :is-supplier="true" />
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
                        :customer-data="supplier"
                        :is-supplier="true"/>
                </VWindowItem>
                <VWindowItem>
                    <CustomerTabSecurity 
                        :user_id="supplier.user_id"
                        @alert="showAlert" />
                </VWindowItem>
                <VWindowItem>
                    <CustomerTabAddressAndBilling 
                        :customer-data="supplier"
                        :is-supplier="true"
                        @copy="handleCopy"
                        @download="handleDownload"
                        @alert="showAlert"
                        @updateBalance="fetchData"/>
                </VWindowItem>
                <VWindowItem>
                    <CustomerTabCompany
                        :customer-data="supplier"
                        :is-supplier="true"
                        @copy="handleCopy"
                        @download="handleDownload"/>
                </VWindowItem>
            </VWindow>
        </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
    meta:
      action: view
      subject: suppliers
</route>