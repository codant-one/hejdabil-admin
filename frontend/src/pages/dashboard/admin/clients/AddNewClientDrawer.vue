<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { urlValidator, emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'

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
const company = ref('')
const organization_number = ref('')
const link = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const fullname = ref('')
const email = ref('')
const reference = ref('')
const bank = ref('')
const iban = ref('')
const compensation_number = ref('')
const account_number = ref('')
const iban_number = ref('')
const bic = ref('')
const bank_transfer = ref('')
const plus_spin = ref('')
const whistle = ref('')
const registration_fee = ref('')
const insurance_company = ref('')
const financial_company = ref('')
const interest = ref('')
const avi_fee = ref('')
const installation_fee = ref('')
const isEdit = ref(false)
const userData = ref(null)
const role = ref(null)

const getTitle = computed(() => {
  return isEdit.value ? 'Update Client': 'Add Client'
})

watchEffect(async() => {
  if (props.isDrawerOpen) {

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value.roles[0].name

    if (!(Object.entries(props.client).length === 0) && props.client.constructor === Object) {

      isEdit.value = true
      id.value = props.client.id
      supplier_id.value = props.client.supplier_id
      company.value = props.client.company
      organization_number.value = props.client.organization_number
      link.value = props.client.link
      address.value = props.client.address
      street.value = props.client.street
      postal_code.value = props.client.postal_code
      phone.value = props.client.phone
      bank.value = props.client.bank
      account_number.value = props.client.account_number
      fullname.value = props.client.fullname 
      email.value = props.client.email
      reference.value = props.client.reference
      iban.value = props.client.iban
      compensation_number.value = props.client.compensation_number
      iban_number.value = props.client.iban_number
      bic.value = props.client.bic
      bank_transfer.value = props.client.bank_transfer
      plus_spin.value = props.client.plus_spin
      whistle.value = props.client.whistle
      registration_fee.value = props.client.registration_fee
      insurance_company.value = props.client.insurance_company
      financial_company.value = props.client.financial_company
      interest.value = props.client.interest
      avi_fee.value = props.client.avi_fee
      installation_fee.value = props.client.installation_fee
    }
  }
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    company.value = null
    organization_number.value = null
    link.value = null
    address.value = null
    street.value = null
    postal_code.value = null
    phone.value = null
    bank.value = null
    account_number.value = null
    fullname.value = null
    email.value = null
    reference.value = null
    iban.value = null
    compensation_number.value = null
    iban_number.value = null
    bic.value = null
    bank_transfer.value = null
    plus_spin.value = null
    whistle.value = null
    registration_fee.value = null
    insurance_company.value = null
    financial_company.value = null
    interest.value = null
    avi_fee.value = null
    installation_fee.value = null
    
    isEdit.value = false 
    id.value = 0
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

      if (!isEdit.value)
        formData.append('supplier_id', supplier_id.value)
        formData.append('email', email.value)

        if (isEdit.value)
        formData.append('supplier_id', supplier_id.value)

        formData.append('fullname', fullname.value)
        formData.append('company', company.value)
        formData.append('organization_number', organization_number.value)
        formData.append('address', address.value)
        formData.append('street', street.value)
        formData.append('postal_code', postal_code.value)
        formData.append('phone', phone.value)
        formData.append('link', link.value)
        formData.append('reference', reference.value)
        formData.append('bank', bank.value)
        formData.append('iban', iban.value)
        formData.append('compensation_number', compensation_number.value)
        formData.append('account_number', account_number.value)       
        formData.append('iban_number', iban_number.value)
        formData.append('bic', bic.value)
        formData.append('bank_transfer', bank_transfer.value)
        formData.append('plus_spin', plus_spin.value)
        formData.append('whistle', whistle.value)
        formData.append('registration_fee', registration_fee.value)
        formData.append('insurance_company', insurance_company.value)
        formData.append('financial_company', financial_company.value)
        formData.append('interest', interest.value)
        formData.append('avi_fee', avi_fee.value)
        formData.append('installation_fee', installation_fee.value)

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
            <VCol cols="12" md="12">
              <VSelect
                v-if="role !== 'Supplier'"
                v-model="supplier_id"
                placeholder="Suppliers"
                :items="suppliers"
                :item-title="item => item.full_name"
                :item-value="item => item.id"
                autocomplete="off"
                clearable
                clear-icon="tabler-x"
                :menu-props="{ maxHeight: '300px' }"/>
            </VCol>
            <VCol cols="12" md="12">
                <VTextField
                    v-model="company"
                    label="Company name"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="fullname"
                    label="Fullname"
                    :rules="[requiredValidator]"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="email"
                    :rules="[emailValidator, requiredValidator]"
                    label="E-mail"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="organization_number"
                    label="Organization number"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="address"
                    :rules="[requiredValidator]"
                    label="Address"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="street"
                    :rules="[requiredValidator]"
                    label="Street"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="postal_code"
                    :rules="[requiredValidator]"
                    label="Postal code"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="phone"
                    :rules="[requiredValidator, phoneValidator]"
                    label="Phone"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="link"
                    :rules="[urlValidator]"
                    label="Page"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="reference"
                    label="Reference"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="bank"
                    :rules="[requiredValidator]"
                    label="Bank"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="iban"
                    label="IBAN"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="compensation_number"
                    label="Compensation number"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="account_number"
                    :rules="[requiredValidator]"
                    label="Account number"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="iban_number"
                    label="Iban number"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="bic"
                    label="BIC"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="bank_transfer"
                    label="Bank transfer"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="plus_spin"
                    label="Plus spin"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="whistle"
                    label="Whistle"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="registration_fee"
                    label="Registration fee"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="insurance_company"
                    label="Insurance company"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="financial_company"
                    label="Financial company"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="interest"
                    label="Interest"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="avi_fee"
                    label="Avi fee"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="installation_fee"
                    label="Installation fee"
                />
            </VCol>
              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  {{ isEdit ? 'Update': 'Add' }}
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="secondary"
                  @click="closeNavigationDrawer"
                >
                  Cancel
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
