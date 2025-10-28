<script setup>

import { useAuthStores } from '@/stores/useAuth'
import QRCode from 'qrcode-generator';

import logo from "@images/logos/billogg-logo.svg";

const authStores = useAuthStores()
const route = useRoute()
const router = useRouter()

const load = ref(false)
const otp = ref('')

const token  = localStorage.getItem('token')
const otpauthUri  = localStorage.getItem('qr')

const typeNumber = 0;
const errorCorrectionLevel = 'L';
const qr = QRCode(typeNumber, errorCorrectionLevel);
qr.addData(otpauthUri);
qr.make();

const qrCodeDataUrl = ref(qr.createDataURL(4))

const handleOtp = (value) => {
    otp.value = value
}

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const onSubmit = () => {

    if (otp.value.length === 6) {
        load.value = true
        
        let data = {
          panel: false,
          token_2fa: otp.value,
          token: localStorage.getItem('token')
        }

        authStores.validate(data)
            .then(response => {
                // Redirect to `to` query if exist or redirect to index route
                router.replace(route.query.to ? String(route.query.to) : '/info')
            }).catch(err => {

                load.value = false

                if(err.message === 'invalid_code'){
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

</script>

<template>
  <div class="v-application__wrap bg-gradient d-flex justify-md-center pa-6">
    <VAlert 
      v-if="advisor.message" 
      border="start" 
      :border-color="advisor.type === 'error' ? 'error' : 'success'"
      class="mb-5 flex-grow-0">
      <div v-html="advisor.message"></div>
    </VAlert>

    <div class="d-flex logo-box mt-2 mt-md-0">
      <RouterLink to="/login">
        <img :src="logo" width="121" height="40" />
      </RouterLink>
    </div>

    <div class="d-flex flex-column align-center text-center box-2fa gap-3">
       <img :src="qrCodeDataUrl"  width="200" height="200" class="mx-auto"/>
         
       <h2 class="login-title">
        Skanna QR-koden
       </h2>
      <p class="letter">
        Alternativt kan du använda koden <strong>{{ token }}</strong>.
      </p>

      <VForm
        class="auth-form d-flex flex-column gap-6"
        @submit.prevent="onSubmit">
        <VRow>
          <!-- email -->
          <VCol cols="12">
            <AppOtpInput @updateOtp="handleOtp"/>
          </VCol>

          <!-- reset password -->
          <VCol cols="12">
            <VBtn
              block
              type="submit"
              class="btn-gradient w-100"
            >
              Send
              <VProgressCircular
                v-if="load"
                indeterminate
                color="#fff"
              />
            </VBtn>
          </VCol>
        </VRow>
      </VForm>
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

      .letter, .v-selection-control--inline .v-label {
        font-size: 11.5px !important;
      }

      .v-selection-control__wrapper {
        width: 28px !important;
        margin-left: 4px;
      }
      
      .text-h5 {
        font-size: 1.25rem !important;
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
