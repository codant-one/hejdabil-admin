<script setup>

import { useDisplay } from "vuetify";
import { emailValidator, requiredValidator, phoneValidator, smsSenderValidator, urlValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { PHONE_INPUT_DEFAULTS, formatPhonePayload, normalizePhoneInput } from '@/@core/utils/phone'
import { useSuppliersStores } from '@/stores/useSuppliers'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router'
import MobileScrollTabs from "@/components/common/MobileScrollTabs.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const sectionEl = ref(null);
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const suppliersStores = useSuppliersStores()

const emitter = inject("emitter")
const route = useRoute()

const isRequestOngoing = ref(true)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref('1')
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

const allowNavigation = ref(false)
const isConfirmLeaveVisible = ref(false)
const nextRoute = ref(null)
const initialData = ref(null)

const hasPhoneValue = value => !!String(value ?? '').trim()

const phoneOrLandlineRequiredValidator = value => {
    return hasPhoneValue(value) || hasPhoneValue(phone.value) || hasPhoneValue(landline.value) || 'krävs *'
}

const supplierPhonePrefix = `+${PHONE_INPUT_DEFAULTS.defaultPhoneCode}`
const supplierPhoneDigits = PHONE_INPUT_DEFAULTS.defaultPhoneDigits

const supplierPhoneRules = computed(() => [
    phoneOrLandlineRequiredValidator,
    minLengthDigitsValidator(supplierPhoneDigits),
    phoneValidator,
])

const landlineRules = computed(() => [
    phoneOrLandlineRequiredValidator,
    phoneValidator,
])

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

    nextTick(() => {
        initialData.value = JSON.parse(JSON.stringify(currentData.value))
    })  
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

const showTabValidationWarning = (message) => {
    advisor.value = {
        type: 'warning',
        message,
        show: true,
    }

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false,
        }
    }, 3000)
}

const getTabValidationErrors = () => {
    const isPhoneValid = supplierPhoneRules.value.every(rule => rule(phone.value) === true)
    const isLandlineValid = landlineRules.value.every(rule => rule(landline.value) === true)

    const hasTab0Errors = !company.value ||
                            !organization_number.value ||
                            // !link.value ||
                            !address.value ||
                            !postal_code.value ||
                            !street.value ||
                            !isPhoneValid ||
                            !isLandlineValid

    const hasTab1Errors = !bank.value ||
                            !account_number.value 

    // const hasTab2Errors = !name.value ||
    //                         !last_name.value ||
    //                         !email.value ||

    return {
        hasTab0Errors,
        hasTab1Errors,
    }
}

const validateTabByIndex = async (tabIndex, tabErrors = getTabValidationErrors()) => {
    const tabMessages = {
        0: 'Vänligen fyll i alla obligatoriska fält i fliken Företag',
        1: 'Vänligen fyll i alla obligatoriska fält i fliken Bankuppgifter',
    }

    const hasErrorsByTab = {
        0: tabErrors.hasTab0Errors,
        1: tabErrors.hasTab1Errors,
        //2: tabErrors.hasTab2Errors,
    }

    if (hasErrorsByTab[tabIndex]) {
        await nextTick()
        refForm.value?.validate()
        showTabValidationWarning(tabMessages[tabIndex])
        return false
    }

    return true
}

const onTabChange = async (targetTab) => {
    const nextTab = Number(targetTab)
    if (Number.isNaN(nextTab) || nextTab === currentTab.value) return

    if (nextTab < currentTab.value) {
        currentTab.value = nextTab
        return
    }

    const tabErrors = getTabValidationErrors()

    for (let tabIndex = 0; tabIndex < nextTab; tabIndex++) {
        const isCurrentStepValid = await validateTabByIndex(tabIndex, tabErrors)
        if (!isCurrentStepValid) {
            currentTab.value = tabIndex
            return
        }
    }

    currentTab.value = nextTab
}

const onSubmit = async () => {
    const {
        hasTab0Errors,
        hasTab1Errors,
    } = getTabValidationErrors()

    if (currentTab.value === 0) {
        const isTabValid = await validateTabByIndex(0, { hasTab0Errors, hasTab1Errors })
        if (!isTabValid) return

        currentTab.value++
        return
    }

    if (currentTab.value === 1) {
        const isTabValid = await validateTabByIndex(1, { hasTab0Errors, hasTab1Errors })
        if (!isTabValid) return

        currentTab.value++
        return
    }

    if (currentTab.value === 2) {
        if (hasTab0Errors) {
            currentTab.value = 0
            await nextTick()
            refForm.value?.validate()
            showTabValidationWarning('Vänligen fyll i alla obligatoriska fält i fliken Företag')
            return
        }

        if (hasTab1Errors) {
            currentTab.value = 1
            await nextTick()
            refForm.value?.validate()
            showTabValidationWarning('Vänligen fyll i alla obligatoriska fält i fliken Bankuppgifter')
            return
        }
        refForm.value?.validate().then(({ valid }) => {
            if (valid) {
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
                            allowNavigation.value = true;

                            let data = {
                                message: 'Uppdaterad leverantör!',
                                error: false
                            }

                            // Save current state so the dirty-check stops blocking navigation
                            initialData.value = JSON.parse(JSON.stringify(currentData.value));

                            skapatsDialog.value = true;
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

                        // Save current state so the dirty-check stops blocking navigation
                        initialData.value = JSON.parse(JSON.stringify(currentData.value));

                        router.push({ name : 'dashboard-admin-suppliers'})
                        emitter.emit('toast', data)

                        isRequestOngoing.value = false
                    })
            }
        })
    }
}

const currentData = computed(() => ({
    //Company
    company: company.value,
    organization_number: organization_number.value,
    link: link.value,
    address: address.value,
    street: street.value,
    postal_code: postal_code.value,
    phone: phone.value,
    landline: landline.value,
    swish: swish.value,
    sms_sender: sms_sender.value,

    //bank
    bank: bank.value,
    account_number: account_number.value,

    // contact
    name: name.value ,
    last_name: last_name.value,
    email: email.value
}))

const isDirty = computed(() => {
  if (!initialData.value) return false
  try {
    return JSON.stringify(currentData.value) !== JSON.stringify(initialData.value)
  } catch (e) {
    return true
  }
})

const confirmLeave = () => {
    isConfirmLeaveVisible.value = false;
    allowNavigation.value = true;

    if (nextRoute.value) {
        router.push(nextRoute.value);
    }
};

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});

// Intercept all navigation attempts
onBeforeRouteLeave((to, from, next) => {
  if (allowNavigation.value || !isDirty.value) {
    next();
  } else {
    nextRoute.value = to;
    isConfirmLeaveVisible.value = true;
    next(false);
  }
});

</script>

<template>
    <section class="page-section agreement-page" ref="sectionEl">
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
            v-model="isFormValid"
            class="card-form"
            validate-on="submit"
            @submit.prevent="onSubmit"
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
                                Lägg till en ny leverantör
                            </span>
                            <span 
                                class="subtitle-page"
                                :class="windowWidth < 1024 ? 'd-none' : 'justify-start'"
                            >
                                Fyll i leverantörens uppgifter för att skapa ett nytt konto.
                            </span>
                            <span 
                                :class="windowWidth >= 1024 ? 'd-none' : 'justify-start'"
                            >
                                <div class="d-flex align-center justify-content-between">
                                    <span class="d-flex subtitle-page">Företag</span>
                                    <div class="flex-grow-1"></div>
                                    <span class="d-flex subtitle-page gap-1">Steg {{ currentTab + 1}} av 3</span>
                                </div>
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

                <VDivider :class="windowWidth < 1024 ? 'd-none' : 'mb-8'" />

                <MobileScrollTabs
                    :target-ref="sectionEl"
                    :model-value="currentTab"
                    @update:modelValue="onTabChange"
                    grow               
                    :show-arrows="false"
                    class="agreements-tabs"
                >
                    <VTab :value="0" :class="{ 'tab-completed': currentTab > 0 }">
                        <div :class="windowWidth < 1024 ? 'd-none' : 'tab-icon'">1</div>
                        Företag
                    </VTab>
                    <VTab :value="1" :class="{ 'tab-completed': currentTab > 1 }">
                        <div :class="windowWidth < 1024 ? 'd-none' : 'tab-icon'">2</div>
                        Bankuppgifter
                    </VTab>
                    <VTab :value="1" :class="{ 'tab-completed': currentTab > 2 }">
                        <div :class="windowWidth < 1024 ? 'd-none' : 'tab-icon'">3</div>
                        <span v-if="windowWidth < 1024"> Kontakt </span>
                        <span v-else> Plan och kontakt </span>
                    </VTab>
                </MobileScrollTabs>

                <VCardText class="px-0">                
                    <VWindow v-model="currentTab">
                        <!-- company -->
                        <VWindowItem :value="0" class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Företagsinformation
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Företagsnamn*" />
                                            <VTextField
                                                v-model="company"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>   
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Organisationsnummer*" />
                                            <VTextField
                                                v-model="organization_number"
                                                :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                                                minLength="11"
                                                maxlength="11"
                                                @input="formatOrgNumber()"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Hemsida" />
                                            <VTextField
                                                v-model="link"
                                                :rules="[urlValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />
                                            <VTextarea
                                                v-model="address"
                                                rows="3"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Postnummer*" />
                                            <VTextField
                                                v-model="postal_code"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Stad*" />
                                            <VTextField
                                                v-model="street"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mobilnummer*" />
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
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon*" />
                                            <VTextField
                                                v-model="landline"
                                                type="tel"
                                                inputmode="numeric"
                                                :rules="landlineRules"
                                                @input="handleLandlineInput"
                                            />
                                        </div>
                                    </div>

                                    <VDivider :class="windowWidth < 1024 ? 'my-4' : 'my-8'" />

                                    <div class="title-tabs mb-5">
                                        Betalningsinformation
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Swish" />
                                            <VTextField
                                                v-model="swish"
                                                :rules="[phoneValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="SMS Sender" />
                                            <VTextField
                                                v-model="sms_sender"
                                                :rules="supplierSmsSenderRules"
                                                :maxlength="supplierSmsSenderMaxLength"
                                                hint="A-Z, 0-9 och mellanslag, max 11 tecken"
                                                persistent-hint
                                                @input="handleSmsSenderInput"
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>
                        <!-- bank -->
                        <VWindowItem :value="1" class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Bankuppgifter
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bankens namn*" />
                                            <VTextField
                                                v-model="bank"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kontonummer*" />
                                            <VTextField
                                                v-model="account_number"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>
                        <!-- contact -->
                        <VWindowItem :value="2" class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Kontaktperson
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Namn*" />
                                            <VTextField
                                                v-model="name"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Efternamn*" />
                                            <VTextField
                                                v-model="last_name"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
                                            <VTextField
                                                :rules="[emailValidator, requiredValidator]"
                                                v-model="email"
                                                disabled
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>
                    </VWindow>
                </VCardText>

                <VDivider :class="windowWidth < 1024 ? 'mb-4' : 'mb-8'" />

                <VCardText class="p-0 d-flex w-100">
                    <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'"/>
                    <div class="d-flex mb-4" :class="windowWidth < 1024 ? 'w-100 gap-2' : 'gap-4'">
                        <VBtn
                            v-if="currentTab > 0"
                            class="btn-light"
                            :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                            :block="windowWidth < 1024"
                            @click="currentTab--"
                            >
                            <VIcon icon="custom-return" size="24" />
                            Tillbaka
                        </VBtn>
                        <VBtn 
                            type="button" 
                            :block="windowWidth < 1024"
                            class="btn-gradient"
                            :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                            @click="onSubmit"
                        >
                            {{ (currentTab === 2) ? 'Uppdatering' : 'Nästa' }}
                        </VBtn>
                    </div>
                </VCardText>
            </VCard>
        </VForm>

        <!-- Confirm leave without saving -->
        <VDialog
            v-model="isConfirmLeaveVisible"
            persistent
            class="action-dialog"
        >
            <VBtn
                icon
                class="btn-white close-btn"
                @click="isConfirmLeaveVisible = false"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>
            <VCard>
                <VCardText class="dialog-title-box">
                    <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
                    <div class="dialog-title">Du har osparade ändringar</div>
                </VCardText>
                <VCardText class="dialog-text">
                    Om du lämnar sidan nu kommer dina ändringar inte att sparas.
                </VCardText>
                <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="confirmLeave">Lämna sidan</VBtn>
                    <VBtn class="btn-gradient" @click="isConfirmLeaveVisible = false">Stanna kvar</VBtn>
                </VCardText>
            </VCard>
        </VDialog>

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

    .tab-icon {
        background-color: #1C2925;
        border-radius: 20%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 12px;
        font-weight: 500;
    }
</style>

<style lang="scss">
    .always-show-prefix .v-text-field__prefix {
        opacity: 1 !important;
    }

    .card-info {
        background-color: #F6F6F6;
        border-radius: 16px;
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
    }

    .v-tabs.agreements-tabs {
    .v-btn {
      min-width: 50px !important;
      .v-btn__content {
        font-size: 14px !important;
        color: #454545;
      }
    }

    .v-btn.tab-completed {
      .v-tab__slider {
        display: block;
        opacity: 1;
        block-size: 1px;
        background: linear-gradient(
          90deg,
          #57f287 0%,
          #00eeb0 50%,
          #00ffff 100%
        );
      }
    }
  }

  @media (max-width: 776px) {
      .v-tabs.agreements-tabs {
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
      action: edit
      subject: suppliers
</route>