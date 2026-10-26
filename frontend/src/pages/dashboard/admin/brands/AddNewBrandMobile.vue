<script setup>

import { themeConfig } from '@themeConfig'
import { requiredValidator, urlValidator } from '@/@core/utils/validators'
import { Cropper, CircleStencil } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg"

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  brand: {
    type: Object,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'brandData',
  "edited"
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(0)
const name = ref('')
const logo = ref(null)
const logoCropped = ref(null)
const logoOld = ref(null)
const filename = ref([])
const url = ref('')
const cropper = ref()
const isEdit = ref(false)

const initialData = ref(null)
const isConfirmLeaveVisible = ref(false)

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera märke': 'Lägg till märke'
})

const currentData = computed(() => ({
  id: id.value,
  name: name.value,
  logoOld: logoOld.value,
  logoCropped: logoCropped.value,
  url: url.value
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

const normalizeLogoPath = value => {
  if (!value || typeof value !== 'string')
    return null

  const trimmed = value.trim()
  if (!trimmed || trimmed === 'null' || trimmed === 'undefined')
    return null

  return trimmed
}

watchEffect(async() => {
  if (props.isDrawerOpen) {

    if (!(Object.entries(props.brand).length === 0) && props.brand.constructor === Object) {
      isEdit.value = true
      id.value = props.brand.id
      name.value = props.brand.name
      const normalizedLogo = normalizeLogoPath(props.brand.logo)
      logoCropped.value = normalizedLogo ? `${themeConfig.settings.urlStorage}${normalizedLogo}` : null
      url.value = props.brand.url

      await nextTick()
      refForm.value?.resetValidation()
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
  emit("update:isDrawerOpen", false)
  // emit('resetDuplicate')
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    name.value = ''
    logo.value = null
    logoCropped.value = null
    logoOld.value = null
    filename.value = []
    url.value = ''

    isEdit.value = false 
    id.value = 0
    initialData.value = null
    emit('edited', false)
  })
}

defineExpose({
  reallyCloseAndReset,
})

const closeNavigationDrawer = () => {
  if (isDirty.value) {
    isConfirmLeaveVisible.value = true
    
    return
  }
  reallyCloseAndReset()
}

const onCropChange = (coordinates) => {
    // console.log('coordinates', coordinates)
}

const resetAvatar = () => {
  logoCropped.value = null
  logoOld.value = null
}

const resizeImage = function(file, maxWidth, maxHeight, quality) {
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
      }, file.type, quality)
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

const onImageSelected = event => {
  const file = event.target.files[0]

  if (!file) return
  // logoOld.value = file

  URL.createObjectURL(file)

  resizeImage(file, 1200, 1200, 1)
    .then(async blob => {
        let r = await blobToBase64(blob)
        logoCropped.value = 'data:image/jpeg;base64,' + r
    })
}

const dataURLtoBlob = (dataURL) => {
  const [header, base64] = dataURL.split(',');
  const mimeMatch = header.match(/:(.*?);/);
  const mime = mimeMatch ? mimeMatch[1] : 'image/png'; 
  const binary = atob(base64);
  const len = binary.length;
  const u8arr = new Uint8Array(len);
  for (let i = 0; i < len; i++) {
    u8arr[i] = binary.charCodeAt(i);
  }
  return new Blob([u8arr], { type: mime });
}

const getCroppedLogoBlob = () => {
  if (!cropper.value || typeof cropper.value.getResult !== 'function')
    return null

  const result = cropper.value.getResult({
    mime: 'image/png',
    quality: 1,
    fillColor: 'transparent',
  })

  if (!result?.canvas)
    return null

  return dataURLtoBlob(result.canvas.toDataURL('image/png'))
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const blob = getCroppedLogoBlob()
      logoOld.value = blob
      if (!logoOld.value && !isEdit.value) {
        advisor.value = {
          type: 'error',
          message: 'Logotyp krävs *',
          show: true,
        }
        return
      }

      let formData = new FormData()

      formData.append('name', name.value)
      if (logoOld.value)
        formData.append('logo', logoOld.value)
      formData.append('url', url.value)

      emit('brandData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

      // closeNavigationDrawer()
    }
  })
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
    class="card-form brand-mobile"
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

      <VListItem v-if="logoCropped">
          <Cropper
              ref="cropper"
              class="cropper-container"
              preview-class="cropper-preview"
              background-class="cropper-background"
              :src="logoCropped"
              :stencil-props="{
                  previewClass: 'cropper-preview-circle'
              }"
              @change="onCropChange"
          />
      </VListItem>
      <VListItem>
        <VLabel
          class="mb-1 text-body-2 text-high-emphasis"
          text="Logotyp"
        />
        <VFileInput 
          v-model="filename"
          class="mb-2"
          accept="image/png, image/jpeg, image/bmp, image/webp"
          prepend-icon="tabler-camera"
          :rules="isEdit ? [] : [v => (v && v.length > 0) || 'krävs *']"
          @change="onImageSelected"
          @click:clear="resetAvatar"
        />
      </VListItem>
      <VListItem>
        <VLabel
          class="mb-1 text-body-2 text-high-emphasis"
          text="Namn*"
        />
        <VTextField
            v-model="name"
            :rules="[requiredValidator]"
        />
      </VListItem>
      <VListItem>
        <VLabel
          class="mb-1 text-body-2 text-high-emphasis"
          text="Hemsida*"
        />
        <VTextField
            v-model="url"
            :rules="[requiredValidator, urlValidator]"
        />
      </VListItem>
    </VList>

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
    .card-form.brand-mobile {
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

  .v-btn {
    width: 100%;
  }

  .form-actions {
    .v-btn {
      flex: 1;
    }
  }

  .btn-close-brand {
    height: 32px !important;
  }

  .card-brand {
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
