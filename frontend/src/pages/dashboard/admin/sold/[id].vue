<script setup>

import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { useVehiclesStores } from '@/stores/useVehicles'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router'

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
            <VRow>
                <VCol cols="12" md="12" class="py-0">
                    <div class="d-flex flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
                        <div class="d-flex flex-column justify-center">
                            <h6 class="text-md-h4 text-h6 font-weight-medium">
                                Försäljningsuppgifter
                            </h6>
                            <span class="d-flex align-center">
                                {{ reg_num }}
                            </span>
                        </div>
                        <VSpacer />
                        <div class="d-flex flex-column flex-md-row gap-1 gap-md-4 w-100 w-md-auto">
                            <VBtn
                                variant="tonal"
                                color="secondary"
                                class="mb-2 w-100 w-md-auto"
                                :to="{ name: 'dashboard-admin-stock' }"
                                >
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
                                <VTabs v-model="currentTab" fixed-tabs>
                                    <VTab>Fordon</VTab>
                                    <VTab>Kund</VTab>
                                </VTabs>
                                <VCardText class="px-0 px-md-2">
                                    <VWindow v-model="currentTab" class="pt-3">
                                        <!-- Fordon -->
                                        <VWindowItem class="px-md-5">
                                            <VRow class="px-md-5">
                                                <VCol cols="12" md="6">
                                                    <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                        Grund och teknisk information
                                                    </h6>
                                                    <VList class="card-list mt-2">
                                                        <VListItem>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Reg nr:
                                                                    <span class="text-body-2">
                                                                        {{ reg_num }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Miltal:
                                                                    <span class="text-body-2">
                                                                        {{ mileage }} Mil
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Märke:
                                                                    <span class="text-body-2">
                                                                        {{ brands.filter(item => item.id === brand_id)[0]?.name }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Modell:
                                                                    <span class="text-body-2">
                                                                        {{ models.filter(item => item.id === model_id)[0]?.name }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Generation:
                                                                    <span class="text-body-2">
                                                                        {{ generation }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Kaross:
                                                                    <span class="text-body-2">
                                                                        {{ carbodies.filter(item => item.id === car_body_id)[0]?.name }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Årsmodell:
                                                                    <span class="text-body-2">
                                                                        {{ year }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Inköpsdatum:
                                                                    <span class="text-body-2">
                                                                        {{ purchase_date }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Kontrollbesiktning gäller tom:
                                                                    <span class="text-body-2">
                                                                        {{ control_inspection }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Färg:
                                                                    <span class="text-body-2">
                                                                        {{ color }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Drivmedel:
                                                                    <span class="text-body-2">
                                                                        {{ fuels.filter(item => item.id === fuel_id)[0]?.name }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Växellåda:
                                                                    <span class="text-body-2">
                                                                        {{ gearboxes.filter(item => item.id === gearbox_id)[0]?.name }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                        </VListItem>
                                                    </VList>
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                        Prisinformation
                                                    </h6>
                                                    <VList class="card-list mt-2">
                                                        <VListItem>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    Inköpspris:
                                                                    <span class="text-body-2">
                                                                        {{ purchase_price }} {{ currencies.filter(item => item.id === currency_id)[0].code }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                            <VListItemTitle>
                                                                <h6 class="text-base font-weight-semibold">
                                                                    VMB / Moms:
                                                                    <span class="text-body-2">
                                                                        {{ ivas.filter(item => item.id === iva_purchase_id)[0]?.name }}
                                                                    </span>
                                                                </h6>
                                                            </VListItemTitle>
                                                        </VListItem>
                                                    </VList>
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
                                                        <VCol cols="12" md="6">
                                                            <AppDateTimePicker
                                                                :key="JSON.stringify(startDateTimePickerConfig)"
                                                                v-model="sale_date"
                                                                density="compact"
                                                                :config="startDateTimePickerConfig"
                                                                label="Försäljningsdag"
                                                                :rules="[requiredValidator]"
                                                                clearable
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
                                        <VWindowItem class="px-md-5">
                                            <VRow class="px-md-5">
                                                <VCol cols="12" md="6">
                                                    <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                        Köpare
                                                    </h6>
                                                    <VRow>
                                                        <VCol cols="12" md="12">
                                                            <VAutocomplete
                                                                v-model="client_id"
                                                                label="Kunder"
                                                                :items="clients"
                                                                :item-title="item => item.fullname"
                                                                :item-value="item => item.id"
                                                                autocomplete="off"
                                                                clearable
                                                                @click:clear="clearClient"
                                                                @update:modelValue="selectClient"/>
                                                        </VCol>
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
                                                        Comments
                                                    </h6>
                                                    <VRow>
                                                        <VCol cols="12" md="12">
                                                            <VTextarea
                                                                v-model="sale_comments"
                                                                rows="4"
                                                                label="Comments"
                                                            />
                                                        </VCol>
                                                        <VCol cols="12" md="12" class="py-0">
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
        </VForm>
    </section>
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
</style>

<route lang="yaml">
    meta:
      action: edit
      subject: stock
</route>