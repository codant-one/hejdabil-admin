<script setup>
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import {
  emailValidator,
  requiredValidator,
  phoneValidator,
  minLengthDigitsValidator,
} from "@/@core/utils/validators";

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
});

const emit = defineEmits(["update:isDrawerOpen", "clientData", "edited"]);

const isFormValid = ref(false);
const refForm = ref();

const id = ref(0);
const supplier_id = ref(null);
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
}))
const isDirty = computed(() => {
  if (!initialData.value) return false
  try {
    return JSON.stringify(currentData.value) !== JSON.stringify(initialData.value)
  } catch (e) {
    return true
  }
})

const getTitle = computed(() => {
  return isEdit.value ? "Uppdatera klient" : "Lägg till kund";
});

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

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData();

      formData.append("supplier_id", supplier_id.value);
      formData.append("supplier_id", supplier_id.value);
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
    class="card-client"
    ref="refForm"
    v-model="isFormValid"
    @submit.prevent="onSubmit"
  >
    <VList>
      <VListItem v-if="role !== 'Supplier' && role !== 'User'">
        <VSelect
          v-model="supplier_id"
          placeholder="Leverantörer"
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
        <VTextField
          v-model="fullname"
          label="Fullständigt namn*"
          :rules="[requiredValidator]"
        />
      </VListItem>
      <VListItem>
        <VTextField
          v-model="email"
          :rules="[emailValidator, requiredValidator]"
          label="E-post*"
        />
      </VListItem>
      <VListItem>
        <VTextField
          v-model="organization_number"
          label="Org/personummer*"
          :rules="[requiredValidator, minLengthDigitsValidator(10)]"
          minLength="11"
          maxlength="13"
          @input="formatOrgNumber()"
        />
      </VListItem>
      <VListItem>
        <VTextField
          v-model="address"
          :rules="[requiredValidator]"
          label="Adress*"
        />
      </VListItem>
      <VListItem>
        <VTextField
          v-model="postal_code"
          :rules="[requiredValidator]"
          label="Postnummer*"
        />
      </VListItem>
      <VListItem>
        <VTextField
          v-model="street"
          :rules="[requiredValidator]"
          label="Stad*"
        />
      </VListItem>
      <VListItem>
        <VTextField
          v-model="phone"
          :rules="[requiredValidator, phoneValidator]"
          label="Telefon*"
        />
      </VListItem>
      <VListItem>
        <VTextField v-model="reference" label="Vår referens" />
      </VListItem>
      <VListItem>
        <VTextarea v-model="comments" label="Beskrivning" />
      </VListItem>
    </VList>
    <VRow class="px-9">
      <!-- 👉 Submit and Cancel -->
      <VCol cols="6">
        <VBtn
          type="reset"
          block
          class="btn-light me-3"
          @click="closeNavigationDrawer"
        >
          Avbryt
        </VBtn>
      </VCol>
      <VCol cols="6">
        <VBtn
          type="submit"
          class="btn-gradient"
        >
          {{ isEdit ? "Uppdatering" : "Lägg till" }}
        </VBtn>
      </VCol>
    </VRow>
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
.card-client {
  border-radius: 0 !important;
}
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
</style>

<style lang="scss">
.card-client {
  .v-list {
    padding: 28px 24px 40px !important;

    .v-list-item {
      margin-bottom: 0px;
      padding: 0px !important;
      gap: 0px !important;

      .v-input--density-compact {
        --v-input-control-height: 48px !important;
      }

      .v-select .v-field {
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
          padding-top: 16px;
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
          @media (max-width: 991px) {
            top: 12px !important;
          }
        }
      }
    }
  }
}

.v-dialog .v-overlay__content {
  max-height: calc(100dvh - 48px) !important;
}
</style>
