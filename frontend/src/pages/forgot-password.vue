<script setup>

import { useAuthStores } from '@/stores/useAuth'
import { emailValidator, requiredValidator } from '@validators'
import authV1BottomShape from '@images/pages/block-1.png'
import authV1TopShape from '@images/pages/block-1.png'
const authStores = useAuthStores()
const router = useRouter()

const email = ref('')
const load = ref(false)
const refVForm = ref()

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const errors = ref()

const onSubmit = () => {

  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      load.value = true

      // ENVIA CONTRASE#A PARA VALIDAR 
      let data = { email: email.value }

      authStores.forgot_password(data)
        .then(response => {

          advisor.value.show = true
          advisor.value.type = response.success ? 'success' : 'error'
          advisor.value.message = response.data.register_success
    
          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
            router.push({ name: 'login' })
          }, 5000)

          load.value = false

        }).catch(err => {

          load.value = false

          if(err.message === 'error') {
            advisor.value.show = true
            advisor.value.type = 'error'
            advisor.value.message = err.data.register_success
          } else if(err.message === 'not_found'){
            advisor.value.show = true
            advisor.value.type = 'error'
            advisor.value.message = err.errors
          } else {
            advisor.value.show = true
            advisor.value.type = 'error'
            advisor.value.message = 'Ett fel har inträffat...! (Serverfel)'
          }

          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
          }, 5000)

          console.error(err.message)
        })
      }
    })
}

</script>

<template>
  <div class="auth-wrapper-2fa d-flex align-center justify-center pa-4">
    <div class="position-relative my-sm-16">
      <!-- 👉 Top shape -->
      <VImg
        :src="authV1TopShape"
        class="auth-v1-top-shape d-none d-sm-block"
      />

      <!-- 👉 Bottom shape -->
      <VImg
        :src="authV1BottomShape"
        class="auth-v1-bottom-shape d-none d-sm-block"
      />

      <VAlert
        v-if="advisor.show"
        :type="advisor.type"
        class="mb-6"
      >
        {{ advisor.message }}
      </VAlert>

      <div class="d-block">
        <!-- 👉 Auth card -->
        <VCard
          class="auth-card auth pa-4"
          max-width="448"
        >
          <VCardText class="pt-2 px-2 px-md-6">
            <h5 class="text-h5 font-weight-semibold mb-1">
              Har du glömt ditt lösenord? 🔒
            </h5>
            <p class="mb-0 letter">
              Ange din e-postadress så skickar vi en länk till dig för att återställa ditt lösenord.
            </p>
          </VCardText>

          <VCardText class="px-2 px-md-6 pb-5">
            <VForm 
              ref="refVForm"
              @submit.prevent="onSubmit">
              <VRow>
                <!-- email -->
                <VCol cols="12">
                  <VTextField
                    v-model="email"
                    label="E-post"
                    type="email"
                    :rules="[requiredValidator, emailValidator]"
                    :error-messages="errors"
                  />
                </VCol>

                <!-- reset password -->
                <VCol cols="12">
                  <VBtn
                    block
                    type="submit"
                  >
                    Skicka
                    <VProgressCircular
                      v-if="load"
                      indeterminate
                      color="#fff"
                    />
                  </VBtn>
                </VCol>

                <!-- back to login -->
                <VCol cols="12">
                  <RouterLink
                    class="d-flex align-center justify-center"
                    :to="{ name: 'login' }"
                  >
                    <VIcon
                      icon="tabler-chevron-left"
                      class="flip-in-rtl"
                    />
                    <span>Tillbaka till inloggning</span>
                  </RouterLink>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
    @use "@core/scss/template/pages/page-auth.scss";

    .auth .v-card-item__prepend {
        padding-inline-end: 0 !important;
    }

    .auth .v-card-item {
        padding: 0 24px !important;
    }

    @media(max-width: 991px){
      .auth .v-card--variant-elevated {
        box-shadow: none !important;
      }

      .text-h5 {
        font-size: 1.2rem !important;
      }
      
      .letter, .v-selection-control--inline .v-label {
        font-size: 11.5px !important;
      }
    }
</style>

<route lang="yaml">
    meta:
      layout: blank
      action: view
      subject: Auth
      redirectIfLoggedIn: false
</route>
