<script setup>

import { useDisplay } from "vuetify";
import { emailValidator, requiredValidator, phoneValidator, smsSenderValidator, urlValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { PHONE_INPUT_DEFAULTS, formatPhonePayload, normalizePhoneInput } from '@/@core/utils/phone'
import { handleNumericTextFieldKeydown as handlePhoneKeydown, normalizeNumericTextInput, numericRangeValidator, numericTextFieldProps } from '@/@core/utils/numericTextField'
import { formatNumberInteger } from "@/@core/utils/formatters";
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

const isRequestOngoing = ref(true)

const refForm = ref()
const currentTab = ref(0)
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
const landline = ref('')
const swish = ref('')
const sms_sender = ref('')
const bank = ref('')
const account_number = ref('')
const name = ref('')
const last_name = ref('')
const email = ref('')
const is_yearly = ref(0)
const start_date = ref(null)
const end_date = ref(null)
const sms_price = ref(1.00)
const nonNegativeNumericRules = [numericRangeValidator({ min: 1 })]

const allowNavigation = ref(false)
const isConfirmLeaveVisible = ref(false)
const nextRoute = ref(null)
const initialData = ref(null)

const plans = ref([])
const selectedPlan = ref(2)

const parseLocalDate = (value) => {
    if (!value) return null

    if (value instanceof Date) {
        if (Number.isNaN(value.getTime())) return null
        return new Date(value.getFullYear(), value.getMonth(), value.getDate())
    }

    if (typeof value === 'string') {
        const match = value.match(/^(\d{4})-(\d{2})-(\d{2})$/)
        if (match) {
            const year = Number(match[1])
            const month = Number(match[2]) - 1
            const day = Number(match[3])
            return new Date(year, month, day)
        }
    }

    const parsed = new Date(value)
    if (Number.isNaN(parsed.getTime())) return null

    return new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate())
}

const toLocalDateYmd = (value) => {
    const date = parseLocalDate(value)
    if (!date) return null

    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
}

const addMonthsExact = (date, monthsToAdd) => {
    const source = new Date(date)
    const sourceDay = source.getDate()

    const target = new Date(source)
    target.setMonth(target.getMonth() + monthsToAdd, 1)

    const daysInTargetMonth = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate()
    target.setDate(Math.min(sourceDay, daysInTargetMonth))

    return target
}

const datePickerConfig = computed(() => ({
    dateFormat: 'Y-m-d',
    position: 'auto right',
}))

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

    await suppliersStores.fetchPlans()
    plans.value = suppliersStores.getPlans

    setTimeout(() => {
        isRequestOngoing.value = false;

        nextTick(() => {
            initialData.value = JSON.parse(JSON.stringify(currentData.value))
        })  
    }, 3000);
  
}

onMounted(async () => {
    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

watch([start_date, is_yearly], ([startDateValue, isYearlyValue]) => {
    const sourceDate = parseLocalDate(startDateValue)

    if (!sourceDate) {
        end_date.value = null
        return
    }

    const calculatedDate = Number(isYearlyValue) === 1
        ? addMonthsExact(sourceDate, 12)
        : addMonthsExact(sourceDate, 1)

    end_date.value = toLocalDateYmd(calculatedDate)
}, { immediate: true })

onMounted(() => {
    if (!start_date.value)
        start_date.value = toLocalDateYmd(new Date())
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
    const smsPriceValue = String(sms_price.value ?? '').trim()
    const smsPriceNumber = Number(smsPriceValue)
    const hasInvalidSmsPrice = smsPriceValue === '' || Number.isNaN(smsPriceNumber) || smsPriceNumber < 1

    const hasTab0Errors = !company.value ||
                            !organization_number.value ||
                            !address.value ||
                            !postal_code.value ||
                            !street.value ||
                            !isPhoneValid ||
                            !isLandlineValid

    const hasTab1Errors = !bank.value ||
                          !account_number.value 

    const hasTab2Errors = !name.value ||
                          !last_name.value ||
                          (email.value && emailValidator(email.value) !== true) ||
                          !start_date.value ||
                          hasInvalidSmsPrice

    return {
        hasTab0Errors,
        hasTab1Errors,
        hasTab2Errors
    }
}

const validateTabByIndex = async (tabIndex, tabErrors = getTabValidationErrors()) => {
    const tabMessages = {
        0: 'Vänligen fyll i alla obligatoriska fält i fliken Företag',
        1: 'Vänligen fyll i alla obligatoriska fält i fliken Bankuppgifter',
        2: 'Vänligen fyll i alla obligatoriska fält i fliken Plan och kontakt',
    }

    const hasErrorsByTab = {
        0: tabErrors.hasTab0Errors,
        1: tabErrors.hasTab1Errors,
        2: tabErrors.hasTab2Errors,
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
        hasTab2Errors,
    } = getTabValidationErrors()

    if (currentTab.value === 0) {
        const isTabValid = await validateTabByIndex(0, { hasTab0Errors, hasTab1Errors, hasTab2Errors })
        if (!isTabValid) return

        currentTab.value++
        return
    }

    if (currentTab.value === 1) {
        const isTabValid = await validateTabByIndex(1, { hasTab0Errors, hasTab1Errors, hasTab2Errors })
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

        if (hasTab2Errors) {
            await nextTick()
            refForm.value?.validate()
            showTabValidationWarning('Vänligen fyll i alla obligatoriska fält i fliken Plan och kontakt')
            return
        }

        refForm.value?.validate().then(({ valid }) => {
            if (valid) {
                let formData = new FormData()

                //company
                formData.append('company', company.value)
                formData.append('organization_number', organization_number.value)
                formData.append('link', link.value)
                formData.append('address', address.value)
                formData.append('street', street.value)
                formData.append('postal_code', postal_code.value)
                formData.append('phone', formatSupplierPhoneForPayload(phone.value))
                formData.append('landline', normalizeLandlineForInput(landline.value))
                formData.append('swish', swish.value)
                formData.append('sms_sender', formatSupplierSmsSenderForPayload(sms_sender.value))

                //bank
                formData.append('bank', bank.value)
                formData.append('account_number', account_number.value)

                //contact
                formData.append('name', name.value)
                formData.append('last_name', last_name.value)
                formData.append('email', email.value)
                formData.append('plan_id', selectedPlan.value)
                formData.append('is_yearly', is_yearly.value)
                formData.append('start_date', start_date.value)
                formData.append('end_date', end_date.value)
                formData.append('sms_price', sms_price.value)

                isRequestOngoing.value = true

                suppliersStores.addSupplier(formData)
                    .then((res) => {
                        if (res.data.success) {
                            allowNavigation.value = true;

                            let data = {
                                type: 'success',
                                message: 'Leverantör skapad!',
                                error: false
                            }
                            // skapatsDialog.value = true;;
                            router.push({ name : 'dashboard-admin-suppliers'})
                            emitter.emit('toast', data)
                        }
                        isRequestOngoing.value = false
                    })
                    .catch((err) => {
                        const emailValidationMessage = getEmailValidationMessage(err)

                        // Save current state so the dirty-check stops blocking navigation
                        initialData.value = JSON.parse(JSON.stringify(currentData.value));

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
                                        type: 'error',
                                        message: emailValidationMessage,
                                        error: true
                                    }
                                    router.push({ name : 'dashboard-admin-suppliers'})
                                    emitter.emit('toast', data)
                                    isRequestOngoing.value = false
                                })
                                .catch(() => {
                                    let data = {
                                        type: 'error',
                                        message: emailValidationMessage,
                                        error: true
                                    }
                                    router.push({ name : 'dashboard-admin-suppliers'})
                                    emitter.emit('toast', data);
                                    isRequestOngoing.value = false
                                })

                            return
                        }

                        let data = {
                            type: 'error',
                            message: err?.response?.data?.message || err.message,
                            error: true
                        }

                        router.push({ name : 'dashboard-admin-suppliers'})
                        emitter.emit('respAddOrUpdateSuppliers', data)

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
    email: email.value,
    plan_id: selectedPlan.value,
    is_yearly: is_yearly.value,
    start_date: start_date.value,
    end_date: end_date.value,
    sms_price: sms_price.value
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
                                Avbryt
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
                    class="suppliers-tabs"
                >
                    <VTab :value="0" :class="{ 'tab-completed': currentTab > 0 }">
                        <div :class="windowWidth < 1024 ? 'd-none' : 'tab-icon'">1</div>
                        Företag
                    </VTab>
                    <VTab :value="1" :class="{ 'tab-completed': currentTab > 1 }">
                        <div :class="windowWidth < 1024 ? 'd-none' : 'tab-icon'">2</div>
                        Bankuppgifter
                    </VTab>
                    <VTab :value="2" :class="{ 'tab-completed': currentTab > 2 }">
                        <div :class="windowWidth < 1024 ? 'd-none' : 'tab-icon'">3</div>
                        <span>Plan och kontakt</span>
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
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon" />
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
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
                                            <VTextField
                                                v-model="email"
                                                :rules="[requiredValidator, emailValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(25% - 18px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Startdatum*" />
                                            <AppDateTimePicker
                                                :key="JSON.stringify(datePickerConfig)"
                                                v-model="start_date"
                                                density="default"
                                                :config="datePickerConfig"
                                                :rules="[requiredValidator]"
                                                clearable
                                                class="field-solo-flat"
                                                placeholder="Startdatum"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Priset för ett sms*" />
                                            <VTextField
                                                v-bind="numericTextFieldProps"
                                                v-model="sms_price"
                                                suffix="KR"
                                                :rules="[requiredValidator, ...nonNegativeNumericRules]"
                                                @input="sms_price = normalizeNumericTextInput(sms_price)"
                                                @keydown="handlePhoneKeydown"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(25% - 18px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Slutdatum" />
                                            <AppDateTimePicker
                                                :key="JSON.stringify(datePickerConfig)"
                                                v-model="end_date"
                                                density="default"
                                                :config="datePickerConfig"
                                                class="field-solo-flat"
                                                placeholder="Slutdatum"
                                                disabled
                                            />
                                        </div>
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
                                                                <span class="title-card" style="overflow: hidden; white-space: nowrap; flex: 1;">
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

                                                                <VDivider class="border-card-line mb-1 d-none" />

                                                                <div class="d-none gap-4 mt-4 align-center justify-content-center">
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
                            {{ (currentTab === 2) ? 'Skapa' : 'Nästa' }}
                        </VBtn>
                    </div>
                </VCardText>
            </VCard>
        </VForm>

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
    .suppliers-page .radio-form.v-radio-group .v-selection-control-group .v-radio:not(:last-child) {
        margin-inline-end: 1.5rem !important;
    }

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

    .v-tabs.suppliers-tabs {
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

    .title-comments {
        font-weight: 600;
        font-size: 16px;
        line-height: 100%;
        color: #454545 !important;
        overflow: visible !important 
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
      action: create
      subject: suppliers
</route>