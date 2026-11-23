<script setup>

import { Capacitor } from '@capacitor/core'
import { onMounted, ref } from 'vue'
import { usePushNotifications } from '@/composables/usePushNotifications'

const isNative = Capacitor.isNativePlatform()
const { init, notificationsEnabled, setNotificationsEnabled } = usePushNotifications()
const busy = ref(false)
const errorMessage = ref('')

onMounted(async () => {
  if (!isNative || notificationsEnabled.value !== undefined)
    return

  busy.value = true
  try {
    await init()
    if (notificationsEnabled.value === undefined)
      errorMessage.value = 'Det gick inte att läsa notisinställningen. Kontrollera att notiser är tillåtna för appen i enhetens inställningar.'
  } finally {
    busy.value = false
  }
})

const toggle = async value => {
  if (busy.value || notificationsEnabled.value === undefined)
    return

  busy.value = true
  errorMessage.value = ''
  try {
    if (!await setNotificationsEnabled(value)) {
      errorMessage.value = value
        ? 'Det gick inte att aktivera notiser. Försök igen.'
        : 'Det gick inte att avaktivera notiser. Kontrollera anslutningen och försök igen.'
    }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VCardText
    v-if="isNative"
    class="pb-0"
  >
    <div class="settings-layout border-bottom-settings pb-4">
      <div class="settings-layout__sidebar">
        <div class="d-flex flex-column gap-4">
          <span class="subtitle-settings">Pushnotiser</span>
          <span class="text-settings">
            Inställningen gäller endast den här enheten. Avaktivering stoppar pushnotiser här utan att påverka dina andra enheter. Du kan aktivera funktionen igen när som helst.
          </span>
        </div>
      </div>
      <div class="settings-layout__content">
        <VSwitch
          :model-value="notificationsEnabled ?? null"
          :disabled="busy || notificationsEnabled === undefined"
          class="notifications-switch"
          hide-details
          inset
          @update:model-value="toggle"
        >
          <template #label>
            <span class="notifications-switch__label">Notiser på den här enheten</span>
          </template>
        </VSwitch>

        <VAlert
          v-if="errorMessage"
          type="error"
          class="mt-4"
        >
          {{ errorMessage }}
        </VAlert>
      </div>
    </div>
  </VCardText>
</template>

<style scoped>
.notifications-switch {
  width: 100%;
}

.notifications-switch :deep(.v-label) {
  display: block;
  white-space: normal;
  overflow: visible;
  text-overflow: unset;
}

.notifications-switch__label {
  font-weight: 700;
  font-size: 16px;
  line-height: 24px;
  letter-spacing: 0;
  color: #454545;
}
</style>
