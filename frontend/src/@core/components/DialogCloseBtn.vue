<script setup>
const props = defineProps({
  icon: {
    type: String,
    required: false,
    default: 'custom-close',
  },
})
</script>

<template>
  <VBtn
    icon
    class="v-dialog-close-btn"
  >
    <VIcon :icon="props.icon" />
  </VBtn>
</template>
