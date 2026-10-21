<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { requiredValidator } from '@/@core/utils/validators'
import 'vue-advanced-cropper/dist/style.css'
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
  <VNavigationDrawer
    temporary
    :width="550"
    location="end"
    class="scrollable-content right-drawer rounded-left-4"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerCurrencyValueUpdate"
  >
    <!-- 👉 Title -->
    <div class="d-flex align-center pa-6">
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
        <VIcon
          size="20"
          icon="custom-close"
        />
      </VBtn>
    </div>
    
    <VDivider />

    <PerfectScrollbar 
      :options="{ wheelPropagation: false }"
      class="scrollbar-no-border"
    >
      <VCard 
        flat 
        class="card-form currency-desktop"
      >
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            validate-on="submit"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol
                  v-if="advisor.show"
                  cols="12"
                  md="12"
                >
                  <VAlert
                    :color="advisor.type"
                    class="alert-no-shrink custom-alert"
                    style="flex: none;"
                  >
                    <VAlertTitle>{{ advisor.message }}</VAlertTitle>
                  </VAlert>
              </VCol>

              <VCol cols="12" md="12">
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
              </VCol>

              <VCol cols="12" md="6">
                  <VLabel
                    class="mb-1 text-body-2 text-high-emphasis"
                    text="Namnet*"
                  />
                  <VTextField
                      v-model="name"
                      :rules="[requiredValidator]"
                  />
              </VCol>
              <VCol cols="12" md="6">
                  <VLabel
                    class="mb-1 text-body-2 text-high-emphasis"
                    text="Kod*"
                  />
                  <VTextField
                      v-model="code"
                      :rules="[requiredValidator]"
                  />
              </VCol>
              <VCol :cols="isValidFlagUrl ? 10 : 12" :md="isValidFlagUrl ? 11 : 12">
                  <VLabel
                    class="mb-1 text-body-2 text-high-emphasis"
                    text="Flagga*"
                  />  
                  <VTextField
                      v-model="flag"
                      :rules="[requiredValidator]"
                  />
              </VCol>
              <VCol cols="2" md="1" class="d-flex align-end">
                <img v-if="isValidFlagUrl" :src="flag" alt="US Flag" width="38" height="38" />
              </VCol>

              <!-- 👉 Submit and Cancel -->
              <VCol
                cols="12"
                class="pb-0"
              >
                <VBtn
                  type="reset"
                  class="btn-light me-3"
                  @click="closeNavigationDrawer"
                >
                  Avbryt
                </VBtn>
                <VBtn
                  type="submit"
                  class="btn-gradient"
                >
                  {{ isEdit ? "Uppdatering" : "Lägg till" }}
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
  @media (min-width: 1024px) {
    .card-form.currency-desktop {
      .v-input:not(.v-textarea) {
        .v-input__control {
          .v-field {
            background-color: #f6f6f6 !important;
            min-height: 40px !important;
            /*height: 40px !important;*/

            .v-text-field__suffix {
              padding: 8px 16px !important;
            }

            .v-field__input {
              min-height: 40px !important;
              /*height: 40px !important;*/
              padding: 8px 16px !important;

              input {
                min-height: 40px !important;
                height: 40px !important;
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
              height: 40px;
            }
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

      .v-select .v-field {
        .v-select__selection {
            align-items: center;
            color: #454545;
        }

        .v-field__input > input {
          top: 0px;
          left: 18px;

        }

        .v-field__input input::placeholder,
        input.v-field__input::placeholder,
        .v-field__input textarea::placeholder,
        textarea.v-field__input::placeholder {
            color: #454545 !important;
            opacity: 1 !important;
          }
      }

      .selector-country {
        .v-input__prepend {
          margin-inline-end: 6px !important;
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
</style>
