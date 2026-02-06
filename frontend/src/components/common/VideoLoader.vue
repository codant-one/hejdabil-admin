<script setup>
import { ref, onMounted } from 'vue'
import videoSrc from '@/assets/images/billogg_alpha.mp4'

const props = defineProps({
  showOnce: {
    type: Boolean,
    default: false
  },
  storageKey: {
    type: String,
    default: 'hasSeenVideo'
  },
  useLocalStorage: {
    type: Boolean,
    default: false
  }
})

const showVideoLoader = ref(false)

const getStorage = () => {
  return props.useLocalStorage ? localStorage : sessionStorage
}

const checkAndShowVideo = () => {
  if (props.showOnce) {
    const storage = getStorage()
    const hasSeen = storage.getItem(props.storageKey)
    if (!hasSeen) {
      showVideoLoader.value = true
    }
  } else {
    showVideoLoader.value = true
  }
}

const hideVideoLoader = () => {
  showVideoLoader.value = false
  if (props.showOnce) {
    const storage = getStorage()
    storage.setItem(props.storageKey, 'true')
  }
}

onMounted(() => {
  checkAndShowVideo()
})
</script>

<template>
  <Transition name="fade">
    <div v-if="showVideoLoader" class="video-loader">
      <video
        autoplay
        muted
        playsinline
        class="video-loader__video"
        @ended="hideVideoLoader"
      >
        <source :src="videoSrc" type="video/mp4">
      </video>
    </div>
  </Transition>
</template>

<style scoped>
.video-loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  height: 100dvh; /* Para mobile con barras de navegación */
  z-index: 9999;
  background: #1f1f1f; /* Color de fondo del video */
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.video-loader__video {
  width: 100%;
  height: 100%;
  object-fit: contain; /* Muestra el video completo sin recortar */
}

/* Ajustes específicos para mobile */
@media (max-width: 768px) {
  .video-loader__video {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
