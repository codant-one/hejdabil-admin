<script setup>

import { useDisplay } from "vuetify";
import { onBeforeRouteLeave } from 'vue-router';
import { ref, watchEffect, inject, computed, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { requiredValidator, emailValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useAuthStores } from '@/stores/useAuth'
import { useAgreementsStores } from '@/stores/useAgreements'
import { useCarInfoStores } from '@/stores/useCarInfo'
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { usePersonInfoStores } from '@/stores/usePersonInfo'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const router = useRouter()
const emitter = inject("emitter")

const authStores = useAuthStores()
const carInfoStores = useCarInfoStores()
const agreementsStores = useAgreementsStores()
const companyInfoStores = useCompanyInfoStores()
const personInfoStores = usePersonInfoStores()
const ability = useAppAbility()

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const sectionEl = ref(null);
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const isRequestOngoing = ref(false)
const refForm = ref()
const currentTab = ref(0)

const isConfirmLeaveVisible = ref(false)
const nextRoute = ref(null)
const initialData = ref(null)
const allowNavigation = ref(false)

const userData = ref(null)
const role = ref(null)
const currencies = ref([])
const currency_id = ref(1)

const brands = ref([])
const models = ref([])
const brand_id = ref(null)
const model_id = ref(null)
const model = ref(null)
const modelsByBrand = ref([])
const offer_id = ref(null)
const reg_num = ref(null)
const mileage = ref(null)
const price = ref(null)
const comment = ref(null)
const terms_other_conditions = ref(null)

// Kund
const clients = ref([])
const client_types = ref([])
const identifications = ref([])
const client_id = ref(null)
const client_type_id = ref(null)
const identification_id = ref(null)
const fullname = ref(null)
const email = ref(null)
const organization_number = ref(null)
const address = ref(null)
const street = ref(null)
const postal_code = ref(null)
const phone = ref(null)
const disabled_client = ref(false)
const save_client = ref(true)

const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);
const err = ref(null);

// Recargar la página al crear otro acuerdo
function reloadPage() {
  window.location.reload();
}

watchEffect(async () => {
    isRequestOngoing.value = true

    await agreementsStores.info()

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value.roles[0].name

    const { user_data, userAbilities } = await authStores.me(userData.value)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

    ability.update(userAbilities)

    localStorage.setItem('user_data', JSON.stringify(user_data))

    if(role.value === 'Supplier') {
      offer_id.value = user_data.supplier.user.offers.length + 1
    } else if(role.value === 'User') {
      offer_id.value = user_data.supplier.boss.user.offers.length + 1
    } else {
      offer_id.value = agreementsStores.offer_id + 1
    }

    brands.value = agreementsStores.brands
    models.value = agreementsStores.models 
    currencies.value = agreementsStores.currencies

    clients.value = agreementsStores.clients
    client_types.value = agreementsStores.client_types
    identifications.value = agreementsStores.identifications

    isRequestOngoing.value = false

    nextTick(() => {
      initialData.value = JSON.parse(JSON.stringify(currentData.value))
    })
   
})

const formatOrgNumber = () => {
  if (!organization_number.value) return

  let numbers = organization_number.value.replace(/\D/g, '')
  if (numbers.length > 4) {
    numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
  }
  organization_number.value = numbers
}

const isCompanyNumber = value => {
  const cleanNumber = (value ?? '').toString().replace(/[\s\-]/g, '')
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
  if (!client) return

  const selected = clients.value.find(item => item.id === client)
  if (!selected) return

  fullname.value = selected.fullname
  email.value = selected.email
  organization_number.value = selected.organization_number
  address.value = selected.address
  street.value = selected.street
  postal_code.value = selected.postal_code
  phone.value = selected.phone

  // Si el cliente seleccionado tiene tipo/identificación, asigna si existen
  client_type_id.value = selected.client_type_id ?? client_type_id.value
  identification_id.value = selected.identification_id ?? identification_id.value

  disabled_client.value = true
  save_client.value = false
}

const selectBrand = brand => {
  if (brand) {
      let _brand = brands.value.find(item => item.id === brand)
  
      model_id.value = ''
      modelsByBrand.value = models.value.filter(item => item.brand_id === _brand.id)
  }
}

const onClearBrand = () => {
  modelsByBrand.value = []
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

const selectModel = selected => {

  model.value = selected !== 0 ? null : model.value

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
    const isSuccess = carRes?.success === true || carRes?.sucess === true
    
    if (isSuccess && carRes?.result) {
        
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
        
        if (carRes.result.mileage) {
            mileage.value = carRes.result.mileage
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
        message: error?.response?.data?.message || error?.message || 'Fel vid hämtning av fordonsdata',
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

const goToAgreements = () => {

  let data = {
      message: 'Erbjudande framgångsrikt skapat',
      error: false
  }

  router.push({ name : 'dashboard-admin-agreements'})
  emitter.emit('toast', data)  

};

const onSubmit = async () => {
  // Validación manual ANTES de usar VForm.validate()
  // Verificar tab 0 (Erbjudande)
  const hasTab0Errors = !reg_num.value || 
                        !brand_id.value || 
                        (model_id.value !== 0 && !model_id.value) || // si no es 0 y está vacío → error
                        (model_id.value === 0 && !model.value) || // si es 0, el campo texto debe tener valor
                        !mileage.value || 
                        !price.value

  // Verificar tab 1 (Kund)
  const hasTab1Errors = !organization_number.value || 
                        (organization_number.value && minLengthDigitsValidator(10)(organization_number.value) !== true) ||
                        !client_type_id.value || 
                        !fullname.value || 
                        !address.value || 
                        !postal_code.value || 
                        !street.value || 
                        !phone.value || 
                        (phone.value && phoneValidator(phone.value) !== true) ||
                        !identification_id.value || 
                        !email.value || 
                        (email.value && emailValidator(email.value) !== true)

    // Si estamos en el tab 0, solo validar y avanzar (NO guardar)
    if (currentTab.value === 0) {
        if (hasTab0Errors) {
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Erbjudande',
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
        
        // Si no hay errores, avanzar al siguiente tab
        currentTab.value = 1
        return
    }
    
    // Si estamos en el tab 1, verificar TODOS los tabs y GUARDAR
    if (currentTab.value === 1) {
        // Si hay errores en el tab 0, regresar
        if (hasTab0Errors) {
            currentTab.value = 0
            
            await nextTick()
            refForm.value?.validate()
            
            advisor.value = {
                type: 'warning',
                message: 'Vänligen fyll i alla obligatoriska fält i fliken Erbjudande',
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
        
        // Si hay errores en el tab 1
        if (hasTab1Errors) {
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

      // Si no hay errores en ningún tab, proceder con el submit
      refForm.value?.validate().then(({ valid: isValid }) => {
          if (isValid) {
            let formData = new FormData()

            //vehicle
            formData.append('reg_num', reg_num.value)
            formData.append('brand_id', brand_id.value)
            formData.append('model_id', model_id.value)
            formData.append('model', model.value)
            formData.append('offerId', offer_id.value)
            formData.append('mileage', mileage.value)
            formData.append('comment', comment.value)
            formData.append('price', price.value)
            formData.append('terms_other_conditions', terms_other_conditions.value)

            //kund (agreement_client)
            formData.append('client_id', client_id.value)
            formData.append('client_type_id', client_type_id.value)
            formData.append('identification_id', identification_id.value)
            formData.append('fullname', fullname.value)
            formData.append('email', email.value)
            formData.append('organization_number', organization_number.value)
            formData.append('address', address.value)
            formData.append('street', street.value)
            formData.append('postal_code', postal_code.value)
            formData.append('phone', phone.value)
            formData.append('save_client', save_client.value);

            //agreement
            formData.append('agreement_type_id', 4)
            formData.append('currency_id', currency_id.value)
            formData.append('price', price.value)
            formData.append('residual_debt', 0)
            formData.append('guaranty', 0)
            formData.append('insurance_company', 0)

            isRequestOngoing.value = true

            agreementsStores.addAgreement(formData)
              .then((res) => {
                  if (res.data.success) {
                      allowNavigation.value = true;

                      // Save current state so the dirty-check stops blocking navigation
                      initialData.value = JSON.parse(JSON.stringify(currentData.value));

                      skapatsDialog.value = true;
                  } else {
                      // Save current state so the dirty-check stops blocking navigation
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

const currentData = computed(() => ({
    // Tab 0: Erbjudande
    reg_num: reg_num.value,
    brand_id: brand_id.value,
    model_id: model_id.value,
    model: model.value,
    mileage: mileage.value,
    price: price.value,
    comment: comment.value,
    terms_other_conditions: terms_other_conditions.value,
    // Tab 1: Kund
    client_id: client_id.value,
    organization_number: organization_number.value,
    client_type_id: client_type_id.value,
    identification_id: identification_id.value,
    fullname: fullname.value,
    email: email.value,
    address: address.value,
    street: street.value,
    postal_code: postal_code.value,
    phone: phone.value,
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
                  :to="{ name: 'dashboard-admin-agreements' }"
              >
                  <VIcon icon="custom-return" size="24" />
                  Gå ut
              </VBtn>
              
              <div class="d-flex flex-column gap-4">
                  <span class="title-page">
                      Prisförslag
                  </span>
              </div>

              <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

              <div 
                  :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-4 align-center'"
              >
                  <VBtn
                      class="btn-light w-auto" 
                      block
                      :to="{ name: 'dashboard-admin-agreements' }">
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
          <VTab :value="0" :class="{ 'tab-completed': currentTab > 0 }">
            <VIcon size="24" icon="custom-autofordon" />
            Erbjudande
          </VTab>
          <VTab :value="1" :class="{ 'tab-completed': currentTab > 1 }">
            <VIcon size="24" icon="custom-clients" />
            Kund
          </VTab>
        </VTabs>

        <VCardText class="px-0">
          <VWindow v-model="currentTab">
            <!-- Erbjudande -->
            <VWindowItem :value="0" class="px-md-0">
              <VRow class="px-md-3">
                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                  <div class="title-tabs mb-5">
                    Detaljer om erbjudandet
                  </div>
                  <div 
                    class="d-flex flex-wrap"
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
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Offertnummer" />
                      <VTextField
                        v-model="offer_id"
                        readonly
                        disabled
                        prefix="#"
                        density="compact"
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Märke*" />
                      <AppAutocomplete
                        v-model="brand_id"
                        :items="brands"
                        :item-title="item => item.name"
                        :item-value="item => item.id"
                        autocomplete="off"
                        clearable
                        clear-icon="tabler-x"
                        :rules="[requiredValidator]"
                        @update:modelValue="selectBrand"
                        @click:clear="onClearBrand"
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
                        :rules="[requiredValidator]"
                        @update:modelValue="selectModel"
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
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mätarställning*" />
                      <VTextField 
                        v-model="mileage" 
                        suffix="Mil"
                        :rules="[requiredValidator]" 
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" :text="'Belopp ' + (currencies.find(item => item.id === currency_id)?.code || '') + '*'" />
                      <VTextField
                        v-model="price"
                        type="number"
                        min="0"
                        suffix="KR"
                        :rules="[requiredValidator]"
                      />
                    </div>
                    <div class="w-100">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Anmärkning" />
                      <VTextarea v-model="comment" rows="3" />
                    </div>
                    <div class="w-100">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Villkor och bestämmelser" />
                      <VTextarea v-model="terms_other_conditions" rows="3" />
                    </div>
                  </div>
                </VCol>
              </VRow>
            </VWindowItem>
            <!-- Kund -->
            <VWindowItem :value="1" class="px-md-0">
              <VRow class="px-md-3">
                <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                  <div class="title-tabs mb-5">
                    Kund
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
          <div class="dialog-title">Avtalet har skapats!</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Ditt nya avtal har sparats och finns nu i din avtalslista.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="goToAgreements" >
            Gå till avtalslistan
          </VBtn>
          <VBtn class="btn-gradient" @click="reloadPage">
            Skapa ett till avtal 
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
          <div class="dialog-title">Kunde inte skapa avtalet</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Ett fel inträffade. Kontrollera att alla obligatoriska fält är korrekt ifyllda och försök igen.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="showError">
            Stäng
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
            top: 12px !important;
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
</style>

<route lang="yaml">
  meta:
    action: create
    subject: agreements
</route>