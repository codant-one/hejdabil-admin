<script setup>

import { emailValidator, requiredValidator, phoneValidator, urlValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { PHONE_INPUT_DEFAULTS, formatPhonePayload, normalizePhoneInput } from '@/@core/utils/phone'
import { useSuppliersStores } from '@/stores/useSuppliers'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router'

const suppliersStores = useSuppliersStores()

const emitter = inject("emitter")

const isRequestOngoing = ref(true)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref('tab-1')
const isMobile = ref(false)
const isReactivateSupplierDialog = ref(false)
const isReactivatingSupplier = ref(false)
const reactivationSupplierId = ref(null)

const company = ref('')
const organization_number = ref('')
const link = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const swish = ref('')
const bank = ref('')
const account_number = ref('')
const name = ref('')
const last_name = ref('')
const email = ref('')

const supplierPhonePrefix = `+${PHONE_INPUT_DEFAULTS.defaultPhoneCode}`
const supplierPhoneDigits = PHONE_INPUT_DEFAULTS.defaultPhoneDigits
const supplierPhoneRules = [requiredValidator, minLengthDigitsValidator(supplierPhoneDigits), phoneValidator]

const normalizeSupplierPhoneForInput = value => normalizePhoneInput(value, [], null, PHONE_INPUT_DEFAULTS)

const formatSupplierPhoneForPayload = value => formatPhonePayload(value, [], null, PHONE_INPUT_DEFAULTS)

const closeReactivateSupplierDialog = function() {
    isReactivateSupplierDialog.value = false
    reactivationSupplierId.value = null
}

const getEmailValidationMessage = function(error) {
    if (error?.response?.data?.feedback !== 'params_validation_failed')
        return ''

    if (typeof error.response.data.message === 'string')
        return error.response.data.message

    return ''
}

const reactivateSupplierAccount = async function() {
    if (!reactivationSupplierId.value) {
        closeReactivateSupplierDialog()
        return
    }

    isReactivatingSupplier.value = true
    isRequestOngoing.value = true

    try {
        const response = await suppliersStores.activateSupplier(reactivationSupplierId.value)

        closeReactivateSupplierDialog()

        let data = {
            message: response.data.message || 'Användaren har återaktiverats!',
            error: false
        }

        router.push({ name : 'dashboard-admin-suppliers'})
        emitter.emit('toast', data)
    } catch (error) {
        closeReactivateSupplierDialog()

        let data = {
            message: error?.response?.data?.message || error?.message || 'Ett serverfel uppstod. Försök igen.',
            error: true
        }

        emitter.emit('toast', data)
    } finally {
        isReactivatingSupplier.value = false
        isRequestOngoing.value = false
    }
}


watchEffect(fetchData);

async function fetchData() {
  setTimeout(() => {
    isRequestOngoing.value = false;
  }, 3000);
  
}

onMounted(async () => {
    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

const formatOrgNumber = () => {

    let numbers = organization_number.value.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    organization_number.value = numbers
}

const handlePhoneInput = () => {
    phone.value = normalizeSupplierPhoneForInput(phone.value)
}

const onSubmit = () => {

    refForm.value?.validate().then(({ valid }) => {
        if (valid && currentTab.value === 0 && refForm.value.items.length < 10) {
            currentTab.value++
        } else if ((!valid && currentTab.value === 0 && refForm.value.items.length === 10) || (!valid && currentTab.value === 0 && refForm.value.items.length === 13)) {
            currentTab.value++
        } else if (!valid && currentTab.value === 1 && refForm.value.items.length > 10) {
            currentTab.value++
        } else if (valid  && currentTab.value < 2 && refForm.value.items.length > 8) {
            currentTab.value++
        } else if (valid && currentTab.value === 2) {
            let formData = new FormData()

            //company
            formData.append('company', company.value)
            formData.append('organization_number', organization_number.value)
            formData.append('link', link.value)
            formData.append('address', address.value)
            formData.append('street', street.value)
            formData.append('postal_code', postal_code.value)
            formData.append('phone', formatSupplierPhoneForPayload(phone.value))
            formData.append('swish', swish.value)

            //bank
            formData.append('bank', bank.value)
            formData.append('account_number', account_number.value)

            //contact
            formData.append('name', name.value)
            formData.append('last_name', last_name.value)
            formData.append('email', email.value)

            isRequestOngoing.value = true

            suppliersStores.addSupplier(formData)
                .then((res) => {
                    if (res.data.success) {
                        
                        let data = {
                            message: 'Leverantör skapad!',
                            error: false
                        }

                        router.push({ name : 'dashboard-admin-suppliers'})
                        emitter.emit('toast', data)
                    }
                    isRequestOngoing.value = false
                })
                .catch((err) => {
                    const emailValidationMessage = getEmailValidationMessage(err)

                    if (emailValidationMessage.includes('En användare med den angivna e-postadressen finns redan')) {
                        suppliersStores.getInactiveSupplierByEmail(email.value)
                            .then((inactiveSupplier) => {
                                if (inactiveSupplier?.supplier_id) {
                                    reactivationSupplierId.value = inactiveSupplier.supplier_id
                                    isReactivateSupplierDialog.value = true
                                    isRequestOngoing.value = false
                                    return
                                }

                                let data = {
                                    message: emailValidationMessage,
                                    error: true
                                }

                                router.push({ name : 'dashboard-admin-suppliers'})
                                emitter.emit('toast', data)
                                isRequestOngoing.value = false
                            })
                            .catch(() => {
                                let data = {
                                    message: emailValidationMessage,
                                    error: true
                                }

                                router.push({ name : 'dashboard-admin-suppliers'})
                                emitter.emit('toast', data)
                                isRequestOngoing.value = false
                            })

                        return
                    }

                    let data = {
                        message: err?.response?.data?.message || err.message,
                        error: true
                    }

                    router.push({ name : 'dashboard-admin-suppliers'})
                    emitter.emit('toast', data)

                    isRequestOngoing.value = false
                })
        }
    })
}

</script>

<template>
    <section>
        <VRow>
            <LoadingOverlay :is-loading="isRequestOngoing" />

            <VCol cols="12" md="12">
                <div class="d-flex mt-5 flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
                    <div class="d-flex flex-column justify-center">
                        <h6 class="text-md-h4 text-h6 font-weight-medium">
                            Lägg till en ny leverantör 😃🌟
                        </h6>
                        <span>Ladda upp ditt företag med leverantörer 🎉</span>
                    </div>
                    <VSpacer />
                    <div class="d-flex gap-4 w-100 w-md-auto">
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
            </VCol>
            <VCol cols="12" md="12">
                <VForm
                    ref="refForm"
                    v-model="isFormValid"
                    @submit.prevent="onSubmit">
                    <VCard flat class="px-2 px-md-12">
                        <VCardText class="px-2 pt-0 px-md-12 pt-md-5">                
                            <VTabs
                                v-model="currentTab"
                                grow
                                stacked
                                disabled>
                                <VTab>
                                    <VIcon icon="mdi-domain" class="mb-0 mb-md-2" />
                                    <span v-if="!isMobile">Företag</span>
                                </VTab>
                                <VTab>
                                    <VIcon icon="mdi-bank" class="mb-0 mb-md-2" />
                                    <span v-if="!isMobile">Bankuppgifter</span>
                                </VTab>
                                <VTab>
                                    <VIcon icon="mdi-account-tie" class="mb-0 mb-md-2"/>
                                    <span v-if="!isMobile">Kontakt</span>
                                </VTab>
                            </VTabs>
                            <VCardText class="px-2 px-md-12">
                                <VWindow v-model="currentTab" class="pt-3">
                                    <!-- company -->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <VTextField
                                                    v-model="company"
                                                    :rules="[requiredValidator]"
                                                    label="Företagsnamn"
                                                />
                                            </VCol>   
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="organization_number"
                                                    label="Organisationsnummer"
                                                    :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                                                    minLength="11"
                                                    maxlength="11"
                                                    @input="formatOrgNumber()"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="link"
                                                    :rules="[urlValidator]"
                                                    label="Hemsida"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="12">
                                                <VTextarea
                                                    v-model="address"
                                                    rows="3"
                                                    :rules="[requiredValidator]"
                                                    label="Adress"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="postal_code"
                                                    :rules="[requiredValidator]"
                                                    label="Postnummer"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="street"
                                                    :rules="[requiredValidator]"
                                                    label="Stad"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="phone"
                                                    class="always-show-prefix"
                                                    :rules="supplierPhoneRules"
                                                    :min-length="supplierPhoneDigits"
                                                    :maxlength="supplierPhoneDigits"
                                                    :prefix="supplierPhonePrefix"
                                                    inputmode="numeric"
                                                    @input="handlePhoneInput"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="swish"
                                                    :rules="[phoneValidator]"
                                                    label="Swish"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!-- bank -->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="bank"
                                                    :rules="[requiredValidator]"
                                                    label="Bankens namn"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="account_number"
                                                    :rules="[requiredValidator]"
                                                    label="Kontonummer"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!-- contact -->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="name"
                                                    :rules="[requiredValidator]"
                                                    label="Namn"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="last_name"
                                                    :rules="[requiredValidator]"
                                                    label="Efternamn"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="12">
                                                <VTextField
                                                    :rules="[emailValidator, requiredValidator]"
                                                    v-model="email"
                                                    label="E-post"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                </VWindow>
                            </VCardText>
                        </VCardText>
                    </VCard>
                    <VRow class="mt-5">
                        <!-- 👉 Submit and Cancel -->
                        <VCol cols="12" md="12">
                            <div class="text-center align-center justify-content-center">
                                <VBtn
                                    v-if="currentTab > 0"
                                    variant="tonal"
                                    color="secondary"
                                    class="mb-3 mb-md-0 me-md-3 w-100 w-md-auto"
                                    @click="currentTab--"
                                    >
                                    Tillbaka
                                </VBtn>
                                <VBtn type="submit" class="w-100 w-md-auto">
                                    {{ (currentTab === 2) ? 'Skicka' : ' Nästa' }}
                                </VBtn>
                            </div>
                        </VCol>
                    </VRow>
                </VForm>
            </VCol>
        </VRow>
    </section>

    <VDialog
        v-model="isReactivateSupplierDialog"
        persistent
        class="action-dialog"
    >
        <VCard title="Återaktivera konto">
            <VDivider class="mt-4"/>
            <VCardText>
                Denna e-postadress är redan registrerad.<br>
                Vill du återaktivera kontot?
            </VCardText>

            <VCardText class="d-flex justify-end gap-3 flex-wrap">
                <VBtn
                    color="secondary"
                    variant="tonal"
                    @click="closeReactivateSupplierDialog"
                >
                    Avbryt
                </VBtn>
                <VBtn
                    :loading="isReactivatingSupplier"
                    @click="reactivateSupplierAccount"
                >
                    Acceptera
                </VBtn>
            </VCardText>
        </VCard>
    </VDialog>
</template>

<style scoped>
    :deep(.always-show-prefix .v-text-field__prefix) {
        opacity: 1 !important;
        height: 56px;
        color: #454545;
    }

    :deep(.v-input.always-show-prefix .v-field__input) {
        padding: 16px 0 !important;
    }

    .v-btn--disabled {
        opacity: 1 !important;
    }
</style>

<route lang="yaml">
    meta:
      action: create
      subject: suppliers
</route>