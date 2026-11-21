<script setup>

import { Capacitor } from '@capacitor/core'
import { onMounted, ref } from 'vue'
import { usePushNotifications } from '@/composables/usePushNotifications'

const isNative = Capacitor.isNativePlatform()
const { init, notificationsEnabled, setNotificationsEnabled } = usePushNotifications()
const busy = ref(false)
const failed = ref(false)

onMounted(async () => {
  if (isNative && notificationsEnabled.value === undefined)
    await init()
})

const toggle = async value => {
  if (busy.value || notificationsEnabled.value === undefined)
    return

  busy.value = true
  try {
    failed.value = !await setNotificationsEnabled(value)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VCardText v-if="isNative">
    <VSwitch
      :model-value="notificationsEnabled ?? null"
      :disabled="busy || notificationsEnabled === undefined"
      :loading="busy"
      :error-messages="failed ? 'Det gick inte att spara notisinställningen.' : undefined"
      label="Notiser på den här enheten"
      hide-details="auto"
      inset
      @update:model-value="toggle"
    />
  </VCardText>
</template>
