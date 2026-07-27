 <script setup>

import { themeConfig } from "@themeConfig";
import { useSuppliersStores } from '@/stores/useSuppliers'
import { useDisplay } from "vuetify";
import { getRemoteFileSize, openStorageFileUrl, formatDateSimple, getFileNameFromPath, formatFileSize } from '@/@core/utils/formatters'
import { requiredValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

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
const payout_number = ref(null)
const csr_url = ref(null)
const csr_at = ref(null)
const csr_size = ref(null)
const pem_url = ref(null)
const pem_at = ref(null)
const pem_size = ref(null)
const is_payout = ref(false)
const pemFile = ref([])
const pemFileInputRef = ref(null)
const isPemDragOver = ref(false)
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
        csr_at.value = supplier.value.csr_at || null
        csr_size.value = null
        pem_url.value = supplier.value.pem_url || null
        pem_at.value = supplier.value.pem_at || null
        pem_size.value = null
        is_payout.value = supplier.value.is_payout === 0 ? false : true
        pemFile.value = []

        if (csr_url.value) {
            csr_size.value = await getRemoteFileSize(csr_url.value)
        }

        if (pem_url.value) {
            pem_size.value = await getRemoteFileSize(pem_url.value)
        }

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

const swishHasCSR = computed(() => !!csr_url.value)
const pemFileName = computed(() => {
    const file = Array.isArray(pemFile.value) ? pemFile.value[0] : pemFile.value
    return file?.name || ''
})
const selectedPemFile = computed(() => {
    const file = Array.isArray(pemFile.value) ? pemFile.value[0] : pemFile.value
    return file || null
})
const hasPendingPemSelection = computed(() => !!selectedPemFile.value)
const shouldShowPemCard = computed(() => !!pem_url.value || !!selectedPemFile.value)
const pemDisplayName = computed(() => {
    if (hasPendingPemSelection.value) return selectedPemFile.value?.name || ''
    if (pem_url.value) return getFileNameFromPath(pem_url.value)
    return ''
})
const pemDisplayMeta = computed(() => {
    if (hasPendingPemSelection.value) return `${formatFileSize(selectedPemFile.value.size)} · Uppladdad ${formatDateSimple(new Date())}`
    if (pem_url.value) return `${formatFileSize(pem_size.value)} · Uppladdad ${formatDateSimple(pem_at.value)}`
    return ''
})

const PEM_MAX_SIZE_BYTES = 2 * 1024 * 1024

const showAdvisorMessage = (type, message, timeout = 3500) => {
    advisor.value = {
        type,
        message,
        show: true,
    }

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false,
        }
    }, timeout)
}

const normalizePemFiles = value => {
    if (!value) return []
    if (Array.isArray(value)) return value
    return [value]
}

const clearPemFileInput = () => {
    const input = pemFileInputRef.value?.$el?.querySelector('input[type="file"]')
    if (input) input.value = ''
}

const validatePemFile = file => {
    if (!file) return false

    const hasValidExtension = file.name?.toLowerCase().endsWith('.pem')
    if (!hasValidExtension) {
        showAdvisorMessage('error', 'Endast .pem-filer ar tillatna.')
        return false
    }

    if (file.size > PEM_MAX_SIZE_BYTES) {
        showAdvisorMessage('error', 'PEM-filen far vara max 2 MB.')
        return false
    }

    return true
}

const setPemFile = file => {
    if (!validatePemFile(file)) {
        pemFile.value = []
        clearPemFileInput()
        return
    }

    pemFile.value = [file]
}

const openPemFilePicker = () => {
    pemFileInputRef.value?.$el?.querySelector('input[type="file"]')?.click()
}

const onPemInputChange = value => {
    const files = normalizePemFiles(value)
    if (!files.length) {
        pemFile.value = []
        return
    }

    setPemFile(files[0])
}

const onPemDrop = event => {
    isPemDragOver.value = false

    const file = event.dataTransfer?.files?.[0]
    if (!file) return

    setPemFile(file)
}

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
                message: 'Uppdaterad Swish',
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

const removePemFile = () => {
  pemFile.value = []
  pem_url.value = null
  pem_at.value = null
  pem_size.value = null
    isPemDragOver.value = false
    clearPemFileInput()
}

const undoPendingPemSelection = () => {
    pemFile.value = []
    isPemDragOver.value = false
    clearPemFileInput()
}

const downloadFile = async url => {
  if (!url) {
    isFileMissingDialogVisible.value = true

    return
  }

  try {
    isRequestOngoing.value = true

    const response = await fetch(
      themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + url,
    )

    isRequestOngoing.value = false
    const blob = await response.blob()
    const blobUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = blobUrl
    link.download = url.split('/').pop()
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Error:', error)
    setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
    clearAdvisorLater(5000)
  }
}

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});

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
            {{ advisor.message }}
        </VSnackbar>
        
        <VCard
            class="card-fill"
            :class="[
                windowWidth < 1024 ? 'flex-column' : 'flex-row',
                $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
            ]"
        >
            <VForm
                ref="refForm"
                class="card-form"
                v-model="isFormValid"
                @submit.prevent="swish"
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
                                class="subtitle-page-swish"
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

                <VCardText :class="windowWidth < 1024 ? 'p-0' : 'px-0 py-4'">
                    <div :class="windowWidth < 1024 ? 'p-0' : 'px-0'">
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
                    </div>
                    
                    <div class="mt-4 d-flex flex-column gap-4" v-if="swishHasCSR && is_payout" :class="windowWidth < 1024 ? '' : 'px-0'">
                        <div class="d-flex flex-column gap-2">
                            <div class="title-info" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'">
                                CSR-fil
                            </div>
                            <span class="subtitle-info" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'">
                                Ladda ner CSR-filen och skicka den till din bank för att beställa ett Swish-certifikat.
                            </span>
                            <div 
                                class="d-flex flex-wrap"
                                :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                            >
                                <div
                                    class="d-flex flex-column"
                                    :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'"
                                >
                                    <div
                                        v-if="csr_url" 
                                        class="card-swish-card d-flex justify-space-between gap-4"
                                        :class="windowWidth < 1024 ? 'flex-column align-start' : 'flex-row align-center'"
                                    >
                                        <div class="d-flex align-center gap-2">
                                            <VAvatar
                                                color="#E7E7E7"
                                                icon="custom-facture"
                                                variant="flat"
                                                size="44"
                                                rounded="lg"
                                                class="indicator-icon"
                                                :style="{ '--icon-color': '#454545' }"
                                            />

                                            <div class="d-flex flex-column gap-1">
                                                <div class="card-swish-title">{{ getFileNameFromPath(csr_url) }}</div>
                                                <div class="card-swish-meta">Genererad {{ formatDateSimple(csr_at) }} · {{ formatFileSize(csr_size) }}</div>
                                            </div>
                                        </div>

                                        <VBtn
                                            class="btn-gradient"
                                            :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                                            :download="getFileNameFromPath(csr_url)"
                                            @click="downloadFile(csr_url)"
                                        >
                                            <VIcon icon="custom-download" size="24"/>
                                            Ladda ner
                                        </VBtn>
                                    </div>
                                    <div v-else class="text-disabled">Ingen CSR-fil tillgänglig.</div>
                                </div>
                            </div>
                        </div>

                       <div class="d-flex flex-column gap-2">
                            <div class="title-info" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'">
                                PEM-fil
                            </div>
                             <span class="subtitle-info" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'">
                                Ladda upp certifikatet du fått från din bank för att aktivera automatiska Swish-utbetalningar. Du kan ersätta filen när som helst.
                            </span>
                            <div 
                                class="d-flex flex-wrap"
                                :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                            >
                                <div 
                                    class="d-flex flex-column gap-4" 
                                    :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'"
                                >
                                    <div
                                        v-if="!shouldShowPemCard"
                                        class="pem-dropzone"
                                        :class="{ 'is-dragover': isPemDragOver, 'has-file': !!pemFileName }"
                                        role="button"
                                        tabindex="0"
                                        @click="openPemFilePicker"
                                        @keydown.enter.prevent="openPemFilePicker"
                                        @keydown.space.prevent="openPemFilePicker"
                                        @dragover.prevent="isPemDragOver = true"
                                        @dragleave.prevent="isPemDragOver = false"
                                        @drop.prevent="onPemDrop"
                                    >
                                        <VIcon icon="custom-upload" size="24" class="pem-dropzone-icon" />

                                        <div class="pem-dropzone-title">
                                            <template v-if="pemFileName">
                                                {{ pemFileName }}
                                            </template>
                                            <template v-else>
                                                Dra och slapp filen har, eller <span class="pem-dropzone-link">bladdra bland filer</span>
                                            </template>
                                        </div>

                                        <div class="pem-dropzone-subtitle">
                                            Endast .pem-filer, max 2 MB
                                        </div>
                                    </div>

                                    <VFileInput
                                        ref="pemFileInputRef"
                                        v-model="pemFile"
                                        class="d-none"
                                        accept=".pem"
                                        @update:modelValue="onPemInputChange"
                                    />

                                    <div
                                        v-if="shouldShowPemCard" 
                                        class="card-swish-card d-flex justify-space-between gap-4"
                                        :class="windowWidth < 1024 ? 'flex-column align-start' : 'flex-row align-center'"
                                    >
                                        <div class="d-flex align-center gap-2">
                                            <VAvatar
                                                color="#E7E7E7"
                                                icon="custom-star"
                                                variant="flat"
                                                size="44"
                                                rounded="lg"
                                                class="indicator-icon"
                                                :style="{ '--icon-color': '#454545' }"
                                            />

                                            <div class="d-flex flex-column gap-1">
                                                <div class="d-flex align-center gap-1">
                                                    <div class="card-swish-title">{{ pemDisplayName }}</div>
                                                    <span 
                                                        v-if="hasPendingPemSelection" 
                                                        class="status-chip-mobile status-chip-pending"
                                                        :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                                                    >
                                                        Väntar på Spara
                                                    </span>
                                                </div>
                                                <div class="card-swish-meta">{{ pemDisplayMeta }}</div>
                                            </div>
                                        </div>

                                        <VBtn
                                            v-if="hasPendingPemSelection"
                                            class="btn-light"
                                            :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                                            @click="undoPendingPemSelection"
                                        >
                                            <VIcon icon="custom-return" size="24"/>
                                            Ångra
                                        </VBtn>

                                        <div v-else class="d-flex gap-2" :class="windowWidth < 1024 ? 'w-100' : 'w-auto'">                                            
                                            <VBtn
                                                class="btn-light"
                                                block
                                                @click="openPemFilePicker"
                                            >
                                                <VIcon icon="custom-refresh" size="24"/>
                                                Ersätt fil
                                            </VBtn>
                                            <VBtn
                                                class="btn-error"
                                                :download="getFileNameFromPath(csr_url)"
                                                @click="removePemFile()"
                                            >
                                                <VIcon icon="custom-waste" size="24"/>
                                            </VBtn>
                                        </div>
                                    </div>
                                </div>  
                            </div>
                       </div>   
                    </div>

                    <div class="title-info mt-4">
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
                                :class="windowWidth < 1024 ? 'me-3' : 'ms-8'"
                                hide-details
                                inset
                            />
                        </div>  
                    </div>
                </VCardText>

                <VCardText 
                    class="d-flex justify-end gap-3 flex-wrap dialog-actions"
                    :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 50%;'"
                    :class="windowWidth < 1024 ? 'flex-column px-0' : 'flex-row pe-0'"
                >
                    <VBtn 
                        class="btn-light" 
                        :to="{ name: 'dashboard-admin-suppliers' }"
                    >
                        Avbryt
                    </VBtn>
                    <VBtn class="btn-gradient" type="submit"> Spara </VBtn>
                </VCardText>
            </VForm>
        </VCard>
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

    .subtitle-page-swish {
        font-weight: 400;
        font-size: 16px;
        line-height: 100%;
        color: #454545;
    }

    .title-info {
        font-weight: 700;
        font-size: 16px;
        line-height: 24px;
        letter-spacing: 0;
        color: #454545;
    }

    .subtitle-info {
        font-weight: 400;
        font-size: 16px;
        line-height: 24px;
        letter-spacing: 0;
        color: #454545;
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

        .card-swish-card {
            padding: 16px 18px;
            border: 1px solid #E7E7E7;
            border-radius: 14px;
        }

        .card-swish-title {
            overflow: hidden;
            font-weight: 700;
            font-size: 16px;
            line-height: 100%;
            letter-spacing: 0;
            color: #454545;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card-swish-meta {
            font-weight: 400;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 0;
            color: #878787;
        }

        .pem-dropzone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 141px;
            padding: 24px;
            border: 1px dashed #E7E7E7;
            border-radius: 14px;
            background: #F6F6F6;
            cursor: pointer;
            text-align: center;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .pem-dropzone.is-dragover {
            border-color: #006D5C;
            background: #F6F6F6;
        }

        .pem-dropzone.has-file {
            border-style: solid;
            background: #F6F6F6;
        }

        .pem-dropzone-icon {
            color: #454545;
        }

        .pem-dropzone-title {
            font-weight: 600;
            font-size: 15px;
            line-height: 100%;
            letter-spacing: 0;
            color: #1C2925;
            word-break: break-word;
        }

        .pem-dropzone-link {
            color: #0D6E2D;
            font-weight: 600;
        }

        .pem-dropzone-subtitle {
            font-weight: 400;
            font-size: 13px;
            line-height: 100%;
            letter-spacing: 0;
            color: #878787;
        }
    }

    
</style>

<route lang="yaml">
    meta:
      action: view
      subject: suppliers
</route>