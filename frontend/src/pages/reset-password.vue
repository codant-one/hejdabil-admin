<script setup>

import { confirmedValidator, passwordValidator, requiredValidator } from '@/@core/utils/validators'
import { useAuthStores } from '@/stores/useAuth'
import authV1BottomShape from '@images/pages/block-1.png'
import authV1TopShape from '@images/pages/block-1.png'

const route = useRoute()
const router = useRouter()
const authStores = useAuthStores()

const user = route.query.user

const load = ref(false)
const refVForm = ref()
const password = ref()
const passwordConfirmation = ref()
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)

const advisor = ref({
    type: '',
    message: '',
    show: false
})

watchEffect(fetchData)

async function fetchData() {

  if(typeof route.query.token !== 'undefined') {
    authStores.find(route.query.token)
      .then(response => {
      }).catch(err => {

          if(err.message === 'not_found'){
              advisor.value.show = true
              advisor.value.type = 'error'
              advisor.value.message = err.errors
          }

          setTimeout(() => {
              advisor.value.show = false
              advisor.value.type = ''
              advisor.value.message = ''
          }, 5000)

          console.error(err.message)
      })
  }
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
        load.value = true

        let data = {
            token: route.query.token,
            password: password.value
        }

        authStores.change(data)
            .then(response => {

                advisor.value.show = true
                advisor.value.type = response.success ? 'success' : 'error'
                advisor.value.message = response.data

                setTimeout(() => {
                    advisor.value.show = false
                    advisor.value.type = ''
                    advisor.value.message = ''
                    router.push({ name: 'login' })
                }, 5000)

                load.value = false                    
                
            }).catch(err => {

                load.value = false

                if(err.message === 'error'){
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
              Återställ lösenord 🔒
            </h5>
            <p class="mb-0 letter">
              för <span class="font-weight-bold">{{ user }}</span>
            </p>
          </VCardText>

          <VCardText class="px-2 px-md-6 pb-5">
            <VForm 
              ref="refVForm"
              @submit.prevent="onSubmit">
              <VRow>
                <!-- password -->
                <VCol cols="12" class="pb-0">
                  <VTextField
                    v-model="password"
                    label="Nytt lösenord"
                    :type="isNewPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                    :rules="[requiredValidator, passwordValidator]"
                    @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                  />
                </VCol>

                <!-- Confirm Password -->
                <VCol cols="12">
                  <VTextField
                    v-model="passwordConfirmation"
                    label="Bekräfta lösenord"
                    :type="isConfirmPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                    :rules="[requiredValidator, confirmedValidator(passwordConfirmation, password)]"
                    @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
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
        font-size: 1.15rem !important;
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
