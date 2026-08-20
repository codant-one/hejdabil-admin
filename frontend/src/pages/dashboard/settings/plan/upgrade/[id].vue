<script setup>

import { formatNumberInteger } from "@/@core/utils/formatters";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import Suppliers from '@/api/suppliers'

const route = useRoute()
const { width: windowWidth } = useWindowSize()

const sectionEl = ref(null)
const supplier_id = ref(0)
const supplierData = ref(null)
const plans = ref(null)
const is_yearly = ref(0)
const selectedPlan = ref(null)
const isPlansDetailsVisible = ref(false)
const isConfirmUpgradePlanDialogVisible = ref(false);
const refForm = ref(null)

const isRequestOngoing = ref(false);
const advisor = ref({
  type: '',
  message: '',
  show: false,
})

const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')

function resizeSectionToRemainingViewport() {
    const el = sectionEl.value;
    if (!el) return;

    const rect = el.getBoundingClientRect();
    const remaining = Math.max(0, window.innerHeight - rect.top - 25);
    el.style.minHeight = `${remaining}px`;
}

async function loadUserData() {
    isRequestOngoing.value = true

    //Supplier ID de prueba. Cambiar por el correcto
    
    if(Number(route.params.id) && route.name === 'dashboard-settings-plan-upgrade-id') {
        supplier_id.value = Number(route.params.id);
        var responses = await Suppliers.show(supplier_id.value);
        supplierData.value = responses?.data?.data?.supplier;
        plans.value = responses?.data?.data?.plans;
        selectedPlan.value = Number(supplierData.value.plan_id ?? plans.value?.[0]?.id ?? 0)
        is_yearly.value = Number(supplierData.value.is_yearly ?? 0)
    }

    isRequestOngoing.value = false 
}

const planCycleLabel = computed(() => Number(is_yearly.value) === 1 ? 'kr / år' : 'kr / mån')

const availablePlans = computed(() => {
    const list = Array.isArray(plans.value) ? plans.value : []

    return list.map(plan => {
        const planId = Number(plan?.id)
        const monthlyPrice = Number(plan?.price_month ?? 0)
        const yearlyPrice = Number(plan?.price_annual ?? 0)

        return {
            id: planId,
            name: plan?.name ?? '',
            price: Number(is_yearly.value) === 1 ? yearlyPrice : monthlyPrice,
            icon: planId === 1 ? 'custom-swish-gray' : 'custom-fairytale',
        }
    })
})

const showConfirmUpgradePlanDialog = (plan) => {
    const normalizedPlan = Number(plan)

    if (Number.isFinite(normalizedPlan) && normalizedPlan > 0)
        selectedPlan.value = normalizedPlan

    isConfirmUpgradePlanDialogVisible.value = true;
    isPlansDetailsVisible.value = false;
    //   selectedPlan.value = { ...plan };
};

const upgradePlan = async (supplier, plan) => {

    // Implement the upgrade plan logic here
    let formData = new FormData()
    formData.append('_method', 'POST')
    formData.append('supplier_id', supplier.id)
    formData.append('plan_id', plan)
    formData.append('is_yearly', is_yearly.value)

    let data = { data: formData }

    isRequestOngoing.value = true
    isConfirmUpgradePlanDialogVisible.value = false

    Suppliers.requestPlanUpgrade(data)
        .then((response) => {
            advisor.value.show = true
            advisor.value.type = response.data.type
            advisor.value.message = response.data.message

            isRequestOngoing.value = false
        })
        .catch((err) => {
            isRequestOngoing.value = false

            advisor.value.show = true
            advisor.value.type = err.type
            advisor.value.message = err.message
        });
    
};

onMounted(() => {
  loadUserData();
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});

</script>

<template>
    <section class="page-section bg-white suppliers-page" ref="sectionEl">
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
            <div class="settings-layout pb-0 pb-md-4">
                <div class="settings-layout__sidebar">
                    <div class="d-flex flex-column gap-4">
                        <span class="subtitle-settings">
                            {{ windowWidth < 1024 ? 'Uppgradera plan' : 'Plan' }}
                        </span>
                        <span class="text-settings">
                            Välj den plan som passar dig bäst.
                        </span>
                    </div>
                </div>

                <div class="settings-layout__content"></div>
            </div>
        </VCardText>

        <VCardText class="pt-0 pt-md-4 pb-4 card-form d-flex flex-column gap-8">
            <VCard 
                v-if="supplierData"
                class="card-overview__main"
                :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 70%;'"
            >
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'">
                    <VLabel class="mb-4 text-body-2 text-high-emphasis" text="Välj plan" />
                    <div class="d-flex flex-row align-center mb-4" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'">
                        <VLabel class="title-comments me-2" text="Månadsvis" />
                        <VSwitch
                            v-model="is_yearly"
                            class="plan-time"
                            :false-value="0"
                            :true-value="1"
                            hide-details
                            inset
                        />
                        <VLabel class="title-comments ms-2" text="Årlig" />
                    </div>
                    <VRadioGroup 
                        v-model="selectedPlan"
                        hide-details
                        false-icon="custom-settings-checkbox-false"
                        true-icon="custom-settings-checkbox-true"
                        class="delivery-method-group"
                    >
                        <div class="d-flex flex-wrap gap-4">
                            <VCard
                                v-for="(plan, index) in availablePlans"
                                :key="plan.id"
                                flat
                                :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 11px);'"
                                class="readonly-form d-flex flex-column"
                                :class="selectedPlan === plan.id ? 'border-card-comment-selected' : 'border-card-comment'"
                            >
                                <div id="cardContent" 
                                    class="gap-2" 
                                    :class="[
                                        selectedPlan === plan.id ? 'card-bg-selected' : '',
                                        windowWidth < 1024 ? 'px-4 py-4' : 'px-8 py-6'
                                    ]"
                                    style="width: 100%;"
                                >
                                    <VCardText 
                                        class="d-flex align-center px-0 gap-2" 
                                        style="min-height: 48px; max-height: 48px;"
                                        > 
                                        <VIcon 
                                            :icon="plan.icon"
                                            size="40" 
                                        />
                                        <span class="title-card">
                                            {{ plan.name }}
                                        </span>
                                        <VSpacer />
                                        
                                        <VRadio
                                            class="mt-4 me-0 cursor-pointer delivery-method-option flex-0"
                                            :value="plan.id"
                                        />
                                    </VCardText>

                                    <div class="d-block gap-4 px-0 mt-auto">
                                        <div class="price-text mt-4">
                                            {{ formatNumberInteger(plan.price) }}
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" :text="planCycleLabel" />
                                        </div>
        
                                        <div class="d-flex gap-4 my-4">
                                            <span class="small-text">
                                                exkl. moms
                                            </span>
                                        </div>

                                        <VDivider class="border-card-line mb-3" />

                                        <div 
                                            class="gap-4 mt-4 align-center d-flex flex-row justify-content-center cursor-pointer" 
                                            @click="isPlansDetailsVisible = true"
                                        >
                                            <span class="details-text">
                                                Se vad som ingår
                                            </span>
                                            <VIcon 
                                                icon="custom-arrow-right" 
                                                size="24" 
                                                class="cursor-pointer"
                                                style="flex-shrink: 0;"                                                
                                            />
                                        </div>
                                    </div>
                                </div>    
                            </VCard>
                        </div>
                    </VRadioGroup>
                </div>

                <VCardText 
                    class="d-flex justify-end gap-3 flex-wrap dialog-actions p-0 mt-6"
                    :style="windowWidth < 1024 ? 'width: 100%; flex-direction: column-reverse !important;' : 'width: 100%;'"
                    :class="windowWidth < 1024 ? 'flex-column px-0' : 'flex-row pe-0'"
                >
                    <VBtn 
                        class="btn-light" 
                        :to="{ name: 'dashboard-settings-plan' }"
                    >
                        Tillbaka
                    </VBtn>

                    <VBtn 
                        class="btn-gradient" 
                        @click="showConfirmUpgradePlanDialog(selectedPlan)"
                    > 
                        Bekräfta byte 
                    </VBtn>
                    
                </VCardText>
            </VCard>
        </VCardText>
    </VCard>

    <!-- 👉 Plans Details Dialog -->
    <VDialog 
        v-model="isPlansDetailsVisible"
        :fullscreen="windowWidth < 1024"
        persistent
        :scrim="windowWidth < 1024 ? false : true"
        :scrollable="false"
        :class="windowWidth >= 1024 ? 'action-dialog' : 'action-dialog dialog-fullscreen'"
        :transition="windowWidth < 1024 ? 'dialog-bottom-transition' : undefined"
        :content-class="windowWidth < 1024 ? 'dialog-bottom-full-width' : undefined"
        :width="windowWidth < 1024 ? '' : '800px'"
    >
        <!-- Dialog close btn -->
        <VBtn
            icon
            class="btn-white close-btn"
            @click="isPlansDetailsVisible = false"
            >
            <VIcon size="16" icon="custom-close" />
        </VBtn>

        <VCard class="plans-details-dialog-card" :class="windowWidth < 1024 ? 'h-100 d-flex flex-column' : ''">
            <VCardText class="dialog-title-box pb-0">
                <div class="dialog-title">
                    Jämför planer
                </div>                
            </VCardText>
            <VCardText class="dialog-text pe-0">
                Se vad som ingår i varje plan innan du bekräftar bytet.
            </VCardText>

            <VDivider class="mt-4" />
            
            <VCardText 
                class="dialog-text mt-4 pb-6 plans-details-dialog-content">        
                <div class="d-flex flex-row" style="width: 100%;">
                    <div class="text-start" style="width: 50%;">
                        
                    </div>
                    <div class="d-flex flex-column justify-content-center align-center" style="width: 25%;">
                        <div>
                            <VIcon 
                                :icon="availablePlans[0].icon"
                                :size="windowWidth < 1024 ? 18 : 24" 
                            />
                            <span class="plan-details-content ms-2" style="overflow: hidden; white-space: nowrap; flex: 1;">
                                {{ availablePlans[0].name }}
                            </span>
                        </div>
                        <span class="plan-details-current-indicator py-1 px-2 mt-2" v-if="supplierData.plan_id === 1"> 
                            <span v-if="windowWidth >= 1024">Din nuvarande plan</span>
                            <span v-if="windowWidth < 1024">Nuvarande</span>                            
                        </span>
                        <span style="height: 19px;" class=" px-2 mt-2" v-else>                            
                            
                        </span>
                    </div>
                    <div class="d-flex flex-column justify-content-center align-center" style="width: 25%;">
                        <div>
                            <VIcon 
                                :icon="availablePlans[1].icon"
                                :size="windowWidth < 1024 ? 18 : 24" 
                            />
                            <span class="plan-details-content ms-1" style="overflow: hidden; white-space: nowrap; flex: 1;">
                                {{ availablePlans[1].name }}
                            </span>
                        </div>
                        <span class="plan-details-current-indicator py-1 px-2 mt-2" v-if="supplierData.plan_id === 2"> 
                            <span v-if="windowWidth >= 1024">Din nuvarande plan</span>
                            <span v-if="windowWidth < 1024">Nuvarande</span>                            
                        </span>
                        <span class="plan-details-most-popular-indicator py-1 px-2 mt-2" v-else>                            
                            <span v-if="windowWidth >= 1024">Mest populär</span>
                            <span v-if="windowWidth < 1024">Populär</span>
                        </span>
                    </div>
                </div>

                <div class="my-4" style="width: 100%;">
                    <span class="plan-details-title ">Betalningar</span>
                </div>

                <div class="d-flex flex-row plan-details-box pb-2 mt-2" style="width: 100%;">
                    <div class="text" style="width: 50%;">
                        <span class="plan-details-content">Swish-utbetalningar</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">Obegränsat</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">Obegränsat</span>
                    </div>
                </div>

                <div class="my-4" style="width: 100%;">    
                    <span class="plan-details-title ">Användare & fordon</span>
                </div>

                <div class="d-flex flex-row plan-details-box pb-2 mb-4" style="width: 100%;">
                    <div class="text-start" style="width: 50%;">
                        <span class="plan-details-content">Användare</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">Upp till 3</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">Flera</span>
                    </div>
                </div>

                <div class="d-flex flex-row plan-details-box pb-2 mb-4" style="width: 100%;">
                    <div class="d-flex flex-column align-start" style="width: 50%;">
                        <span class="plan-details-content">Fordon i lager</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">-</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">Obegränsat</span>
                    </div>
                </div>

                <div class="my-4" style="width: 100%;">    
                    <span class="plan-details-title ">Fakturering & avtal</span>
                </div>

                <div class="d-flex flex-row plan-details-box pb-2 mb-4" style="width: 100%;">
                    <div class="d-flex flex-column align-start" style="width: 50%;">
                        <span class="plan-details-content">Fakturering</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">-</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">Obegränsad</span>
                    </div>
                </div>

                <div class="d-flex flex-row plan-details-box pb-2 mb-4" style="width: 100%;">
                    <div class="d-flex flex-column align-start" style="width: 50%;">
                        <span class="plan-details-content">Digitala avtal med e-signering</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">-</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">
                            <VIcon 
                                icon="custom-check-mark"
                                size="24" 
                            />
                        </span>
                    </div>
                </div>

                <div class="d-flex flex-row plan-details-box pb-2 mb-4" style="width: 100%;">
                    <div class="d-flex flex-column align-start" style="width: 50%;">
                        <span class="plan-details-content">E-signering för eget dokument</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">-</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">
                            <VIcon 
                                icon="custom-check-mark"
                                size="24" 
                            />
                        </span>
                    </div>
                </div>

                <div class="my-4" style="width: 100%;">    
                    <span class="plan-details-title ">Kunder & insikter</span>
                </div>
                <div class="d-flex flex-row plan-details-box pb-2 mb-4" style="width: 100%;">
                    <div class="d-flex flex-column align-start" style="width: 50%;">
                        <span class="plan-details-content">Kundhantering</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">-</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">
                            <VIcon 
                                icon="custom-check-mark"
                                size="24" 
                            />
                        </span>
                    </div>
                </div>

                <div class="d-flex flex-row plan-details-box pb-2 mb-4" style="width: 100%;">
                    <div class="d-flex flex-column align-start" style="width: 50%;">
                        <span class="plan-details-content">Insikter och analys</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">-</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">
                            <VIcon 
                                icon="custom-check-mark"
                                size="24" 
                            />
                        </span>
                    </div>
                </div>

                <div class="my-4" style="width: 100%;">    
                    <span class="plan-details-title ">Säkerhet & historik</span>
                </div>

                <div class="d-flex flex-row plan-details-box pb-2 mb-4" style="width: 100%;">
                    <div class="d-flex flex-column align-start" style="width: 50%;">
                        <span class="plan-details-content">Transaktionshistorik</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">-</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">
                            <VIcon 
                                icon="custom-check-mark"
                                size="24" 
                            />
                        </span>
                    </div>
                </div>

                <div class="d-flex flex-row plan-details-box pb-2 mb-4" style="width: 100%;">
                    <div class="d-flex flex-column align-start" style="width: 50%;">
                        <span class="plan-details-content">Verifiering med säkerhetskod</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">-</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">
                            <VIcon 
                                icon="custom-check-mark"
                                size="24" 
                            />
                        </span>
                    </div>
                </div>

                <div class="my-4" style="width: 100%;">
                    <span class="plan-details-title ">Support</span>
                </div>
                <div class="d-flex flex-row plan-details-box pb-2 mt-2" style="width: 100%;">
                    <div class="d-flex flex-column align-start" style="width: 50%;">
                        <span class="plan-details-content">Support</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">E-post</span>
                    </div>
                    <div class="text-center" style="width: 25%;">
                        <span class="plan-details-content">Prioriterad</span>
                    </div>
                </div>
                <VCardText 
                    class="d-flex justify-end gap-3 flex-wrap dialog-actions p-0 mt-6"
                    :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'"
                    :class="windowWidth < 1024 ? 'flex-column px-0' : 'flex-row pe-0'"
                >
                    <span class="py-3 text-center d-none">
                        Din nuvarande plan är Swish
                    </span>

                    <VBtn 
                        class="btn-gradient" 
                        :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 180px;'"
                        @click="showConfirmUpgradePlanDialog(supplierData)"
                    > 
                        Uppgradera till Pro 
                    </VBtn>
                    
                </VCardText>
            </VCardText>
        </VCard>
    </VDialog>

    <!-- 👉 Confirm Upgrade Plan -->
    <VDialog
      v-model="isConfirmUpgradePlanDialogVisible"
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmUpgradePlanDialogVisible = !isConfirmUpgradePlanDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

        <!-- Dialog Content -->
       <VForm
            ref="refForm"
            class="card-form"
            validate-on="submit"
            @submit.prevent="upgradePlan(supplierData, selectedPlan)"
        >
        <VCard>
            <VCardText class="dialog-title-box">
                <VIcon size="32" icon="custom-card" class="action-icon" />
                <div class="dialog-title">
                    Byta abonnemang
                </div>
            </VCardText>
            <VCardText class="dialog-text">
                Genom att fortsätta skickas en förfrågan om abonnemangsbyte till Bilflogg.
            </VCardText>
            <VCardText class="dialog-text mt-4">
                Vi kontaktar dig inom kort för att hjälpa dig med ändringen.

                <div class="upgrade-contact-row mt-4">
                    <span class="upgrade-contact-row__label">Telefon</span>
                    <span class="upgrade-contact-row__value">072-277 22 97</span>
                </div>

                <div class="upgrade-contact-row mt-2">
                    <span class="upgrade-contact-row__label">E-post</span>
                    <span class="upgrade-contact-row__value">info@bilflogg.se</span>
                </div>
                
            </VCardText>

            <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn class="btn-light" @click="isConfirmUpgradePlanDialogVisible = false">
                Avbryt
            </VBtn>
            <VBtn class="btn-gradient" type="submit"> Skicka förfrågan </VBtn>
            </VCardText>
        </VCard>
      </VForm>
    </VDialog>

    </section>
</template>

<style lang="scss">

    .suppliers-page .radio-form.v-radio-group .v-selection-control-group .v-radio:not(:last-child) {
        margin-inline-end: 1.5rem !important;
    }
    
    .scrollable-dialog-content {
        max-height: 90vh !important;
        overflow-y: auto !important;
    }

    .plans-details-dialog-card {
        @media (min-width: 1024px) {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
    }

    .plans-details-dialog-content {
        overflow-y: auto;
        overflow-x: hidden;
        flex: 1 1 auto;
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

    .always-show-prefix .v-text-field__prefix {
        opacity: 1 !important;
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


    //Plans Card Styles
    .title-comments {
        font-weight: 600;
        font-size: 16px;
        line-height: 100%;
        color: #454545 !important;
        overflow: visible !important;
    }

    .small-text {
        font-weight: 400;
        font-size: 12px;
        line-height: 100%;
        color: #454545; 
    }

    .price-text {
        font-weight: 600;
        font-size: 40px;
        line-height: 100%;
        color: #454545; 
    }

    .details-text {
        font-weight: 500;
        font-size: 16px;
        line-height: 100%;
        color: #6E9383; 
    }

    .title-card {
        font-weight: 700;
        font-size: 24px;
        line-height: 100%;
        color: #454545; 
    }

    .border-card-comment {
        border: 2px solid #E7E7E7;
        border-radius: 24px !important;
    }

    .border-card-line {
        border: 1px solid transparent;
        border-image: linear-gradient(to right, #FFFFFF 0%, #1C2925 50%, #FFFFFF 100%) 1;
    }

    .card-bg-selected {
        background-color: #F5F8F6; /* Example background color for selected card */
    }

    .border-card-comment-selected {
        /* Set your border size and make it transparent */
        border: 2px solid transparent;
        border-radius: 24px !important;
        
        /* Layer 1 (top): Solid inner background color */
        /* Layer 2 (bottom): The actual gradient */
        background-image: linear-gradient( #F5F8F6, #F5F8F6, #F5F8F6), 
                            linear-gradient(to right, #57F287, #00BEB0, #00FFFF);
        
        /* Map backgrounds to the right boxes */
        background-origin: border-box;
        background-clip: padding-box, border-box;        
    }


    .delivery-method-group .v-selection-control {
        align-items: start !important;
    }

    .delivery-method-group .v-radio.v-selection-control--dirty .v-selection-control__input .iconify--custom, .v-radio-btn.v-selection-control--dirty .v-selection-control__input .iconify--custom {
        box-shadow: none !important;
    }

    .delivery-method-group .v-radio .v-selection-control__input .iconify--custom, .v-radio-btn .v-selection-control__input .iconify--custom {
        block-size: 24px !important;
        font-size: 24px !important;
        inline-size: 24px!important;
    }

    .delivery-method-group {
        width: 100%;
    }

    .delivery-method-group .v-selection-control-group .v-radio {
        margin-inline-end: 0 !important;
    }

    .delivery-method-option {
        margin-bottom: 24px;
    }

    .delivery-method-option .v-selection-control {
        align-items: flex-start;
    }

    .delivery-method-option .v-label {
        display: block;
        flex: 1;
        min-width: 0;
        max-width: 100%;
        white-space: normal;
        overflow: visible;
        text-overflow: unset;
    }

    .delivery-method-group .v-selection-control-group .v-radio {
        margin-inline-end: 0 !important;
    }

    .plan-time .v-label {
        display: block;
        white-space: normal;
        overflow: visible;
        text-overflow: unset;
    }

    .plan-time .v-label {
      max-width: 100%;
    }

    .v-switch.v-switch--inset:not(.v-input--disabled) .v-switch__track {
        border-color: #E7E7E7;
        background-color: #E7E7E7;
    }

    .v-switch.v-switch--inset .v-selection-control__input .v-switch__thumb {
            background: #FFFFFF;
    }

    .plan-details-box {
        border-bottom: 1px solid #E7E7E7;
    }  

    .plan-details-title {
        font-weight: 600;
        font-size: 12px;
        line-height: 100%;
        color: #878787;
        text-transform: uppercase;
    }

    .plan-details-content {
        font-weight: 600;
        font-size: 16px;
        line-height: 100%;
        color: #5D5D5D; 

        @media (max-width: 1023px) {
            font-size: 12px;
        }
    }

    .plan-details-current-indicator {
        border: 0;
        border-radius: 24px !important;
        background-color: #DFF6E7;    
        font-weight: 400;
        font-size: 11px;
        line-height: 100%;
        color: #1C2925; 
    }

    .plan-details-most-popular-indicator {
        border: 0;
        border-radius: 24px !important;
        background-color: #5D5D5D;    
        font-weight: 400;
        font-size: 11px;
        line-height: 100%;
        color: #FFFFFF; 
    }

    .upgrade-contact-row {
        width: 100%;
        height: 40px;
        border: 1px solid #E7E7E7;
        border-radius: 8px;
        background-color: #F6F6F6;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .upgrade-contact-row__label {
        font-weight: 400;
        font-size: 16px;
        line-height: 24px;
        letter-spacing: 0;
        color:#878787;
    }

    .upgrade-contact-row__value {
        font-weight: 400;
        font-size: 16px;
        line-height: 24px;
        letter-spacing: 0;
        color: #454545;
    }
</style>

<route lang="yaml">
  meta:
    navActiveLink: dashboard-settings
    action: view
    subject: dashboard
</route>