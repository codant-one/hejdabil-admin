<script setup>

import { Cropper } from 'vue-advanced-cropper'
import { requiredValidator } from '@/@core/utils/validators'
import { resolveHelpVideoThumbnailUrl } from '@/@core/utils/helpVideo'
import HelpVideoPreview from '@/components/common/HelpVideoPreview.vue'
import modalWarningIcon from '@/assets/images/icons/alerts/modal-warning-icon.svg'
import 'vue-advanced-cropper/dist/style.css'

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  helpVideo: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  busy: { type: Boolean, default: false },
  errorMessage: { type: String, default: '' },
})

const emit = defineEmits(['update:isDrawerOpen', 'help-video-data'])
const form = ref({})
const initialData = ref('')
const isConfirmLeaveVisible = ref(false)
const isSubmitting = ref(false)
const fileInput = ref(null)
const cropper = ref(null)
const thumbnailFile = ref(null)
const thumbnailPreview = ref('')
const isThumbnailReady = ref(false)
const imageError = ref('')
const acceptedImageTypes = ['image/jpeg', 'image/png', 'image/bmp', 'image/webp']
const providers = [
  { title: 'YouTube', value: 'youtube' },
  { title: 'Vimeo', value: 'vimeo' },
  { title: 'MP4', value: 'mp4' },
]
let resolveLeave = null

const isBusy = computed(() => props.busy || isSubmitting.value)
const isDirty = computed(() => JSON.stringify(form.value) !== initialData.value || Boolean(thumbnailFile.value))
const getTitle = computed(() => form.value.id ? 'Uppdatera video' : 'Lägg till video')
const previewThumbnail = computed(() => thumbnailPreview.value || resolveHelpVideoThumbnailUrl(form.value.thumbnail_url))

const clearSelectedImage = () => {
  if (thumbnailPreview.value) URL.revokeObjectURL(thumbnailPreview.value)
  thumbnailPreview.value = ''
  thumbnailFile.value = null
  isThumbnailReady.value = false
  imageError.value = ''
  if (fileInput.value) fileInput.value.value = ''
}

const onThumbnailSelected = event => {
  if (isBusy.value) return

  const file = event.target?.files?.[0] || event.dataTransfer?.files?.[0]
  if (!file) return

  if (fileInput.value) fileInput.value.value = ''
  if (!acceptedImageTypes.includes(file.type) || file.size > 10 * 1024 * 1024) {
    imageError.value = acceptedImageTypes.includes(file.type)
      ? 'Bilden får vara högst 10 MB.'
      : 'Välj en bild i PNG, JPEG, BMP eller WebP.'

    return
  }

  clearSelectedImage()
  thumbnailFile.value = file
  thumbnailPreview.value = URL.createObjectURL(file)
}

const onThumbnailError = () => {
  isThumbnailReady.value = false
  imageError.value = 'Kunde inte läsa bilden. Välj en annan miniatyrbild.'
}

watch(() => props.isDrawerOpen, open => {
  clearSelectedImage()
  if (!open) return

  form.value = {
    id: props.helpVideo.id ?? null,
    help_category_id: props.helpVideo.help_category_id ?? null,
    state_id: props.helpVideo.state_id ?? 2,
    title: props.helpVideo.title ?? '',
    description: props.helpVideo.description ?? '',
    video_url: props.helpVideo.video_url ?? '',
    provider: props.helpVideo.provider ?? 'youtube',
    thumbnail_url: props.helpVideo.thumbnail_url ?? '',
  }
  initialData.value = JSON.stringify(form.value)
}, { immediate: true })

const reallyCloseAndReset = () => {
  isConfirmLeaveVisible.value = false
  emit('update:isDrawerOpen', false)
  resolveLeave?.(true)
  resolveLeave = null
}

const closeNavigationDrawer = () => {
  if (isBusy.value) return
  if (isDirty.value) isConfirmLeaveVisible.value = true
  else reallyCloseAndReset()
}

const cancelDiscard = () => {
  isConfirmLeaveVisible.value = false
  resolveLeave?.(false)
  resolveLeave = null
}

defineExpose({ closeNavigationDrawer })

onBeforeRouteLeave(() => {
  if (!props.isDrawerOpen) return true
  if (isBusy.value) return false
  if (!isDirty.value) return true
  isConfirmLeaveVisible.value = true

  return new Promise(resolve => { resolveLeave = resolve })
})

onBeforeUnmount(() => {
  clearSelectedImage()
  resolveLeave?.(false)
})

const onSubmit = async event => {
  if (isBusy.value || imageError.value || (thumbnailFile.value && !isThumbnailReady.value)) return

  isSubmitting.value = true
  try {
    const { valid } = await event
    if (!valid) return

    const data = new FormData()
    Object.entries(form.value).forEach(([key, value]) => {
      if (key !== 'id' && key !== 'thumbnail_url') data.append(key, value ?? '')
    })

    if (thumbnailFile.value) {
      try {
        const canvas = cropper.value?.getResult()?.canvas
        if (!canvas?.width || !canvas.height) throw new Error('Bilden är inte redo för uppladdning.')

        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'))
        if (!blob) throw new Error('Kunde inte bearbeta miniatyrbilden.')

        data.append('thumbnail', blob, 'thumbnail.png')
      } catch {
        imageError.value = 'Kunde inte bearbeta miniatyrbilden. Välj miniatyrbilden igen.'

        return
      }
    }

    emit('help-video-data', { id: form.value.id, data }, form.value.id ? 'update' : 'create')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <VForm
    class="card-form help-video-mobile"
    validate-on="submit"
    @submit.prevent="onSubmit"
  >
    <VCardText class="d-flex flex-column gap-4">
      <VAlert v-if="errorMessage" type="error" variant="tonal" class="help-video-alert">
        {{ errorMessage }}
      </VAlert>

      <VSelect
        v-model="form.help_category_id"
        :items="categories"
        item-title="name"
        item-value="id"
        placeholder="Kategorier"
        aria-label="Kategorier"
        persistent-placeholder
        :rules="[requiredValidator]"
      />
      <div>
        <VLabel for="help-video-title" class="mb-1 text-body-2 text-high-emphasis" text="Titel" />
        <VTextField
          id="help-video-title"
          v-model="form.title"
          maxlength="255"
          :rules="[requiredValidator]"
        />
      </div>
      <div>
        <VLabel for="help-video-description" class="mb-1 text-body-2 text-high-emphasis" text="Beskrivning" />
        <VTextarea id="help-video-description" v-model="form.description" rows="1" auto-grow />
      </div>
      <VSelect
        v-model="form.provider"
        :items="providers"
        placeholder="Leverantör"
        aria-label="Leverantör"
        :rules="[requiredValidator]"
      />
      <div>
        <VLabel for="help-video-url" class="mb-1 text-body-2 text-high-emphasis" text="Videolänk" />
        <VTextField
          id="help-video-url"
          v-model="form.video_url"
          maxlength="255"
          :rules="[requiredValidator]"
        />
      </div>

      <div class="file-upload-area" @dragover.prevent @drop.prevent="onThumbnailSelected">
        <input
          ref="fileInput"
          type="file"
          :accept="acceptedImageTypes.join(',')"
          class="d-none"
          :disabled="isBusy"
          @change="onThumbnailSelected"
        >
        <button
          type="button"
          class="file-upload-trigger"
          :disabled="isBusy"
          @click="fileInput?.click()"
        >
          <VIcon icon="custom-attach" size="24" color="grey" />
          <span class="file-upload-text">{{ thumbnailFile?.name || 'Miniatyrbild' }}</span>
        </button>
        <VBtn
          v-if="thumbnailFile"
          icon
          type="button"
          class="btn-white file-remove-btn"
          aria-label="Ta bort vald miniatyrbild"
          :disabled="isBusy"
          @click="clearSelectedImage"
        >
          <VIcon icon="custom-close" size="12" />
        </VBtn>
      </div>
      <VAlert v-if="imageError" type="error" variant="tonal" class="help-video-alert">
        {{ imageError }}
      </VAlert>
      <Cropper
        v-if="thumbnailPreview"
        :key="thumbnailPreview"
        ref="cropper"
        class="thumbnail-cropper"
        :src="thumbnailPreview"
        @ready="isThumbnailReady = true"
        @error="onThumbnailError"
      />
      <VImg v-else-if="previewThumbnail" :src="previewThumbnail" alt="Miniatyrbild" max-height="250" contain />

      <div v-if="isDrawerOpen" class="video-preview-field">
        <h3 class="text-body-2 font-weight-bold mb-2">Förhandsvisning</h3>
        <HelpVideoPreview
          :provider="form.provider"
          :url="form.video_url"
          :title="form.title || 'Förhandsvisning'"
          :thumbnail="previewThumbnail || ''"
        />
      </div>
    </VCardText>

    <div class="pb-6 px-6 d-flex gap-4 form-actions">
      <VBtn type="button" class="btn-light" :disabled="isBusy" @click="closeNavigationDrawer">
        Avbryt
      </VBtn>
      <VBtn
        type="submit"
        class="btn-gradient"
        :loading="isBusy"
        :disabled="!!thumbnailFile && !isThumbnailReady"
      >
        Spara
      </VBtn>
    </div>
  </VForm>

  <VDialog v-model="isConfirmLeaveVisible" persistent class="action-dialog">
    <VBtn icon class="btn-white close-btn" aria-label="Stäng" @click="cancelDiscard">
      <VIcon size="16" icon="custom-close" />
    </VBtn>
    <VCard>
      <VCardText class="dialog-title-box">
        <img :src="modalWarningIcon" alt="" class="action-icon">
        <div class="dialog-title">Avsluta utan att spara?</div>
      </VCardText>
      <VCardText class="dialog-text">
        <strong>Du har osparade ändringar.</strong> Om du lämnar den här vyn nu kommer informationen du har angett inte att sparas.
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn class="btn-light" @click="cancelDiscard">Avbryt</VBtn>
        <VBtn class="btn-gradient" @click="reallyCloseAndReset">Ja, fortsätt</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.help-video-mobile .dialog-title {
  font-size: 24px;
  line-height: 28px;
  font-weight: 600;
  color: #5d5d5d;
}

.file-upload-area {
  position: relative;
  border: 1px dashed #e7e7e7;
  border-radius: 8px;
  background-color: #f6f6f6;
}

.file-upload-trigger {
  display: flex;
  width: 100%;
  min-height: 80px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 16px;
}

.file-upload-text {
  max-width: calc(100% - 40px);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-remove-btn {
  position: absolute;
  top: 6px;
  right: 6px;
}

.thumbnail-cropper {
  flex-shrink: 0;
  height: 250px;
  overflow: hidden;
  border-radius: 8px;
}

.help-video-alert {
  flex: 0 0 auto;
  font-size: 14px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}


.form-actions {
  .v-btn {
    flex: 1;
  }
}

.video-preview-field div {
  padding: 16px;
  gap: 16px;
  display: flex;
  border-radius: 8px;
  font-weight: 400;
  font-size: 14px;
  line-height: 100%;
  letter-spacing: 0;
  color: #878787 !important;
  background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
}
</style>
