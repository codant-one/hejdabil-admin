<script setup>
import { Capacitor } from '@capacitor/core'
import { onMounted, ref } from 'vue'
import { useBiometricLogin } from '@/composables/useBiometricLogin'

const biometric = useBiometricLogin()
const enabled = ref(false)
const busy = ref(true)
const errorMessage = ref('')

onMounted(async () => {
  try {
    enabled.value = await biometric.isEnabled()
  } catch (error) {
    errorMessage.value = 'Det gick inte att läsa inställningen för biometrisk inloggning.'
  } finally {
    busy.value = false
  }
})

const disable = async value => {
  if (value || busy.value)
    return
  busy.value = true
  errorMessage.value = ''
  try {
    await biometric.disable()
    enabled.value = false
  } catch (error) {
    errorMessage.value = 'Det gick inte att avaktivera biometrisk inloggning. Kontrollera anslutningen och försök igen.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VCardText
    v-if="Capacitor.isNativePlatform()"
    class="pb-0"
  >
    <div class="settings-layout border-bottom-settings pb-4">
      <div class="settings-layout__sidebar">
        <div class="d-flex flex-column gap-4">
          <span class="subtitle-settings">Biometrisk inloggning</span>
          <span class="text-settings">
            Avaktivering återkallar alla dina enhetstoken. Aktivera funktionen igen efter inloggning med lösenord.
          </span>
        </div>
      </div>
      <div class="settings-layout__content">
        <VSwitch
          :model-value="enabled"
          :disabled="busy"
          :readonly="!enabled"
          :loading="busy"
          class="biometric-switch"
          hide-details
          inset
          @update:model-value="disable"
        >
          <template #label>
            <span class="biometric-switch__label">Logga in med fingeravtryck/Face ID</span>
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
.biometric-switch {
  width: 100%;
}

.biometric-switch :deep(.v-label) {
  display: block;
  white-space: normal;
  overflow: visible;
  text-overflow: unset;
}

.biometric-switch__label {
  font-weight: 700;
  font-size: 16px;
  line-height: 24px;
  letter-spacing: 0;
  color: #454545;
}
</style>
