<script setup>

import { useDisplay } from "vuetify";
import { onBeforeRouteLeave } from 'vue-router';
import { requiredValidator, yearValidator, emailValidator, phoneValidator, minLengthDigitsValidator, passwordValidator } from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'
import permissions from './permissions.vue'
import { formatNumber, formatDateSwedish } from '@/@core/utils/formatters'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router'
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import { id } from "date-fns/locale";

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
const isPasswordVisible = ref(false)
const isUserPermissionsDialog = ref(false)

const usersStores = useSuppliersStores()
const ability = useAppAbility()
const emitter = inject("emitter")
const err = ref(null);

const isRequestOngoing = ref(false)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref(0)
const isMobile = ref(false)

const email = ref('')
const name = ref('')
const password = ref('')
const last_name = ref('')
const phone = ref('----')
const address = ref('----')
const assignedPermissions = ref([])
const readonly =  ref(false)


   
   
   
       
const getPermissions = function(permissions){
    assignedPermissions.value = permissions
}       
   

// Recargar la página al crear otro acuerdo
function reloadPage() {
  window.location.reload();
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

    isRequestOngoing.value = true

      let data = {
        id: '',
        search: '',
        orderByField: 'order_id',
        orderBy: 'desc',
        limit: 10,
        page: 0
    }

    await usersStores.fetchUsers(data)

    // await agreementsStores.info()

    // userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    // role.value = userData.value.roles[0].name

    // const { user_data, userAbilities } = await authStores.me(userData.value)

    // localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

    // ability.update(userAbilities)

    // localStorage.setItem('user_data', JSON.stringify(user_data))

    // if(role.value === 'Supplier') {
    //     company.value = user_data.user_detail
    //     company.value.email = user_data.email
    //     company.value.name = user_data.name
    //     company.value.last_name = user_data.last_name
    //     agreement_id.value = user_data.supplier.agreements.length + 1
    // } else if(role.value === 'User') {
    //     company.value = user_data.supplier.boss.user.user_detail
    //     company.value.email = user_data.supplier.boss.user.email
    //     company.value.name = user_data.supplier.boss.user.name
    //     company.value.last_name = user_data.supplier.boss.user.last_name
    //     agreement_id.value = user_data.supplier.boss.agreements.length + 1
    // } else {
    //     await configsStores.getFeature('company')
    //     await configsStores.getFeature('logo')

    //     company.value = configsStores.getFeaturedConfig('company')
    //     company.value.logo = configsStores.getFeaturedConfig('logo').logo

    //     agreement_id.value = agreementsStores.agreement_id + 1
    // }

    // vehicles.value = agreementsStores.vehicles
    // guarantyTypes.value = agreementsStores.guarantyTypes
    // insuranceTypes.value = agreementsStores.insuranceTypes
    // brands.value = agreementsStores.brands
    // models.value = agreementsStores.models 
    // fuels.value = agreementsStores.fuels
    // gearboxes.value = agreementsStores.gearboxes
    // carbodies.value = agreementsStores.carbodies
    // currencies.value = agreementsStores.currencies
    // ivas.value = agreementsStores.ivas
    // clients.value = agreementsStores.clients
    // client_types.value = agreementsStores.client_types
    // identifications.value = agreementsStores.identifications
    // paymentTypes.value = agreementsStores.paymentTypes
    // advances.value = agreementsStores.advances

    // sale_date.value = formatDate(new Date())
    // purchase_date.value = formatDate(new Date())

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

const onSubmit = async () => {
    // Validación manual ANTES de usar VForm.validate()
        // Verificar tab 0 (Försäljning)
    const hasTab0Errors = !email.value || !name.value || !password.value || !last_name.value || !phone.value || !address.value


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
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Försäljning',
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
                formData.append('password', password.value)
                formData.append('last_name', last_name.value)
                formData.append('phone', phone.value)
                formData.append('address', address.value)
                // formData.append('permissions', assignedPermissions.value)
                assignedPermissions.value.forEach(p => formData.append('permissions[]', p))

                isRequestOngoing.value = true

                usersStores.addUser(formData)
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
    address: address.value,
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
      message: 'Medarbetaren har lagts till!',
      error: false
  }

  router.push({ name : 'dashboard-profile' })
  emitter.emit('toast', data)  

};

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
                            :to="{ name: 'dashboard-profile' }"
                        >
                            <VIcon icon="custom-return" size="24" />
                            Gå ut
                        </VBtn>
                        
                        <div class="d-flex flex-column gap-4">
                            <span class="title-page">
                                Lägg till medarbetare
                            </span>
                        </div>

                        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

                        <div 
                            :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-4 align-center'"
                        >
                            <VBtn
                                class="btn-light w-auto" 
                                block
                                :to="{ name: 'dashboard-profile' }">
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
                        <VIcon size="24" icon="custom-profile" />
                        Konto
                    </VTab>
                    <VTab :class="{ 'tab-completed': currentTab > 1 }">
                        <VIcon size="24" icon="custom-settings-light" />
                        Behörigheter
                    </VTab>
                </VTabs>
                      
                <VCardText class="px-0">
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
                                            :rules="[requiredValidator,emailValidator]"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Lösenord*" />
                                            <VTextField
                                                v-model="password"
                                                :type="isPasswordVisible ? 'text' : 'password'"
                                                :append-inner-icon="isPasswordVisible ? 'custom-eye-off' : 'custom-eye'"
                                                :rules="[requiredValidator]"
                                                @click:append-inner="isPasswordVisible = !isPasswordVisible"
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon" />
                                            <VTextField
                                            v-model="phone"
                                            type="tel"
                                            placeholder="+(XX) XXXXXXXXX"
                                            disabled
                                            />
                                        </div>
                                        <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress" />
                                            <VTextField
                                            v-model="address"
                                            disabled
                                            />
                                        </div>
                                    </div>

                                </VCol>
                            </VRow>
                        </VWindowItem>

                        <!--Inbytesfordon-->
                        <VWindowItem class="px-md-0">
                            <VRow class="px-md-3">
                                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                                    <div class="title-tabs mb-5">
                                        Modules
                                    </div>
                                    <div 
                                        class="d-flex flex-wrap"
                                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                                    >
                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                $can('view','clients') ||
                                                $can('create','clients') ||
                                                $can('edit','clients') ||
                                                $can('delete','clients')
                                            "
                                        >
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kunder" />
                                            <div class="demo-space-x ml-5 permissions-grid">
                                                <VCheckbox
                                                    v-if="$can('view','clients')"
                                                    v-model="assignedPermissions"
                                                    label="view clients"
                                                    value="view clients"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('create','clients')"
                                                    v-model="assignedPermissions"
                                                    label="create clients"
                                                    value="create clients"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('edit','clients')"
                                                    v-model="assignedPermissions"
                                                    label="edit clients"
                                                    value="edit clients"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('delete','clients')"
                                                    v-model="assignedPermissions"
                                                    label="delete clients"
                                                    value="delete clients"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>

                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                $can('view','billings') ||
                                                $can('create','billings') ||
                                                $can('edit','billings') ||
                                                $can('delete','billings')
                                            "
                                        >
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Fakturor" />
                                            <div class="demo-space-x ml-5 permissions-grid">
                                                <VCheckbox
                                                    v-if="$can('view','billings')"
                                                    v-model="assignedPermissions"
                                                    label="view billings"
                                                    value="view billings"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('create','billings')"
                                                    v-model="assignedPermissions"
                                                    label="create billings"
                                                    value="create billings"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('edit','billings')"
                                                    v-model="assignedPermissions"
                                                    label="edit billings"
                                                    value="edit billings"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('delete','billings')"
                                                    v-model="assignedPermissions"
                                                    label="delete billings"
                                                    value="delete billings"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>

                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                $can('view','stock') ||
                                                $can('create','stock') ||
                                                $can('edit','stock') ||
                                                $can('delete','stock') ||
                                                $can('view','sold') ||
                                                $can('delete','sold')
                                            "
                                        >
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mitt Fordonslager" />
                                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'">
                                                <div class="ml-5 w-100">
                                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="I Lager" 
                                                        v-if="
                                                            $can('view','stock') ||
                                                            $can('create','stock') ||
                                                            $can('edit','stock') ||
                                                            $can('delete','stock')
                                                        "
                                                    />
                                                    <div class="demo-space-x mb-4 ml-5 permissions-grid"
                                                        v-if="
                                                            $can('view','stock') ||
                                                            $can('create','stock') ||
                                                            $can('edit','stock') ||
                                                            $can('delete','stock')
                                                        "
                                                    >
                                                        <VCheckbox
                                                            v-if="$can('view','stock')"
                                                            v-model="assignedPermissions"
                                                            label="view stock"
                                                            value="view stock"
                                                            :readonly="readonly"
                                                        />
                                                        <VCheckbox
                                                            v-if="$can('create','stock')"
                                                            v-model="assignedPermissions"
                                                            label="create stock"
                                                            value="create stock"
                                                            :readonly="readonly"
                                                        />
                                                        <VCheckbox
                                                            v-if="$can('edit','stock')"
                                                            v-model="assignedPermissions"
                                                            label="edit stock"
                                                            value="edit stock"
                                                            :readonly="readonly"
                                                        />
                                                        <VCheckbox
                                                            v-if="$can('delete','stock')"
                                                            v-model="assignedPermissions"
                                                            label="delete stock"
                                                            value="delete stock"
                                                            :readonly="readonly"
                                                        />
                                                    </div>
                                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Sålda Fordon" 
                                                        v-if="
                                                            $can('view','sold') ||
                                                            $can('delete','sold')
                                                        "
                                                    />
                                                    <div class="demo-space-x ml-5 permissions-grid"
                                                        v-if="
                                                            $can('view','sold') ||
                                                            $can('delete','sold')
                                                        "
                                                    >
                                                        <VCheckbox
                                                            v-if="$can('view','sold')"
                                                            v-model="assignedPermissions"
                                                            label="view sold"
                                                            value="view sold"
                                                            :readonly="readonly"
                                                        />
                                                        <VCheckbox
                                                            v-if="$can('delete','sold')"
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
                                                $can('view','agreements') ||
                                                $can('create','agreements') ||
                                                $can('edit','agreements') ||
                                                $can('delete','agreements')
                                            "
                                        >
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Avtal" />
                                            <div class="demo-space-x ml-5 permissions-grid">
                                                <VCheckbox
                                                    v-if="$can('view','agreements')"
                                                    v-model="assignedPermissions"
                                                    label="view agreements"
                                                    value="view agreements"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('create','agreements')"
                                                    v-model="assignedPermissions"
                                                    label="create agreements"
                                                    value="create agreements"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('edit','agreements')"
                                                    v-model="assignedPermissions"
                                                    label="edit agreements"
                                                    value="edit agreements"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('delete','agreements')"
                                                    v-model="assignedPermissions"
                                                    label="delete agreements"
                                                    value="delete agreements"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>
                                        
                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                $can('view','signed-documents') ||
                                                $can('create','signed-documents') ||
                                                $can('edit','signed-documents') ||
                                                $can('delete','signed-documents')
                                            "
                                        >
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Signera dokument" />
                                            <div class="demo-space-x ml-5"
                                                :class="windowWidth < 1024 ? 'd-flex flex-column align-start' : 'permissions-grid'"
                                            >
                                                <VCheckbox
                                                    v-if="$can('view','signed-documents')"
                                                    v-model="assignedPermissions"
                                                    label="view signed-documents"
                                                    value="view signed-documents"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('create','signed-documents')"
                                                    v-model="assignedPermissions"
                                                    label="create signed-documents"
                                                    value="create signed-documents"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('edit','signed-documents')"
                                                    v-model="assignedPermissions"
                                                    label="edit signed-documents"
                                                    value="edit signed-documents"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('delete','signed-documents')"
                                                    v-model="assignedPermissions"
                                                    label="delete signed-documents"
                                                    value="delete signed-documents"
                                                    :readonly="readonly"
                                                />
                                            </div>
                                        </div>

                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                $can('view','payouts') ||
                                                $can('create','payouts') ||
                                                $can('edit','payouts') ||
                                                $can('delete','payouts')
                                            "
                                        >
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Swish" />
                                            <div class="demo-space-x ml-5 permissions-grid">
                                                <VCheckbox
                                                    v-if="$can('view','payouts')"
                                                    v-model="assignedPermissions"
                                                    label="view payouts"
                                                    value="view payouts"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('create','payouts')"
                                                    v-model="assignedPermissions"
                                                    label="create payouts"
                                                    value="create payouts"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('edit','payouts')"
                                                    v-model="assignedPermissions"
                                                    label="edit payouts"
                                                    value="edit payouts"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('delete','payouts')"
                                                    v-model="assignedPermissions"
                                                    label="delete payouts"
                                                    value="delete payouts"
                                                    :readonly="readonly"
                                                />
                                            </div> 
                                        </div>

                                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                                            v-if="
                                                $can('view','notes') ||
                                                $can('create','notes') ||
                                                $can('edit','notes') ||
                                                $can('delete','notes')
                                            "
                                        >
                                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mina Värderingar" />
                                            <div class="demo-space-x ml-5 permissions-grid">
                                                <VCheckbox
                                                    v-if="$can('view','notes')"
                                                    v-model="assignedPermissions"
                                                    label="view notes"
                                                    value="view notes"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('create','notes')"
                                                    v-model="assignedPermissions"
                                                    label="create notes"
                                                    value="create notes"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('edit','notes')"
                                                    v-model="assignedPermissions"
                                                    label="edit notes"
                                                    value="edit notes"
                                                    :readonly="readonly"
                                                />
                                                <VCheckbox
                                                    v-if="$can('delete','notes')"
                                                    v-model="assignedPermissions"
                                                    label="delete notes"
                                                    value="delete notes"
                                                    :readonly="readonly"
                                                />
                                            </div>
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
                            <VIcon v-if="currentTab === 1" icon="custom-save"  size="24" />
                            {{ (currentTab === 1) ? 'Skapa' : 'Nästa' }}
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
                    <div class="dialog-title">Medarbetaren har lagts till!</div>
                </VCardText>
                <VCardText class="dialog-text text-center">
                    En inbjudan har skickats via e-post. Personen finns nu listad i ditt team med valda behörigheter.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="reloadPage" >
                        Lägg till en till
                    </VBtn>
                    <VBtn class="btn-gradient" @click="goToProfile">
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
                    <div class="dialog-title">Kunde inte lägga till medarbetaren</div>
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

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Crea 2 columnas de igual tamaño */
        gap: 10px; /* Espacio entre elementos */
    }
    
    .permissions-card {
        border: 1px solid #E7E7E7;
        border-radius: 16px !important;
        padding: 16px;
    }
</style>
<style lang="scss">

    .border-card-comment {
        border: 1px solid #E7E7E7;
        border-radius: 16px !important;
    }

    .agreements-page .radio-form.v-radio-group .v-selection-control-group .v-radio:not(:last-child) {
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

<route lang="yaml">
    meta:
      action: create
      subject: users
</route>