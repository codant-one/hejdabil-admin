<script setup>

import { useToastsStores } from '@/stores/useToasts'

const toastsStores = useToastsStores()

const toast = ref({
  type: '',
  message: '',
  show: false
})

// 👉 watching toasts
watchEffect(() => {

  if (toastsStores.getItems.type){

    toast.value = {
      type: toastsStores.getItems.type,
      message: toastsStores.getItems.message,
      show: true
    } 

    setTimeout(() => {
      toastsStores.items = {}
      toast.value = {
        type: '',
        message: '',
        show: false
      }
    }, 3000)

  }
})

</script>

<template>
  <section>
    <VAlert
        v-if="toast.show"
        :type="toast.type"
        class="mb-6">
        {{ toast.message }}
    </VAlert>
  </section>
</template>