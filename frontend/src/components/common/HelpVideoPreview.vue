<script setup>
const props = defineProps({
  provider: { type: String, default: 'youtube' },
  url: { type: String, default: '' },
  title: { type: String, default: 'Förhandsvisning' },
  thumbnail: { type: String, default: '' },
})

const youtubeEmbedUrl = (url, host, path) => {
  let id = null
  if (['youtu.be', 'www.youtu.be'].includes(host)) id = path
  else if (['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'].includes(host)) {
    id = path === 'watch' ? url.searchParams.get('v') : path.match(/^(?:embed|shorts|live)\/([^/]+)$/)?.[1]
  }

  return /^[a-zA-Z0-9_-]{11}$/.test(id ?? '') ? `https://www.youtube.com/embed/${id}` : null
}

const vimeoEmbedUrl = (url, host, path) => {
  if (['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'].includes(host)) {
    const match = path.match(/^(?:video\/|channels\/[^/]+\/|groups\/[^/]+\/videos\/)?(\d+)(?:\/([a-zA-Z0-9]+))?$/)

    if (!match) return null
    const hash = match[2] ?? url.searchParams.get('h')
    const base = `https://player.vimeo.com/video/${match[1]}`

    return hash && /^[a-zA-Z0-9]+$/.test(hash) ? `${base}?h=${hash}` : base
  }

  return null
}

const embedUrl = computed(() => {
  let url
  try {
    url = new URL(props.url)
  } catch {
    return null
  }
  if (!['http:', 'https:'].includes(url.protocol)) return null
  if (props.provider === 'mp4') return url.href
  const path = url.pathname.replace(/^\/|\/$/g, '')
  const host = url.hostname.toLowerCase()

  if (props.provider === 'youtube') return youtubeEmbedUrl(url, host, path)
  if (props.provider === 'vimeo') return vimeoEmbedUrl(url, host, path)

  return null
})
</script>

<template>
  <div>
    <video
      v-if="embedUrl && provider === 'mp4'"
      :key="`video-${embedUrl}`"
      :src="embedUrl"
      :poster="thumbnail || undefined"
      :aria-label="title"
      controls
      preload="metadata"
      class="help-video-preview"
    />
    <iframe
      v-else-if="embedUrl"
      :key="`iframe-${embedUrl}`"
      :src="embedUrl"
      :title="title"
      allow="fullscreen; picture-in-picture"
      class="help-video-preview"
    />
    <VAlert
      v-else
      type="info"
      variant="tonal"
    >
      Ange en giltig videolänk för den valda leverantören för att visa förhandsvisningen.
    </VAlert>
  </div>
</template>

<style scoped>
.help-video-preview {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 9;
  border: 0;
}
</style>
