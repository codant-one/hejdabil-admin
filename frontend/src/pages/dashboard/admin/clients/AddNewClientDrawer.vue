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

const id = ref(0)
const supplier_id = ref(null)
const organization_number = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const fullname = ref('')
const email = ref('')
const reference = ref('')
const comments = ref('')
const isEdit = ref(false)
const userData = ref(null)
const role = ref(null)
const isRequestOngoing = ref(false)
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
      comments.value = props.client.comments
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
    comments.value = null

    isEdit.value = false 
    id.value = 0
    initialData.value = null
    emit('edited', false)
  })
}

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

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData();

      formData.append('supplier_id', supplier_id.value)
      formData.append('supplier_id', supplier_id.value)
      formData.append('email', email.value)
      formData.append('fullname', fullname.value)
      formData.append('organization_number', organization_number.value)
      formData.append('address', address.value)
      formData.append('street', street.value)
      formData.append('postal_code', postal_code.value)
      formData.append('phone', phone.value)
      formData.append('reference', reference.value)
      formData.append('comments', comments.value)

      emit(
        "clientData",
        { data: formData, id: id.value },
        isEdit.value ? "update" : "create"
      );
      isRequestOngoing.value = true
      setTimeout(() => {
        isRequestOngoing.value = false
        // After successful submit, close without confirmation
        reallyCloseAndReset();
      }, 1000)
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

    <PerfectScrollbar :options="{ wheelPropagation: false }" class="scrollbar-no-border">
      <VCard flat class="card-client">
        <VCardText>
          <!-- 👉 Form -->
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12" md="12" v-if="role !== 'Supplier' && role !== 'User'">
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
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="fullname"
                  label="Fullständigt namn"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="email"
                  :rules="[emailValidator, requiredValidator]"
                  label="E-post"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="organization_number"
                  label="Org/personummer"
                  :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                  minLength="11"
                  maxlength="13"
                  @input="formatOrgNumber()"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="address"
                  :rules="[requiredValidator]"
                  label="Adress"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="postal_code"
                  :rules="[requiredValidator]"
                  label="Postnummer"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="street"
                  :rules="[requiredValidator]"
                  label="Stad"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="phone"
                  :rules="[requiredValidator, phoneValidator]"
                  label="Telefon"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField v-model="reference" label="Vår referens" />
              </VCol>
              <VCol cols="12" md="12">
                <VTextarea 
                  v-model="comments"
                  label="Beskrivning"
                />
              </VCol>
            
              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn
                  type="reset"
                  class="btn-light me-3"
                  @click="closeNavigationDrawer"
                >
                  Avbryt
                </VBtn>
                <VBtn type="submit" class="btn-gradient">
                  {{ isEdit ? "Uppdatering" : "Lägg till" }}
                  <VProgressCircular
                    v-if="isRequestOngoing"
                    indeterminate
                    color="#fff"
                  />
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

<style scoped>
.btn-close-client {
  height: 32px !important;
}
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
.right-drawer {
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
