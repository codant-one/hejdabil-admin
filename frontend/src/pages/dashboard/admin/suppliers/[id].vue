<script setup>

import { useSuppliersStores } from '@/stores/useSuppliers'
import { useDisplay } from "vuetify";
import { themeConfig } from "@themeConfig";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import Toaster from "@/components/common/Toaster.vue";
import SupplierProfile from '@/views/dashboard/profile/SupplierProfile.vue'
import CustomerTabOverview from '@/views/apps/ecommerce/customer/view/CustomerTabOverview.vue'
import CustomerTabSecurity from '@/views/apps/ecommerce/customer/view/CustomerTabSecurity.vue'
import CustomerTabBilling from '@/views/apps/ecommerce/customer/view/CustomerTabBilling.vue'
import CustomerTabCompany from '@/views/apps/ecommerce/customer/view/CustomerTabCompany.vue'

defineProps({
  id: [String, Number],
});

const route = useRoute()
const suppliersStores = useSuppliersStores()

const userTab = ref(0)
const supplier = ref(null)
const isRequestOngoing = ref(true)
const sectionEl = ref(null)

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const avatar = ref('')
const avatarOld = ref('')
const haveAvatar = ref(false)
const avatar_id = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const tabs = [
    { icon: 'custom-tabler', title: 'Översikt' },
    { icon: 'custom-password-outlined', title: 'Säkerhet' },
    { icon: 'custom-facture', title: 'Fakturering' },
    { icon: 'custom-business', title: 'Företag' }
]

watchEffect(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  if(Number(route.params.id) && route.name === 'dashboard-admin-suppliers-id') {
    supplier.value = await suppliersStores.showSupplier(Number(route.params.id))

    avatarOld.value = themeConfig.settings.urlStorage + supplier.value.user.avatar
    avatar.value = themeConfig.settings.urlStorage + supplier.value.user.avatar
    haveAvatar.value = supplier.value.user.avatar === null ? false : true
    avatar_id.value = supplier.value.user.user_detail.avatar_id
    //console.log('supplier', supplier.value)
  }

  isRequestOngoing.value = false
}

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
}

const showLoading = function (value) {
  isRequestOngoing.value = value;
};

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value
  if (!el) return

  const rect = el.getBoundingClientRect()
  const remaining = Math.max(0, window.innerHeight - rect.top - 25)
  el.style.minHeight = `${remaining}px`
}

onMounted(() => {
  resizeSectionToRemainingViewport()
  window.addEventListener('resize', resizeSectionToRemainingViewport)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
})
</script>

<template>
    <section class="page-section" ref="sectionEl">
        <LoadingOverlay :is-loading="isRequestOngoing" />

        <VSnackbar
            v-model="advisor.show"
            transition="scroll-y-reverse-transition"
            :location="snackbarLocation"
            :color="advisor.type"
            class="snackbar-alert snackbar-dashboard"
        >
            <span class="snackbar-message">{{ advisor.message }}</span>
        </VSnackbar> 
        
        <Toaster />
        
        <VCard class="client-slug card-fill h-100" v-if="supplier">

            <VCardText
                class="pt-4 pb-0 px-4"
                :class="$vuetify.display.smAndDown ? 'pt-6 pb-0 px-6' : ''"
            >
                <VBtn
                class="btn-light"
                :class="$vuetify.display.smAndDown ? 'flex-1 order-1' : 'w-auto'"
                :to="{ name: 'dashboard-admin-suppliers' }"
                >
                <template #prepend>
                    <VIcon icon="custom-return" size="24" />
                </template>
                Tillbaka
                </VBtn>
            </VCardText>

            <VCardText
                class="d-flex flex-column pa-4 gap-4"
                :class="$vuetify.display.smAndDown ? 'pa-6 gap-6 pt-8' : ''"
            >
                <SupplierProfile
                    :user="supplier.user"
                    :avatarOld="avatarOld"
                    :avatar="avatar"
                    :haveAvatar="haveAvatar"
                    :avatarId="avatar_id"
                    :supplier="supplier"
                    :show-button="false"
                />
            </VCardText>

            <VCardText>
                <VTabs 
                    v-model="userTab"
                     grow
                    :show-arrows="false"
                     class="suppliers-tabs">
                    <VTab
                        v-for="tab in tabs"
                        :key="tab.title">
                        <VIcon
                            :size="24"
                            :icon="tab.icon"
                            />
                        <span>{{ tab.title }}</span>
                    </VTab>
                </VTabs>
        
                <VWindow v-model="userTab">
                    <VWindowItem :value="0">
                        <CustomerTabOverview
                            :customer-data="supplier"
                            :is-supplier="true"
                            @loading="showLoading"
                            
                        />
                    </VWindowItem>
                    <VWindowItem :value="1">
                        <CustomerTabSecurity 
                            :user_id="supplier.user_id"
                            @alert="showAlert"
                            @loading="showLoading"
                        />
                    </VWindowItem>
                    <VWindowItem :value="2">
                        <CustomerTabBilling 
                            :customer-data="supplier"
                            :is-supplier="true"
                            @alert="showAlert"
                            @loading="showLoading"
                        />
                    </VWindowItem>
                    <VWindowItem :value="3">
                        <CustomerTabCompany
                            :customer-data="supplier"
                            :is-supplier="true"
                        />
                    </VWindowItem>
                </VWindow>
            </VCardText>
        </VCard>
  </section>
</template>

<style lang="scss" scoped>
    
    .v-tabs.suppliers-tabs {
        :deep(.v-btn) {
            background-color: #FFFFFF !important;
            min-width: 50px !important;
            .v-btn__content {
                font-size: 14px !important;
                color: #454545;
            }
        }
        :deep(.v-slide-group__prev),
        :deep(.v-slide-group__next) {
            display: none !important;
        }
    }

    @media (max-width: 776px) {
        .v-tabs.suppliers-tabs {
            :deep(.v-icon) {
                display: none !important;
            }
            :deep(.v-btn) {
                 background-color: #FFFFFF !important;
                .v-btn__content {
                    white-space: break-spaces;
                }
            }
        }
    }
</style>

<route lang="yaml">
    meta:
      action: view
      subject: suppliers
</route>