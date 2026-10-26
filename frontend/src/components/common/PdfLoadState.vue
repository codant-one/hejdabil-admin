<script setup>
import { Capacitor } from '@capacitor/core'
import { openExternalLink } from '@/composables/useExternalLink'
import { saveAndShareBlob } from '@/composables/useFileDownload'

const props = defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: null,
    default: null,
  },
  url: {
    type: String,
    default: '',
  },
  fileName: {
    type: String,
    default: 'dokument.pdf',
  },
})

const emit = defineEmits(['retry'])

const openPdf = async () => {
  if (!props.url)
    return

  try {
    // Las URL blob: solo existen dentro del WebView; el navegador in-app no puede abrirlas.
    if (props.url.startsWith('blob:') && Capacitor.isNativePlatform()) {
      const blob = await (await fetch(props.url)).blob()

      await saveAndShareBlob(blob, props.fileName)

      return
    }

    await openExternalLink(props.url)
  } catch (e) {
    console.error('Could not open PDF:', e)
  }
}
</script>

<template>
  <div
    v-if="loading || error"
    class="pdf-load-state d-flex flex-column align-center justify-center gap-4 pa-6 text-center"
  >
    <VProgressCircular
      v-if="loading"
      indeterminate
      color="primary"
    />
    <template v-else>
      <div>PDF-filen kunde inte visas.</div>
      <div class="d-flex flex-wrap justify-center gap-2">
        <VBtn
          class="btn-light"
          @click="emit('retry')"
        >
          Försök igen
        </VBtn>
        <VBtn
          class="btn-gradient"
          :disabled="!url"
          @click="openPdf"
        >
          Öppna PDF
        </VBtn>
      </div>
    </template>
  </div>
</template>

<style scoped>
.pdf-load-state {
  min-height: 200px;
  width: 100%;
}
</style>
