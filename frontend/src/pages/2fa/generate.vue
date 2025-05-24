<script setup>
import { useAuthStores } from '@/stores/useAuth'
import authV1BottomShape from '@images/pages/block-1.png'
import authV1TopShape from '@images/pages/block-1.png'
import QRCode from 'qrcode-generator';

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
          <VCardText class="px-2 px-md-6 pb-5">
              <span class="d-flex justify-center"> 
                  <img :src="qrCodeDataUrl"/>
              </span>
            <h5 class="text-h5 font-weight-semibold mb-1">
              Scan the QR code 💬
            </h5>
            <p class="mb-1 letter">
              Alternatively, you can use the code <strong>{{ token }}</strong>.
            </p>
          </VCardText>

          <VCardText class="px-2 px-md-6">
            <VForm
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
