<script setup>

import router from '@/router'
import { requiredValidator, yearValidator, emailValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useAgreementsStores } from '@/stores/useAgreements'
import { useCarInfoStores } from '@/stores/useCarInfo'
import { useAuthStores } from '@/stores/useAuth'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useConfigsStores } from '@/stores/useConfigs'
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { useToastsStores } from '@/stores/useToasts'

const agreementsStores = useAgreementsStores()
const authStores = useAuthStores()
const carInfoStores = useCarInfoStores()
const configsStores = useConfigsStores()
const companyInfoStores = useCompanyInfoStores()
const toastsStores = useToastsStores()
const ability = useAppAbility()
const emitter = inject("emitter")

const isRequestOngoing = ref(false)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref(0)
const isMobile = ref(false)

const userData = ref(null)
const role = ref(null)
const company = ref([])

const currencies = ref([])
const commission_id = ref(null)
const currency_id = ref(1)

//const tab 1
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

//const tab 2
const brands = ref([])
const models = ref([])
const gearboxes = ref([])
const carbodies = ref([])
const fuels = ref([])
const reg_num = ref(null)
const brand_id = ref(null)
const model_id = ref(null)
const model = ref(null)
const modelsByBrand = ref([])
const year = ref(null)
const color = ref(null)
const chassis = ref(null)
const mileage = ref(null)      
const gearbox_id = ref(null)
const fuel_id = ref(null)
const number_keys = ref(null)
const service_book = ref(0)
const summer_tire = ref(0)
const winter_tire = ref(0)
const comments = ref(null)

//const tab 3
const commission_types = ref([])
const commission_type_id = ref(null)
const commission_fee = ref(null)
const outstanding_debt = ref(1)
const remaining_debt = ref(null)
const paid_bank = ref(null)
const selling_price = ref(null)
const residual_debt = ref(0)
const optionsSettled = ['Bilhandlare', 'Kund']
const optionsRadio = ['Ja', 'Nej']

// const tab 4
const bank_name = ref(null) 
const payment_days = ref(1) 
const account_number = ref(null)
const payment_description = ref(null)

//Const tab 5 - Förmedlingsdatum
const start_date = ref(null)
const end_date = ref(null)
const endDateTimePickerConfig = computed(() => {
    return {
        dateFormat: 'Y-m-d',
        position: 'auto right',
        minDate: start_date.value 
    }
})

//Const tab 6
const terms_other_conditions = ref(null)
const terms_other_information = ref(`Förmedlaren har rätt att sälja fordonet och ansvarar för visning, marknadsföring, kontrakt, betalning, leverans samt hantering av reklamationer. Fordonsägaren ansvarar för att bilen är försäkrad under förmedlingsperioden och att tillhörigheter som nycklar och servicehistorik lämnas in.

Förmedlaren har rätt till provision enligt avtalet. Vid garanterad utbetalning har förmedlaren rätt att sätta priset. Eventuella rekond- eller reparationskostnader debiteras om inte annat avtalats.

Fordonsägaren intygar att bilen är fri från skulder och dolda fel.`)

watch(start_date, (newStartDate) => {
    if (end_date.value && newStartDate > end_date.value) {
        end_date.value = null; 
    }
})

const formatDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0') // meses de 0 a 11
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

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

    const { user_data, userAbilities } = await authStores.me(userData.value)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

    ability.update(userAbilities)

    localStorage.setItem('user_data', JSON.stringify(user_data))

    if(role.value === 'Supplier') {
        company.value = user_data.user_detail
        company.value.email = user_data.email
        company.value.name = user_data.name
        company.value.last_name = user_data.last_name
        commission_id.value = user_data.supplier.user.commissions.length + 1
    } else if(role.value === 'User') {
        company.value = user_data.supplier.boss.user.user_detail
        company.value.email = user_data.supplier.boss.user.email
        company.value.name = user_data.supplier.boss.user.name
        company.value.last_name = user_data.supplier.boss.user.last_name
        commission_id.value = user_data.supplier.boss.user.commissions.length + 1
    } else {
        await configsStores.getFeature('company')
        await configsStores.getFeature('logo')

        company.value = configsStores.getFeaturedConfig('company')
        company.value.logo = configsStores.getFeaturedConfig('logo').logo

        commission_id.value = agreementsStores.commission_id + 1
    }

    brands.value = agreementsStores.brands
    models.value = agreementsStores.models 
    carbodies.value = agreementsStores.carbodies
    gearboxes.value = agreementsStores.gearboxes
    fuels.value = agreementsStores.fuels
    currencies.value = agreementsStores.currencies
    client_types.value = agreementsStores.client_types
    identifications.value = agreementsStores.identifications
    commission_types.value = agreementsStores.commission_types

    start_date.value = formatDate(new Date())
    isRequestOngoing.value = false
}

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

    if (modelsByBrand.value.length > 0) {
        models.push({ title: 'En annan..', value: 0 })
    }

    return models
})

const selectModel = selected => {

    model.value = selected !== 0 ? null : model.value

}

const formatOrgNumber = () => {

    let numbers = organization_number.value.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    organization_number.value = numbers
}

const searchCompany = async () => {
    if (!organization_number.value) return

    try {
        const response = await companyInfoStores.getCompanyInfo(organization_number.value)
        
        if (response) {
             // Set Client Type to Företag
            const foretagType = client_types.value.find(t => t.name === 'Företag')
            if (foretagType) {
                client_type_id.value = foretagType.id
            }

            // Set Name
            if (response.organisationsnamn?.organisationsnamnLista?.[0]?.namn) {
                fullname.value = response.organisationsnamn.organisationsnamnLista[0].namn
            } else {
                fullname.value = ''
            }

            // Set Postal Code
            if (response.postadressOrganisation?.postadress?.postnummer) {
                postal_code.value = response.postadressOrganisation.postadress.postnummer
            } else {
                postal_code.value = ''
            }
        }

    } catch (error) {
        toastsStores.addToast({
            message: 'Ingen företag hittades med det registreringsnumret',
            type: 'error'
        })
    }
}

const searchVehicule = async () => {

    isRequestOngoing.value = true

    const carRes = await carInfoStores.getLicensePlate(reg_num.value)

    isRequestOngoing.value = false

    if (carRes.success) {
        chassis.value = carRes.result.chassis
        year.value = carRes.result.model_year
    }
}

const onSubmit = () => {
  const tab = currentTab.value
  const itemsLength = refForm.value.items.length

  refForm.value?.validate().then(({ valid }) => {
    if (tab === 0) {
        if (valid || (!valid && itemsLength > 16 && itemsLength < 60)) {
            currentTab.value++
        }
    } else if (tab === 1) {
        if (valid || (!valid && itemsLength > 24 && itemsLength < 60)) {
            currentTab.value++
        }
    } else if (tab === 2) {
        if (valid || (!valid && itemsLength > 28 && itemsLength < 60)) {
            currentTab.value++
        }
    } else if (tab === 3) {
        if (valid || (!valid && itemsLength > 33 && itemsLength < 60)) {
            currentTab.value++
        }
    } else if (tab === 4) {
        if (valid || (!valid && itemsLength > 37 && itemsLength < 60)) {
            currentTab.value++
        }
    } else if (tab === 5) {
        let formData = new FormData()

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
        formData.append('gearbox_id', gearbox_id.value)
        formData.append('fuel_id', fuel_id.value)
        formData.append('number_keys', number_keys.value)
        formData.append('service_book', service_book.value)
        formData.append('summer_tire', summer_tire.value)
        formData.append('winter_tire', winter_tire.value)
        formData.append('comments', comments.value)

        // commission
        formData.append('commission_type_id', commission_type_id.value)
        formData.append('commissionId', commission_id.value)
        formData.append('commission_fee', commission_fee.value)
        formData.append('start_date', start_date.value)
        formData.append('end_date', end_date.value)
        formData.append('outstanding_debt', outstanding_debt.value)
        formData.append('remaining_debt', remaining_debt.value)
        formData.append('residual_debt', residual_debt.value)
        formData.append('paid_bank', paid_bank.value)
        formData.append('selling_price', selling_price.value)
        formData.append('payment_days', payment_days.value)
        formData.append('payment_description', payment_description.value)

        //agreement
        formData.append('agreement_type_id', 3)
        formData.append('currency_id', currency_id.value)
        formData.append('price', selling_price.value)
        formData.append('residual_debt', 0)
        formData.append('guaranty', 0)
        formData.append('insurance_company', 0)
        formData.append('terms_other_conditions', terms_other_conditions.value)
        formData.append('terms_other_information', terms_other_information.value)

        isRequestOngoing.value = true

        agreementsStores.addAgreement(formData)
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
                                        <VRow class="px-md-5 mt-2">
                                            <VCol cols="12" md="6" class="d-none d-md-block"></VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="commission_id"
                                                    disabled
                                                    label="Avtalsnummer"
                                                    prefix="#"
                                                    density="compact"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Fordonsägare
                                                </h6>
                                                <VRow>
                                                    <VCol cols="10" md="11">
                                                        <VTextField
                                                            v-model="organization_number"
                                                            label="Org/personummer"
                                                            :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                                                            minLength="11"
                                                            maxlength="13"
                                                            @input="formatOrgNumber()"
                                                        />
                                                    </VCol>
                                                    <VCol cols="2" md="1" class="px-0 d-flex align-start">
                                                        <VBtn
                                                            icon="tabler-search"
                                                            variant="tonal"
                                                            color="primary"
                                                            size="x-small"
                                                            class="mt-1"
                                                            @click="searchCompany"
                                                            :loading="companyInfoStores.loading"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VAutocomplete
                                                            v-model="client_type_id"
                                                            label="Fordonsägaren är:"
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
                                                            label="Adress"
                                                            :rules="[requiredValidator]"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="postal_code"
                                                            label="Postnummer"
                                                            :rules="[requiredValidator]"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="street"
                                                            label="Stad"
                                                            :rules="[requiredValidator]"
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
                                                            :rules="[requiredValidator]"
                                                            autocomplete="off"/>
                                                    </VCol>
                                                    <VCol cols="12" md="12">
                                                        <VTextField
                                                            v-model="email"
                                                            :rules="[emailValidator,requiredValidator]"
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
                                                                    {{ company.name }} {{ company.last_name }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Org/personummer:
                                                                <span class="text-body-2">
                                                                    {{ company.organization_number }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Adress:
                                                                <span class="text-body-2">
                                                                    {{ company.address }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Postnr. ort:
                                                                <span class="text-body-2">
                                                                    {{ company.street + ' ' +  company.postal_code }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Telefon:
                                                                <span class="text-body-2">
                                                                    {{ company.phone }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                E-post
                                                                <span class="text-body-2">
                                                                    {{ company.email }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Bilfirma:
                                                                <span class="text-body-2">
                                                                    {{ company.company }}
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
                                        <VRow class="px-md-5 mt-1">
                                            <VCol cols="12" md="6" class="d-none d-md-block"></VCol>
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
                                                    @click="searchVehicule"
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
                                                    :rules="[yearValidator, requiredValidator]"
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
                                                    :rules="[requiredValidator]"
                                                /> 
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    type="number"
                                                    v-model="mileage"
                                                    suffix="Mil"
                                                    label="Miltal"
                                                    :rules="[requiredValidator]"
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
                                                    :rules="[requiredValidator]"
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
                                                    :rules="[requiredValidator]"
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
                                                    :rules="[requiredValidator]"
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
                                                    v-model="comments"
                                                    label="Kända fel, brister och övrig information"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>

                                    <!--Förmedlingsavgift-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Förmedlingsavgift
                                                </h6>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="commission_type_id"
                                                    label="Typ av provision"
                                                    :items="commission_types"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    :rules="[requiredValidator]"
                                                    autocomplete="off"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="commission_fee"
                                                    :label="'Provisionsavgift ' + (currencies.find(item => item.id === currency_id)?.code || '')"
                                                    type="number"
                                                    min="0"
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <div class="d-flex flex-column">
                                                    <label class="v-label text-body-2 text-wrap"> Har fordonet restskuld</label>
                                                    <VRadioGroup v-model="outstanding_debt" inline class="radio-form">
                                                        <VRadio
                                                            v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                            :key="index"
                                                            :label="radio"
                                                            :value="index"
                                                        />
                                                    </VRadioGroup>
                                                </div>
                                            </VCol>
                                            <VCol cols="12" md="3">
                                                <VTextField
                                                    v-model="selling_price"
                                                    :label="'Försäljningspris ' + (currencies.find(item => item.id === currency_id)?.code || '')"
                                                    type="number"
                                                    min="0"
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="3" v-if="outstanding_debt === 0">
                                                <div class="d-flex flex-column">
                                                    <label class="v-label text-body-2 text-wrap">Restskulden löses av</label>
                                                    <VRadioGroup v-model="residual_debt" inline class="radio-form">
                                                        <VRadio
                                                            v-for="(radio, index) in optionsSettled"
                                                            :key="index"
                                                            :label="radio"
                                                            :value="index"
                                                        />
                                                    </VRadioGroup>
                                                </div>
                                            </VCol>
                                            <VCol cols="12" md="3" v-if="outstanding_debt === 0">
                                                <VTextField
                                                    v-model="remaining_debt"
                                                    :label="'Restskuld ' + (currencies.find(item => item.id === currency_id)?.code || '')"
                                                    type="number"
                                                    min="0"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6" v-if="outstanding_debt === 0">
                                                <VTextField
                                                    v-model="paid_bank"
                                                    label="Restskuld betalas till bank"
                                                />
                                            </VCol>                                          
                                        </VRow>
                                    </VWindowItem>

                                    <!--Betalningsinformation-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Betalningsinformation
                                                </h6>
                                            </VCol>                                            
                                            <VCol cols="12" md="4">
                                                <VTextField
                                                    v-model="bank_name"
                                                    label="Bankens namn"
                                                    disabled
                                                />
                                            </VCol>        
                                            <VCol cols="12" md="4">
                                                <VTextField
                                                    v-model="account_number"
                                                    label="Kontonummer"
                                                    disabled
                                                />
                                            </VCol>                                 
                                            <VCol cols="12" md="4">
                                                <VTextField
                                                    v-model="payment_days"
                                                    label="Utbetalning antal bankdagar efter försäljning"
                                                    type="number"
                                                    min="0"
                                                    max="30"
                                                    :rules="[requiredValidator]"
                                                />
                                            </VCol>                                            
                                                                                   
                                            <VCol cols="12" md="12">
                                                <VTextarea
                                                    v-model="payment_description"
                                                    label="Betalningsbeskrivning"
                                                    rows="3"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!--Förmedlingsdatum-->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="12">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Förmedlingsdatum och giltighetstid
                                                </h6>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <AppDateTimePicker
                                                    v-model="start_date"
                                                    label="Startdatum"
                                                    :rules="[requiredValidator]"
                                                    :config="{
                                                        dateFormat: 'Y-m-d',
                                                        position: 'auto right'
                                                    }"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <AppDateTimePicker
                                                    :key="start_date"
                                                    v-model="end_date"
                                                    label="Slutdatum"
                                                    :rules="[requiredValidator]"
                                                    :config="endDateTimePickerConfig"
                                                />
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
                                    {{ (currentTab === 5) ? 'Skapa' : ' Nästa' }}
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
      action: create
      subject: agreements
</route>