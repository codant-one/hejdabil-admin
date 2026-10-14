<script setup>
import { onBeforeUnmount, ref } from 'vue'
import { useBiometricLogin } from '@/composables/useBiometricLogin'

const biometric = useBiometricLogin()
const visible = ref(false)
const busy = ref(false)
const errorMessage = ref('')
let resolveOffer

const close = () => {
  visible.value = false
  resolveOffer?.()
  resolveOffer = undefined
}

const offer = async () => {
  try {
    if (!await biometric.isAvailable() || await biometric.isEnabled())
      return
  } catch (error) {
    errorMessage.value = 'Det gick inte att kontrollera biometrisk inloggning. Du kan fortsätta utan att aktivera den.'
  }
  visible.value = true

  return new Promise(resolve => {
    resolveOffer = resolve
  })
}

const enable = async () => {
  busy.value = true
  errorMessage.value = ''
  try {
    await biometric.enable()
    close()
  } catch (error) {
    errorMessage.value = 'Det gick inte att aktivera biometrisk inloggning. Försök igen eller fortsätt utan.'
  } finally {
    busy.value = false
  }
}

onBeforeUnmount(close)
defineExpose({ offer })
</script>

<template>
  <VDialog
    v-model="visible"
    persistent
    class="action-dialog"
  >
    <VCard>
      <VCardText class="dialog-title-box">
        <div class="dialog-title">Aktivera biometrisk inloggning?</div>
      </VCardText>

      <VCardText class="dialog-text">
        Vill du logga in med fingeravtryck eller Face ID nästa gång? Ditt lösenord sparas inte.
        <VAlert
          v-if="errorMessage"
          type="error"
          class="mt-4"
        >
          {{ errorMessage }}
        </VAlert>
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn
          class="btn-light"
          :disabled="busy"
          @click="close"
        >
          Inte nu
        </VBtn>
        <VBtn
          class="btn-gradient"
          :loading="busy"
          :disabled="busy"
          @click="enable"
        >
          Aktivera
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
