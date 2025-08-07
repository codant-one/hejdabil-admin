<script setup>

import router from '@/router'
import { requiredValidator, yearValidator, emailValidator, phoneValidator } from '@/@core/utils/validators'
import { useAgreementsStores } from '@/stores/useAgreements'
import { useAuthStores } from '@/stores/useAuth'
import { useAppAbility } from '@/plugins/casl/useAppAbility'

const agreementsStores = useAgreementsStores()
const authStores = useAuthStores()
const ability = useAppAbility()
const emitter = inject("emitter")
const route = useRoute()

const isRequestOngoing = ref(false)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref(0)
const isMobile = ref(false)

const userData = ref(null)
const role = ref(null)
const supplier = ref([])

const agreement = ref([])
const vehicles = ref([])
const vehicle_id = ref(null)
const reg_num = ref(null)
const agreement_id = ref(null)
const currencies = ref([])
const currency_id = ref(1)

//const tab 1
const brands = ref([])
const models = ref([])
const brand_id = ref(null)
const model_id = ref(null)
const model = ref(null)
const modelsByBrand = ref([])
const year = ref(null)
const color = ref(null)
const chassis = ref(null)
const mileage = ref(null)
const purchase_date = ref(null)
const gearbox_id = ref(null)
const fuel_id = ref(null)
const number_keys = ref(null)
const service_book = ref(0)
const summer_tire = ref(0)
const winter_tire = ref(0)     
const gearboxes = ref([])
const carbodies = ref([])
const fuels = ref([])
const optionsRadio = ['Ja', 'Nej', 'Vet ej']

//const tab 2
const client_types = ref([])
const client_type_id = ref(null)
const identifications = ref([])
const identification_id = ref(null)
const organization_number = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const fullname = ref('')
const email = ref('')

//const tab 3
const ivas = ref([])
const price = ref(null)
const iva_id = ref(null)
const iva_sale_amount = ref(0)
const iva_sale_exclusive = ref(0)
const is_loan = ref(1)
const loan_amount = ref(null)
const lessor = ref(null)
const settled_by = ref(0)
const bank = ref(null)
const account = ref(null)
const description = ref(null)
const registration_fee = ref(0)
const total_sale = ref(0)
const payment_type = ref(null)
const paymentTypes = ref([])
const payment_type_id = ref(null)
const advances = ref([])
const advance_id = ref(null)
const optionsSettled = ['Bilhandlare', 'Kund']

//Const tab 4
const terms_other_conditions = ref(null)
const terms_other_information = ref(null)

const vehicle_client_id = ref(null)

const calculate = () => {
    const sale = Number(price.value) || 0
    const fee = Number(registration_fee.value) || 0
    const value = (sale + fee)

    if(iva_id.value === 2)
        iva_sale_amount.value = ((Number(value) || 0) * 0.2)
    else
        iva_sale_amount.value = 0

    iva_sale_exclusive.value = value - iva_sale_amount.value
    total_sale.value = value
}

const remaining_amount = computed(()=>{
    return price.value - loan_amount.value
})

onMounted(async () => {
    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile)
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768
}

watch(() => price.value, (val) => {
    calculate()
})

watch(() => iva_id.value, (val) => {
    calculate()
})

watch(() => registration_fee.value, (val) => {
    calculate()
})


watchEffect(fetchData)

async function fetchData() {

    if(Number(route.params.id) && route.name === 'dashboard-admin-agreements-purchase-edit-id') {
        isRequestOngoing.value = true

        await agreementsStores.info()

        userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
        role.value = userData.value.roles[0].name

        if(role.value === 'Supplier') {
            const { user_data, userAbilities } = await authStores.me(userData.value)

            localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

            ability.update(userAbilities)

            localStorage.setItem('user_data', JSON.stringify(user_data))

            supplier.value = user_data.supplier
            agreement_id.value = supplier.value.agreements.length + 1
        } else {
            agreement_id.value = agreementsStores.agreement_id + 1
        }

        vehicles.value = agreementsStores.vehicles
        brands.value = agreementsStores.brands
        models.value = agreementsStores.models 
        carbodies.value = agreementsStores.carbodies
        gearboxes.value = agreementsStores.gearboxes
        fuels.value = agreementsStores.fuels
        currencies.value = agreementsStores.currencies
        ivas.value = agreementsStores.ivas
        client_types.value = agreementsStores.client_types
        identifications.value = agreementsStores.identifications
        paymentTypes.value = agreementsStores.paymentTypes
        advances.value = agreementsStores.advances

        agreement.value = await agreementsStores.showAgreement(Number(route.params.id))

        reg_num.value = agreement.value.vehicle_client.vehicle.reg_num
        agreement_id.value = agreement.value.agreement_id

        number_keys.value = agreement.value.vehicle_client.vehicle.number_keys
        fuel_id.value = agreement.value.vehicle_client.vehicle.fuel_id ?? fuel_id.value
        gearbox_id.value = agreement.value.vehicle_client.vehicle.gearbox_id ?? gearbox_id.value
        year.value = agreement.value.vehicle_client.vehicle.year
        color.value = agreement.value.vehicle_client.vehicle.color
        chassis.value = agreement.value.vehicle_client.vehicle.chassis
        mileage.value = agreement.value.vehicle_client.vehicle.mileage
        purchase_date.value = agreement.value.vehicle_client.vehicle.purchase_date === null ? formatDate(new Date()) : agreement.value.vehicle_client.vehicle.purchase_date

        if(agreement.value.vehicle_client.vehicle.model_id !== null) {
            let modelId = agreement.value.vehicle_client.vehicle.model_id
            let brandId = models.value.filter(item => item.id === modelId)[0].brand.id
            selectBrand(brandId)
            brand_id.value = brandId
            model_id.value = agreement.value.vehicle_client.vehicle.model_id
        }


        client_type_id.value = agreement.value.agreement_client.client_type_id
        identification_id.value = agreement.value.agreement_client.identification_id
        organization_number.value = agreement.value.agreement_client.organization_number
        address.value = agreement.value.agreement_client.address
        street.value = agreement.value.agreement_client.street
        postal_code.value = agreement.value.agreement_client.postal_code
        phone.value = agreement.value.agreement_client.phone
        fullname.value = agreement.value.agreement_client.fullname
        email.value = agreement.value.agreement_client.email

        price.value = formatDecimal(agreement.value.price)
        iva_id.value = agreement.value.iva_id
        iva_sale_amount.value = formatDecimal(agreement.value.iva_sale_amount ?? 0)
        iva_sale_exclusive.value = formatDecimal(agreement.value.iva_sale_exclusive ?? 0)
        registration_fee.value = formatDecimal(agreement.value.registration_fee ?? 0)
        total_sale.value = agreement.value.total_sale
        payment_type.value = agreement.value.payment_type
        payment_type_id.value = agreement.value.payment_type_id
        advance_id.value = agreement.value.advance_id

        is_loan.value = agreement.value.vehicle_client.vehicle.payment.is_loan
        loan_amount.value = agreement.value.vehicle_client.vehicle.payment.loan_amount
        lessor.value = agreement.value.vehicle_client.vehicle.payment.lessor
        settled_by.value = agreement.value.vehicle_client.vehicle.payment.settled_by
        bank.value = agreement.value.vehicle_client.vehicle.payment.bank
        account.value = agreement.value.vehicle_client.vehicle.payment.account
        description.value = agreement.value.vehicle_client.vehicle.payment.description

        terms_other_conditions.value = agreement.value.terms_other_conditions
        terms_other_information.value = agreement.value.terms_other_information

        vehicle_client_id.value = agreement.value.vehicle_client_id

        isRequestOngoing.value = false
    }
}

const formatDecimal = (value) => {
    const number = parseFloat(value);

    if (number % 1 !== 0) {
        return number.toFixed(2);
    }

    return number.toString();
}

const getPaymentTypes = computed(() => {
    const types = paymentTypes.value.map((model) => ({
        title: model.name,
        value: model.id
    }))

    if (paymentTypes.value.length > 0) {
        types.push({ title: 'En annan..', value: 0 })
    }

    return types
})

const startDateTimePickerConfig = computed(() => {

    const now = new Date()
    const tomorrow = new Date(now)
    tomorrow.setDate(now.getDate() + 1)

    const formatToISO = (date) => date.toISOString().split('T')[0]


    const config = {
        dateFormat: 'Y-m-d',
        position: 'auto right',
        disable: [{
            from: formatToISO(tomorrow),
            to: '2099-12-31' // Una fecha futura lejana para bloquear indefinidamente
        }]
    }


    return config
})

const selectBrand = brand => {
    if (brand) {
        let _brand = brands.value.find(item => item.id === brand)
    
        model_id.value = ''
        modelsByBrand.value = models.value.filter(item => item.brand_id === _brand.id)
    }
}

const selectModel = selected => {

    model.value = selected !== 0 ? null : model.value

}

const onClearBrand = () => {
    modelsByBrand.value = []
}

const getModels = computed(() => {
    const models = modelsByBrand.value.map((model) => ({
        title: model.name,
        value: model.id
    }))

    if (modelsByBrand.value.length > 0) {
        models.push({ title: 'En annan..', value: 0 })
    }

    return models
})

const getFlag = (currency_id) => {
    return currencies.value.filter(item => item.id === currency_id)[0].flag
}

const selectPaymentType = () => {
    advance_id.value = null
}

const formatDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0') // meses de 0 a 11
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const onSubmit = () => {
    refForm.value?.validate().then(({ valid }) => {
        if (valid && currentTab.value === 0 && refForm.value.items.length < 42) {
            currentTab.value++
        } else if (!valid && currentTab.value === 0 && refForm.value.items.length > 16 && refForm.value.items.length < 42) {
            currentTab.value++
        } else if (valid && currentTab.value === 1 && refForm.value.items.length < 42) {
            currentTab.value++
        }  else if (!valid && currentTab.value === 1 && refForm.value.items.length > 26 && refForm.value.items.length < 42) {
            currentTab.value++
        } else if (valid && currentTab.value === 2 && refForm.value.items.length < 46) {
            currentTab.value++
        } else if (!valid && currentTab.value === 2 && refForm.value.items.length > 42 && refForm.value.items.length < 46) {
            currentTab.value++
        } else if (currentTab.value === 3) {

            let formData = new FormData()

            formData.append('id', Number(route.params.id))
            formData.append('_method', 'PUT')

            //vehicle
            formData.append('reg_num', reg_num.value)
            formData.append('brand_id', brand_id.value)
            formData.append('model_id', model_id.value)
            formData.append('model', model.value)
            formData.append('year', year.value)
            formData.append('color', color.value)
            formData.append('chassis', chassis.value)
            formData.append('mileage', mileage.value)
            formData.append('purchase_date', purchase_date.value)
            formData.append('vehicle_id', vehicle_id.value)
            formData.append('purchase_price', price.value)
            formData.append('iva_purchase_id', iva_id.value)
            formData.append('gearbox_id', gearbox_id.value)
            formData.append('number_keys', number_keys.value)
            formData.append('service_book', service_book.value)
            formData.append('summer_tire', summer_tire.value)
            formData.append('winter_tire', winter_tire.value)
            formData.append('fuel_id', fuel_id.value)

            //vehicle payment
            formData.append('is_loan', is_loan.value)
            formData.append('loan_amount', loan_amount.value)
            formData.append('lessor', lessor.value)
            formData.append('remaining_amount', remaining_amount.value)
            formData.append('settled_by', settled_by.value)
            formData.append('bank', bank.value)
            formData.append('account', account.value)
            formData.append('description', description.value)

            //client
            formData.append('client_type_id', client_type_id.value)
            formData.append('identification_id', identification_id.value)
            formData.append('fullname', fullname.value)
            formData.append('email', email.value)
            formData.append('organization_number', organization_number.value)
            formData.append('address', address.value)
            formData.append('street', street.value)
            formData.append('postal_code', postal_code.value)
            formData.append('phone', phone.value)

            //agreement
            formData.append('agreement_type_id', 2)
            formData.append('currency_id', currency_id.value)
            formData.append('agreement_id', agreement_id.value)
            formData.append('price', price.value)
            formData.append('residual_debt', 0)
            formData.append('guaranty', 0)
            formData.append('insurance_company', 0)
            formData.append('iva_id', iva_id.value)
            formData.append('iva_sale_amount', iva_sale_amount.value)
            formData.append('iva_sale_exclusive', iva_sale_exclusive.value)
            formData.append('registration_fee', registration_fee.value)
            formData.append('total_sale', total_sale.value)
            formData.append('payment_type', payment_type.value)
            formData.append('payment_type_id', payment_type_id.value === 0 ? null : payment_type_id.value)
            formData.append('advance_id', advance_id.value)
            formData.append('vehicle_client_id', vehicle_client_id.value)

            formData.append('terms_other_conditions', terms_other_conditions.value)
            formData.append('terms_other_information', terms_other_information.value)
            
            isRequestOngoing.value = true

            let data = {
                data: formData, 
                id: Number(route.params.id)
            }

            agreementsStores.updateAgreement(data)
                .then((res) => {
                    if (res.data.success) {
                        
                        let data = {
                            message: 'Kontrakt framgångsrikt skapat',
                            error: false
                        }

                        router.push({ name : 'dashboard-admin-agreements'})
                        emitter.emit('toast', data)
                    }
                    isRequestOngoing.value = false
                })
                .catch((err) => {
                    
                    let data = {
                        message: err.message,
                        error: true
                    }

                    router.push({ name : 'dashboard-admin-agreements'})
                    emitter.emit('toast', data)

                    isRequestOngoing.value = false
                })
        }

    })
}

</script>

<template>
    <section>
        <VDialog
            v-model="isRequestOngoing"
            width="auto"
            persistent>
            <VProgressCircular
            indeterminate
            color="primary"
            class="mb-0"/>
        </VDialog>

        <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit">
            <VRow>
                <VCol cols="12" md="12" class="py-0">
                    <div class="d-flex flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
                        <div class="d-flex flex-column justify-center">
                            <h6 class="text-md-h4 text-h6 font-weight-medium">
                                Inköpsavtal
                            </h6>
                        </div>
                        <VSpacer />
                            <div class="d-flex flex-column flex-md-row gap-1 gap-md-4 w-100 w-md-auto">
                                <VBtn
                                    variant="tonal"
                                    color="secondary"
                                    class="mb-2 w-100 w-md-auto"
                                    :to="{ name: 'dashboard-admin-agreements' }">
                                    Tillbaka
                                </VBtn>
                            </div>
                    </div>
                </VCol>
                <VCol cols="12" md="12">              
                    <VCard flat class="px-2 px-md-12">
                        <VCardText class="px-2 pt-0 pt-md-5">               
                            <VTabs v-model="currentTab" grow disabled>
                                <VTab>Inköpsavtal - Reg nr</VTab>
                                <VTab>Kund</VTab>
                                <VTab>Pris</VTab>
                                <VTab>Villkor</VTab>
                            </VTabs>
                            <VCardText class="px-0 px-md-2">
                                <VWindow v-model="currentTab">
                                    <!--Inköpsavtal - Reg nr-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5 mt-1">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="agreement_id"
                                                    disabled
                                                    label="Avtalsnummer"
                                                    prefix="#"
                                                    density="compact"
                                                />
                                            </VCol>
                                            <VCol cols="10" md="5">
                                                <VTextField
                                                    v-model="reg_num"
                                                    label="Regnr"
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="2" md="1" class="px-0 d-flex align-center">
                                                <VBtn
                                                    icon="tabler-search"
                                                    variant="tonal"
                                                    color="primary"
                                                    size="x-small"
                                                />
                                            </VCol>
                                        </VRow>
                                        <h6 class="text-md-h4 text-h5 font-weight-medium my-5">
                                            Fordonsinformation
                                        </h6>
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="brand_id"
                                                    label="Märke"
                                                    :items="brands"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                    :rules="[requiredValidator]"
                                                    @update:modelValue="selectBrand"
                                                    @click:clear="onClearBrand"
                                                    :menu-props="{ maxHeight: '300px' }"/>
                                            </VCol>
                                            <VCol cols="12" :md="model_id !== 0 ? 6 : 3">
                                                <VAutocomplete
                                                    v-model="model_id"
                                                    label="Modell"
                                                    :items="getModels"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                    :rules="[requiredValidator]"
                                                    @update:modelValue="selectModel"
                                                    :menu-props="{ maxHeight: '300px' }"/> 
                                            </VCol>
                                            <VCol cols="12" md="3" v-if="model_id === 0">
                                                <VTextField
                                                    v-model="model"
                                                    label="Modellens namn"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="year"
                                                    label="Årsmodell"
                                                    :rules="[requiredValidator, yearValidator]"
                                                />   
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="color"
                                                    label="Färg"
                                                    :rules="[requiredValidator]"
                                                /> 
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="chassis"
                                                    label="Chassinummer"
                                                /> 
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    type="number"
                                                    v-model="mileage"
                                                    suffix="Mil"
                                                    label="Miltal"
                                                    min="0"
                                                    :rules="[requiredValidator]"
                                                /> 
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="fuel_id"
                                                    label="Drivmedel"
                                                    :items="fuels"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="gearbox_id"
                                                    label="Växellåda"
                                                    :items="gearboxes"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="number_keys"
                                                    type="number"
                                                    label="Antal nycklar"
                                                    min="1"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="2">
                                                <div class="d-flex flex-column">
                                                    <label class="v-label text-body-2 text-wrap"> Servicebok finns?</label>
                                                    <VRadioGroup v-model="service_book" inline class="radio-form">
                                                        <VRadio
                                                            v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                            :key="index"
                                                            :label="radio"
                                                            :value="index"
                                                        />
                                                    </VRadioGroup>
                                                </div>
                                            </VCol>
                                            <VCol cols="12" md="2">                                                
                                                <div class="d-flex flex-column">
                                                    <label class="v-label text-body-2 text-wrap">Sommardäck finns?</label>
                                                    <VRadioGroup v-model="summer_tire" inline class="radio-form">
                                                        <VRadio
                                                            v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                            :key="index"
                                                            :label="radio"
                                                            :value="index"
                                                        />
                                                    </VRadioGroup>
                                                </div>
                                            </VCol>
                                            <VCol cols="12" md="2">                                                
                                                <div class="d-flex flex-column">
                                                    <label class="v-label text-body-2 text-wrap">Vinterdäck finns?</label>
                                                    <VRadioGroup v-model="winter_tire" inline class="radio-form">
                                                        <VRadio
                                                            v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                            :key="index"
                                                            :label="radio"
                                                            :value="index"
                                                        />
                                                    </VRadioGroup>
                                                </div>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="purchase_date"
                                                    density="compact"
                                                    label="Inköpsdatum"
                                                    :config="startDateTimePickerConfig"
                                                    :rules="[requiredValidator]"
                                                    clearable
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>

                                    <!--Kund-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Säljare
                                                </h6>
                                                <VRow>
                                                    <VCol cols="10" md="11">
                                                        <VTextField
                                                            v-model="organization_number"
                                                            label="Org/personummer"
                                                            :rules="[requiredValidator]"
                                                        />
                                                    </VCol>
                                                    <VCol cols="2" md="1" class="px-0 d-flex align-center">
                                                        <VBtn
                                                            icon="tabler-search"
                                                            variant="tonal"
                                                            color="primary"
                                                            size="x-small"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VAutocomplete
                                                            v-model="client_type_id"
                                                            label="Köparen är"
                                                            :items="client_types"
                                                            :item-title="item => item.name"
                                                            :item-value="item => item.id"
                                                            :rules="[requiredValidator]"
                                                            autocomplete="off"/>
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="fullname"
                                                            :rules="[requiredValidator]"
                                                            label="Namn"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="12">
                                                        <VTextField
                                                            v-model="address"
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
                                                            :rules="[requiredValidator, phoneValidator]"
                                                            label="Telefon"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VAutocomplete
                                                            v-model="identification_id"
                                                            label="Legitimation"
                                                            :items="identifications"
                                                            :item-title="item => item.name"
                                                            :item-value="item => item.id"
                                                            :rules="[requiredValidator]"
                                                            autocomplete="off"/>
                                                    </VCol>
                                                    <VCol cols="12" md="12">
                                                        <VTextField
                                                            v-model="email"
                                                            :rules="[emailValidator, requiredValidator]"
                                                            label="E-post"
                                                        />
                                                    </VCol>
                                                </VRow>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Köpare
                                                </h6>
                                                <VList class="card-list mt-2">
                                                    <VListItem>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Namn:
                                                                <span class="text-body-2">
                                                                    {{ userData.name }} {{ userData.last_name }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Org/personummer:
                                                                <span class="text-body-2">
                                                                    {{ role === 'Supplier' ? supplier.organization_number : userData.user_details.organization_number }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Adress:
                                                                <span class="text-body-2">
                                                                    {{ role === 'Supplier' ? supplier.address : userData.user_details.address }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Postnr. ort:
                                                                <span class="text-body-2">
                                                                    {{ 
                                                                        role === 'Supplier' ? 
                                                                        supplier.street + ' ' +  supplier.postal_code : 
                                                                        userData.user_details.street  + ' ' +  userData.user_details.postal_code
                                                                    }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Telefon:
                                                                <span class="text-body-2">
                                                                    {{ role === 'Supplier' ? supplier.phone : userData.user_details.phone }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                E-post
                                                                <span class="text-body-2">
                                                                    {{ userData.email }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Bilfirma:
                                                                <span class="text-body-2">
                                                                    {{ role === 'Supplier' ? supplier.company : userData.user_details.company }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                    </VListItem>
                                                </VList>
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>

                                    <!--Pris-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium">
                                                    Fordonsinformation
                                                </h6>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="price"
                                                    label="Inköpspris"
                                                    type="number"
                                                    min="0"
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="currency_id"
                                                    label="Valuta"
                                                    :items="currencies"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    disabled
                                                    clear-icon="tabler-x">
                                                    <template
                                                        v-if="currency_id"
                                                        #prepend
                                                        >
                                                            <VAvatar
                                                            start
                                                            style="margin-top: -8px;"
                                                            size="36"
                                                            :image="getFlag(currency_id)"
                                                        />
                                                    </template>
                                                </VAutocomplete>
                                            </VCol>                                        
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="iva_id"
                                                    label="Moms / VMB"
                                                    :items="ivas"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                    :rules="[requiredValidator]"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    type="number"
                                                    v-model="iva_sale_amount"
                                                    label="Varav moms"
                                                    min="0"
                                                    disabled
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    type="number"
                                                    v-model="iva_sale_exclusive"
                                                    label="Prix ex moms"
                                                    min="0"
                                                    disabled
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <div class="d-flex flex-column">
                                                    <label class="v-label text-body-2 text-wrap"> Har bilen Kredit/leasing?</label>
                                                    <VRadioGroup v-model="is_loan" inline class="radio-form">
                                                        <VRadio
                                                            v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                            :key="index"
                                                            :label="radio"
                                                            :value="index"
                                                        />
                                                    </VRadioGroup>
                                                </div>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="loan_amount"
                                                    label="Kreditbelopp"
                                                    type="number"
                                                    min="0"
                                                    :disabled="is_loan === 1 ? true : false"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="lessor"
                                                    label="Kredit/leasinggivare"
                                                    :disabled="is_loan === 1 ? true : false"
                                                />
                                            </VCol>           
                                            <VCol cols="12" md="6" class="d-flex align-center">
                                                <span class="ms-1 ms-md-0">Restsumma: {{ remaining_amount }} {{ currencies.filter(item => item.id === currency_id)[0].code }}</span>
                                            </VCol>                                 

                                            <VCol cols="12" md="6">
                                                <div class="d-flex flex-column">
                                                    <label class="v-label text-body-2 text-wrap">Restskulden löses av</label>
                                                    <VRadioGroup v-model="settled_by" inline class="radio-form">
                                                        <VRadio
                                                            v-for="(radio, index) in optionsSettled"
                                                            :key="index"
                                                            :label="radio"
                                                            :value="index"
                                                        />
                                                    </VRadioGroup>
                                                </div>
                                            </VCol>
                                            <VCol cols="12" :md="payment_type_id !== 0 ? 6 : 3">
                                                <VAutocomplete
                                                    v-model="payment_type_id"
                                                    label="Typ av utbetalning till säljaren"
                                                    :items="getPaymentTypes"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                    :rules="[requiredValidator]"
                                                    @update:modelValue="selectPaymentType"
                                                    @click:clear="selectPaymentType"
                                                    :disabled="settled_by === 0 ? true : false"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="3" v-if="payment_type_id === 0">
                                                <VTextField
                                                    v-model="payment_type"
                                                    label="Betalsätt"
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="bank"
                                                    label="Namn på banken"
                                                    :rules="[requiredValidator]"
                                                    :disabled="settled_by === 0 ? true : false"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="account"
                                                    label="Clearing/kontonummer"
                                                    :rules="[requiredValidator]"
                                                    :disabled="settled_by === 0 ? true : false"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="description"
                                                    label="Betalningsbeskrivning"
                                                    :rules="[requiredValidator]"
                                                    :disabled="settled_by === 0 ? true : false"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>

                                    <!--Villkor-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium">
                                                    Villkor
                                                </h6>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextarea
                                                    v-model="terms_other_conditions"
                                                    label="Övriga villkor inhämtas från mall"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextarea
                                                    v-model="terms_other_information"
                                                    label="Övriga upplysningar"
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
                                    {{ (currentTab === 4) ? 'Skicka' : ' Nästa' }}
                                </VBtn>
                            </div>
                        </VCol>
                    </VRow>
                </VCol>
            </VRow>
        </VForm>
    </section>
</template>

<style scoped>
    :deep(.radio-form .v-input--density-comfortable), :deep(.v-radio) {
        --v-input-control-height: 0 !important;
    }

    :deep(.radio-form .v-selection-control__wrapper) {
        height: 20px !important;
    }

    :deep(.radio-form .v-icon--size-default) {
        font-size: calc(var(--v-icon-size-multiplier) * 1em) !important;
    }
    
    .v-btn--disabled {
        opacity: 1 !important
    }

    .justify-content-end {
        justify-content: end !important;
    }
</style>

<route lang="yaml">
    meta:
      action: edit
      subject: agreements
</route>