<script setup>

import { onBeforeRouteLeave } from "vue-router";
import { useDisplay } from "vuetify";
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { ref, nextTick, onMounted, onBeforeUnmount, computed, inject, watch } from 'vue'
import { themeConfig } from '@themeConfig'
import { avatarText, formatNumber } from '@/@core/utils/formatters'
import { useVehiclesStores } from '@/stores/useVehicles'
import { useCarInfoStores } from '@/stores/useCarInfo'
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { usePersonInfoStores } from '@/stores/usePersonInfo'
import { yearValidator, requiredValidator, emailValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useTasksStores } from '@/stores/useTasks'
import { useAuthStores } from '@/stores/useAuth'
import { useDocumentsStores } from '@/stores/useDocuments'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useConfigsStores } from '@/stores/useConfigs'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import router from '@/router'

const ability = useAppAbility()
const authStores = useAuthStores()
const vehiclesStores = useVehiclesStores()
const carInfoStores = useCarInfoStores()
const tasksStores = useTasksStores()
const documentsStores = useDocumentsStores()
const configsStores = useConfigsStores()
const companyInfoStores = useCompanyInfoStores()
const personInfoStores = usePersonInfoStores()

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const emitter = inject("emitter")
const route = useRoute()

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const { width: windowWidth } = useWindowSize();
const sectionEl = ref(null);

const userData = ref(null)
const company = ref(null)
const role = ref(null)

const isRequestOngoing = ref(true)
const isConfirmStatusTaskDialogVisible = ref(false)
const isConfirmStatusDialogVisible = ref(false)
const isConfirmTaskDialogVisible = ref(false)
const isConfirmTaskMobileDialogVisible = ref(false)
const isConfirmUpdateTaskDialogVisible = ref(false)
const isConfirmUpdateTaskMobileDialogVisible = ref(false)
const isConfirmCreateDocumentDialogVisible = ref(false)
const isConfirmCreateDocumentMobileDialogVisible = ref(false)
const isConfirmSendDocumentDialogVisible = ref(false)

const selectedTask = ref({
    measure: null,
    description: null,
    cost: null,
    start_date: null,
    end_date: null,
    is_cost: 0,
    comments: [],
    histories: []
})

const comment = ref(null)

const isFormValid = ref(false)
const refForm = ref()
const refTask = ref()
const refUpdate = ref()
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
const state = ref('I lager')
const state_id = ref(10)
const state_idOld = ref(10)
const sale_price = ref(null)
const purchase_date = ref(null)
const chassis = ref(null)
const engine = ref(null)
const car_name = ref(null)
const sale_date = ref(null)
const number_keys = ref(null)
const service_book = ref(0)
const summer_tire = ref(0)
const winter_tire = ref(0)
const last_service = ref(null)
const last_service_date = ref(null)
const dist_belt = ref(0)
const last_dist_belt = ref(null)
const last_dist_belt_date = ref(null)
const comments = ref(null)
const currency_id = ref(1)

const description = ref(null)

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
const save_client = ref(false)
const disabled_client = ref(false)

const tasks = ref([])
const measure = ref(null)
const cost = ref(null)
const start_date = ref(null)
const end_date = ref(null)
const is_cost = ref(0)
const isEdit = ref(false);

const optionsRadio = ['Ja', 'Nej', 'Vet ej']

const initialVehicleData = ref(null);
const savedVehicleData = ref(null);
const allowNavigation = ref(false);
const nextRoute = ref(null);
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);
const isConfirmLeaveVisible = ref(false);
const err = ref(null);

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

const currentVehicleData = computed(() => ({
  // Datos del vehículo (tab-1)
  reg_num: reg_num.value,
  mileage: mileage.value,
  brand_id: brand_id.value,
  model_id: model_id.value,
  model: model.value,
  generation: generation.value,
  car_body_id: car_body_id.value,
  year: year.value,
  control_inspection: control_inspection.value,
  color: color.value,
  fuel_id: fuel_id.value,
  gearbox_id: gearbox_id.value,
  chassis: chassis.value,
  engine: engine.value,
  car_name: car_name.value,
  number_keys: number_keys.value,
  service_book: service_book.value,
  summer_tire: summer_tire.value,
  winter_tire: winter_tire.value,
  last_service: last_service.value,
  last_service_date: last_service_date.value,
  dist_belt: dist_belt.value,
  last_dist_belt: last_dist_belt.value,
  last_dist_belt_date: last_dist_belt_date.value,
  comments: comments.value,
  
  // Datos de precio (tab-2)
  purchase_price: purchase_price.value,
  iva_purchase_id: iva_purchase_id.value,
  currency_id: currency_id.value,
  sale_price: sale_price.value,
  purchase_date: purchase_date.value,
  sale_date: sale_date.value,
  state_id: state_id.value,
  
  // Datos del cliente (tab-3)
  client_type_id: client_type_id.value,
  identification_id: identification_id.value,
  client_id: client_id.value,
  fullname: fullname.value,
  email: email.value,
  organization_number: organization_number.value,
  address: address.value,
  street: street.value,
  postal_code: postal_code.value,
  phone: phone.value,
  save_client: save_client.value,
  
  // Tareas y documentos (tab-5 y tab-6)
  tasks: JSON.stringify(tasks.value),
  documents: JSON.stringify(documents.value.map(doc => ({
    id: doc.id,
    document_type_id: doc.document_type_id,
    reference: doc.reference,
    file: doc.file
  }))),
}));

const isDirty = computed(() => {
  if (!initialVehicleData.value) return false;
  try {
    return JSON.stringify(currentVehicleData.value) !== JSON.stringify(initialVehicleData.value);
  } catch (e) {
    return true;
  }
});

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

const formatDateDisplay = (dateString) => {
    if (!dateString) return ''
    // Parsear directamente el string para evitar problemas de zona horaria
    const [year, month, day] = dateString.split('T')[0].split('-')
    return `${year}/${month}/${day}`
}

const isDateOverdue = (dateString) => {
    if (!dateString) return false
    const taskDate = new Date(dateString.split('T')[0])
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    return taskDate < today
}

const formatCommentDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    const months = ['jan', 'feb', 'mars', 'apr', 'maj', 'juni', 'juli', 'aug', 'sept', 'okt', 'nov', 'dec']
    const day = date.getDate()
    const month = months[date.getMonth()]
    const year = date.getFullYear()
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    return `${day} ${month} ${year}, ${hours}:${minutes}`
}

watchEffect(fetchData)

async function fetchData() {

    isRequestOngoing.value = true
    
    // Intentar recuperar reg_num de sessionStorage primero, luego del store
    const storedRegNum = sessionStorage.getItem('temp_reg_num')
    reg_num.value = storedRegNum || await vehiclesStores.getRegNum || ''
    
    // Si obtuvimos el reg_num del store, guardarlo en sessionStorage
    if (!storedRegNum && vehiclesStores.getRegNum) {
        sessionStorage.setItem('temp_reg_num', vehiclesStores.getRegNum)
    }
    
    // Intentar recuperar commonInfo de sessionStorage primero, luego del store
    const storedCommonInfo = sessionStorage.getItem('temp_common_info')
    let data = storedCommonInfo ? JSON.parse(storedCommonInfo) : await vehiclesStores.getCommonInfo || {}
    
    // Si obtuvimos commonInfo del store, guardarlo en sessionStorage
    if (!storedCommonInfo && vehiclesStores.getCommonInfo) {
        sessionStorage.setItem('temp_common_info', JSON.stringify(vehiclesStores.getCommonInfo))
    }

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
    } else if(role.value === 'User') {
        company.value = user_data.supplier.boss.user.user_detail
        company.value.email = user_data.supplier.boss.user.email
        company.value.name = user_data.supplier.boss.user.name
        company.value.last_name = user_data.supplier.boss.user.last_name
    } else {
        await configsStores.getFeature('company')
        await configsStores.getFeature('logo')

        company.value = configsStores.getFeaturedConfig('company')
        company.value.logo = configsStores.getFeaturedConfig('logo').logo
    }

    brands.value = data.brands
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
    currencies.value = data.currencies

    await searchVehicleByPlate()

    purchase_date.value = formatDate(new Date())

    // Save initial state for dirty checking
    await nextTick();
    initialVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));
    savedVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));

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
        const isSuccess = carRes.success === true || carRes.sucess === true

        if (isSuccess && carRes.result) {
            // Actualizar año del modelo
            if (carRes.result.model_year) {
                year.value = carRes.result.model_year
            }
            
            // Actualizar generación
            if (carRes.result.generation) {
                generation.value = carRes.result.generation
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
            
            // Actualizar tipo de carrocería (Kaross)
            if (carRes.result.car_body_id) {
                car_body_id.value = carRes.result.car_body_id
            }
            
            // Actualizar tipo de combustible (Drivmedel)
            if (carRes.result.fuel_id) {
                fuel_id.value = carRes.result.fuel_id
            }

            // Actualizar caja de cambios (Växellåda)
            if (carRes.result.gearbox_id) {
                gearbox_id.value = carRes.result.gearbox_id
            }

            if (carRes.result.color) {
                color.value = carRes.result.color
            }

            if (carRes.result.mileage) {
                mileage.value = carRes.result.mileage
            }

            if (carRes.result.control_inspection) {
                control_inspection.value = carRes.result.control_inspection
            }

            if (carRes.result.chassis_number) {
                chassis.value = carRes.result.chassis_number
            }

            if (carRes.result.engine) {
                engine.value = carRes.result.engine
            }

            if (carRes.result.car_name) {
                car_name.value = carRes.result.car_name
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
            message: 'Fel vid hämtning av fordonsdata',
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

const createTask = async () => {

    refTask.value?.validate().then(async({ valid }) => {
            if (valid) {
                // let formData = new FormData()

                // formData.append('vehicle_id', vehicle_id.value)
                // formData.append('measure', measure.value)
                // formData.append('cost', cost.value)
                // formData.append('description', description.value)
                // formData.append('start_date', start_date.value)
                // formData.append('end_date', end_date.value)
                // formData.append('is_cost', is_cost.value)

                var currentTasks = tasks.value || [];

                currentTasks.push({
                    id: Date.now(), // Generar un ID temporal para el nuevo task
                    measure: measure.value,
                    cost: cost.value,
                    description: description.value,
                    start_date: start_date.value,
                    end_date: end_date.value,
                    is_cost: is_cost.value,
                    comments: []
                })

                tasks.value = currentTasks

                isConfirmTaskMobileDialogVisible.value = false
                // isRequestOngoing.value = true

                // tasksStores.addTask(formData)
                //     .then(async (res) => {
                //         if (res.data.success) {
                //             advisor.value = {
                //                 type: 'success',
                //                 message: 'Uppgift skapad!',
                //                 show: true
                //             }
                //         }
                //         isRequestOngoing.value = false

                //         measure.value = null
                //         cost.value = null
                //         description.value = null
                //         start_date.value = null
                //         end_date.value = null
                //         is_cost.value = 0

                //         await fetchData()
                //     })
                //     .catch((err) => {
                        
                //         advisor.value = {
                //             type: 'error',
                //             message: err.message,
                //             show: true
                //         }

                //         isRequestOngoing.value = false
                //     })
                
                isRequestOngoing.value = false

                measure.value = null
                cost.value = null
                description.value = null
                start_date.value = null
                end_date.value = null
                is_cost.value = 0

                await nextTick();
                initialVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));
                savedVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));

                isConfirmTaskDialogVisible.value = false

                // setTimeout(() => {
                //     advisor.value = {
                //         type: '',
                //         message: '',
                //         show: false
                //     }
                // }, 3000)
            }
    })
}

const chageStatus = (statusId) => {
    state.value = states.value.find(item => item.id === statusId).name
}

const updateTask = async () => {

    refUpdate.value?.validate().then(async({ valid }) => {
            if (valid) {
                const taskId = selectedTask.value.id
                const taskIndex = tasks.value.findIndex(item => item.id === taskId)

                if (taskIndex !== -1) {
                    tasks.value[taskIndex] = { ...selectedTask.value }
                }


                // let formData = new FormData()

                // formData.append('id', selectedTask.value.id)
                // formData.append('_method', 'PUT')
                // formData.append('vehicle_id', selectedTask.value.vehicle_id)
                // formData.append('measure', selectedTask.value.measure)
                // formData.append('description', selectedTask.value.description)
                // formData.append('cost', selectedTask.value.cost)
                // formData.append('start_date', selectedTask.value.start_date)
                // formData.append('end_date', selectedTask.value.end_date)

                isRequestOngoing.value = true
                isConfirmUpdateTaskMobileDialogVisible.value = false
                
                // let data = {
                //     data: formData, 
                //     id: selectedTask.value.id
                // }

                // tasksStores.updateTask(data)
                //     .then(async(res) => {
                //         if (res.data.success) {
                //             advisor.value = {
                //                 type: 'success',
                //                 message: 'Uppgift uppdaterad!',
                //                 show: true
                //             }
                //         }

                //         isRequestOngoing.value = false
                //         selectedTask.value.vehicle_id = null
                //         selectedTask.value.measure = null
                //         selectedTask.value.cost = null
                //         selectedTask.value.description = null
                //         selectedTask.value.start_date = null
                //         selectedTask.value.end_date = null
                //         selectedTask.value.is_cost = 0

                //         await fetchData()
                //     })
                //     .catch((err) => {
                        
                //         advisor.value = {
                //             type: 'error',
                //             message: err.message,
                //             show: true
                //         }

                //         isRequestOngoing.value = false
                //     })
                
                isRequestOngoing.value = false
                selectedTask.value.vehicle_id = null
                selectedTask.value.measure = null
                selectedTask.value.cost = null
                selectedTask.value.description = null
                selectedTask.value.start_date = null
                selectedTask.value.end_date = null
                selectedTask.value.is_cost = 0

                await nextTick();
                initialVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));
                savedVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));

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
    isConfirmTaskDialogVisible.value = false
    isConfirmTaskMobileDialogVisible.value = false
    isConfirmUpdateTaskDialogVisible.value = false
    isConfirmUpdateTaskMobileDialogVisible.value = false
    isEdit.value = false
    is_cost.value = 0
    selectedTask.value.vehicle_id = null
    selectedTask.value.measure = null
    selectedTask.value.cost = null
    selectedTask.value.start_date = null
    selectedTask.value.end_date = null
    selectedTask.value.is_cost = 0
}

const showStatusModal = (taskData) => {
    selectedTask.value = {
        ...taskData,
        start_date: taskData.start_date ?? null,
        end_date: taskData.end_date ?? null
    }

    isConfirmStatusTaskDialogVisible.value = true
}

const updateTypeTask = async () => {
    isRequestOngoing.value = true

    // await tasksStores.typeTask(selectedTask.value.id)

    const taskData = { ...selectedTask.value }
    selectedTask.value = {
        ...taskData,
        is_cost: 1
    }

    const taskId = selectedTask.value.id
    const taskIndex = tasks.value.findIndex(item => item.id === taskId)

    if (taskIndex !== -1) {
        tasks.value[taskIndex] = { ...selectedTask.value }
    }

    isRequestOngoing.value = false

    await nextTick();
    initialVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));
    savedVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));

    isConfirmStatusTaskDialogVisible.value = false

    advisor.value = {
        type: 'success',
        message: 'Uppgift uppdaterad!',
        show: true
    }

    // await fetchData()

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)

    return true
}

const showTask = (taskData, isMobile = false, is_edit = false) => {
    isEdit.value = is_edit;
    selectedTask.value = {
        ...taskData,
        start_date: taskData.start_date ?? null,
        end_date: taskData.end_date ?? null
    }
    selectedTask.value.cost = formatDecimal(selectedTask.value.cost)
    
    if (isMobile) {
        isConfirmUpdateTaskMobileDialogVisible.value = true
    } else {
        isConfirmUpdateTaskDialogVisible.value = true
    }
}

const removeTask = async (task) => {
    // let res = await tasksStores.deleteTask(task.id)

    const taskId = task.id
    const taskIndex = tasks.value.findIndex(item => item.id === taskId)

    if (taskIndex !== -1) {
        tasks.value.splice(taskIndex, 1)
    }

    advisor.value = {
        type: 'success',
        message: 'Uppgift borttagen!',
        show: true
    }

    // await fetchData()

    await nextTick();
    initialVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));
    savedVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));

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
        
        const taskId = selectedTask.value.id

        const commentsTask = tasks.value.find(item => item.id === taskId)
        const currentComments = commentsTask && commentsTask.comments ? commentsTask.comments : []
        currentComments.push({
            comment: comment.value,
            id: Date.now(), // Generar un ID temporal para el nuevo comentario
            user: userData.value,
            user_id: userData.value.id,
            vehicle_task_id: taskId
        })

        const taskIndex = tasks.value.findIndex(item => item.id === taskId)

        if (taskIndex !== -1) {
            tasks.value[taskIndex].comments = currentComments
        }
        
        // await tasksStores.sendComment({ id: taskId, comment: comment.value})
        
        isRequestOngoing.value = false
        
        // await refreshTasks()
        
        await nextTick()
        
        // Actualizar selectedTask con los datos frescos
        const updatedTask = tasks.value.find(item => item.id === taskId)

        if (updatedTask) {
            selectedTask.value = {
                ...updatedTask,
                cost: formatDecimal(updatedTask.cost)
            }
        }

        comment.value = null

        advisor.value = {
            type: 'success',
            message: 'Kommentar skapad!',
            show: true
        }

        setTimeout(() => {
            advisor.value = {
                type: '',
                message: '',
                show: false
            }
        }, 3000)

        return true
    }
}

const editComment = async (commentData) => {
    if(commentData.comment !== null && commentData.comment !== '') {
        isRequestOngoing.value = true
        
        const taskId = selectedTask.value.id

        const commentsTask = tasks.value.find(item => item.id === taskId)
        const currentComments = commentsTask && commentsTask.comments ? commentsTask.comments : []
        const commentIndex = currentComments.findIndex(item => item.id === commentData.id)

        if (commentIndex !== -1) {
            currentComments[commentIndex].comments = commentData.comment
        }

        const taskIndex = tasks.value.findIndex(item => item.id === taskId)

        if (taskIndex !== -1) {
            tasks.value[taskIndex].comments = currentComments
        }

        
        // await tasksStores.updateComment({ 
        //     task_id: taskId, 
        //     comment_id: commentData.id, 
        //     comment: commentData.comment
        // })
        
        isRequestOngoing.value = false
        
        // await refreshTasks()
        
        await nextTick()
        
        // Actualizar selectedTask con los datos frescos
        const updatedTask = tasks.value.find(item => item.id === taskId)
        if (updatedTask) {
            selectedTask.value = {
                ...updatedTask,
                cost: formatDecimal(updatedTask.cost)
            }
        }

        advisor.value = {
            type: 'success',
            message: 'Kommentar uppdaterad!',
            show: true
        }

        setTimeout(() => {
            advisor.value = {
                type: '',
                message: '',
                show: false
            }
        }, 3000)

        return true
    }
}

const deleteComment = async (commentData) => {
    
    isRequestOngoing.value = true
    
    const taskId = selectedTask.value.id

    const commentsTask = tasks.value.find(item => item.id === taskId)
    const currentComments = commentsTask && commentsTask.comments ? commentsTask.comments : []
    const commentIndex = currentComments.findIndex(item => item.id === commentData.id)

    if (commentIndex !== -1) {
        currentComments.splice(commentIndex, 1)
    }

    const taskIndex = tasks.value.findIndex(item => item.id === taskId)

    if (taskIndex !== -1) {
        tasks.value[taskIndex].comments = currentComments
    }
    
    // await tasksStores.deleteComment({ 
    //     task_id: taskId, 
    //     comment_id: commentData.id
    // })
    
    isRequestOngoing.value = false
    
    // await refreshTasks()
    
    await nextTick()
    
    // Actualizar selectedTask con los datos frescos
    const updatedTask = tasks.value.find(item => item.id === taskId)
    if (updatedTask) {
        selectedTask.value = {
            ...updatedTask,
            cost: formatDecimal(updatedTask.cost)
        }
    }

    advisor.value = {
        type: 'success',
        message: 'Kommentar borttagen!',
        show: true
    }

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)

    return true

}

const formatDecimal = (value) => {
    const number = parseFloat(value);

    if (number % 1 !== 0) {
        return number.toFixed(2);
    }

    return number.toString();
}

// Función para refrescar solo los tasks sin recargar toda la data
const refreshTasks = async () => {
    const data = await vehiclesStores.showVehicle(Number(route.params.id))
    if (data && data.vehicle && data.vehicle.tasks) {
        tasks.value = data.vehicle.tasks.map(task => ({
            ...task,
            cost: task.cost
        }))
    }
}

const showDocument = (isMobile = false) => {
    alertFile.value = null
    document_type_id.value = null
    reference.value = null
    filename.value = []
    
    if (isMobile) {
        isConfirmCreateDocumentMobileDialogVisible.value = true
    } else {
        isConfirmCreateDocumentDialogVisible.value = true
    }
}

const closeDocument = () => {
    isConfirmCreateDocumentDialogVisible.value = false
    isConfirmCreateDocumentMobileDialogVisible.value = false
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

            // formData.append('vehicle_id', vehicle_id.value)
            // formData.append('document_type_id', document_type_id.value)
            // formData.append('reference', reference.value)
            // formData.append('file', file)

            isRequestOngoing.value = true

            // var currentDocuments = documents.value || [];
            var type = document_types.value.find(item => item.id === document_type_id.value)

            documents.value.push({
                id: Date.now(), // Generar un ID temporal para el nuevo document
                vehicle_id: null,
                document_type_id: document_type_id.value,
                type: type,
                reference: reference.value,
                fileObj: file,
                file: 'vehicles/' + userData.value.name.toLowerCase() + '-' + userData.value.last_name.toLowerCase() + '/' + file.name, //Para poder mostrar el nombre del archivo en el listado.
                user: userData.value,
                user_id: userData.value.id,
            })

            // currentTasks.push({
                    
            //         measure: measure.value,
            //         cost: cost.value,
            //         description: description.value,
            //         start_date: start_date.value,
            //         end_date: end_date.value,
            //         is_cost: is_cost.value,
            //         comments: []
            //     })

            // documents.value = currentDocuments

            // documentsStores.addDocument(formData)
            //     .then(async(res) => {
            //         if (res.data.success) {
            //             advisor.value = {
            //                 type: 'success',
            //                 message: 'Dokument skapad!',
            //                 show: true
            //             }

            //             closeDocument()
            //             await fetchData()
            //         } else {
            //             alertFile.value = res.data.message
            //             setTimeout(() => {
            //                 alertFile.value = null
            //             }, 5000)
            //         }
                     
            //         isRequestOngoing.value = false
            //     })
            //     .catch((err) => {
                    
            //         advisor.value = {
            //             type: 'error',
            //             message: err.message,
            //             show: true
            //         }

            //         isRequestOngoing.value = false
            //     })

            advisor.value = {
                type: 'success',
                message: 'Dokument skapad!',
                show: true
            }

            closeDocument()
            // await fetchData()

            await nextTick();
            initialVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));
            savedVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));

            isRequestOngoing.value = false
            

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

const downloadDocument = (doc) => {
    if (doc.fileObj) {
        const url = URL.createObjectURL(doc.fileObj);
        const a = document.createElement('a');
        a.href = url;
        a.download = doc.fileObj.name;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } else {
        download(doc);
    }
}

const removeDocument = async (document) => {

    // let res = await documentsStores.deleteDocument(document.id)

    const documentId = document.id
    const documentIndex = documents.value.findIndex(item => item.id === documentId)

    if (documentIndex !== -1) {
        documents.value.splice(documentIndex, 1)
    }

    

    advisor.value = {
        type: 'success',
        message: 'Dokument borttagen!',
        show: true
    }

    // await fetchData()

    await nextTick();
    initialVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));
    savedVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));

    setTimeout(() => {
        advisor.value = {
        type: '',
        message: '',
        show: false
        }
    }, 3000)

    return true
}

const formatOrgNumber = () => {

    let numbers = organization_number.value.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    organization_number.value = numbers
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

const selectCl = client => {
    if (client) {
        let _client = clients.value.find(item => item.id === client)
    
        mail.value = _client.email
    } else {
        mail.value = ''
        refSend.value?.resetValidation()
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

const goToVehicles = () => {

    let data = {
        message: 'Aktie uppdaterad framgångsrikt.!',
        error: false
    }

    router.push({ name : 'dashboard-admin-stock'})
    emitter.emit('toast', data)                 

};

const createVehicles = () => {
  router.push({
    name: "dashboard-admin-stock",
    query: { action: "create" }
  });
};

const confirmLeave = () => {
  isConfirmLeaveVisible.value = false;
  allowNavigation.value = true;
  
  if (nextRoute.value) {
    router.push(nextRoute.value);
  }
};

const onSubmit = async () => {
    // Validación manual ANTES de usar VForm.validate()
    // Verificar tab-1 (Fordon)
    const hasTab1Errors = !reg_num.value || 
                          !mileage.value || 
                          !brand_id.value || 
                          (model_id.value !== 0 && !model_id.value) || // si no es 0 y está vacío → error
                          (model_id.value === 0 && !model.value) || // si es 0, el campo texto debe tener valor
                          !car_body_id.value || 
                          !year.value || 
                          yearValidator(year.value) !== true ||
                          !chassis.value ||
                          !car_name.value ||
                          number_keys.value === null || 
                          number_keys.value === undefined || 
                          number_keys.value === ''

    // Verificar tab-2 (Prisinformation)
    const hasTab2Errors = purchase_price.value === null || 
                          purchase_price.value === undefined || 
                          purchase_price.value === '' ||
                          !currency_id.value || 
                          !iva_purchase_id.value

    // Verificar tab-3 (Kund)
    const hasTab3Errors = !client_type_id.value || 
                          !organization_number.value || 
                          (organization_number.value && minLengthDigitsValidator(10)(organization_number.value) !== true) ||
                          !fullname.value || 
                          !street.value || 
                          !address.value || 
                          !identification_id.value || 
                          !postal_code.value ||
                          (email.value && emailValidator(email.value) !== true) ||
                          (phone.value && phoneValidator(phone.value) !== true)

    // Si hay errores, ir al primer tab con error
    if (hasTab1Errors) {
        currentTab.value = 'tab-1'
        
        // Esperar a que el tab se monte y luego validar
        await nextTick()
        refForm.value?.validate()
        
        advisor.value = {
            type: 'warning',
            message: 'Vänligen fyll i alla obligatoriska fält i fliken Fordon',
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
        currentTab.value = 'tab-2'
        
        await nextTick()
        refForm.value?.validate()
        
        advisor.value = {
            type: 'warning',
            message: 'Vänligen fyll i alla obligatoriska fält i fliken Prisinformation',
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
        currentTab.value = 'tab-3'
        
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

    // Si no hay errores manuales, proceder con el submit
    refForm.value?.validate().then(({ valid }) => {
        if (valid) {
            let formData = new FormData()

            state_id.value = state_idOld.value

            // if(state_id.value === 12) {
            //     router.push({ name : 'dashboard-admin-sold-id'})
            //     return false;
            // }

            // formData.append('id', Number(route.params.id))
            formData.append('_method', 'POST')
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
            formData.append('last_service_date', last_service_date.value)
            formData.append('dist_belt', dist_belt.value)
            formData.append('last_dist_belt', last_dist_belt.value)
            formData.append('last_dist_belt_date', last_dist_belt_date.value)
            formData.append('comments', comments.value)
            formData.append('chassis', chassis.value)
            formData.append('car_name', car_name.value)
            formData.append('engine', engine.value)

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

            formData.append('tasks', JSON.stringify(tasks.value))
            // formData.append('documents', JSON.stringify(documents.value))
            
            documents.value.forEach((doc, index) => {
                if (doc.fileObj) {
                    formData.append(`documents[${index}][file]`, doc.fileObj)
                }
                formData.append(`documents[${index}][document_type_id]`, doc.document_type_id ?? '')
                formData.append(`documents[${index}][reference]`, doc.reference ?? '')
                if (doc.id) {
                    formData.append(`documents[${index}][id]`, doc.id)
                }
            })

            isConfirmStatusDialogVisible.value = false
            isRequestOngoing.value = true

            // let data = {
            //     data: formData
            // }

            vehiclesStores.addVehicle(formData)
                .then((res) => {
                    if (res.data.success) {
                        allowNavigation.value = true;
                        
                        // Save current state as the saved state
                        savedVehicleData.value = JSON.parse(JSON.stringify(currentVehicleData.value));
                        
                        // Limpiar datos temporales de sessionStorage
                        sessionStorage.removeItem('temp_reg_num')
                        sessionStorage.removeItem('temp_common_info')
                        
                        skapatsDialog.value = true;
                    }
                    isRequestOngoing.value = false
                })
                .catch((error) => {
                    err.value = error;
                    inteSkapatsDialog.value = true;
                    isRequestOngoing.value = false;
                })
        }
    })
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
  
  // Check for URL hash to activate specific tab
  const hash = window.location.hash;
  if (hash === '#tab-tasks') {
    currentTab.value = 'tab-5';
  }
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
  
  // Limpiar sessionStorage si se sale de la página sin crear el vehículo
  if (!vehicle_id.value) {
    sessionStorage.removeItem('temp_reg_num')
    sessionStorage.removeItem('temp_common_info')
  }
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
                    <div 
                        class="d-flex flex-wrap gap-y-4 gap-x-6 mb-4"
                        :class="windowWidth < 1024 ? 'justify-center text-center' : 'justify-start justify-sm-space-between'">
                        <div 
                            class="d-flex align-center gap-4"
                            :class="windowWidth < 1024 ? 'flex-column' : ''"
                            >
                            <VAvatar
                                v-if="logo"
                                variant="tonal"
                                style="width: 88px; height: 88px; border-radius: 16px;"
                                :image="themeConfig.settings.urlStorage + logo"
                                /> 
                            <div 
                                v-else
                                class="header-image-placeholder d-flex align-center justify-center" 
                                style="width: 88px; height: 88px; background-color: #D9D9D9; border-radius: 16px;">
                                <!-- Placeholder for car image -->
                            </div>
                            <div class="d-flex flex-column justify-center gap-4">
                                <span class="title-page">
                                    {{ reg_num }}
                                </span>
                                <span 
                                    class="d-flex subtitle-page"
                                    :class="windowWidth < 1024 ? 'justify-center' : 'justify-start'">
                                    {{ state }}
                                    <VIcon v-if=false icon="custom-pencil" size="24" class="ms-2 cursor-pointer" @click="isConfirmStatusDialogVisible = true"/>
                                </span>
                            </div>
                        </div>
                        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
                        <div 
                            class="d-flex gap-4"
                            :class="windowWidth < 1024 ? 'w-100' : 'align-center'">
                            <VBtn
                                class="btn-light w-auto" 
                                block
                                :to="{ name: state_id === 12 ? 'dashboard-admin-sold' :'dashboard-admin-stock' }"
                            >
                                <VIcon icon="custom-return" size="24" />
                                Tillbaka
                            </VBtn>

                            <VBtn 
                                class="btn-gradient"
                                block
                                type="submit" 
                            >
                                <VIcon icon="custom-save"  size="24" />
                                Spara
                            </VBtn>
                        </div>
                    </div>
        
                    <VDivider :class="windowWidth < 1024 ? 'mb-4' : 'mb-8'" />
                    
                    <VTabs 
                        v-model="currentTab" 
                        grow
                        :show-arrows="false"
                        class="vehicles-tabs"
                    >
                        <VTab value="tab-1">
                            <VIcon size="24" icon="custom-autofordon" />
                            <span>Fordon</span>
                        </VTab>
                        <VTab value="tab-2">
                            <VIcon size="24" icon="custom-pris-information" />
                            <span>Prisinformation</span>
                        </VTab>
                        <VTab value="tab-3">
                            <VIcon size="24" icon="custom-clients" />
                            <span>Kund</span>
                        </VTab>
                        <VTab value="tab-5">
                            <VIcon size="24" icon="custom-atgarder-2" />
                            <span>Åtgärder / Kostnader</span>
                        </VTab>
                        <VTab value="tab-6">
                            <VIcon size="24" icon="custom-dokument-ilager" />
                            <span>Dokument</span>
                        </VTab>
                    </VTabs>
                    <VCardText class="px-0">
                        <VWindow v-model="currentTab">
                            <!-- Fordon -->
                            <VWindowItem value="tab-1" class="px-md-0">
                                <h6 class="mb-7 title-tab">
                                    Grund och teknisk information
                                </h6>
                                 <div 
                                    class="d-flex flex-wrap card-form"
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
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Miltal*" />
                                        <VTextField
                                            type="number"
                                            v-model="mileage"
                                            suffix="Mil"
                                            min="0"
                                            :rules="[requiredValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'" class="form">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Märke*" />
                                        <AppAutocomplete
                                            v-model="brand_id"
                                            :items="brands"
                                            :item-title="item => item.name"
                                            :item-value="item => item.id"
                                            autocomplete="off"
                                            clearable
                                            clear-icon="tabler-x"
                                            @update:modelValue="selectBrand"
                                            @click:clear="onClearBrand"
                                            :rules="[requiredValidator]"
                                            :menu-props="{ maxHeight: '300px' }"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : model_id !== 0 ? 'width: calc(50% - 12px);' : 'width: calc(25% - 18px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modell*" />
                                        <AppAutocomplete
                                            v-model="model_id"
                                            :items="getModels"
                                            autocomplete="off"
                                            clearable
                                            clear-icon="tabler-x"
                                            @update:modelValue="selectModel"
                                            :rules="[requiredValidator]"
                                            :menu-props="{ maxHeight: '300px' }"
                                        />
                                    </div>
                                    <div v-if="model_id === 0" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(25% - 18px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modellens namn*" />
                                        <VTextField
                                            v-model="model"
                                            :rules="[requiredValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bilnamn*" />
                                        <VTextField
                                            v-model="car_name"
                                            :rules="[requiredValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Motor" />
                                        <VTextField
                                            v-model="engine"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Generation" />
                                        <VTextField
                                            v-model="generation"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kaross*" />
                                        <AppAutocomplete
                                            v-model="car_body_id"
                                            :items="carbodies"
                                            :item-title="item => item.name"
                                            :item-value="item => item.id"
                                            autocomplete="off"
                                            clearable
                                            clear-icon="tabler-x"
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
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Inköpsdatum" />
                                        <AppDateTimePicker
                                            :key="JSON.stringify(startDateTimePickerConfig)"
                                            v-model="purchase_date"
                                            density="default"
                                            :config="startDateTimePickerConfig"
                                            clearable
                                            class="field-solo-flat"
                                            placeholder="Välj datum"
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
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kontrollbesiktning gäller tom" />
                                        <AppDateTimePicker
                                            :key="JSON.stringify(endDateTimePickerConfig)"
                                            v-model="control_inspection"
                                            density="default"
                                            :config="endDateTimePickerConfig"
                                            clearable
                                            class="field-solo-flat"
                                            placeholder="Välj datum"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Färg" />
                                        <VTextField
                                            v-model="color"
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
                                            clear-icon="tabler-x"
                                        />
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
                                          <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Antal nycklar*" />
                                            <VTextField
                                                v-model="number_keys"
                                                type="number"
                                                min="1"
                                                :rules="[requiredValidator]"
                                            />
                                    </div>  
                                    <div class="d-flex flex-column gap-4" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <div class="d-flex gap-2" :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'">
                                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 16px);'">
                                                <div class="d-flex flex-column">
                                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Servicebok finns?*" />
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
                                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 16px);'">                                                
                                                <div class="d-flex flex-column">
                                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Sommardäck finns?*" />
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
                                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 16px);'">                                                
                                                <div class="d-flex flex-column">
                                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vinterdäck finns?*" />
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
                                         </div>
                                         <div class="d-flex flex-column">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kamrem bytt?" />
                                            <VRadioGroup v-model="dist_belt" inline class="radio-form">
                                                <VRadio
                                                    v-for="(radio, index) in optionsRadio"
                                                    :key="index"
                                                    :label="radio"
                                                    :value="index"
                                                />
                                            </VRadioGroup>
                                        </div>
                                    </div>                                    
                                    <div class="d-flex flex-column gap-4" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">                                       
                                        <div class="d-flex gap-2">
                                            <div class="w-50">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Senaste service: Mil/datum" />
                                                <VTextField
                                                    type="number"
                                                    v-model="last_service"
                                                    suffix="Mil"
                                                    min="0"
                                                />
                                            </div>
                                            <div class="w-50">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="" />
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                                    v-model="last_service_date"
                                                    density="default"
                                                    :config="endDateTimePickerConfig"
                                                    clearable
                                                    class="field-solo-flat"
                                                    placeholder="YYYY-MM-DD"
                                                    style="margin-top: 5.5px"
                                                />
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2" v-if="dist_belt === 0">
                                            <div class="w-50">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kamrem bytt vid: Mil/datum" />
                                                <VTextField
                                                    type="number"
                                                    v-model="last_dist_belt"
                                                    suffix="Mil"
                                                    min="0"
                                                />
                                            </div>
                                            <div class="w-50">
                                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="" />
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                                    v-model="last_dist_belt_date"
                                                    density="default"
                                                    :config="endDateTimePickerConfig"
                                                    clearable
                                                    class="field-solo-flat"
                                                    placeholder="YYYY-MM-DD"
                                                    style="margin-top: 5.5px"
                                                />
                                            </div>
                                        </div>                                        
                                    </div>                                    
                                    <div class="w-100">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Anteckningar" />
                                        <VTextarea
                                            v-model="comments"
                                            rows="4"
                                        />
                                    </div>
                                </div>
                            </VWindowItem>
                            <!-- Prisinformation -->
                            <VWindowItem value="tab-2" class="px-md-0">
                                <div 
                                    class="d-flex flex-wrap card-form"
                                    :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                >
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Inköpspris*" />
                                        <VTextField
                                            type="number"
                                            v-model="purchase_price"
                                            min="0"
                                            suffix="KR"
                                            :rules="[requiredValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <AppAutocomplete
                                            v-model="currency_id"
                                            label="Valuta*"
                                            :items="currencies"
                                            :item-title="item => item.name"
                                            :item-value="item => item.id"
                                            autocomplete="off"
                                            disabled
                                            clear-icon="tabler-x"
                                            :rules="[requiredValidator]">
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
                                            v-model="iva_purchase_id"
                                            label="VMB / Moms*"
                                            :items="ivas"
                                            :item-title="item => item.name"
                                            :item-value="item => item.id"
                                            autocomplete="off"
                                            clearable
                                            clear-icon="tabler-x"
                                            :rules="[requiredValidator]"/>
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <AppAutocomplete
                                            v-model="state_idOld"
                                            label="Status"
                                            :items="states"
                                            :item-title="item => item.name"
                                            :item-value="item => item.id"
                                            @update:modelValue="chageStatus(state_idOld)"
                                            />
                                    </div>
                                </div>
                            </VWindowItem>
                            <!-- Kund -->
                            <VWindowItem value="tab-3" class="px-md-0">
                                <h6 class="mb-7 title-tab">
                                    Säljare
                                </h6>
                                <div>
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
                                                label="Säljaren är*"
                                                :items="client_types"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                :rules="[requiredValidator]"/>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Namn*" />
                                            <VTextField
                                                v-model="fullname"
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
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />                                            
                                            <VTextField
                                                v-model="address"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <AppAutocomplete
                                                v-model="identification_id"
                                                label="Legitimation*"
                                                :items="identifications"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                autocomplete="off"
                                                :rules="[requiredValidator]"/>
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Postnummer*" />                                            
                                            <VTextField
                                                v-model="postal_code"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon" />                                            
                                            <VTextField
                                                v-model="phone"
                                                :rules="[phoneValidator]"
                                            />
                                        </div>                                        
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post" />                                            
                                            <VTextField
                                                v-model="email"
                                                :rules="[emailValidator]"
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
                                </div>
                                <VDivider :class="windowWidth < 1024 ? 'my-4' : 'my-8'" />
                                <div>
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
                                </div>                            
                            </VWindowItem>
                            <!-- Planerade åtgärder -->
                            <VWindowItem value="tab-5" class="px-md-0">
                                <div class="d-flex gap-4 align-center flex-wrap pb-4 w-100">           
                                    <h6 class="title-tab">
                                        Övrigt
                                    </h6>
                                    <VSpacer />                                   
                                    <VBtn
                                        v-if="$can('edit', 'stock') && windowWidth >= 1024"
                                        class="btn-gradient"
                                        @click="isConfirmTaskDialogVisible = true">
                                        <VIcon icon="custom-plus" size="24" />
                                        Lägg till åtgärder/kostnader
                                    </VBtn>
                                    <VBtn
                                        v-if="$can('edit', 'stock') && windowWidth < 1024"
                                        class="btn-gradient"
                                        @click="isConfirmTaskMobileDialogVisible = true">
                                        <VIcon icon="custom-plus" size="24" />
                                        Lägg till åtgärder/kostnader
                                    </VBtn>
                                </div>
                                <div v-if="tasks.length === 0" 
                                    class="mt-10 text-center empty-state"
                                    :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
                                    >
                                    <VIcon
                                        :size="$vuetify.display.mdAndDown ? 80 : 120"
                                        icon="custom-f-list"
                                    />
                                    <div class="empty-state-content">
                                        <div class="empty-state-title">Inga åtgärder registrerade än</div>
                                        <div class="empty-state-text">
                                            Lägg till reparationer, service eller övriga kostnader för att få en tydlig överblick över arbetet som krävs på detta fordon.
                                        </div>
                                    </div>
                                    <VBtn
                                        v-if="$can('edit', 'stock') && windowWidth >= 1024"
                                        class="btn-ghost"
                                        @click="isConfirmTaskDialogVisible = true"
                                        >
                                        Lägg till åtgärder/kostnader
                                        <VIcon icon="custom-arrow-right" size="24" />
                                    </VBtn>
                                    <VBtn
                                        v-if="$can('edit', 'stock') && windowWidth < 1024"
                                        class="btn-ghost"
                                        @click="isConfirmTaskMobileDialogVisible = true"
                                        >
                                        Lägg till åtgärder/kostnader
                                        <VIcon icon="custom-arrow-right" size="24" />
                                    </VBtn>
                                </div>

                                <div 
                                    v-else
                                    class="d-flex flex-wrap gap-4"
                                >
                                    <VCard
                                        v-for="(task, index) in tasks"
                                        :key="task.id"
                                        flat
                                        :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33.333% - 11px);'"
                                        class="border-card-comment py-2 px-4 readonly-form d-flex flex-column"
                                    >
                                        <VCardText 
                                            class="d-flex align-center px-0 border-comments gap-2" 
                                            style="min-height: 48px; max-height: 48px;"
                                            > 
                                            <span class="title-comments" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1;">{{ task.measure }}</span>
                                            <VSpacer />
                                            <VIcon 
                                                v-if="!task.is_cost"
                                                icon="custom-finance" 
                                                size="24" 
                                                class="cursor-pointer"
                                                @click="showStatusModal(task)"
                                            />
                                            <VIcon 
                                                icon="custom-waste" 
                                                size="24" 
                                                class="cursor-pointer"
                                                style="flex-shrink: 0;"
                                                @click="removeTask(task)"
                                            />
                                        </VCardText>

                                        <div class="d-block gap-4 px-0 mt-auto">
                                            <div class="text-comments mt-4" v-if="task.description">
                                                {{ task.description }}
                                            </div>
            
                                            <div class="d-flex gap-4 my-4" v-if="!task.is_cost">
                                                <span class="note-value-field w-100">
                                                    {{ formatDateDisplay(task.start_date) }}
                                                </span>
                                                <span 
                                                    class="note-value-field w-100"
                                                    :class="{ 'date-overdue': isDateOverdue(task.end_date) }"
                                                >
                                                    {{ formatDateDisplay(task.end_date) }}
                                                </span>
                                            </div>

                                            <div class="note-value-field my-4">
                                                {{ formatNumber(task.cost ?? 0) }} (kr)
                                            </div>                                                         

                                            <div class="d-flex align-center px-0">
                                                <div class="text-no-wrap">
                                                    <VAvatar
                                                        color="#E3DEEB"
                                                        :variant="userData.avatar ? 'outlined' : 'tonal'"
                                                        size="40"
                                                    >
                                                        <VImg
                                                            v-if="userData.avatar"
                                                            style="border-radius: 50%;"
                                                            :src="userData.avatar"
                                                        />
                                                        <span v-else>{{ avatarText(userData.name) }}</span>
                                                    </VAvatar>
                                                    <span class="ms-2 text-comments text-neutral-3">{{ userData.name }} {{ userData.last_name }}</span>
                                                </div>

                                                <VSpacer />

                                                <div class="d-flex align-center">
                                                    <VIcon 
                                                        icon="custom-pencil" 
                                                        size="24" 
                                                        class="cursor-pointer me-2"
                                                        @click="showTask(task, windowWidth < 1024 ? true : false, true)"
                                                    />
                                                    <VIcon 
                                                        icon="custom-comments" 
                                                        size="24" 
                                                        class="cursor-pointer"
                                                        @click="showTask(task, windowWidth < 1024 ? true : false, false)"
                                                    />

                                                    <span class="ms-2 text-comments text-neutral-3">{{ task.comments.length }}</span>
                                                </div>
                                            </div>
                                        </div>    
                                    </VCard>
                                </div>
                            </VWindowItem>
                            <!-- Dokument -->
                            <VWindowItem value="tab-6" class="px-md-0">
                                <div 
                                    class="d-flex gap-4 align-center flex-wrap pb-4 w-100 w-md-auto" 
                                    :class="windowWidth < 1024 === 0 ? 'flex-column' : ''">           
                                    <h6 class="title-tab">
                                        Dokument
                                    </h6>
                                    <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
                                    <div 
                                        class="d-flex gap-2"
                                        :class="windowWidth < 1024 ? 'w-100' : 'align-center'">
                                        <VBtn
                                            v-if="selectedIds.length > 0"
                                            class="btn-light w-auto"
                                            block
                                            @click="isConfirmSendDocumentDialogVisible = true">
                                            Sänd PDF
                                        </VBtn>  
                                        <VBtn
                                            v-if="$can('edit', 'stock') && windowWidth >= 1024"
                                            class="btn-gradient"
                                            block
                                            @click="showDocument(false)">
                                            <VIcon icon="custom-plus" size="24" />
                                            Ladda upp dokument
                                        </VBtn>
                                        <VBtn
                                            v-if="$can('edit', 'stock') && windowWidth < 1024"
                                            class="btn-gradient"
                                            block
                                            @click="showDocument(true)">
                                            <VIcon icon="custom-plus" size="24" />
                                            Ladda upp dokument
                                        </VBtn>
                                    </div>
                                </div>
                                <div v-if="documents.length === 0" 
                                    class="mt-10 text-center empty-state"
                                    :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
                                    >
                                    <VIcon
                                        :size="$vuetify.display.mdAndDown ? 80 : 120"
                                        icon="custom-f-document-car"
                                    />
                                    <div class="empty-state-content">
                                        <div class="empty-state-title">Inga dokument uppladdade än</div>
                                        <div class="empty-state-text">
                                            Ladda upp viktiga filer som registreringsbevis, besiktningsprotokoll och garantier för att hålla all dokumentation samlad för detta fordon.
                                        </div>
                                    </div>
                                    <VBtn
                                        v-if="$can('create', 'stock') && windowWidth >= 1024"
                                        class="btn-ghost"
                                        @click="showDocument(false)"
                                        >
                                        Ladda upp dokument
                                        <VIcon icon="custom-arrow-right" size="24" />
                                    </VBtn>
                                    <VBtn
                                        v-if="$can('create', 'stock') && windowWidth < 1024"
                                        class="btn-ghost"
                                        @click="showDocument(true)"
                                        >
                                        Ladda upp dokument
                                        <VIcon icon="custom-arrow-right" size="24" />
                                    </VBtn>
                                </div>
                                <template v-else>
                                    <VTable 
                                        v-if="!$vuetify.display.mdAndDown"
                                        class="pt-2 px-4 pb-6 text-no-wrap"
                                        style="border-radius: 0 !important"
                                    >
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
                                                <!-- 👉 Actions -->
                                                <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'stock') || $can('delete', 'stock')">      
                                                    <VMenu>
                                                        <template #activator="{ props }">
                                                            <VBtn v-bind="props" icon variant="text" color="default" size="x-small">
                                                                <VIcon icon="custom-dots-vertical" size="22" />
                                                            </VBtn>
                                                        </template>
                                                        <VList>
                                                            <VListItem v-if="$can('edit', 'stock')" @click="download(document)">
                                                                <template #prepend>
                                                                    <VIcon icon="custom-download" class="mr-2" size="24" />
                                                                </template>
                                                                <VListItemTitle>Ladda ner</VListItemTitle>
                                                            </VListItem>
                                                            <VListItem v-if="$can('delete','stock')" @click="removeDocument(document)">
                                                                <template #prepend>
                                                                    <VIcon icon="custom-waste" size="24" class="mr-2" />
                                                                </template>
                                                                <VListItemTitle>Ta bort</VListItemTitle>
                                                            </VListItem>
                                                        </VList>
                                                    </VMenu>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </VTable>

                                    <VExpansionPanels
                                        class="expansion-panels pb-6 px-0"
                                        v-if="$vuetify.display.mdAndDown"
                                    >
                                        <VExpansionPanel v-for="(document, index) in documents" :key="index">
                                        <VExpansionPanelTitle
                                            collapse-icon="custom-chevron-right"
                                            expand-icon="custom-chevron-down"
                                        >
                                            <span class="order-id" @click.stop>
                                                <VCheckbox
                                                    :value="document.id"
                                                    v-model="selectedIds"
                                                    density="compact"
                                                    hide-details
                                                />
                                                <VIcon icon="custom-pdf-file" class="ms-1" size="24" />
                                            </span>
                                            <div class="order-title-box">
                                            <span class="title-panel">{{  document.file.split('/').pop() }}</span>
                                            </div>
                                        </VExpansionPanelTitle>
                                        <VExpansionPanelText>
                                            <div class="mb-6">
                                            <div class="expansion-panel-item-label">Dokumenttyp</div>
                                            <div class="expansion-panel-item-value">
                                                {{ document.document_type_id === 4 ? document.reference : document.type.name }} 
                                            </div>
                                            </div>
                                            <div class="mb-6">
                                            <div class="expansion-panel-item-label">Datum</div>
                                            <div class="expansion-panel-item-value">
                                                {{ new Date(document.created_at).toLocaleString('sv-SE', { 
                                                    year: 'numeric', 
                                                    month: '2-digit', 
                                                    day: '2-digit', 
                                                    hour: '2-digit', 
                                                    minute: '2-digit',
                                                    hour12: false
                                                }) }}
                                            </div>
                                            </div>
                                            <div class="mb-6">
                                            <div class="expansion-panel-item-label">Skapad av</div>
                                            <div class="expansion-panel-item-value">
                                                {{ document.user.name }} {{ document.user.last_name }}
                                            </div>
                                            </div>
                                            <div class="mb-4 row-with-buttons">
                                            <VBtn
                                                v-if="$can('delete','stock')"
                                                class="btn-light"
                                                @click="removeDocument(document)"
                                            >
                                                <VIcon icon="custom-waste" size="24" />
                                                Ta bort
                                            </VBtn>
                                            <VBtn
                                                v-if="$can('edit', 'stock')"
                                                class="btn-light"
                                                @click="downloadDocument(document)">
                                                <VIcon icon="custom-download" size="24" />
                                                Ladda ner
                                            </VBtn>
                                            </div>
                                        </VExpansionPanelText>
                                        </VExpansionPanel>
                                    </VExpansionPanels>
                                </template>
                                
                            </VWindowItem>
                        </VWindow>
                    </VCardText>
                </VCardText>
            </VCard> 
        </VForm>

        <!-- 👉 Confirm update state task -->
        <VDialog
            v-model="isConfirmStatusTaskDialogVisible"
            persistent
            class="action-dialog" >
            
            <VBtn
                icon
                class="btn-white close-btn"
                @click="isConfirmStatusTaskDialogVisible = false"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>

            <!-- Dialog Content -->
            <VForm
                ref="refForm"
                @submit.prevent="updateTypeTask">
                <VCard flat class="card-form">
                    <VCardText class="dialog-title-box">
                        <VIcon size="32" icon="custom-finance" class="action-icon" />
                        <div class="dialog-title">
                            Flytta till kostnader
                        </div>
                    </VCardText>
                    <VCardText class="dialog-text">
                        Är du säker på att du vill ändra planen <strong>{{ selectedTask.measure }}</strong> till kostnad?
                    </VCardText>

                    <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
                        <VBtn
                            class="btn-light"
                            @click="isConfirmStatusTaskDialogVisible = false">
                            Avbryt
                        </VBtn>
                        <VBtn class="btn-gradient" type="submit">
                            Uppdatera
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>

        <!-- 👉 Confirm update state -->
        <VDialog
            v-model="isConfirmStatusDialogVisible"
            persistent
            class="action-dialog" >
            
            <VBtn
                icon
                class="btn-white close-btn"
                @click="isConfirmStatusDialogVisible = false"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>

            <!-- Dialog Content -->
            <VForm
                ref="refForm"
                @submit.prevent="onSubmit">
                <VCard flat class="card-form">
                    <VCardText class="dialog-title-box">
                        <VIcon size="32" icon="custom-pencil" class="action-icon" />
                        <div class="dialog-title">
                            Redigera status
                        </div>
                    </VCardText>
                    <VCardText class="pt-0">
                        <AppAutocomplete
                            v-model="state_idOld"
                            label="Status"
                            :items="states"
                            :item-title="item => item.name"
                            :item-value="item => item.id"
                            autocomplete="off"
                            :rules="[requiredValidator]"/>
                    </VCardText>

                    <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
                        <VBtn
                            class="btn-light"
                            @click="isConfirmStatusDialogVisible = false">
                            Avbryt
                        </VBtn>
                        <VBtn class="btn-gradient" type="submit">
                            Uppdatera status
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>

        <!-- 👉 Create task  -->
        <VNavigationDrawer
            temporary
            :width="550"
            location="end"
            class="scrollable-content right-drawer rounded-left-4"
            :model-value="isConfirmTaskDialogVisible"
            @update:model-value="(val) => !val && closeTask()"
        >
            <!-- 👉 Title -->
            <div class="d-flex align-center pa-6 pb-1">
                <h6 class="title-modal font-blauer">
                    Lägg till åtgärder/kostnader
                </h6>

            <VSpacer />

            <!-- 👉 Close btn -->
            <VBtn
                icon
                class="btn-white"
                @click="closeTask"
            >
                <VIcon size="32" icon="custom-cancel" />
            </VBtn>
            </div>

            <VDivider class="mt-4" />

            <PerfectScrollbar :options="{ wheelPropagation: false }" class="scrollbar-no-border">
                <VCard flat class="card-drawer-form">
                    <VCardText>
                        <!-- 👉 Form -->
                        <VForm
                            ref="refTask"
                            @submit.prevent="createTask">                            
                            <VRow>
                                <VCol cols="12" md="12" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vad ska goras?*" />
                                    <VTextField
                                        v-model="measure"
                                        :rules="[requiredValidator]"
                                    />
                                </VCol>
                                <VCol cols="12" md="12" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Beskrivning" />
                                    <VTextarea
                                        v-model="description"
                                        rows="4"
                                    />
                                </VCol>
                                <VCol cols="12" md="12" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Beräknad kostnad (kr)*" />
                                    <VTextField
                                        v-model="cost"
                                        type="number"
                                        min="0"
                                        suffix="KR"
                                        :rules="[requiredValidator]"
                                    />
                                </VCol>
                                <VCol cols="12" md="6" v-if="!is_cost" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Startdatum*" />
                                    <AppDateTimePicker
                                        :key="JSON.stringify(endDateTimePickerConfig)"
                                        v-model="start_date"
                                        density="compact"
                                        :config="endDateTimePickerConfig"
                                        :rules="[requiredValidator]"
                                        clearable
                                    />
                                </VCol>
                                <VCol cols="12" md="6" v-if="!is_cost" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Slutdatum" />
                                    <AppDateTimePicker
                                        :key="JSON.stringify(endDateTimePickerConfig)"
                                        v-model="end_date"
                                        density="compact"
                                        :config="endDateTimePickerConfig"
                                        clearable
                                    />
                                </VCol>
                                <VCol cols="12">
                                    <VCheckbox
                                        v-model="is_cost"
                                        color="primary"
                                        label="Är det en kostnad för fordonet?"
                                        class="ms-2 w-100 text-center d-flex justify-start"
                                    />
                                </VCol>
                                <VCol cols="12">
                                    <VBtn
                                        class="btn-light me-3"
                                        @click="closeTask">
                                        Avbryt
                                    </VBtn>
                                    <VBtn class="btn-gradient" type="submit">
                                        Lägg till
                                    </VBtn>
                                </VCol>
                            </VRow>
                        </VForm>
                    </VCardText>
                </VCard>
            </PerfectScrollbar>
        </VNavigationDrawer>
        
        <!-- 👉 Create task mobile -->
        <VDialog
            v-model="isConfirmTaskMobileDialogVisible"
            fullscreen
            persistent
            :scrim="false"
            transition="dialog-bottom-transition"
            class="action-dialog dialog-fullscreen">
            
            <VBtn
                icon
                class="btn-white close-btn"
                @click="closeTask"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>
            <VForm
                ref="refTask"
                class="h-100 d-flex flex-column"
                @submit.prevent="createTask">
                <VCard flat class="card-drawer-form h-100 d-flex flex-column">
                    <VCardText class="dialog-title-box mt-8 mb-2 pb-0 flex-0">
                        <div class="dialog-title">
                           Lägg till åtgärder/kostnader
                        </div>
                    </VCardText>
                    <VCardText class="py-4 flex-grow-1" style="overflow-y: auto; overflow-x: hidden;">
                        <VRow>
                            <VCol cols="12" md="12" class="pb-0">
                                <VLabel class="text-body-2 text-high-emphasis" text="Vad ska goras?*" />
                                <VTextField
                                    v-model="measure"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="12" class="pb-0">
                                <VLabel class="text-body-2 text-high-emphasis" text="Beskrivning" />
                                <VTextarea
                                    v-model="description"
                                    rows="3"
                                />
                            </VCol>
                            <VCol cols="12" md="12" class="pb-0">
                                <VLabel class="text-body-2 text-high-emphasis" text="Beräknad kostnad (kr)*" />
                                <VTextField
                                    v-model="cost"
                                    type="number"
                                    min="0"
                                    suffix="KR"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="6" v-if="!is_cost" class="pb-0">
                                <VLabel class="text-body-2 text-high-emphasis" text="Startdatum*" />
                                <AppDateTimePicker
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="start_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    :rules="[requiredValidator]"
                                    clearable
                                />
                            </VCol>
                            <VCol cols="12" md="6" v-if="!is_cost" class="pb-0">
                                <VLabel class="text-body-2 text-high-emphasis" text="Slutdatum" />
                                <AppDateTimePicker
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="end_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    clearable
                                />
                            </VCol>
                            <VCol cols="12">
                                <VCheckbox
                                    v-model="is_cost"
                                    color="primary"
                                    label="Är det en kostnad för fordonet?"
                                    class="ms-2 w-100 text-center d-flex justify-start"
                                />
                            </VCol>                            
                        </VRow>  
                        
                        <div class="d-flex justify-end gap-3 flex-wrap dialog-actions px-0 pt-2 pb-0">
                            <VBtn
                                class="btn-light"
                                @click="closeTask">
                                Avbryt
                            </VBtn>
                            <VBtn class="btn-gradient" type="submit">
                                Lägg till
                            </VBtn>
                        </div>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>
      
        <!-- 👉 Update task  -->
        <VNavigationDrawer
            temporary
            :width="550"
            location="end"
            class="scrollable-content right-drawer rounded-left-4"
            :model-value="isConfirmUpdateTaskDialogVisible"
            @update:model-value="(val) => !val && closeTask()"
        >
            <!-- 👉 Title -->
            <div class="d-flex align-center pa-6 pb-1">
                <h6 class="title-modal font-blauer">
                    {{ isEdit ? 'Uppdatera åtgärder/kostnader' : 'Kommentera åtgärder/kostnader' }}
                </h6>

                <VSpacer />

                <!-- 👉 Close btn -->
                <VBtn
                    icon
                    class="btn-white"
                    @click="closeTask"
                >
                    <VIcon size="32" icon="custom-cancel" />
                </VBtn>
            </div>

            <VDivider class="mt-4" />

            <PerfectScrollbar :options="{ wheelPropagation: false }" class="scrollbar-no-border">
                <VCard flat class="card-drawer-form">
                    <VCardText>
                        <!-- 👉 Form -->
                        <VForm
                            ref="refUpdate"
                            @submit.prevent="updateTask">                            
                            <VRow>
                                <VCol cols="12" md="12" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vad ska goras?*" />
                                    <VTextField
                                        v-model="selectedTask.measure"
                                        :rules="[requiredValidator]"
                                        :readonly="!isEdit"
                                    />
                                </VCol>
                                <VCol cols="12" md="12" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Beskrivning" />                                    
                                    <VTextarea
                                        v-model="selectedTask.description"
                                        rows="4"
                                        :readonly="!isEdit"
                                        persistent-placeholder
                                    />
                                </VCol>
                                <VCol cols="12" md="12" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Beräknad kostnad (kr)*" />                                    
                                    <VTextField
                                        v-model="selectedTask.cost"
                                        type="number"
                                        min="0"
                                        suffix="KR"
                                        :rules="[requiredValidator]"
                                        :readonly="!isEdit"
                                    />
                                </VCol>
                                <VCol cols="12" md="6" v-if="!selectedTask.is_cost">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Startdatum*" />                                    
                                    <AppDateTimePicker
                                        v-if="isEdit"
                                        :key="JSON.stringify(endDateTimePickerConfig)"
                                        v-model="selectedTask.start_date"
                                        density="compact"
                                        :config="endDateTimePickerConfig"
                                        :rules="[requiredValidator]"
                                        clearable
                                    />
                                    <VTextField
                                        v-else
                                        :model-value="selectedTask.start_date"
                                        readonly
                                    />
                                </VCol>
                                <VCol cols="12" md="6" v-if="!selectedTask.is_cost">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Slutdatum" />
                                    <AppDateTimePicker
                                        v-if="isEdit"
                                        :key="JSON.stringify(endDateTimePickerConfig)"
                                        v-model="selectedTask.end_date"
                                        density="compact"
                                        :config="endDateTimePickerConfig"
                                        clearable
                                    />
                                    <VTextField
                                        v-else
                                        :model-value="selectedTask.end_date"
                                        readonly
                                    />
                                </VCol>
                                <VCol cols="12" :class="isEdit ? '' : 'd-none'">
                                    <VBtn
                                        class="btn-light me-3"
                                        @click="closeTask">
                                        Avbryt
                                    </VBtn>
                                    <VBtn class="btn-gradient" type="submit">
                                        Uppdatering
                                    </VBtn>
                                </VCol>
                            </VRow>
                            <VDivider 
                                :class="[
                                    windowWidth < 1024 ? 'my-4' : 'my-6',
                                    isEdit ? 'd-none' : ''
                                ]" 
                            />

                            <div class="mb-6" :class="isEdit ? 'd-none' : 'd-flex gap-2'">
                                <VIcon size="24" icon="custom-comments-2" class="action-icon" />
                                <span class="span-comments">
                                   Kommentarer
                                </span>
                            </div>
         
                            <div :class="isEdit ? 'd-none' : 'd-flex flex-column gap-6'">
                                <VTextField
                                    v-model="comment"
                                    placeholder="Skriv en kommentar"
                                />
                                <VBtn class="btn-light w-auto align-self-start" @click="sendComment">
                                    Kommentar
                                </VBtn>
                            </div>

                            <VDivider 
                                v-if="selectedTask.comments?.length > 0" :class="[
                                windowWidth < 1024 ? 'my-4' : 'my-6',
                                    isEdit ? 'd-none' : ''
                                ]" 
                            />

                            <div 
                                v-for="(comment, index) in selectedTask.comments" 
                                :key="index"
                                class="mb-4"
                                :class="isEdit ? 'd-none' : 'd-flex flex-column gap-2 justify-center'"
                            > 
                                <div class="text-no-wrap w-100">
                                    <VAvatar
                                        color="#E3DEEB"
                                        :variant="comment.user.avatar ? 'outlined' : 'tonal'"
                                        size="40"
                                    >
                                        <VImg
                                            v-if="comment.user.avatar"
                                            style="border-radius: 50%;"
                                            :src="comment.user.avatar"
                                        />
                                        <span v-else>{{ avatarText(comment.user.name) }}</span>
                                    </VAvatar>
                                    <span class="ms-2 user-comments">
                                        {{ comment.user.name }} {{ comment.user.last_name }}

                                        <span class="date-comments">  
                                            {{ formatCommentDate(comment.created_at) }}
                                        </span>
                                    </span>
                                    
                                </div>
                                <VTextField
                                    v-model="comment.comment"
                                    placeholder="Kommentar.."
                                />
                                <div class="d-flex gap-4">
                                    <span class="link-comments cursor-pointer" @click="editComment(comment)">Redigera</span>
                                    <span class="link-comments cursor-pointer" @click="deleteComment(comment)">Eliminera</span>
                                </div>
                            </div>
                        </VForm>
                    </VCardText>
                </VCard>
            </PerfectScrollbar>
        </VNavigationDrawer>
        
        <!-- 👉 Update task mobile -->
        <VDialog
            v-model="isConfirmUpdateTaskMobileDialogVisible"
            fullscreen
            persistent
            :scrim="false"
            transition="dialog-bottom-transition"
            class="action-dialog dialog-fullscreen" >
            
            <VBtn
                icon
                class="btn-white close-btn"
                @click="closeTask"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>
            <VForm
                ref="refUpdate"
                class="h-100 d-flex flex-column"
                @submit.prevent="updateTask">
                <VCard flat class="card-drawer-form h-100 d-flex flex-column">
                    <VCardText class="dialog-title-box mt-8 mb-2 pb-0 flex-0">
                        <div class="dialog-title">
                           {{ isEdit ? 'Uppdatera åtgärder/kostnader' : 'Kommentera åtgärder/kostnader' }}
                        </div>
                    </VCardText>
                    <VCardText class="pt-5 pb-0 flex-grow-1" style="overflow-y: auto; overflow-x: hidden;">
                        <VRow>
                            <VCol cols="12" md="12" class="pb-0">
                                <VLabel class="text-body-2 text-high-emphasis" text="Vad ska goras?*" />
                                <VTextField
                                    v-model="selectedTask.measure"
                                    :rules="[requiredValidator]"
                                    :readonly="!isEdit"
                                />
                            </VCol>
                            <VCol cols="12" md="12" class="pb-0">
                                <VLabel class="text-body-2 text-high-emphasis" text="Beskrivning" />                                    
                                <VTextarea
                                    v-model="selectedTask.description"
                                    rows="3"
                                    :readonly="!isEdit"
                                    persistent-placeholder
                                />
                            </VCol>
                            <VCol cols="12" md="12" class="pb-0">
                                <VLabel class="text-body-2 text-high-emphasis" text="Beräknad kostnad (kr)*" />                                    
                                <VTextField
                                    v-model="selectedTask.cost"
                                    type="number"
                                    min="0"
                                    suffix="KR"
                                    :rules="[requiredValidator]"
                                    :readonly="!isEdit"
                                />
                            </VCol>
                            <VCol cols="12" md="12" class="pb-0" v-if="!selectedTask.is_cost">
                                <VLabel class="text-body-2 text-high-emphasis" text="Startdatum*" />                                    
                                <AppDateTimePicker
                                    v-if="isEdit"
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="selectedTask.start_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    :rules="[requiredValidator]"
                                    clearable
                                />
                                <VTextField
                                    v-else
                                    :model-value="selectedTask.start_date"
                                    readonly
                                />
                            </VCol>
                            <VCol cols="12" md="12" class="pb-0" v-if="!selectedTask.is_cost">
                                <VLabel class="text-body-2 text-high-emphasis" text="Slutdatum" />
                                <AppDateTimePicker
                                    v-if="isEdit"
                                    :key="JSON.stringify(endDateTimePickerConfig)"
                                    v-model="selectedTask.end_date"
                                    density="compact"
                                    :config="endDateTimePickerConfig"
                                    clearable
                                />
                                <VTextField
                                    v-else
                                    :model-value="selectedTask.end_date"
                                    readonly
                                />
                            </VCol>
                        </VRow>
                        
                        <div :class="isEdit ? 'd-flex justify-end gap-3 flex-wrap dialog-actions px-0 pb-0' : 'd-none'">
                            <VBtn
                                class="btn-light"
                                @click="closeTask">
                                Avbryt
                            </VBtn>
                            <VBtn class="btn-gradient" type="submit">
                                Uppdatering
                            </VBtn>
                        </div>

                        <VDivider 
                            :class="[
                                windowWidth < 1024 ? 'mt-6 mb-4' : 'my-6',
                                isEdit ? 'd-none' : ''
                            ]" 
                        />

                        <div class="mb-6" :class="isEdit ? 'd-none' : 'd-flex gap-2'">
                            <VIcon size="24" icon="custom-comments-2" class="action-icon" />
                            <span class="span-comments">
                                Kommentarer
                            </span>
                        </div>
        
                        <div :class="isEdit ? 'd-none' : 'd-flex flex-column gap-6'">
                            <VTextField
                                v-model="comment"
                                placeholder="Skriv en kommentar"
                            />
                            <VBtn class="btn-light w-auto align-self-start" @click="sendComment">
                                Kommentar
                            </VBtn>
                        </div>

                        <VDivider 
                            v-if="selectedTask.comments?.length > 0" 
                            :class="[
                                windowWidth < 1024 ? 'my-4' : 'my-6', 
                                isEdit ? 'd-none' : ''
                            ]"
                        />

                        <div 
                            v-for="(comment, index) in selectedTask.comments" 
                            :key="index"
                            class="mb-4"
                            :class="isEdit ? 'd-none' : 'd-flex flex-column gap-2 justify-center'"
                        >
                            <div class="text-no-wrap w-100">
                                <VAvatar
                                    color="#E3DEEB"
                                    :variant="comment.user.avatar ? 'outlined' : 'tonal'"
                                    size="40"
                                >
                                    <VImg
                                        v-if="comment.user.avatar"
                                        style="border-radius: 50%;"
                                        :src="themeConfig.settings.urlStorage + comment.user.avatar"
                                    />
                                    <span v-else>{{ avatarText(comment.user.name) }}</span>
                                </VAvatar>
                                <span class="ms-2 user-comments">
                                    {{ comment.user.name }} {{ comment.user.last_name }}

                                    <span class="date-comments">  
                                        {{ formatCommentDate(comment.created_at) }}
                                    </span>
                                </span>
                                
                            </div>
                            <VTextField
                                v-model="comment.comment"
                                placeholder="Kommentar.."
                            />
                            <div class="d-flex gap-4">
                                <span class="link-comments cursor-pointer" @click="editComment(comment)">Redigera</span>
                                <span class="link-comments cursor-pointer" @click="deleteComment(comment)">Eliminera</span>
                            </div>
                        </div>
                        
                        
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>

        <!-- 👉 Create document (Desktop) -->
        <VNavigationDrawer
            temporary
            :width="550"
            location="end"
            class="scrollable-content right-drawer rounded-left-4"
            :model-value="isConfirmCreateDocumentDialogVisible"
            @update:model-value="(val) => !val && closeDocument()"
        >
            <!-- 👉 Title -->
            <div class="d-flex align-center pa-6 pb-1">
                <h6 class="title-modal font-blauer">
                    Ladda upp dokument
                </h6>

                <VSpacer />

                <!-- 👉 Close btn -->
                <VBtn
                    icon
                    class="btn-white"
                    @click="closeDocument"
                >
                    <VIcon size="32" icon="custom-cancel" />
                </VBtn>
            </div>

            <VDivider class="mt-4" />

            <PerfectScrollbar :options="{ wheelPropagation: false }" class="scrollbar-no-border">
                <VCard flat class="card-drawer-form">
                    <VCardText>
                        <!-- 👉 Form -->
                        <VForm
                            ref="refDocument"
                            @submit.prevent="handleFileUpload">
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
                                <VCol cols="12" md="12" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Dokumenttyp*" />
                                    <AppAutocomplete
                                        v-model="document_type_id"
                                        :items="document_types"
                                        :item-title="item => item.name"
                                        :item-value="item => item.id"
                                        autocomplete="off"
                                        :rules="[requiredValidator]"/>
                                </VCol>
                                <VCol cols="12" md="12" class="pb-0"v-if="document_type_id === 4">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Övrigt" />
                                    <VTextField
                                        v-model="reference"
                                        :rules="document_type_id === 4 ? [requiredValidator] : []"
                                    />
                                </VCol>
                                <VCol cols="12" md="6" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Datum" />
                                    <VTextField
                                        :model-value="formattedDate"
                                        disabled
                                    />
                                </VCol>
                                <VCol cols="12" md="6" class="pb-0">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Ladda upp fil*" />
                                    <VFileInput       
                                        v-model="filename"
                                        prepend-icon=""
                                        append-inner-icon="custom-upload"
                                        :rules="[requiredValidator]"
                                    />
                                </VCol>
                                <VCol cols="12">
                                    <VBtn
                                        class="btn-light me-3"
                                        @click="closeDocument">
                                        Avbryt
                                    </VBtn>
                                    <VBtn class="btn-gradient" type="submit">
                                        Ladda upp
                                    </VBtn>
                                </VCol>
                            </VRow>
                        </VForm>
                    </VCardText>
                </VCard>
            </PerfectScrollbar>
        </VNavigationDrawer>

        <!-- 👉 Create document (Mobile) -->
        <VDialog
            v-model="isConfirmCreateDocumentMobileDialogVisible"
            transition="dialog-bottom-transition"
            scrollable
            content-class="dialog-bottom-full-width">
            <VCard>
                <VForm
                    ref="refDocument"
                    class="card-form"
                    @submit.prevent="handleFileUpload">

                    <VAlert
                        v-if="alertFile"
                        color="error"
                        icon="mdi-alert-octagon-outline"
                        variant="tonal"
                        class="mb-5"
                        >
                        {{alertFile}}
                    </VAlert>
                    <VList>
                        <VListItem>
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Dokumenttyp*" />
                            <AppAutocomplete
                                v-model="document_type_id"
                                :items="document_types"
                                :item-title="item => item.name"
                                :item-value="item => item.id"
                                autocomplete="off"
                                :rules="[requiredValidator]"/>
                        </VListItem>
                        <VListItem v-if="document_type_id === 4">
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Övrigt*" />
                            <VTextField
                                v-model="reference"
                                :rules="document_type_id === 4 ? [requiredValidator] : []"
                            />
                        </VListItem>
                        <VListItem>
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Datum" /> 
                            <VTextField
                                :model-value="formattedDate"
                                disabled
                            />
                        </VListItem>
                        <VListItem>
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Ladda upp fil*" />                                            
                            <VFileInput       
                                v-model="filename"           
                                prepend-icon=""
                                append-inner-icon="custom-upload"
                                :rules="[requiredValidator]"
                            />
                        </VListItem>
                    
                    </VList>
                    <div class="px-5 mb-5 d-flex flex-column gap-2 w-100">
                        <VBtn
                            type="reset"
                            block
                            class="btn-light"
                            @click="closeDocument"
                        >
                            Avbryt
                        </VBtn>
                        <VBtn
                            type="submit"
                            class="btn-gradient"
                            >
                            Ladda upp
                        </VBtn>
                    </div>
                </VForm>
            </VCard>
        </VDialog>

        <!-- 👉 Confirm send documents -->
        <VDialog
            v-model="isConfirmSendDocumentDialogVisible"
            persistent
            class="action-dialog">
            <!-- Dialog close btn -->
                
            <VBtn
                icon
                class="btn-white close-btn"
                @click="isConfirmSendDocumentDialogVisible = !isConfirmSendDocumentDialogVisible">
                <VIcon size="16" icon="custom-close" />
            </VBtn>

            <!-- Dialog Content -->
            <VForm
                ref="refSend"
                @submit.prevent="handleSendMail">
                <VCard>
                    <VCardText class="dialog-title-box">
                        <VIcon size="32" icon="custom-paper-plane" class="action-icon" />
                        <div class="dialog-title">
                            Skicka PDF via e-post
                        </div>
                    </VCardText>
                    <VCardText class="dialog-text pb-0">
                        <AppAutocomplete
                            prepend-icon="custom-profile"
                            :items="clients"
                            :item-title="item => item.fullname"
                            :item-value="item => item.id"
                            placeholder="Kunder"
                            autocomplete="off"
                            clearable
                            clear-icon="tabler-x"
                            class="selector-user selector-truncate w-auto"
                            @update:modelValue="selectCl"/>
                    </VCardText>
                    <VCardText class="dialog-text card-form">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
                        <VTextField
                            v-model="mail"                           
                            placeholder="Ange mottagarens e-postadress"
                            :rules="[requiredValidator, emailValidator]"
                        />                           
                    </VCardText>
                   <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
                        <VBtn
                            class="btn-light"
                            @click="isConfirmSendDocumentDialogVisible = false">
                            Avbryt
                        </VBtn>
                        <VBtn class="btn-gradient" type="submit">
                            Skicka
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>

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
                    name: 'dashboard-admin-stock'
                })"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>

            <VCard>
                <VCardText class="dialog-title-box big-icon justify-center pb-0">
                    <VIcon size="72" icon="custom-f-suv" />
                </VCardText>
                <VCardText class="dialog-title-box justify-center">
                    <div class="dialog-title">Fordonet har lagts till i lagret!</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    "Märke och modell" har registrerats och finns nu i din lagerlista.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="goToVehicles">
                        Gå till lagerlistan
                    </VBtn>
                    <VBtn class="btn-gradient" @click="createVehicles"> Lägg till ett till fordon </VBtn>
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
                    <div class="dialog-title">Kunde inte lägga till fordonet</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    Ett fel uppstod. Kontrollera att alla obligatoriska fält är korrekt ifyllda och försök igen.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="showError">
                        Stäng
                    </VBtn>
                </VCardText>
            </VCard>
        </VDialog>

        <VDialog 
            v-model="isConfirmLeaveVisible" 
            persistent 
            class="action-dialog">
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
        font-weight: 500;
        font-size: 16px;
        line-height: 100%;
        color: #6E9383;
    }

    .title-tab {
        font-weight: 700;
        font-size: 24px;
        line-height: 100%;
        color: #454545;

        @media (max-width: 1023px) {
            font-size: 16px
        }
    }

    .title-comments {
        font-weight: 700;
        font-size: 20px;
        line-height: 100%;
        color: #454545; 
    }

    .text-comments {
        font-weight: 400;
        font-size: 16px;
        line-height: 100%;
        color: #5D5D5D; 
    }

    .span-comments {
        font-weight: 400;
        font-size: 24px;
        line-height: 100%;
        color: #454545; 
    }

    .user-comments {
        font-weight: 400;
        font-size: 16px;
        line-height: 100%;
        color: #454545; 
    }

    .date-comments {
        font-weight: 400;
        font-size: 12px;
        line-height: 100%;
        color: #878787; 
    }

    .link-comments {
        font-weight: 500;
        font-size: 12px;
        line-height: 100%;
        color: #454545;
        text-decoration: underline;
    }

    .border-comments {
        border-bottom: 1px solid #E7E7E7;
    }

    .note-value-field {
        background-color: #F6F6F6;
        border-radius: 8px;
        border: 1px solid #E7E7E7;
        padding: 0 16px;
        height: 40px !important;
        align-items: center;
        display: flex;
        font-weight: 400;
        font-size: 16px;
        line-height: 24px;
        color: #878787;

        &.date-overdue {
            border-color: #FF4D4F;
            color: #9B191B;
        }
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

    @media (max-width: 776px) {
        .v-tabs.vehicles-tabs {
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

    .v-btn--disabled {
        opacity: 1 !important;
    }

    .radio-form {
        display: flex;
        align-items: center;
        height: 48px;

        :deep(.v-selection-control-group) {
            gap: 16px;
        }

        :deep(.v-selection-control) {
            min-height: auto;
        }

        :deep(.v-selection-control--dirty) {
            .v-selection-control__input > .v-icon {
                color: #00E1E2 !important;
            }
        }

        :deep(.v-label) {
            color: #5D5D5D;
            font-size: 12px;
            opacity: 1;
        }
    }

    .border-bottom-secondary {
        border-bottom: 1px solid #d9d9d9;
        padding-bottom: 10px;
    }

    .justify-content-end {
        justify-content: end !important;
    }

    /* New styles for the updated design */
    .header-image-placeholder {
        background-color: #E0E0E0;
        min-width: 80px;
        min-height: 80px;
    }

    /* Custom input styling */
    :deep(.v-field--variant-solo) {
        box-shadow: none !important;
        border: 1px solid transparent;
    }

    :deep(.v-field--variant-solo.v-field--focused) {
        border-color: #009688;
    }

    /* Target AppDateTimePicker if it uses v-field internally */
    :deep(.app-picker-field .v-field),
    :deep(.field-solo-flat .v-field) {
        background-color: #F5F5F5 !important;
        border-radius: 6px;
        box-shadow: none !important;
        border: 1px solid transparent;
    }
    
    :deep(.field-solo-flat .v-field--focused) {
        border-color: #009688 !important;
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
                        top: 12px !important;
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
</style>

<style lang="scss">

    .border-card-comment {
        border: 1px solid #E7E7E7;
        border-radius: 16px !important;
    }

    .stock-edit-page .radio-form.v-radio-group .v-selection-control-group .v-radio:not(:last-child) {
        margin-inline-end: 1.5rem !important;
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

<style>
    .right-drawer.v-navigation-drawer {
        border: none !important;
        border-color: transparent !important;
        border-width: 0 !important;
        border-style: none !important;
        box-shadow: none !important;
    }

    .right-drawer.v-navigation-drawer .v-navigation-drawer__content {
        border: none !important;
    }

    .dialog-fullscreen.v-overlay--active .v-overlay__content {
        width: 100% !important;
        height: 100% !important;
        max-width: 100% !important;
        max-height: 100% !important;
    }
</style>
<style lang="scss">
.card-form {
  .v-list {
    padding: 28px 24px 40px !important;

    .v-list-item {
      margin-bottom: 0px;
      padding: 4px 0 !important;
      gap: 0px !important;

      .v-input--density-compact {
        --v-input-control-height: 48px !important;
      }

      .v-select .v-field {
        .v-select__selection {
          align-items: center;
        }

        .v-field__input > input {
          top: 0px;
          left: 0px;
        }

        .v-field__append-inner {
          align-items: center;
          padding-top: 0px;
        }
      }

      .v-text-field {
        .v-input__control {
          padding-top: 0;
          input {
            min-height: 48px;
            padding: 12px 16px;
          }
        }
      }
    }
  }
  & .v-input {
    & .v-input__control {
      .v-field {
        background-color: #f6f6f6 !important;
        .v-field-label {
          @media (max-width: 991px) {
            top: 12px !important;
          }
        }
      }
    }
  }
}
</style>

<route lang="yaml">
    meta:
      action: edit
      subject: stock
</route>