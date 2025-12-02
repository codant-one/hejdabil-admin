<script setup>
import { watch, ref } from 'vue'

const props = defineProps({
  isLoading: {
    type: Boolean,
    required: true,
    default: false
  }
})

const imgKey = ref(0)

// Forzar recarga del GIF cada vez que se muestra para asegurar que la animación comience desde el inicio
watch(() => props.isLoading, (newVal) => {
  if (newVal) {
    imgKey.value++
  }
})
</script>

<template>
  <VDialog 
    :model-value="isLoading" 
    width="auto" 
    persistent
    class="loading-overlay-dialog"
  >
    <VCard class="loading-card">
      <img 
        :key="imgKey"
        src="@/assets/images/billogg_loading.gif" 
        alt="Loading..." 
        class="loading-gif"
      />
    </VCard>
  </VDialog>
</template>

<style scoped>
.loading-card {
  background: transparent !important;
  box-shadow: none !important;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.loading-gif {
  width: 400px;
  height: 400px;
  object-fit: contain;
}

:deep(.v-overlay__content) {
  background: transparent !important;
  box-shadow: none !important;
}
</style>
