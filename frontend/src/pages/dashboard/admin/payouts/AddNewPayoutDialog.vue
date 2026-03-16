<script setup>

import { usePersonInfoStores } from '@/stores/usePersonInfo'
import { requiredValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { formatNumber } from '@/@core/utils/formatters'

const props = defineProps({
  isDialogOpen: {
    type: Boolean,
    required: true,
  },
  payoutData: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits([
  'update:isDialogOpen',
  'payoutData',
  'showError',
  'showLoading'
])

const currentStep = ref(1)
const isFormValid = ref(false)
const refForm = ref()
const refFormStep1 = ref()
const shouldShowStep1Validation = ref(false)
const shouldShowStep2Validation = ref(false)

const payee_alias = ref(null)
const message = ref(null)
const amount = ref(null)
const payee_ssn = ref(null)
const master_password = ref(null)
const isMasterPasswordVisible = ref(false)

const fullname = ref(null)

const { width: windowWidth } = useWindowSize();

const personInfoStores = usePersonInfoStores()

const normalizePayeeAliasForInput = value => {
  const digits = String(value ?? '').replace(/\D/g, '')

  if (digits.length === 11 && digits.startsWith('46'))
    return digits.slice(2)

  return digits
}

const formatPayeeAliasForPayload = value => {
  const localNumber = normalizePayeeAliasForInput(value)

  return localNumber ? `46${localNumber}` : null
}

const getTitle = computed(() => {
  return props.payoutData && Object.keys(props.payoutData).length > 0 
    ? 'Bekräfta betalning' 
    : 'Ny betalning'
})

watchEffect(() => {
  if (props.isDialogOpen) {
    currentStep.value = 1
    
    // Pre-fill form if payoutData exists
    if (props.payoutData && Object.keys(props.payoutData).length > 0) {
      payee_alias.value = normalizePayeeAliasForInput(props.payoutData.payee_alias) || null
      amount.value = props.payoutData.amount || null
      payee_ssn.value = props.payoutData.payee_ssn || null
      message.value = props.payoutData.message || null
      fullname.value = props.payoutData.fullname || null
    } else {
      payee_alias.value = null
      amount.value = null
      payee_ssn.value = null
      message.value = null
      fullname.value = null
    }
    
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
    shouldShowStep1Validation.value = false
    shouldShowStep2Validation.value = false
  })
}

const format_number = (value) => {
  
  if  (value === 'payee_alias') {
    let numbers = payee_alias.value.replace(/\D/g, '')
    payee_alias.value = numbers
    return
  }

  let numbers = payee_ssn.value.replace(/\D/g, '')
  payee_ssn.value = numbers
}

const nextStep = async () => {
  shouldShowStep1Validation.value = true

  const { valid } = await refFormStep1.value.validate()
  
  if (valid) {
    if(fullname.value === null) {
      await searchPerson()
    }
    currentStep.value = 2
  }
}

const prevStep = () => {
  currentStep.value = 1
}

/**
 * Search for person information in SPAR (Statens Personadressregister) API.
 */
const searchPerson = async () => {
    try {
        emit('showLoading', true)

        const response = await personInfoStores.getPersonInfo(payee_ssn.value)
        
        emit('showLoading', false)
        if (response?.success && response?.data) {
            const personData = response.data

            fullname.value = personData.fullname || ''
        }

    } catch (error) {
        const errorMessage = error?.response?.data?.message || 'Ingen person hittades med det personnumret'
        emit('showLoading', false)
        emit('showError', errorMessage)
    }
}

const onSubmit = async () => {
  shouldShowStep1Validation.value = true
  shouldShowStep2Validation.value = true

  const { valid } = await refForm.value.validate()

  if (valid) {
    const payload = {
      payee_alias: formatPayeeAliasForPayload(payee_alias.value),
      amount: amount.value,
      payee_ssn: payee_ssn.value,
      message: message.value,
      master_password: master_password.value,
      fullname: fullname.value
    }

    emit('payoutData', { data: payload })
    closeDialog()
  }
}

</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    persistent
    scrollable
    class="action-dialog"
    content-class="scrollable-dialog-content"
    @update:model-value="val => emit('update:isDialogOpen', val)"
  >

    <VBtn
      icon
      class="btn-ghost close-btn me-3"
      @click="closeDialog"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <VForm
      ref="refForm"
      v-model="isFormValid"
      @submit.prevent="onSubmit"
    >
      <VCard>
        <VCardText 
          class="dialog-title-box flex-row"
          :class="[
            windowWidth < 1024 && (props.payoutData && Object.keys(props.payoutData).length > 0) ? 'gap-modal' : ''
          ]">
          <VIcon size="32" icon="custom-surface" class="action-icon" />
          <div class="dialog-title">
             {{ getTitle }}
          </div>
        </VCardText>
        <VCardText class="dialog-text pe-0">
          Överför pengar till dina kunder via Swish
        </VCardText>

        <VCardText class="dialog-text mt-2">        
          <!-- Stepper Header -->
          <VTabs 
            v-model="currentStep" 
            grow
            disabled
            :show-arrows="false"
            class="payouts-tabs"
          >
            <VTab :value="1">
                <VIcon size="24" icon="custom-cash" />
                <span>Betalningsinfo</span>
            </VTab>
            <VTab :value="2">
                <VIcon size="24" icon="custom-check-mark" />
                <span>Bekräftelse</span>
            </VTab>
          </VTabs>
        
          <VWindow v-model="currentStep">
            <!-- Step 1: Payment Information -->
            <VWindowItem :value="1">
              <VForm ref="refFormStep1" class="card-form">
                <div class="d-flex flex-column gap-4">
                  <div class="mt-4">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mobilnummer*" />
                    <VTextField
                      v-model="payee_alias"
                      class="always-show-prefix"
                      :rules="shouldShowStep1Validation ? [requiredValidator, minLengthDigitsValidator(9)] : []"
                      minLength="9"
                      maxlength="9"
                      prefix="+46"
                      @input="format_number('payee_alias')"
                    />
                  </div>
                  <div>
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Personnummer*" />
                    <VTextField
                      v-model="payee_ssn"
                      placeholder="Personnummer"
                      :rules="shouldShowStep1Validation ? [requiredValidator, minLengthDigitsValidator(12)] : []"
                      minLength="12"
                      maxlength="12"
                      @input="format_number('payee_ssn')"
                    />
                  </div>
                  <div>
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Belopp*" />
                    <VTextField
                      v-model="amount"
                      type="number"
                      suffix="KR"
                      placeholder="Belopp (kr)"
                      :rules="shouldShowStep1Validation ? [requiredValidator] : []"
                      min="1"
                    />
                  </div>
                  <div>
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Meddelande" />
                    <VTextarea
                      v-model="message"
                      placeholder="Meddelande (valfritt)"
                      persistent-placeholder
                      rows="3"
                    />
                  </div>
                </div>
              </VForm>
            </VWindowItem>
            <!-- Step 2: Security Password -->
            <VWindowItem :value="2">
              <div class="mt-4 card-form d-flex flex-column gap-4">
                <div class="bg-alert">
                  <span class="mb-2 d-flex justify-between text-neutral-3">
                    Namn: <strong class="text-black">{{ fullname }}</strong>
                  </span>
                  <VDivider />
                  <span class="mb-2 d-flex justify-between mt-2 text-neutral-3">
                    Mobilnummer: <strong class="text-black">+{{ payee_alias }}</strong>
                  </span>
                  <VDivider />
                  <span class="mb-2 d-flex justify-between mt-2 text-neutral-3">
                    Personnummer: <strong class="text-black">{{ payee_ssn }}</strong>
                  </span>
                  <VDivider />                  
                  <span class="mb-2 d-flex justify-between mt-2 text-neutral-3">
                    Belopp: <strong class="text-black">{{ formatNumber(amount ?? 0) }} SEK</strong>
                  </span>
                  <VDivider v-if="message" class="mb-2"/>
                  <span v-if="message">
                    Meddelande: <br> <strong class="text-black">{{ message }}</strong>
                  </span>
                </div>

                <div>
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Säkerhetslösenord*" />
                  <VTextField
                    v-model="master_password"
                    :type="isMasterPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isMasterPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                    :rules="shouldShowStep2Validation ? [requiredValidator] : []"
                    placeholder="Ange ditt säkerhetslösenord för att bekräfta"
                    @click:append-inner="isMasterPasswordVisible = !isMasterPasswordVisible"
                  />
                </div>
              </div>
            </VWindowItem>
          </VWindow>

          <!-- Buttons -->
          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions px-0">
            <VBtn
              v-if="currentStep === 2"
              class="btn-ghost"
              @click="prevStep"
            >
              Tillbaka
            </VBtn>

            <VSpacer v-else />
            
            <VBtn
              class="btn-light"
              @click="closeDialog"
            >
              Avbryt
            </VBtn>
            <VBtn
              v-if="currentStep === 1"
              class="btn-gradient"
              @click="nextStep"
            >
              Nästa
            </VBtn>
            <VBtn
              v-else
              class="btn-gradient"
              type="submit"
            >
              Bekräfta betalning
            </VBtn>
          </VCardText>
        </VCardText>
      </VCard>
    </VForm>
  </VDialog>
</template>

<style lang="scss">

  .gap-modal {
    gap: 4px !important;
  }

  .scrollable-dialog-content {
    max-height: 90vh !important;
    overflow-y: auto !important;
  }

  .bg-alert {
    background: linear-gradient(90deg, #EAFFF1 0%, #EAFFF8 50%, #ECFFFF 100%);
    border-radius: 16px;
    gap: 16px;
    opacity: 1;
    padding-top: 16px;
    padding-right: 24px;
    padding-bottom: 16px;
    padding-left: 24px;
  }

  .v-tabs.payouts-tabs {
    .v-btn {
      min-width: 50px !important;
      .v-btn__content {
        font-size: 14px !important;
        color: #454545;
      }
    }
  }

  @media (max-width: 776px) {
    .v-tabs.payouts-tabs {
      .v-icon {
        width: 20px!important;
        height: 20px!important;
      }
      .v-btn {
        .v-btn__content {
          white-space: break-spaces;
        }
      }
    }
  }
</style>

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

<style lang="scss">
  .always-show-prefix .v-text-field__prefix {
    opacity: 1 !important;
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
            height: 48px;
          }
        }
      }
    }

    .v-input.always-show-prefix {
      .v-input__control {
        .v-field {
          .v-field__input {
            padding: 12px 0 !important;
          }
        }
      }
    }
  }
</style>


