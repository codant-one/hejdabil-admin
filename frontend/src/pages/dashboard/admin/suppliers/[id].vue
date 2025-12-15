<script setup>

import { themeConfig } from '@themeConfig'
import { useSuppliersStores } from '@/stores/useSuppliers'
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { useClipboard } from '@vueuse/core';
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import Toaster from "@/components/common/Toaster.vue";
import CustomerBioPanel from '@/views/apps/ecommerce/customer/view/CustomerBioPanel.vue'
import CustomerTabOverview from '@/views/apps/ecommerce/customer/view/CustomerTabOverview.vue'
import CustomerTabSecurity from '@/views/apps/ecommerce/customer/view/CustomerTabSecurity.vue'
import CustomerTabAddressAndBilling from '@/views/apps/ecommerce/customer/view/CustomerTabAddressAndBilling.vue'
import CustomerTabCompany from '@/views/apps/ecommerce/customer/view/CustomerTabCompany.vue'

const route = useRoute()
const suppliersStores = useSuppliersStores()
const cp = useClipboard()

const userTab = ref(0)

const supplier = ref(null)
const online = ref(null)

const isRequestOngoing = ref(true)

const tabs = [
    { icon: 'mdi-cog', title: 'Översikt' },
    { icon: 'tabler-lock', title: 'Säkerhet' },
    { icon: 'mdi-invoice-list', title: 'Fakturering' },
    { icon: 'tabler-building-store', title: 'Företag' }
]
const isMobile = ref(false)

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

onMounted(async () => {

    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
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
        </VCol>
    </VRow>
    
    <!-- 👉 Header  -->
    <div v-if="supplier" class="d-block d-md-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
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
                class="mb-2 w-100 w-md-auto"
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
            <VTabs v-model="userTab" grow stacked class="mb-5">
                <VTab
                    v-for="tab in tabs"
                    :key="tab.title">
                    <VIcon
                        :size="18"
                        :icon="tab.icon"
                        />
                    <span v-if="!isMobile">{{ tab.title }}</span>
                </VTab>
            </VTabs>
    
            <VWindow v-model="userTab">
                <VWindowItem :value="0">
                    <CustomerTabOverview
                        :customer-data="supplier"
                        :is-supplier="true"/>
                </VWindowItem>
                <VWindowItem :value="1">
                    <CustomerTabSecurity 
                        :user_id="supplier.user_id"
                        @alert="showAlert" />
                </VWindowItem>
                <VWindowItem :value="2">
                    <CustomerTabAddressAndBilling 
                        :customer-data="supplier"
                        :is-supplier="true"
                        @copy="handleCopy"
                        @download="handleDownload"
                        @alert="showAlert"
                        @updateBalance="fetchData"/>
                </VWindowItem>
                <VWindowItem :value="3">
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