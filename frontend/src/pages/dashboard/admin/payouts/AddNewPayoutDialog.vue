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

const currentStep = ref(1)
const isFormValid = ref(false)
const refForm = ref()
const refFormStep1 = ref()

const payer_alias = ref(props.payer_alias)
const payee_alias = ref(null)
const message = ref(null)
const amount = ref(null)
const payee_ssn = ref(null)
const master_password = ref(null)
const isMasterPasswordVisible = ref(false)

const getTitle = computed(() => 'Ny betalning från ' + payer_alias.value)

watchEffect(() => {
  if (props.isDialogOpen) {
    currentStep.value = 1
    payee_alias.value = null
    amount.value = null
    payee_ssn.value = null
    message.value = null
    master_password.value = null
  }
})

const closeDialog = () => {
  emit('update:isDialogOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    currentStep.value = 1
    payee_alias.value = null
    amount.value = null
    payee_ssn.value = null
    message.value = null
    master_password.value = null
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

const nextStep = async () => {
  // Validar solo el formulario del paso 1
  const { valid } = await refFormStep1.value.validate()
  
  if (valid) {
    currentStep.value = 2
  }
}

const prevStep = () => {
  currentStep.value = 1
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const payload = {
        payee_alias: payee_alias.value,
        amount: amount.value,
        payee_ssn: payee_ssn.value,
        message: message.value,
        master_password: master_password.value
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
    max-width="600"
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
        <!-- Stepper Header -->
        <div class="stepper-header mb-6">
          <div class="d-flex align-center justify-space-between">
            <div class="d-flex flex-column align-center" style="flex: 1;">
              <div 
                class="stepper-circle"
                :class="{ 'active': currentStep === 1, 'completed': currentStep > 1 }"
              >
                <VIcon v-if="currentStep > 1" icon="tabler-check" size="20" />
                <span v-else>1</span>
              </div>
              <span class="text-sm mt-2">Betalningsinfo</span>
            </div>
            
            <div class="stepper-line" :class="{ 'active': currentStep > 1 }"></div>
            
            <div class="d-flex flex-column align-center" style="flex: 1;">
              <div 
                class="stepper-circle"
                :class="{ 'active': currentStep === 2 }"
              >
                2
              </div>
              <span class="text-sm mt-2">Bekräftelse</span>
            </div>
          </div>
        </div>

        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <!-- Step 1: Payment Information -->
          <VWindow v-model="currentStep">
            <VWindowItem :value="1">
              <VForm ref="refFormStep1">
                <VRow>
                  <VCol cols="12" class="mt-2">
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
                </VRow>
              </VForm>
            </VWindowItem>

            <!-- Step 2: Security Password -->
            <VWindowItem :value="2">
              <VRow>
                <VCol cols="12">
                  <VAlert
                    variant="tonal"
                    color="warning"
                    class="mb-4"
                  >
                    <VAlertTitle class="mb-2">Bekräfta betalning</VAlertTitle>
                    <div class="text-sm">
                      <p class="mb-2"><strong>Mobilnummer:</strong> +{{ payee_alias }}</p>
                      <p class="mb-2"><strong>Personnummer:</strong> {{ payee_ssn }}</p>
                      <p class="mb-2"><strong>Belopp:</strong> {{ amount }} SEK</p>
                      <p v-if="message" class="mb-0"><strong>Meddelande:</strong> {{ message }}</p>
                    </div>
                  </VAlert>
                </VCol>

                <VCol cols="12">
                  <VTextField
                    v-model="master_password"
                    label="Säkerhetslösenord"
                    :type="isMasterPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isMasterPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                    :rules="[requiredValidator]"
                    placeholder="Ange ditt säkerhetslösenord för att bekräfta"
                    @click:append-inner="isMasterPasswordVisible = !isMasterPasswordVisible"
                  />
                </VCol>
              </VRow>
            </VWindowItem>
          </VWindow>

          <!-- Buttons -->
          <VCol cols="12" class="d-flex justify-space-between gap-3 px-0 mt-4">
            <VBtn
              v-if="currentStep === 2"
              color="secondary"
              variant="tonal"
              @click="prevStep"
            >
              <VIcon icon="tabler-arrow-left" class="me-2" />
              Tillbaka
            </VBtn>
            <VSpacer v-else />
            
            <div class="d-flex gap-3">
              <VBtn
                color="secondary"
                variant="tonal"
                @click="closeDialog"
              >
                Avbryt
              </VBtn>
              <VBtn
                v-if="currentStep === 1"
                @click="nextStep"
              >
                Nästa
                <VIcon icon="tabler-arrow-right" class="ms-2" />
              </VBtn>
              <VBtn
                v-else
                type="submit"
              >
                Bekräfta betalning
              </VBtn>
            </div>
          </VCol>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.stepper-header {
  padding: 0 2rem;
}

.stepper-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: rgb(var(--v-theme-surface-variant));
  color: rgb(var(--v-theme-on-surface-variant));
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  transition: all 0.3s ease;
}

.stepper-circle.active {
  background-color: rgb(var(--v-theme-primary));
  color: white;
}

.stepper-circle.completed {
  background-color: rgb(var(--v-theme-success));
  color: white;
}

.stepper-line {
  height: 2px;
  flex: 1;
  background-color: rgb(var(--v-theme-surface-variant));
  margin: 0 1rem;
  transition: all 0.3s ease;
}

.stepper-line.active {
  background-color: rgb(var(--v-theme-success));
}
</style>


