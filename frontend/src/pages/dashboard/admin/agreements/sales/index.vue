<script setup>

import { useDisplay } from "vuetify";
import { onBeforeRouteLeave } from 'vue-router';
import { requiredValidator, yearValidator, emailValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useAgreementsStores } from '@/stores/useAgreements'
import { useAuthStores } from '@/stores/useAuth'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useConfigsStores } from '@/stores/useConfigs'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router'
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { usePersonInfoStores } from '@/stores/usePersonInfo'
import { useToastsStores } from '@/stores/useToasts'
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();

const isConfirmLeaveVisible = ref(false)
const nextRoute = ref(null)
const initialData = ref(null)
const allowNavigation = ref(false)
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);

const agreementsStores = useAgreementsStores()
const authStores = useAuthStores()
const configsStores = useConfigsStores()
const companyInfoStores = useCompanyInfoStores()
const personInfoStores = usePersonInfoStores()
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
const residual_price = ref(0)
const ivas = ref([])
const iva_purchase_id_interchange = ref(null)

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
const terms_other_conditions = ref(null)
const terms_other_information = ref(null)

// Recargar la página al crear otro acuerdo
function reloadPage() {
  window.location.reload();
}

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

    const { user_data, userAbilities } = await authStores.me(userData.value)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

    ability.update(userAbilities)

    localStorage.setItem('user_data', JSON.stringify(user_data))

    if(role.value === 'Supplier') {
        company.value = user_data.user_detail
        company.value.email = user_data.email
        company.value.name = user_data.name
        company.value.last_name = user_data.last_name
        agreement_id.value = user_data.supplier.agreements.length + 1
    } else if(role.value === 'User') {
        company.value = user_data.supplier.boss.user.user_detail
        company.value.email = user_data.supplier.boss.user.email
        company.value.name = user_data.supplier.boss.user.name
        company.value.last_name = user_data.supplier.boss.user.last_name
        agreement_id.value = user_data.supplier.boss.agreements.length + 1
    } else {
        await configsStores.getFeature('company')
        await configsStores.getFeature('logo')

        company.value = configsStores.getFeaturedConfig('company')
        company.value.logo = configsStores.getFeaturedConfig('logo').logo

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
    clients.value = agreementsStores.clients
    client_types.value = agreementsStores.client_types
    identifications.value = agreementsStores.identifications
    paymentTypes.value = agreementsStores.paymentTypes
    advances.value = agreementsStores.advances

    sale_date.value = formatDate(new Date())

    isRequestOngoing.value = false

    nextTick(() => {
      initialData.value = JSON.parse(JSON.stringify(currentData.value))
    })
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

const selectVehicle = vehicle => {
    if (vehicle) {
        let _vehicle = vehicles.value.find(item => item.id === vehicle)
    
        selectBrand(_vehicle.model?.brand.id)

        reg_num.value = _vehicle.reg_num
        brand_id.value = _vehicle.model?.brand.id
        model_id.value = _vehicle.model?.id
        year.value = _vehicle.year
        color.value = _vehicle.color
        mileage.value = _vehicle.mileage
    }
}

const clearVehicle = () => {
    reg_num.value = null
    brand_id.value = null
    model_id.value = null
    year.value = null
    color.value = null
    mileage.value = null
    modelsByBrand.value = []
}

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

const selectClient = client => {
    if (client) {
        let _client = clients.value.find(item => item.id === client)
    
        fullname.value = _client.fullname
        email.value = _client.email
        organization_number.value = _client.organization_number
        address.value = _client.address
        street.value = _client.street
        postal_code.value = _client.postal_code
        phone.value = _client.phone

        save_client.value = false
        disabled_client.value = true
    }
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

const formatOrgNumber = () => {

    let numbers = organization_number.value.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    organization_number.value = numbers
}

/**
 * Swedish organization numbers start with 5.
 * Otherwise treat as personal identity number.
 */
const isCompanyNumber = (value) => {
    const cleanNumber = (value ?? '').toString().replace(/[\s\-]/g, '')
    return cleanNumber.startsWith('5')
}

const isEntitySearchLoading = computed(() => {
    return companyInfoStores.loading || personInfoStores.loading
})

const searchEntity = async () => {
    if (!organization_number.value) return

    if (isCompanyNumber(organization_number.value)) {
        await searchCompany()
    } else {
        await searchPerson()
    }
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

            // Set Address
            if (response.postadressOrganisation?.postadress?.utdelningsadress) {
                address.value = response.postadressOrganisation.postadress.utdelningsadress
            } else {
                address.value = ''
            }

            // Set City (Postort)
            if (response.postadressOrganisation?.postadress?.postort) {
                street.value = response.postadressOrganisation.postadress.postort
            } else {
                street.value = ''
            }
        }

    } catch (error) {
        toastsStores.addToast({
            message: 'Ingen företag hittades med det registreringsnumret',
            type: 'error'
        })
    }
}

const searchPerson = async () => {
    try {
        const response = await personInfoStores.getPersonInfo(organization_number.value)

        if (response?.success && response?.data) {
            const personData = response.data

            // Set Client Type to Privat
            const privatType = client_types.value.find(t => t.name === 'Privat')
            if (privatType) {
                client_type_id.value = privatType.id
            }

            fullname.value = personData.fullname || ''
            postal_code.value = personData.postnummer || ''
            address.value = personData.adress || ''
            street.value = personData.postort || ''
        }
    } catch (error) {
        const errorMessage = error?.response?.data?.message || 'Ingen person hittades med det personnumret'
        toastsStores.addToast({
            message: errorMessage,
            type: 'error'
        })
    }
}

const onSubmit = () => {
    refForm.value?.validate().then(({ valid: isValid }) => {
        if (isValid && currentTab.value === 0 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (!isValid && currentTab.value === 0 && refForm.value.items.length > 16 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (currentTab.value === 1 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (isValid && currentTab.value === 2 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (!isValid && currentTab.value === 2 && refForm.value.items.length > 44 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (isValid && currentTab.value === 3 && refForm.value.items.length < 60) {
            currentTab.value++
        } else if (currentTab.value === 4) {

            let formData = new FormData()

            //client
            formData.append('save_client', save_client.value)
            formData.append('client_type_id', client_type_id.value)
            formData.append('identification_id', identification_id.value)
            formData.append('client_id', client_id.value)
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
            formData.append('vehicle_id', vehicle_id.value)

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

            formData.append('terms_other_conditions', terms_other_conditions.value)
            formData.append('terms_other_information', terms_other_information.value)

            isRequestOngoing.value = true

            agreementsStores.addAgreement(formData)
                .then((res) => {
                    if (res.data.success) {
                        
                        // let data = {
                        //     message: 'Kontrakt framgångsrikt skapat',
                        //     error: false
                        // }

                        // router.push({ name : 'dashboard-admin-agreements'})
                        // emitter.emit('toast', data)

                        allowNavigation.value = true;

                        // Save current state so the dirty-check stops blocking navigation
                        initialData.value = JSON.parse(JSON.stringify(currentData.value));

                        skapatsDialog.value = true;
                    } else {
                                        
                        // Save current state so the dirty-check stops blocking navigation
                        initialData.value = JSON.parse(JSON.stringify(currentData.value));
    
                        inteSkapatsDialog.value = true;
                    }
                    
                    isRequestOngoing.value = false
                })
                .catch((err) => {
                    
                    // let data = {
                    //     message: err.message,
                    //     error: true
                    // }

                    // router.push({ name : 'dashboard-admin-agreements'})
                    // emitter.emit('toast', data)

                    // Save current state so the dirty-check stops blocking navigation
                    initialData.value = JSON.parse(JSON.stringify(currentData.value));
    
                    inteSkapatsDialog.value = true;

                    isRequestOngoing.value = false
                })
        }

    })
}

/*
    Campos `v-model` dentro de los tabs (class="vehicles-tabs")

    Nota: la lista arriba incluye solo campos dentro de los tabs contenidos por la pestaña `vehicles-tabs`.
*/
const currentData = computed(() => ({
    // Tab 1: Venta
    vehicle_id: vehicle_id.value,
    reg_num: reg_num.value,
    agreement_id: agreement_id.value,
    brand_id: brand_id.value,
    model_id: model_id.value,
    model: model.value,
    year: year.value,
    color: color.value,
    chassis: chassis.value,
    mileage: mileage.value,
    sale_date: sale_date.value,
    guaranty: guaranty.value,
    guaranty_description: guaranty_description.value,
    guaranty_type_id: guaranty_type_id.value,
    insurance_company: insurance_company.value,
    insurance_company_description: insurance_company_description.value,
    insurance_type_id: insurance_type_id.value,

    // Tab 2: Inbytesfordon
    reg_num_interchange: reg_num_interchange.value,
    brand_id_interchange: brand_id_interchange.value,
    model_id_interchange: model_id_interchange.value,
    model_interchange: model_interchange.value,
    year_interchange: year_interchange.value,
    meter_reading_interchange: meter_reading_interchange.value,
    car_body_id_interchange: car_body_id_interchange.value,
    color_interchange: color_interchange.value,
    chassis_interchange: chassis_interchange.value,
    sale_date_interchange: sale_date_interchange.value,
    trade_price: trade_price.value,
    residual_debt: residual_debt.value,
    residual_price: residual_price.value,
    iva_purchase_id_interchange: iva_purchase_id_interchange.value,

    // Tab 3: Cliente
    client_id: client_id.value,
    client_type_id: client_type_id.value,
    identification_id: identification_id.value,
    organization_number: organization_number.value,
    address: address.value,
    street: street.value,
    postal_code: postal_code.value,
    phone: phone.value,
    fullname: fullname.value,
    email: email.value,
    save_client: save_client.value,
    disabled_client: disabled_client.value,

    // Tab 4: Precio
    price: price.value,
    iva_id: iva_id.value,
    iva_sale_amount: iva_sale_amount.value,
    iva_sale_exclusive: iva_sale_exclusive.value,
    discount: discount.value,
    registration_fee: registration_fee.value,
    total_sale: total_sale.value,
    payment_type: payment_type.value,
    payment_type_id: payment_type_id.value,
    advances: advances.value,
    advance_id: advance_id.value,
    payment_received: payment_received.value,
    payment_method_forcash: payment_method_forcash.value,
    installment_amount: installment_amount.value,
    installment_contract_upon_delivery: installment_contract_upon_delivery.value,
    payment_description: payment_description.value,
    middle_price: middle_price.value,

    // Tab 5: Términos
    terms_other_conditions: terms_other_conditions.value,
    terms_other_information: terms_other_information.value,
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
    <section>
        <LoadingOverlay :is-loading="isRequestOngoing" />

        <VForm
            ref="refForm"
            class="card-form"
            v-model="isFormValid"
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
                    <div class="d-flex flex-wrap gap-y-4 gap-x-6 mb-4 justify-start justify-sm-space-between">
                        <div class="d-flex flex-column gap-4">
                            <span class="title-page">
                                Försäljningsavtal
                            </span>
                        </div>

                        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

                        <div 
                            class="d-flex gap-4"
                            :class="windowWidth < 1024 ? 'w-100' : 'align-center'"
                        >
                            <VBtn
                                class="btn-light w-auto" 
                                block
                                :to="{ name: 'dashboard-admin-agreements' }">
                                <VIcon icon="custom-return" size="24" />
                                Avbryt
                            </VBtn>
                        </div>
                    </div>
                </VCardText>

                <VDivider :class="windowWidth < 1024 ? 'mb-4' : 'mb-8'" />

                <VTabs 
                    v-model="currentTab" 
                    :grow="windowWidth < 1024 ? true : false"                
                    :show-arrows="false"
                    class="vehicles-tabs" 
                    disabled
                >
                    <VTab>
                        <VIcon size="24" icon="custom-bribery" />
                        Försäljning
                    </VTab>
                    <VTab>
                        <VIcon size="24" icon="custom-car" />
                        Inbytesfordon
                    </VTab>
                    <VTab>
                        <VIcon size="24" icon="custom-clients" />
                        Kund
                    </VTab>
                    <VTab>
                        <VIcon size="24" icon="custom-cash-2" />
                        Pris
                    </VTab>
                    <VTab>
                        <VIcon size="24" icon="custom-cash" />
                        Villkor
                    </VTab>
                </VTabs>
                      
                <VCardText class="px-0 px-md-2">
                    <VWindow v-model="currentTab">
                        <!--Försäljning-->
                        <VWindowItem class="px-md-5">
                            <VRow class="px-md-5">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Fordon
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Lagerbilar" />
                                            <AppAutocomplete
                                                v-model="vehicle_id"
                                                :items="vehicles"
                                                item-title="reg_num"      
                                                item-value="id"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                @update:modelValue="selectVehicle"
                                                @click:clear="clearVehicle"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Regnr*" />
                                            <VTextField
                                                v-model="reg_num"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Avtalsnummer" />
                                            <VTextField
                                                v-model="agreement_id"
                                                disabled
                                                prefix="#"
                                                density="compact"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Märke*" />
                                            <AppAutocomplete
                                                v-model="brand_id"
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
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modell*" />
                                            <AppAutocomplete
                                                v-model="model_id"
                                                :items="getModels"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                :rules="[requiredValidator]"
                                                @update:modelValue="selectModel"
                                                :menu-props="{ maxHeight: '300px' }"/> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'" v-if="model_id === 0">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modellens namn*" />
                                            <VTextField
                                                v-model="model"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Årsmodell*" />
                                            <VTextField
                                                v-model="year"
                                                :rules="[requiredValidator, yearValidator]"
                                            />   
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Färg*" />
                                            <VTextField
                                                v-model="color"
                                                :rules="[requiredValidator]"
                                            /> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Chassinummer" />
                                            <VTextField
                                                v-model="chassis"
                                            /> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Miltal*" />
                                            <VTextField
                                                type="number"
                                                v-model="mileage"
                                                suffix="Mil"
                                                min="0"
                                                :rules="[requiredValidator]"
                                            /> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Försäljningsdatum*" />
                                            <AppDateTimePicker
                                                :key="JSON.stringify(startDateTimePickerConfig)"
                                                v-model="sale_date"
                                                density="compact"
                                                :config="startDateTimePickerConfig"
                                                :rules="[requiredValidator]"
                                                clearable
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Garanti*" />
                                            <AppAutocomplete
                                                v-model="guaranty"
                                                :items="guaranties"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                @update:modelValue="selectGuaranty"
                                                :rules="[requiredValidator]"
                                            /> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Garantibeskrivning" />
                                            <VTextField
                                                v-model="guaranty_description"
                                                :rules="guarantyDescriptionRules"
                                                :disabled="guaranty === 0"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Typ av garanti" />
                                            <AppAutocomplete
                                                v-model="guaranty_type_id"
                                                :items="guarantyTypes"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :disabled="guaranty === 0"
                                                autocomplete="off"
                                            />    
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Försäkring*" />
                                            <AppAutocomplete
                                                v-model="insurance_company"
                                                :items="insuranceCompanies"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :rules="[requiredValidator]"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />    
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Beskrivning av försäkringsbolag" />
                                            <VTextField
                                                v-model="insurance_company_description"
                                                :rules="insuranceDescriptionRules"
                                                :disabled="insurance_company === 0"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Försäkringstyp" />
                                            <VSelect
                                                v-model="insurance_type_id"
                                                :items="insuranceTypes"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :disabled="insurance_company === 0"
                                                autocomplete="off"
                                            />    
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Inbytesfordon-->
                        <VWindowItem class="px-md-5">
                            <VRow class="px-md-5">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Inbytesfordon
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Regnr" />
                                            <div class="d-flex gap-2"> 
                                                <VTextField
                                                    v-model="reg_num_interchange"
                                                />
                                                <VBtn
                                                    class="btn-light w-auto px-4"
                                                >
                                                    <VIcon icon="custom-search" size="24" />
                                                    Hämta
                                                </VBtn>
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Märke" />
                                            <AppAutocomplete
                                                v-model="brand_id_interchange"
                                                :items="brands"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                @update:modelValue="selectBrandInterchange"
                                                @click:clear="onClearBrandInterchange"
                                                :menu-props="{ maxHeight: '300px' }"/> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modell" />
                                            <AppAutocomplete
                                                v-model="model_id_interchange"
                                                :items="getModelsInterchange"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                @update:modelValue="selectModelInterchange"
                                                :menu-props="{ maxHeight: '300px' }"/> 
                                        </div>
                                        <div v-if="model_id_interchange === null" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modellens namn*" />
                                            <VTextField
                                                v-model="model_interchange"
                                                :rules="[requiredValidator]"    
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Årsmodell" />
                                            <VTextField
                                                v-model="year_interchange"
                                                :rules="[yearValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mätarställning" />
                                            <VTextField
                                                v-model="meter_reading_interchange" 
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kaross" />
                                            <AppAutocomplete
                                                v-model="car_body_id_interchange"
                                                :items="carbodies"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Färg" />
                                            <VTextField
                                                v-model="color_interchange"
                                            />
                                        </div>                                        
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Chassinummer" />
                                            <VTextField
                                                v-model="chassis_interchange"
                                            /> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Inbytesdatum" />
                                            <AppDateTimePicker
                                                :key="JSON.stringify(startDateTimePickerConfig)"
                                                v-model="sale_date_interchange"
                                                density="compact"
                                                :config="startDateTimePickerConfig"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" :text="'Inbytespris ' + (currencies.find(item => item.id === currency_id)?.code || '')" />
                                            <VTextField
                                                v-model="trade_price"
                                                type="number"
                                                min="0"
                                            /> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <div class="d-flex flex-column ms-2">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Restskuld" />
                                                <VRadioGroup 
                                                    v-model="residual_debt" 
                                                    inline 
                                                    class="radio-form ms-2"
                                                    @update:modelValue="onChangeRadio">
                                                    <VRadio
                                                        v-for="(radio, index) in optionsRadio"
                                                        :key="index"
                                                        :label="radio"
                                                        :value="index"
                                                    />
                                                </VRadioGroup>
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" :text="'Restskuld ' + (currencies.find(item => item.id === currency_id)?.code || '')" />
                                            <VTextField
                                                v-model="residual_price"
                                                type="number"
                                                min="0"
                                                :disabled="residual_debt === 0 ? true : false"
                                            /> 
                                        </div>                                        
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" :text="'Verkligt värde ' + (currencies.find(item => item.id === currency_id)?.code || '')" />
                                            <VTextField
                                                :model-value="fair_value"
                                                disabled 
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="VMB / Moms" />
                                            <AppAutocomplete
                                                v-model="iva_purchase_id_interchange"
                                                :items="ivas"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"/>
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Kund-->
                        <VWindowItem class="px-md-5">
                            <VRow class="px-md-5">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Köpare
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kunder" />
                                            <AppAutocomplete
                                                v-model="client_id"
                                                :items="clients"
                                                :item-title="item => item.fullname"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                clearable
                                                @click:clear="clearClient"
                                                @update:modelValue="selectClient"/>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Org/personummer*" />
                                            <div class="d-flex gap-2"> 
                                                <VTextField
                                                    v-model="organization_number"
                                                    :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                                                    minLength="11"
                                                    maxlength="13"
                                                    @input="formatOrgNumber()"
                                                />
                                                <VBtn
                                                    class="btn-light w-auto px-4"
                                                    @click="searchEntity"
                                                    :loading="isEntitySearchLoading"
                                                >
                                                    <VIcon icon="custom-search" size="24" />
                                                    Hämta
                                                </VBtn>
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Köparen är*" />
                                            <AppAutocomplete
                                                v-model="client_type_id"
                                                :items="client_types"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :rules="[requiredValidator]"
                                                autocomplete="off"/>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Namn*" />
                                            <VTextField
                                                v-model="fullname"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />
                                            <VTextField
                                                v-model="address"
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
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon*" />
                                            <VTextField
                                                v-model="phone"
                                                :rules="[requiredValidator, phoneValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Legitimation*" />
                                            <AppAutocomplete
                                                v-model="identification_id"
                                                :items="identifications"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :rules="[requiredValidator]"
                                                autocomplete="off"/>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
                                            <VTextField
                                                v-model="email"
                                                :rules="[emailValidator, requiredValidator]"
                                            />
                                        </div>
                                    </div>
                                </VCol> 

                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Säljare
                                    </div>
                                    <VList class="card-list mt-2">
                                        <VListItem>
                                            <VListItemTitle>
                                                <h6 class="text-base font-weight-semibold">
                                                    Namn:
                                                    <span class="text-body-2 text-high-emphasis">
                                                        {{ company.name }} {{ company.last_name }}
                                                    </span>
                                                </h6>
                                            </VListItemTitle>
                                            <VListItemTitle>
                                                <h6 class="text-base font-weight-semibold">
                                                    Org/personummer:
                                                    <span class="text-body-2 text-high-emphasis">
                                                        {{ company.organization_number }}
                                                    </span>
                                                </h6>
                                            </VListItemTitle>
                                            <VListItemTitle>
                                                <h6 class="text-base font-weight-semibold">
                                                    Adress:
                                                    <span class="text-body-2 text-high-emphasis">
                                                        {{ company.address }}
                                                    </span>
                                                </h6>
                                            </VListItemTitle>
                                            <VListItemTitle>
                                                <h6 class="text-base font-weight-semibold">
                                                    Postnr. ort:
                                                    <span class="text-body-2 text-high-emphasis">
                                                        {{ company.street + ' ' +  company.postal_code }}
                                                    </span>
                                                </h6>
                                            </VListItemTitle>
                                            <VListItemTitle>
                                                <h6 class="text-base font-weight-semibold">
                                                    Telefon:
                                                    <span class="text-body-2 text-high-emphasis">
                                                        {{ company.phone }}
                                                    </span>
                                                </h6>
                                            </VListItemTitle>
                                            <VListItemTitle>
                                                <h6 class="text-base font-weight-semibold">
                                                    E-post
                                                    <span class="text-body-2 text-high-emphasis">
                                                        {{ company.email }}
                                                    </span>
                                                </h6>
                                            </VListItemTitle>
                                            <VListItemTitle>
                                                <h6 class="text-base font-weight-semibold">
                                                    Bilfirma:
                                                    <span class="text-body-2 text-high-emphasis">
                                                        {{ company.company }}
                                                    </span>
                                                </h6>
                                            </VListItemTitle>
                                        </VListItem>
                                    </VList>

                                    <VRow>
                                        <VCol cols="12" md="12" class="py-3">
                                            <VCheckbox
                                                v-model="save_client"
                                                :readonly="disabled_client"
                                                color="primary"
                                                label="Spara kund?"
                                                class="w-100 text-center d-flex justify-content-end"
                                            />
                                        </VCol>
                                    </VRow>
                                </VCol>                                                                                    
                            </VRow>
                            
                        </VWindowItem>

                        <!--Pris-->
                        <VWindowItem class="px-md-5">
                            <VRow class="px-md-5">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Specifikation, pris
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Pris*" />
                                            <VTextField
                                                v-model="price"
                                                type="number"
                                                min="0"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Valuta" />
                                            <AppAutocomplete
                                                v-model="currency_id"
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
                                            </AppAutocomplete>
                                        </div>                                        
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Moms / VMB / Export*" />
                                            <AppAutocomplete
                                                v-model="iva_id"
                                                :items="ivas"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                :rules="[requiredValidator]"/>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Varav moms*" />
                                            <VTextField
                                                type="number"
                                                v-model="iva_sale_amount"
                                                min="0"
                                                disabled
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Pris ex moms*" />
                                            <VTextField
                                                type="number"
                                                v-model="iva_sale_exclusive"
                                                min="0"
                                                disabled
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Rabatt*" />
                                            <VTextField
                                                type="number"
                                                v-model="discount"
                                                min="0"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div style="width: 100%;">
                                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Registreringsavgift*" />
                                                <VTextField
                                                    type="number"
                                                    v-model="registration_fee"
                                                    min="0"
                                                    :rules="[requiredValidator]"
                                                />
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(30% - 12px);'">
                                            <h6 class="text-base font-weight-semibold">
                                                Totalpris:
                                                <span class="text-body-2 text-high-emphasis">
                                                    {{ total_sale }} {{ currencies.filter(item => item.id === currency_id)[0].code }}
                                                </span>
                                            </h6>
                                            
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(30% - 12px);'">
                                            <h6 class="text-base font-weight-semibold">
                                                Pris på inbytesbil:
                                                <span class="text-body-2 text-high-emphasis">
                                                    {{ trade_price }} {{ currencies.filter(item => item.id === currency_id)[0].code }}
                                                </span>
                                            </h6>
                                            
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 12px);'">
                                            <h6 class="text-base font-weight-semibold">
                                                Mellanpris:
                                                <span class="text-body-2 text-high-emphasis">
                                                    {{ middle_price }} {{ currencies.filter(item => item.id === currency_id)[0].code }}
                                                </span>
                                            </h6>
                                            
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Betalsätt*" />
                                            <AppAutocomplete
                                                v-model="payment_type_id"
                                                :items="getPaymentTypes"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                :rules="[requiredValidator]"
                                                @update:modelValue="selectPaymentType"
                                                @click:clear="selectPaymentType"
                                            />
                                        </div>
                                        <div v-if="payment_type_id === 0" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(30% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Betalsätt" />
                                            <VTextField
                                                v-model="payment_type"
                                            />
                                        </div>
                                        <div class="d-none" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Handpenning procent" />
                                            <AppAutocomplete
                                                v-model="advance_id"
                                                :items="advances"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :disabled="payment_type_id !== 3 && payment_type_id !== 4 && payment_type_id !== 10"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Summa kontant / handpenning" />
                                            <VTextField
                                                v-model="payment_received"
                                                type="number"
                                                min="0"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Betalsätt för kontant / handpenning" />
                                            <VTextField
                                                v-model="payment_method_forcash"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Avbetalningsbelopp (kreditbelopp/leasing)" />
                                            <VTextField
                                                v-model="installment_amount"
                                                type="number"
                                                min="0"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Betalningsbeskrivning" />
                                            <VTextField
                                                v-model="payment_description"
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Villkor-->
                        <VWindowItem class="px-md-5">
                            <VRow class="px-md-5">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Villkor
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Övriga villkor inhämtas från mall" />
                                            <VTextarea
                                                v-model="terms_other_conditions"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Övriga upplysningar" />
                                            <VTextarea
                                                v-model="terms_other_information"
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>
                    </VWindow>
                </VCardText>

                <VCardText class="p-0">
                    <!-- 👉 Submit and Cancel -->
                    <div 
                        class="d-flex gap-4 mb-12"
                        :class="windowWidth < 1024 ? 'w-100' : 'justify-content-end'"
                    >
                        <VBtn
                            v-if="currentTab > 0"
                            class="btn-light w-auto"
                            :block="windowWidth < 1024"
                            @click="currentTab--"
                            >
                            <VIcon icon="custom-return" size="24" />
                            Tillbaka
                        </VBtn>
                        <VBtn 
                            type="submit" 
                            :block="windowWidth < 1024"
                            class="btn-gradient"
                        >
                            <VIcon v-if="currentTab === 4" icon="custom-save"  size="24" />
                            {{ (currentTab === 4) ? 'Skapa' : 'Nästa' }}
                        </VBtn>
                    </div>
                </VCardText>
            </VCard>
        </VForm>

        <!-- 👉 Dialogs Section -->

        <!-- 👉 Skapats Dialogs -->
        <VDialog
            v-model="skapatsDialog"
            persistent
            class="action-dialog dialog-big-icon"
            >
            <VBtn
                icon
                class="btn-white close-btn"
                @click="reloadPage"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>

            <VCard>
                <VCardText class="dialog-title-box big-icon justify-center pb-0">
                    <VIcon size="72" icon="custom-certificate" />
                </VCardText>
                <VCardText class="dialog-title-box justify-center">
                    <div class="dialog-title">Avtalet har skapats!</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    Ditt nya avtal har sparats och finns nu i din avtalslista.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" :to="{ name: 'dashboard-admin-agreements' }" >
                        Gå till avtalslistan
                    </VBtn>
                    <VBtn class="btn-gradient" @click="reloadPage">
                        Skapa ett till avtal 
                    </VBtn>
                </VCardText>
            </VCard>
        </VDialog>

        <VDialog
            v-model="inteSkapatsDialog"
            persistent
            class="action-dialog dialog-big-icon"
            >
            <VBtn
                icon
                class="btn-white close-btn"
                @click="inteSkapatsDialog = !inteSkapatsDialog"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>
            <VCard>
                <VCardText class="dialog-title-box big-icon justify-center pb-0">
                    <VIcon size="72" icon="custom-f-cancel" />
                </VCardText>
                <VCardText class="dialog-title-box justify-center">
                    <div class="dialog-title">Kunde inte skapa avtalet</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    Ett fel inträffade. Kontrollera att alla obligatoriska fält är korrekt ifyllda och försök igen.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="inteSkapatsDialog = !inteSkapatsDialog">
                        Stäng
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

<style lang="scss">
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
        font-size: 24px;
        line-height: 100%;
        color: #878787;
    }

    .v-btn--disabled {
        opacity: 1 !important;
    }

    .border-bottom-secondary {
        border-bottom: 1px solid #d9d9d9;
        padding-bottom: 10px;
    }

    .justify-content-end {
        justify-content: end !important;
    }

    .v-tabs.vehicles-tabs {
        .v-btn {
            min-width: 50px !important;
            .v-btn__content {
                font-size: 14px !important;
                color: #454545;
            }
        }
    }

    .info-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;

        .info-item {
            flex: 0 0 calc(100% / 7 - 14px);
            min-width: 0;

            span  {
                font-weight: 400;
                font-size: 16px;
                line-height: 24px;
                color: #454545;
            }

            .value-field {
                background-color: #F6F6F6;
                border-radius: 8px;
                border: 1px solid #E7E7E7;
                padding: 16px;
                height: 48px !important;
                align-items: center;
                display: flex;
                font-weight: 400;
                font-size: 12px;
                line-height: 24px;
                color: #5D5D5D;
            }

            @media (max-width: 1023px) {
                flex: 0 0 calc(50% - 8px);
            }
        }
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
                        @media (max-width: 991px) {
                            top: 12px !important;
                        }
                    }

                    .v-field__append-inner {
                        align-items: center;
                        padding-top: 0px;
                    }
                }
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

    .vehicles-pills > div {
        flex: 1 1;
    }

    .vehicles-pill {
        display: flex;
        align-items: center;
        padding: 16px;
        border-radius: 8px;
    }

    .vehicles-pill-title {
        font-family: "Blauer Nue";
        font-weight: 400;
        font-size: 16px;
        line-height: 100%;
        margin-right: 4px;
    }

    .vehicles-pill-value {
        font-family: "Blauer Nue";
        font-weight: 700;
        font-style: Bold;
        font-size: 16px;
        line-height: 100%;
    }

    @media (max-width: 991px) {
        .vehicles-pills {
            flex-direction: column;
            gap: 8px;
        }

        .vehicles-pill {
            padding: 8px 16px;
        }
    }
</style>

<route lang="yaml">
    meta:
      action: create
      subject: agreements
</route>