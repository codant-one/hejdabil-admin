<script setup>

import { useAuthStores } from '@/stores/useAuth'
import { useDisplay } from "vuetify";
import QRCode from 'qrcode-generator';

import logo from "@images/logos/billogg-logo.svg";

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const authStores = useAuthStores()
const route = useRoute()
const router = useRouter()

const load = ref(false)
const otp = ref('')
const otpInputKey = ref(0)

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
            localStorage.removeItem('is_2fa')
            localStorage.removeItem('two_factor')
            localStorage.removeItem('token')
            localStorage.removeItem('qr')

                // Redirect to `to` query if exist or redirect to index route
                router.replace(route.query.to ? String(route.query.to) : '/info')
            }).catch(err => {

                load.value = false
                otp.value = ''
                otpInputKey.value += 1

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
  <VSnackbar
    v-model="advisor.show"
    transition="scroll-y-reverse-transition"
    :location="snackbarLocation"
    :color="advisor.type"
    class="snackbar-alert"
  >
    {{ advisor.message }}
  </VSnackbar> 

  <div class="v-application__wrap bg-gradient d-flex justify-md-center pa-6">
    <div class="d-flex logo-box mt-2 mt-md-0">
      <RouterLink to="/login">
        <img :src="logo" width="121" height="40" />
      </RouterLink>
    </div>

    <div class="d-flex flex-column align-center text-center box-2fa gap-3">
      <img :src="qrCodeDataUrl"  width="200" height="200" class="mx-auto"/>
         
      <h2 class="login-title">Skanna QR-koden</h2>

      <p class="letter">
        Alternativt kan du använda koden <strong>{{ token }}</strong>.
      </p>

      <VForm
        class="auth-form d-flex flex-column gap-6"
        @submit.prevent="onSubmit">

        <div class="form-field form-field-2fa d-flex flex-column align-center gap-4">
          <AppOtpInput :key="otpInputKey" @updateOtp="handleOtp" />
        </div>

        <VBtn 
          class="btn-gradient w-100" 
          type="submit"
          :loading="load"
          >Skicka
            <VProgressCircular
              v-if="load"
              indeterminate
              color="#fff"
            />
        </VBtn>
      </VForm>
    </div>
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth.scss";

.box-2fa {
  max-width: 442px;
  width: 100%;
  margin: 0 auto;
}

.login-title {
  font-weight: 700;
  font-size: 32px;
  line-height: 56px;
  letter-spacing: 0;
  text-align: center;
  color: #454545;
}

@media (max-width: 991px) {
  .logo-box {
    position: relative;
    top: auto;
    left: auto;
    margin-top: 32px;
    margin-bottom: 32px;
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
