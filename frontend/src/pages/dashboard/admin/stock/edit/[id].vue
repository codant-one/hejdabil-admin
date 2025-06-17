<script setup>

import router from '@/router'
import { themeConfig } from '@themeConfig'
import { yearValidator, requiredValidator } from '@/@core/utils/validators'
import { useVehiclesStores } from '@/stores/useVehicles'

const vehiclesStores = useVehiclesStores()

const emitter = inject("emitter")
const route = useRoute()

const isRequestOngoing = ref(true)
const isConfirmStatusDialogVisible = ref(false)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref('tab-1')
const isMobile = ref(false)

const brands = ref([])
const models = ref([])
const modelsByBrand = ref([])
const carbodies = ref([])
const gearboxes = ref([])
const ivas = ref([])
const states = ref([])
const logo = ref(null)

const vehicle = ref(null)
const reg_num = ref('')
const mileage = ref(null)
const brand_id = ref(null)
const model_id = ref(null)
const generation = ref(null)
const car_body_id = ref(null)
const year = ref(null)
const first_insc = ref(null)
const control_inspection = ref(null)
const color = ref(null)
const fuel = ref(null)
const gearbox_id = ref(null)
const purchase_price = ref(null)
const iva_id = ref(null)
const state_id = ref(null)
const state_idOld = ref(null)
const sale_price = ref(null)
const min_sale_price = ref(null)
const purchase_date = ref(null)
const sale_date = ref(null)
const number_keys = ref(null)
const service_book = ref(0)
const summer_tire = ref(0)
const winter_tire = ref(0)
const last_service = ref(null)
const dist_belt = ref(0)
const last_dist_belt = ref(null)
const comments = ref(null)

const optionsRadio = ['Ja', 'Nej', 'Finns ej']

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

const endDateTimePickerConfig = computed(() => {
    const now = new Date();
    const twoYearsLater = new Date();
    twoYearsLater.setFullYear(now.getFullYear() + 2);

    const formatToISO = (date) => date.toISOString().split('T')[0];

    const config = {
        dateFormat: 'Y-m-d',
        position: 'auto right',
        disable: [{
            from: formatToISO(twoYearsLater),
            to: '2099-12-31' // Una fecha futura lejana para bloquear indefinidamente
        }]
    }

    return config;
});


onMounted(async () => {

    checkIfMobile()

    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

watchEffect(async() => {

    isRequestOngoing.value = true


    if(Number(route.params.id) && route.name === 'dashboard-admin-stock-edit-id') {
        const data = await vehiclesStores.showVehicle(Number(route.params.id))
    
        vehicle.value = data.vehicle
        brands.value = data.brands
        models.value = data.models
        carbodies.value = data.carbodies
        gearboxes.value = data.gearboxes
        ivas.value = data.ivas
        states.value = data.states

        reg_num.value = vehicle.value.reg_num
        mileage.value = vehicle.value.mileage

        generation.value = vehicle.value.generation
        car_body_id.value = vehicle.value.car_body_id
        year.value = vehicle.value.year
        first_insc.value = vehicle.value.first_insc
        control_inspection.value = vehicle.value.control_inspection
        color.value = vehicle.value.color
        fuel.value = vehicle.value.fuel
        gearbox_id.value = vehicle.value.gearbox_id
        purchase_price.value = vehicle.value.purchase_price
        iva_id.value = vehicle.value.iva_id
        state_id.value = vehicle.value.state_id
        state_idOld.value = vehicle.value.state_id
        sale_price.value = vehicle.value.sale_price
        min_sale_price.value = vehicle.value.min_sale_price
        purchase_date.value = vehicle.value.purchase_date
        sale_date.value = vehicle.value.sale_date
        number_keys.value = vehicle.value.number_keys
        service_book.value = vehicle.value.service_book
        summer_tire.value = vehicle.value.summer_tire
        winter_tire.value = vehicle.value.winter_tire
        last_service.value = vehicle.value.last_service
        dist_belt.value = vehicle.value.dist_belt
        last_dist_belt.value = vehicle.value.last_dist_belt
        comments.value = vehicle.value.comments

        if(vehicle.value.model_id !== null) {
            let modelId = vehicle.value.model_id
            let brandId = models.value.filter(item => item.id === modelId)[0].brand.id
            selectBrand(brandId)
            brand_id.value = brandId
            model_id.value = vehicle.value.model_id
        }
    }

    isRequestOngoing.value = false
})


const getModels = computed(() => {
  return modelsByBrand.value.map((model) => {
    return {
      title: model.name,
      value: model.id
    }
  })
})

const selectBrand = brand => {
    if (brand) {
        let _brand = brands.value.find(item => item.id === brand)
    
        model_id.value = ''
        logo.value = _brand.logo
        modelsByBrand.value = models.value.filter(item => item.brand_id === _brand.id)
    }
}

const refetchData = hideOverlay => {
  setTimeout(hideOverlay, 3000)
}

const onSubmit = () => {
    refForm.value?.validate().then(({ valid }) => {
        if (valid) {
            let formData = new FormData()

            state_id.value = state_idOld.value

            formData.append('id', Number(route.params.id))
            formData.append('_method', 'PUT')
            formData.append('reg_num', reg_num.value)
            formData.append('model_id', model_id.value)
            formData.append('car_body_id', car_body_id.value)
            formData.append('gearbox_id', gearbox_id.value)
            formData.append('iva_id', iva_id.value)
            formData.append('state_id', state_id.value)
            formData.append('mileage', mileage.value)
            formData.append('generation', generation.value)
            formData.append('year', year.value)
            formData.append('first_insc', first_insc.value)
            formData.append('control_inspection', control_inspection.value)
            formData.append('color', color.value)
            formData.append('fuel', fuel.value)
            formData.append('purchase_price', purchase_price.value)
            formData.append('purchase_date', purchase_date.value)
            formData.append('sale_price', sale_price.value)
            formData.append('min_sale_price', min_sale_price.value)
            formData.append('sale_date', sale_date.value)
            formData.append('number_keys', number_keys.value)
            formData.append('service_book', service_book.value)
            formData.append('summer_tire', summer_tire.value)
            formData.append('winter_tire', winter_tire.value)
            formData.append('last_service', last_service.value)
            formData.append('dist_belt', dist_belt.value)
            formData.append('last_dist_belt', last_dist_belt.value)
            formData.append('comments', comments.value)

            isConfirmStatusDialogVisible.value = false
            isRequestOngoing.value = true

            let data = {
                data: formData, 
                id: Number(route.params.id)
            }

            vehiclesStores.updateVehicle(data)
                .then((res) => {
                    if (res.data.success) {
                        
                        let data = {
                            message: 'Aktie uppdaterad framgångsrikt.!',
                            error: false
                        }

                        router.push({ name : 'dashboard-admin-stock'})
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

</script>

<template>
    <section v-if="reg_num">
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
                <VCol cols="12" md="12">
                    <div class="d-flex mt-5 flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
                        <div class="d-flex align-center">
                            <VAvatar
                                v-if="model_id === null"
                                size="x-large"
                                variant="tonal"
                                color="secondary"
                            >
                                <VIcon size="x-large" icon="tabler-car" />                        
                            </VAvatar>
                            <VAvatar
                                v-else
                                size="x-large"
                                variant="tonal"
                                color="secondary"
                                :image="themeConfig.settings.urlStorage + logo"
                            />
                        </div>
                        <div class="d-flex flex-column justify-center">
                            <h6 class="text-md-h4 text-h6 font-weight-medium">
                                {{ reg_num }}
                            </h6>
                            <span class="d-flex align-center">
                                {{ states.filter(item => item.id === state_id)[0].name }}
                                <VIcon icon="tabler-edit" class="ms-1" @click="isConfirmStatusDialogVisible = true"/>
                            </span>
                        </div>
                        <VSpacer />
                        <div class="d-flex gap-4 w-100 w-md-auto">
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
                            <VCardText class="px-2 pt-0 px-md-12 pt-md-5">                
                                <VTabs v-model="currentTab" fixed-tabs>
                                    <VTab>Fordon</VTab>
                                    <VTab>Prisinformation</VTab>
                                    <VTab>Utrustningslista</VTab>
                                    <VTab>Information om bilen</VTab>
                                    <VTab>Fordonsplanering</VTab>
                                    <VTab>Kostnader</VTab>
                                    <VTab>Dokument</VTab>
                                </VTabs>
                                <VCardText class="px-2 px-md-12">
                                    <VWindow v-model="currentTab" class="pt-3">
                                        <!-- Fordon -->
                                        <VWindowItem class="px-md-5">
                                            <VRow class="px-md-5">
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        v-model="reg_num"
                                                        disabled
                                                        label="Reg nr"
                                                    />
                                                </VCol>   
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        type="number"
                                                        v-model="mileage"
                                                        label="Miltal"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VSelect
                                                        v-model="brand_id"
                                                        label="Märke"
                                                        :items="brands"
                                                        :item-title="item => item.name"
                                                        :item-value="item => item.id"
                                                        autocomplete="off"
                                                        clearable
                                                        clear-icon="tabler-x"
                                                        @update:modelValue="selectBrand"/>
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VSelect
                                                        v-model="model_id"
                                                        label="Modell"
                                                        :items="getModels"
                                                        autocomplete="off"
                                                        clearable
                                                        clear-icon="tabler-x"/>
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        v-model="generation"
                                                        label="Generation"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VSelect
                                                        v-model="car_body_id"
                                                        label="Kaross"
                                                        :items="carbodies"
                                                        :item-title="item => item.name"
                                                        :item-value="item => item.id"
                                                        autocomplete="off"
                                                        clearable
                                                        clear-icon="tabler-x"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        v-model="year"
                                                        :rules="[yearValidator]"
                                                        label="Årsmodell"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <AppDateTimePicker
                                                        :key="JSON.stringify(startDateTimePickerConfig)"
                                                        v-model="first_insc"
                                                        density="compact"
                                                        :config="startDateTimePickerConfig"
                                                        label="Första registreringsdatum"
                                                        clearable
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <AppDateTimePicker
                                                        :key="JSON.stringify(endDateTimePickerConfig)"
                                                        v-model="control_inspection"
                                                        density="compact"
                                                        :config="endDateTimePickerConfig"
                                                        label="Kontrollbesiktning gäller tom"
                                                        clearable
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
                                                        v-model="fuel"
                                                        label="Drivmedel"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VSelect
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
                                            </VRow>
                                        </VWindowItem>
                                        <!-- Prisinformation -->
                                        <VWindowItem class="px-md-5">
                                            <VRow class="px-md-5">
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        type="number"
                                                        v-model="purchase_price"
                                                        label="Inköpspris"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VSelect
                                                        v-model="iva_id"
                                                        label="VMB / Moms"
                                                        :items="ivas"
                                                        :item-title="item => item.name"
                                                        :item-value="item => item.id"
                                                        autocomplete="off"
                                                        clearable
                                                        clear-icon="tabler-x"/>
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VSelect
                                                        v-model="state_idOld"
                                                        label="Status"
                                                        :items="states"
                                                        :item-title="item => item.name"
                                                        :item-value="item => item.id"/>
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        v-model="sale_price"
                                                        type="number"
                                                        label="Försäljningspris"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        v-model="min_sale_price"
                                                        type="number"
                                                        label="Lägsta försäljningspris"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <AppDateTimePicker
                                                        :key="JSON.stringify(startDateTimePickerConfig)"
                                                        v-model="purchase_date"
                                                        density="compact"
                                                        :config="startDateTimePickerConfig"
                                                        label="Inköpsdatum"
                                                        clearable
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <AppDateTimePicker
                                                        :key="JSON.stringify(startDateTimePickerConfig)"
                                                        v-model="sale_date"
                                                        density="compact"
                                                        :config="startDateTimePickerConfig"
                                                        label="Försäljningsdag"
                                                        clearable
                                                    />
                                                </VCol>
                                            </VRow>
                                        </VWindowItem>
                                        <!-- Utrustningslista -->
                                        <VWindowItem class="px-md-5 text-center">
                                            Utrustningslista finns inte tillgänglig
                                            <VRow class="px-md-5" v-if="false">
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        v-model="phone"
                                                        label="Namn"
                                                    />
                                                </VCol>
                                            </VRow>
                                        </VWindowItem>
                                        <!-- Information om bilen -->
                                        <VWindowItem class="px-md-5">
                                            <VRow class="px-md-5">
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        v-model="number_keys"
                                                        type="number"
                                                        label="Antal nycklar"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="2">
                                                    <div class="d-flex flex-column">
                                                        <label class="v-label text-body-2 text-wrap"> Servicebok finns?</label>
                                                        <VRadioGroup v-model="service_book" inline>
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
                                                        <VRadioGroup v-model="summer_tire" inline>
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
                                                        <VRadioGroup v-model="winter_tire" inline>
                                                            <VRadio
                                                                v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
                                                        </VRadioGroup>
                                                    </div>
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        v-model="last_service"
                                                        label="Senaste service: Mil/datum"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <div class="d-flex flex-column">
                                                        <label class="v-label text-body-2 text-wrap">Kamrem bytt?</label>
                                                        <VRadioGroup
                                                            v-model="dist_belt"
                                                            inline
                                                        >
                                                            <div>
                                                            <VRadio
                                                                v-for="(radio, index) in optionsRadio"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
                                                            </div>
                                                        </VRadioGroup>
                                                    </div>
                                                </VCol>
                                                <VCol cols="12" md="12">
                                                    <VTextField
                                                        v-model="last_dist_belt"
                                                        label="Kamrem bytt vid Mil/datum"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="12">
                                                    <VTextarea
                                                        v-model="comments"
                                                        rows="5"
                                                        label="Kommenter"
                                                    />
                                                </VCol>
                                            </VRow>
                                        </VWindowItem>
                                        <!-- Fordonsplanering -->
                                        <VWindowItem class="px-md-5">
                                            <div class="d-flex align-end justify-sm-space-between gap-x-6 border-bottom-secondary">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium">
                                                    Övrigt
                                                </h6>
                                                <VBtn class="w-100 w-md-auto">
                                                    Lägg till en uppgift
                                                </VBtn>
                                            </div>

                                            <VCard
                                                flat
                                                color="success"
                                                style="box-shadow: none !important; border-radius: 12px !important;"
                                            >
                                                <VCardItem>
                                                <template #prepend>
                                                    icon
                                                    <!-- <VIcon
                                                    size="1.9rem"
                                                    color="white"
                                                    :icon="data.icon"
                                                    /> -->
                                                </template>
                                                <VCardTitle class="text-white">
                                                aaaa
                                                </VCardTitle>
                                                </VCardItem>

                                                <VCardText>
                                                <p class="clamp-text text-white mb-0">
                                                    aaaaaaaa
                                                </p>
                                                </VCardText>

                                                <VCardText class="d-flex justify-space-between align-center flex-wrap">
                                                <div class="text-no-wrap">
                                                    image
                                                    <!-- <VAvatar
                                                    size="34"
                                                    :image="data.avatarImg"
                                                    /> -->
                                                    <span class="text-white ms-2">aaa</span>
                                                </div>

                                                <div class="d-flex align-center">
                                                    <VIcon
                                                    icon="tabler-heart"
                                                    color="white"
                                                    class="me-1"
                                                    />
                                                    <span class="text-subtitle-2 text-white me-4">aaa</span>

                                                    <VIcon
                                                    icon="tabler-share"
                                                    color="white"
                                                    class="me-1"
                                                    />
                                                    <span class="text-subtitle-2 text-white mt-1">aaa</span>
                                                </div>
                                                </VCardText>
                                            </VCard>
                                        </VWindowItem>
                                        <VWindowItem class="px-md-5">
                                            agregar tablas
                                        </VWindowItem>
                                        <VWindowItem class="px-md-5">
                                            agregar documentso
                                        </VWindowItem>
                                    </VWindow>
                                </VCardText>
                            </VCardText>
                        </VCard>
                
                </VCol>
            </VRow>
        </VForm>

        <!-- 👉 Confirm create -->
        <VDialog
            v-model="isConfirmStatusDialogVisible"
            persistent
            class="v-dialog-sm" >
            <!-- Dialog close btn -->
                
            <DialogCloseBtn @click="isConfirmStatusDialogVisible = !isConfirmStatusDialogVisible" />

            <!-- Dialog Content -->
            <VForm
                ref="refForm"
                @submit.prevent="onSubmit">
                <VCard title="Redigera status">
                    <VDivider />
                    <VCardText>
                        <VSelect
                            v-model="state_idOld"
                            label="Status"
                            :items="states"
                            :item-title="item => item.name"
                            :item-value="item => item.id"
                            autocomplete="off"
                            :rules="[requiredValidator]"/>
                    </VCardText>

                    <VCardText class="d-flex justify-end gap-3 flex-wrap">
                        <VBtn
                            color="secondary"
                            variant="tonal"
                            @click="isConfirmStatusDialogVisible = false">
                            Avbryt
                        </VBtn>
                        <VBtn type="submit">
                            Uppdatera status
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>
    </section>
</template>

<style scoped>

    ::v-deep .v-input--density-comfortable, ::v-deep  .v-radio {
        --v-input-control-height: 0 !important;
    }

    ::v-deep .v-selection-control__wrapper {
        height: 20px !important;
    }

    ::v-deep .v-icon--size-default {
        font-size: calc(var(--v-icon-size-multiplier) * 1em) !important;
    }

    .v-btn--disabled {
        opacity: 1 !important;
    }

    .border-bottom-secondary {
        border-bottom: 1px solid #d9d9d9;
        padding-bottom: 5px;
    }

    .justify-content-center {
        justify-content: center !important;
    }
</style>

<route lang="yaml">
    meta:
      action: edit
      subject: stock
</route>