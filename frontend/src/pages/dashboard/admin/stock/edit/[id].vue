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
const phone = ref('')


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

        model_id.value = vehicle.value.model_id
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
        if (valid && currentTab.value === 0 && refForm.value.items.length < 10) {
            currentTab.value++
        } else if ((!valid && currentTab.value === 0 && refForm.value.items.length === 10) || (!valid && currentTab.value === 0 && refForm.value.items.length === 13)) {
            currentTab.value++
        } else if (!valid && currentTab.value === 1 && refForm.value.items.length > 10) {
            currentTab.value++
        } else if (valid  && currentTab.value < 2 && refForm.value.items.length > 8) {
            currentTab.value++
        } else if (valid && currentTab.value === 2) {
            let formData = new FormData()

            formData.append('id', Number(route.params.id))
            formData.append('_method', 'PUT')

            formData.append('phone', phone.value)

            isRequestOngoing.value = true

            let data = {
                data: formData, 
                id: Number(route.params.id)
            }

            vehiclesStores.updateVehicle(data)
                .then((res) => {
                    if (res.data.success) {
                        
                        let data = {
                            message: 'Uppdaterad leverantör!',
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
        <VRow>
            <VDialog
                v-model="isRequestOngoing"
                width="auto"
                persistent>
                <VProgressCircular
                indeterminate
                color="primary"
                class="mb-0"/>
            </VDialog>

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
                            På lager 
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

                        <VBtn class="w-100 w-md-auto">
                            Spara
                        </VBtn>
                    </div>
                </div>
            </VCol>
            <VCol cols="12" md="12">
                <VForm
                    ref="refForm"
                    v-model="isFormValid"
                    @submit.prevent="onSubmit">
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
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="control_inspection"
                                                    density="compact"
                                                    :config="startDateTimePickerConfig"
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
                                                    v-model="state_id"
                                                    label="Status"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="phone"
                                                    type="number"
                                                    label="Försäljningspris"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="phone"
                                                    type="number"
                                                    label="Lägsta försäljningspris"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="phone"
                                                    density="compact"
                                                    :config="startDateTimePickerConfig"
                                                    label="Inköpsdatum"
                                                    clearable
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="phone"
                                                    density="compact"
                                                    :config="startDateTimePickerConfig"
                                                    label="Försäljningsdag"
                                                    clearable
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!-- contact -->
                                    <VWindowItem class="px-md-5">
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
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="phone"
                                                    type="number"
                                                    label="Antal nycklar"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Servicebok finns?"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Sommardäck finns?"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Vinterdäck finns?"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="phone"
                                                    label="Senaste service: Mil/datum"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Kamrem bytt?"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="12">
                                                <VTextField
                                                    v-model="phone"
                                                    label="Kamrem bytt vid Mil/datum"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="12">
                                                <VTextarea
                                                    v-model="phone"
                                                    rows="5"
                                                    label="Kommenter"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <VWindowItem class="px-md-5">
                                        <div class="d-flex align-end justify-sm-space-between gap-x-6 border-bottom-secondary">
                                            <h6 class="text-md-h4 text-h6 font-weight-medium">
                                                Övrigt
                                            </h6>
                                            <VBtn class="w-100 w-md-auto">
                                                Lägg till en uppgift
                                            </VBtn>
                                        </div>
                                        <div class="d-flex justify-content-center mt-10">
                                            Ingen post hittades
                                        </div>

                                        <AppCardActions
        title="Slots"
        @refresh="refetchData"
      >
        <template #before-actions>
          <VChip
            class="me-3"
            color="primary"
            size="small"
          >
            3 Updates
          </VChip>
        </template>

        <VCardText>
          <p><code>app-card-actions</code> also provides <code>before-actions</code> and <code>after-actions</code> slot</p>
          <span>You can find more details in our documentation</span>
        </VCardText>
      </AppCardActions>
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
                </VForm>
            </VCol>
        </VRow>

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
                            v-model="state_id"
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