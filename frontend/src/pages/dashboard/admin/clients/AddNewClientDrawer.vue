<script setup>

import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { emailValidator, requiredValidator, phoneValidator, minLengthDigitsValidator, duplicateOrganizationNumberValidator } from "@/@core/utils/validators";
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { usePersonInfoStores } from '@/stores/usePersonInfo'
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  client: {
    type: Object,
    required: false,
  },
  suppliers: {
    type: Object,
    required: false,
  },
  client_types: {
    type: Object,
    required: false,
  },
  countries: {
    type: Object,
    required: false
  },
  isDuplicate: {
    type: Boolean,
    required: false,
    default: false,
  }
});

const emit = defineEmits([
  "update:isDrawerOpen", 
  "clientData", 
  "resetDuplicate",
  "edited",
  "alert",
  "loading"
]);

const companyInfoStores = useCompanyInfoStores()
const personInfoStores = usePersonInfoStores()

const isFormValid = ref(false);
const refForm = ref();
const organizationNumberFieldRef = ref();

const id = ref(0)
const supplier_id = ref(null)
const client_type_id = ref(null)
const organization_number = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const fullname = ref('')
const email = ref('')
const reference = ref('')
const num_iva = ref('')
const country_id = ref('')
const comments = ref('')
const isEdit = ref(false)
const userData = ref(null)
const role = ref(null)
const isConfirmLeaveVisible = ref(false)

const initialData = ref(null)
const currentData = computed(() => ({
  supplier_id: supplier_id.value,
  organization_number: organization_number.value,
  address: address.value,
  street: street.value,
  postal_code: postal_code.value,
  phone: phone.value,
  fullname: fullname.value,
  email: email.value,
  reference: reference.value,
  num_iva: num_iva.value,
  comments: comments.value,
  client_type_id: client_type_id.value,
  country_id: country_id.value
}))

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const isDirty = computed(() => {
  if (!initialData.value) return false
  try {
    return JSON.stringify(currentData.value) !== JSON.stringify(initialData.value)
  } catch (e) {
    return true
  }
})

const organizationNumberRules = computed(() => [
  requiredValidator,
  minLengthDigitsValidator(10),
  duplicateOrganizationNumberValidator(props.isDuplicate),
])

const organizationNumberForeignRules = computed(() => [
  requiredValidator,
  duplicateOrganizationNumberValidator(props.isDuplicate),
])

watch(() => props.isDuplicate, async isDuplicate => {
  await nextTick()
  if (isDuplicate) {
    organizationNumberFieldRef.value?.validate?.()
    return
  }
  organizationNumberFieldRef.value?.resetValidation?.()
})

const getTitle = computed(() => {
  return isEdit.value ? "Uppdatera klient" : "Lägg till kund";
});

watchEffect(async () => {
  if (props.isDrawerOpen) {
    userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
    role.value = userData.value.roles[0].name;

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value.roles[0].name

    if (!(Object.entries(props.client).length === 0) && props.client.constructor === Object) {

      isEdit.value = true
      id.value = props.client.id
      supplier_id.value = props.client.supplier_id
      organization_number.value = props.client.organization_number
      address.value = props.client.address
      street.value = props.client.street
      postal_code.value = props.client.postal_code
      phone.value = props.client.phone
      fullname.value = props.client.fullname 
      email.value = props.client.email
      reference.value = props.client.reference
      num_iva.value = props.client.num_iva
      comments.value = props.client.comments
      client_type_id.value = props.client.client_type_id 
      country_id.value = props.client.country_id ?? props.client.country?.id ?? props.client.country?.name ?? null
    }

    // snapshot initial state after fields are populated
    nextTick(() => {
      initialData.value = { ...currentData.value }
      emit('edited', false)
    })
  }
});

// 👉 drawer close
const reallyCloseAndReset = () => {
  emit("update:isDrawerOpen", false);
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();

    organization_number.value = null
    address.value = null
    street.value = null
    postal_code.value = null
    phone.value = null
    fullname.value = null
    email.value = null
    reference.value = null
    num_iva.value = null
    comments.value = null
    client_type_id.value = null
    country_id.value = null

    isEdit.value = false 
    id.value = 0
    initialData.value = null
    emit('edited', false)
  })
}

defineExpose({
  reallyCloseAndReset,
})

const closeNavigationDrawer = () => {
  if (isDirty.value) {
    isConfirmLeaveVisible.value = true
    return
  }
  reallyCloseAndReset()
}

const formatOrgNumber = () => {
  let numbers = organization_number.value.replace(/\D/g, "");
  if (numbers.length > 4) {
    numbers = numbers.slice(0, -4) + "-" + numbers.slice(-4);
  }
  organization_number.value = numbers;
};

const handleOrganizationNumberInput = () => {
  formatOrgNumber()
  emit('resetDuplicate')
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
    emit("loading", true);

    const response = await companyInfoStores.getCompanyInfo(organization_number.value)
    
    emit("loading", false);

    if (response) {
          // Set Client Type to Företag
        const foretagType = props.client_types?.find(t => t.name === 'Företag')
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
      emit("loading", false);
      advisor.value = {
          type: 'error',
          message: 'Ingen företag hittades med det registreringsnumret',
          show: true
      }
      emit('alert', advisor)

      setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
        emit('alert', advisor)
      }, 3000)
  }
}

/**
 * Search for person information in SPAR (Statens Personadressregister) API.
 */
const searchPerson = async () => {
  try {
    emit("loading", true);

    const response = await personInfoStores.getPersonInfo(organization_number.value)

    emit("loading", false);

    if (response?.success && response?.data) {
        const personData = response.data

        // Set Client Type to Privat
        const privatType = props.client_types?.find(t => t.name === 'Privat')
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
    emit("loading", false);

    const errorMessage = error?.response?.data?.message || 'Ingen person hittades med det personnumret'
    
    advisor.value = {
        type: 'error',
        message: errorMessage,
        show: true
    }

    setTimeout(() => {
      advisor.value = {
          type: '',
          message: '',
          show: false
      }
      emit('alert', advisor)
    }, 3000)
  }
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData();

      const selectedCountry = Array.isArray(props.countries)
        ? props.countries.find(item => String(item.id) === String(country_id.value))
          || props.countries.find(item => item.name === country_id.value)
        : null

      const normalizedCountryId = selectedCountry?.id ?? country_id.value

      formData.append('supplier_id', supplier_id.value)
      formData.append('supplier_id', supplier_id.value)
      formData.append('client_type_id', client_type_id.value)
      formData.append('country_id', normalizedCountryId)
      formData.append('email', email.value)
      formData.append('fullname', fullname.value)
      formData.append('organization_number', organization_number.value)
      formData.append('address', address.value)
      formData.append('street', street.value)
      formData.append('postal_code', postal_code.value)
      formData.append('phone', phone.value)
      formData.append('reference', reference.value)
      formData.append('num_iva', num_iva.value)
      formData.append('comments', comments.value)

      emit(
        "clientData",
        { data: formData, id: id.value },
        isEdit.value ? "update" : "create"
      );

    }
  });
};

const handleDrawerModelValueUpdate = (val) => {
  if (val === false) {
    if (isDirty.value) {
      // keep drawer open and show confirm dialog
      emit("update:isDrawerOpen", true)
      isConfirmLeaveVisible.value = true
      return
    }
    reallyCloseAndReset()
    return
  }
  emit("update:isDrawerOpen", val)
};

watch(currentData, () => {
  if (!initialData.value) return
  emit('edited', isDirty.value)
}, { deep: true })

watch(client_type_id, async () => {
  await nextTick()
  refForm.value?.validate?.()
})

const getFlagCountry = country => {
  if (!country || !Array.isArray(props.countries)) return ''

  const normalizeText = value =>
    String(value ?? '')
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")

  let selectedCountry = null

  if (typeof country === 'object') {
    selectedCountry = props.countries.find(item => item.id === country.id)
  } else {
    selectedCountry = props.countries.find(item => String(item.id) === String(country))
      || props.countries.find(item => normalizeText(item.name) === normalizeText(country))
  }

  return selectedCountry?.iso
    ? `https://hatscripts.github.io/circle-flags/flags/${String(selectedCountry.iso).toLowerCase()}.svg`
    : ''
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="550"
    location="end"
    class="scrollable-content right-drawer rounded-left-4"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="title-modal font-blauer">
        {{ getTitle }}
      </h6>

      <VSpacer />

      <!-- 👉 Close btn -->
      <VBtn
        icon
        class="btn-white"
        @click="closeNavigationDrawer"
      >
        <VIcon size="32" icon="custom-cancel" />
      </VBtn>
    </div>

    <VDivider class="mt-4" />

    <PerfectScrollbar 
      :options="{ wheelPropagation: false }" 
      class="scrollbar-no-border">
      <VCard flat class="card-form">
        <VCardText>
          <!-- 👉 Form -->
          <VForm 
            ref="refForm" 
            v-model="isFormValid" 
            @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12" md="12" v-if="advisor.show">
                <VAlert
                  :color="advisor.type"
                  class="alert-no-shrink custom-alert"
                  style="flex: none;"
                >
                  <VAlertTitle>{{ advisor.message }}</VAlertTitle>
                </VAlert>
              </VCol>
              <VCol cols="12" md="12" class="py-0" v-if="role !== 'Supplier' && role !== 'User'">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Leverantörer" />
                <VSelect                 
                  v-model="supplier_id"
                  :items="suppliers"
                  :item-title="(item) => item.full_name"
                  :item-value="(item) => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"
                  :menu-props="{ maxHeight: '300px' }"
                />
              </VCol>               
              <VCol cols="12" md="6" class="pb-0">
                <AppAutocomplete
                  v-model="client_type_id"
                  label="Kunden är*"
                  :items="client_types"
                  :item-title="item => item.name"
                  :item-value="item => item.id"
                  :rules="[requiredValidator]"
                  autocomplete="off"
                />
              </VCol> 
              <VCol cols="12" md="6" class="pb-0">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Org/personnummer*" />
                <div class="d-flex gap-2">
                  <VTextField
                    v-if="client_type_id !== 3"
                    ref="organizationNumberFieldRef"
                    v-model="organization_number"
                    :class="{ 'org-number-duplicate': props.isDuplicate }"
                    :error="props.isDuplicate"
                    :rules="organizationNumberRules"
                    minLength="11"
                    maxlength="13"
                    @input="handleOrganizationNumberInput"
                  /> 
                  <VTextField
                    v-else
                    ref="organizationNumberFieldRef"
                    v-model="organization_number"
                    :class="{ 'org-number-duplicate': props.isDuplicate }"
                    :error="props.isDuplicate"
                    :rules="organizationNumberForeignRules"
                    @input="emit('resetDuplicate')"
                  />                
                  <VBtn
                    v-if="client_type_id !== 3"
                    class="btn-ghost w-auto px-3"
                    @click="searchEntity"
                  >
                    <VIcon icon="custom-search" size="24" />
                  </VBtn>
                </div>
              </VCol>
              <VCol cols="12" md="6" class="pb-0">
                <VLabel v-if="client_type_id === 2" class="mb-1 text-body-2 text-high-emphasis" text="Företagsnamn*" />
                <VLabel v-else class="mb-1 text-body-2 text-high-emphasis" text="Fullständigt namn*" />
                <VTextField
                  v-model="fullname"
                  :rules="[requiredValidator]"
                />
              </VCol>  
              <VCol cols="12" md="6" class="pb-0">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon*" />
                <VTextField
                  v-model="phone"
                  :rules="[requiredValidator, phoneValidator]"
                />
              </VCol> 
              <VCol cols="12" md="12" class="pb-0" v-if="client_type_id === 1">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />
                <VTextField
                  v-model="address"
                  :rules="[requiredValidator]"
                />
              </VCol>    
              <VCol cols="12" md="6" class="pb-0" v-if="client_type_id === 2">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Momsreg. nr." />
                <VTextField
                  v-model="num_iva"
                />
              </VCol>
              <VCol cols="12" md="6" class="pb-0" v-if="client_type_id === 2">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />
                <VTextField
                  v-model="address"
                  :rules="[requiredValidator]"
                />
              </VCol> 
              <VCol cols="12" md="6" class="pb-0" v-if="client_type_id === 3">
                <AppAutocomplete
                  v-model="country_id"
                  label="Land*"
                  :items="countries"
                  :item-title="item => item.name"
                  :item-value="item => item.id"
                  :rules="[requiredValidator]"
                  :menu-props="{ maxHeight: '200px' }"
                  autocomplete="off"
                >
                  <template
                    v-if="country_id"
                    #prepend
                    >
                    <VAvatar
                      start
                      style="margin-top: -3px;"
                      size="40"
                      :image="getFlagCountry(country_id)"
                    />
                  </template>
                </AppAutocomplete>
              </VCol>  
              <VCol cols="12" md="6" class="pb-0" v-if="client_type_id === 3">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />
                <VTextField
                  v-model="address"
                  :rules="[requiredValidator]"
                />
              </VCol> 
              <VCol cols="12" md="6" class="pb-0">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Postnummer*" />
                <VTextField
                  v-model="postal_code"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6" class="pb-0">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Stad*" />
                <VTextField
                  v-model="street"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6" class="pb-0">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
                <VTextField
                  v-model="email"
                  :rules="[emailValidator, requiredValidator]"
                />
              </VCol>               
              <VCol cols="12" md="6" class="pb-0">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vår referens" />
                <VTextField v-model="reference" />
              </VCol>
              <VCol cols="12" md="12">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Beskrivning" />
                <VTextarea 
                  v-model="comments"
                  rows="3"
                  />
              </VCol>            
              <!-- 👉 Submit and Cancel -->
              <VCol cols="12" class="pb-0">
                <VBtn
                  type="reset"
                  class="btn-light me-3"
                  @click="closeNavigationDrawer"
                >
                  Avbryt
                </VBtn>
                <VBtn type="submit" class="btn-gradient">
                  {{ isEdit ? "Uppdatering" : "Lägg till" }}
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>

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
</template>

<style lang="scss">
  .btn-close-client {
    height: 32px !important;
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

    .v-select .v-field {
      .v-select__selection {
          align-items: center;
          color: #454545;
      }

      .v-field__input > input {
        top: 0px;
        left: 18px;

      }

      .v-field__input input::placeholder,
      input.v-field__input::placeholder,
      .v-field__input textarea::placeholder,
      textarea.v-field__input::placeholder {
          color: #454545 !important;
          opacity: 1 !important;
        }
    }
  }

  .org-number-duplicate {
    &.v-input--error .v-input__control .v-field:not(.v-field--disabled) .v-field__outline,
    .v-field--error:not(.v-field--disabled) .v-field__outline {
      color: #FF4D4F !important;
      border-color: #FF4D4F !important;
    }

    &.v-input--error .v-input__control .v-field:not(.v-field--disabled) .v-field__outline__start,
    &.v-input--error .v-input__control .v-field:not(.v-field--disabled) .v-field__outline__notch,
    &.v-input--error .v-input__control .v-field:not(.v-field--disabled) .v-field__outline__end {
      border-color: #FF4D4F !important;
    }
  }
  .border-img {
    border: 1.8px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 6px;
  }
  .border-img .v-img__img--contain {
    padding: 10px;
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
</style>
