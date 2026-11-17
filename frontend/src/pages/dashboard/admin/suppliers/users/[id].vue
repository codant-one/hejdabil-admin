<script setup>

import { useDisplay } from "vuetify";
import { onBeforeRouteLeave } from 'vue-router';
import { requiredValidator, emailValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { PHONE_INPUT_DEFAULTS, formatPhonePayload, normalizePhoneInput } from '@/@core/utils/phone'
import { useSuppliersStores } from '@/stores/useSuppliers'
import { usePermissionsStores } from '@/stores/usePermissions'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import MobileScrollTabs from "@/components/common/MobileScrollTabs.vue";
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

const route = useRoute();
const isConfirmLeaveVisible = ref(false)
const nextRoute = ref(null)
const initialData = ref(null)
const allowNavigation = ref(false)
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);
const isPasswordVisible = ref(false)
const isUserPermissionsDialog = ref(false)

const usersStores = useSuppliersStores()
const permissionsStores = usePermissionsStores()
const emitter = inject("emitter")
const err = ref(null);

const isRequestOngoing = ref(false)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref(0)
const isMobile = ref(false)

const selectedUser = ref({})
const id = ref('')
const email = ref('')
const name = ref('')
const password = ref('')
const last_name = ref('')
const phone = ref('')
const landline = ref('')
const address = ref('----')
const position = ref(null)
const assignedPermissions = ref([])
const permissionsRol = ref([])
const isPermissionsLoading = ref(false)
const permissionsLoaded = ref(false)
const permissionLoadError = ref('')
const permissionLabels = ref(new Map())

const modulePermissions = (subject, actions = ['view', 'create', 'edit', 'delete']) =>
    actions.map(action => ({
        value: `${action} ${subject}`,
        disabled: subject === 'roles' && action === 'delete',
    }))

const permissionSections = [
    {
        title: 'Konto & team',
        modules: [
            {
                title: 'Mitt team',
                description: 'Företagsprofil och team',
                permissions: [
                    ...modulePermissions('my-team'),
                    { value: 'view team-reports' },
                ],
            },
        ],
    },
    {
        title: 'Moduler',
        modules: [
            { title: 'Kunder', description: 'Kundregister och historik', permissions: modulePermissions('clients') },
            { title: 'Fakturor', description: 'Fakturor kopplade till affärer', permissions: modulePermissions('billings') },
            {
                title: 'Fordonslager',
                description: 'Fordon, märken och modeller',
                children: [
                    { title: 'Lager', permissions: modulePermissions('stock') },
                    { title: 'Sålda fordon', permissions: modulePermissions('sold', ['view', 'delete']) },
                ],
            },
            { title: 'Avtal', description: 'Köp- och försäljningsavtal', permissions: modulePermissions('agreements') },
            { title: 'Signera dokument', description: 'Dokument för digital signering', permissions: modulePermissions('signed-documents') },
            { title: 'Swish', description: 'Utbetalningar och betalningar', permissions: modulePermissions('payouts') },
            { title: 'Mina värderingar', description: 'Värderingar och anteckningar', permissions: modulePermissions('notes') },
        ],
    },
]

const getModuleRows = module => module.children ?? [module]

const getSectionPermissions = section =>
    section.modules.flatMap(module => getModuleRows(module).flatMap(row => row.permissions))

const selectablePermissions = section => getSectionPermissions(section).filter(permission => !permission.disabled)

const selectedCount = section =>
    selectablePermissions(section).filter(permission => assignedPermissions.value.includes(permission.value)).length

const isSectionSelected = section => selectedCount(section) === selectablePermissions(section).length
const isSectionIndeterminate = section => selectedCount(section) > 0 && !isSectionSelected(section)
const selectablePermissionValues = [...new Set(
    permissionSections.flatMap(selectablePermissions).map(permission => permission.value),
)]
const totalPermissions = selectablePermissionValues.length
const selectedPermissionsCount = computed(() =>
    selectablePermissionValues.filter(permission => assignedPermissions.value.includes(permission)).length,
)

const toggleSection = (section, checked) => {
    const values = selectablePermissions(section).map(permission => permission.value)

    assignedPermissions.value = checked
        ? [...new Set([...assignedPermissions.value, ...values])]
        : assignedPermissions.value.filter(permission => !values.includes(permission))
}

async function loadPermissions() {
    if (isPermissionsLoading.value)
        return

    isPermissionsLoading.value = true
    permissionLoadError.value = ''

    try {
        const { permissions } = await permissionsStores.allPermissions({ with_labels: true })
        const labels = new Map(permissions.map(permission => [permission.name, permission.label]))
        const hasMissingLabels = permissionSections.flatMap(getSectionPermissions)
            .some(permission => !labels.get(permission.value)?.trim())

        if (hasMissingLabels)
            throw new Error('En eller flera behörigheter saknar etikett. Kontrollera behörigheterna och försök igen.')

        permissionLabels.value = labels
        permissionsLoaded.value = true
    } catch (error) {
        permissionLoadError.value = error instanceof Error
            ? error.message
            : 'Det gick inte att hämta behörigheterna. Försök igen.'
    } finally {
        isPermissionsLoading.value = false
    }
}

const hasPhoneValue = value => !!String(value ?? '').trim()

const phoneOrLandlineRequiredValidator = value => {
    return hasPhoneValue(value) || hasPhoneValue(phone.value) || hasPhoneValue(landline.value) || 'krävs *'
}

const userPhonePrefix = `+${PHONE_INPUT_DEFAULTS.defaultPhoneCode}`
const userPhoneDigits = PHONE_INPUT_DEFAULTS.defaultPhoneDigits
const userPhoneRules = [phoneOrLandlineRequiredValidator, minLengthDigitsValidator(userPhoneDigits), phoneValidator]
const landlineRules = [phoneOrLandlineRequiredValidator, phoneValidator]

const normalizeUserPhoneForInput = value => normalizePhoneInput(value, [], null, PHONE_INPUT_DEFAULTS)
const normalizeLandlineForInput = value => String(value ?? '').replace(/\D/g, '')

const formatUserPhoneForPayload = value => formatPhonePayload(value, [], null, PHONE_INPUT_DEFAULTS)

const positions = ref ([
  { id: 1, name: "Admin" },
  { id: 2, name: "Inköpare" },
  { id: 3, name: "Säljare" },
  { id: 4, name: "Revisor" }
])

// Recargar la página al crear otro acuerdo
function reloadPage() {
  window.location.reload();
}

onMounted(async () => {
    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile)
    await loadPermissions()
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768
}

watchEffect(fetchData)

async function fetchData() {

    isRequestOngoing.value = true

      let data = {
        id: route.params.id,
        search: '',
        orderByField: 'order_id',
        orderBy: 'desc',
        limit: 10,
        page: 0
    }

    await usersStores.fetchUsers(data)

    const routeUserId = Number(route.params.id)
    const users = Array.isArray(usersStores.getUsers) ? usersStores.getUsers : []
    selectedUser.value = users.find(item => Number(item.id) === routeUserId)

    if (!selectedUser.value?.user) {
        isRequestOngoing.value = false
        return
    }

    id.value = selectedUser.value.user.id
    email.value = selectedUser.value.user.email
    name.value = selectedUser.value.user.name
    password.value = selectedUser.value.user.password
    last_name.value = selectedUser.value.user.last_name
    phone.value = normalizeUserPhoneForInput(selectedUser.value.user.user_detail?.personal_phone)
    landline.value = normalizeLandlineForInput(selectedUser.value.user.user_detail?.personal_landline)
    address.value = selectedUser.value.user.user_detail?.personal_address
    position.value = selectedUser.value.position
    permissionsRol.value = []

    selectedUser.value.user.permissions.forEach(function(pe) {
        permissionsRol.value.push(pe.name)
    })

    assignedPermissions.value = permissionsRol.value

    isRequestOngoing.value = false

    nextTick(() => {
      initialData.value = JSON.parse(JSON.stringify(currentData.value))
    })
}


const showError = () => {
    inteSkapatsDialog.value = false;

    advisor.value.show = true;
    advisor.value.type = "error";
        const responseData = err.value?.response?.data;
    
        if (responseData?.message) {
            advisor.value.message = responseData.message;
        } else if (responseData?.errors) {
            advisor.value.message = Object.values(responseData.errors)
                .flat()
                .join("<br>");
        } else if (err.value?.message) {
            advisor.value.message = err.value.message;
    } else {
      advisor.value.message = "Ett serverfel uppstod. Försök igen.";
    }

    setTimeout(() => {
      advisor.value.show = false;
      advisor.value.type = "";
      advisor.value.message = "";
    }, 3000);

};

const handlePhoneInput = () => {
    phone.value = normalizeUserPhoneForInput(phone.value)
}

const handleLandlineInput = () => {
    landline.value = normalizeLandlineForInput(landline.value)
}

const onSubmit = async () => {
    // Validación manual ANTES de usar VForm.validate()
    // Verificar tab 0 (Konto)
    const hasMissingContactNumber = !hasPhoneValue(phone.value) && !hasPhoneValue(landline.value)
    const hasInvalidPhone = hasPhoneValue(phone.value)
        && (minLengthDigitsValidator(userPhoneDigits)(phone.value) !== true || phoneValidator(phone.value) !== true)
    const hasInvalidLandline = hasPhoneValue(landline.value) && phoneValidator(landline.value) !== true

    const hasTab0Errors = !name.value?.trim() ||
        !last_name.value?.trim() ||
        !email.value?.trim() ||
        !address.value?.trim() ||
        hasMissingContactNumber ||
        hasInvalidPhone ||
        hasInvalidLandline ||
        (email.value && emailValidator(email.value) !== true) ||
        !position.value
    const hasTab1Errors = assignedPermissions.value.length === 0

    // Lógica de navegación entre tabs (0, 1, 2, 3)
    if (currentTab.value === 0) {
        if (hasTab0Errors) {
            // Validar el formulario para mostrar errores visuales
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Konto',
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
            isUserPermissionsDialog.value = true
            return
        }
    }
    
    if (currentTab.value === 1) {
        // Si hay errores en tabs anteriores, regresar al primero con error
        if (hasTab0Errors) {
            currentTab.value = 0
            
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Konto',
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
            advisor.value = {
                type: 'warning',
                message: 'Vänligen välj minst en behörighet i fliken Behörigheter',
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

                formData.append('email', email.value)
                formData.append('name', name.value)
                formData.append('last_name', last_name.value)
                formData.append('personal_phone', formatUserPhoneForPayload(phone.value))
                formData.append('personal_landline', normalizeLandlineForInput(landline.value))
                formData.append('personal_address', address.value)
                formData.append('position', position.value)

                assignedPermissions.value.forEach(p => formData.append('permissions[]', p))

                isRequestOngoing.value = true

                usersStores.updateUser(formData, id.value)
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
    email: email.value,
    name: name.value,
    password: password.value,
    last_name: last_name.value,
    phone: phone.value,
    landline: landline.value,
    address: address.value,
    position: position.value,
    assignedPermissions: assignedPermissions.value
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

const goToProfile = () => {

    let data = {
        message: 'Medarbetaren har lagts uppdaterats!',
        error: false
    }

    router.push({ name : 'dashboard-my-team'})
    emitter.emit('toast', data)  

};

</script>

<template>
    <section class="page-section" ref="sectionEl">
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
            validate-on="submit"
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
                            :to="{ name: 'dashboard-my-team' }"
                        >
                            <VIcon icon="custom-return" size="24" />
                            Tillbaka
                        </VBtn>
                        
                        <div class="d-flex flex-column gap-4">
                            <span class="title-page">
                                Uppdatera medarbetare
                            </span>
                        </div>

                        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

                        <div 
                            :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-4 align-center'"
                        >
                            <VBtn
                                class="btn-light w-auto" 
                                block
                                :to="{ name: 'dashboard-my-team' }">
                                <VIcon icon="custom-return" size="24" />
                                Avbryt
                            </VBtn>
                        </div>
                    </div>
                </VCardText>

                <VDivider :class="windowWidth < 1024 ? 'mb-4' : 'mb-8'" />

                <MobileScrollTabs 
                    :target-ref="sectionEl"
                    v-model="currentTab" 
                    grow             
                    :show-arrows="false"
                    class="users-tabs" 
                >
                    <VTab :class="{ 'tab-completed': currentTab > 0 }">
                        <VIcon size="24" icon="custom-profile" />
                        Konto
                    </VTab>
                    <VTab :class="{ 'tab-completed': currentTab > 1 }">
                        <VIcon size="24" icon="custom-settings-light" />
                        Behörigheter
                    </VTab>
                </MobileScrollTabs>
                      
                <VCardText class="px-0 pb-2">
                    <VWindow v-model="currentTab">
                        <!--Försäljning-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Namn*" />
                                            <VTextField
                                                v-model="name"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Efternamn*" />
                                            <VTextField
                                                v-model="last_name"
                                                :rules="[requiredValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
                                            <VTextField
                                                v-model="email"
                                                type="email"
                                                disabled
                                                :rules="[requiredValidator,emailValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Lösenord" />
                                            <VTextField
                                                v-model="email"
                                                disabled
                                                :type="isPasswordVisible ? 'text' : 'password'"
                                                :append-inner-icon="isPasswordVisible ? 'custom-eye-off' : 'custom-eye'"
                                                @click:append-inner="isPasswordVisible = !isPasswordVisible"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mobilnummer" />
                                            <VTextField
                                                v-model="phone"
                                                type="tel"
                                                class="always-show-prefix"
                                                :rules="userPhoneRules"
                                                :min-length="userPhoneDigits"
                                                :maxlength="userPhoneDigits"
                                                :prefix="userPhonePrefix"
                                                inputmode="numeric"
                                                @input="handlePhoneInput"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon" />
                                            <VTextField
                                                v-model="landline"
                                                type="tel"
                                                inputmode="numeric"
                                                :rules="landlineRules"
                                                @input="handleLandlineInput"
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
                                                v-model="position"
                                                label="Roll*"
                                                :items="positions"
                                                :item-title="item => item.name"
                                                :item-value="item => item.id"
                                                :rules="[requiredValidator]"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />
                                        </div>
                                    </div>

                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Inbytesfordon-->
                        <VWindowItem class="px-md-0">
                            <!-- <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="text-modules mb-5">
                                        Modules
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                canWithPlan('view','clients') ||
                                                canWithPlan('create','clients') ||
                                                canWithPlan('edit','clients') ||
                                                canWithPlan('delete','clients')
                                            "
                                        >
                                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Kunder" />
                                            <div class="ml-2 permissions-grid">
                                                <VCheckbox
                                                    v-if="canWithPlan('view','clients')"
                                                    v-model="assignedPermissions"
                                                    label="view clients"
                                                    value="view clients"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('create','clients')"
                                                    v-model="assignedPermissions"
                                                    label="create clients"
                                                    value="create clients"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('edit','clients')"
                                                    v-model="assignedPermissions"
                                                    label="edit clients"
                                                    value="edit clients"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('delete','clients')"
                                                    v-model="assignedPermissions"
                                                    label="delete clients"
                                                    value="delete clients"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>

                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                canWithPlan('view','billings') ||
                                                canWithPlan('create','billings') ||
                                                canWithPlan('edit','billings') ||
                                                canWithPlan('delete','billings')
                                            "
                                        >
                                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Fakturor" />
                                            <div class="ml-2 permissions-grid">
                                                <VCheckbox
                                                    v-if="canWithPlan('view','billings')"
                                                    v-model="assignedPermissions"
                                                    label="view billings"
                                                    value="view billings"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('create','billings')"
                                                    v-model="assignedPermissions"
                                                    label="create billings"
                                                    value="create billings"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('edit','billings')"
                                                    v-model="assignedPermissions"
                                                    label="edit billings"
                                                    value="edit billings"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('delete','billings')"
                                                    v-model="assignedPermissions"
                                                    label="delete billings"
                                                    value="delete billings"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>

                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                canWithPlan('view','stock') ||
                                                canWithPlan('create','stock') ||
                                                canWithPlan('edit','stock') ||
                                                canWithPlan('delete','stock') ||
                                                canWithPlan('view','sold') ||
                                                canWithPlan('delete','sold')
                                            "
                                        >
                                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Mitt Fordonslager" />
                                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'">
                                                <div class="ml-5 w-100">
                                                    <VLabel class="mb-4 text-body-3 text-high-emphasis" text="I Lager" 
                                                        v-if="
                                                            canWithPlan('view','stock') ||
                                                            canWithPlan('create','stock') ||
                                                            canWithPlan('edit','stock') ||
                                                            canWithPlan('delete','stock')
                                                        "
                                                    />
                                                    <div class="demo-space-x mb-4 ml-2 permissions-grid"
                                                        v-if="
                                                            canWithPlan('view','stock') ||
                                                            canWithPlan('create','stock') ||
                                                            canWithPlan('edit','stock') ||
                                                            canWithPlan('delete','stock')
                                                        "
                                                    >
                                                        <VCheckbox
                                                            v-if="canWithPlan('view','stock')"
                                                            v-model="assignedPermissions"
                                                            label="view stock"
                                                            value="view stock"
                                                            :readonly="readonly"
                                                        />
                                                        <VCheckbox
                                                            v-if="canWithPlan('create','stock')"
                                                            v-model="assignedPermissions"
                                                            label="create stock"
                                                            value="create stock"
                                                            :readonly="readonly"
                                                        />
                                                        <VCheckbox
                                                            v-if="canWithPlan('edit','stock')"
                                                            v-model="assignedPermissions"
                                                            label="edit stock"
                                                            value="edit stock"
                                                            :readonly="readonly"
                                                        />
                                                        <VCheckbox
                                                            v-if="canWithPlan('delete','stock')"
                                                            v-model="assignedPermissions"
                                                            label="delete stock"
                                                            value="delete stock"
                                                            :readonly="readonly"
                                                        />
                                                    </div>
                                                    <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Sålda Fordon" 
                                                        v-if="
                                                            canWithPlan('view','sold') ||
                                                            canWithPlan('delete','sold')
                                                        "
                                                    />
                                                    <div class="ml-2 permissions-grid"
                                                        v-if="
                                                            canWithPlan('view','sold') ||
                                                            canWithPlan('delete','sold')
                                                        "
                                                    >
                                                        <VCheckbox
                                                            v-if="canWithPlan('view','sold')"
                                                            v-model="assignedPermissions"
                                                            label="view sold"
                                                            value="view sold"
                                                            :readonly="readonly"
                                                        />
                                                        <VCheckbox
                                                            v-if="canWithPlan('delete','sold')"
                                                            v-model="assignedPermissions"
                                                            label="delete sold"
                                                            value="delete sold"
                                                            :readonly="readonly"
                                                        />
                                                    </div>                                
                                                </div>
                                            </div>
                                        </div>


                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                canWithPlan('view','agreements') ||
                                                canWithPlan('create','agreements') ||
                                                canWithPlan('edit','agreements') ||
                                                canWithPlan('delete','agreements')
                                            "
                                        >
                                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Avtal" />
                                            <div class="ml-2 permissions-grid">
                                                <VCheckbox
                                                    v-if="canWithPlan('view','agreements')"
                                                    v-model="assignedPermissions"
                                                    label="view agreements"
                                                    value="view agreements"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('create','agreements')"
                                                    v-model="assignedPermissions"
                                                    label="create agreements"
                                                    value="create agreements"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('edit','agreements')"
                                                    v-model="assignedPermissions"
                                                    label="edit agreements"
                                                    value="edit agreements"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('delete','agreements')"
                                                    v-model="assignedPermissions"
                                                    label="delete agreements"
                                                    value="delete agreements"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>
                                        
                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                canWithPlan('view','signed-documents') ||
                                                canWithPlan('create','signed-documents') ||
                                                canWithPlan('edit','signed-documents') ||
                                                canWithPlan('delete','signed-documents')
                                            "
                                        >
                                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="E-signering" />
                                            <div class="demo-space-x ml-5"
                                                :class="windowWidth < 1024 ? 'd-flex flex-column align-start' : 'permissions-grid'"
                                            >
                                                <VCheckbox
                                                    v-if="canWithPlan('view','signed-documents')"
                                                    v-model="assignedPermissions"
                                                    label="view signed-documents"
                                                    value="view signed-documents"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('create','signed-documents')"
                                                    v-model="assignedPermissions"
                                                    label="create signed-documents"
                                                    value="create signed-documents"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('edit','signed-documents')"
                                                    v-model="assignedPermissions"
                                                    label="edit signed-documents"
                                                    value="edit signed-documents"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('delete','signed-documents')"
                                                    v-model="assignedPermissions"
                                                    label="delete signed-documents"
                                                    value="delete signed-documents"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>

                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                canWithPlan('view','payouts') ||
                                                canWithPlan('create','payouts') ||
                                                canWithPlan('edit','payouts') ||
                                                canWithPlan('delete','payouts')
                                            "
                                        >
                                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Swish" />
                                            <div class="ml-2 permissions-grid">
                                                <VCheckbox
                                                    v-if="canWithPlan('view','payouts')"
                                                    v-model="assignedPermissions"
                                                    label="view payouts"
                                                    value="view payouts"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('create','payouts')"
                                                    v-model="assignedPermissions"
                                                    label="create payouts"
                                                    value="create payouts"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('edit','payouts')"
                                                    v-model="assignedPermissions"
                                                    label="edit payouts"
                                                    value="edit payouts"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('delete','payouts')"
                                                    v-model="assignedPermissions"
                                                    label="delete payouts"
                                                    value="delete payouts"
                                                    :readonly="readonly"
                                                />
                                            </div> 
                                        </div>

                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                canWithPlan('view','notes') ||
                                                canWithPlan('create','notes') ||
                                                canWithPlan('edit','notes') ||
                                                canWithPlan('delete','notes')
                                            "
                                        >
                                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Mina Värderingar" />
                                            <div class="ml-2 permissions-grid">
                                                <VCheckbox
                                                    v-if="canWithPlan('view','notes')"
                                                    v-model="assignedPermissions"
                                                    label="view notes"
                                                    value="view notes"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('create','notes')"
                                                    v-model="assignedPermissions"
                                                    label="create notes"
                                                    value="create notes"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('edit','notes')"
                                                    v-model="assignedPermissions"
                                                    label="edit notes"
                                                    value="edit notes"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('delete','notes')"
                                                    v-model="assignedPermissions"
                                                    label="delete notes"
                                                    value="delete notes"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>

                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                canWithPlan('view','my-team') ||
                                                canWithPlan('create','my-team') ||
                                                canWithPlan('edit','my-team') ||
                                                canWithPlan('delete','my-team') ||
                                                canWithPlan('view','team-reports')
                                            "
                                        >
                                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Mitt team" />
                                            <div class="ml-2 permissions-grid">
                                                <VCheckbox
                                                    v-if="canWithPlan('view','my-team')"
                                                    v-model="assignedPermissions"
                                                    label="view my-team"
                                                    value="view my-team"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('create','my-team')"
                                                    v-model="assignedPermissions"
                                                    label="create my-team"
                                                    value="create my-team"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('edit','my-team')"
                                                    v-model="assignedPermissions"
                                                    label="edit my-team"
                                                    value="edit my-team"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('delete','my-team')"
                                                    v-model="assignedPermissions"
                                                    label="delete my-team"
                                                    value="delete my-team"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="canWithPlan('view','team-reports')"
                                                    v-model="assignedPermissions"
                                                    label="view team-reports"
                                                    value="view team-reports"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </VCol>
                            </VRow> -->

                            <VAlert
                                v-if="permissionLoadError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                            >
                                {{ permissionLoadError }}
                                <VBtn
                                    variant="text"
                                    :disabled="isPermissionsLoading"
                                    @click="loadPermissions"
                                >
                                    Försök igen
                                </VBtn>
                            </VAlert>
                            <p
                                v-if="isPermissionsLoading"
                                class="text-medium-emphasis"
                                role="status"
                            >
                                Hämtar behörigheter...
                            </p>
                            <fieldset
                                v-if="permissionsLoaded"
                                class="permission-fieldset"
                                :disabled="isPermissionsLoading || isRequestOngoing"
                                aria-label="Behörigheter"
                            >
                                <section
                                    v-for="section in permissionSections"
                                    :key="section.title"
                                    class="permission-section"
                                    :aria-label="section.title"
                                >
                                    <header class="permission-section-header">
                                        <h3>{{ section.title }}</h3>
                                        <span class="permission-count">
                                            {{ selectedCount(section) }}/{{ selectablePermissions(section).length }}
                                        </span>
                                        <div class="section-select-all">
                                            <VCheckbox
                                                :model-value="isSectionSelected(section)"
                                                :indeterminate="isSectionIndeterminate(section)"
                                                true-icon="custom-checked-filled-checkbox"
                                                false-icon="custom-empty-checkbox"
                                                indeterminate-icon="custom-minus-checkbox"
                                                color="#454545"
                                                :ripple="false"
                                                hide-details
                                                :disabled="isPermissionsLoading || isRequestOngoing"
                                                :aria-label="`Välj alla i ${section.title}`"
                                                label="Välj alla"
                                                @update:model-value="toggleSection(section, $event)"
                                            />
                                        </div>
                                    </header>

                                    <div
                                        v-for="module in section.modules"
                                        :key="module.title"
                                        class="permission-module"
                                        role="group"
                                        :aria-label="module.title"
                                    >
                                        <div class="module-heading d-flex flex-column justify-content-start">
                                            <h4>{{ module.title }}</h4>
                                            <p v-if="module.description">{{ module.description }}</p>
                                        </div>
                                        <div class="module-rows">
                                            <div
                                                v-for="row in getModuleRows(module)"
                                                :key="row.title"
                                                class="permission-row"
                                                :class="{ 'permission-row-nested': module.children }"
                                                role="group"
                                                :aria-label="row.title"
                                            >
                                                <span
                                                    v-if="module.children"
                                                    class="permission-row-title"
                                                >
                                                    {{ row.title }}
                                                </span>
                                                <div class="permission-options">
                                                    <div
                                                        v-for="permission in row.permissions"
                                                        :key="permission.value"
                                                        class="permission-chip"
                                                        :class="{
                                                            'permission-chip-selected': assignedPermissions.includes(permission.value),
                                                            'permission-chip-disabled': permission.disabled,
                                                        }"
                                                        :title="permission.disabled ? 'Endast SuperAdmin kan tilldela denna behörighet.' : undefined"
                                                    >
                                                        <VCheckbox
                                                            v-model="assignedPermissions"
                                                            :value="permission.value"
                                                            :label="permissionLabels.get(permission.value)"
                                                            :aria-label="permissionLabels.get(permission.value)"
                                                            :disabled="isRequestOngoing || permission.disabled"
                                                            true-icon="custom-checked-filled-checkbox"
                                                            false-icon="custom-empty-checkbox"
                                                            indeterminate-icon="custom-minus-checkbox"
                                                            color="#454545"
                                                            :ripple="false"
                                                            hide-details
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </fieldset>
                        </VWindowItem>
                    </VWindow>
                </VCardText>

                <VCardText
                    class="p-0 d-flex w-100 align-center justify-between my-4 gap-4"
                    :class="windowWidth < 1024 ? 'flex-column' : ''"
                >
                    <span v-if="currentTab === 1 && permissionsLoaded" class="selection-summary" aria-live="polite">
                        <strong style="color: #1C2925;">{{ selectedPermissionsCount }}</strong>
                        av {{ totalPermissions }} behörigheter aktiva
                    </span>
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
                            <VIcon v-if="currentTab === 1" icon="custom-save"  size="24" />
                            {{ (currentTab === 1) ? 'Uppdatering' : 'Nästa' }}
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
                    <VIcon size="72" icon="custom-f-checkmark" />
                </VCardText>
                <VCardText class="dialog-title-box justify-center">
                    <div class="dialog-title">Medarbetaren har lagts uppdaterats!</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    Ändringarna har sparats.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="reloadPage" >
                        Redigera Medarbetaren 
                    </VBtn>
                    <VBtn 
                        class="btn-gradient"
                        :class="windowWidth < 1024 ? '' : 'w-30'" 
                        @click="goToProfile">
                        Klar
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
                    <div class="dialog-title">Kunde inte uppdateras till medarbetaren</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    Ett oväntat fel inträffade och ändringarna kunde inte sparas. Vänligen kontrollera din anslutning och försök igen.
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

    .always-show-prefix .v-text-field__prefix {
        opacity: 1 !important;
    }

    .border-bottom-secondary {
        border-bottom: 1px solid #d9d9d9;
        padding-bottom: 10px;
    }

    .justify-content-end {
        justify-content: end !important;
    }

    .v-tabs.users-tabs {
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
            .v-tabs.users-tabs {
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
                        opacity: 1 !important;
                        padding-top: 8px !important  ;
                        height: 48px;
                        color: #33303CAD;
                    }
                }
            }
        }

        .v-input.always-show-prefix {
            .v-input__control {
                .v-field {
                    .v-field__input {
                        padding: 12px 0 !important;
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

    .text-modules {
        font-weight: 700;
        font-size: 20px;
        line-height: 100%;
        letter-spacing: 0;
        color: #878787;
    }

    .text-body-3 {
        font-weight: 700;
        font-size: 16px;
        line-height: 100%;
        letter-spacing: 0;
        color: #454545;
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Crea 2 columnas de igual tamaño */
        gap: 16px; /* Espacio entre elementos */
    }
    
    .permissions-card {
        border: 1px solid #E7E7E7;
        border-radius: 16px !important;
        padding: 16px;
    }

    .permission-fieldset {
        min-inline-size: 0;
        padding: 0;
        border: 0;
    }

    .permission-section {
        overflow: hidden;
        border: 1px solid #E3EAE6;
        border-radius: 16px;

        & + & {
            margin-block-start: 20px;
        }
    }

    .permission-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 20px;
        background: #F6FAF8;

        h3 {
            font-weight: 700;
            font-size: 17px;
            line-height: 100%;
            letter-spacing: 0;
            color: #1C2925;
        }
    }

    .permission-count {
        padding: 2px 9px;
        border: 1px solid #E3EAE6;
        border-radius: 999px;
        background: #FFFFFF;
        font-weight: 600;
        font-size: 12px;
        letter-spacing: 0;
        color: #6B7A75;
    }

    .section-select-all {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-inline-start: auto;
        cursor: pointer;
        white-space: nowrap;
        font-weight: 600;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 0;
        color: #1C2925;
    }

    .section-select-all,
    .permission-chip {
        .v-selection-control {
            min-height: 24px;
        }

        .v-selection-control__wrapper {
            --v-selection-control-size: 24px;

            margin-inline-end: 8px;
        }

        .v-label {
            color: inherit;
            font: inherit;
            opacity: 1;
            margin-left: 0;
        }
    }

    .permission-module {
        display: grid;
        grid-template-columns: minmax(160px, 23%) minmax(0, 1fr);
        gap: 24px;
        padding: 16px 20px;
        border-block-start: 1px solid #edf1ef;
    }

    .module-heading {
        h4 {
            font-weight: 600;
            font-size: 15px;
            line-height: 100%;
            letter-spacing: 0;
            color: #1C2925;
        }

        p {
            margin: 2px 0 0;
            font-weight: 400;
            font-size: 12.5px;
            line-height: 16.88px;
            letter-spacing: 0;
            color: #6B7A75;
        }
    }

    .permission-row {
        display: flex;
        align-items: center;
        gap: 12px;

        & + & {
            margin-block-start: 10px;
        }
    }

    .permission-row-nested {
        display: grid;
        grid-template-columns: 90px minmax(0, 1fr);
    }

    .permission-row-title {
        font-weight: 600;
        font-size: 13px;
        line-height: 100%;
        letter-spacing: 0;
        color: #6B7A75;
    }

    .permission-options {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .permission-chip {
        display: inline-flex;
        flex: 0 1 auto;
        align-items: center;
        padding: 8px 16px;
        border: 1px solid #E3EAE6;
        border-radius: 10px;
        background: #FFFFFF;
        font-weight: 500;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 0;
        color: #1C2925;
        cursor: pointer;

        .v-input {
            flex: 1 1 auto;
            min-inline-size: 0;
        }

        .v-input__control,
        .v-selection-control {
            min-inline-size: 0;
            inline-size: 100%;
        }

        .v-selection-control__wrapper {
            flex-shrink: 0;
        }

        &:focus-within {
            outline: 2px solid #55786b;
            outline-offset: 2px;
        }
    }

    .permission-chip-selected {
        border-color: #B9E5CB;
        background: #F1FAF5;
    }

    .permission-chip-disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    .permission-fieldset:disabled .v-label {
        cursor: wait;
    }

    .module-heading p {
        color: #6e7c78;
    }

    .selection-summary {
        font-weight: 400;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 0;
        color: #6B7A75;
    }

    @media (max-width: 767px) {
        .permission-section-header,
        .permission-module {
            padding: 14px;
        }

        .permission-section-header {
            gap: 8px;

            h3 {
                font-size: 16px;
            }
        }

        .permission-module {
            grid-template-columns: minmax(0, 1fr);
            gap: 10px;
        }

        .module-heading {
            h4 {
                font-weight: 600;
                font-size: 15px;
                line-height: 100%;
                letter-spacing: 0;
            }

            p {
                margin-block-start: 4px;
                font-weight: 400;
                font-size: 12.5px;
                line-height: 100%;
                letter-spacing: 0;
            }
        }

        .permission-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 6px;
        }

        .permission-row-title {
            font-size: 10px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .permission-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
        }

        .permission-chip {
            min-inline-size: 0;
            min-block-size: 34px;

            .v-label {
                min-inline-size: 0;
                overflow-wrap: anywhere;
                line-height: 1.35;
                white-space: normal;
                margin-left: 0;
            }
        }

        .permission-row-nested .permission-chip {
            font-size: 10px;
            letter-spacing: 0.04em;
        }
    }
</style>
<style lang="scss">

    .border-card-comment {
        border: 1px solid #E7E7E7;
        border-radius: 16px !important;
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
      permissionsAny:
        - action: edit
          subject: users
        - action: edit
          subject: my-team
</route>