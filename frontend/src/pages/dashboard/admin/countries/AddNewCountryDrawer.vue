<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { handleNumericTextFieldKeydown, normalizeNumericTextInput, numericRangeValidator, numericTextFieldProps } from '@/@core/utils/numericTextField'
import { requiredValidator, minLengthNonDigitsValidator } from '@/@core/utils/validators'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg"

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  country: {
    type: Object,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'countryData',
  "edited"
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(0)
const name = ref('')
const iso = ref('')
const iso3 = ref('')
const numcode = ref('')
const phonecode = ref('')
const phone_digits = ref('')
const flag = ref('')
const flagFile = ref([])
const cropper = ref()
const isFlagLoading = ref(false)
let flagSelectionId = 0
const isEdit = ref(false)
const nonNegativeNumericRules = [numericRangeValidator({ min: 0 })]

const initialData = ref(null)
const isConfirmLeaveVisible = ref(false)

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera land': 'Lägg till land'
})

const currentData = computed(() => ({
  id: id.value,
  name: name.value,
  iso: iso.value,
  iso3: iso3.value,
  numcode: numcode.value,
  phonecode: phonecode.value,
  phone_digits: phone_digits.value,
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

    if (!(Object.entries(props.country).length === 0) && props.country.constructor === Object) {

      isEdit.value = true
      id.value = props.country.id
      name.value = props.country.name
      iso.value = props.country.iso
      iso3.value = props.country.iso3
      numcode.value = props.country.numcode
      phonecode.value = props.country.phonecode
      phone_digits.value = props.country.phone_digits
      flag.value = props.country.flag
    }

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
    iso.value = ''
    iso3.value = ''
    numcode.value = ''
    phonecode.value = ''
    phone_digits.value = ''
    flag.value = ''
    flagFile.value = []
    flagSelectionId++
    isFlagLoading.value = false
    advisor.value = { type: '', message: '', show: false }
    isFormValid.value = false
    
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

const showFlagError = () => {
  isFlagLoading.value = false
  advisor.value = {
    type: 'error',
    message: 'Bilden kunde inte laddas eller beskäras. Välj bilden igen.',
    show: true,
  }
}

const onImageSelected = files => {
  const file = Array.isArray(files)
    ? files[0]
    : (files?.[0] || files)

  resetFlag()
  if (!file) return

  isFlagLoading.value = true
  const selectionId = flagSelectionId
  const reader = new FileReader()
  reader.onload = () => {
    if (selectionId !== flagSelectionId) return
    flag.value = reader.result
  }
  reader.onerror = () => {
    if (selectionId === flagSelectionId) showFlagError()
  }
  reader.readAsDataURL(file)
}

const resetFlag = () => {
  flagSelectionId++
  flag.value = ''
  isFlagLoading.value = false
  advisor.value = { type: '', message: '', show: false }
}

const onSubmit = () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      let flagBlob = null
      if (flagFile.value?.length) {
        const canvas = cropper.value?.getResult()?.canvas
        if (isFlagLoading.value || !canvas) {
          showFlagError()
          return
        }
        try {
          flagBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'))
        } catch (error) {
          showFlagError()
          return
        }
        if (!flagBlob) {
          showFlagError()
          return
        }
      }

      let formData = new FormData()

      formData.append('name', name.value)
      formData.append('iso', iso.value)
      formData.append('iso3', iso3.value)
      formData.append('numcode', numcode.value)
      formData.append('phonecode', phonecode.value)
      formData.append('phone_digits', phone_digits.value)

      if (flagBlob) {
        const isoFileName = String(iso.value || 'flag').trim().toLowerCase()
        formData.append('flag', flagBlob, `${isoFileName}.png`)
      }

      emit('countryData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

      // closeNavigationDrawer()
    }
  })
}

const handleDrawerCountryValueUpdate = val => {
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
    @update:model-value="handleDrawerCountryValueUpdate"
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
        <VIcon
          size="20"
          icon="custom-close"
        />
      </VBtn>
    </div>
    
    <VDivider class="mt-4"/>

    <PerfectScrollbar 
      :options="{ wheelPropagation: false }"
      class="scrollbar-no-border"
    >
      <VCard 
        flat 
        class="card-form country-desktop"
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

              <VCol v-if="flag" cols="12" md="12">
                <Cropper
                  v-if="flagFile.length"
                  ref="cropper"
                  class="cropper-container"
                  background-class="cropper-background"
                  :src="flag"
                  :canvas="{ maxWidth: 1200, maxHeight: 1200 }"
                  :stencil-props="{ previewClass: 'cropper-preview-circle' }"
                  @ready="isFlagLoading = false"
                  @error="showFlagError"
                />
                <VImg v-else :src="flag" height="120" contain />
              </VCol>
              <VCol cols="12" md="12">
                <VLabel
                  class="mb-1 text-body-2 text-high-emphasis"
                  text="Flagga*"
                />
                <VFileInput
                  v-model="flagFile"
                  class="mb-2"
                  accept="image/png, image/jpeg, image/bmp, image/webp"
                  prepend-icon="tabler-camera"
                  :rules="isEdit ? [] : [v => (v && v.length > 0) || 'krävs *']"
                  @update:model-value="onImageSelected"
                  @click:clear="resetFlag"
                />
              </VCol>

              <VCol cols="12" md="12">
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
                  text="ISO*"
                />
                <VTextField
                  v-model="iso"
                  maxlength="2"
                  :rules="[requiredValidator, minLengthNonDigitsValidator(2)]"
                  @input="iso = iso.toUpperCase()"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VLabel
                  class="mb-1 text-body-2 text-high-emphasis"
                  text="ISO 3*"
                />
                <VTextField
                  v-model="iso3"
                  maxlength="3"
                  :rules="[requiredValidator, minLengthNonDigitsValidator(3)]"
                  @input="iso3 = iso3.toUpperCase()"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VLabel
                  class="mb-1 text-body-2 text-high-emphasis"
                  text="Telefonkod*"
                />
                <VTextField
                  v-model="phonecode"
                  v-bind="numericTextFieldProps"
                  :rules="[requiredValidator, ...nonNegativeNumericRules]"
                  @input="phonecode = normalizeNumericTextInput(phonecode)"
                  @keydown="handleNumericTextFieldKeydown"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VLabel
                  class="mb-1 text-body-2 text-high-emphasis"
                  text="Maximalt antal telefonnummer*"
                />
                <VTextField
                  v-model="phone_digits"
                  v-bind="numericTextFieldProps"
                  :rules="[requiredValidator, ...nonNegativeNumericRules]"
                  @input="phone_digits = normalizeNumericTextInput(phone_digits)"
                  @keydown="handleNumericTextFieldKeydown"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VLabel
                  class="mb-1 text-body-2 text-high-emphasis"
                  text="ISO sifferkod*"
                />
                <VTextField
                  v-model="numcode"
                  v-bind="numericTextFieldProps"
                  :rules="[requiredValidator, ...nonNegativeNumericRules]"
                  @input="numcode = normalizeNumericTextInput(numcode)"
                  @keydown="handleNumericTextFieldKeydown"
                />
              </VCol>
              <!-- 👉 Submit and Cancel -->
              <VCol
                cols="12"
                class="pb-0"
              >
                <VBtn
                  type="button"
                  class="btn-light me-3"
                  @click="closeNavigationDrawer"
                >
                  Avbryt
                </VBtn>
                <VBtn
                  type="submit"
                  class="btn-gradient"
                  :disabled="isFlagLoading"
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
  .cropper-container {
    width: 100%;
    height: 250px;
    background-color: #f5f5f5;
    border-radius: 8px;
    overflow: hidden;
  }

  @media (min-width: 1024px) {
    .card-form.country-desktop {
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


  .btn-close-country {
    height: 32px !important;
  }
  .card-country {
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
