<script setup>

import { useDisplay } from "vuetify";
import { onBeforeRouteLeave } from 'vue-router';
import { requiredValidator, yearValidator, emailValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useAgreementsStores } from '@/stores/useAgreements'
import { useCarInfoStores } from '@/stores/useCarInfo'
import { useAuthStores } from '@/stores/useAuth'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useConfigsStores } from '@/stores/useConfigs'
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { usePersonInfoStores } from '@/stores/usePersonInfo'
import { useToastsStores } from '@/stores/useToasts'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router'
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

const agreementsStores = useAgreementsStores()
const authStores = useAuthStores()
const carInfoStores = useCarInfoStores()
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

const isConfirmLeaveVisible = ref(false)
const nextRoute = ref(null)
const initialData = ref(null)
const allowNavigation = ref(false)
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);

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
const comments = ref(null)
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

//const tab 3
const ivas = ref([])
const price = ref(null)
const iva_id = ref(null)
const iva_sale_amount = ref(0)
const iva_sale_exclusive = ref(0)
const is_loan = ref(1)
const loan_amount = ref(0)
const lessor = ref(null)
const settled_by = ref(2)
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

// Recargar la página al crear otro acuerdo
function reloadPage() {
  window.location.reload();
}

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

const conditionalRules = computed(() => {
    return settled_by.value === 1 ? [requiredValidator] : []
})

const conditionalRulesJa = computed(() => {
    return is_loan.value === 0 ? [requiredValidator] : []
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

    purchase_date.value = formatDate(new Date())

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

const handleChange = (val) => {

    if(val === 1) {
        loan_amount.value = 0
        lessor.value = null
        settled_by.value = 2
        payment_type_id.value = null
        payment_type.value = null
        bank.value = null
        account.value = null
        description.value = null
    } else {
        settled_by.value = 0
    }

    if (refForm.value) {
        refForm.value.validate()
    }
}

const handleChangeTwo = (val) => {
    if(val === 0) {
         payment_type_id.value = null
        payment_type.value = null
        bank.value = null
        account.value = null
        description.value = null
    }

    if (refForm.value) {
        refForm.value.validate()
    }
}

/**
 * Buscar información del vehículo por matrícula usando la API car.info
 * Llena automáticamente los campos: Modell, Kaross, Drivmedel, etc.
 */
const searchVehicleByPlate = async () => {
  if (!reg_num.value) {
    advisor.value = {
        type: 'warning',
        message: 'Ange ett registreringsnummer',
        show: true
    }

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)

    return
  }

  isRequestOngoing.value = true

  try {
    const carRes = await carInfoStores.getLicensePlate(reg_num.value)
    
    // Verificar success (también manejar typo 'sucess' de la API)
    const isSuccess = carRes?.success === true || carRes?.sucess === true
    
    if (isSuccess && carRes?.result) {
        // Actualizar año del modelo
        if (carRes.result.model_year) {
            year.value = carRes.result.model_year
        }                    

        if (carRes.result.color) {
            color.value = carRes.result.color
        }

        if (carRes.result.chassis_number) {
            chassis.value = carRes.result.chassis_number
        }

        // Actualizar marca (Märke)
        if (carRes.result.brand_id) {
            brand_id.value = carRes.result.brand_id
            selectBrand(brand_id.value)
        }
        
        // Actualizar modelo (Modell)
        if (carRes.result.model_id) {
            model_id.value = carRes.result.model_id
        } else if (carRes.result.model_name) {
            // Si no se encontró el modelo en la DB, usar el campo de texto libre
            model_id.value = 0
            model.value = carRes.result.model_name
        }
        
        if (carRes.result.mileage) {
            mileage.value = carRes.result.mileage
        }

        // Actualizar tipo de combustible (Drivmedel)
        if (carRes.result.fuel_id) {
            fuel_id.value = carRes.result.fuel_id
        }

        // Actualizar caja de cambios (Växellåda)
        if (carRes.result.gearbox_id) {
            gearbox_id.value = carRes.result.gearbox_id
        }

        advisor.value = {
            type: 'success',
            message: 'Fordonsdata hämtades framgångsrikt',
            show: true
        }   

    } else {
        advisor.value = {
            type: 'warning',
            message: 'Ingen information hittades för detta registreringsnummer',
            show: true
        }
    }
  } catch (error) {    
    advisor.value = {
        type: 'error',
        message: error?.response?.data?.message || error?.message || 'Fel vid hämtning av fordonsdata',
        show: true
    }
  } finally {
      setTimeout(() => {
          advisor.value = {
              type: '',
              message: '',
              show: false
          }
      }, 3000)

      isRequestOngoing.value = false
  }
}


const onSubmit = async () => {
    // Validación manual ANTES de usar VForm.validate()
    // Verificar tab 0 (Inköpsavtal)
    const hasTab0Errors = !reg_num.value || 
                          !brand_id.value || 
                          !model_id.value || 
                          (model_id.value === 0 && !model.value) ||
                          !year.value ||
                          !color.value ||
                          !mileage.value || 
                          !purchase_date.value

    // Verificar tab 1 (Kund)
    const hasTab1Errors = !organization_number.value || 
                          (organization_number.value && minLengthDigitsValidator(10)(organization_number.value) !== true) ||
                          !client_type_id.value || 
                          !fullname.value || 
                          !address.value || 
                          !postal_code.value || 
                          !street.value || 
                          !phone.value || 
                          (phone.value && phoneValidator(phone.value) !== true) ||
                          !identification_id.value || 
                          !email.value || 
                          (email.value && emailValidator(email.value) !== true)

    // Verificar tab 2 (Pris)
    const hasTab2Errors = !price.value || 
                          !iva_id.value ||
                          (payment_type_id.value === 0 && !payment_type.value) ||
                          (is_loan.value === 0 && (!loan_amount.value || !lessor.value)) ||
                          (settled_by.value === 1 && (!payment_type_id.value || !bank.value || !account.value || !description.value))

    // Si hay errores, ir al primer tab con error
    if (hasTab0Errors && currentTab.value !== 0) {
        currentTab.value = 0
        
        // Esperar a que el tab se monte y luego validar
        await nextTick()
        refForm.value?.validate()
        
        advisor.value = {
            type: 'warning',
            message: 'Vänligen fyll i alla obligatoriska fält i fliken Inköpsavtal',
            show: true
        }
        
        setTimeout(() => {
            advisor.value = {
                type: '',
                message: '',
                show: false
            }
        }, 3000)
        
        return
    }
    
    if (hasTab1Errors && currentTab.value !== 1) {
        currentTab.value = 1
        
        await nextTick()
        refForm.value?.validate()
        
        advisor.value = {
            type: 'warning',
            message: 'Vänligen fyll i alla obligatoriska fält i fliken Kund',
            show: true
        }
        
        setTimeout(() => {
            advisor.value = {
                type: '',
                message: '',
                show: false
            }
        }, 3000)
        
        return
    }

    if (hasTab2Errors && currentTab.value !== 2) {
        currentTab.value = 2
        
        await nextTick()
        refForm.value?.validate()
        
        advisor.value = {
            type: 'warning',
            message: 'Vänligen fyll i alla obligatoriska fält i fliken Pris',
            show: true
        }
        
        setTimeout(() => {
            advisor.value = {
                type: '',
                message: '',
                show: false
            }
        }, 3000)
        
        return
    }

    // Lógica de navegación entre tabs
    if (currentTab.value === 0 && !hasTab0Errors) {
        currentTab.value++
        return
    } else if (currentTab.value === 1 && !hasTab1Errors) {
        currentTab.value++
        return
    } else if (currentTab.value === 2 && !hasTab2Errors) {
        currentTab.value++
        return
    }

    // Si estamos en el último tab (3), proceder con el submit final
    if (currentTab.value === 3) {
        refForm.value?.validate().then(({ valid: isValid }) => {
            if (isValid) {
                let formData = new FormData()

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
                formData.append('comments', comments.value)

                //vehicle payment
                formData.append('is_loan', is_loan.value)
                formData.append('loan_amount', loan_amount.value === 0 ? null : loan_amount.value)
                formData.append('lessor', lessor.value)
                formData.append('remaining_amount', remaining_amount.value)
                formData.append('settled_by', settled_by.value)
                formData.append('bank', bank.value)
                formData.append('account', account.value)
                formData.append('description', description.value)

                //client
                formData.append('type', 2)
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

                formData.append('terms_other_conditions', terms_other_conditions.value)
                formData.append('terms_other_information', terms_other_information.value)
                

                isRequestOngoing.value = true

                agreementsStores.addAgreement(formData)
                    .then((res) => {
                        if (res.data.success) {
                            allowNavigation.value = true;
                            initialData.value = JSON.parse(JSON.stringify(currentData.value));
                            skapatsDialog.value = true;
                        } else {
                            initialData.value = JSON.parse(JSON.stringify(currentData.value));
                            inteSkapatsDialog.value = true;
                        }
                        isRequestOngoing.value = false
                    })
                    .catch((err) => {
                        initialData.value = JSON.parse(JSON.stringify(currentData.value));
                        inteSkapatsDialog.value = true;
                        isRequestOngoing.value = false
                    })
            }
        })
    }
}


/*
    Campos `v-model` con la regla `requiredValidator` dentro de los tabs (class="agreements-tabs")

    Tab 1 (Inköpsavtal):
    - reg_num
    - brand_id
    - model_id
    - year
    - color
    - mileage
    - purchase_date

    Tab 2 (Kund):
    - organization_number
    - client_type_id
    - fullname
    - address
    - postal_code
    - street
    - phone
    - identification_id
    - email

    Tab 3 (Pris):
    - price
    - iva_id
    - iva_sale_amount (tiene `:rules="[requiredValidator]"` aunque está deshabilitado)
    - iva_sale_exclusive (tiene `:rules="[requiredValidator]"` aunque está deshabilitado)
    - payment_type (campo alternativo visible cuando `payment_type_id === 0`, tiene `:rules="[requiredValidator]"`)

    Reglas condicionales (computed que retornan `[requiredValidator]`):
    - payment_type_id (usa `:rules="conditionalRules"` → requerido si `settled_by === 1`)
    - loan_amount (usa `:rules="conditionalRulesJa"` → requerido si `is_loan === 0`)
    - lessor (usa `:rules="conditionalRulesJa"` → requerido si `is_loan === 0`)
    - bank, account, description (usan `:rules="conditionalRules"` → requeridos si `settled_by === 1`)

    Nota: la lista arriba incluye solo campos dentro de los tabs contenidos por la pestaña `agreements-tabs`.
*/
const currentData = computed(() => ({
    reg_num: reg_num.value,
    brand_id: brand_id.value,
    model_id: model_id.value,
    year: year.value,
    color: color.value,
    mileage: mileage.value,
    purchase_date: purchase_date.value,
    organization_number: organization_number.value,
    client_type_id: client_type_id.value,
    fullname: fullname.value,
    address: address.value,
    postal_code: postal_code.value,
    street: street.value,
    phone: phone.value,
    identification_id: identification_id.value,
    email: email.value,
    price: price.value,
    iva_id: iva_id.value,
    iva_sale_amount: iva_sale_amount.value,
    iva_sale_exclusive: iva_sale_exclusive.value,
    payment_type: payment_type.value,
    payment_type_id: payment_type_id.value,
    loan_amount: loan_amount.value,
    lessor: lessor.value,
    bank: bank.value,
    account: account.value,
    description: description.value
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
  
  // Check for URL hash to activate specific tab
  const hash = window.location.hash;
  if (hash === '#tab-tasks') {
    currentTab.value = 'tab-5';
  }
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
    <section class="page-section agreements-page" ref="sectionEl">
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
                    <div 
                        class="d-flex  gap-y-4 gap-x-6 mb-4 justify-start justify-sm-space-between"
                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-wrap'"
                    >
                
                        <VBtn
                            :class="windowWidth < 1024 ? 'd-flex' : 'd-none'" 
                            class="btn-light"
                            style="width: 120px;"
                            :to="{ name: 'dashboard-admin-agreements' }"
                        >
                            <VIcon icon="custom-return" size="24" />
                            Gå ut
                        </VBtn>
                        
                        <div class="d-flex flex-column gap-4">
                            <span class="title-page">
                                Inköpsavtal
                            </span>
                        </div>

                        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

                        <div 
                            :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-4 align-center'"
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
                    class="agreements-tabs" 
                >
                    <VTab>
                        <VIcon size="24" icon="custom-agreement" />
                        Inköpsavtal
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

                <VCardText class="px-0">
                    <VWindow v-model="currentTab">
                        <!--Inköpsavtal - Reg nr-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Fordonsinformation
                                    </div>

                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
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
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Regnr*" />
                                            <div class="d-flex gap-2"> 
                                                <VTextField
                                                    v-model="reg_num"
                                                    :rules="[requiredValidator]"
                                                />
                                                <VBtn
                                                    class="btn-light w-auto px-4"
                                                    @click="searchVehicleByPlate"
                                                >
                                                    <VIcon icon="custom-search" size="24" />
                                                    Hämta
                                                </VBtn>
                                            </div>
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
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : model_id !== 0 ? 'width: calc(50% - 12px);' : 'width: calc(25% - 18px);'">
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
                                        <div v-if="model_id === 0" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(25% - 18px);'">
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
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Drivmedel" />
                                            <AppAutocomplete
                                                v-model="fuel_id"
                                                :items="fuels"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"/>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Växellåda" />
                                            <AppAutocomplete
                                                v-model="gearbox_id"
                                                :items="gearboxes"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Antal nycklar" />
                                            <VTextField
                                                v-model="number_keys"
                                                type="number"
                                                min="1"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Inköpsdatum*" />
                                            <AppDateTimePicker
                                                :key="JSON.stringify(startDateTimePickerConfig)"
                                                v-model="purchase_date"
                                                density="compact"
                                                :config="startDateTimePickerConfig"
                                                :rules="[requiredValidator]"
                                                clearable
                                            />
                                        </div>
                                        <div class="w-100">
                                            <VRow no-gutters>
                                                <VCol cols="12" md="4">
                                                    <div class="d-flex flex-column">
                                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Servicebok finns?" />
                                                        <VRadioGroup v-model="service_book" inline class="radio-form ms-2">
                                                            <VRadio
                                                                v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
                                                        </VRadioGroup>
                                                    </div>
                                                </VCol>
                                                <VCol cols="12" md="4"> 
                                                    <div class="d-flex flex-column">
                                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Sommardäck finns?" />

                                                        <VRadioGroup v-model="summer_tire" inline class="radio-form ms-2">
                                                            <VRadio
                                                                v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
                                                        </VRadioGroup>
                                                    </div>
                                                </VCol>
                                                <VCol cols="12" md="4">
                                                    <div class="d-flex flex-column">
                                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vinterdäck finns?" />
                                                        <VRadioGroup v-model="winter_tire" inline class="radio-form ms-2">
                                                            <VRadio
                                                                v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
                                                        </VRadioGroup>
                                                    </div>
                                                </VCol>
                                            </VRow>
                                        </div>
                                        <div class="w-100">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Anteckningar" />
                                            <VTextarea
                                                v-model="comments"
                                                rows="3"
                                            />
                                        </div>                                       
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Kund-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Säljare
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

                                <VDivider class="my-4" />

                                <VCol cols="12">
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column gap-1' : 'flex-row gap-4'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%; margin-bottom: 8px;' : 'width: calc(20%);'">
                                            <span class="title-kopare mb-5">
                                                Köpare
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column gap-1" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(28%);'">
                                            <h6 class="list-kopare text-neutral-3">
                                                Namn:
                                                <span>
                                                    {{ company.name }} {{ company.last_name }}
                                                </span>
                                            </h6>
                                              <h6 class="list-kopare text-neutral-3">
                                                Org/personummer:
                                                <span>
                                                    {{ company.organization_number }}
                                                </span>
                                            </h6>
                                            <h6 class="list-kopare text-neutral-3">
                                                Adress:
                                                <span>
                                                    {{ company.address }}
                                                </span>
                                            </h6>
                                            <h6 class="list-kopare text-neutral-3">
                                                Postnr. ort:
                                                <span>
                                                    {{ (company.street ?? '') + ' ' +  (company.postal_code ?? '') }}
                                                </span>
                                            </h6>
                                        </div>
                                        <div class="d-flex flex-column gap-1" :style="windowWidth < 1024 ? 'width: 100%;; margin-bottom: 8px;' : 'width: calc(45% - 12px);'">
                                            <h6 class="list-kopare text-neutral-3">
                                                Telefon:
                                                <span>
                                                    {{ company.phone }}
                                                </span>
                                            </h6>
                                            <h6 class="list-kopare text-neutral-3">
                                                E-post:
                                                <span>
                                                    {{ company.email }}
                                                </span>
                                            </h6>
                                            <h6 class="list-kopare text-neutral-3">
                                                Bilfirma:
                                                <span>
                                                    {{ company.company }}
                                                </span>
                                            </h6>
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Pris-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Fordonsinformation
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >                                        
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Inköpspris*" />
                                            <VTextField
                                                v-model="price"
                                                type="number"
                                                min="0"
                                                suffix="KR"
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
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Moms / VMB*" />
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
                                                suffix="KR"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Prix ex moms*" />
                                            <VTextField
                                                type="number"
                                                v-model="iva_sale_exclusive"
                                                min="0"
                                                disabled
                                                suffix="KR"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <div class="d-flex flex-column">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Har bilen Kredit/leasing?" />
                                                <VRadioGroup 
                                                    v-model="is_loan" 
                                                    inline 
                                                    class="radio-form ms-2 mt-3"
                                                    @update:modelValue="handleChange">
                                                    <VRadio
                                                        v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                        :key="index"
                                                        :label="radio"
                                                        :value="index"
                                                    />
                                                </VRadioGroup>
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel v-if="is_loan === 0" class="mb-1 text-body-2 text-high-emphasis" text="Kreditbelopp*" />
                                            <VLabel v-else class="mb-1 text-body-2 text-high-emphasis" text="Kreditbelopp" />
                                            <VTextField
                                                v-model="loan_amount"
                                                type="number"
                                                min="0"
                                                suffix="KR"
                                                :rules="conditionalRulesJa"
                                                :disabled="is_loan === 1 ? true : false"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel v-if="is_loan === 0" class="mb-1 text-body-2 text-high-emphasis" text="Kredit/leasinggivare*" />
                                            <VLabel v-else class="mb-1 text-body-2 text-high-emphasis" text="Kredit/leasinggivare" />
                                            <VTextField
                                                v-model="lessor"
                                                :rules="conditionalRulesJa"
                                                :disabled="is_loan === 1 ? true : false"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <div
                                                class="d-flex w-100 p-4 agreements-pill"
                                                :style="{ backgroundColor: '#D8FFE4', color: '#0C5B27' }"
                                            >
                                                <VIcon icon="custom-coins" :color="'#0C5B27'" size="24" class="mr-2" />
                                                <div class="agreements-pill-title">Restsumma</div>
                                                <div class="agreements-pill-value">{{ remaining_amount }} {{ currencies.filter(item => item.id === currency_id)[0].code }}</div>
                                            </div>
                                            
                                        </div>                                 
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <div class="d-flex flex-column">                                                
                                                <VLabel v-if="is_loan === 0" class="mb-1 text-body-2 text-high-emphasis" text="Restskulden löses av*" />
                                                <VLabel v-else class="mb-1 text-body-2 text-high-emphasis" text="Restskulden löses av" />
                                                <VRadioGroup 
                                                    v-model="settled_by" 
                                                    inline 
                                                    class="radio-form ms-2" 
                                                    @update:modelValue="handleChangeTwo">
                                                    <VRadio
                                                        v-for="(radio, index) in optionsSettled"
                                                        :key="index"
                                                        :label="radio"
                                                        :value="index"
                                                        :disabled="is_loan === 1 ? true : false"                                                        
                                                    />
                                                </VRadioGroup>
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel v-if="settled_by === 1" class="mb-1 text-body-2 text-high-emphasis" text="Typ av utbetalning till säljaren*" />
                                            <VLabel v-else class="mb-1 text-body-2 text-high-emphasis" text="Typ av utbetalning till säljaren" />
                                            <AppAutocomplete
                                                v-model="payment_type_id"
                                                :items="getPaymentTypes"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                :rules="conditionalRules"
                                                @update:modelValue="selectPaymentType"
                                                @click:clear="selectPaymentType"
                                                :disabled="settled_by !== 1 ? true : false"
                                            />
                                        </div>
                                        <div v-if="payment_type_id === 0" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Betalsätt*" />
                                            <VTextField
                                                v-model="payment_type"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel v-if="settled_by === 1" class="mb-1 text-body-2 text-high-emphasis" text="Namn på banken*" />
                                            <VLabel v-else class="mb-1 text-body-2 text-high-emphasis" text="Namn på banken" />
                                            <VTextField
                                                v-model="bank"
                                                :rules="conditionalRules"
                                                :disabled="settled_by !== 1 ? true : false"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel v-if="settled_by === 1" class="mb-1 text-body-2 text-high-emphasis" text="Clearing/kontonummer*" />
                                            <VLabel v-else class="mb-1 text-body-2 text-high-emphasis" text="Clearing/kontonummer" />
                                            <VTextField
                                                v-model="account"
                                                :rules="conditionalRules"
                                                :disabled="settled_by !== 1 ? true : false"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel v-if="settled_by === 1" class="mb-1 text-body-2 text-high-emphasis" text="Betalningsbeskrivning*" />
                                            <VLabel v-else class="mb-1 text-body-2 text-high-emphasis" text="Betalningsbeskrivning" />
                                            <VTextField
                                                v-model="description"
                                                :rules="conditionalRules"
                                                :disabled="settled_by !== 1 ? true : false"
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Villkor-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Villkor
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div class="w-100">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Övriga villkor inhämtas från mall" />
                                            <VTextarea
                                                v-model="terms_other_conditions"
                                                rows="3"
                                            />
                                        </div>
                                        <div class="w-100">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Övriga upplysningar" />
                                            <VTextarea
                                                v-model="terms_other_information"
                                                rows="3"
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>
                    </VWindow>
                </VCardText>

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
                            type="submit" 
                            :block="windowWidth < 1024"
                            class="btn-gradient"
                            :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                        >
                            <VIcon v-if="currentTab === 3" icon="custom-save"  size="24" />
                            {{ (currentTab === 3) ? 'Skapa' : 'Nästa' }}
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
                <VIcon size="16" icon="custom-f-cancel" />
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
<style lang="scss" scoped>
    :deep(.radio-form .v-input--density-comfortable), :deep(.v-radio) {
        --v-input-control-height: 0 !important;
    }

    :deep(.radio-form .v-selection-control__wrapper) {
        height: 20px !important;
    }

    :deep(.radio-form .v-icon--size-default) {
        font-size: calc(var(--v-icon-size-multiplier) * 1em) !important;
    }

    :deep(.radio-form .v-selection-control--dirty) {
        .v-selection-control__input > .v-icon {
            color: #00E1E2 !important;
        }
    }

    :deep(.radio-form .v-label) {
        color: #5D5D5D;
        font-size: 12px;
    }
</style>
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

    .list-kopare {
        font-size: 16px;
        line-height: 100%;
        font-weight: 700;

        span {
            font-weight: 400;
            font-size: 16px;
        }
    }
    
    .title-kopare {
        font-weight: 700;
        font-size: 24px;
        line-height: 100%;
        color: #878787;

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

    .v-tabs.agreements-tabs {
        .v-btn {
            min-width: 50px !important;
            .v-btn__content {
                font-size: 14px !important;
                color: #454545;
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

    .agreements-pills > div {
        flex: 1 1;
    }

    .agreements-pill {
        display: flex;
        align-items: center;
        padding: 16px;
        border-radius: 8px;
    }

    .agreements-pill-title {
        font-family: "Blauer Nue";
        font-weight: 400;
        font-size: 16px;
        line-height: 100%;
        margin-right: 4px;
    }

    .agreements-pill-value {
        font-family: "Blauer Nue";
        font-weight: 700;
        font-style: Bold;
        font-size: 16px;
        line-height: 100%;
    }

    @media (max-width: 991px) {
        .agreements-pills {
            flex-direction: column;
            gap: 8px;
        }

        .agreements-pill {
            padding: 8px 16px;
        }
    }
</style>

<style lang="scss">

    .border-card-comment {
        border: 1px solid #E7E7E7;
        border-radius: 16px !important;
    }

    .agreements-page .radio-form.v-radio-group .v-selection-control-group .v-radio:not(:last-child) {
        margin-inline-end: 12rem !important;

        @media (max-width: 991px) {
        margin-inline-end: 5rem !important;
        }
    }

    :deep(.right-drawer.v-navigation-drawer) {
        border-color: transparent !important;
        border-width: 0 !important;
        border-style: none !important;
        box-shadow: none !important;
    }

    :deep(.right-drawer.v-navigation-drawer .v-navigation-drawer__content) {
        border: none !important;
    }
</style>

<route lang="yaml">
    meta:
      action: create
      subject: agreements
</route>