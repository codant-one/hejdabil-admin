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
const guaranty_id = ref(1)
const guaranties = ref([])
const guaranty_type_id = ref(null)
const guarantyTypes = ref([])
const insurance_company_id = ref(null)
const insuranceCompanies = ref([])
const insurance_type_id = ref(5)                                 
const insuranceTypes = ref([])

const gearbox_id = ref(null)
const fuel_id = ref(null)
const number_keys = ref(null)
const service_book = ref(0)
const summer_tire = ref(0)
const winter_tire = ref(0)     

//const tab 2
const brands = ref([])
const models = ref([])
const gearboxes = ref([])
const carbodies = ref([])
const fuels = ref([])
const trade_price = ref(0)
const residual_debt = ref(0)
const optionsRadio = ['Ja', 'Nej']
const residual_price = ref(null)
const ivas = ref([])

//const tab 3
const clients = ref([])
const client_id = ref(null)
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
const save_client = ref(true)
const disabled_client = ref(false)

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
const test = ref(null)
const terms_other_conditions = ref(null)
const terms_other_information = ref(`Förmedlaren har rätt att sälja fordonet och ansvarar för visning, marknadsföring, kontrakt, betalning, leverans samt hantering av reklamationer. Fordonsägaren ansvarar för att bilen är försäkrad under förmedlingsperioden och att tillhörigheter som nycklar och servicehistorik lämnas in.

Förmedlaren har rätt till provision enligt avtalet. Vid garanterad utbetalning har förmedlaren rätt att sätta priset. Eventuella rekond- eller reparationskostnader debiteras om inte annat avtalats.

Fordonsägaren intygar att bilen är fri från skulder och dolda fel.`)

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
    carbodies.value = agreementsStores.carbodies
    gearboxes.value = agreementsStores.gearboxes
    fuels.value = agreementsStores.fuels
    currencies.value = agreementsStores.currencies
    ivas.value = agreementsStores.ivas
    clients.value = agreementsStores.clients
    client_types.value = agreementsStores.client_types
    identifications.value = agreementsStores.identifications
    paymentTypes.value = agreementsStores.paymentTypes
    advances.value = agreementsStores.advances

    isRequestOngoing.value = false
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

const onClearBrand = () => {
    modelsByBrand.value = []
}

const getModels = computed(() => {
    const models = modelsByBrand.value.map((model) => ({
        title: model.name,
        value: model.id
    }))

    return models
})

const clearClient = () => {
    fullname.value = null
    email.value = null
    organization_number.value = null
    address.value = null
    street.value = null
    postal_code.value = null
    phone.value = null

    save_client.value = true
    disabled_client.value = false
}


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

const selectModel = selected => {

model.value = selected !== 0 ? null : model.value

}

const onSubmit = () => {
    refForm.value?.validate().then(({ valid }) => {
        
            currentTab.value++
       

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
                                Förmedlingsavtal
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
                                <VTab>Kund</VTab>
                                <VTab>Fordonsinformation</VTab>
                                <VTab>Förmedlingsavgift</VTab>
                                <VTab>Betalningsinformation</VTab>
                                <VTab>Förmedlingsdatum</VTab>
                                <VTab>Tillägg</VTab>
                            </VTabs>
                            <VCardText class="px-0 px-md-2">
                                <VWindow v-model="currentTab">
                                    <!--Kund-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Fordonsägare
                                                </h6>
                                                <VRow>
                                                    <VCol cols="10" md="11">
                                                        <VTextField
                                                            v-model="organization_number"
                                                            label="Org/personummer"
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
                                                            autocomplete="off"/>
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="fullname"
                                                            label="Namn"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="12">
                                                        <VTextField
                                                            v-model="address"
                                                            label="Adress"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="postal_code"
                                                            label="Postnummer"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="street"
                                                            label="Stad"
                                                        /> 
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="phone"
                                                            :rules="[phoneValidator]"
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
                                                            autocomplete="off"/>
                                                    </VCol>
                                                    <VCol cols="12" md="12">
                                                        <VTextField
                                                            v-model="email"
                                                            :rules="[emailValidator]"
                                                            label="E-post"
                                                        />
                                                    </VCol>
                                                </VRow>
                                            </VCol>                                        
                                            <VCol cols="12" md="6" v-if="userData">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Förmedlare
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

                                    <!--Fordonsinformation-->
                                    <VWindowItem class="px-md-5">
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
                                                    @click:clear="onClearBrand"/>
                                            </VCol>
                                            <VCol cols="12" :md="model_id !== 0 ? 6 : 3">
                                                <VAutocomplete
                                                    v-model="model_id"
                                                    label="Modell"
                                                    :items="getModels"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                    @update:modelValue="selectModel"/> 
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
                                                    :rules="[yearValidator]"
                                                />   
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="color"
                                                    label="Färg"
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
                                            <VCol cols="12" md="12">
                                                <VTextarea
                                                    v-model="test"
                                                    label="Kända fel, brister och övrig information"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>

                                    <!--Förmedlingsavgift-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium">
                                                    Förmedlingsavgift
                                                </h6>
                                            </VCol>
                                            
                                        </VRow>
                                    </VWindowItem>

                                    <!--Betalningsinformation-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium">
                                                    Betalningsinformation
                                                </h6>
                                            </VCol>
                                            
                                        </VRow>
                                    </VWindowItem>

                                      <!--Förmedlingsdatum-->
                                      <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium">
                                                    Förmedlingsdatum
                                                </h6>
                                            </VCol>
                                            
                                        </VRow>
                                    </VWindowItem>

                                    <!--Tillägg-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium">
                                                    Tillägg
                                                </h6>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextarea
                                                    v-model="terms_other_conditions"
                                                    label="Övriga upplysningar"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextarea
                                                    v-model="terms_other_information"
                                                    label="Övriga villkor"
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
</template>a

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
      action: create
      subject: agreements
</route>