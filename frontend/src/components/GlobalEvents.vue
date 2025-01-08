<script setup>

import { inject } from "vue"
import { useToastsStores } from '@/stores/useToasts'

const emitter = inject("emitter")
const toastsStores = useToastsStores()

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('toast', toast)
}

function toast(data) {
    if (data.error) {
        toastsStores.addToastError({
            type: 'error',
            message: data.message
        })
    } else {
        toastsStores.addToast({
            type: 'success',
            message: data.message
        })
    }
}
</script>

<template>
    <span />
</template>