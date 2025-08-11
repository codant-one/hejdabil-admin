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
const reg_num = ref(null)
const agreement_id = ref(null)
const currencies = ref([])
const currency_id = ref(1)

//const tab 1
const brand_id = ref(null)
const model_id = ref(null)
const model = ref(null)
const modelsByBrand = ref([])
const year = ref(null)
const color = ref(null)
const chassis = ref(null)
const mileage = ref(null)
const sale_date = ref(null)
const guaranty = ref(0);
const guaranties = ref([
    { id: 1, name: 'Ja' },
    { id: 0, name: 'Ingen garanti' }
]);
const guaranty_description = ref(null);
const guaranty_type_id = ref(null)
const guarantyTypes = ref([])
const insurance_company = ref(0);
const insuranceCompanies = ref([
    { id: 1, name: 'Ja' },
    { id: 0, name: 'Ingen försäkring' }
]);
const insurance_company_description = ref(null);
const insurance_type_id = ref(5)                                 
const insuranceTypes = ref([])

//const tab 2
const brands = ref([])
const brand_id_interchange = ref(null)
const models = ref([])
const modelsByBrandInterchange = ref([])
const model_id_interchange = ref(null)
const model_interchange = ref(null)
const year_interchange = ref(null)
const meter_reading_interchange = ref(null)
const carbodies = ref([])
const car_body_id_interchange = ref(null)
const color_interchange = ref(null)
const reg_num_interchange = ref(null)
const chassis_interchange = ref(null)
const sale_date_interchange = ref(null)
const trade_price = ref(0)
const residual_debt = ref(0)
const optionsRadio = ['Nej', 'Ja']
const residual_price = ref(null)
const ivas = ref([])
const iva_purchase_id_interchange = ref(null)

//const tab 3
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

//Const tab 4
const price = ref(null)
const iva_id = ref(null)
const iva_sale_amount = ref(0)
const iva_sale_exclusive = ref(0)
const discount = ref(0)
const registration_fee = ref(0)
const total_sale = ref(0)
const payment_type = ref(null)
const paymentTypes = ref([])
const payment_type_id = ref(null)
const advances = ref([])
const advance_id = ref(null)
const payment_received = ref(null)
const payment_method_forcash = ref(null)
const installment_amount = ref(null)
const installment_contract_upon_delivery = ref(false)
const payment_description = ref(null)

//Const tab 5
const terms_other_conditions = ref(null)
const terms_other_information = ref(null)

const vehicle_client_id = ref(null)
const vehicle_interchange_id = ref(null)

const calculate = () => {
    const sale = Number(price.value) || 0
    const fee = Number(registration_fee.value) || 0
    const disc = Number(discount.value) || 0
    const value = (sale + fee) - disc 

    if(iva_id.value === 2)
        iva_sale_amount.value = ((Number(value) || 0) * 0.2)
    else
        iva_sale_amount.value = 0

    iva_sale_exclusive.value = value - iva_sale_amount.value
    total_sale.value = value
}

const middle_price = computed(()=>{
    return total_sale.value - trade_price.value
})

const fair_value = computed(() => {
  const price1 = Number(trade_price.value) || 0
  const price2 = Number(residual_price.value) || 0
  
  return price1 - price2
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

watch(() => discount.value, (val) => {
    calculate()
})

watch(() => registration_fee.value, (val) => {
    calculate()
})


watchEffect(fetchData)

async function fetchData() {

    if(Number(route.params.id) && route.name === 'dashboard-admin-agreements-sales-edit-id') {
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
        guarantyTypes.value = agreementsStores.guarantyTypes
        insuranceTypes.value = agreementsStores.insuranceTypes
        brands.value = agreementsStores.brands
        models.value = agreementsStores.models 
        carbodies.value = agreementsStores.carbodies
        currencies.value = agreementsStores.currencies
        ivas.value = agreementsStores.ivas
        client_types.value = agreementsStores.client_types
        identifications.value = agreementsStores.identifications
        paymentTypes.value = agreementsStores.paymentTypes
        advances.value = agreementsStores.advances

        agreement.value = await agreementsStores.showAgreement(Number(route.params.id))

        reg_num.value = agreement.value.vehicle_client.vehicle.reg_num
        agreement_id.value = agreement.value.agreement_id

        year.value = agreement.value.vehicle_client.vehicle.year
        color.value = agreement.value.vehicle_client.vehicle.color
        chassis.value = agreement.value.vehicle_client.vehicle.chassis
        mileage.value = agreement.value.vehicle_client.vehicle.mileage
        sale_date.value = agreement.value.vehicle_client.vehicle.sale_date === null ? formatDate(new Date()) : agreement.value.vehicle_client.vehicle.sale_date
        guaranty.value = agreement.value.guaranty
        guaranty_description.value = agreement.value.guaranty_description
        guaranty_type_id.value = agreement.value.guaranty_type_id
        insurance_company.value = agreement.value.insurance_company
        insurance_company_description.value = agreement.value.insurance_company_description
        insurance_type_id.value = agreement.value.insurance_type_id

        if(agreement.value.vehicle_client.vehicle.model_id !== null) {
            let modelId = agreement.value.vehicle_client.vehicle.model_id
            let brandId = models.value.filter(item => item.id === modelId)[0].brand.id
            selectBrand(brandId)
            brand_id.value = brandId
            model_id.value = agreement.value.vehicle_client.vehicle.model_id
        }

        if(agreement.value.vehicle_interchange) {
            year_interchange.value = agreement.value.vehicle_interchange.year
            meter_reading_interchange.value = agreement.value.vehicle_interchange.meter_reading
            car_body_id_interchange.value = agreement.value.vehicle_interchange.car_body_id
            color_interchange.value = agreement.value.vehicle_interchange.color
            reg_num_interchange.value = agreement.value.vehicle_interchange.reg_num
            chassis_interchange.value = agreement.value.vehicle_interchange.chassis
            sale_date_interchange.value = agreement.value.vehicle_interchange.sale_date
            trade_price.value = formatDecimal(agreement.value.vehicle_interchange.purchase_price ?? 0)
            residual_debt.value = agreement.value.residual_debt
            residual_price.value = agreement.value.residual_price ? formatDecimal(agreement.value.residual_price) : null
            iva_purchase_id_interchange.value = agreement.value.vehicle_interchange.iva_purchase_id

            if(agreement.value.vehicle_interchange.model_id !== null) {
                let modelId = agreement.value.vehicle_interchange.model_id
                let brandId = models.value.filter(item => item.id === modelId)[0].brand.id
                selectBrandInterchange(brandId)
                brand_id_interchange.value = brandId
                model_id_interchange.value = agreement.value.vehicle_interchange.model_id
            }
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
        discount.value = formatDecimal(agreement.value.discount ?? 0)
        registration_fee.value = formatDecimal(agreement.value.registration_fee ?? 0)
        total_sale.value = agreement.value.total_sale
        payment_type.value = agreement.value.payment_type
        payment_type_id.value = agreement.value.payment_type_id
        advance_id.value = agreement.value.advance_id
        payment_received.value = agreement.value.payment_received ? formatDecimal(agreement.value.payment_received) : null
        payment_method_forcash.value = agreement.value.payment_method_forcash ? formatDecimal(agreement.value.payment_method_forcash) : null
        installment_amount.value =agreement.value.installment_amount ?  formatDecimal(agreement.value.installment_amount) : null
        payment_description.value = agreement.value.payment_description

        terms_other_conditions.value = agreement.value.terms_other_conditions
        terms_other_information.value = agreement.value.terms_other_information

        vehicle_client_id.value = agreement.value.vehicle_client_id
        vehicle_interchange_id.value = agreement.value.vehicle_interchange_id

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

const selectGuaranty  = guaranty => {
    if (guaranty) {
        guaranty_type_id.value = 1
    }
}

const selectBrandInterchange = brand => {
    if (brand) {
        let _brand = brands.value.find(item => item.id === brand)
    
        model_id_interchange.value = ''
        modelsByBrandInterchange.value = models.value.filter(item => item.brand_id === _brand.id)
    }
}

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

const selectModelInterchange = selected => {

    model_interchange.value = selected !== 0 ? null : model_interchange.value

}

const onClearBrandInterchange = () => {
    modelsByBrandInterchange.value = []
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

const getModelsInterchange = computed(() => {
    const models = modelsByBrandInterchange.value.map((model) => ({
        title: model.name,
        value: model.id
    }))

    if (modelsByBrandInterchange.value.length > 0) {
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

const onChangeRadio = (newValue) => {
  residual_price.value = newValue === 1 ? 0 : residual_price.value
}

const guarantyDescriptionRules = computed(() => {
    if (guaranty.value === 1) {
        return [requiredValidator];
    }
    return [];
});

const insuranceDescriptionRules = computed(() => {
    if (insurance_company.value === 1) {
        return [requiredValidator];
    }
    return [];
});

const onSubmit = () => {
    refForm.value?.validate().then(({ valid }) => {
        if (valid && currentTab.value === 0 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (!valid && currentTab.value === 0 && refForm.value.items.length > 16 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (currentTab.value === 1 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (valid && currentTab.value === 2 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (!valid && currentTab.value === 2 && refForm.value.items.length > 44 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (valid && currentTab.value === 3 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (currentTab.value === 4) {

            let formData = new FormData()

            formData.append('id', Number(route.params.id))
            formData.append('_method', 'PUT')

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

            //vehicle
            formData.append('reg_num', reg_num.value)
            formData.append('brand_id', brand_id.value)
            formData.append('model_id', model_id.value)
            formData.append('model', model.value)
            formData.append('year', year.value)
            formData.append('color', color.value)
            formData.append('chassis', chassis.value)
            formData.append('mileage', mileage.value)
            formData.append('sale_date', sale_date.value)

            //vehicle interchange
            formData.append('interchange', reg_num_interchange.value !== null ? true : false)
            formData.append('reg_num_interchange', reg_num_interchange.value)
            formData.append('brand_id_interchange', brand_id_interchange.value)
            formData.append('model_id_interchange', model_id_interchange.value)
            formData.append('model_interchange', model_interchange.value)
            formData.append('car_body_id_interchange', car_body_id_interchange.value)
            formData.append('iva_purchase_id_interchange', iva_purchase_id_interchange.value)
            formData.append('year_interchange', year_interchange.value)
            formData.append('color_interchange', color_interchange.value)
            formData.append('purchase_price_interchange', trade_price.value)
            formData.append('purchase_date_interchange', formatDate(new Date()))
            formData.append('meter_reading_interchange', meter_reading_interchange.value)
            formData.append('chassis_interchange', chassis_interchange.value)
            formData.append('sale_date_interchange', sale_date_interchange.value)

            //agreement
            formData.append('agreement_type_id', 1)
            formData.append('currency_id', currency_id.value)
            formData.append('agreement_id', agreement_id.value)
            formData.append('guaranty_type_id', guaranty_type_id.value)
            formData.append('insurance_type_id', insurance_type_id.value)
            formData.append('fair_value', fair_value.value)
            formData.append('residual_debt', residual_debt.value)
            formData.append('residual_price', residual_price.value)
            formData.append('price', price.value)
            formData.append('iva_id', iva_id.value)
            formData.append('iva_sale_amount', iva_sale_amount.value)
            formData.append('iva_sale_exclusive', iva_sale_exclusive.value)
            formData.append('discount', discount.value)
            formData.append('registration_fee', registration_fee.value)
            formData.append('total_sale', total_sale.value)
            formData.append('payment_type', payment_type.value)
            formData.append('payment_type_id', payment_type_id.value === 0 ? null : payment_type_id.value)
            formData.append('advance_id', advance_id.value)
            formData.append('middle_price', middle_price.value)
            formData.append('payment_received', payment_received.value)
            formData.append('payment_method_forcash', payment_method_forcash.value)
            formData.append('installment_amount', installment_amount.value)
            formData.append('installment_contract_upon_delivery', installment_contract_upon_delivery.value === false ? 0 : 1)
            formData.append('guaranty', guaranty.value)
            formData.append('guaranty_description', guaranty_description.value)
            formData.append('insurance_company', insurance_company.value)
            formData.append('insurance_company_description', insurance_company_description.value)
            formData.append('payment_description', payment_description.value)
            formData.append('vehicle_client_id', vehicle_client_id.value)
            formData.append('vehicle_interchange_id', vehicle_interchange_id.value)

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
                                Försäljningsavtal
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
                                <VTab>Försäljning</VTab>
                                <VTab>Inbytesfordon</VTab>
                                <VTab>Kund</VTab>
                                <VTab>Pris</VTab>
                                <VTab>Villkor</VTab>
                            </VTabs>
                            <VCardText class="px-0 px-md-2">
                                <VWindow v-model="currentTab">
                                    <!--Försäljning-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5 mt-1">
                                            <VCol cols="12" md="3">
                                                <VTextField
                                                    v-model="reg_num"
                                                    label="Regnr"
                                                    disabled
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="9">
                                                <VTextField
                                                    v-model="agreement_id"
                                                    disabled
                                                    label="Avtalsnummer"
                                                    prefix="#"
                                                    density="compact"
                                                />
                                            </VCol>
                                        </VRow>
                                        <h6 class="text-md-h4 text-h5 font-weight-medium my-5">
                                            Fordon
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
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="sale_date"
                                                    density="compact"
                                                    label="Försäljningsdatum"
                                                    :config="startDateTimePickerConfig"
                                                    :rules="[requiredValidator]"
                                                    clearable
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="guaranty"
                                                    label="Garanti"
                                                    :items="guaranties"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    @update:modelValue="selectGuaranty"
                                                    :rules="[requiredValidator]"
                                                /> 
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="guaranty_description"
                                                    label="Garantibeskrivning"
                                                    :rules="guarantyDescriptionRules"
                                                    :disabled="guaranty === 0"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="guaranty_type_id"
                                                    :items="guarantyTypes"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    :disabled="guaranty === 1"
                                                    label="Typ av garanti"
                                                    autocomplete="off"
                                                />    
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="insurance_company"
                                                    :items="insuranceCompanies"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    :rules="[requiredValidator]"
                                                    label="Försäkringsbolag"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                />    
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="insurance_company_description"
                                                    label="Beskrivning av försäkringsbolag"
                                                    :rules="insuranceDescriptionRules"
                                                    :disabled="insurance_company === 0"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="insurance_type_id"
                                                    :items="insuranceTypes"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    label="Försäkringstyp"
                                                    autocomplete="off"
                                                />    
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>

                                    <!--Inbytesfordon-->
                                    <VWindowItem class="px-md-5">
                                        <h6 class="text-md-h4 text-h5 font-weight-medium mb-5 d-block d-md-flex mt-2">
                                            Inbytesfordon
                                            <VSpacer />
                                            <div class="d-flex w-md-50 mt-5 mt-md-0">
                                                <VTextField
                                                    v-model="reg_num_interchange"
                                                    label="Regnr"
                                                    :disabled="reg_num_interchange !== null"
                                                />
                                                <div class="px-0 d-flex align-center ms-2">
                                                    <VBtn
                                                        icon="tabler-search"
                                                        variant="tonal"
                                                        color="primary"
                                                        size="x-small"
                                                    />
                                                </div>
                                            </div>
                                        </h6>
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="brand_id_interchange"
                                                    label="Märke"
                                                    :items="brands"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                    @update:modelValue="selectBrandInterchange"
                                                    @click:clear="onClearBrandInterchange"
                                                    :menu-props="{ maxHeight: '300px' }"/> 
                                            </VCol>
                                            <VCol cols="12" :md="model_id_interchange !== 0 ? 6 : 3">
                                                <VAutocomplete
                                                    v-model="model_id_interchange"
                                                    label="Modell"
                                                    :items="getModelsInterchange"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                    @update:modelValue="selectModelInterchange"
                                                    :menu-props="{ maxHeight: '300px' }"/> 
                                            </VCol>
                                            <VCol cols="12" md="3" v-if="model_id_interchange === 0">
                                                <VTextField
                                                    v-model="model_interchange"
                                                    label="Modellens namn"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <VTextField
                                                    v-model="year_interchange"
                                                    :rules="[yearValidator]"
                                                    label="Årsmodell"
                                                />
                                            </VCol> 
                                            <VCol cols="12" md="3">
                                                <VTextField
                                                    v-model="meter_reading_interchange" 
                                                    label="Mätarställning"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <VAutocomplete
                                                    v-model="car_body_id_interchange"
                                                    label="Kaross"
                                                    :items="carbodies"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <VTextField
                                                    v-model="color_interchange"
                                                    label="Färg"
                                                />
                                            </VCol>                                        
                                            <VCol cols="12" md="3">
                                                <VTextField
                                                    v-model="chassis_interchange"
                                                    label="Chassinummer"
                                                /> 
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="sale_date_interchange"
                                                    density="compact"
                                                    :config="startDateTimePickerConfig"
                                                    label="Försäljningsdatum"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <VTextField
                                                    v-model="trade_price"
                                                    :label="'Inbytespris ' + (currencies.find(item => item.id === currency_id)?.code || '')"
                                                    type="number"
                                                    min="0"
                                                /> 
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <div class="d-flex flex-column ms-2">
                                                    <label class="v-label text-body-2 text-wrap">Restskuld</label>
                                                    <VRadioGroup 
                                                        v-model="residual_debt" 
                                                        inline 
                                                        class="radio-form"
                                                        @update:modelValue="onChangeRadio">
                                                        <VRadio
                                                            v-for="(radio, index) in optionsRadio"
                                                            :key="index"
                                                            :label="radio"
                                                            :value="index"
                                                        />
                                                    </VRadioGroup>
                                                </div>
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <VTextField
                                                    v-model="residual_price"
                                                    :label="'Restskuld ' + (currencies.find(item => item.id === currency_id)?.code || '')"
                                                    type="number"
                                                    min="0"
                                                    :disabled="residual_debt === 0 ? true : false"
                                                /> 
                                            </VCol>                                        
                                            <VCol cols="12" md="3">
                                                <VTextField
                                                    :model-value="fair_value"
                                                    :label="'Verkligt värde ' + (currencies.find(item => item.id === currency_id)?.code || '')"
                                                    disabled 
                                                />
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <VAutocomplete
                                                    v-model="iva_purchase_id_interchange"
                                                    label="VMB / Moms"
                                                    :items="ivas"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>

                                    <!--Kund-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Köpare
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
                                                            label="Namn"
                                                            :rules="[requiredValidator]"
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
                                                    Säljare
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
                                                    Specifikation, pris
                                                </h6>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="price"
                                                    label="Pris"
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
                                                    label="Moms / VMB / Export"
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
                                                <VTextField
                                                    type="number"
                                                    v-model="discount"
                                                    label="Rabatt"
                                                    min="0"
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    type="number"
                                                    v-model="registration_fee"
                                                    label="Registreringsavgift"
                                                    min="0"
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6" class="d-flex align-center">
                                                <span class="ms-1 ms-md-0">Totalpris: {{ total_sale }} {{ currencies.filter(item => item.id === currency_id)[0].code }}</span>
                                            </VCol>
                                            <VCol cols="12" md="3" class="d-flex align-center">
                                                <span class="ms-1 ms-md-0">Pris på inbytesbil: {{ trade_price }} {{ currencies.filter(item => item.id === currency_id)[0].code }}</span>
                                            </VCol>
                                            <VCol cols="12" md="3" class="d-flex align-center">
                                                <span class="ms-1 ms-md-0">Mellanpris: {{ middle_price }} {{ currencies.filter(item => item.id === currency_id)[0].code }}</span>
                                            </VCol>
                                            <VCol cols="12" :md="payment_type_id !== 0 ? 6 : 3">
                                                <VAutocomplete
                                                    v-model="payment_type_id"
                                                    label="Betalsätt"
                                                    :items="getPaymentTypes"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                    :rules="[requiredValidator]"
                                                    @update:modelValue="selectPaymentType"
                                                    @click:clear="selectPaymentType"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="3" v-if="payment_type_id === 0">
                                                <VTextField
                                                    v-model="payment_type"
                                                    label="Betalsätt"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6" class="d-none">
                                                <VAutocomplete
                                                    v-model="advance_id"
                                                    label="Handpenning procent"
                                                    :items="advances"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    :disabled="payment_type_id !== 3 && payment_type_id !== 4 && payment_type_id !== 10"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="payment_received"
                                                    label="Summa kontant / handpenning"
                                                    type="number"
                                                    min="0"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="payment_method_forcash"
                                                    label="Betalsätt för kontant / handpenning"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="installment_amount"
                                                    label="Avbetalningsbelopp (kreditbelopp/leasing)"
                                                    type="number"
                                                    min="0"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="payment_description"
                                                    label="Betalningsbeskrivning"
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
                                    {{ (currentTab === 4) ? 'Uppdatering' : ' Nästa' }}
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