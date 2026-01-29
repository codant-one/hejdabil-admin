<script setup>

import { useDisplay } from "vuetify";
import { onBeforeRouteLeave } from 'vue-router';
import { requiredValidator, yearValidator, emailValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useAgreementsStores } from '@/stores/useAgreements'
import { useAuthStores } from '@/stores/useAuth'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useConfigsStores } from '@/stores/useConfigs'
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { usePersonInfoStores } from '@/stores/usePersonInfo'
import { useCarInfoStores } from '@/stores/useCarInfo'
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

const isConfirmLeaveVisible = ref(false)
const nextRoute = ref(null)
const initialData = ref(null)
const allowNavigation = ref(false)
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);

const route = useRoute()
const authStores = useAuthStores()
const agreementsStores = useAgreementsStores()
const configsStores = useConfigsStores()
const companyInfoStores = useCompanyInfoStores()
const personInfoStores = usePersonInfoStores()
const carInfoStores = useCarInfoStores()
const ability = useAppAbility()
const emitter = inject("emitter")
const err = ref(null);

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
const agreement = ref(null)

//const tab 1
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

// Recargar la página al crear otro acuerdo
function reloadPage() {
  window.location.reload();
}

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

    if(Number(route.params.id) && route.name === 'dashboard-admin-agreements-mediation-edit-id') {
        isRequestOngoing.value = true

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

            bank_name.value = user_data.user_detail.bank
            account_number.value = user_data.user_detail.account_number

            commission_id.value = user_data.supplier.user.commissions.length + 1
        } else if(role.value === 'User') {
            company.value = user_data.supplier.boss.user.user_detail
            company.value.email = user_data.supplier.boss.user.email
            company.value.name = user_data.supplier.boss.user.name
            company.value.last_name = user_data.supplier.boss.user.last_name

            bank_name.value = user_data.supplier.boss.user.user_detail.bank
            account_number.value = user_data.supplier.boss.user.user_detail.account_number

            commission_id.value = user_data.supplier.boss.user.commissions.length + 1
        } else {
            await configsStores.getFeature('company')
            await configsStores.getFeature('logo')

            company.value = configsStores.getFeaturedConfig('company')
            company.value.logo = configsStores.getFeaturedConfig('logo').logo

            bank_name.value = company.value.bank
            account_number.value = company.value.account_number

            commission_id.value = agreementsStores.commission_id + 1
        }

        await agreementsStores.info()

        agreement.value = await agreementsStores.showAgreement(Number(route.params.id))

        brands.value = agreementsStores.brands
        models.value = agreementsStores.models 
        carbodies.value = agreementsStores.carbodies
        gearboxes.value = agreementsStores.gearboxes
        fuels.value = agreementsStores.fuels
        currencies.value = agreementsStores.currencies
        clients.value = agreementsStores.clients
        client_types.value = agreementsStores.client_types
        identifications.value = agreementsStores.identifications
        commission_types.value = agreementsStores.commission_types

        client_type_id.value = agreement.value.commission.client.client_type_id
        identification_id.value = agreement.value.commission.client.identification_id
        organization_number.value = agreement.value.commission.client.organization_number
        address.value = agreement.value.commission.client.address
        street.value = agreement.value.commission.client.street
        postal_code.value = agreement.value.commission.client.postal_code
        phone.value = agreement.value.commission.client.phone
        fullname.value = agreement.value.commission.client.fullname
        email.value = agreement.value.commission.client.email
        
        reg_num.value = agreement.value.commission.vehicle.reg_num
        year.value = agreement.value.commission.vehicle.year
        color.value = agreement.value.commission.vehicle.color
        chassis.value = agreement.value.commission.vehicle.chassis
        mileage.value = agreement.value.commission.vehicle.mileage
        gearbox_id.value = agreement.value.commission.vehicle.gearbox_id
        fuel_id.value = agreement.value.commission.vehicle.fuel_id
        number_keys.value = agreement.value.commission.vehicle.number_keys
        service_book.value = agreement.value.commission.vehicle.service_book
        summer_tire.value = agreement.value.commission.vehicle.summer_tire
        winter_tire.value = agreement.value.commission.vehicle.winter_tire
        comments.value = agreement.value.commission.vehicle.comments

        if(agreement.value.commission.vehicle.model_id !== null) {
            let modelId = agreement.value.commission.vehicle.model_id
            let brandId = models.value.filter(item => item.id === modelId)[0].brand.id
            selectBrand(brandId)
            brand_id.value = brandId
            model_id.value = agreement.value.commission.vehicle.model_id
        } 

        commission_id.value = agreement.value.commission.commission_id
        commission_type_id.value = agreement.value.commission.commission_type_id
        commission_fee.value = formatDecimal(agreement.value.commission.commission_fee)
        outstanding_debt.value = agreement.value.commission.outstanding_debt
        remaining_debt.value = agreement.value.commission.remaining_debt
        paid_bank.value = agreement.value.commission.paid_bank
        selling_price.value = formatDecimal(agreement.value.commission.selling_price)
        residual_debt.value = agreement.value.commission.residual_debt
        payment_days.value = agreement.value.commission.payment_days
        payment_description.value = agreement.value.commission.payment_description
        start_date.value = agreement.value.commission.start_date
        end_date.value = agreement.value.commission.end_date

        terms_other_conditions.value = agreement.value.terms_other_conditions
        terms_other_information.value = agreement.value.terms_other_information

        isRequestOngoing.value = false

        nextTick(() => {
            initialData.value = JSON.parse(JSON.stringify(currentData.value))
        })
    }
}

const formatDecimal = (value) => {
    const number = parseFloat(value);

    if (number % 1 !== 0) {
        return number.toFixed(2);
    }

    return number.toString();
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

const clearClient = () => {
    fullname.value = null
    email.value = null
    organization_number.value = null
    address.value = null
    street.value = null
    postal_code.value = null
    phone.value = null
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
    }
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

/**
 * Search for entity information based on the organization/personal number.
 * If the number starts with 5, searches in CompanyInfo (Bolagsverket).
 * Otherwise, searches in SPAR (Statens Personadressregister).
 */
const searchEntity = async () => {
    if (!organization_number.value) return

    if (isCompanyNumber(organization_number.value)) {
        await searchCompany()
    } else {
        await searchPerson()
    }
}

const searchCompany = async () => {
  try {
    isRequestOngoing.value = true

    const response = await companyInfoStores.getCompanyInfo(organization_number.value)
    
    isRequestOngoing.value = false

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

        // Set Street/City (Postort)
        if (response.postadressOrganisation?.postadress?.postort) {
            street.value = response.postadressOrganisation.postadress.postort
        } else {
            street.value = ''
        }
    }

  } catch (error) {
      isRequestOngoing.value = false
      advisor.value = {
          type: 'error',
          message: 'Ingen företag hittades med det registreringsnumret',
          show: true
      }
  }
}

/**
 * Search for person information in SPAR (Statens Personadressregister) API.
 */
const searchPerson = async () => {
  try {
    isRequestOngoing.value = true

    const response = await personInfoStores.getPersonInfo(organization_number.value)

    isRequestOngoing.value = false

    if (response?.success && response?.data) {
        const personData = response.data

        // Set Client Type to Privat
        const privatType = client_types.value.find(t => t.name === 'Privat')
        if (privatType) {
            client_type_id.value = privatType.id
        }

        // Set Name
        fullname.value = personData.fullname || ''

        // Set Postal Code
        postal_code.value = personData.postnummer || ''

        // Set Address
        address.value = personData.adress || ''

        // Set Street/City (Postort)
        street.value = personData.postort || ''
    }

  } catch (error) {
    isRequestOngoing.value = false

    const errorMessage = error?.response?.data?.message || 'Ingen person hittades med det personnumret'
    
    advisor.value = {
        type: 'error',
        message: errorMessage,
        show: true
    }
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

const goToAgreements = () => {

  let data = {
      message: 'Förmedlingsavtal framgångsrikt skapat',
      error: false
  }

  router.push({ name : 'dashboard-admin-agreements'})
  emitter.emit('toast', data)  

};

const showError = () => {
    inteSkapatsDialog.value = false;

    advisor.value.show = true;
    advisor.value.type = "error";
    
    if (err.value && err.value.response && err.value.response.data && err.value.response.data.errors) {
      advisor.value.message = Object.values(err.value.response.data.errors)
                .flat()
                .join("<br>");
    } else {
      advisor.value.message = "Ett serverfel uppstod. Försök igen.";
    }

    setTimeout(() => {
      advisor.value.show = false;
      advisor.value.type = "";
      advisor.value.message = "";
    }, 3000);

};

const onSubmit = async () => {
    // Validación manual ANTES de usar VForm.validate()
    // Verificar tab 0 (Kund)
    const hasTab0Errors = !organization_number.value || 
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

    // Verificar tab 1 (Fordonsinformation)
    const hasTab1Errors = !reg_num.value || 
                          !brand_id.value || 
                          (model_id.value !== 0 && !model_id.value) || // si no es 0 y está vacío → error
                          (model_id.value === 0 && !model.value) || // si es 0, el campo texto debe tener valor
                          !year.value ||
                          !chassis.value ||
                          !mileage.value || 
                          !fuel_id.value ||
                          !gearbox_id.value ||
                          !number_keys.value

    // Verificar tab 2 (Förmedlingsavgift)
    const hasTab2Errors = !commission_type_id.value || 
                          !commission_fee.value ||
                          !selling_price.value

    // Verificar tab 3 (Betalningsinformation)
    const hasTab3Errors = !payment_days.value

    // Verificar tab 4 (Förmedlingsdatum)
    const hasTab4Errors = !start_date.value || 
                          !end_date.value

    // Tab 5 (Tillägg) no tiene campos obligatorios

    // Lógica de navegación entre tabs (0, 1, 2, 3, 4)
    if (currentTab.value === 0) {
        if (hasTab0Errors) {
            // Validar el formulario para mostrar errores visuales
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
        } else {
            // Avanzar al siguiente tab
            currentTab.value++
            return
        }
    }
    
    if (currentTab.value === 1) {
        if (hasTab1Errors) {
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Fordonsinformation',
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
        } else {
            // Avanzar al siguiente tab
            currentTab.value++
            return
        }
    }

    if (currentTab.value === 2) {
        if (hasTab2Errors) {
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Förmedlingsavgift',
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
        } else {
            // Avanzar al siguiente tab
            currentTab.value++
            return
        }
    }

    if (currentTab.value === 3) {
        if (hasTab3Errors) {
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Betalningsinformation',
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
        } else {
            // Avanzar al siguiente tab
            currentTab.value++
            return
        }
    }

    if (currentTab.value === 4) {
        if (hasTab4Errors) {
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Förmedlingsdatum',
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
        } else {
            // Avanzar al siguiente tab
            currentTab.value++
            return
        }
    }

    // Si estamos en el último tab (5), verificar TODOS los tabs antes de enviar
    if (currentTab.value === 5) {
        // Si hay errores en tabs anteriores, regresar al primero con error
        if (hasTab0Errors) {
            currentTab.value = 0
            
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
        
        if (hasTab1Errors) {
            currentTab.value = 1
            
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Fordonsinformation',
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

        if (hasTab2Errors) {
            currentTab.value = 2
            
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Förmedlingsavgift',
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

        if (hasTab3Errors) {
            currentTab.value = 3
            
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Betalningsinformation',
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

        if (hasTab4Errors) {
            currentTab.value = 4
            
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Förmedlingsdatum',
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

        // Si no hay errores en ningún tab, proceder con el submit final
        refForm.value?.validate().then(({ valid: isValid }) => {
            if (isValid) {
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
                formData.append('commission_id', agreement.value.commission_id)
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

                let data = {
                    data: formData, 
                    id: Number(route.params.id)
                }

                agreementsStores.updateAgreement(data)
                    .then((res) => {
                        if (res.data.success) {                            
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
                    .catch((error) => {
                        err.value = error;
                        initialData.value = JSON.parse(JSON.stringify(currentData.value));
                        inteSkapatsDialog.value = true;
                        isRequestOngoing.value = false
                    })
            }
        })
    }
}

const currentData = computed(() => ({
    // Tab 1: Cliente
    client_id: client_id.value,
    organization_number: organization_number.value,
    client_type_id: client_type_id.value,
    fullname: fullname.value,
    address: address.value,
    postal_code: postal_code.value,
    street: street.value,
    phone: phone.value,
    identification_id: identification_id.value,
    email: email.value,

    // Tab 2: Vehículo
    reg_num: reg_num.value,
    brand_id: brand_id.value,
    model_id: model_id.value,
    model: model.value,
    year: year.value,
    color: color.value,
    chassis: chassis.value,
    mileage: mileage.value,
    fuel_id: fuel_id.value,
    gearbox_id: gearbox_id.value,
    number_keys: number_keys.value,
    service_book: service_book.value,
    summer_tire: summer_tire.value,
    winter_tire: winter_tire.value,
    comments: comments.value,

    // Tab 3: Comisión
    commission_type_id: commission_type_id.value,
    commission_fee: commission_fee.value,
    outstanding_debt: outstanding_debt.value,
    remaining_debt: remaining_debt.value,
    paid_bank: paid_bank.value,
    selling_price: selling_price.value,
    residual_debt: residual_debt.value,

    // Tab 4: Pago
    bank_name: bank_name.value,
    payment_days: payment_days.value,
    account_number: account_number.value,
    payment_description: payment_description.value,

    // Tab 5: Fechas
    start_date: start_date.value,
    end_date: end_date.value,

    // Tab 6: Términos
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
                                Förmedlingsavtal
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
                    grow             
                    :show-arrows="false"
                    class="agreements-tabs" 
                >
                    <VTab :class="{ 'tab-completed': currentTab > 0 }">
                        <VIcon size="24" icon="custom-clients" />
                        Kund
                    </VTab>
                    <VTab :class="{ 'tab-completed': currentTab > 1 }">
                        <VIcon size="24" icon="custom-car" />
                        Fordonsinformation
                    </VTab>
                    <VTab :class="{ 'tab-completed': currentTab > 2 }">
                        <VIcon size="24" icon="custom-pris-information" />
                        Förmedlingsavgift
                    </VTab>
                    <VTab :class="{ 'tab-completed': currentTab > 3 }">
                        <VIcon size="24" icon="custom-cash-2" />
                        Betalningsinformation
                    </VTab>
                    <VTab :class="{ 'tab-completed': currentTab > 4 }">
                        <VIcon size="24" icon="custom-calendar" />
                        Förmedlingsdatum
                    </VTab>
                    <VTab :class="{ 'tab-completed': currentTab > 5 }">
                        <VIcon size="24" icon="custom-add-column" />
                        Tillägg
                    </VTab>
                </VTabs>

                <VCardText class="px-0 px-md-2">
                    <VWindow v-model="currentTab">
                        <!--Kund-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Fordonsägare
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

                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column gap-1' : 'flex-row gap-4'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%; margin-bottom: 8px;' : 'width: calc(20%);'">
                                            <span class="title-kopare mb-5">
                                                Förmedlare
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

                        <!--Fordonsinformation-->
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
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Reg nr*" />
                                            <div class="d-flex gap-2"> 
                                                <VTextField
                                                    v-model="reg_num"
                                                    :rules="[requiredValidator]"
                                                    @input="reg_num = reg_num.toUpperCase()"
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
                                                :rules="[yearValidator, requiredValidator]"
                                            />   
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Färg" />
                                            <VTextField
                                                v-model="color"
                                            /> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Chassinummer*" />
                                            <VTextField
                                                v-model="chassis"
                                                :rules="[requiredValidator]"
                                            /> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Miltal*" />
                                            <VTextField
                                                type="number"
                                                v-model="mileage"
                                                suffix="Mil"
                                                :rules="[requiredValidator]"
                                                min="0"
                                            /> 
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Drivmedel*" />
                                            <AppAutocomplete
                                                v-model="fuel_id"
                                                :items="fuels"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :rules="[requiredValidator]"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"/>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Växellåda*" />
                                            <AppAutocomplete
                                                v-model="gearbox_id"
                                                :items="gearboxes"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :rules="[requiredValidator]"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Antal nycklar*" />
                                            <VTextField
                                                v-model="number_keys"
                                                type="number"
                                                :rules="[requiredValidator]"
                                                min="1"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(32% - 12px);'">
                                            <div class="d-flex flex-column">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text=" Servicebok finns?" />
                                                <VRadioGroup v-model="service_book" inline class="radio-form">
                                                    <VRadio
                                                        v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                        :key="index"
                                                        :label="radio"
                                                        :value="index"
                                                    />
                                                </VRadioGroup>
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(32% - 12px);'">                                                
                                            <div class="d-flex flex-column">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Sommardäck finns?" />
                                                <VRadioGroup v-model="summer_tire" inline class="radio-form">
                                                    <VRadio
                                                        v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                        :key="index"
                                                        :label="radio"
                                                        :value="index"
                                                    />
                                                </VRadioGroup>
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(32% - 12px);'">                                                
                                            <div class="d-flex flex-column">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vinterdäck finns?" />
                                                <VRadioGroup v-model="winter_tire" inline class="radio-form">
                                                    <VRadio
                                                        v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                        :key="index"
                                                        :label="radio"
                                                        :value="index"
                                                    />
                                                </VRadioGroup>
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kända fel, brister och övrig information" />
                                            <VTextarea
                                                v-model="comments"
                                                rows="3"
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Förmedlingsavgift-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Förmedlingsavgift
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Typ av provision*" />
                                            <AppAutocomplete
                                                v-model="commission_type_id"
                                                :items="commission_types"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :rules="[requiredValidator]"
                                                autocomplete="off"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" :text="'Provisionsavgift ' + (currencies.find(item => item.id === currency_id)?.code || '') + '*'" />
                                            <VTextField
                                                v-model="commission_fee"
                                                type="number"
                                                min="0"
                                                suffix="KR"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>                                        
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" :text="'Försäljningspris ' + (currencies.find(item => item.id === currency_id)?.code || '') + '*'" />
                                            <VTextField
                                                v-model="selling_price"
                                                type="number"
                                                min="0"
                                                suffix="KR"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <div class="d-flex flex-column">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Har fordonet restskuld" />
                                                <VRadioGroup 
                                                    v-model="outstanding_debt" 
                                                    inline 
                                                    class="radio-form mt-2">
                                                    <VRadio
                                                        v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                        :key="index"
                                                        :label="radio"
                                                        :value="index"
                                                    />
                                                </VRadioGroup>
                                            </div>
                                        </div>
                                        <div v-if="outstanding_debt === 0" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'" >
                                            <div class="d-flex flex-column">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Restskulden löses av" />
                                                <VRadioGroup 
                                                    v-model="residual_debt" 
                                                    inline 
                                                    class="radio-form mt-2">
                                                    <VRadio
                                                        v-for="(radio, index) in optionsSettled"
                                                        :key="index"
                                                        :label="radio"
                                                        :value="index"
                                                    />
                                                </VRadioGroup>
                                            </div>
                                        </div>
                                        <div v-if="outstanding_debt === 0" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" :text="'Restskuld ' + (currencies.find(item => item.id === currency_id)?.code || '') + '*'" />
                                            <VTextField
                                                v-model="remaining_debt"
                                                type="number"
                                                min="0"
                                                suffix="KR"
                                            />
                                        </div>
                                        <div v-if="outstanding_debt === 0" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Restskuld betalas till*" />
                                            <VTextField
                                                v-model="paid_bank"
                                            />
                                        </div>                                          
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Betalningsinformation-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Betalningsinformation
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >                                         
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bankens namn" />
                                            <VTextField
                                                v-model="bank_name"
                                                disabled
                                            />
                                        </div>                                          
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kontonummer" />
                                            <VTextField
                                                v-model="account_number"
                                                disabled
                                            />
                                        </div>                                          
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Utbetalning antal bankdagar efter försäljning*" />
                                            <VTextField
                                                v-model="payment_days"
                                                type="number"
                                                min="0"
                                                max="30"
                                                :rules="[requiredValidator]"
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

                        <!--Förmedlingsdatum-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Förmedlingsdatum och giltighetstid
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Startdatum*" />
                                            <AppDateTimePicker
                                                v-model="start_date"
                                                :rules="[requiredValidator]"
                                                :config="{
                                                    dateFormat: 'Y-m-d',
                                                    position: 'auto right'
                                                }"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Slutdatum*" />
                                            <AppDateTimePicker
                                                v-model="end_date"
                                                :rules="[requiredValidator]"
                                                :config="endDateTimePickerConfig"
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Tillägg-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Tillägg
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div class="w-100">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Övriga upplysningar" />
                                            <VTextarea
                                                v-model="terms_other_conditions"
                                                rows="4"
                                                counter="560"
                                                maxlength="560"
                                            />
                                        </div>
                                        <div class="w-100">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Övriga villkor" />
                                            <VTextarea
                                                v-model="terms_other_information"
                                                rows="4"
                                                counter="560"
                                                maxlength="560"
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
                            <VIcon v-if="currentTab === 5" icon="custom-save"  size="24" />
                            {{ (currentTab === 5) ? 'Uppdatering' : 'Nästa' }}
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
                    <div class="dialog-title">Avtalet har uppdaterats!</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    Ditt avtal har uppdaterats och ändringarna har sparats.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                <VBtn class="btn-light" @click="goToAgreements">
                    Gå till avtalslistan
                </VBtn>
                <VBtn class="btn-gradient" @click="reloadPage">
                    Redigera avtal
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
                    <VBtn class="btn-light" @click="showError">
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
            pointer-events: none;
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

                    .v-text-field__prefix {
                        padding-top: 12px !important  ;
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
      action: edit
      subject: agreements
</route>