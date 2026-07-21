 <script setup>

import { useSuppliersStores } from '@/stores/useSuppliers'
import { useDisplay } from "vuetify";
import { themeConfig } from "@themeConfig";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import { requiredValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import router from '@/router'
import Toaster from "@/components/common/Toaster.vue";

defineProps({
  id: [String, Number],
});


const route = useRoute()
const suppliersStores = useSuppliersStores()

const { mdAndDown } = useDisplay();
const { width: windowWidth } = useWindowSize();
const sectionEl = ref(null);
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const isRequestOngoing = ref(true)

const supplier = ref(null)
const selectedSupplier = ref({})
const isMobile = ref(false)
const initialData = ref(null)
const currentTab = ref(0)
const payout_number = ref(null)
const csr_url = ref(null)
const pem_url = ref(null)
const is_payout = ref(false)
const pemFile = ref([])
const refForm = ref(null)
const isFormValid = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

watchEffect(fetchData);

async function fetchData() {
    isRequestOngoing.value = true

    if(Number(route.params.id) && route.name === 'dashboard-admin-suppliers-swish-id') {
        supplier.value = await suppliersStores.showSupplier(Number(route.params.id))

        selectedSupplier.value = { ...supplier.value }
        payout_number.value = supplier.value.payout_number || null
        csr_url.value = supplier.value.csr_url || null
        pem_url.value = supplier.value.pem_url || null
        is_payout.value = supplier.value.is_payout === 0 ? false : true
        pemFile.value = []

        nextTick(() => {
            refForm.value?.resetValidation()
        })


    }

    isRequestOngoing.value = false
}

onMounted(async () => {
    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

const formatOrgNumber = () => {

    let numbers = payout_number.value.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    payout_number.value = numbers
}

// const pemFileRules = computed(() => {
//   if (!swishHasSteps.value || !is_payout.value)
//     return []

//   return [
//     value => {
//       const hasNewFile = Array.isArray(value) && value.length > 0
//       const hasExistingPem = !!pem_url.value
//       return hasNewFile || hasExistingPem || 'Ladda upp en PEM-fil för att aktivera Swish-utbetalningar.'
//     },
//   ]
// })

const openStorageFileUrl = filePath => {
  if (!filePath) return ''
  if (filePath.startsWith('http://') || filePath.startsWith('https://')) return filePath
  return `${themeConfig.settings.urlStorage}${filePath}`
}

const swishHasCSR = computed(() => !!csr_url.value)

const swish = () => {

  refForm.value?.validate().then(({ valid }) => {
    if(valid) {
      isRequestOngoing.value = true
      
      let formData = new FormData()
      formData.append('payout_number', payout_number.value)
      formData.append('is_payout', is_payout.value ? 1 : 0)
      if (pemFile.value && pemFile.value.length > 0) {
        formData.append('file', pemFile.value[0])
      }
      
      suppliersStores.swish(selectedSupplier.value.id, formData)
        .then(async (res) => {
            if (res.data.success) {
              selectedSupplier.value = {}
              payout_number.value = null
              is_payout.value = false
              pemFile.value = []
              
              await fetchData()

              advisor.value = {
                type: 'success',
                message: res.data.data.supplier.is_payout === 1 ? 'Swish aktiverad!' : 'Swish avaktiverad!',
                show: true
              }

              setTimeout(() => {
                advisor.value = {
                  type: '',
                  message: '',
                  show: false
                }
              }, 3000)

            }
            isRequestOngoing.value = false
        })
        .catch((err) => {

          advisor.value = {
            type: 'error',
            message: err.response?.data?.message || err.message,
            show: true
          }

          setTimeout(() => {
            advisor.value = {
              type: '',
              message: '',
              show: false
            }
          }, 3000)

          isRequestOngoing.value = false
        })
    }
  })
}

const onSubmit = async () => {
}

const currentData = computed(() => ({
    
}))

</script>

<template>
    <section class="page-section suppliers-page" ref="sectionEl">
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

        <VForm
            ref="refForm"
            class="card-form"
            v-model="isFormValid"
            @submit.prevent="swish"
            >
            <VCard
                flat 
                class="card-fill"
                :class="[
                    windowWidth < 1024 ? 'flex-column' : 'flex-row',
                    $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
                ]"
            >
                <VCardText class="p-0">
                    <div 
                        class="d-flex  gap-y-4 gap-x-6 mb-4 justify-start justify-sm-space-between"
                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-wrap'"
                    >
                
                        <VBtn
                            :class="windowWidth < 1024 ? 'd-flex' : 'd-none'" 
                            class="btn-light"
                            style="width: 120px;"
                            :to="{ name: 'dashboard-admin-suppliers' }"
                        >
                            <VIcon icon="custom-return" size="24" />
                            Tillbaka 
                        </VBtn>
                        
                        <div class="d-flex flex-column gap-4">
                            <span class="title-page">
                                Swish
                            </span>
                            <span 
                                class="subtitle-page"
                                :class="windowWidth < 1024 ? 'd-none' : 'justify-start'"
                            >
                                Betalningsuppgifter för leverantören Nordic Parts AB
                            </span>
                        </div>

                        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

                        <div 
                            :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-4 align-center'"
                        >
                            <VBtn
                                class="btn-light w-auto" 
                                block
                                :to="{ name: 'dashboard-admin-suppliers' }">
                                <VIcon icon="custom-return" size="24" />
                                Tillbaka
                            </VBtn>
                        </div>
                    </div>
                </VCardText>

                <VDivider :class="windowWidth < 1024 ? 'd-none' : ''" />

                <VCardText class="px-0">
                    <VRow class="px-md-3">
                        <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                            <div 
                                class="d-flex flex-wrap"
                                :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                            >

                                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Utbetalningsnummer*" />
                                    <VTextField
                                        v-model="payout_number"
                                        :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                                        minLength="11"
                                        maxlength="11"
                                        @input="formatOrgNumber()"
                                    />
                                </div> 
                            </div>

                            <div class="title-tabs mb-2 pt-6">
                                Aktivera Swish utbetalningar
                            </div>
                            <div 
                                class="d-flex flex-wrap"
                                :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                            >

                                <div class="d-flex flex-row" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Möjliggör automatiska utbetalningar till leverantören via Swish." />
                                    <VSwitch
                                        v-model="is_payout"
                                        class="ms-8"
                                        hide-details
                                        inset
                                    />
                                </div>  
                            </div>
                        </VCol>
                    </VRow>

                    <VRow 
                        v-if="swishHasCSR"
                        class="px-md-3"
                    >
                        <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                            <div class="title-tabs mb-2 pt-6">
                                Certifikat
                            </div>
                            <div 
                                class="d-flex flex-wrap"
                                :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                            >

                                <div class="d-flex flex-row" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Krävs för att aktivera automatiska Swish-utbetalningar." />
                                </div>  
                            </div>

                            <div 
                                class="d-flex flex-wrap mt-6"
                                :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                            >

                                <div
                                    class="d-flex flex-column" 
                                    :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'"
                                >
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="CSR-fil" />
                                    <VBtn
                                        v-if="csr_url"
                                        class="file_download"
                                        variant="tonal"
                                        :href="openStorageFileUrl(csr_url)"
                                        target="_blank"
                                    >
                                        {{ csr_url.split('/').pop() }}
                                    </VBtn>
                                    <div v-else class="text-disabled">Ingen CSR-fil tillgänglig.</div>
                                </div>  
                            </div>

                            <div 
                                class="d-flex flex-wrap mt-6"
                                :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                            >

                                <div 
                                    class="d-flex flex-column" 
                                    :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'"
                                >
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="PEM-fil" />
                                    <VBtn
                                        v-if="pem_url"
                                        class="file_download"
                                        variant="tonal"
                                        :href="openStorageFileUrl(pem_url)"
                                        target="_blank"
                                    >
                                        {{ pem_url.split('/').pop() }}
                                    </VBtn>
                                    <div v-else class="text-disabled">Ingen PEM-fil uppladdad än.</div>

                                    <VFileInput
                                        class="mt-4"
                                        v-model="pemFile"
                                        label="Ladda upp PEM-fil"
                                        accept=".pem"
                                        prepend-icon="tabler-file"
                                    />
                                </div>  
                            </div>
                        </VCol>
                    </VRow>
                </VCardText>

                <VCardText 
                    class="d-flex justify-end gap-3 flex-wrap dialog-actions pe-0"
                    :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'"
                >
                    <VBtn 
                        class="btn-light" 
                        :to="{ name: 'dashboard-admin-suppliers' }"
                    >
                        Avbryt
                    </VBtn>
                    <VBtn class="btn-gradient" type="submit"> Acceptera </VBtn>
                </VCardText>
            </VCard>
        </VForm>
    </section>
</template>

<style lang="scss">
    .title-page {
        font-weight: 700;
        font-size: 32px;
        line-height: 100%;
        color: #1C2925;

        @media (max-width: 1023px) {
            font-size: 24px
        }
    }

    .subtitle-page {
        font-weight: 400;
        font-size: 16px;
        line-height: 100%;
        color: #454545;
    }

    .title-tabs {
        font-weight: 700;
        font-size: 24px;
        line-height: 100%;
        color: #454545;

        @media (max-width: 1023px) {
            font-size: 16px
        }
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
                        top: 12px !important;
                    }

                    .v-field__append-inner {
                        align-items: center;
                        padding-top: 0px;
                    }

                    .v-text-field__prefix {
                        height: 48px;
                        color: #33303CAD;
                    }
                }
            }
        }

        .v-label {
            white-space: break-spaces !important;
        }

        .v-input.always-show-prefix {
            .v-input__control {
                .v-field {
                    .v-field__input {
                        padding: 8px 0 !important;
                    }
                }
            }
        }

        .selector-country {
            .v-input__prepend {
                margin-inline-end: 6px !important;
            }
        }

        .v-select .v-field,
        .v-autocomplete .v-field {
            .v-select__selection,
            .v-autocomplete__selection {
                align-items: center;
            }

            .v-field__input > input {
                top: 0px;
                left: 0px;
            }
        }

        .v-btn.file_download {
            color: #F6F6F6 !important;
            border-color: #E7E7E7 !important;
            border-width: 1px !important;
            border-radius: 8px !important;
            justify-content: start !important;
            
        }

        .v-btn.file_download .v-btn__content {
            font-weight: 700;
            font-size: 16px;
            line-height: 100%;
            color: #5D5D5D;
        }
    }

    
</style>

<route lang="yaml">
    meta:
      action: view
      subject: suppliers
</route>