<script setup>

import router from '@/router'
import { themeConfig } from '@themeConfig'
import { avatarText } from '@/@core/utils/formatters'
import { formatNumber } from '@/@core/utils/formatters'
import { useVehiclesStores } from '@/stores/useVehicles'
import { yearValidator, requiredValidator, emailValidator, phoneValidator } from '@/@core/utils/validators'
import { useTasksStores } from '@/stores/useTasks'
import { useCostsStores } from '@/stores/useCosts'
import { useAuthStores } from '@/stores/useAuth'
import { useDocumentsStores } from '@/stores/useDocuments'

const authStores = useAuthStores()
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

const userData = ref(null)
const role = ref(null)
const supplier = ref([])

const isRequestOngoing = ref(true)
const isConfirmStatusDialogVisible = ref(false)
const isConfirmTaskDialogVisible = ref(false)
const isConfirmUpdateTaskDialogVisible = ref(false)
const isConfirmCreateCostDialogVisible = ref(false)
const isConfirmCreateDocumentDialogVisible = ref(false)
const isConfirmSendDocumentDialogVisible = ref(false)

const selectedTask = ref({})
const comment = ref(null)

const isFormValid = ref(false)
const refForm = ref()
const refTask = ref()
const refUpdate = ref()
const refCost = ref()
const refDocument = ref()
const refSend = ref()
const currentTab = ref('tab-1')
const isMobile = ref(false)

const brands = ref([])
const models = ref([])
const modelsByBrand = ref([])
const currencies = ref([])
const carbodies = ref([])
const gearboxes = ref([])
const ivas = ref([])
const fuels = ref([])
const document_types = ref([])
const states = ref([])
const logo = ref(null)

const vehicle = ref(null)
const vehicle_id = ref(null)
const reg_num = ref('')
const mileage = ref(null)
const brand_id = ref(null)
const model_id = ref(null)
const model = ref(null)
const generation = ref(null)
const car_body_id = ref(null)
const year = ref(null)
const control_inspection = ref(null)
const color = ref(null)
const fuel_id = ref(null)
const gearbox_id = ref(null)
const purchase_price = ref(null)
const iva_purchase_id = ref(null)
const state_id = ref(null)
const state_idOld = ref(null)
const sale_price = ref(null)
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
const currency_id = ref(1)

const costs = ref([])
const type = ref([])
const dateCost = ref([])
const description = ref([])
const value = ref([])
const selectedCost = ref([])

const today = new Date()
const formattedDate = ref(today.toISOString().split('T')[0])
const documents = ref([])
const document_type_id = ref(null)
const filename = ref([])
const reference = ref([])
const alertFile = ref(null)
const selectedIds = ref([])

const clients = ref([])
const cl_id = ref(null)
const mail = ref(null)

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

const isCreateCost = ref(true)
const tasks = ref([])
const measure = ref(null)
const cost = ref(null)
const start_date = ref(null)
const end_date = ref(null)

const optionsRadio = ['Ja', 'Nej', 'Vet ej']

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

async function fetchData() {

    isRequestOngoing.value = true

    if(Number(route.params.id) && route.name === 'dashboard-admin-stock-edit-id') {
        userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
        role.value = userData.value.roles[0].name

        if(role.value === 'Supplier') {
            const { user_data, userAbilities } = await authStores.me(userData.value)

            localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

            ability.update(userAbilities)

            localStorage.setItem('user_data', JSON.stringify(user_data))

            supplier.value = user_data.supplier
        }

        const data = await vehiclesStores.showVehicle(Number(route.params.id))
    
        vehicle.value = data.vehicle
        brands.value = data.brands
        currencies.value = data.currencies
        models.value = data.models
        carbodies.value = data.carbodies
        gearboxes.value = data.gearboxes
        ivas.value = data.ivas
        fuels.value = data.fuels
        document_types.value = data.document_types
        states.value = data.states
        clients.value = data.clients
        client_types.value = data.client_types
        identifications.value = data.identifications

        vehicle_id.value = vehicle.value.id ?? vehicle_id.value
        reg_num.value = vehicle.value.reg_num ?? reg_num.value
        tasks.value = vehicle.value.tasks ?? tasks.value
        costs.value = vehicle.value.costs ?? costs.value
        documents.value = vehicle.value.documents ?? documents.value

        mileage.value = vehicle.value.mileage ?? mileage.value
        generation.value = vehicle.value.generation ?? generation.value
        car_body_id.value = vehicle.value.car_body_id ?? car_body_id.value
        year.value = vehicle.value.year ?? year.value
        control_inspection.value = vehicle.value.control_inspection ?? control_inspection.value
        color.value = vehicle.value.color ?? color.value
        fuel_id.value = vehicle.value.fuel_id ?? fuel_id.value
        gearbox_id.value = vehicle.value.gearbox_id ?? gearbox_id.value
        purchase_price.value = vehicle.value.purchase_price ?? purchase_price.value
        iva_purchase_id.value = vehicle.value.iva_purchase_id ?? iva_purchase_id.value
        state_id.value = vehicle.value.state_id ?? state_id.value
        state_idOld.value = vehicle.value.state_id ?? state_idOld.value
        sale_price.value = vehicle.value.sale_price ?? sale_price.value
        purchase_date.value = vehicle.value.purchase_date === null ? formatDate(new Date()) : vehicle.value.purchase_date
        sale_date.value = vehicle.value.sale_date ?? sale_date.value
        number_keys.value = vehicle.value.number_keys ?? number_keys.value
        service_book.value = vehicle.value.service_book ?? service_book.value
        summer_tire.value = vehicle.value.summer_tire ?? summer_tire.value
        winter_tire.value = vehicle.value.winter_tire ?? winter_tire.value
        last_service.value = vehicle.value.last_service ?? last_service.value
        dist_belt.value = vehicle.value.dist_belt ?? dist_belt.value
        dist_belt.value = vehicle.value.last_dist_belt ?? dist_belt.value
        comments.value = vehicle.value.comments ?? comments.value

        client_type_id.value = vehicle.value.client_purchase?.client_type_id ?? client_type_id.value
        identification_id.value = vehicle.value.client_purchase?.identification_id ?? identification_id.value
        client_id.value = vehicle.value.client_purchase?.client_id ?? client_id.value
        fullname.value = vehicle.value.client_purchase?.fullname ?? fullname.value
        email.value = vehicle.value.client_purchase?.email ?? email.value
        organization_number.value = vehicle.value.client_purchase?.organization_number ?? organization_number.value
        address.value = vehicle.value.client_purchase?.address ?? address.value
        postal_code.value = vehicle.value.client_purchase?.postal_code ?? postal_code.value
        phone.value = vehicle.value.client_purchase?.phone ?? phone.value
        street.value = vehicle.value.client_purchase?.street ?? street.value

        if(vehicle.value.model_id !== null) {
            let modelId = vehicle.value.model_id
            let brandId = models.value.filter(item => item.id === modelId)[0].brand.id
            selectBrand(brandId)
            brand_id.value = brandId
            model_id.value = vehicle.value.model_id
        }

        if(Object.keys(selectedTask.value).length > 0 && selectedTask.value.cost) {
            selectedTask.value = tasks.value.filter(item => item.id === selectedTask.value.id)[0]
            selectedTask.value.cost = formatDecimal(selectedTask.value.cost)
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

const onClearBrand = () => {
    modelsByBrand.value = []
}

const selectModel = selected => {

    model.value = selected !== 0 ? null : model.value

}

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
                    .then(async (res) => {
                        if (res.data.success) {
                            advisor.value = {
                                type: 'success',
                                message: 'Uppgift skapad!',
                                show: true
                            }
                        }
                        isRequestOngoing.value = false

                        measure.value = null
                        cost.value = null
                        start_date.value = null
                        end_date.value = null

                        await fetchData()
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
                    .then(async(res) => {
                        if (res.data.success) {
                            advisor.value = {
                                type: 'success',
                                message: 'Uppgift uppdaterad!',
                                show: true
                            }
                        }

                        isRequestOngoing.value = false
                        selectedTask.value.vehicle_id = null
                        selectedTask.value.measure = null
                        selectedTask.value.cost = null
                        selectedTask.value.start_date = null
                        selectedTask.value.end_date = null

                        await fetchData()
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

const closeTask = () => {
    isConfirmUpdateTaskDialogVisible.value = false
    selectedTask.value.vehicle_id = null
    selectedTask.value.measure = null
    selectedTask.value.cost = null
    selectedTask.value.start_date = null
    selectedTask.value.end_date = null
}

const showTask = taskData => {
    isConfirmUpdateTaskDialogVisible.value = true
    selectedTask.value = { ...taskData }
    selectedTask.value.cost = formatDecimal(selectedTask.value.cost)
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
    type.value = costData.type
    description.value = costData.description
    dateCost.value = costData.date
    value.value = formatDecimal(costData.value)
    isCreateCost.value = false
}

const formatDecimal = (value) => {
    const number = parseFloat(value);

    if (number % 1 !== 0) {
        return number.toFixed(2);
    }

    return number.toString();
}

const handleCost = async () => {
    refCost.value?.validate().then(async({ valid }) => {
        if (valid) {
            let formData = new FormData()

            formData.append('vehicle_id', vehicle_id.value)
            formData.append('type', type.value)
            formData.append('description', description.value)
            formData.append('value', value.value)
            formData.append('date', dateCost.value)

            isRequestOngoing.value = true

            if(isCreateCost.value) {
                costsStores.addCost(formData)
                    .then(async(res) => {
                        if (res.data.success) {
                            advisor.value = {
                                type: 'success',
                                message: 'Kostnader skapad!',
                                show: true
                            }
                        }
                        
                        isRequestOngoing.value = false
                        await fetchData()
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
                    .then(async(res) => {
                        if (res.data.success) {
                            advisor.value = {
                                type: 'success',
                                message: 'Kostnader uppdaterad!',
                                show: true
                            }
                        }

                        isRequestOngoing.value = false
                        await fetchData()
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
            type.value = null
            description.value = null
            value.value = null
            dateCost.value = null

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

const showDocument = () => {
    isConfirmCreateDocumentDialogVisible.value = true
    alertFile.value = null
    document_type_id.value = null
    reference.value = null
    filename.value = []
}

const handleFileUpload = async (event) => {

    refDocument.value?.validate().then(async({ valid }) => {
        if (valid) {
            const file = filename.value[0]
            if (!file) return

            let formData = new FormData()

            formData.append('vehicle_id', vehicle_id.value)
            formData.append('document_type_id', document_type_id.value)
            formData.append('reference', reference.value)
            formData.append('file', file)

            isRequestOngoing.value = true

            documentsStores.addDocument(formData)
                .then(async(res) => {
                    if (res.data.success) {
                        advisor.value = {
                            type: 'success',
                            message: 'Dokument skapad!',
                            show: true
                        }

                        isConfirmCreateDocumentDialogVisible.value = false
                        await fetchData()
                    } else {
                        alertFile.value = res.data.message
                        setTimeout(() => {
                            alertFile.value = null
                        }, 5000)
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

const selectCl = client => {
    if (client) {
        let _client = clients.value.find(item => item.id === client)
    
        mail.value = _client.email
    }
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

const handleSendMail = () => {
    if (!selectedIds.value.length) return

    refSend.value?.validate().then(({ valid }) => {
        if (valid) {
            let formData = new FormData()

            formData.append('ids', selectedIds.value)
            formData.append('email', mail.value)

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
                mail.value = null
                cl_id.value = null
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

            if(state_id.value === 12) {
                router.push({ name : 'dashboard-admin-sold-id'})
                return false;
            }

            formData.append('id', Number(route.params.id))
            formData.append('_method', 'PUT')
            formData.append('reg_num', reg_num.value)
            formData.append('brand_id', brand_id.value)
            formData.append('model_id', model_id.value)
            formData.append('model', model.value)
            formData.append('car_body_id', car_body_id.value)
            formData.append('gearbox_id', gearbox_id.value)
            formData.append('iva_purchase_id', iva_purchase_id.value)
            formData.append('currency_id', currency_id.value)
            formData.append('state_id', state_id.value)
            formData.append('mileage', mileage.value)
            formData.append('generation', generation.value)
            formData.append('year', year.value)
            formData.append('control_inspection', control_inspection.value)
            formData.append('color', color.value)
            formData.append('fuel_id', fuel_id.value)
            formData.append('purchase_price', purchase_price.value)
            formData.append('purchase_date', purchase_date.value)
            formData.append('sale_price', sale_price.value)
            formData.append('sale_date', sale_date.value)
            formData.append('number_keys', number_keys.value)
            formData.append('service_book', service_book.value)
            formData.append('summer_tire', summer_tire.value)
            formData.append('winter_tire', winter_tire.value)
            formData.append('last_service', last_service.value)
            formData.append('dist_belt', dist_belt.value)
            formData.append('last_dist_belt', last_dist_belt.value)
            formData.append('comments', comments.value)

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

const getFlag = (currency_id) => {
    return currencies.value.filter(item => item.id === currency_id)[0].flag
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
        <VAlert
            v-if="advisor.show"
            :type="advisor.type"
            class="mb-6">
                
            {{ advisor.message }}
        </VAlert>
        <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit">
            <VRow>
                <VCol cols="12" md="12" class="py-0">
                    <div class="d-flex flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
                        <div class="d-flex align-center">
                            <VAvatar
                                v-if="model_id === null || logo === null"
                                size="x-large"
                                variant="tonal"
                                color="secondary"
                            >
                                <VIcon size="x-large" icon="mdi-image-outline" />                        
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
                                :to="{ name: state_id === 12 ? 'dashboard-admin-sold' :'dashboard-admin-stock' }"
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
                                <VTab>Kund</VTab>
                                <VTab>Information om bilen</VTab>
                                <VTab>Planerade åtgärder</VTab>
                                <VTab>Kostnader</VTab>
                                <VTab>Dokument</VTab>
                            </VTabs>
                            <VCardText class="px-2">
                                <VWindow v-model="currentTab" class="pt-3">
                                    <!-- Fordon -->
                                    <VWindowItem class="px-md-5">
                                        <h6 class="text-md-h4 text-h5 font-weight-medium mb-7">
                                            Grund och teknisk information
                                        </h6>
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
                                                    suffix="Mil"
                                                    label="Miltal"
                                                    min="0"
                                                />
                                            </VCol>
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
                                                    @update:modelValue="selectBrand"
                                                    @click:clear="onClearBrand"/>
                                            </VCol>
                                            <VCol cols="12" :md="model_id !== 0 ? 6 : 3">
                                                <VAutocomplete
                                                    v-model="model_id"
                                                    label="Modell"
                                                    :items="getModels"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"
                                                    @update:modelValue="selectModel"/>
                                            </VCol>
                                            <VCol cols="12" md="3" v-if="model_id === 0">
                                                <VTextField
                                                    v-model="model"
                                                    label="Modellens namn"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="generation"
                                                    label="Generation"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
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
                                                    v-model="purchase_date"
                                                    density="compact"
                                                    :config="startDateTimePickerConfig"
                                                    label="Inköpsdatum"
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
                                                <VAutocomplete
                                                    v-model="fuel_id"
                                                    label="Drivmedel"
                                                    :items="fuels"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
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
                                                    min="0"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="currency_id"
                                                    label="Valuta"
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
                                                </VAutocomplete>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="iva_purchase_id"
                                                    label="VMB / Moms"
                                                    :items="ivas"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VAutocomplete
                                                    v-model="state_idOld"
                                                    label="Status"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"/>
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!-- Kund -->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Säljare
                                                </h6>
                                                <VRow>
                                                    <VCol cols="12" md="12" v-if="vehicle.client_purchase === null">
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
                                                            autocomplete="off"/>
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="fullname"
                                                            label="Namn"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="12">
                                                        <VTextField
                                                            v-model="address"
                                                            label="Adress"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="postal_code"
                                                            label="Postnummer"
                                                        />
                                                    </VCol>
                                                    <VCol cols="12" md="6">
                                                        <VTextField
                                                            v-model="street"
                                                            label="Stad"
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
                                                            autocomplete="off"/>
                                                    </VCol>
                                                    <VCol cols="12" md="12">
                                                        <VTextField
                                                            v-model="email"
                                                            :rules="[emailValidator]"
                                                            label="E-post"
                                                        />
                                                    </VCol>
                                                </VRow>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <h6 class="text-md-h4 text-h6 font-weight-medium mb-5">
                                                    Köpare
                                                </h6>
                                                <VList class="card-list mt-2">
                                                    <VListItem>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Namn:
                                                                <span class="text-body-2">
                                                                    {{ userData.name }} {{ userData.last_name }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Org/personummer:
                                                                <span class="text-body-2">
                                                                    {{ role === 'Supplier' ? supplier.organization_number : userData.user_details.organization_number }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Adress:
                                                                <span class="text-body-2">
                                                                    {{ role === 'Supplier' ? supplier.address : userData.user_details.address }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Postnr. ort:
                                                                <span class="text-body-2">
                                                                    {{ 
                                                                        role === 'Supplier' ? 
                                                                        supplier.street + ' ' +  supplier.postal_code : 
                                                                        userData.user_details.street  + ' ' +  userData.user_details.postal_code
                                                                    }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Telefon:
                                                                <span class="text-body-2">
                                                                    {{ role === 'Supplier' ? supplier.phone : userData.user_details.phone }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                E-post
                                                                <span class="text-body-2">
                                                                    {{ userData.email }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                        <VListItemTitle>
                                                            <h6 class="text-base font-weight-semibold">
                                                                Bilfirma:
                                                                <span class="text-body-2">
                                                                    {{ role === 'Supplier' ? supplier.company : userData.user_details.company }}
                                                                </span>
                                                            </h6>
                                                        </VListItemTitle>
                                                    </VListItem>
                                                </VList>
                                                <VRow v-if="vehicle.client_purchase === null">
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
                                    <!-- Information om bilen -->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="number_keys"
                                                    type="number"
                                                    label="Antal nycklar"
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
                                            <VCol cols="12" md="12" v-if="dist_belt === 0">
                                                <VTextField
                                                    v-model="last_dist_belt"
                                                    label="Kamrem bytt vid Mil/datum"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="12">
                                                <VTextarea
                                                    v-model="comments"
                                                    rows="4"
                                                    label="Anteckningar"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!-- Planerade åtgärder -->
                                    <VWindowItem class="px-md-5">
                                        <div class="d-flex flex-column flex-md-row text-center justify-md-space-between gap-x-6" :class="tasks.length === 0 ? 'border-bottom-secondary' : ''">
                                            <h6 class="text-md-h4 text-h5 font-weight-medium mb-5 mb-0">
                                                Övrigt
                                            </h6>
                                            <VBtn class="w-100 w-md-auto" @click="isConfirmTaskDialogVisible = true">
                                                Lägg till åtgärd
                                            </VBtn>
                                        </div>

                                        <div v-if="tasks.length === 0" class="mt-10 text-center">Inga åtgärder hittades</div>

                                        <VRow no-gutters v-else class="mt-5">
                                            <VCol
                                                v-for="(task, index) in tasks"
                                                :key="index"
                                                cols="12" md="4"
                                            >
                                                <VCard
                                                    flat
                                                    color="#E3DEEB"
                                                    class="mx-1 my-1"
                                                    style="box-shadow: none !important; border-radius: 12px !important;"
                                                >
                                                    <VCardItem>
                                                        <template #prepend>
                                                            <VIcon
                                                            size="1.9rem"
                                                            icon="mdi-note-outline"
                                                            />
                                                        </template>
                                                    <VCardTitle> {{ index + 1 }}</VCardTitle>
                                                    </VCardItem>

                                                    <VCardText>
                                                        <p class="clamp-text mb-0">
                                                            <strong>Vad ska göras?:</strong> {{ task.measure }}
                                                        </p>
                                                        <p class="clamp-text mb-0">
                                                            <strong>Beräknad kostnad (kr):</strong> {{ task.cost }} kr
                                                        </p>
                                                        <p class="clamp-text mb-0">
                                                            <strong>Planerat startdatum:</strong> {{ task.start_date }}
                                                        </p>
                                                        <p class="clamp-text mb-0">
                                                            <strong>Förväntat slutdatum:</strong> {{ task.end_date }}
                                                        </p>
                                                        <p class="clamp-text mb-0 mt-2">
                                                            <VExpansionPanels>
                                                                <VExpansionPanel>
                                                                    <VExpansionPanelTitle>kommentarer</VExpansionPanelTitle>
                                                                    <VExpansionPanelText>
                                                                        <VAlert 
                                                                            v-for="(comment, index) in task.comments" 
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
                                                                    </VExpansionPanelText>
                                                                </VExpansionPanel>
                                                            </VExpansionPanels>
                                                        </p>
                                                    </VCardText>

                                                    <VCardText class="d-flex justify-space-between align-center flex-wrap">
                                                    <div class="text-no-wrap">
                                                        <VAvatar
                                                            color="#E3DEEB"
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
                                                        <span class="ms-2">{{ task.user.name }} {{ task.user.last_name }}</span>
                                                    </div>

                                                    <div class="d-flex align-center">
                                                        <VIcon
                                                            icon="tabler-edit"
                                                            class="me-1 cursor-pointer"
                                                            @click="showTask(task)"
                                                        />
                                                        <VIcon
                                                            icon="tabler-trash"
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
                                        <div class="d-flex align-center flex-wrap pb-4 w-100 w-md-auto" :class="costs.length === 0 ? 'border-bottom-secondary' : ''">           
                                            <VSpacer class="d-none d-md-block"/>   
                                            <VBtn
                                                v-if="$can('edit', 'stock')"
                                                class="w-100 w-md-auto"
                                                prepend-icon="tabler-plus"
                                                @click="isConfirmCreateCostDialogVisible = true">
                                                Lägg till kostnad
                                            </VBtn>
                                        </div>
                                        <div v-if="costs.length === 0" class="mt-10 text-center">Ingen kostnader registrerade ännu</div>
                                        <VTable v-else class="text-no-wrap">
                                            <!-- 👉 table head -->
                                            <thead>
                                                <tr>
                                                    <th scope="col">Händelse</th>
                                                    <th scope="col">Datum</th>
                                                    <th scope="col">Typ</th>
                                                    <th scope="col" class="text-end">Belopp (kr)</th>
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
                                                    <td> {{ cost.date }} </td>
                                                    <td class="text-wrap"> {{ cost.type }} </td>
                                                    <td class="text-end"> {{ formatNumber(cost.value ?? 0) }} kr</td>
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
                                                                <VListItemTitle>Ta bort</VListItemTitle>
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
                                        <VCardText class="d-block d-md-flex text-center align-center flex-wrap px-0 py-3" v-if="costs.length > 0">
                                            <span class="d-block d-md-flex text-sm text-disabled">
                                                <strong class="d-block me-md-5">Totalt: {{ formatNumber(costs.reduce((sum, item) => sum + parseFloat(item.value), 0) ?? 0) }} kr</strong>
                                            </span>
                                        </VCardText>
                                    </VWindowItem>
                                    <!-- Dokument -->
                                    <VWindowItem class="px-md-5">
                                        <div class="d-flex align-center flex-wrap pb-4 w-100 w-md-auto" :class="documents.length === 0 ? 'border-bottom-secondary' : ''">           
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
                                                @click="showDocument">
                                                Ladda upp dokument
                                            </VBtn>

                                        </div>
                                        <div v-if="documents.length === 0" class="mt-10 text-center">Inga dokument uppladdade</div>
                                        <VTable v-else class="text-no-wrap">
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
                                                    <th scope="col">Namn</th>
                                                    <th scope="col">Dokumenttyp</th>
                                                    <th scope="col">Datum</th>
                                                    <th scope="col">Skapad av</th>                                                        
                                                    <th scope="col" v-if="$can('edit', 'stock') || $can('delete', 'stock')"></th>
                                                </tr>
                                            </thead>
                                            <!-- 👉 table body -->
                                            <tbody>
                                                <tr 
                                                    v-for="(document, index) in documents"
                                                    :key="index"
                                                    style="height: 3rem;">
                                                    <td style="min-width: 30px;">
                                                        <VCheckbox
                                                            :value="document.id"
                                                            v-model="selectedIds"
                                                            density="compact"
                                                            hide-details
                                                        />
                                                    </td>
                                                    <td class="text-wrap">{{ document.file.split('/').pop() }} </td>
                                                    <td> {{ document.document_type_id === 4 ? document.reference : document.type.name }} </td>
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
                                                    <td> {{ document.user.name }} {{ document.user.last_name }}</td>
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
                                                                    <VListItemTitle>Ta bort</VListItemTitle>
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
                        <VAutocomplete
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
                <VCard title="Lägg till åtgärd för fordonet" subtitle="Fyll i planerad åtgärd nedan">
                    <VDivider />
                    <VCardText>
                        <VRow>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="measure"
                                    label="Vad ska göras?"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="cost"
                                    type="number"
                                    min="0"
                                    label="Beräknad kostnad (kr)"
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
                                    label="Planerat startdatum"
                                    clearable
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <AppDateTimePicker
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="end_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    label="Förväntat slutdatum"
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
                
            <DialogCloseBtn @click="closeTask" />

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
                                    label="Vad ska göras?"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="selectedTask.cost"
                                    type="number"
                                    min="0"
                                    label="Beräknad kostnad (kr)"
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
                                    label="Planerat startdatum"
                                    clearable
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <AppDateTimePicker
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="selectedTask.end_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    label="Förväntat slutdatum"
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
                                        <span v-if="history.is_created"><strong>{{ history.user.name }} {{ history.user.last_name }}</strong> lade till detta kort till <strong>Övrigt.</strong></span>
                                        <span v-else><strong>{{ history.user.name }} {{ history.user.last_name }}</strong> uppdaterade kortet.</span>
                                        <span>  {{}}
                                            {{ new Date(history.created_at).toLocaleString('sv-SE', { 
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
                            @click="closeTask">
                            Avbryt
                        </VBtn>
                        <VBtn class="mt-4" type="submit">
                            Uppdatering
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>

        <!-- 👉 Create document -->
        <VDialog
            v-model="isConfirmCreateDocumentDialogVisible"
            persistent
            class="v-dialog-sm">
            <!-- Dialog close btn -->
                
            <DialogCloseBtn @click="isConfirmCreateDocumentDialogVisible = !isConfirmCreateDocumentDialogVisible" />

            <!-- Dialog Content -->
            <VForm
                ref="refDocument"
                @submit.prevent="handleFileUpload">
                <VCard title="Ladda upp dokument">
                    <VDivider />
                    <VCardText style="max-height: 450px;">
                        <VAlert
                            v-if="alertFile"
                            color="error"
                            icon="mdi-alert-octagon-outline"
                            variant="tonal"
                            class="mb-5"
                            >
                            {{alertFile}}
                        </VAlert>
                        <VRow>
                            <VCol cols="12" md="12">
                                <VAutocomplete
                                    v-model="document_type_id"
                                    label="Dokumenttyp"
                                    :items="document_types"
                                    :item-title="item => item.name"
                                    :item-value="item => item.id"
                                    autocomplete="off"
                                    :rules="[requiredValidator]"/>
                            </VCol>
                             <VCol cols="12" md="12" v-if="document_type_id === 4">
                                <VTextField
                                    v-model="reference"
                                    label="Övrigt"
                                    :rules="document_type_id === 4 ? [requiredValidator] : []"
                                />
                            </VCol>
                            <VCol cols="12" md="3">
                                <VTextField
                                    :model-value="formattedDate"
                                    disabled
                                    label="Datum"
                                />
                            </VCol>
                            <VCol cols="12" md="9">
                                <VFileInput       
                                    v-model="filename"                   
                                    label="Välj fil"
                                    placeholder="Välj fil"
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
                            @click="isConfirmCreateDocumentDialogVisible = false">
                             Avbryt
                        </VBtn>
                        <VBtn class="mt-4" type="submit">
                             Ladda upp
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
                                <VTextField
                                    v-model="type"
                                    label="Vad gäller kostnaden?"
                                    placeholder="T.ex. 'Reparation', 'Service', 'Besiktning'..."
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="12">
                                <VTextarea
                                    v-model="description"
                                    rows="4"
                                    label="Beskrivning (valfritt)"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="value"
                                    type="number"
                                    label="Kostnader"
                                    min="0"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <AppDateTimePicker
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="dateCost"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    label="Datum"
                                    :rules="[requiredValidator]"
                                    clearable
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
                                <VAutocomplete
                                    v-model="cl_id"
                                    label="Kunder"
                                    :items="clients"
                                    :item-title="item => item.fullname"
                                    :item-value="item => item.id"
                                    autocomplete="off"
                                    @update:modelValue="selectCl"
                                    :rules="[requiredValidator]"/>
                            </VCol>
                            <VCol cols="12" md="12">
                                <VTextField
                                    v-model="mail"
                                    label="E-post"
                                    :rules="[emailValidator]"
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
        opacity: 1 !important;
    }

    .border-bottom-secondary {
        border-bottom: 1px solid #d9d9d9;
        padding-bottom: 10px;
    }

    .justify-content-center {
        justify-content: center !important;
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