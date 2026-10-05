<script setup>

import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import billings from "@/pages/dashboard/settings/plan/billings.vue";
import { useSuppliersStores } from '@/stores/useSuppliers'

const suppliersStores = useSuppliersStores()

const { width: windowWidth } = useWindowSize()
const sectionEl = ref(null)

const supplier_id = ref(0)
const supplierData = ref(null)
const plans = ref(null)
const userTab = ref(0)

const userData = ref(null)
const role = ref('')

const isConfirmCancelDialogVisible = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const selectedCancellationReason = ref(null)
const cancellationFeedback = ref('')

const cancellationReasons = [
    'För dyrt',
    'Använder det inte tillräckligt',
    'Saknar funktioner jag behöver',
    'Tekniska problem',
    'Byter till en annan lösning',
    'Annat',
]
const defaultCancellationReason = cancellationReasons[0]
selectedCancellationReason.value = defaultCancellationReason

const isRequestOngoing = ref(false);
const advisor = ref({
  type: '',
  message: '',
  show: false,
})

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
}

const showLoading = function (value) {
  isRequestOngoing.value = value;
};

const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')

const tabs = [
    { icon: 'custom-card-back', title: 'Prenumeration' },
    { icon: 'custom-facture', title: 'Betalningshistorik' }
]

const TAB_INDEX_BY_KEY = {
    subscription: 0,
    payment_history: 1,
}

const BILLINGS_TAB_HASH = '#tab-billings'

const resolveTabFromQuery = tab => {
    if (typeof tab === 'string') {
        const normalized = tab.trim().toLowerCase()

        if (Object.prototype.hasOwnProperty.call(TAB_INDEX_BY_KEY, normalized))
            return TAB_INDEX_BY_KEY[normalized]

        const asNumber = Number(normalized)
        if (Number.isInteger(asNumber) && asNumber >= 0 && asNumber < tabs.length)
            return asNumber
    }

    return 0
}

function resizeSectionToRemainingViewport() {
    const el = sectionEl.value;
    if (!el) return;

    const rect = el.getBoundingClientRect();
    const remaining = Math.max(0, window.innerHeight - rect.top - 25);
    el.style.minHeight = `${remaining}px`;
}

async function loadUserData() {
    isRequestOngoing.value = true

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value?.roles?.[0]?.name ?? ''
    supplier_id.value = userData.value.supplier.id;

    supplierData.value = await suppliersStores.showSupplier(supplier_id.value);

    plans.value = suppliersStores.getPlans;

    tab => {
        userTab.value = resolveTabFromQuery(tab)
    }

    isRequestOngoing.value = false 
}

function syncTabWithHash() {
    if (window.location.hash === BILLINGS_TAB_HASH) {
        userTab.value = 1
    }
}

onMounted(() => {
  loadUserData();
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
    window.addEventListener("hashchange", syncTabWithHash);

    syncTabWithHash();
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
    window.removeEventListener("hashchange", syncTabWithHash);
});

const cancelSubscription = async () => {
    const cancellationReason = selectedCancellationReason.value
    const cancellationFeedbackText = cancellationFeedback.value.trim()

    isConfirmCancelDialogVisible.value = false
    selectedCancellationReason.value = defaultCancellationReason
    cancellationFeedback.value = ''
    isRequestOngoing.value = true

    let res = await suppliersStores.cancelSubscription(supplier_id.value, {
        cancellation_reason: cancellationReason,
        cancellation_feedback: cancellationFeedbackText,
    })

    isRequestOngoing.value = false
    advisor.value = {
        type: res.data.success ? 'success' : 'error',
        message: res.data.success ? (res.data.message ?? 'Prenumeration avbruten!') : res.data.message,
        show: true
    }

    await loadUserData()

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)

    return true
}

const closeCancelDialog = () => {
    isConfirmCancelDialogVisible.value = false
    selectedCancellationReason.value = defaultCancellationReason
    cancellationFeedback.value = ''
}

const activeSubscription = async () => {
    isConfirmActiveDialogVisible.value = false
    isRequestOngoing.value = true

    let res = await suppliersStores.activeSubscription(supplier_id.value)

    isRequestOngoing.value = false
    advisor.value = {
        type: res.data.success ? 'success' : 'error',
        message: res.data.success ? (res.data.message ?? 'Prenumeration återaktiverad!') : res.data.message,
        show: true
    }

    await loadUserData()

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
    <section class="page-section bg-white" ref="sectionEl">
        <LoadingOverlay :is-loading="isRequestOngoing" />
        <VSnackbar
            v-model="advisor.show"
            transition="scroll-y-reverse-transition"
            :location="snackbarLocation"
            :color="advisor.type"
            class="snackbar-alert snackbar-dashboard"
        >
            {{ advisor.message }}
        </VSnackbar>

        <VCard class="card-fill">
            <VCardText class="pb-0" v-if="windowWidth < 1024">
            <div class="d-flex flex-column gap-4 flex-1">
                <VBtn
                class="btn-light"
                style="width: 120px;"
                :to="{ name: 'dashboard-settings' }"
                >
                <VIcon icon="custom-return" size="24" />
                Tillbaka
                </VBtn>

                <span class="title-settings pb-4 border-bottom-settings">
                Plan
                </span>
            </div>
            </VCardText>
            <VCardText class="pb-0">
                <div class="settings-layout">
                    <div class="settings-layout__sidebar">
                        <div class="d-flex flex-column gap-4">
                            <span class="subtitle-settings">Plan</span>
                            <span class="text-settings">
                                Hantera ditt abonnemang och betalningsuppgifter.
                            </span>
                        </div>
                    </div>

                    <div class="settings-layout__content"></div>
                </div>
            </VCardText>
            <VCardText class="pt-0 pt-md-4 pb-4 card-form d-flex flex-column gap-8">
                <VTabs 
                    v-model="userTab"
                    grow
                    :show-arrows="false"
                    class="suppliers-tabs">
                    <VTab
                        v-for="(tab, index) in tabs"
                        :key="index">
                        <VIcon
                            :size="24"
                            :icon="tab.icon"
                            />
                        <span>{{ tab.title }}</span>
                    </VTab>
                </VTabs>

                <VWindow v-model="userTab">
                    <VWindowItem :value="0">
                        <VCard 
                            v-if="supplierData"
                            class="card-overview__main"
                            :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 70%;'"
                        >
                            <VCardTitle class="p-0 card-subtitle d-flex flex-row justify-between">
                                Nuvarande plan
                                <div
                                    class="status-chip-mobile"
                                    :class="`status-chip-${supplierData.state_id === 2 ? 'success' : 'error'}`"
                                >
                                    {{ supplierData.state.name }} 
                                </div>
                            </VCardTitle>
                            <VCardText class="card-title p-0 mb-4">
                                {{ supplierData.plan.name }} 
                            </VCardText>
                            <VCardText 
                                class="d-flex gap-2 align-start p-0"
                                :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                            >
                                <div class="d-flex flex-column card-subtitle me-4">
                                    <div class="p-0 card-subtitle">
                                        Pris
                                    </div>
                                    <div class="p-0 card-content">
                                        {{ supplierData.is_yearly ? supplierData.plan.price_annual : supplierData.plan.price_month }} kr / 
                                        {{ supplierData.is_yearly ? 'år' : 'mån' }}
                                    </div>
                                </div>

                                <div class="d-none flex-column card-subtitle">
                                    <div class="p-0 card-subtitle">
                                        Förnyas
                                    </div>
                                    <div class="p-0 card-content">
                                        14 augusti 2026
                                    </div>
                                </div>
                            </VCardText>

                            <VDivider class="my-4" />

                            <VCardText 
                                class="d-flex justify-start gap-3 flex-wrap dialog-actions p-0"
                                :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'"
                                :class="windowWidth < 1024 ? 'flex-column px-0' : 'flex-row pe-0'"
                            >
                                <VBtn 
                                    class="btn-gradient" 
                                    :to="{ name: 'dashboard-settings-plan-upgrade-id', params: { id: supplierData.id } }"
                                > 
                                    {{ supplierData.plan_id === 1 ? 'Uppgradera plan' : 'Byta plan' }}
                                </VBtn>
                                <VBtn 
                                    v-if="role !== 'User' && supplierData.cancellation_date === null"
                                    class="btn-light" 
                                    @click="isConfirmCancelDialogVisible = true"
                                >
                                    <VIcon icon="custom-unavailable" size="24" />
                                    Avsluta abonnemang
                                </VBtn>

                                <VBtn 
                                    v-if="role !== 'User' && supplierData.cancellation_date !== null"
                                    class="btn-light" 
                                    @click="isConfirmActiveDialogVisible = true"
                                >
                                    <VIcon icon="custom-check-mark" size="24" />
                                    Återaktivera abonnemang
                                </VBtn>
                                
                            </VCardText>
                        </VCard>
                    </VWindowItem>

                    <VWindowItem :value="1">
                        <billings
                            :customer-data="supplierData"
                            :is-supplier="true"
                            @alert="showAlert"
                            @loading="showLoading"
                        />
                    </VWindowItem>

                </VWindow>
            </VCardText>
        </VCard>

        <!-- 👉 Confirm cancel subscription -->
        <VDialog
            v-model="isConfirmCancelDialogVisible"
            :fullscreen="windowWidth < 1024"
            persistent
            :scrim="windowWidth < 1024 ? false : true"
            :scrollable="windowWidth >= 1024"
            :class="windowWidth >= 1024 ? 'action-dialog' : 'action-dialog dialog-fullscreen'"
            :transition="windowWidth < 1024 ? 'dialog-bottom-transition' : undefined"
            :content-class="windowWidth < 1024 ? 'dialog-bottom-full-width' : undefined"
            width="560"
        >
            <!-- Dialog close btn -->
            <VBtn
                icon
                class="btn-ghost close-btn me-2"
                @click="closeCancelDialog"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>
                
            <!-- Dialog Content -->
            <VCard :class="windowWidth < 1024 ? 'h-100 d-flex flex-column' : ''">
                <VCardText 
                    class="dialog-title-box flex-row" 
                    :class="windowWidth < 1024 ? 'pb-0' : ''"
                    :style="windowWidth < 1024 ? '' : 'overflow-y: hidden;'"
                >
                    <div class="dialog-title">
                        Innan du säger upp — hjälp oss förstå varför
                    </div>
                </VCardText>

                <VCardText 
                    class="dialog-text d-flex flex-column gap-4 card-form"
                    :style="windowWidth < 1024 ? 'overflow-y: auto; overflow-x: hidden;' : 'overflow-y: auto;'"
                >

                    <span class="dialog-text">
                        Din feedback hjälper oss att förbättra Bilflogg. Detta steg är valfritt.
                    </span>
                    
                    <div class="cancel-feedback-dialog__label">
                        Vad är den huvudsakliga anledningen?
                    </div>

                    <VRadioGroup
                        v-model="selectedCancellationReason"
                        hide-details
                        false-icon="custom-plan-checkbox-false"
                        true-icon="custom-plan-checkbox-true"
                        class="cancel-feedback-reason-group"
                    >
                        <VRadio
                            v-for="reason in cancellationReasons"
                            :key="reason"
                            :value="reason"
                            class="cancel-feedback-reason-option"
                        >
                            <template #label>
                                <span class="cancel-feedback-reason-option__label">
                                    {{ reason }}
                                </span>
                            </template>
                        </VRadio>
                    </VRadioGroup>

                    <div class="cancel-feedback-dialog__label">
                        Berätta gärna mer (valfritt)
                    </div>

                    <VTextarea
                        v-model="cancellationFeedback"
                        placeholder="Skriv här..."
                        rows="2"
                        auto-grow
                    />
                    
                    <span class="dialog-text">
                        Enligt avtalet gäller 3 månaders uppsägningstid. Din prenumeration och tillgång till tjänsten förblir därför aktiv under uppsägningstiden och avslutas därefter automatiskt.
                    </span>

                    <VCardText class="d-flex gap-3 dialog-actions pt-0 px-0">
                        <VBtn class="btn-light" block @click="closeCancelDialog">
                            Avbryt
                        </VBtn>
                        <VBtn
                            class="btn-gradient" block
                            :disabled="!selectedCancellationReason"
                            @click="cancelSubscription"
                        >
                            Säg upp abonnemang
                        </VBtn>
                    </VCardText>
                </VCardText>
            </VCard>
        </VDialog>

        <!-- 👉 Confirm active subscription -->
        <VDialog
            v-model="isConfirmActiveDialogVisible"
            persistent
            class="action-dialog" >
            <!-- Dialog close btn -->

            <VBtn
                icon
                class="btn-white close-btn"
                @click="isConfirmActiveDialogVisible = false"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>
                
            <!-- Dialog Content -->
            <VCard>
                <VCardText class="dialog-title-box">
                    <VIcon size="32" icon="custom-check-mark-outlined" class="action-icon" />
                    <div class="dialog-title">
                        Återaktivera ditt konto?
                    </div>
                </VCardText>

                <VCardText class="dialog-text">
                    Vill du återaktivera ditt konto hos Bilflogg?
                </VCardText>

                <VCardText class="dialog-text mt-2">
                    När du skickar din förfrågan meddelas vi automatiskt via e-post. Vi kommer därefter att kontakta dig för att hjälpa dig att återaktivera ditt konto och abonnemang.
                </VCardText>               

                <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="isConfirmActiveDialogVisible = false">
                        Avbryt
                    </VBtn>
                    <VBtn class="btn-gradient" @click="activeSubscription"> Skicka förfrågan </VBtn>
                </VCardText>
            </VCard>
        </VDialog>
    </section>
</template>

<style lang="scss">

    .scrollable-dialog-content {
        max-height: 90vh !important;
        overflow-y: auto !important;
    }

    .card-form {
        .v-input {
            .v-input__control {
            .v-field {
                background-color: #f6f6f6 !important;
                min-height: 48px !important;

                .v-text-field__suffix {
                padding: 12px 16px !important;
                }

                .v-field__input {
                min-height: 48px !important;
                padding: 12px 16px !important;

                input {
                    min-height: 48px !important;
                }
                }

                .v-field-label {
                @media (max-width: 991px) {
                    top: 12px !important;
                }
                }

                .v-field__append-inner {
                align-items: center;
                padding-top: 0;
                }

                .v-text-field__prefix {
                height: 48px;
                color: #33303CAD;
                }
            }
            }
        }

        .v-input.always-show-prefix {
            .v-input__control {
            .v-field {
                .v-field__input {
                padding: 12px 0 !important;
                }
            }
            }
        }

        .v-select .v-field,
        .v-autocomplete .v-field {
            .v-select__selection,
            .v-autocomplete__selection {
            align-items: center;
            }

            .v-field__input > input {
            top: 0;
            left: 0;
            }
        }
    }

    .card-overview__main {
        border-radius: 8px !important;
        padding: 16px;
        display: flex;
        flex-direction: column;
        border: 1px solid #D4E6DF;
        //background: #F6FDFB;
        //height: 170px;
        //@media (max-width: 1023px) { 
        //    height: 157px;
        //}
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1C2925;
    }

    .card-subtitle {
        font-size: 11px;
        font-weight: 400;
        color: #878787;
    }

    .card-content {
        font-size: 14px;
        font-weight: 400;
        color: #454545;
    }

    .v-tabs.suppliers-tabs {
        .v-btn {
        min-width: 50px !important;
        .v-btn__content {
            font-size: 14px !important;
            color: #454545;
        }
        }
    }

    @media (max-width: 776px) {
      .v-tabs.suppliers-tabs {
          .v-icon {
              display: none !important;
          }
          .v-btn {
              .v-btn__content {
                  white-space: break-spaces;
              }
          }
      }
    }

    .cancel-feedback-dialog__body {
        padding: 28px 40px 0;
    }

    .cancel-feedback-dialog__label {
        font-weight: 700;
        font-size: 12.5px;
        line-height: 100%;
        letter-spacing: 0;
        color: #1C2925;
    }

    .cancel-feedback-reason-group .v-selection-control-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .cancel-feedback-reason-group .v-selection-control {
        width: 100%;
        margin: 0 !important;
        border: 1px solid #E5EAE8;
        border-radius: 10px;
        padding: 10px 14px;
        transition: border-color .2s ease, background-color .2s ease;
    }

    .cancel-feedback-reason-group .v-radio .v-selection-control__input .iconify--custom {
        block-size: 18px !important;
        font-size: 18px !important;
        inline-size: 18px !important;
    }

    .cancel-feedback-reason-group .v-selection-control--dirty {
        border-color: #57F287;
        background-color: #F3FCF7;
    }

    .cancel-feedback-reason-option__label {
        font-weight: 600;
        font-size: 13.5px;
        line-height: 100%;
        letter-spacing: 0;
        color: #1C2925;
        margin-left: 8px;
    }
</style>

<route lang="yaml">
  meta:
    navActiveLink: dashboard-settings
    action: view
    subject: dashboard
</route>