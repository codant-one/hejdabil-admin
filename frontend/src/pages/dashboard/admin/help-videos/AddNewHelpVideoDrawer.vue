<script setup>
import { useDisplay } from 'vuetify'
import { VDialog, VNavigationDrawer } from 'vuetify/components'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import { requiredValidator } from '@/@core/utils/validators'
import { resolveHelpVideoThumbnailUrl } from '@/@core/utils/helpVideo'
import HelpVideoPreview from '@/components/common/HelpVideoPreview.vue'

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  video: { type: Object, default: () => ({}) },
  modules: { type: Array, default: () => [] },
  busy: { type: Boolean, default: false },
})

const emit = defineEmits(['update:isDrawerOpen', 'videoData'])
const { mdAndDown } = useDisplay()
const refForm = ref()
const form = ref({})
const initialData = ref('')
const confirmLeave = ref(false)
const thumbnailFile = ref([])
const thumbnailPreview = ref(null)
const cropper = ref()
const imageError = ref('')
let resolveLeave = null
const dirty = computed(() => JSON.stringify(form.value) !== initialData.value || !!thumbnailPreview.value)
const title = computed(() => form.value.id ? 'Uppdatera video' : 'Lägg till video')
const previewThumbnail = computed(() => thumbnailPreview.value || resolveHelpVideoThumbnailUrl(form.value.thumbnail_url))

const clearThumbnailSelection = () => {
  if (thumbnailPreview.value) URL.revokeObjectURL(thumbnailPreview.value)
  thumbnailPreview.value = null
  imageError.value = ''
}

const onThumbnailSelected = event => {
  clearThumbnailSelection()

  const file = event.target.files?.[0]

  if (file && ['image/jpeg', 'image/png', 'image/bmp', 'image/webp'].includes(file.type) && file.size <= 10 * 1024 * 1024)
    thumbnailPreview.value = URL.createObjectURL(file)
}

watch(() => props.isDrawerOpen, open => {
  clearThumbnailSelection()
  thumbnailFile.value = []
  if (!open) return
  form.value = {
    id: props.video.id ?? null,
    help_category_id: props.video.help_category_id ?? null,
    state_id: props.video.state_id ?? 2,
    title: props.video.title ?? '',
    description: props.video.description ?? '',
    video_url: props.video.video_url ?? '',
    provider: props.video.provider ?? 'youtube',
    thumbnail_url: props.video.thumbnail_url ?? '',
  }
  initialData.value = JSON.stringify(form.value)
  nextTick(() => refForm.value?.resetValidation())
})

onBeforeUnmount(clearThumbnailSelection)

const close = () => {
  if (props.busy) return
  if (dirty.value) confirmLeave.value = true
  else emit('update:isDrawerOpen', false)
}

const discard = () => {
  confirmLeave.value = false
  emit('update:isDrawerOpen', false)
  resolveLeave?.(true)
  resolveLeave = null
}

const cancelDiscard = () => {
  confirmLeave.value = false
  resolveLeave?.(false)
  resolveLeave = null
}

onBeforeRouteLeave(() => {
  if (!props.isDrawerOpen || !dirty.value) return true
  confirmLeave.value = true

  return new Promise(resolve => { resolveLeave = resolve })
})

const submit = async () => {
  const result = await refForm.value.validate()

  if (!result.valid) return
  const data = new FormData()

  Object.entries(form.value).forEach(([key, value]) => {
    if (key !== 'id' && key !== 'thumbnail_url') data.append(key, value ?? '')
  })

  if (thumbnailPreview.value) {
    try {
      const canvas = cropper.value?.getResult()?.canvas
      if (!canvas) throw new Error('Bilden är inte redo för uppladdning.')
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'))
      if (!blob) throw new Error('Kunde inte bearbeta miniatyrbilden.')
      data.append('thumbnail', blob, 'thumbnail.png')
    } catch (error) {
      imageError.value = error.message || 'Kunde inte bearbeta miniatyrbilden.'

      return
    }
  }
  emit('videoData', { id: form.value.id, data }, form.value.id ? 'update' : 'create')
}
</script>

<template>
  <component
    :is="mdAndDown ? VDialog : VNavigationDrawer"
    :model-value="isDrawerOpen"
    :width="mdAndDown ? undefined : 550"
    location="end"
    temporary
    :persistent="busy"
    :class="mdAndDown ? '' : 'scrollable-content right-drawer rounded-left-4'"
    :content-class="mdAndDown ? 'dialog-bottom-full-width' : undefined"
    @update:model-value="!$event && close()"
  >
    <div class="d-flex align-center pa-6">
      <h6 class="title-modal font-blauer">
        {{ title }}
      </h6>
      <VSpacer />
      <VBtn
        icon
        class="btn-white"
        :disabled="busy"
        aria-label="Stäng"
        @click="close"
      >
        <VIcon
          size="20"
          icon="custom-close"
        />
      </VBtn>
    </div>
    <VDivider />
    <PerfectScrollbar
      class="scrollbar-no-border"
      :options="{ wheelPropagation: false }"
    >
      <VCard
        flat
        class="card-form"
      >
        <VCardText>
          <VForm
            ref="refForm"
            validate-on="submit"
            @submit.prevent="submit"
          >
            <VRow>
              <VCol cols="12">
                <VSelect
                  v-model="form.help_category_id"
                  :items="modules"
                  item-title="name"
                  item-value="id"
                  label="Kategorier"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="form.title"
                  label="Titel"
                  maxlength="255"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12">
                <VTextarea
                  v-model="form.description"
                  label="Beskrivning"
                />
              </VCol>
              <VCol cols="12">
                <VSelect
                  v-model="form.provider"
                  :items="['youtube', 'vimeo', 'mp4']"
                  label="Leverantör"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="form.video_url"
                  label="Videolänk"
                  maxlength="255"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12">
                <VLabel class="mb-1 text-body-2 text-high-emphasis">
                  Miniatyrbild
                </VLabel>
                <VFileInput
                  v-model="thumbnailFile"
                  accept="image/png,image/jpeg,image/bmp,image/webp"
                  prepend-icon="tabler-camera"
                  :disabled="busy"
                  :rules="[
                    files => !files?.length || ['image/jpeg', 'image/png', 'image/bmp', 'image/webp'].includes(files[0].type) || 'Välj en bild i PNG, JPEG, BMP eller WebP.',
                    files => !files?.length || files[0].size <= 10 * 1024 * 1024 || 'Bilden får vara högst 10 MB.',
                  ]"
                  @change="onThumbnailSelected"
                  @click:clear="clearThumbnailSelection"
                />
                <VAlert
                  v-if="imageError"
                  type="error"
                  variant="tonal"
                  class="mt-2"
                >
                  {{ imageError }}
                </VAlert>
              </VCol>
              <VCol
                v-if="thumbnailPreview"
                cols="12"
              >
                <Cropper
                  ref="cropper"
                  class="thumbnail-cropper"
                  :src="thumbnailPreview"
                />
              </VCol>
              <VCol
                v-else-if="previewThumbnail"
                cols="12"
              >
                <VImg
                  :src="previewThumbnail"
                  alt="Miniatyrbild"
                  max-height="250"
                  contain
                />
              </VCol>
              <VCol
                v-if="isDrawerOpen"
                cols="12"
              >
                <h3 class="mb-3">
                  Förhandsvisning
                </h3>
                <HelpVideoPreview
                  :provider="form.provider"
                  :url="form.video_url"
                  :title="form.title || 'Förhandsvisning'"
                  :thumbnail="previewThumbnail || ''"
                />
              </VCol>
              <VCol
                cols="12"
                class="d-flex justify-end gap-3"
              >
                <VBtn
                  class="btn-light"
                  :disabled="busy"
                  @click="close"
                >
                  Avbryt
                </VBtn>
                <VBtn
                  type="submit"
                  class="btn-gradient"
                  :loading="busy"
                >
                  Spara
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </component>
  <VDialog
    v-model="confirmLeave"
    persistent
    class="action-dialog"
  >
    <VCard>
      <VCardTitle>Osparade ändringar</VCardTitle>
      <VCardText>Vill du lämna utan att spara?</VCardText>
      <VCardActions class="justify-end">
        <VBtn
          class="btn-light"
          @click="cancelDiscard"
        >
          Avbryt
        </VBtn>
        <VBtn
          class="btn-gradient"
          @click="discard"
        >
          Lämna
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.thumbnail-cropper {
  max-height: 300px;
}
</style>
