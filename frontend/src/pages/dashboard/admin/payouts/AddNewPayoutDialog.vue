<script setup>

import { requiredValidator } from '@/@core/utils/validators'

const props = defineProps({
  isDialogOpen: {
    type: Boolean,
    required: true,
  },
  payout: {
    type: Object,
    required: false,
    default: () => ({}),
  },
})

const emit = defineEmits([
  'update:isDialogOpen',
  'payoutData',
])

const isFormValid = ref(false)
const refForm = ref()

const payer_alias = ref('')
const amount = ref(null)
const payee_ssn = ref('')

const ssnValidator = value => {
  if (!value) return true
  const ok = /^\d{12}$/.test(value)
  return ok || 'Ange 12 siffror: YYYYMMDDXXXX'
}

const getTitle = computed(() => 'Ny betalning')

watchEffect(() => {
  if (props.isDialogOpen) {
    // Si en el futuro quieres soportar edición, aquí podrías precargar datos desde props.payout
    payer_alias.value = ''
    amount.value = null
    payee_ssn.value = ''
  }
})

const closeDialog = () => {
  emit('update:isDialogOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    payer_alias.value = ''
    amount.value = null
    payee_ssn.value = ''
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const payload = {
        payer_alias: payer_alias.value,
        amount: amount.value,
        payee_ssn: payee_ssn.value || undefined,
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

      <VDivider class="mt-2" />

      <VCardText>
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="payer_alias"
                label="Swish-nummer (mottagare)"
                :rules="[requiredValidator]"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="payee_ssn"
                label="Personnummer (YYYYMMDDXXXX)"
                :rules="[ssnValidator]"
                hint="Valfritt – krävs ibland i sandbox"
                persistent-hint
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


