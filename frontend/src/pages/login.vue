<script setup>

import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useAlertStore } from '@/stores/useAlerts.js'
import { VForm } from 'vuetify/components'
import { emailValidator, requiredValidator } from '@validators'
import { useAuthStores } from '@/stores/useAuth'

import people from '@images/pages/people-1.png'
import logo from '@images/logo.svg'
import favicon from '@images/favicon@2x.png'

const alertStore = useAlertStore()
const authStores = useAuthStores()

const isPasswordVisible = ref(false)
const route = useRoute()
const router = useRouter()
const ability = useAppAbility()

const errors = ref({
  email: undefined,
  password: undefined,
})

const refVForm = ref()
const email = ref('')
const password = ref('')
const remember_me = ref(true)
const load = ref(false)

watchEffect(fetchData)

async function fetchData() {

  remember_me.value = (localStorage.getItem('remember_me') === 'true') ? true : false
  email.value = localStorage.getItem('email') ?? ''
  password.value = localStorage.getItem('password') ?? ''
  
}

const inputChange = () => {
  errors.value = {
    email: undefined, 
    password: undefined
  }
}

const login = async () => {
  load.value = true

  let data = {
    email: email.value,
    password: password.value,
  }

  authStores.login(data)
    .then(response => {
      load.value = false

      const { qr, token, accessToken, user_data, userAbilities } = response.data     
      const two_factor = { generate_qr: (response.message === '2fa-generate') ? true : false }       
      const is_2fa = { status: (user_data.is_2fa === 0) ? false : true } 

      ability.update(userAbilities)

      localStorage.setItem('userAbilities', JSON.stringify(userAbilities))      
      localStorage.setItem('user_data', JSON.stringify(user_data))
      localStorage.setItem('accessToken', accessToken)     
      localStorage.setItem('qr', qr)
      localStorage.setItem('token', token)
      localStorage.setItem('two_factor', JSON.stringify(two_factor))      
      localStorage.setItem('remember_me', remember_me.value);
      localStorage.setItem('is_2fa', JSON.stringify(is_2fa));

      if(remember_me.value){
        localStorage.setItem('email', email.value);
        localStorage.setItem('password', password.value);
      } else {
        localStorage.setItem('email','');
        localStorage.setItem('password',''); 
      }

      // Redirect to `to` query if exist or redirect to index route
      router.replace(route.query.to ? String(route.query.to) : (response.message === 'login_success' ? '/info': '/'))

    }).catch(err => {

      load.value = false

      errors.value = {
        email: err.errors, 
        password: ''
      }

      console.error(err.message)
    })
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      login()
  })
}
</script>

<template>

  <div class="auth-wrapper d-flex flex-column">
    <div class="bg-primary text-center text-title py-1">
      Administrativ panel
    </div>
    <div class="bg-secondary text-center d-flex justify-content-center align-center" style="height: 70px;">
      <img
        :src="logo"
        class="my-auto"
        width="167"
        height="38"
      />
    </div>
    <VRow no-gutters>
      <VCol
        cols="12"
        md="6"
        class="d-none d-lg-flex"
      >
        <div class="position-relative ms-15 me-0 mb-0 w-100">
          <div class="d-flex align-center justify-center">
            <img
              :src="people"
              class="auth-illustration"
              width="600"
            />
          </div>
        </div>
      </VCol>
      <VCol
        cols="12"
        md="6"
        class="d-flex align-center justify-center px-5"
      >
        <div class="d-block">
          <VCard
            flat
            :max-width="400"
            class="pa-5 card-auth"
          >
            <VAlert
              v-if="alertStore.message"
              :type="alertStore.type"
            >
              {{ alertStore.message }}
            </VAlert>
            <VCardText>    
              <img :src="favicon" />        
              
              <h5 class="text-h5 font-weight-semibold mb-1 d-block">
                Välkommen till din panel! 👋🏻
              </h5>
              <p class="mb-0">
                Logga in på ditt konto
              </p>
            </VCardText>
            <VCardText>
              <VForm
                ref="refVForm"
                @submit.prevent="onSubmit"
              >
                <VRow>
                  <!-- email -->
                  <VCol cols="12" class="pb-0">
                    <VTextField
                      class="login"
                      v-model="email"
                      label="E-post"
                      type="email"
                      :rules="[requiredValidator, emailValidator]"
                      :error-messages="errors.email"
                      @input="inputChange()"
                    />
                  </VCol>

                  <!-- password -->
                  <VCol cols="12">
                    <VTextField
                      class="login"
                      v-model="password"
                      label="Lösenord"
                      :error-messages="errors.password"
                      :rules="[requiredValidator]"
                      :type="isPasswordVisible ? 'text' : 'password'"
                      :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                      @input="inputChange()"
                      @click:append-inner="isPasswordVisible = !isPasswordVisible"
                    />

                    <div class="d-flex align-center flex-wrap justify-space-between mt-2 mb-4">
                      <VCheckbox
                        v-model="remember_me"
                        label="Kom ihåg mig"
                        class="letter"
                      />
                  
                        <RouterLink
                          class="text-primary letter"
                          :to="{ name: 'forgot-password' }"
                        >
                          Har du glömt ditt lösenord?
                        </RouterLink>
                    </div>

                    <VBtn
                      block
                      type="submit"
                    > 
                      Logga in
                      <VProgressCircular
                        v-if="load"
                        indeterminate
                        color="#fff"
                      />
                    </VBtn>
                  </VCol>
                </VRow>
              </VForm>
            </VCardText>
          </VCard>
        </div>
      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss">
  @use "@core/scss/template/pages/page-auth.scss";

  .text-title {
    color: #FFFFFF !important;
    font-family: "Roboto", Sans-serif;
    font-size: 12px;
    font-weight: 600;
  }

  .justify-content-center {
    justify-content: center !important;
  }

  .card-auth {
    height: 450px;
  }

  @media(max-width: 991px){
    .card-auth {
      height: auto;
    }  

    .v-card-text {
      padding: 10px !important;
    }

    .letter, .v-selection-control--inline .v-label {
      font-size: 11.5px !important;
    }

    .v-selection-control__wrapper {
      width: 28px !important;
      margin-left: 4px;
    }
  }
</style>

<route lang="yaml">
meta:
  layout: blank
  action: view
  subject: Auth
  redirectIfLoggedIn: true
</route>
