<script setup>

import { confirmedValidator, passwordValidator, requiredValidator } from '@/@core/utils/validators'
import { useProfileStores } from '@/stores/useProfile'
import { useAuthStores } from '@/stores/useAuth'
import AddAuthenticatorAppDialog from "@/components/dialogs/AddAuthenticatorAppDialog.vue";
import QRCode from 'qrcode-generator';

const profileStores = useProfileStores()
const authStores = useAuthStores()

const refVForm = ref()
const password = ref()
const passwordConfirmation = ref()
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const isRequestOngoing = ref(true)

const isDialogVisible = ref(false)
const is_2fa = ref(false)
const data = ref(null)
const qr = ref(null)
const token = ref(null)

const advisor = ref({
    message: '',
    show: false,  
    type: '',
})

const emit = defineEmits([
  'alert'
])

const enabled2fa = () => {
  isDialogVisible.value = true
}

watchEffect(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  data.value = await authStores.generateQR()

  const typeNumber = 0;
  const errorCorrectionLevel = 'L';
  const qr_ = QRCode(typeNumber, errorCorrectionLevel);
  qr_.addData(data.value.qr);
  qr_.make();

  qr.value = qr_.createDataURL(4)
  token.value = data.value.token
  is_2fa.value = data.value.is_2fa


  isRequestOngoing.value = false
}

const chance2fa = (code) => {

  let data = {
    panel: true,
    token_2fa: code,
    token: token.value
  }

  isRequestOngoing.value = true

  authStores.validate(data)
    .then(response => {
      advisor.value.show = true
      advisor.value.message = 'Updated information'
      advisor.value.type = 'success' 
      
      emit('alert', advisor)

      isRequestOngoing.value = false
    }).catch(err => {

      window.scrollTo(0, 0)

      if(err.message === 'invalid_code'){
        advisor.value.show = true
        advisor.value.type = 'error'
        advisor.value.message = err.errors
      }

      console.error(err.message)
      emit('alert', advisor)

      isRequestOngoing.value = false
    })

  setTimeout(() => {
    advisor.value.show = false
    advisor.value.type = ''
    advisor.value.message = ''
    emit('alert', advisor)
  }, 5000)

  fetchData()
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if(isValid) {
            
      let data = { password: password.value}

      isRequestOngoing.value = true

      profileStores.updatePassword(data)
        .then(response => {

          window.scrollTo(0, 0)

          advisor.value.show = true
          advisor.value.message = 'Password changed'
          advisor.value.type = 'success'

          emit('alert', advisor)

          localStorage.setItem('user_data', JSON.stringify(response.user_data))
                
          password.value = undefined
          passwordConfirmation.value = undefined

          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
            emit('alert', advisor)
          }, 5000)

          isRequestOngoing.value = false

        }).catch(error =>{

          window.scrollTo(0, 0)

          advisor.value.show = true
          advisor.value.message = 'An error has occurred...! (Server Error)'
          advisor.value.type = 'error'
                
          emit('alert', advisor)

          password.value = undefined
          passwordConfirmation.value = undefined
                
          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''    
            emit('alert', advisor)  
          }, 5000)

          isRequestOngoing.value = false
        })
    }
  })
}

</script>

<template>
  <section>
    <VDialog
      v-model="isRequestOngoing"
      width="300"
      persistent>
      <VCard
        color="primary"
        width="300">
        <VCardText class="pt-3">
          Loading
          <VProgressLinear
            indeterminate
            color="white"
            class="mb-0"
          />
        </VCardText>
      </VCard>
    </VDialog>

    <VRow>
      <VCol cols="12" class="pb-0">
        <VCard title="Change password">
          <VCardText>
            <VAlert
              variant="tonal"
              color="warning"
              class="mb-4"
            >
              <VAlertTitle class="mb-1">
                Make sure these requirements are met
              </VAlertTitle>
              <span>Minimum 8 characters, uppercase, lowercase and numbers</span>
            </VAlert>

            <VForm
              ref="refVForm"
              @submit.prevent="onSubmit"
            >
              <VRow>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="password"
                    label="New password"
                    :type="isNewPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                    :rules="[requiredValidator, passwordValidator]"
                    @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="passwordConfirmation"
                    label="Confirm password"
                    :type="isConfirmPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                    :rules="[requiredValidator, confirmedValidator(passwordConfirmation, password)]"
                    @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                  />
                </VCol>

                <VCol cols="12">
                  <VBtn type="submit">
                    Change password
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
        <VCard title="Enable" class="mt-5">
          <VCardText>
            <VTable class="text-no-wrap rounded border">
              <thead>
                <tr>
                  <th scope="col">
                    TYPE
                  </th>
                  <th scope="col"></th>
                  <th scope="col"></th>
                  <th scope="col">ENABLE</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td> Two-factor authentication (2FA)  </td>
                  <td> </td>
                  <td> </td>
                  <td>
                    <VCheckbox 
                      v-model="is_2fa"
                      @update:model-value="enabled2fa" />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <AddAuthenticatorAppDialog
      v-model:isDialogVisible="isDialogVisible"
      :qr="qr"
      :token="token"
      :is_2fa="is_2fa"
      @submit="chance2fa"
      @close="fetchData"
    />
  </section>
</template>
