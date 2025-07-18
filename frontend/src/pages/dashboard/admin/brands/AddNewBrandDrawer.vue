<script setup>

import { themeConfig } from '@themeConfig'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { requiredValidator, urlValidator } from '@/@core/utils/validators'
import { Cropper, CircleStencil } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'

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

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera märke': 'Lägg till märke'
})

watchEffect(async() => {
  if (props.isDrawerOpen) {

    if (!(Object.entries(props.brand).length === 0) && props.brand.constructor === Object) {
      isEdit.value = true
      id.value = props.brand.id
      name.value = props.brand.name
      logoCropped.value = themeConfig.settings.urlStorage + props.brand.logo 
      url.value = props.brand.url

      await nextTick()
      refForm.value?.resetValidation()
    }
  }
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    filename.value = []
      
    name.value = ''
    logoCropped.value = null
    url.value = ''
    
    isEdit.value = false 
    id.value = 0
  })
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

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {

      const result = cropper.value.getResult({
          mime: 'image/png',
          quality: 1,
          fillColor: 'transparent'
      });
      const blob = dataURLtoBlob(result.canvas.toDataURL("image/png"));

      logoOld.value = blob 

      let formData = new FormData()

      formData.append('name', name.value)
      formData.append('logo', logoOld.value)
      formData.append('url', url.value)

      emit('brandData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

</script>

<template>
  <VNavigationDrawer
    temporary
    :width="550"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ getTitle }}
      </h6>

      <VSpacer />

      <!-- 👉 Close btn -->
      <VBtn
        variant="tonal"
        color="default"
        icon
        size="32"
        class="rounded btn-close-brand"
        @click="closeNavigationDrawer"
      >
        <VIcon
          size="18"
          icon="tabler-x"
        />
      </VBTn>
    </div>
    
    <VDivider class="mt-4"/>

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat class="card-brand">
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
          <VRow>
             <VCol cols="12" md="12" v-if="logoCropped">
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
            </VCol>
            <VCol cols="12" md="12">
              <VFileInput 
                v-model="filename"
                label="Logotyp"
                class="mb-2"
                accept="image/png, image/jpeg, image/bmp, image/webp"
                prepend-icon="tabler-camera"
                :rules="isEdit ? [] : [v => (v && v.length > 0) || 'krävs *']"
                @change="onImageSelected"
                @click:clear="resetAvatar"
            />
            </VCol>
            <VCol cols="12" md="12">
                <VTextField
                    v-model="name"
                    label="Namn"
                    :rules="[requiredValidator]"
                />
            </VCol>
            <VCol cols="12" md="12">
                <VTextField
                    v-model="url"
                    label="Hemsida"
                    :rules="[requiredValidator, urlValidator]"
                />
            </VCol>
              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  {{ isEdit ? 'Uppdatering': 'Lägg till' }}
                </VBtn>
                <VBtn
                  variant="tonal"
                  color="secondary"
                  @click="closeNavigationDrawer"
                >
                  Avbryt
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style scoped>

  :deep(.vue-simple-handler) {
      background: #9966FF !important;
  }
  :deep(.cropper-preview-circle) {
      border: dashed 1px #9966FF
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
