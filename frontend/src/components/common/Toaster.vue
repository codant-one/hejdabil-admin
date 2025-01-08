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
    <v-alert
        v-if="toast.show"
        :type="toast.type"
        class="mb-6">
        {{ toast.message }}
    </v-alert>
  </section>
</template>