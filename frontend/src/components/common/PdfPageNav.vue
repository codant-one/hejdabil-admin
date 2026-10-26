<script setup>
defineProps({
  page: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['update:page'])
</script>

<template>
  <div class="d-flex align-center justify-center gap-4 py-3">
    <VBtn
      class="btn-light"
      size="small"
      :disabled="page <= 1"
      @click="emit('update:page', page - 1)"
    >
      Föregående
    </VBtn>
    <span>Sida {{ page }} av {{ total }}</span>
    <VBtn
      class="btn-light"
      size="small"
      :disabled="page >= total"
      @click="emit('update:page', page + 1)"
    >
      Nästa
    </VBtn>
  </div>
</template>
