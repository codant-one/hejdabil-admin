<script setup>

const props = defineProps({
  provider: { type: String, default: 'youtube' },
  url: { type: String, default: '' },
  title: { type: String, default: 'Förhandsvisning' },
  thumbnail: { type: String, default: '' },
  showPoster: { type: Boolean, default: false },
})

const hasStarted = ref(false)
const player = ref(null)

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

const isPosterVisible = computed(() => props.showPoster && Boolean(props.thumbnail && embedUrl.value) && !hasStarted.value)

const iframeUrl = computed(() => {
  if (!embedUrl.value || !hasStarted.value) return embedUrl.value

  const url = new URL(embedUrl.value)
  url.searchParams.set('autoplay', '1')

  return url.href
})

const startPlayback = async () => {
  hasStarted.value = true
  await nextTick()
  player.value?.focus({ preventScroll: true })
}
</script>

<template>
  <div>
    <button
      v-if="isPosterVisible"
      type="button"
      class="help-video-preview help-video-poster"
      :aria-label="`Spela upp: ${title}`"
      @click="startPlayback"
    >
      <img :src="thumbnail" alt="">
      <span class="help-video-poster-play">
        <VIcon icon="tabler-player-play" size="72" />
      </span>
    </button>
    <video
      v-else-if="embedUrl && provider === 'mp4'"
      :key="`video-${embedUrl}`"
      ref="player"
      :src="embedUrl"
      :poster="thumbnail || undefined"
      :autoplay="hasStarted"
      :aria-label="title"
      controls
      preload="metadata"
      class="help-video-preview"
    />
    <iframe
      v-else-if="embedUrl"
      :key="`iframe-${embedUrl}`"
      ref="player"
      :src="iframeUrl"
      :title="title"
      :allow="hasStarted ? 'autoplay; fullscreen; picture-in-picture' : 'fullscreen; picture-in-picture'"
      class="help-video-preview"
    />
    <div v-else class="d-flex gap-4">
      <VIcon
        size="32"
        icon="custom-info-color"
        class="flex-shrink-0"
      />
      <span>
      Ange en giltig videolänk för den valda leverantören för att visa förhandsvisningen.
      </span>
    </div>
  </div>
</template>

<style scoped>
.help-video-preview {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 9;
  border: 0;
}

.help-video-poster {
  position: relative;
  background: #252525;
  cursor: pointer;
}

.help-video-poster img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.help-video-poster-play {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  color: #fff;
  background: rgb(0 0 0 / 25%);
}

.help-video-poster:focus-visible {
  outline: 2px solid #fff;
  outline-offset: -4px;
}
</style>
