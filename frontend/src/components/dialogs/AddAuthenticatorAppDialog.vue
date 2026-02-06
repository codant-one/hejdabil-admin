<script setup>

const props = defineProps({
  authCode: {
    type: String,
    required: false,
  },
  qr: {
    type: String,
    required: false,
  }, 
  token: {
    type: String,
    required: false,
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  is_2fa: {
    type: Boolean,
    required: true,
  }
})

const emit = defineEmits([
  'update:isDialogVisible',
  'submit',
  'close'
])

const authCode = ref(structuredClone(toRaw(props.authCode)))

const formSubmit = () => {
  if (authCode.value) {
    emit('submit', authCode.value)
    emit('update:isDialogVisible', false)
  }
}

const resetAuthCode = () => {
  authCode.value = structuredClone(toRaw(props.authCode))
  emit('update:isDialogVisible', false)
  emit('close')
}

const handleOtp = (value) => {
    authCode.value = value
}

</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    @update:model-value="(val) => $emit('update:isDialogVisible', val)"
    class="action-dialog"    
  >
    <!-- Dialog close btn -->
    <VBtn
      icon
      class="btn-white close-btn"
      @click="resetAuthCode"
      >
        <VIcon size="16" icon="custom-close" />
    </VBtn>

    <VCard>
      <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-auth" class="action-icon" />
          <div class="dialog-title">Lägg till autentiseringsapp</div>
      </VCardText>

      <VCardText class="dialog-text">
        <h6 class="text-lg font-weight-medium mb-2 text-start">
          Appar för autentisering
        </h6>

        <p class="mb-0">
          Använd en autentiseringsapp som Google Authenticator, Microsoft Authenticator, Authy eller 1Password och skanna QR-koden. Det kommer att generera en 6-siffrig kod som du ska ange nästa.
        </p>

        <div class="mb-4">
          <VImg
            width="auto"
            :src="props.qr"
            class="mx-auto"
          />
        </div>

        <VAlert
          color="warning"
          class="alert-no-shrink custom-alert mt-4"
        >
          <span class="text-lg font-weight-medium">{{ props.token }}</span>
          <p class="mb-0">
            Om du inte kan skanna QR-koden kan du ange den hemliga nyckeln manuellt nedan.
          </p>
        </VAlert>
        <VForm @submit.prevent="() => {}">
            <AppOtpInput @updateOtp="handleOtp"  class="my-4"/>

          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0 px-0">
            <VBtn
              class="btn-light"
                @click="resetAuthCode"
              >
              Avbryt
            </VBtn>

            <VBtn
              type="submit"
              class="btn-gradient"
              @click="formSubmit"
            >
              {{ props.is_2fa ? 'Aktivera' : 'Avaktivera' }}
            </VBtn>
          </VCardText>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
