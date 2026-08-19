<script setup>

import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import Suppliers from '@/api/suppliers'
import billings from "@/pages/dashboard/settings/plan/billings.vue";

const { width: windowWidth } = useWindowSize()
const sectionEl = ref(null)
const supplier_id = ref(0)
const supplierData = ref(null)
const plans = ref(null)
const userTab = ref(0)
const userData_ = ref(null)

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

    userData_.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    supplier_id.value = userData_.value.supplier.id;

    var responses = await Suppliers.show(supplier_id.value);
    supplierData.value = responses?.data?.data?.supplier;
    plans.value = responses?.data?.data?.plans;

    tab => {
        userTab.value = resolveTabFromQuery(tab)
    }

    isRequestOngoing.value = false 
}

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
        <VCardText>
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

        <VCardText class="pt-0 pb-6 card-form d-flex flex-column gap-8">
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

                            <div class="d-flex flex-column card-subtitle">
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
                                Uppgradera plan 
                            </VBtn>
                            <VBtn 
                                class="btn-light" 
                            >
                                <VIcon icon="custom-unavailable" size="24" />
                                Avsluta abonnemang
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
    </section>
</template>

<style lang="scss">
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
</style>

<route lang="yaml">
  meta:
    navActiveLink: dashboard-settings
    action: view
    subject: dashboard
</route>