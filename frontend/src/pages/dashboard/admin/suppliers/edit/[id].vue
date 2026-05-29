<script setup>

import { emailValidator, requiredValidator, phoneValidator, smsSenderValidator, urlValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { PHONE_INPUT_DEFAULTS, formatPhonePayload, normalizePhoneInput } from '@/@core/utils/phone'
import { useSuppliersStores } from '@/stores/useSuppliers'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router'

const suppliersStores = useSuppliersStores()

const emitter = inject("emitter")
const route = useRoute()

const isRequestOngoing = ref(true)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref('tab-1')
const isMobile = ref(false)

const supplier = ref(null)
const company = ref('')
const organization_number = ref('')
const link = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const landline = ref('')
const swish = ref('')
const sms_sender = ref('')
const bank = ref('')
const account_number = ref('')
const name = ref('')
const last_name = ref('')
const email = ref('')

const hasPhoneValue = value => !!String(value ?? '').trim()

const phoneOrLandlineRequiredValidator = value => {
    return hasPhoneValue(value) || hasPhoneValue(phone.value) || hasPhoneValue(landline.value) || 'krävs *'
}

const supplierPhonePrefix = `+${PHONE_INPUT_DEFAULTS.defaultPhoneCode}`
const supplierPhoneDigits = PHONE_INPUT_DEFAULTS.defaultPhoneDigits
const supplierPhoneRules = [phoneOrLandlineRequiredValidator, minLengthDigitsValidator(supplierPhoneDigits), phoneValidator]
const landlineRules = [phoneOrLandlineRequiredValidator, phoneValidator]
const supplierSmsSenderMaxLength = 11
const supplierSmsSenderRules = [smsSenderValidator]

const normalizeSupplierPhoneForInput = value => normalizePhoneInput(value, [], null, PHONE_INPUT_DEFAULTS)
const normalizeLandlineForInput = value => String(value ?? '').replace(/\D/g, '')

const normalizeSupplierSmsSenderForInput = value => String(value ?? '')
    .replace(/[åäöÅÄÖ]/g, char => ({ å: 'a', ä: 'a', ö: 'o', Å: 'A', Ä: 'A', Ö: 'O' }[char] ?? char))
    .replace(/[^A-Za-z0-9 ]+/g, '')
    .replace(/\s+/g, ' ')
    .trimStart()
    .slice(0, supplierSmsSenderMaxLength)

const formatSupplierPhoneForPayload = value => formatPhonePayload(value, [], null, PHONE_INPUT_DEFAULTS)

const formatSupplierSmsSenderForPayload = value => normalizeSupplierSmsSenderForInput(value).trim()

onMounted(async () => {

    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

watchEffect(async() => {

    isRequestOngoing.value = true


    if(Number(route.params.id) && route.name === 'dashboard-admin-suppliers-edit-id') {
        supplier.value = await suppliersStores.showSupplier(Number(route.params.id))
       
        //company
        company.value = supplier.value.user.user_detail.company
        organization_number.value = supplier.value.user.user_detail.organization_number
        link.value = supplier.value.user.user_detail.link
        address.value = supplier.value.user.user_detail.address
        street.value = supplier.value.user.user_detail.street
        postal_code.value = supplier.value.user.user_detail.postal_code
        phone.value = normalizeSupplierPhoneForInput(supplier.value.user.user_detail.phone)
        landline.value = normalizeLandlineForInput(supplier.value.user.user_detail.landline)
        swish.value = supplier.value.user.user_detail.swish
        sms_sender.value = supplier.value.sms_sender

        //bank
        bank.value = supplier.value.user.user_detail.bank
        account_number.value = supplier.value.user.user_detail.account_number

        // contact
        name.value  = supplier.value.user.name
        last_name.value = supplier.value.user.last_name 
        email.value = supplier.value.user.email
    }

    isRequestOngoing.value = false
})

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

const handleLandlineInput = () => {
    landline.value = normalizeLandlineForInput(landline.value)
}

const handleSmsSenderInput = () => {
    sms_sender.value = normalizeSupplierSmsSenderForInput(sms_sender.value)
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

            formData.append('id', Number(route.params.id))
            formData.append('_method', 'PUT')

            //company
            formData.append('logo',  supplier.value.user.user_detail?.logo)
            formData.append('company', company.value)
            formData.append('organization_number', organization_number.value)
            formData.append('link', link.value)
            formData.append('address', address.value)
            formData.append('street', street.value)
            formData.append('postal_code', postal_code.value)
            formData.append('phone', formatSupplierPhoneForPayload(phone.value))
            formData.append('landline', normalizeLandlineForInput(landline.value))
            formData.append('swish', swish.value)            
            formData.append('iban', supplier.value.user.user_detail?.iban)     
            formData.append('iban_number', supplier.value.user.user_detail?.iban_number)
            formData.append('bic', supplier.value.user.user_detail?.bic)
            formData.append('plus_spin', supplier.value.user.user_detail?.plus_spin)
            formData.append('sms_sender', formatSupplierSmsSenderForPayload(sms_sender.value))
            formData.append('vat', supplier.value.user.user_detail?.vat)
            formData.append('personal_phone', supplier.value.user.user_detail?.personal_phone)
            formData.append('personal_address', supplier.value.user.user_detail?.personal_address)

            //bank
            formData.append('bank', bank.value)
            formData.append('account_number', account_number.value)

            //contact
            formData.append('name', name.value)
            formData.append('last_name', last_name.value)
            formData.append('email', email.value)

            isRequestOngoing.value = true

            let data = {
                data: formData, 
                id: Number(route.params.id)
            }

            suppliersStores.updateSupplier(data)
                .then((res) => {
                    if (res.data.success) {
                        
                        let data = {
                            message: 'Uppdaterad leverantör!',
                            error: false
                        }

                        router.push({ name : 'dashboard-admin-suppliers'})
                        emitter.emit('toast', data)
                    }
                    isRequestOngoing.value = false
                })
                .catch((err) => {
                    
                    let data = {
                        message: err.message,
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
                            Redigera en leverantör 😃🌟
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
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mobilnummer" />
                                                <VTextField
                                                    v-model="phone"
                                                    type="tel"
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
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon" />
                                                <VTextField
                                                    v-model="landline"
                                                    type="tel"
                                                    inputmode="numeric"
                                                    label="Telefon"
                                                    :rules="landlineRules"
                                                    @input="handleLandlineInput"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="swish"
                                                    :rules="[phoneValidator]"
                                                    label="Swish"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="sms_sender"
                                                    :rules="supplierSmsSenderRules"
                                                    :maxlength="supplierSmsSenderMaxLength"
                                                    hint="A-Z, 0-9 och mellanslag, max 11 tecken"
                                                    persistent-hint
                                                    label="SMS Sender"
                                                    @input="handleSmsSenderInput"
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
                                                    disabled
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
                                    {{ (currentTab === 2) ? 'Uppdatering' : 'Nästa' }}
                                </VBtn>
                            </div>
                        </VCol>
                    </VRow>
                </VForm>
            </VCol>
        </VRow>
    </section>
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
      action: edit
      subject: suppliers
</route>