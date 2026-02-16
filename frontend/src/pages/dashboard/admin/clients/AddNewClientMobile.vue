<script setup>

import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import { emailValidator, requiredValidator, phoneValidator, minLengthDigitsValidator } from "@/@core/utils/validators";
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { usePersonInfoStores } from '@/stores/usePersonInfo'

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
  }
});

const emit = defineEmits([
  "update:isDrawerOpen",
  "clientData", 
  "edited",
  "alert",
  "loading"
]);

const companyInfoStores = useCompanyInfoStores()
const personInfoStores = usePersonInfoStores()

const isFormValid = ref(false);
const refForm = ref();

const id = ref(0);
const supplier_id = ref(null);
const client_type_id = ref(null)
const organization_number = ref("");
const address = ref("");
const street = ref("");
const postal_code = ref("");
const phone = ref("");
const fullname = ref("");
const email = ref("");
const reference = ref("");
const comments = ref("");
const isEdit = ref(false);
const userData = ref(null);
const role = ref(null);
const isConfirmLeaveVisible = ref(false);

const initialData = ref(null);
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
  comments: comments.value,
  client_type_id: client_type_id.value,
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

watchEffect(async () => {
  if (props.isDrawerOpen) {
    userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
    role.value = userData.value.roles[0].name;

    if (
      !(Object.entries(props.client).length === 0) &&
      props.client.constructor === Object
    ) {
      isEdit.value = true;
      id.value = props.client.id;
      supplier_id.value = props.client.supplier_id;
      organization_number.value = props.client.organization_number;
      address.value = props.client.address;
      street.value = props.client.street;
      postal_code.value = props.client.postal_code;
      phone.value = props.client.phone;
      fullname.value = props.client.fullname;
      email.value = props.client.email;
      reference.value = props.client.reference;
      comments.value = props.client.comments;      
      client_type_id.value = props.client.client_type_id; 
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

    organization_number.value = null;
    address.value = null;
    street.value = null;
    postal_code.value = null;
    phone.value = null;
    fullname.value = null;
    email.value = null;
    reference.value = null;
    comments.value = null;
    client_type_id.value = null;

    isEdit.value = false;
    id.value = 0;
    initialData.value = null
    emit('edited', false)
  });
}

const closeNavigationDrawer = () => {
  if (isDirty.value) {
    isConfirmLeaveVisible.value = true
    return
  }
  reallyCloseAndReset()
};

const formatOrgNumber = () => {
  let numbers = organization_number.value.replace(/\D/g, "");
  if (numbers.length > 4) {
    numbers = numbers.slice(0, -4) + "-" + numbers.slice(-4);
  }
  organization_number.value = numbers;
};

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

      formData.append("supplier_id", supplier_id.value);
      formData.append("supplier_id", supplier_id.value);
      formData.append('client_type_id', client_type_id.value);
      formData.append("email", email.value);
      formData.append("fullname", fullname.value);
      formData.append("organization_number", organization_number.value);
      formData.append("address", address.value);
      formData.append("street", street.value);
      formData.append("postal_code", postal_code.value);
      formData.append("phone", phone.value);
      formData.append("reference", reference.value);
      formData.append("comments", comments.value);

      emit(
        "clientData",
        { data: formData, id: id.value },
        isEdit.value ? "update" : "create"
      );

      // After successful submit, close without confirmation
      reallyCloseAndReset();
    }
  });
};

watch(currentData, () => {
  if (!initialData.value) return
  emit('edited', isDirty.value)
}, { deep: true })
</script>

<template>
  <!-- 👉 Form -->
  <VForm
    class="card-form"
    ref="refForm"
    v-model="isFormValid"
    @submit.prevent="onSubmit"
  >
    <VList>
      <VListItem v-if="advisor.show" class="m-4 p-5">
        <VAlert
          :color="advisor.type"
          class="alert-no-shrink custom-alert"
          style="flex: none; margin: 2px 2px 2px 1px"
        >
          <VAlertTitle>{{ advisor.message }}</VAlertTitle>
        </VAlert>
      </VListItem>
      <VListItem v-if="role !== 'Supplier' && role !== 'User'" class="pb-2">
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
      </VListItem>
      <VListItem>
        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Org/personnummer*" />        
        <div class="d-flex gap-2">
          <VTextField
            v-model="organization_number"
            :rules="[requiredValidator, minLengthDigitsValidator(10)]"
            minLength="11"
            maxlength="13"
            @input="formatOrgNumber()"
          />
          <VBtn
            class="btn-ghost w-auto px-3"
            @click="searchEntity"
          >
            <VIcon icon="custom-search" size="24" />
          </VBtn>
        </div>
      </VListItem>
      <VListItem>
        <AppAutocomplete
          v-model="client_type_id"
          label="Kunden är*"
          :items="client_types"
          :item-title="item => item.name"
          :item-value="item => item.id"
          :rules="[requiredValidator]"
          autocomplete="off"/>
      </VListItem>
      <VListItem>
        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Fullständigt namn*" />        
        <VTextField
          v-model="fullname"
          :rules="[requiredValidator]"
        />
      </VListItem>
      <VListItem>
        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />        
        <VTextField
          v-model="address"
          :rules="[requiredValidator]"
        />
      </VListItem>
      <VListItem>
        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Postnummer*" />        
        <VTextField
          v-model="postal_code"
          :rules="[requiredValidator]"
        />
      </VListItem>
      <VListItem>
        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Stad*" />        
        <VTextField
          v-model="street"
          :rules="[requiredValidator]"
        />
      </VListItem>
      <VListItem>
        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon*" />        
        <VTextField
          v-model="phone"
          :rules="[requiredValidator, phoneValidator]"
        />
      </VListItem>
      <VListItem>
        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />        
        <VTextField
          v-model="email"
          :rules="[emailValidator, requiredValidator]"
        />
      </VListItem>      
      <VListItem>
        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vår referens" />        
        <VTextField v-model="reference" />
      </VListItem>
      <VListItem>
        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Beskrivning" />        
        <VTextarea v-model="comments" />
      </VListItem>
    </VList>
    <div class="pb-6 px-6 d-flex gap-4 form-actions">
      <!-- 👉 Submit and Cancel -->
      <VBtn
        class="btn-light" 
        type="reset"
        @click="closeNavigationDrawer"
      >
        Avbryt
      </VBtn>
      <VBtn
        class="btn-gradient"
        type="submit"
      >
        {{ isEdit ? "Uppdatering" : "Lägg till" }}
      </VBtn>
    </div>
  </VForm>

  <!-- Confirm leave without saving (mobile) -->
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

<style scoped>
.border-img {
  border: 1.8px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
}
.border-img .v-img__img--contain {
  padding: 10px;
}

.v-btn {
  width: 100%;
}

.form-actions {
  .v-btn {
    flex: 1;
  }
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
        background-color: #f6f6f6;
        .v-field-label {
          top: 12px !important;
        }
      }
    }
  }
}

.v-dialog .v-overlay__content {
  max-height: calc(100dvh - 48px) !important;
}
</style>
