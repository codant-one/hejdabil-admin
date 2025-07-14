<script setup>

import router from '@/router'
import { requiredValidator, yearValidator } from '@/@core/utils/validators'
import { useAgreementsStores } from '@/stores/useAgreements'

const agreementsStores = useAgreementsStores()

const emitter = inject("emitter")

const isRequestOngoing = ref(false)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref(0)
const isMobile = ref(false)

const userData = ref(null)
const role = ref(null)
const supplier = ref([])

const vehicles = ref([])
const vehicle_id = ref(null)
const reg_num = ref(null)
const agreement_id = ref(null)

//const tab 1
const brand = ref(null)
const model = ref(null)
const year = ref(null)
const color = ref(null)
const chassis = ref(null)
const mileage = ref(null)
const first_registration_date = ref(null)
const sale_date = ref(null)
const guaranty_id = ref(1)
const guaranties = ref([])
const guaranty_type_id = ref(null)
const guarantyTypes = ref([])
const insurance_company_id = ref(null)
const insuranceCompanies = ref([])
const insurance_type_id = ref(null)                                 
const insuranceTypes = ref([])
const insurance_agent = ref(null)

//const tab 2
const brands = ref([])
const brand_id_interchange = ref(null)
const models = ref([])
const modelsByBrand = ref([])
const model_id_interchange = ref(null)
const year_interchange = ref(null)
const meter_reading = ref(null)
const card_body = ref(null)
const color_2 = ref(null)
const regnr_2 = ref(null)
const chassis_2 = ref(null)
const first_date_2 = ref(null)
const trade_price = ref(0)
const selected_residual = ref(null)
const residual_debt = ref([ { label: 'Ja', value: 1 },{ label: 'Nej', value: 2 }])
const residual_debt_price = ref(0)
const selected_vat = ref(null)
const vat_items = ref([{title:'Moms', value: 1}, {title:'VMB', value: 2}])
const remaining_paid_to = ref(null)
const selected_ransom_offer = ref(null)
const ransom_offer_items = ref([{title:'ja', value: 1},{title: 'nej', value: 2}])

//const tab 3
const org_number=ref(null)
const selected_buyer = ref(null)
const buyer_items = ref([{title:'Konsument', value: 1}, {title:'Företag', value:2}])
const name_buyer = ref(null)
const address = ref(null)
const postal_code = ref(null)
const phone = ref(null)
const email_buyer = ref(null)
const selected_legitimation = ref(null)
const legitimation_items = ref([{title:'Pass', value: 1}, {title:'Körkort', value:2}, {title:'Mobilt bank-ID', value:3}]) 

//Const tab 4
const price_2 = ref(0)
const selected_currency = ref(null)
const currency_items = ref([{title:'SEK', value: 1}, {title: 'EUR', value: 2}, {title: 'USD', value: 3}])
const selected_taxes_type = ref(null)
const discount = ref(0)
const registration_fee = ref(0)
const mid_price = ref(0)

const which_vat = computed(()=>{
    const price_1 = Number(price_2.value)||0
    const discount_ = Number(discount.value)||0
    const registration_fee_2 = Number(registration_fee.value)|| 0
    if(selected_taxes_type.value == 1)
    {
        return ((price_1 + registration_fee_2 - discount_) *0.2) 
    }
    else 
    return 0
})

const price_without_vat = computed(()=>{
    const price_1 = Number(price_2.value)||0
    const discount_ = Number(discount.value)||0
    const registration_fee_2 = Number(registration_fee.value)|| 0
    if(selected_taxes_type.value == 1)
    {
        return price_1 + registration_fee_2 - discount_ - which_vat.value 
    }
    else
    return price_1 - discount_
})

const total_price = computed(()=>{
    const price_1 = Number(price_2.value)||0
    const discount_ = Number(discount.value)||0
    const registration_fee_2 = Number(registration_fee.value)|| 0

    return price_1 + registration_fee_2 - discount_
})

const price_whitout_trade_price = computed(()=>{
    return total_price.value - trade_price.value
})

const fair_value = computed(() => {
  const price1 = Number(trade_price.value) || 0
  const price2 = Number(residual_debt_price.value) || 0
  
  return price1 - price2
})

onMounted(async () => {
    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile)
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768
}

watchEffect(fetchData)

async function fetchData() {

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
    guaranties.value = agreementsStores.guaranties
    guarantyTypes.value = agreementsStores.guarantyTypes
    insuranceCompanies.value = agreementsStores.insuranceCompanies
    insuranceTypes.value = agreementsStores.insuranceTypes
    brands.value = agreementsStores.brands
    models.value = agreementsStores.models 

    isRequestOngoing.value = false
}

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

const selectVehicle = vehicle => {
    if (vehicle) {
        let _vehicle = vehicles.value.find(item => item.id === vehicle)
    
        reg_num.value = _vehicle.reg_num
        brand.value = _vehicle.model?.brand.name
        model.value = _vehicle.model?.name
        year.value = _vehicle.year
        color.value = _vehicle.color
        mileage.value = _vehicle.mileage
    }
}

const selectGuaranty  = guaranty => {
    if (guaranty) {
        guaranty_type_id.value = 1
    }
}

const selectBrand = brand => {
    if (brand) {
        let _brand = brands.value.find(item => item.id === brand)
    
        model_id_interchange.value = ''
        modelsByBrand.value = models.value.filter(item => item.brand_id_interchange === _brand.id)
    }
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

const selectModel = selected => {

    model.value = selected !== 0 ? null : model.value

}

const onSubmit = () => {
    refForm.value?.validate().then(({ valid }) => {
        currentTab.value++
        console.log('valid', valid)
        console.log('currentTab.value', currentTab.value)
        console.log('entra', refForm.value.items.length)
        /*if (valid && currentTab.value === 0 && refForm.value.items.length < 12) {
            currentTab.value++
        } else if ((!valid && currentTab.value === 0 && refForm.value.items.length === 10) || (!valid && currentTab.value === 0 && refForm.value.items.length === 13)) {
            currentTab.value++
        } else if (!valid && currentTab.value === 1 && refForm.value.items.length > 10) {
            currentTab.value++
        } else if (valid  && currentTab.value < 2 && refForm.value.items.length > 8) {
            currentTab.value++
        } else if (valid && currentTab.value === 2) {
            console.log('llego')
        }*/

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

                                <VBtn type="submit" class="w-100 w-md-auto">
                                    Spara
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
                            <VCardText>
                            <VWindow v-model="currentTab">
                                <!--Försäljning-->
                                <VWindowItem class="px-md-5">
                                    <VRow class="px-md-5 mt-1">
                                        <VCol cols="12" md="3">
                                            <VAutocomplete
                                                v-model="vehicle_id"
                                                label="Stockbilar"
                                                :items="vehicles"
                                                item-title="reg_num"      
                                                item-value="id"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                @update:modelValue="selectVehicle"
                                                @click:clear="reg_num = null"
                                                :rules="[requiredValidator]"
                                            />
                                        </VCol>
                                        <VCol cols="12" md="3">
                                            <VTextField
                                                v-model="reg_num"
                                                label="Regnr"
                                            />
                                        </VCol>
                                        <VCol cols="12" md="6">
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
                                            <VTextField
                                                v-model="brand"
                                                label="Märke"
                                                :rules="[requiredValidator]"
                                            />   
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <VTextField
                                                v-model="model"
                                                label="Modell"
                                                :rules="[requiredValidator]"
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
                                                :rules="[requiredValidator]"
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
                                                v-model="first_registration_date"
                                                density="compact"
                                                label="Första registreringsdatum"
                                                :config="startDateTimePickerConfig"
                                                :rules="[requiredValidator]"
                                                clearable
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
                                                v-model="guaranty_id"
                                                label="Garanti"
                                                :items="guaranties"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                @update:modelValue="selectGuaranty"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <VAutocomplete
                                                v-model="guaranty_type_id"
                                                :items="guarantyTypes"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :disabled="guaranty_id === 1"
                                                label="Typ av garanti"
                                                autocomplete="off"
                                            />    
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <VAutocomplete
                                                v-model="insurance_company_id"
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
                                                v-model="insurance_agent"
                                                :rules="[requiredValidator]"
                                                label="Ombudsnummer"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <VSelect
                                                v-model="insurance_type_id"
                                                :items="insuranceTypes"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :rules="[requiredValidator]"
                                                label="Försäkringsbolag"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />    
                                        </VCol>
                                    </VRow>
                                </VWindowItem>

                                <!--Inbytesfordon-->
                                <VWindowItem class="px-md-5">
                                    <h6 class="text-md-h4 text-h5 font-weight-medium my-5">
                                        Inbytesfordon
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
                                                @update:modelValue="selectBrand"
                                                @click:clear="onClearBrand"/> 
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <VAutocomplete
                                                v-model="model_id_interchange"
                                                label="Modell"
                                                :items="getModels"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                @update:modelValue="selectModel"/> 
                                        </VCol>
                                        <VCol cols="12" md="3">
                                            <VTextField
                                                v-model="year_interchange"
                                                :rules="[yearValidator]"
                                                label="Årsmodell"
                                            />
                                        </VCol> 
                                        <VCol cols="12" md="3">
                                            <label for="">Mätarställning</label>
                                            <VTextField
                                                v-model="meter_reading"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="3">
                                            <label for="">Kaross</label>
                                            <VTextField
                                                v-model="card_body"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="3">
                                            <label for="">Färg</label>
                                            <VTextField
                                                v-model="color_2"
                                            /> 
                                        </VCol>
                                        
                                        <VCol cols="12" md="3">
                                            <label for="">Regnr</label>
                                            <VTextField
                                                v-model="regnr_2"
                                            /> 
                                        </VCol> 
                                        <VCol cols="12" md="3">
                                            <label for="">Chassinummer</label>
                                            <VTextField
                                                v-model="chassis_2"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Försäljningsdatum</label>
                                            <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="first_date_2"
                                                    density="compact"
                                                    placeholder="YYYY-MM-DD"
                                                    :config="startDateTimePickerConfig"
                                                    clearable
                                                />
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Inbytespris kr</label>
                                            <VTextField
                                                v-model="trade_price"
                                                type="number"
                                            /> 
                                        </VCol>
                                        <VCol cols="6" md="2">
                                            <label for="">Restskuld</label>
                                            <VRadioGroup
                                                v-model="selected_residual"
                                                inline
                                                # :rules="[requiredValidator]"
                                            >
                                                <VRadio
                                                v-for="option in residual_debt"
                                                :key="option.value"
                                                :label="option.label"
                                                :value="option.value"
                                                />
                                            </VRadioGroup>
                                        </VCol>
                                        <VCol cols="6" md="2">
                                            <label for="">Restskuld kr</label>
                                            <VTextField
                                                v-model="residual_debt_price"
                                                type="number"
                                            /> 
                                        </VCol>
                                        
                                        <VCol cols="6" md="2">
                                            <label for="">Verkligt värde kr</label>
                                            <VTextField
                                                :model-value="fair_value"
                                                readonly 
                                            />
                                        </VCol>
                                        <VCol cols="6" md="4">
                                            <label for="">Moms / VMB</label>
                                            <VSelect
                                                v-model="selected_vat"
                                                :items="vat_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />

                                        </VCol>
                                        <VCol cols="6" md="4">
                                            <label for="">Restskuld betalas till</label>
                                            <VTextField
                                                v-model="remaining_paid_to"
                                            />

                                        </VCol>
                                        <VCol cols="6" md="4">
                                            <label for="">Har lösenoffert beställts</label>
                                            <VSelect
                                                v-model="selected_ransom_offer"
                                                :items="ransom_offer_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />

                                        </VCol>
                                    </VRow>
                                </VWindowItem>

                                <!--Kund-->
                                <VWindowItem class="px-md-5">

                                    <VRow class="mt-5">
                                    <VCol cols="12" md="6" class="pe-md-32">
                                            <h4>Köpare</h4>
                                            <VRow class="mt-3">
                                                <VCol cols="12" md="12">
                                                    <label for="">Org/personummer</label>
                                                    <VTextField
                                                        v-model="org_number"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">Köparen är</label>
                                                    <VSelect
                                                        v-model="selected_buyer"
                                                        :items="buyer_items"
                                                        placeholder="välj"
                                                        item-title="title"      
                                                        item-value="value"
                                                        autocomplete="off"
                                                        clearable
                                                        clear-icon="tabler-x"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">Namn</label>
                                                    <VTextField
                                                        v-model="name_buyer"
                                                        :rules="[requiredValidator]"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="12">
                                                    <label for="">Adress</label>
                                                    <VTextField
                                                        v-model="address"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">Postnr. ort</label>
                                                    <VTextField
                                                        v-model="postal_code"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">Telefon</label>
                                                    <VTextField
                                                        v-model="phone"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">E-post</label>
                                                    <VTextField
                                                        v-model="email_buyer"
                                                        type="email"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <label for="">Legitimation</label>
                                                    <VSelect
                                                        v-model="selected_legitimation"
                                                        :items="legitimation_items"
                                                        placeholder="välj"
                                                        item-title="title"      
                                                        item-value="value"
                                                        autocomplete="off"
                                                        clearable
                                                        clear-icon="tabler-x"
                                                    />
                                                </VCol>

                                            </VRow>
                                    </VCol>
                                    
                                    <VCol cols="12" md="6" class="ps-md-32">
                                        <h4>Säljare</h4>
                                    </VCol>
                                                                                
                                    </VRow>
                                    
                                </VWindowItem>

                                <!--Pris-->
                                <VWindowItem class="px-md-5">
                                    <VRow class="mt-5">
                                    <VCol cols="12" md="12">
                                            <h4>Specifikation, pris</h4>
                                    </VCol>
                                    <VCol cols="12" md="6">
                                            <label for="">Pris</label>
                                                <VTextField
                                                    v-model="price_2"
                                                    type="number"
                                                    :rules="[requiredValidator]"
                                                />
                                    </VCol>
                                    <VCol cols="12" md="6">
                                            <label for="">Valuta</label>
                                            <VSelect
                                                v-model="selected_currency"
                                                :items="currency_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                :rule="requiredValidator"
                                            />
                                    </VCol>
                                    
                                    <VCol cols="12" md="6">
                                            <label for="">Moms / VMB / Export</label>
                                            <VSelect
                                                v-model="selected_taxes_type"
                                                :items="vat_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                :rule="requiredValidator"
                                            />
                                    </VCol>
                                    <VCol cols="12" md="6">
                                            <label for="">Varav moms</label>
                                            <VTextField
                                                :model-value="which_vat"
                                                readonly 
                                            />
                                    </VCol>

                                    <VCol cols="12" md="6">
                                            <label for="">Pris ex moms</label>
                                            <VTextField
                                                :model-value="price_without_vat"
                                                readonly 
                                            />
                                    </VCol>

                                    <VCol cols="12" md="6">
                                            <label for="">Rabatt</label>
                                            <VTextField
                                                v-model="discount"
                                                type="number"
                                            />
                                    </VCol>

                                    <VCol cols="12" md="6">
                                            <label for="">Registreringsavgift</label>
                                            <VTextField
                                                v-model="registration_fee"
                                                type="number"
                                            />
                                    </VCol>

                                    <VCol cols="12" md="6">
                                            <label for="">Totalpris</label>
                                            <VTextField
                                                :model-value="total_price"
                                                type="number"
                                                readonly 
                                            />
                                    </VCol>

                                    <VCol cols="12" md="6">
                                            <label for="">Pris på inbytesbil</label>
                                            <VTextField
                                                v-model="trade_price"
                                                type="number"
                                                readonly 
                                            />
                                    </VCol>

                                    <VCol cols="12" md="6">
                                            <label for="">Mellanpris</label>
                                            <VTextField
                                                :model-value="price_whitout_trade_price"
                                                type="number"
                                                readonly 
                                            />
                                    </VCol>
                                    </VRow>
                                </VWindowItem>

                                <!--Villkor-->
                                <VWindowItem class="px-md-5">
                                    Villkor
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
                </VCol>
            </VRow>
        </VForm>
    </section>
</template>

<style scoped>
    .v-btn--disabled {
        opacity: 1 !important
    }
</style>

<route lang="yaml">
    meta:
      action: create
      subject: agreements
</route>