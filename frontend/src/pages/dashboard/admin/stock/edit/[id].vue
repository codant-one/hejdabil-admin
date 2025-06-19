<script setup>

import router from '@/router'
import { themeConfig } from '@themeConfig'
import { avatarText } from '@/@core/utils/formatters'
import { formatNumber } from '@/@core/utils/formatters'
import { yearValidator, requiredValidator } from '@/@core/utils/validators'
import { useVehiclesStores } from '@/stores/useVehicles'
import { useTasksStores } from '@/stores/useTasks'
import { useCostsStores } from '@/stores/useCosts'
import { useDocumentsStores } from '@/stores/useDocuments'

const vehiclesStores = useVehiclesStores()
const tasksStores = useTasksStores()
const costsStores = useCostsStores()
const documentsStores = useDocumentsStores()

const emitter = inject("emitter")
const route = useRoute()

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const isRequestOngoing = ref(true)
const isConfirmStatusDialogVisible = ref(false)
const isConfirmTaskDialogVisible = ref(false)
const isConfirmUpdateTaskDialogVisible = ref(false)
const isConfirmCreateCostDialogVisible = ref(false)
const isConfirmSendDocumentDialogVisible = ref(false)

const selectedTask = ref({})
const comment = ref(null)

const isFormValid = ref(false)
const refForm = ref()
const refTask = ref()
const refUpdate = ref()
const refCost = ref()
const refSend = ref()
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
const vehicle_id = ref(null)
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

const costs = ref([])
const description = ref([])
const value = ref([])
const selectedCost = ref([])

const documents = ref([])
const fileInput = ref()
const selectedIds = ref([])

const clients = ref([])
const client_id = ref(null)
const email = ref(null)

const isCreateCost = ref(true)
const tasks = ref([])
const measure = ref(null)
const cost = ref(null)
const start_date = ref(null)
const end_date = ref(null)

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

const allSelected = computed({
  get: () => documents.value.length > 0 && selectedIds.value.length === documents.value.length,
  set: (value) => {
    selectedIds.value = value ? documents.value.map(doc => doc.id) : []
  }
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

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
        clients.value = data.clients

        vehicle_id.value = vehicle.value.id
        reg_num.value = vehicle.value.reg_num
        tasks.value = vehicle.value.tasks
        costs.value = vehicle.value.costs
        documents.value = vehicle.value.documents

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

        if(Object.keys(selectedTask.value).length > 0) {
            selectedTask.value = tasks.value.filter(item => item.id === selectedTask.value.id)[0]
        }
    }

    isRequestOngoing.value = false
}


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

const createTask = async () => {

    refTask.value?.validate().then(async({ valid }) => {
            if (valid) {
                let formData = new FormData()

                formData.append('vehicle_id', vehicle_id.value)
                formData.append('measure', measure.value)
                formData.append('cost', cost.value)
                formData.append('start_date', start_date.value)
                formData.append('end_date', end_date.value)

                isRequestOngoing.value = true

                tasksStores.addTask(formData)
                    .then((res) => {
                        if (res.data.success) {
                            advisor.value = {
                                type: 'success',
                                message: 'Uppgift skapad!',
                                show: true
                            }
                        }
                        isRequestOngoing.value = false
                    })
                    .catch((err) => {
                        
                        advisor.value = {
                            type: 'error',
                            message: err.message,
                            show: true
                        }

                        let data = {
                            message: err.message,
                            error: true
                        }

                        isRequestOngoing.value = false
                    })
                
                    isConfirmTaskDialogVisible.value = false

                await fetchData()

                setTimeout(() => {
                    advisor.value = {
                        type: '',
                        message: '',
                        show: false
                    }
                }, 3000)
            }
    })
}

const updateTask = async () => {

    refUpdate.value?.validate().then(async({ valid }) => {
            if (valid) {
                let formData = new FormData()

                formData.append('id', selectedTask.value.id)
                formData.append('_method', 'PUT')
                formData.append('vehicle_id', selectedTask.value.vehicle_id)
                formData.append('measure', selectedTask.value.measure)
                formData.append('cost', selectedTask.value.cost)
                formData.append('start_date', selectedTask.value.start_date)
                formData.append('end_date', selectedTask.value.end_date)

                isRequestOngoing.value = true

                
                let data = {
                    data: formData, 
                    id: selectedTask.value.id
                }

                tasksStores.updateTask(data)
                    .then((res) => {
                        if (res.data.success) {
                            advisor.value = {
                                type: 'success',
                                message: 'Uppgift uppdaterad!',
                                show: true
                            }
                        }
                        isRequestOngoing.value = false
                    })
                    .catch((err) => {
                        
                        advisor.value = {
                            type: 'error',
                            message: err.message,
                            show: true
                        }

                        isRequestOngoing.value = false
                    })
                
                    isConfirmUpdateTaskDialogVisible.value = false

                await fetchData()

                setTimeout(() => {
                    advisor.value = {
                        type: '',
                        message: '',
                        show: false
                    }
                }, 3000)
            }
    })
}

const showTask = taskData => {
  isConfirmUpdateTaskDialogVisible.value = true
  selectedTask.value = { ...taskData }
}

const removeTask = async (task) => {

  let res = await tasksStores.deleteTask(task.id)

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Uppgift borttagen!' : res.data.message,
    show: true
  }

  await fetchData()

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const sendComment = async () => {

    if(comment.value !== null && comment.value !== '') {
        isRequestOngoing.value = true
        
        await tasksStores.sendComment({ id: selectedTask.value.id, comment: comment.value})
        
        isRequestOngoing.value = false
        
        await fetchData()

        comment.value = null

        return true
    }
}

const showCost = costData => {
    isConfirmCreateCostDialogVisible.value = true
    selectedCost.value = { ...costData }
    description.value = costData.description
    value.value = costData.value
    isCreateCost.value = false
}

const handleCost = async () => {
    refCost.value?.validate().then(async({ valid }) => {
        if (valid) {
            let formData = new FormData()

            formData.append('vehicle_id', vehicle_id.value)
            formData.append('description', description.value)
            formData.append('value', value.value)

            isRequestOngoing.value = true

            if(isCreateCost.value) {
                costsStores.addCost(formData)
                    .then((res) => {
                        if (res.data.success) {
                            advisor.value = {
                                type: 'success',
                                message: 'Kostnader skapad!',
                                show: true
                            }
                        }
                        isRequestOngoing.value = false
                    })
                    .catch((err) => {
                        
                        advisor.value = {
                            type: 'error',
                            message: err.message,
                            show: true
                        }

                        let data = {
                            message: err.message,
                            error: true
                        }

                        isRequestOngoing.value = false
                    })
            } else {

                formData.append('id', selectedCost.value.id)
                formData.append('_method', 'PUT')
                formData.append('vehicle_id', selectedCost.value.vehicle_id)
                formData.append('description', description.value)
                formData.append('value', value.value)

                let data = {
                    data: formData, 
                    id: selectedCost.value.id
                }

                costsStores.updateCost(data)
                    .then((res) => {
                        if (res.data.success) {
                            advisor.value = {
                                type: 'success',
                                message: 'Kostnader uppdaterad!',
                                show: true
                            }
                        }
                        isRequestOngoing.value = false
                    })
                    .catch((err) => {
                        
                        advisor.value = {
                            type: 'error',
                            message: err.message,
                            show: true
                        }

                        let data = {
                            message: err.message,
                            error: true
                        }

                        isRequestOngoing.value = false
                    })
            }
            
            isCreateCost.value = true
            isConfirmCreateCostDialogVisible.value = false
            description.value = null
            value.value = null
            
            await fetchData()

            setTimeout(() => {
                advisor.value = {
                    type: '',
                    message: '',
                    show: false
                }
            }, 3000)
        }
    })
}

const removeCost = async (cost) => {

    let res = await costsStores.deleteCost(cost.id)

    advisor.value = {
        type: res.data.success ? 'success' : 'error',
        message: res.data.success ? 'Kostnader borttagen!' : res.data.message,
        show: true
    }

    await fetchData()

    setTimeout(() => {
        advisor.value = {
        type: '',
        message: '',
        show: false
        }
    }, 3000)

    return true
}

const handleFileUpload = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    let formData = new FormData()

    formData.append('vehicle_id', vehicle_id.value)
    formData.append('document_type_id', 1)
    formData.append('file', file)

    isRequestOngoing.value = true

    documentsStores.addDocument(formData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Dokument skapad!',
                    show: true
                }
            }
            isRequestOngoing.value = false
        })
        .catch((err) => {
            
            advisor.value = {
                type: 'error',
                message: err.message,
                show: true
            }

            let data = {
                message: err.message,
                error: true
            }

            isRequestOngoing.value = false
        })
    
    await fetchData()

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)

}

const download = async(doc) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + doc.file);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = doc.file.replace('vehicles/', ''); 
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

  } catch (error) {
    console.error('Error:', error);
  }
}

const removeDocument = async (document) => {

    let res = await documentsStores.deleteDocument(document.id)

    advisor.value = {
        type: res.data.success ? 'success' : 'error',
        message: res.data.success ? 'Dokument borttagen!' : res.data.message,
        show: true
    }

    await fetchData()

    setTimeout(() => {
        advisor.value = {
        type: '',
        message: '',
        show: false
        }
    }, 3000)

    return true
}

const selectClient = client => {
    if (client) {
        let _client = clients.value.find(item => item.id === client)
    
        email.value = _client.email
    }
}

const handleSendMail = () => {
    if (!selectedIds.value.length) return

    refSend.value?.validate().then(({ valid }) => {
        if (valid) {
            let formData = new FormData()

            formData.append('ids', selectedIds.value)
            formData.append('email', email.value)

            isConfirmSendDocumentDialogVisible.value = false
            isRequestOngoing.value = true

            documentsStores.sendDocument(formData)
                .then((res) => {
                    if (res.data.success) {
                        advisor.value = {
                            type: 'success',
                            message: 'Skickades framgångsfullt!',
                            show: true
                        }
                    }
                    isRequestOngoing.value = false
                })
                .catch((err) => {
                    
                    advisor.value = {
                        type: 'error',
                        message: err.message,
                        show: true
                    }

                    let data = {
                        message: err.message,
                        error: true
                    }

                    isRequestOngoing.value = false
                })

            setTimeout(() => {
                selectedIds.value = []
                email.value = null
                client_id.value = null
                advisor.value = {
                    type: '',
                    message: '',
                    show: false
                }
            }, 3000)
        }
    })
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

            <VCol cols="12">
                <VAlert
                v-if="advisor.show"
                :type="advisor.type"
                class="mb-6">
                    
                {{ advisor.message }}
                </VAlert>
            </VCol>
        </VRow>

        <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit">
            <VRow>
                <VCol cols="12" md="12">
                    <div class="d-flex flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
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
                                    <VTab>Prisinformation</VTab>
                                    <VTab>Utrustningslista</VTab>
                                    <VTab>Information om bilen</VTab>
                                    <VTab>Fordonsplanering</VTab>
                                    <VTab>Kostnader</VTab>
                                    <VTab>Dokument</VTab>
                                </VTabs>
                                <VCardText class="px-2">
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
                                                <VCol cols="12" md="6">
                                                    <VTextField
                                                        v-model="last_service"
                                                        label="Senaste service: Mil/datum"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <div class="d-flex flex-column">
                                                        <label class="v-label text-body-2 text-wrap">Kamrem bytt?</label>
                                                        <VRadioGroup v-model="dist_belt" inline class="radio-form">
                                                            <VRadio
                                                                v-for="(radio, index) in optionsRadio"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
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
                                            <div class="d-flex flex-column flex-md-row text-center justify-md-space-between gap-x-6" :class="tasks.length === 0 ? 'border-bottom-secondary' : ''">
                                                <h6 class="text-md-h4 text-h5 font-weight-medium mb-5 mb-0">
                                                    Övrigt
                                                </h6>
                                                <VBtn class="w-100 w-md-auto" @click="isConfirmTaskDialogVisible = true">
                                                    Lägg till en uppgift
                                                </VBtn>
                                            </div>

                                            <div v-if="tasks.length === 0" class="mt-10 text-center">Ingen post hittades</div>

                                            <VRow v-else class="mt-5">
                                                <VCol
                                                    v-for="(task, index) in tasks"
                                                    :key="index"
                                                    cols="12" md="4"
                                                >
                                                    <VCard
                                                        flat
                                                        color="#007BB6"
                                                        style="box-shadow: none !important; border-radius: 12px !important;"
                                                    >
                                                        <VCardItem>
                                                            <template #prepend>
                                                                <VIcon
                                                                size="1.9rem"
                                                                color="white"
                                                                icon="mdi-note-outline"
                                                                />
                                                            </template>
                                                        <VCardTitle class="text-white"> {{ index + 1 }}</VCardTitle>
                                                        </VCardItem>

                                                        <VCardText>
                                                            <p class="clamp-text text-white mb-0">
                                                                <strong>Åtgärd:</strong> {{ task.measure }}
                                                            </p>
                                                            <p class="clamp-text text-white mb-0">
                                                                <strong>Kostnader:</strong> {{ task.cost }}
                                                            </p>
                                                            <p class="clamp-text text-white mb-0">
                                                                <strong>Startdatum:</strong> {{ task.start_date }}
                                                            </p>
                                                            <p class="clamp-text text-white mb-0">
                                                                <strong>Slutdatum:</strong> {{ task.end_date }}
                                                            </p>
                                                        </VCardText>

                                                        <VCardText class="d-flex justify-space-between align-center flex-wrap">
                                                        <div class="text-no-wrap">
                                                            <VAvatar
                                                                color="success"
                                                                :variant="task.user.avatar ? 'outlined' : 'tonal'"
                                                                size="34"
                                                            >
                                                                <VImg
                                                                    v-if="task.user.avatar"
                                                                    style="border-radius: 50%;"
                                                                    :src="themeConfig.settings.urlStorage + task.user.avatar"
                                                                />
                                                                <span v-else>{{ avatarText(task.user.name) }}</span>
                                                            </VAvatar>
                                                            <span class="text-white ms-2">{{ task.user.name }} {{ task.user.last_name }}</span>
                                                        </div>

                                                        <div class="d-flex align-center">
                                                            <VIcon
                                                                icon="tabler-edit"
                                                                color="white"
                                                                class="me-1 cursor-pointer"
                                                                @click="showTask(task)"
                                                            />
                                                            <VIcon
                                                                icon="tabler-trash"
                                                                color="white"
                                                                class="cursor-pointer"
                                                                @click="removeTask(task)"
                                                            />
                                                        </div>
                                                        </VCardText>
                                                    </VCard>
                                                </VCol>
                                            </VRow>
                                        </VWindowItem>
                                        <!-- Kostnader -->
                                        <VWindowItem class="px-md-5">
                                            <div class="d-flex align-center flex-wrap pb-4 w-100 w-md-auto">           
                                                <VSpacer class="d-none d-md-block"/>   
                                                <VBtn
                                                    v-if="$can('edit', 'stock')"
                                                    class="w-100 w-md-auto"
                                                    prepend-icon="tabler-plus"
                                                    @click="isConfirmCreateCostDialogVisible = true">
                                                    Lägg till fler kostnader
                                                </VBtn>
                                            </div>
                                            <VTable class="text-no-wrap">
                                                <!-- 👉 table head -->
                                                <thead>
                                                    <tr>
                                                        <th scope="col">#ID</th>
                                                        <th scope="col">Åtgärd</th>
                                                        <th scope="col">Kostnader</th>
                                                        <th scope="col" v-if="$can('edit', 'stock') || $can('delete', 'stock')"></th>
                                                    </tr>
                                                </thead>
                                                <!-- 👉 table body -->
                                                <tbody>
                                                    <tr 
                                                        v-for="(cost, index) in costs"
                                                        :key="index"
                                                        style="height: 3rem;">

                                                        <td> {{ index + 1 }} </td>
                                                        <td class="text-wrap"> {{ cost.description }} </td>
                                                        <td> {{ formatNumber(cost.value ?? 0) }} </td>
                                                        <!-- 👉 Acciones -->
                                                        <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'stock') || $can('delete', 'stock')">      
                                                        <VMenu>
                                                            <template #activator="{ props }">
                                                                <VBtn v-bind="props" icon variant="text" color="default" size="x-small">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                                                    <path d="M12.52 20.924c-.87 .262 -1.93 -.152 -2.195 -1.241a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.088 .264 1.502 1.323 1.242 2.192"></path>
                                                                    <path d="M19 16v6"></path>
                                                                    <path d="M22 19l-3 3l-3 -3"></path>
                                                                    <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                                                                    </svg>
                                                                </VBtn>
                                                            </template>

                                                            <VList>
                                                                <VListItem v-if="$can('edit', 'stock')" @click="showCost(cost)">
                                                                    <template #prepend>
                                                                        <VIcon icon="tabler-edit" />
                                                                    </template>
                                                                    <VListItemTitle>Redigera</VListItemTitle>
                                                                </VListItem>
                                                                <VListItem v-if="$can('delete','stock')" @click="removeCost(cost)">
                                                                    <template #prepend>
                                                                    <VIcon icon="tabler-trash" />
                                                                    </template>
                                                                    <VListItemTitle>Avaktivera</VListItemTitle>
                                                                </VListItem>
                                                            </VList>
                                                        </VMenu>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <!-- 👉 table footer  -->
                                                <tfoot v-show="!costs.length">
                                                <tr>
                                                    <td
                                                    colspan="6"
                                                    class="text-center">
                                                    Uppgifter ej tillgängliga
                                                    </td>
                                                </tr>
                                                </tfoot>
                                            </VTable>
                                            <VCardText class="d-block d-md-flex text-center align-center flex-wrap px-0 py-3">
                                                <span class="d-block d-md-flex text-sm text-disabled">
                                                    <strong class="d-block me-md-5">Totala kostnader: {{ formatNumber(costs.reduce((sum, item) => sum + parseFloat(item.value), 0) ?? 0) }} kr</strong>
                                                </span>
                                            </VCardText>
                                        </VWindowItem>
                                        <!-- Dokument -->
                                        <VWindowItem class="px-md-5">
                                            <div class="d-flex align-center flex-wrap pb-4 w-100 w-md-auto">           
                                                <VSpacer class="d-none d-md-block"/> 
                                                <VBtn
                                                    v-if="selectedIds.length > 0"
                                                    color="secondary"
                                                    variant="tonal"
                                                    class="me-2"
                                                    @click="isConfirmSendDocumentDialogVisible = true">
                                                    Sänd PDF
                                                </VBtn>  
                                                <VBtn
                                                    v-if="$can('edit', 'stock')"
                                                    class="w-100 w-md-auto"
                                                    prepend-icon="mdi-cloud-upload-outline"
                                                    @click="() => fileInput.click()">
                                                    Ladda upp
                                                </VBtn>

                                                <input
                                                    type="file"
                                                    ref="fileInput"
                                                    @change="handleFileUpload"
                                                    style="display: none"
                                                />
                                            </div>
                                            <VTable class="text-no-wrap">
                                                <!-- 👉 table head -->
                                                <thead>
                                                    <tr>
                                                        <th scope="col">
                                                            <VCheckbox
                                                                :model-value="allSelected"
                                                                @update:model-value="allSelected = $event"
                                                                density="compact"
                                                                hide-details
                                                            />
                                                        </th>
                                                        <th scope="col">#ID</th>
                                                        <th scope="col">Namn</th>
                                                        <th scope="col">Skapad av</th>
                                                        <th scope="col">Skapad</th>
                                                        <th scope="col">Typ</th>
                                                        <th scope="col" v-if="$can('edit', 'stock') || $can('delete', 'stock')"></th>
                                                    </tr>
                                                </thead>
                                                <!-- 👉 table body -->
                                                <tbody>
                                                    <tr 
                                                        v-for="(document, index) in documents"
                                                        :key="index"
                                                        style="height: 3rem;">
                                                        <td>
                                                            <VCheckbox
                                                                :value="document.id"
                                                                v-model="selectedIds"
                                                                density="compact"
                                                                hide-details
                                                            />
                                                        </td>
                                                        <td> {{ index + 1 }} </td>
                                                        <td class="text-wrap">{{ document.file.replace('vehicles/', '') }} </td>
                                                        <td> {{ document.user.name }} {{ document.user.last_name }}</td>
                                                        <td>  
                                                            {{ new Date(document.created_at).toLocaleString('sv-SE', { 
                                                                year: 'numeric', 
                                                                month: '2-digit', 
                                                                day: '2-digit', 
                                                                hour: '2-digit', 
                                                                minute: '2-digit',
                                                                hour12: false
                                                            }) }} 
                                                        </td>
                                                        <td> {{ document.type.name }} </td>
                                                        <!-- 👉 Acciones -->
                                                        <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'stock') || $can('delete', 'stock')">      
                                                            <VMenu>
                                                                <template #activator="{ props }">
                                                                    <VBtn v-bind="props" icon variant="text" color="default" size="x-small">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                                                        <path d="M12.52 20.924c-.87 .262 -1.93 -.152 -2.195 -1.241a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.088 .264 1.502 1.323 1.242 2.192"></path>
                                                                        <path d="M19 16v6"></path>
                                                                        <path d="M22 19l-3 3l-3 -3"></path>
                                                                        <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                                                                        </svg>
                                                                    </VBtn>
                                                                </template>

                                                                <VList>
                                                                    <VListItem v-if="$can('edit', 'stock')" @click="download(document)">
                                                                        <template #prepend>
                                                                            <VIcon icon="mdi-cloud-download-outline" />
                                                                        </template>
                                                                        <VListItemTitle>Ladda ner</VListItemTitle>
                                                                    </VListItem>
                                                                    <VListItem v-if="$can('delete','stock')" @click="removeDocument(document)">
                                                                        <template #prepend>
                                                                        <VIcon icon="tabler-trash" />
                                                                        </template>
                                                                        <VListItemTitle>Avaktivera</VListItemTitle>
                                                                    </VListItem>
                                                                </VList>
                                                            </VMenu>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <!-- 👉 table footer  -->
                                                <tfoot v-show="!documents.length">
                                                <tr>
                                                    <td
                                                    colspan="6"
                                                    class="text-center">
                                                    Uppgifter ej tillgängliga
                                                    </td>
                                                </tr>
                                                </tfoot>
                                            </VTable>
                                        </VWindowItem>
                                    </VWindow>
                                </VCardText>
                            </VCardText>
                        </VCard>                
                </VCol>
            </VRow>
        </VForm>

        <!-- 👉 Confirm update state -->
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

        <!-- 👉 Create task -->
        <VDialog
            v-model="isConfirmTaskDialogVisible"
            persistent
            class="v-dialog-sm" >
            <!-- Dialog close btn -->
                
            <DialogCloseBtn @click="isConfirmTaskDialogVisible = !isConfirmTaskDialogVisible" />

            <!-- Dialog Content -->
            <VForm
                ref="refTask"
                @submit.prevent="createTask">
                <VCard title="Lägg till nytt kort">
                    <VDivider />
                    <VCardText>
                        <VRow>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="measure"
                                    label="Åtgärd"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="cost"
                                    type="number"
                                    label="Kostnader"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <AppDateTimePicker
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="start_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    :rules="[requiredValidator]"
                                    label="Startdatum"
                                    clearable
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <AppDateTimePicker
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="end_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    label="Slutdatum"
                                    clearable
                                />
                            </VCol>
                        </VRow>
                        
                    </VCardText>

                    <VCardText class="d-flex justify-end gap-3 flex-wrap">
                        <VBtn
                            color="secondary"
                            variant="tonal"
                            @click="isConfirmTaskDialogVisible = false">
                            Avbryt
                        </VBtn>
                        <VBtn type="submit">
                            Spara
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>

        <!-- 👉 Update task -->
        <VDialog
            v-model="isConfirmUpdateTaskDialogVisible"
            scrollable
            persistent
            class="v-dialog-sm">
            <!-- Dialog close btn -->
                
            <DialogCloseBtn @click="isConfirmUpdateTaskDialogVisible = !isConfirmUpdateTaskDialogVisible" />

            <!-- Dialog Content -->
            <VForm
                ref="refUpdate"
                @submit.prevent="updateTask">
                <VCard title="Se eller redigera kort">
                    <VDivider />
                    <VCardText style="max-height: 450px;">
                        <VRow>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="selectedTask.measure"
                                    label="Åtgärd"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="selectedTask.cost"
                                    type="number"
                                    label="Kostnader"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <AppDateTimePicker
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="selectedTask.start_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    :rules="[requiredValidator]"
                                    label="Startdatum"
                                    clearable
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <AppDateTimePicker
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="selectedTask.end_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    label="Slutdatum"
                                    clearable
                                />
                            </VCol>
                        </VRow>
                        
                        <VAlert
                            color="primary"
                            icon="mdi-information-outline"
                            variant="tonal"
                            class="my-5"
                            >
                            Du kan stänga dialogrutan. Dina ändringar har sparats automatiskt.
                        </VAlert>
                
                        <VExpansionPanels>
                            <VExpansionPanel>
                                <VExpansionPanelTitle>Aktiviteter</VExpansionPanelTitle>
                                <VExpansionPanelText>
                                    <div 
                                        v-for="(history, index) in selectedTask.histories" 
                                        :key="index" 
                                        :class="selectedTask.histories.length > 1 && index !== selectedTask.histories.length - 1 ? 'py-2 border-bottom-secondary' : 'pt-2'"
                                        class="d-flex flex-column">
                                        <span v-if="history.is_updated"><strong>{{ selectedTask.user.name }} {{ selectedTask.user.last_name }}</strong> lade till detta kort till <strong>Övrigt.</strong></span>
                                        <span v-else><strong>{{ selectedTask.user.name }} {{ selectedTask.user.last_name }}</strong> uppdaterade kortet.</span>
                                        <span>  
                                            {{ new Date(selectedTask.created_at).toLocaleString('sv-SE', { 
                                                year: 'numeric', 
                                                month: '2-digit', 
                                                day: '2-digit', 
                                                hour: '2-digit', 
                                                minute: '2-digit',
                                                hour12: false
                                            }) }}
                                        </span>                                        
                                    </div>                                  
                                </VExpansionPanelText>
                            </VExpansionPanel>
                        </VExpansionPanels>

                        <div class="py-5">
                            <h6 class="text-md-h5 text-h6 font-weight-medium mb-3">
                                kommentarer
                            </h6>
                            <div class="d-flex">
                                <VTextField
                                    v-model="comment"
                                    placeholder="Skriv en kommentar"
                                    label="kommentar"
                                />
                                <VBtn class="ms-2" @click="sendComment">
                                    Skicka
                                </VBtn>
                            </div>
                        </div>

                        <VAlert 
                            v-for="(comment, index) in selectedTask.comments" 
                            :key="index"
                            variant="outlined" 
                            color="secondary"
                            class="my-1">
                            <div class="d-flex flex-column">
                                {{ comment.comment }}
                                <span class="text-xs">  
                                    {{ new Date(comment.created_at).toLocaleString('sv-SE', { 
                                        year: 'numeric', 
                                        month: '2-digit', 
                                        day: '2-digit', 
                                        hour: '2-digit', 
                                        minute: '2-digit',
                                        hour12: false
                                    }) }} | <strong>{{ comment.user.name }} {{ comment.user.last_name }}</strong>
                                </span>                                        
                            </div>            
                        </VAlert>
                    </VCardText>

                    <VCardText class="d-flex justify-end gap-3 flex-wrap pb-4">
                        <VBtn
                            class="mt-4"
                            color="secondary"
                            variant="tonal"
                            @click="isConfirmUpdateTaskDialogVisible = false">
                            Avbryt
                        </VBtn>
                        <VBtn class="mt-4" type="submit">
                            Uppdatering
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>

        <!-- 👉 Create/Update cost -->
        <VDialog
            v-model="isConfirmCreateCostDialogVisible"
            persistent
            class="v-dialog-sm">
            <!-- Dialog close btn -->
                
            <DialogCloseBtn @click="isConfirmCreateCostDialogVisible = !isConfirmCreateCostDialogVisible" />

            <!-- Dialog Content -->
            <VForm
                ref="refCost"
                @submit.prevent="handleCost">
                <VCard :title="isCreateCost ? 'Lägg till kostnader' : 'Uppdatera kostnader'">
                    <VDivider />
                    <VCardText style="max-height: 450px;">
                        <VRow>
                            <VCol cols="12" md="12">
                                <VTextarea
                                    v-model="description"
                                    rows="2"
                                    label="Åtgärd"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="12">
                                <VTextField
                                    v-model="value"
                                    type="number"
                                    label="Kostnader"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                        </VRow>                        
                    </VCardText>

                    <VCardText class="d-flex justify-end gap-3 flex-wrap pb-4">
                        <VBtn
                            class="mt-4"
                            color="secondary"
                            variant="tonal"
                            @click="isConfirmCreateCostDialogVisible = false">
                             Avbryt
                        </VBtn>
                        <VBtn class="mt-4" type="submit">
                            {{ isCreateCost ? 'Spara' : 'Uppdatering'}}
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>

        <!-- 👉 Confirm send documents -->
        <VDialog
            v-model="isConfirmSendDocumentDialogVisible"
            persistent
            class="v-dialog-sm" >
            <!-- Dialog close btn -->
                
            <DialogCloseBtn @click="isConfirmSendDocumentDialogVisible = !isConfirmSendDocumentDialogVisible" />

            <!-- Dialog Content -->
            <VForm
                ref="refSend"
                @submit.prevent="handleSendMail">
                <VCard title="Skicka pdf som e-post">
                    <VDivider />
                    <VCardText>
                         <VRow>
                            <VCol cols="12" md="12">
                                <VSelect
                                    v-model="client_id"
                                    label="Kunder"
                                    :items="clients"
                                    :item-title="item => item.fullname"
                                    :item-value="item => item.id"
                                    autocomplete="off"
                                    @update:modelValue="selectClient"
                                    :rules="[requiredValidator]"/>
                            </VCol>
                            <VCol cols="12" md="12">
                                <VTextField
                                    v-model="email"
                                    disabled
                                    label="E-post"
                                />
                            </VCol>
                        </VRow>
                    </VCardText>

                    <VCardText class="d-flex justify-end gap-3 flex-wrap">
                        <VBtn
                            color="secondary"
                            variant="tonal"
                            @click="isConfirmSendDocumentDialogVisible = false">
                            Avbryt
                        </VBtn>
                        <VBtn type="submit">
                            Skicka
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>
    </section>
</template>

<style scoped>

    ::v-deep .radio-form .v-input--density-comfortable, ::v-deep  .v-radio {
        --v-input-control-height: 0 !important;
    }

    ::v-deep .radio-form .v-selection-control__wrapper {
        height: 20px !important;
    }

    ::v-deep .radio-form .v-icon--size-default {
        font-size: calc(var(--v-icon-size-multiplier) * 1em) !important;
    }

    .v-btn--disabled {
        opacity: 1 !important;
    }

    .border-bottom-secondary {
        border-bottom: 1px solid #d9d9d9;
        padding-bottom: 10px;
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