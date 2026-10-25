<script setup>

import { handleNumericTextFieldKeydown, normalizeNumericTextInput, numericRangeValidator, numericTextFieldProps } from '@/@core/utils/numericTextField'
import { requiredValidator, minLengthNonDigitsValidator } from '@/@core/utils/validators'

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
const flagOld = ref(null)
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
    flagOld.value = null
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

const resizeImage = function(file, maxWidth, maxHeight, quality, mimeType = 'image/png') {
  return new Promise((resolve, reject) => {
    const img = new Image()

    img.src = URL.createObjectURL(file)

    img.onload = () => {
      const canvas = document.createElement('canvas')
      const ctx = canvas.getContext('2d')

      let width = img.width
      let height = img.height

      if (maxWidth && width > maxWidth) {
        height *= maxWidth / width
        width = maxWidth
      }

      if (maxHeight && height > maxHeight) {
        width *= maxHeight / height
        height = maxHeight
      }

      canvas.width = width
      canvas.height = height

      ctx.drawImage(img, 0, 0, width, height)

      canvas.toBlob(blob => {
        resolve(blob)
      }, mimeType, quality)
    }
    img.onerror = error => {
      reject(error)
    }
  })
}

const blobToBase64 = blob => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()

    reader.readAsDataURL(blob)
    reader.onload = () => {
      resolve(reader.result.split(',')[1])
    }
    reader.onerror = error => {
      reject(error)
    }
  })
}

const onImageSelected = files => {
  const file = Array.isArray(files)
    ? files[0]
    : (files?.[0] || files)

  if (!file) {
    flag.value = ''
    flagFile.value = []
    return
  }
  flagOld.value = file

  URL.createObjectURL(file)

  resizeImage(file, 400, 400, 0.9)
    .then(async blob => {
      flagOld.value = blob
      let r = await blobToBase64(blob)
      flag.value = 'data:image/png;base64,' + r
    })
}

const resetFlag = () => {
  flag.value = ''
  flagFile.value = []
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

      formData.append('name', name.value)
      formData.append('iso', iso.value)
      formData.append('iso3', iso3.value)
      formData.append('numcode', numcode.value)
      formData.append('phonecode', phonecode.value)
      formData.append('phone_digits', phone_digits.value)

      if (flagOld.value instanceof Blob) {
        const isoFileName = String(iso.value || 'flag').trim().toLowerCase()
        formData.append('flag', flagOld.value, `${isoFileName}.png`)
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
  <!-- 👉 Form -->
  <VForm
    ref="refForm"
    v-model="isFormValid"
    class="card-form country-mobile"
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
          text="ISO*"
        />
        <VTextField
            v-model="iso"
            maxlength="2"
            :rules="[requiredValidator, minLengthNonDigitsValidator(2)]"
            @input="iso = iso.toUpperCase()"
        />
      </VListItem>
      <VListItem>
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
      </VListItem>
      <VListItem>
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
      </VListItem>
      <VListItem>
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
      </VListItem>
      <VListItem>
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
      </VListItem>
      <VListItem>
        <VAvatar
          rounded
          :size="120"
          class="me-md-6 mb-2"
          :color="flag ? 'default' : 'primary'"
          variant="tonal"
        >
          <VImg
            style="border-radius: 6px;"
            :src="flag"
          />
        </VAvatar>

        <VFileInput                          
          v-model="flagFile"
          accept="image/png, image/jpeg, image/bmp"
          placeholder="Avatar"
          prepend-icon=""
          :rules="isEdit ? [] : [requiredValidator]"
          @update:model-value="onImageSelected"
          @click:clear="resetFlag"
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
    .card-form.country-mobile {
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
