<script setup>

import { useDisplay } from "vuetify";
import { formatNumber } from '@/@core/utils/formatters'
import { onBeforeRouteLeave } from 'vue-router';
import { emailValidator, requiredValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators';
import { useVehiclesStores } from '@/stores/useVehicles';
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { usePersonInfoStores } from '@/stores/usePersonInfo'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import router from '@/router'

const vehiclesStores = useVehiclesStores()
const companyInfoStores = useCompanyInfoStores()
const personInfoStores = usePersonInfoStores()

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const sectionEl = ref(null);
const emitter = inject("emitter")
const route = useRoute()

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const isRequestOngoing = ref(true)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref('tab-1')
const isMobile = ref(false)

const brands = ref([])
const models = ref([])
const currencies = ref([])
const carbodies = ref([])
const gearboxes = ref([])
const ivas = ref([])
const fuels = ref([])

const vehicle = ref(null)
const vehicle_id = ref(null)
const reg_num = ref('')
const mileage = ref(null)
const brand_id = ref(null)
const model_id = ref(null)
const generation = ref(null)
const car_body_id = ref(null)
const year = ref(null)
const control_inspection = ref(null)
const color = ref(null)
const fuel_id = ref(null)
const gearbox_id = ref(null)
const purchase_price = ref(null)
const iva_purchase_id = ref(null)
const sale_price = ref(null)
const sale_date = ref(null)
const iva_sale_id = ref(null)
const sale_comments = ref(null)
const purchase_date = ref(null)
const currency_id = ref(null)

const iva_sale_amount = ref(0)
const iva_sale_exclusive = ref(0)
const discount = ref(0)
const registration_fee = ref(0)
const total_sale = ref(0)

const organization_number = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const fullname = ref('')
const email = ref('')
const save_client = ref(true)
const disabled_client = ref(false)

const clients = ref([])
const client_id = ref(null)
const client_types = ref([])
const client_type_id = ref(null)
const identifications = ref([])
const identification_id = ref(null)

const isConfirmLeaveVisible = ref(false)
const initialData = ref(null)
const nextRoute = ref(null)
const allowNavigation = ref(false);
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);
const err = ref(null);

const currentData = computed(() => ({
    sale_price: sale_price.value,
    sale_date: sale_date.value,
    iva_sale_id: iva_sale_id.value,
    sale_comments: sale_comments.value,
    discount: discount.value,
    registration_fee: registration_fee.value,
    client_id: client_id.value,
    organization_number: organization_number.value,
    client_type_id: client_type_id.value,
    fullname: fullname.value,
    address: address.value,
    street: street.value,
    postal_code: postal_code.value,
    phone: phone.value,
    identification_id: identification_id.value,
    email: email.value,
    save_client: save_client.value
}))

const isDirty = computed(() => {
  if (!initialData.value) return false
  try {
    return JSON.stringify(currentData.value) !== JSON.stringify(initialData.value)
  } catch (e) {
    return true
  }
})

// Confirm-leave is handled further down (single guard).

const startDateTimePickerConfig = computed(() => {

    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(now.getDate() + 1);

    const formatToISO = (date) => date.toISOString().split('T')[0];

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

onMounted(async () => {

    checkIfMobile()

    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

const calculate = () => {
    const sale = Number(sale_price.value) || 0
    const fee = Number(registration_fee.value) || 0
    const disc = Number(discount.value) || 0
    const value = (sale + fee) - disc 

    if(iva_sale_id.value === 2)
        iva_sale_amount.value = ((Number(value) || 0) * 0.2)
    else
        iva_sale_amount.value = 0

    iva_sale_exclusive.value = value - iva_sale_amount.value
    total_sale.value = value
}

watch(() => sale_price.value, (val) => {
    calculate()
})

watch(() => iva_sale_id.value, (val) => {
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

    if(Number(route.params.id) && route.name === 'dashboard-admin-sold-id') {
        const data = await vehiclesStores.showVehicle(Number(route.params.id))
    
        vehicle.value = data.vehicle
        brands.value = data.brands
        models.value = data.models
        currencies.value = data.currencies
        carbodies.value = data.carbodies
        gearboxes.value = data.gearboxes
        ivas.value = data.ivas
        fuels.value = data.fuels
        clients.value = data.clients
        client_types.value = data.client_types
        identifications.value = data.identifications

        vehicle_id.value = vehicle.value.id
        reg_num.value = vehicle.value.reg_num

        mileage.value = vehicle.value.mileage
        generation.value = vehicle.value.generation
        car_body_id.value = vehicle.value.car_body_id
        year.value = vehicle.value.year
        control_inspection.value = vehicle.value.control_inspection
        color.value = vehicle.value.color
        fuel_id.value = vehicle.value.fuel_id
        gearbox_id.value = vehicle.value.gearbox_id
        purchase_price.value = vehicle.value.purchase_price
        iva_purchase_id.value = vehicle.value.iva_purchase_id
        purchase_date.value = vehicle.value.purchase_date
        currency_id.value = vehicle.value.currency_purchase_id ?? 1

        sale_price.value = vehicle.value.sale_price
        sale_date.value = formatDate(new Date())
        iva_sale_id.value = vehicle.value.iva_sale_id
        sale_comments.value = vehicle.value.sale_comments

        if(vehicle.value.model_id !== null) {
            let modelId = vehicle.value.model_id
            let brandId = models.value.filter(item => item.id === modelId)[0].brand.id
            brand_id.value = brandId
            model_id.value = vehicle.value.model_id
        }

        nextTick(() => {
             initialData.value = JSON.parse(JSON.stringify(currentData.value))
        })
    }

    isRequestOngoing.value = false
}

const formatDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0') // meses de 0 a 11
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

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

/**
 * Determines if the given number belongs to a company (starts with 5) or a person.
 * Swedish organization numbers start with 5, personal identity numbers do not.
 * @param {string} orgNumber
 * @returns {boolean}
 */
const isCompanyNumber = (orgNumber) => {
    const cleanNumber = (orgNumber || '').replace(/[\s\-]/g, '')
    return cleanNumber.startsWith('5')
}

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

const formatOrgNumber = () => {

    let numbers = organization_number.value.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    organization_number.value = numbers
}


const goToVehicles = () => {

    let data = {
        message: 'Aktie uppdaterad framgångsrikt.!',
        error: false
    }

    router.push({ name : 'dashboard-admin-stock'})

    emitter.emit('toast', data)                 

};

const sendVehicles = () => {

    let data = {
        message: 'Aktie uppdaterad framgångsrikt.!',
        error: false
    }

    router.push({
        name: "dashboard-admin-sold"
    });

    emitter.emit('toast', data)
};

const confirmLeave = () => {
    isConfirmLeaveVisible.value = false;
    allowNavigation.value = true;

    if (nextRoute.value) {
        router.push(nextRoute.value);
    }
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
    // Tab-1: Försäljningsuppgifter
    const hasTab1Errors = !sale_price.value || !sale_date.value || !iva_sale_id.value ||
        iva_sale_amount.value === null || iva_sale_exclusive.value === null ||
        discount.value === null || registration_fee.value === null || total_sale.value === null;

    // Tab-2: Kund
    const hasTab2Errors = !client_type_id.value || !identification_id.value ||
        !fullname.value || !organization_number.value || !address.value || !street.value ||
        !postal_code.value || !phone.value || !email.value;

    if (hasTab1Errors) {
        currentTab.value = 'tab-1';
        await nextTick();
        refForm.value?.validate();
        advisor.value = {
            type: 'warning',
            message: 'Vänligen fyll i alla obligatoriska fält i fliken Försäljningsuppgifter',
            show: true
        };
        setTimeout(() => { advisor.value = { type: '', message: '', show: false }; }, 3000);
        return;
    }
    if (hasTab2Errors) {
        currentTab.value = 'tab-2';
        await nextTick();
        refForm.value?.validate();
        advisor.value = {
            type: 'warning',
            message: 'Vänligen fyll i alla obligatoriska fält i fliken Kund',
            show: true
        };
        setTimeout(() => { advisor.value = { type: '', message: '', show: false }; }, 3000);
        return;
    }

    refForm.value?.validate().then(({ valid }) => {
        if (valid) {
            let formData = new FormData();
            formData.append('id', Number(route.params.id));
            formData.append('sale_price', sale_price.value);
            formData.append('sale_date', sale_date.value);
            formData.append('iva_sale_amount', iva_sale_amount.value);
            formData.append('iva_sale_exclusive', iva_sale_exclusive.value);
            formData.append('discount', discount.value);
            formData.append('registration_fee', registration_fee.value);
            formData.append('total_sale', total_sale.value);
            formData.append('iva_sale_id', iva_sale_id.value);
            formData.append('sale_comments', sale_comments.value);
            formData.append('save_client', save_client.value);

            formData.append('client_type_id', client_type_id.value);
            formData.append('identification_id', identification_id.value);
            formData.append('client_id', client_id.value);
            formData.append('fullname', fullname.value);
            formData.append('email', email.value);
            formData.append('organization_number', organization_number.value);
            formData.append('address', address.value);
            formData.append('street', street.value);
            formData.append('postal_code', postal_code.value);
            formData.append('phone', phone.value);

            isRequestOngoing.value = true;

            vehiclesStores.sendVehicle(formData)
                .then((res) => {
                    if (res.data.success) {
                        allowNavigation.value = true;

                        // Save current state so the dirty-check stops blocking navigation
                        initialData.value = JSON.parse(JSON.stringify(currentData.value));

                        skapatsDialog.value = true;
                    }
                    isRequestOngoing.value = false;
                })
                .catch((error) => {
                    /*let data = {
                        message: err.message,
                        error: true
                    };
                    router.push({ name: 'dashboard-admin-stock' });
                    emitter.emit('toast', data);*/

                    err.value = error;
                    inteSkapatsDialog.value = true;
                    isRequestOngoing.value = false;
                });
        }
    });
}

const getFlag = (currency_id) => {
    return currencies.value.filter(item => item.id === currency_id)[0].flag
}
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
    <section class="page-section stock-edit-page" ref="sectionEl">
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
            v-if="reg_num"
            ref="refForm"
            class="card-form"
            v-model="isFormValid"
            @submit.prevent="onSubmit">
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
                            Försäljningsuppgifter
                        </span>
                        <span 
                            class="d-flex subtitle-page justify-start">
                            {{ reg_num }}
                        </span>
                    </div>

                    <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

                    <div 
                        class="d-flex gap-4"
                        :class="windowWidth < 1024 ? 'w-100' : 'align-center'">
                        <VBtn 
                            class="btn-light w-auto" 
                            block
                            :to="{ name: 'dashboard-admin-stock' }">
                            <VIcon icon="custom-return" size="24" />
                            Tillbaka
                        </VBtn>
                        <VBtn
                            v-if="$can('edit', 'stock')"
                            class="btn-gradient"
                            block
                            type="submit"
                        >
                            <VIcon icon="custom-save"  size="24" />
                            Spara
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
                >
                    <VTab value="tab-1">
                        <VIcon size="24" icon="custom-autofordon" />
                        Fordon
                    </VTab>
                    <VTab value="tab-2">
                        <VIcon size="24" icon="custom-clients" />
                        Kund
                    </VTab>
                </VTabs>
                <VCardText class="px-0">
                    <VWindow v-model="currentTab" class="pt-3">
                        <!-- Fordon -->
                        <VWindowItem value="tab-1" class="px-md-0">
                            <VRow class="px-md-5">
                                <VCol cols="12" class="d-flex flex-column gap-6 pa-6 mb-5 card-info">
                                    <div class="title-tabs mb-5">
                                        Grundläggande, teknisk och prisinformation
                                    </div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <span>Reg nr</span>
                                            <VTextField :model-value="reg_num" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Miltal</span>
                                            <VTextField :model-value="`${mileage} Mil`" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Märke</span>
                                            <VTextField :model-value="brands.filter(item => item.id === brand_id)[0]?.name" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Modell</span>
                                            <VTextField :model-value="models.filter(item => item.id === model_id)[0]?.name" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Generation</span>
                                            <VTextField :model-value="generation" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Kaross</span>
                                            <VTextField :model-value="carbodies.filter(item => item.id === car_body_id)[0]?.name" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Årsmodell</span>
                                            <VTextField :model-value="year" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Inköpsdatum</span>
                                            <VTextField :model-value="purchase_date" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Inspektion av</span>
                                            <VTextField :model-value="control_inspection" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Färg</span>
                                            <VTextField :model-value="color" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Drivmedel</span>
                                            <VTextField :model-value="fuels.filter(item => item.id === fuel_id)[0]?.name" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Växellåda</span>
                                            <VTextField :model-value="gearboxes.filter(item => item.id === gearbox_id)[0]?.name" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>Inköpspris</span>
                                            <VTextField :model-value="`${purchase_price ?? 0} ${currencies.filter(item => item.id === currency_id)[0]?.code}`" disabled density="compact" variant="outlined"  />
                                        </div>
                                        <div class="info-item">
                                            <span>VMB / Moms</span>
                                            <VTextField :model-value="ivas.filter(item => item.id === iva_purchase_id)[0]?.name" disabled density="compact" variant="outlined"/>
                                        </div>
                                    </div>
                                </VCol>
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Försäljningsuppgifter
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap card-form"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Försäljningspris*" />
                                            <VTextField
                                                type="number"
                                                v-model="sale_price"
                                                min="0"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <AppAutocomplete
                                                v-model="currency_id"
                                                label="Valuta"
                                                disabled
                                                :items="currencies"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                clearable
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
                                            <AppAutocomplete
                                                v-model="iva_sale_id"
                                                label="VMB / Moms"
                                                :items="ivas"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                :rules="[requiredValidator]"/>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Varav moms" />
                                            <VTextField
                                                type="number"
                                                v-model="iva_sale_amount"
                                                min="0"
                                                disabled
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Prix ex moms" />
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
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Registreringsavgift*" />
                                            <VTextField
                                                type="number"
                                                v-model="registration_fee"
                                                min="0"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Försäljningsdag*" />
                                            <AppDateTimePicker
                                                :key="JSON.stringify(startDateTimePickerConfig)"
                                                v-model="sale_date"
                                                density="compact"
                                                :config="startDateTimePickerConfig"
                                                placeholder="Försäljningsdag"
                                                :rules="[requiredValidator]"
                                                clearable
                                            />
                                        </div>
                                        
                                        <div
                                            class="d-flex w-100 p-4 vehicles-pill"
                                            :style="{ backgroundColor: '#D8FFE4', color: '#0C5B27' }"
                                        >
                                            <VIcon icon="custom-coins" :color="'#0C5B27'" size="24" class="mr-2" />
                                            <div class="vehicles-pill-title">Totalpris</div>
                                            <div class="vehicles-pill-value">{{ formatNumber(total_sale) }} {{ currencies.filter(item => item.id === currency_id)[0].code }}</div>
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>
                        <!-- Kund -->
                        <VWindowItem value="tab-2" class="px-md-0">
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
                                            <AppAutocomplete
                                                v-model="client_id"
                                                label="Kunder"
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
                                                    style="flex: 1;"
                                                    :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                                                    minLength="11"
                                                    maxlength="13"
                                                    @input="formatOrgNumber()"
                                                />
                                                <VBtn
                                                    class="btn-light w-auto px-4"
                                                    @click="searchEntity"
                                                >
                                                    <VIcon icon="custom-search" size="24" />
                                                    Hämta
                                                </VBtn>
                                            </div>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <AppAutocomplete
                                                v-model="client_type_id"
                                                label="Köparen är*"
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
                                            <AppAutocomplete
                                                v-model="identification_id"
                                                label="Legitimation*"
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
                                        <div style="width: 100%">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Comments" />                                            
                                            <VTextField
                                                v-model="sale_comments"
                                            />
                                        </div>
                                        <div class="ms-2">
                                            <VCheckbox
                                                v-model="save_client"
                                                :readonly="disabled_client"
                                                color="primary"
                                                label="Spara kund?"
                                                class="w-100 text-center d-flex justify-start"
                                            />
                                        </div>
                                    </div>
                                </VCol>
                            </VRow>
                        </VWindowItem>
                    </VWindow>
                </VCardText>                
            </VCard>
        </VForm>
    
        <!-- 👉 Dialogs Section -->
        <VDialog
            v-model="skapatsDialog"
            persistent
            class="action-dialog dialog-big-icon"
        >
            <VBtn
                icon
                class="btn-white close-btn"
                @click="router.push({
                    name: 'dashboard-admin-sold'
                })"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>

            <VCard>
                <VCardText class="dialog-title-box big-icon justify-center pb-0">
                    <VIcon size="72" icon="custom-f-sedan" />
                </VCardText>
                <VCardText class="dialog-title-box justify-center">
                    <div class="dialog-title">Fordonet har markerats som sålt!</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    "Märke och modell" har flyttats från ditt lager till listan över sålda fordon.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="goToVehicles">
                        Stäng
                    </VBtn>
                    <VBtn class="btn-gradient" @click="sendVehicles"> Gå till sålda fordon </VBtn>
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
                    <div class="dialog-title">Kunde inte markera fordonet som sålt</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    Ett fel uppstod när fordonet skulle flyttas. Kontrollera uppgifterna och försök igen.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="showError">
                        Försök igen
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
      action: edit
      subject: stock
</route>