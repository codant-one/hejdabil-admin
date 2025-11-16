<script setup>

import { useToastsStores } from '@/stores/useToasts'
import { useDisplay } from "vuetify";
const toastsStores = useToastsStores()

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

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
    <VSnackbar
      v-model="toast.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="toast.type"
      class="snackbar-alert snackbar-dashboard"
    >
      {{ toast.message }}
    </VSnackbar> 
  </section>
</template>