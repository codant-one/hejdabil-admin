<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
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

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera land': 'Lägg till land'
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
  }
})

// 👉 drawer close
const closeNavigationDrawer = () => {
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
  })
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
        class="rounded btn-close-country"
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
      <VCard flat class="card-country">
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            validate-on="submit"
            @submit.prevent="onSubmit"
          >
          <VRow>
            <VCol cols="12" md="12">
                <VTextField
                    v-model="name"
                    label="Namnet"
                    :rules="[requiredValidator]"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="iso"
                    label="ISO"
                    maxlength="2"
                    :rules="[requiredValidator, minLengthNonDigitsValidator(2)]"
                    @input="iso = iso.toUpperCase()"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="iso3"
                    label="ISO 3"
                    maxlength="3"
                    :rules="[requiredValidator, minLengthNonDigitsValidator(3)]"
                    @input="iso3 = iso3.toUpperCase()"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="phonecode"
                    type="number"
                    label="Código de teléfono"
                    :rules="[requiredValidator]"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="phone_digits"
                    type="number"
                    label="Número máximo de dígitos de teléfono"
                    :rules="[requiredValidator]"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="numcode"
                    type="number"
                    label="C&oacute;digo ISO N&uacute;merico"
                    :rules="[requiredValidator]"
                />
            </VCol>
            <VCol cols="12" md="6">
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
                  type="reset"
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
