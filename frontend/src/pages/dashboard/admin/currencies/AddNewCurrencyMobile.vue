<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { requiredValidator } from '@/@core/utils/validators'
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg"

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  currency: {
    type: Object,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'currencyData',
  "edited"
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(0)
const name = ref('')
const code = ref('')
const flag = ref('')
const isEdit = ref(false)

const initialData = ref(null)
const isConfirmLeaveVisible = ref(false)

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera valuta': 'Lägg till valuta'
})

const currentData = computed(() => ({
  id: id.value,
  name: name.value,
  code: code.value,
  flag: flag.value,
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

    if (!(Object.entries(props.currency).length === 0) && props.currency.constructor === Object) {

      isEdit.value = true
      id.value = props.currency.id
      name.value = props.currency.name
      code.value = props.currency.code
      flag.value = props.currency.flag
    }

    // snapshot initial state after fields are populated
    nextTick(() => {
      initialData.value = { ...currentData.value }
      emit('edited', false)
    })
  }
})

const isValidFlagUrl = computed(() => {
  const pattern = /^https:\/\/hatscripts\.github\.io\/circle-flags\/flags\/[a-z]{2}\.svg$/i
  return pattern.test(flag.value)
})

// 👉 drawer close
const reallyCloseAndReset = () => {
  emit("update:isDrawerOpen", false)
  // emit('resetDuplicate')
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    name.value = ''
    code.value = ''
    flag.value = ''

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
      formData.append('code', code.value)
      formData.append('flag', flag.value)

      emit('currencyData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

      // closeNavigationDrawer()
    }
  })
}

const handleDrawerCurrencyValueUpdate = val => {
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
      class="card-form model-mobile"
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
          <small class="form-text text-muted">
            Använd en URL från webbplatsen <br>
            <a href="https://hatscripts.github.io/circle-flags" target="_blank">https://hatscripts.github.io/circle-flags</a>.<br>
            Flaggan måste motsvara myntets ursprungsland.<br>
            Exempel: För den amerikanska dollarn (<strong>USD</strong>), använder du:<br>
            <code class="p-0">https://hatscripts.github.io/circle-flags/flags/us.svg</code>
          </small>
          <div class="mt-2">
            <img src="https://hatscripts.github.io/circle-flags/flags/us.svg" alt="US Flag" width="32" height="32" />
          </div>
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
              text="Kod*"
            />
            <VTextField
                v-model="code"
                :rules="[requiredValidator]"
            />
        </VListItem>
        <VListItem>
            <VLabel
              class="mb-1 text-body-2 text-high-emphasis"
              text="Flagga*"
            />  
            <VTextField
                v-model="flag"
                :rules="[requiredValidator]"
            />
        </VListItem>
        <VListItem>
          <img v-if="isValidFlagUrl" :src="flag" alt="US Flag" width="32" height="32" />
        </VListItem>

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
      </VList>
    </VForm>
</template>

<style scoped>
  @media (max-width: 1023px) {
    .card-form.currency-mobile {
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

  .cropper-container {
      width: 100%;
      height: 250px;
      background-color: #f5f5f5;
      border-radius: 8px;
      overflow: hidden;
  }

  .cropper-preview {
      width: 100px;
      height: 100px;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-top: 1rem;
  }

  .btn-close-currency {
    height: 32px !important;
  }
  .card-currency {
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

  .form-actions {
    .v-btn {
      flex: 1;
    }
  }
</style>
