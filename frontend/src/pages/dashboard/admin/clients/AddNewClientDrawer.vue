<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { emailValidator, requiredValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  client: {
    type: Object,
    required: false
  },
  suppliers: {
    type: Object,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'clientData',
])

const isFormValid = ref(false)
const refForm = ref()

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
const isEdit = ref(false)
const userData = ref(null)
const role = ref(null)

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera klient': 'Lägg till kund'
})

watchEffect(async() => {
  if (props.isDrawerOpen) {

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
    }
  }
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    organization_number.value = null
    address.value = null
    street.value = null
    postal_code.value = null
    phone.value = null
    fullname.value = null
    email.value = null
    reference.value = null
    
    isEdit.value = false 
    id.value = 0
  })
}

const formatOrgNumber = () => {

  let numbers = organization_number.value.replace(/\D/g, '')
  if (numbers.length > 4) {
    numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
  }
  organization_number.value = numbers
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

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

      emit('clientData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

</script>

<template>
  <VNavigationDrawer
    temporary
    :width="550"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ getTitle }}
      </h6>

      <VSpacer />

      <!-- 👉 Close btn -->
      <VBtn
        variant="tonal"
        color="default"
        icon
        size="32"
        class="rounded btn-close-client"
        @click="closeNavigationDrawer"
      >
        <VIcon
          size="18"
          icon="tabler-x"
        />
      </VBTn>
    </div>
    
    <VDivider class="mt-4"/>

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat class="card-client">
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
          <VRow>
            <VCol cols="12" md="12" v-if="role === 'SuperAdmin' || role === 'Administrator'">
              <VSelect                
                v-model="supplier_id"
                placeholder="Leverantörer"
                :items="suppliers"
                :item-title="item => item.full_name"
                :item-value="item => item.id"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"
                :menu-props="{ maxHeight: '300px' }"/>
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
                <VTextField
                    v-model="reference"
                    label="Vår referens"
                />
            </VCol>
              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  {{ isEdit ? 'Uppdatering': 'Lägg till' }}
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="secondary"
                  @click="closeNavigationDrawer"
                >
                  Avbryt
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
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
</style>
