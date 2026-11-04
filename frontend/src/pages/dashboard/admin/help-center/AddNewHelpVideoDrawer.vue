<script setup>
import { useDisplay } from 'vuetify'
import { VDialog, VNavigationDrawer } from 'vuetify/components'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { requiredValidator } from '@/@core/utils/validators'
import HelpVideoPreview from '@/components/common/HelpVideoPreview.vue'

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  video: { type: Object, default: () => ({}) },
  modules: { type: Array, default: () => [] },
  states: { type: Array, default: () => [] },
  busy: { type: Boolean, default: false },
})

const emit = defineEmits(['update:isDrawerOpen', 'videoData'])
const { mdAndDown } = useDisplay()
const refForm = ref()
const form = ref({})
const initialData = ref('')
const confirmLeave = ref(false)
let resolveLeave = null
const dirty = computed(() => JSON.stringify(form.value) !== initialData.value)
const title = computed(() => form.value.id ? 'Uppdatera video' : 'Lägg till video')

watch(() => props.isDrawerOpen, open => {
  if (!open) return
  form.value = {
    id: props.video.id ?? null,
    help_module_id: props.video.help_module_id ?? null,
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
    if (key !== 'id') data.append(key, value ?? '')
  })
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
    <VCard
      flat
      class="card-form"
    >
      <VCardTitle class="d-flex align-center pa-6 font-blauer">
        {{ title }}
        <VSpacer />
        <VBtn
          icon
          class="btn-white"
          :disabled="busy"
          aria-label="Stäng"
          @click="close"
        >
          <VIcon icon="custom-close" />
        </VBtn>
      </VCardTitle>
      <VDivider />
      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <VCardText>
          <VForm
            ref="refForm"
            validate-on="submit"
            @submit.prevent="submit"
          >
            <VRow>
              <VCol cols="12">
                <VSelect
                  v-model="form.help_module_id"
                  :items="modules"
                  item-title="name"
                  item-value="id"
                  label="Modul"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12">
                <VSelect
                  v-model="form.state_id"
                  :items="states"
                  item-title="name"
                  item-value="id"
                  label="Status"
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
                <VTextField
                  v-model="form.thumbnail_url"
                  label="Miniatyrbildens URL"
                  maxlength="500"
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
                  :thumbnail="form.thumbnail_url"
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
      </PerfectScrollbar>
    </VCard>
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
