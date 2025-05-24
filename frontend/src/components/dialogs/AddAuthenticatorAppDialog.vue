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
    max-width="787"
    :model-value="props.isDialogVisible"
    @update:model-value="(val) => $emit('update:isDialogVisible', val)"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="resetAuthCode" />

    <VCard class="pa-4 pa-sm-8">
      <VCardItem>
        <VCardTitle class="text-h5 font-weight-medium text-center">
          Lägg till autentiseringsapp
        </VCardTitle>
      </VCardItem>

      <VCardText class="pt-3 px-3">
        <h6 class="text-lg font-weight-medium mb-2 text-center text-md-start">
          Appar för autentisering
        </h6>

        <p class="mb-6">
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
          color="light-warning"
          class="text-warning"
        >
          <span class="text-lg font-weight-medium">{{ props.token }}</span>
          <p class="mb-0">
            Om du inte kan skanna QR-koden kan du ange den hemliga nyckeln manuellt nedan.
          </p>
        </VAlert>
        <VForm @submit.prevent="() => {}">
            <AppOtpInput @updateOtp="handleOtp"  class="my-2"/>
          <!-- <AppTextField
            v-model="authCode"
            name="auth-code"
            label="Ingrese el código de autenticación"
            placeholder="123 456"
            class="mb-4"
          /> -->

          <div class="d-flex justify-end flex-wrap gap-3">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="resetAuthCode"
            >
              Avbryt
            </VBtn>

            <VBtn
              type="submit"
              @click="formSubmit"
            >
              {{ props.is_2fa ? 'Aktivera' : 'Avaktivera' }}
              <VIcon
                end
                icon="tabler-arrow-right"
                class="flip-in-rtl"
              />
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
