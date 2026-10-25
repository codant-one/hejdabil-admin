<script setup>

import { handleDecimalTextFieldKeydown, decimalRangeValidator, normalizeDecimalTextInput, numericRangeValidator } from '@/@core/utils/numericTextField'
import { requiredValidator } from '@/@core/utils/validators'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  plan: {
    type: Object,
    required: false
  },
  features: {
    type: Array,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'planData',
  'alert',
  'loading',
  'edited'
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(0)
const name = ref('')
const description = ref('')
const price_month = ref(0)
const price_annual = ref(0)
const selectedFeatures = ref([])
const features = ref([])
const isEdit = ref(false)
const nonNegativeDecimalRules = [decimalRangeValidator({ min: 0 })];

const initialData = ref(null)
const isConfirmLeaveVisible = ref(false)

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera plan': 'Lägg till plan'
})

const currentData = computed(() => ({
  id: id.value,
  name: name.value,
  description: description.value,
  price_month: price_month.value,
  price_annual: price_annual.value,
}))

const isDirty = computed(() => {
  if (!initialData.value) return false
  try {
    return JSON.stringify(currentData.value) !== JSON.stringify(initialData.value)
  } catch (e) {
    return true
  }
})

const advisor = ref({
  type: '',
  message: '',
  show: false,
})

watchEffect(async() => {

  if (props.isDrawerOpen) {

    if (!(Object.entries(props.plan).length === 0) && props.plan.constructor === Object) {

      isEdit.value = true
      id.value = props.plan.id
      name.value = props.plan.name
      description.value = props.plan.description
      price_month.value = props.plan.price_month
      price_annual.value = props.plan.price_annual
      var sFeatures = props.plan.feature_plans

      selectedFeatures.value = (sFeatures !== undefined && sFeatures.length > 0) ? sFeatures.map(feature => feature.feature_id) : [];
    }

    features.value = props.features
    isFormValid.value = true

    // snapshot initial state after fields are populated
    nextTick(() => {
      initialData.value = { ...currentData.value }
      emit('edited', false)
    })
  }
})

// 👉 drawer close
const reallyCloseAndReset = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.resetValidation()

    name.value = ''
    description.value = ''
    price_month.value = 0
    price_annual.value = 0
    isFormValid.value = false
    features.value = []
    selectedFeatures.value = []
    
    isEdit.value = false 
    id.value = 0
    initialData.value = null
    emit('edited', false)
  })
}

defineExpose({
  reallyCloseAndReset,
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  if (isDirty.value) {
    isConfirmLeaveVisible.value = true
    
    return
  }
  reallyCloseAndReset()
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

      formData.append('name', name.value)
      formData.append('description', description.value)
      formData.append('price_month', price_month.value)
      formData.append('price_annual', price_annual.value)
      formData.append('features', JSON.stringify(selectedFeatures.value)) 

      emit('planData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

      // setTimeout(() => {
      //     closeNavigationDrawer()
      // }, 1000)
    }
  })
}

const handleDrawerModelValueUpdate = val => {
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

  emit('update:isDrawerOpen', val)
}

watch(currentData, () => {
  if (!initialData.value) return
  emit('edited', isDirty.value)
}, { deep: true })

</script>

<template>
  <!-- 👉 Form -->
  <VForm
    ref="refForm"
    v-model="isFormValid"
    class="card-form plan-mobile"
    validate-on="submit"
    @submit.prevent="onSubmit"
  >
    <VList>
      <VListItem
          v-if="advisor.show"
          class="m-4 p-5"
        >
        <VAlert
          :color="advisor.type"
          class="alert-no-shrink custom-alert"
          style="flex: none; margin: 2px 2px 2px 1px"
        >
          <VAlertTitle>{{ advisor.message }}</VAlertTitle>
        </VAlert>
      </VListItem>

      <VListItem>
        <VLabel
          class="mb-1 text-body-2 text-high-emphasis"
          text="Namnet*"
        />
        <VTextField
          v-model="name"
          :rules="[requiredValidator]"
        />
      </VListItem>
      <VListItem>
        <VLabel
          class="mb-1 text-body-2 text-high-emphasis"
          text="Beskrivning*"
        />
        <VTextarea
          v-model="description"
          rows="3"
          :rules="[requiredValidator]"
        />
      </VListItem>
      <VListItem>
        <VLabel
          class="mb-1 text-body-2 text-high-emphasis"
          text="Månadpris*"
        />
        <VTextField
          v-model="price_month"
          type="number"
          :rules="[requiredValidator, ...nonNegativeDecimalRules]"
          @input="price_month = normalizeDecimalTextInput(price_month)"
          @keydown="handleDecimalTextFieldKeydown"
        />
      </VListItem>
      <VListItem>
        <VLabel
          class="mb-1 text-body-2 text-high-emphasis"
          text="Årspris*"
        />
        <VTextField
          v-model="price_annual"
          type="number"
          :rules="[requiredValidator, ...nonNegativeDecimalRules]"
          @input="price_annual = normalizeDecimalTextInput(price_annual)"
          @keydown="handleDecimalTextFieldKeydown"
        />
      </VListItem>
      <VListItem>
        <VLabel 
          class="mb-1 text-body-2 text-high-emphasis" 
          text="Features" 
        />
        <VCheckbox
          v-for="feature in features"
          v-model="selectedFeatures"
          class="ps-4"
          :label="feature.name"
          :value="feature.id"
          hide-details
        />
      </VListItem>
    </VList>

    <!-- 👉 Submit and Cancel -->
    <div class="pb-6 px-6 d-flex gap-4 form-actions">
      <!-- 👉 Submit and Cancel -->
      <VBtn
        class="btn-light" 
        type="button"
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
      <VIcon
        size="16"
        icon="custom-close"
      />
    </VBtn>
    <VCard>
      <VCardText class="dialog-title-box">
        <img
          :src="modalWarningIcon"
          alt="Warning"
          class="action-icon"
        >
        <div class="dialog-title">
          Avsluta utan att spara?
        </div>
      </VCardText>
      <VCardText class="dialog-text">
        <strong>Du har osparade ändringar.</strong> Om du lämnar den här vyn nu kommer informationen du har angett inte att sparas.
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn
          class="btn-light"
          @click="isConfirmLeaveVisible = false"
        >
          Avbryt
        </VBtn>
        <VBtn
          class="btn-gradient"
          @click="() => { isConfirmLeaveVisible = false; reallyCloseAndReset(); }"
        >
          Ja, fortsätt
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
  @media (max-width: 1023px) {
    .card-form.plan-mobile {
      .v-list {
        padding: 28px 24px 24px !important;

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

          .selector-country {
            .v-input__prepend {
              margin-inline-end: 6px !important;
            }
          }

          .v-autocomplete .v-field {
            .v-autocomplete__selection-text {
              align-items: center;
              display: flex;
            }

            .v-field__append-inner {
              align-items: center;
              padding-top: 0px;
            }
          }

          .v-text-field {
            .v-input__control {
              padding-top: 0;

              .v-field {
                .v-text-field__prefix {
                  height: 48px;
                  color: #454545;
                }
              }

              input {
                min-height: 48px !important;
                height: 48px !important;
                padding: 12px 16px;
              }
            }
          }

          .v-input.always-show-prefix {
            .v-input__control {
              .v-field {
                .v-field__input {
                  padding: 8px 0 !important;
                }
              }
            }
          }
        }
      }
      & .v-input:not(.v-textarea) {
        & .v-input__control {
          .v-field {
            background-color: #f6f6f6;
            min-height: 48px !important;
            height: 48px !important;
            .v-field-label {
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

  :deep(.vue-simple-handler) {
      background: #57F287 !important;
  }
  :deep(.cropper-preview-circle) {
      border: dashed 1px #57F287
  }
  :deep(.cropper-background),
  :deep(.vue-advanced-cropper__foreground) {
      background-color: transparent !important;
  }

  .btn-close-plan {
    height: 32px !important;
  }
  .card-plan {
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
