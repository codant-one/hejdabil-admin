<script setup>

import { useDisplay } from "vuetify";
import { onBeforeRouteLeave } from 'vue-router';
import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators';
import { useVehiclesStores } from '@/stores/useVehicles';
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router';

import iconFordon from "@/assets/images/iconify-svg/autofordon.svg";
import iconKund from "@/assets/images/iconify-svg/clients.svg";
import iconReturn from "@/assets/images/iconify-svg/return.svg";
import iconSave from "@/assets/images/iconify-svg/save.svg";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const { width: windowWidth } = useWindowSize();

const vehiclesStores = useVehiclesStores()

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

onBeforeRouteLeave((to, from, next) => {
    if (isDirty.value) {
        isConfirmLeaveVisible.value = true
        nextRoute.value = next
        return
    }
    next()
})

const reallyCloseAndReset = () => {
    if (nextRoute.value) {
        initialData.value = JSON.parse(JSON.stringify(currentData.value))
        nextRoute.value()
    }
}

const { mdAndDown } = useDisplay();

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


const onSubmit = () => {
    refForm.value?.validate().then(({ valid }) => {
        if (valid && currentTab.value === 0 && refForm.value.items.length < 12) {
            currentTab.value++
        } else if (valid && currentTab.value === 1) {
            let formData = new FormData()
            
            formData.append('id', Number(route.params.id))
            formData.append('sale_price', sale_price.value)
            formData.append('sale_date', sale_date.value)
            formData.append('iva_sale_amount', iva_sale_amount.value)
            formData.append('iva_sale_exclusive', iva_sale_exclusive.value)
            formData.append('discount', discount.value)
            formData.append('registration_fee', registration_fee.value)
            formData.append('total_sale', total_sale.value) 
            formData.append('iva_sale_id', iva_sale_id.value)
            formData.append('sale_comments', sale_comments.value)
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

            isRequestOngoing.value = true

            vehiclesStores.sendVehicle(formData)
                .then((res) => {
                    if (res.data.success) {
                        
                        let data = {
                            message: 'Aktie uppdaterad framgångsrikt.!',
                            error: false
                        }

                        router.push({ name : 'dashboard-admin-sold'})
                        emitter.emit('toast', data)
                    }
                    isRequestOngoing.value = false
                })
                .catch((err) => {
                    
                    let data = {
                        message: err.message,
                        error: true
                    }

                    router.push({ name : 'dashboard-admin-stock'})
                    emitter.emit('toast', data)

                    isRequestOngoing.value = false
                })
        }
    })
}

const getFlag = (currency_id) => {
    return currencies.value.filter(item => item.id === currency_id)[0].flag
}
</script>

<template>
    <div>
    <section>
        <LoadingOverlay :is-loading="isRequestOngoing" />
        <VAlert
            v-if="advisor.show"
            :type="advisor.type"
            class="mb-6">
                
            {{ advisor.message }}
        </VAlert>
        <VForm
            v-if="reg_num"
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit">
            <VCard class="card-fill">
                <VCardTitle
                    class="d-flex gap-6 justify-space-between"
                    :class="[
                    windowWidth < 1024 ? 'flex-column' : 'flex-row',
                    $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
                    ]"
                >
                    <div class="align-center font-blauer">
                    <h2>Försäljningsuppgifter</h2>
                    </div>

                    <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

                    <div class="d-flex gap-4">
                        <VBtn 
                            class="btn-light w-auto" 
                            block
                            :to="{ name: 'dashboard-admin-stock' }">
                            <img :src="iconReturn" alt="Fordon" class="me-2" width="24" height="24" />
                            Tillbaka
                        </VBtn>
                        <VBtn
                            v-if="$can('edit', 'stock')"
                            class="btn-gradient"
                            block
                            type="submit"
                        >
                            <img :src="iconSave" alt="Fordon" class="me-2" width="24" height="24" />
                            Spara
                        </VBtn>
                    </div>
                </VCardTitle>

                <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

                <VRow>
                    <VCol cols="12" md="12">              
                        <VCard flat class="px-2 px-md-12">
                            <VCardText class="px-2 pt-0 pt-md-5">                
                                <VTabs v-model="currentTab" class="v-tabs-pill" align-tabs="start">
                                    <VTab value="tab-1" class="v-tabs-pill" align-tabs="start">
                                        <img :src="iconFordon" alt="Fordon" class="me-2" width="24" height="24" />
                                        Fordon
                                    </VTab>
                                    <VTab value="tab-2">
                                        <img :src="iconKund" alt="Kund" class="me-2" width="24" height="24" />
                                        Kund
                                    </VTab>
                                </VTabs>
                                <VCardText class="px-0 px-md-2">
                                    <VWindow v-model="currentTab" class="pt-3">
                                        <!-- Fordon -->
                                        <VWindowItem value="tab-1" class="px-md-5">
                                            <VRow class="px-md-5">
                                                <VCol cols="12" class="pa-4 rounded-lg mb-5" style="background-color: #F5F5F5 !important;">
                                                    <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                        Grundläggande, teknisk och prisinformation
                                                    </h6>
                                                    <VList class="card-list mt-2" id="miLista" bg-color="#F5F5F5">
                                                        <VListItem style="background-color: #F5F5F5 !important;"   >
                                                            <VRow>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Reg nr:
                                                                        </h6>
                                                                        <VTextField :model-value="reg_num" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Miltal:
                                                                        </h6>
                                                                        <VTextField :model-value="`${mileage} Mil`" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Märke:
                                                                        </h6>
                                                                        <VTextField :model-value="brands.filter(item => item.id === brand_id)[0]?.name" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Modell:
                                                                        </h6>
                                                                        <VTextField :model-value="models.filter(item => item.id === model_id)[0]?.name" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Generation:
                                                                        </h6>
                                                                        <VTextField :model-value="generation" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Kaross:
                                                                        </h6>
                                                                        <VTextField :model-value="carbodies.filter(item => item.id === car_body_id)[0]?.name" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Årsmodell:
                                                                        </h6>
                                                                        <VTextField :model-value="year" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Inköpsdatum:
                                                                        </h6>
                                                                        <VTextField :model-value="purchase_date" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Kontrollbesiktning gäller tom:
                                                                        </h6>
                                                                        <VTextField :model-value="control_inspection" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Färg:
                                                                        </h6>
                                                                        <VTextField :model-value="color" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Drivmedel:
                                                                        </h6>
                                                                        <VTextField :model-value="fuels.filter(item => item.id === fuel_id)[0]?.name" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Växellåda:
                                                                        </h6>
                                                                        <VTextField :model-value="gearboxes.filter(item => item.id === gearbox_id)[0]?.name" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>



                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            Inköpspris:
                                                                        </h6>
                                                                        <VTextField :model-value="`${purchase_price ?? 0} ${currencies.filter(item => item.id === currency_id)[0]?.code}`" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                                <VCol cols="12" md="4" lg="2">
                                                                    <VListItemTitle class="h-auto mb-2">
                                                                        <h6 class="text-base font-weight-semibold mb-1">
                                                                            VMB / Moms:
                                                                        </h6>
                                                                        <VTextField :model-value="ivas.filter(item => item.id === iva_purchase_id)[0]?.name" disabled density="compact" variant="outlined" hide-details bg-color="#F5F5F5" />
                                                                    </VListItemTitle>
                                                                </VCol>
                                                            </VRow>
                                                        </VListItem>
                                                    </VList>
                                                </VCol>
                                                <VCol cols="12">
                                                    <h6 class="text-md-h4 text-h6 font-weight-medium my-5">
                                                        Försäljningsuppgifter
                                                    </h6>
                                                    <VRow>
                                                        <VCol cols="12" md="6">
                                                            <VTextField
                                                                type="number"
                                                                v-model="sale_price"
                                                                label="Försäljningspris"
                                                                min="0"
                                                                bg-color="#F5F5F5"
                                                                :rules="[requiredValidator]"
                                                            />
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VAutocomplete
                                                                v-model="currency_id"
                                                                label="Valuta"
                                                                disabled
                                                                :items="currencies"
                                                                :item-title="item => item.name"
                                                                :item-value="item => item.id"
                                                                autocomplete="off"
                                                                clearable
                                                                bg-color="#F5F5F5"
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
                                                                v-model="iva_sale_id"
                                                                label="VMB / Moms"
                                                                :items="ivas"
                                                                :item-title="item => item.name"
                                                                :item-value="item => item.id"
                                                                autocomplete="off"
                                                                clearable
                                                                bg-color="#F5F5F5"
                                                                clear-icon="tabler-x"
                                                                :rules="[requiredValidator]"/>
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VTextField
                                                                type="number"
                                                                v-model="iva_sale_amount"
                                                                label="Varav moms"
                                                                min="0"
                                                                bg-color="#F5F5F5"
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
                                                                bg-color="#F5F5F5"
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
                                                                bg-color="#F5F5F5"
                                                                :rules="[requiredValidator]"
                                                            />
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VTextField
                                                                type="number"
                                                                v-model="registration_fee"
                                                                label="Registreringsavgift"
                                                                min="0"
                                                                bg-color="#F5F5F5"
                                                                :rules="[requiredValidator]"
                                                            />
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <AppDateTimePicker
                                                                :key="JSON.stringify(startDateTimePickerConfig)"
                                                                v-model="sale_date"
                                                                density="compact"
                                                                :config="startDateTimePickerConfig"
                                                                label="Försäljningsdag"
                                                                :rules="[requiredValidator]"
                                                                clearable
                                                                bg-color="#F5F5F5"
                                                            />
                                                        </VCol>
                                                        <VCol cols="12" md="12" class="text-end">
                                                            <span>Totalpris: {{ total_sale }} {{ currencies.filter(item => item.id === currency_id)[0].code }}</span>
                                                        </VCol>
                                                    </VRow>
                                                </VCol>
                                            </VRow>
                                        </VWindowItem>
                                        <!-- Kund -->
                                        <VWindowItem value="tab-2" class="px-md-5">
                                            <VRow class="px-md-5">
                                                <VCol cols="12">
                                                    <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                        Köpare
                                                    </h6>
                                                    <VRow>
                                                        <VCol cols="12" md="6">
                                                            <VAutocomplete
                                                                v-model="client_id"
                                                                label="Kunder"
                                                                :items="clients"
                                                                :item-title="item => item.fullname"
                                                                :item-value="item => item.id"
                                                                autocomplete="off"
                                                                clearable
                                                                bg-color="#F5F5F5"
                                                                @click:clear="clearClient"
                                                                @update:modelValue="selectClient"/>
                                                        </VCol>
                                                        <VCol cols="12" md="6" class="d-flex align-items-end">
                                                            <VTextField
                                                                v-model="organization_number"
                                                                label="Org/personummer"
                                                                :rules="[requiredValidator]"
                                                                bg-color="#F5F5F5"
                                                            />

                                                            <VBtn 
                                                                variant="outlined" 
                                                                color="secondary"
                                                                class="ms-2 px-4" 
                                                                style="border-color: #BDBDBD; height: 39px !important;">
                                                                <VIcon icon="tabler-search" class="me-2" />
                                                                Hämta
                                                            </VBtn>
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VAutocomplete
                                                                v-model="client_type_id"
                                                                label="Köparen är"
                                                                :items="client_types"
                                                                :item-title="item => item.name"
                                                                :item-value="item => item.id"
                                                                :rules="[requiredValidator]"
                                                                autocomplete="off"
                                                                bg-color="#F5F5F5"/>
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VTextField
                                                                v-model="fullname"
                                                                label="Namn"
                                                                :rules="[requiredValidator]"
                                                                bg-color="#F5F5F5"
                                                            />
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VTextField
                                                                v-model="address"
                                                                :rules="[requiredValidator]"
                                                                label="Adress"
                                                                bg-color="#F5F5F5"
                                                            />
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VTextField
                                                                v-model="postal_code"
                                                                :rules="[requiredValidator]"
                                                                label="Postnummer"
                                                                bg-color="#F5F5F5"
                                                            />
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VTextField
                                                                v-model="street"
                                                                :rules="[requiredValidator]"
                                                                label="Stad"
                                                                bg-color="#F5F5F5"
                                                            /> 
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VTextField
                                                                v-model="phone"
                                                                :rules="[requiredValidator, phoneValidator]"
                                                                label="Telefon"
                                                                bg-color="#F5F5F5"
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
                                                                autocomplete="off"
                                                                bg-color="#F5F5F5"/>
                                                        </VCol>
                                                        <VCol cols="12" md="6">
                                                            <VTextField
                                                                v-model="email"
                                                                :rules="[emailValidator, requiredValidator]"
                                                                label="E-post"
                                                                bg-color="#F5F5F5"
                                                            />
                                                        </VCol>
                                                    </VRow>
                                                </VCol>
                                                <VCol cols="12">
                                                    <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                        Comments
                                                    </h6>
                                                    <VRow>
                                                        <VCol cols="12" md="12">
                                                            <VTextarea
                                                                v-model="sale_comments"
                                                                rows="4"
                                                                label="Comments"
                                                                bg-color="#F5F5F5"
                                                            />
                                                        </VCol>
                                                        <VCol cols="12" md="12" class="pb-4">
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
                                    </VWindow>
                                </VCardText>
                            </VCardText>
                        </VCard>                
                    </VCol>
                </VRow>
            </VCard>
        </VForm>
    </section>

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
            <div class="dialog-title">Avsluta utan att spara?</div>
        </VCardText>
        <VCardText class="dialog-text">
            <strong>Du har osparade ändringar.</strong> Om du lämnar den här vyn nu kommer informationen du har angett inte att sparas.
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn class="btn-light" @click="isConfirmLeaveVisible = false">Avbryt</VBtn>
            <VBtn class="btn-gradient" @click="() => { isConfirmLeaveVisible = false; reallyCloseAndReset(); }">Ja, fortsätt</VBtn>
        </VCardText>
        </VCard>
    </VDialog>
    </div>
</template>

<style scoped>
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

    .v-tabs-pill .v-tab {
        text-transform: none;
        letter-spacing: normal;
        font-weight: 500;
        color: #757575;
    }

    .v-tabs-pill .v-tab--selected {
        color: #009688; /* Teal */
        border-bottom: 1px solid;
        border-image-source: linear-gradient(90deg, #57F287 0%, #00EEB0 50%, #00FFFF 100%);
        border-image-slice: 1;
    }
</style>

<route lang="yaml">
    meta:
      action: edit
      subject: stock
</route>