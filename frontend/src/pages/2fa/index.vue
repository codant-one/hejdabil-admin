<script setup>

import { useAuthStores } from "@/stores/useAuth";

import logo from "@images/logos/billogg-logo.svg";

const authStores = useAuthStores();
const route = useRoute();
const router = useRouter();

const load = ref(false);
const otp = ref("");

const handleOtp = (value) => {
  otp.value = value;
};

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const onSubmit = () => {
  if (otp.value.length === 6) {
    load.value = true;

    let data = {
      panel: false,
      token_2fa: otp.value,
      token: localStorage.getItem("token"),
    };

    authStores
      .validate(data)
      .then((response) => {
        // Redirect to `to` query if exist or redirect to index route
        router.replace(route.query.to ? String(route.query.to) : "/info");
      })
      .catch((err) => {
        load.value = false;

        if (err.message === "invalid_code") {
          advisor.value.show = true;
          advisor.value.type = "error";
          advisor.value.message = err.errors;
        }

        setTimeout(() => {
          advisor.value.show = false;
          advisor.value.type = "";
          advisor.value.message = "";
        }, 5000);

        console.error(err.message);
      });
  }
};
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
      <VIcon
        icon="custom-f-two-factor-auth"
        size="120"
        class="mx-auto"
      ></VIcon>

      <h2 class="login-title">Autentiserare</h2>

      <p class="letter">Ange din 6-siffriga säkerhetskod</p>

      <VForm @submit.prevent="onSubmit" class="auth-form d-flex flex-column gap-6">
        <div class="form-field form-field-2fa d-flex flex-column gap-4">
          <AppOtpInput @updateOtp="handleOtp" />
        </div>

        <!-- reset password -->
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

        <!-- back to login -->
        <div class="d-none justify-center">
          <RouterLink
            class="gray-link d-flex align-center justify-center gap-2"
            :to="{ name: 'login' }"
          >
            <span>Fick du inte verifieringskoden? <span class="text-underline">Lorem Ipsum</span></span>
          </RouterLink>
        </div>
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
  letter-spacing: 0%;
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
