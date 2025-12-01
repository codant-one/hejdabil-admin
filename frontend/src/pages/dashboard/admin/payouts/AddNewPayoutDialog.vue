<script setup>

import { requiredValidator, minLengthDigitsValidator } from '@/@core/utils/validators'

const props = defineProps({
  isDialogOpen: {
    type: Boolean,
    required: true,
  },
  payer_alias: {
    type: String,
    required: true,
    default: '',
  },
})

const emit = defineEmits([
  'update:isDialogOpen',
  'payoutData',
])

const isFormValid = ref(false)
const refForm = ref()

const payer_alias = ref(props.payer_alias)
const payee_alias = ref(null)
const message = ref(null)
const amount = ref(null)
const payee_ssn = ref(null)

const getTitle = computed(() => 'Ny betalning från ' + payer_alias.value)

watchEffect(() => {
  if (props.isDialogOpen) {
    payee_alias.value = null
    amount.value = null
    payee_ssn.value = null
    message.value = null
  }
})

const closeDialog = () => {
  emit('update:isDialogOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    payee_alias.value = null
    amount.value = null
    payee_ssn.value = null
    message.value = null
  })
}

const formatNumber = (value) => {
  
  if  (value === 'payee_alias') {
    let numbers = payee_alias.value.replace(/\D/g, '')
    payee_alias.value = numbers
    return
  }

  let numbers = payee_ssn.value.replace(/\D/g, '')
  payee_ssn.value = numbers
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const payload = {
        payee_alias: payee_alias.value,
        amount: amount.value,
        payee_ssn: payee_ssn.value,
        message: message.value
      }

      emit('payoutData', { data: payload })
      closeDialog()
    }
  })
}

</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="500"
    persistent
    @update:model-value="val => emit('update:isDialogOpen', val)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ getTitle }}</span>
        <VBtn
          icon
          variant="text"
          size="32"
          @click="closeDialog"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="payee_alias"
                label="Mobilnummer"
                :rules="[requiredValidator, minLengthDigitsValidator(11)]"
                minLength="11"
                maxlength="11"
                @input="formatNumber('payee_alias')"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="payee_ssn"
                label="Personnummer"
                :rules="[requiredValidator, minLengthDigitsValidator(12)]"
                minLength="12"
                maxlength="12"
                @input="formatNumber('payee_ssn')"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="amount"
                type="number"
                label="Belopp (SEK)"
                :rules="[requiredValidator]"
                min="1"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="message"
                label="Meddelande (valfritt)"
                rows="3"
              />
            </VCol>

            <VCol cols="12" class="d-flex justify-end gap-3">
              <VBtn
                color="secondary"
                variant="tonal"
                @click="closeDialog"
              >
                Avbryt
              </VBtn>
              <VBtn
                type="submit"
              >
                Spara
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>


