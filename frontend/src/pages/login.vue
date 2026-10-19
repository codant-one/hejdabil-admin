<script setup>

import { useLoginSession } from "@/composables/useLoginSession";
import { useBiometricLogin } from "@/composables/useBiometricLogin";
import { useAlertStore } from "@/stores/useAlerts.js";
import { VForm } from "vuetify/components";
import { emailValidator, requiredValidator } from "@validators";
import { useAuthStores } from "@/stores/useAuth";
import { useDisplay } from "vuetify";
import BiometricLoginDialog from "@/components/dialogs/BiometricLoginDialog.vue";

import VideoLoader from "@/components/common/VideoLoader.vue";
import people from "@images/pages/login/login_image.png";
import logo from "@images/logos/bilflogg-logo.svg";

const alertStore = useAlertStore();
const authStores = useAuthStores();

const isPasswordVisible = ref(false);
const route = useRoute();
const router = useRouter();
const { completeLogin } = useLoginSession();
const biometric = useBiometricLogin();
const biometricAvailable = ref(false);
const biometricEnabled = ref(false);
const biometricDialog = ref();
const shouldShowBiometricButton = computed(
  () => biometricAvailable.value && biometricEnabled.value,
);

const errors = ref({
  email: undefined,
  password: undefined,
});

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const refVForm = ref();
const email = ref("");
const password = ref("");
const remember_me = ref(true);
const loginLoading = ref(false);
const biometricLoading = ref(false);
const DEFAULT_AUTH_LOGOUT_MESSAGE = 'Din session har avslutats. Logga in igen för att fortsätta.';

const showForceLogoutAlert = () => {
  if (route.query.reason !== 'force_logout')
    return

  alertStore.type = 'warning'
  alertStore.message = typeof route.query.message === 'string' && route.query.message.length > 0
    ? route.query.message
    : DEFAULT_AUTH_LOGOUT_MESSAGE
  alertStore.show = true

  const newQuery = { ...route.query }
  delete newQuery.reason
  delete newQuery.message

  router.replace({ query: newQuery })
}

onMounted(async () => {
  showForceLogoutAlert()
  remember_me.value =
    localStorage.getItem("remember_me") === "true" ? true : false;
  email.value = localStorage.getItem("email") ?? "";

  try {
    biometricAvailable.value = await biometric.isAvailable()
    biometricEnabled.value = await biometric.isEnabled()
  } catch (error) {
    alertStore.type = 'error'
    alertStore.message = 'Det gick inte att kontrollera biometrisk inloggning.'
    alertStore.show = true
  }

  if (shouldShowBiometricButton.value)
    localStorage.removeItem("password");
  else
    password.value = localStorage.getItem("password") ?? "";
})

const inputChange = () => {
  errors.value = {
    email: undefined,
    password: undefined,
  };
};

const login = async () => {
  loginLoading.value = true;

  let data = {
    email: email.value,
    password: password.value,
  };

  authStores
    .login(data)
    .then(async (response) => {
      loginLoading.value = false;
      const enteredPassword = password.value;
      password.value = "";
      await completeLogin(response, {
        rememberMe: remember_me.value,
        email: email.value,
        password: enteredPassword,
        clearPassword: shouldShowBiometricButton.value,
        beforeNavigate: () => biometricDialog.value.offer(),
      });
    })
    .catch((err) => {
      loginLoading.value = false;

      if (err.message === "user_inactive") {
        alertStore.type = 'error';
        alertStore.message = err.errors;
        alertStore.show = true;
      } else 
        errors.value = {
          email: err.errors,
          password: "",
        };

      console.error(err.message);
    });
};

const loginWithBiometrics = async () => {
  biometricLoading.value = true;
  try {
    await biometric.login();
  } catch (error) {
    if (error.requiresPassword)
      biometricEnabled.value = false;
    alertStore.type = 'error';
    alertStore.message = error.requiresPassword
      ? error.message
      : error?.message === 'user_inactive'
        ? 'Denna användare har inaktiverats. Kontakta administratören.'
        : 'Biometrisk inloggning misslyckades. Försök igen eller logga in med ditt lösenord.';
    alertStore.show = true;
  } finally {
    biometricLoading.value = false;
  }
};

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) login();
  });
};
</script>

<template>
  <BiometricLoginDialog ref="biometricDialog" />
  <!--<VideoLoader 
    show-once 
    use-local-storage 
    storage-key="hasSeenLoginVideo" 
  />-->

  <VSnackbar
    v-model="alertStore.show"
    transition="scroll-y-reverse-transition"
    :location="snackbarLocation"
    :color="alertStore.type"
    class="snackbar-alert"
  >
    {{ alertStore.message }}
  </VSnackbar> 

  <div class="v-application__wrap login-page d-flex flex-column">
    <VRow no-gutters>
      <div
        class="d-flex flex-column flex-row-md align-center justify-md-center px-6 bg-white flex-1 w-md-50"
      >
        <div class="d-flex logo-box">
          <RouterLink to="/login">
            <img :src="logo" width="121" height="40" />
          </RouterLink>
        </div>
        <div class="d-block w-100 w-md-auto">
          <div class="p-0">
            <h2 class="login-title mb-6">Välkommen till din panel!</h2>
            <VForm ref="refVForm" @submit.prevent="onSubmit" max-width="100%" class="d-flex flex-column gap-4">
              <div class="form-field d-flex flex-column gap-1">
                <label>E-post</label>
                <VTextField
                  class="login"
                  v-model="email"
                  type="email"
                  placeholder="bilflogg@gmail.com"
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                  @input="inputChange()"
                />
              </div>

              <div class="form-field d-flex flex-column gap-1">
                <label>Lösenord</label>
                <VTextField
                  class="login"
                  v-model="password"
                  placeholder="Ange ditt lösenord"
                  :error-messages="errors.password"
                  :rules="[requiredValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="
                    isPasswordVisible ? 'custom-eye' : 'custom-eye-off'
                  "
                  @input="inputChange()"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </div>
              <div
                class="d-flex flex-column flex-md-row align-md-center flex-wrap justify-space-between gap-4 w-100"
              >
                <div
                  class="form-field form-field-checkbox d-flex align-center gap-2"
                >
                  <label class="me-2">Kom ihåg mig</label>
                  <VCheckbox
                    v-model="remember_me"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                  />
                </div>

                <RouterLink
                  class="green-link"
                  :to="{ name: 'forgot-password' }"
                >
                  Har du glömt ditt lösenord?
                </RouterLink>
              </div>

              <VBtn
                block
                class="btn-gradient w-100"
                type="submit"
                :disabled="loginLoading"
              >
                Logga in
                <VProgressCircular
                  v-if="loginLoading"
                  indeterminate
                  color="#fff"
                />
              </VBtn>
              <VBtn
                v-if="shouldShowBiometricButton"
                block
                class="btn-light"
                :loading="biometricLoading"
                :disabled="biometricLoading"
                @click="loginWithBiometrics"
              >
                Logga in med fingeravtryck/Face ID
              </VBtn>
            </VForm>
          </div>
        </div>
      </div>
      <div class="d-none d-md-block w-50 login-background" :style="`background: url(${people});`">
      </div>
    </VRow>
  </div>
</template>

<style scoped lang="scss">
@use "@core/scss/template/pages/page-auth.scss";

.login-page {
  background: #fff;
  //min-height: 100vh;
}

.login-title {
  font-weight: 700;
  font-size: 32px;
  line-height: 56px;
  letter-spacing: 0;
  text-align: center;
  color: #454545;
}

.login-background {
  background-repeat: no-repeat !important;
  background-size: cover !important;
  background-position: bottom !important;
}

@media (max-width: 991px) {
  .logo-box {
    position: relative;
    top: auto;
    left: auto;
    margin-top: 32px;
    margin-bottom: 32px;
  }

  .login-title {
    font-size: 24px;
    line-height: 56px;
  }

  .form-field {
    .v-input {
      &.v-text-field {

        .v-field {
          height: 38px;
        }
      }

      &.v-textarea {
        .v-field {
          --v-textarea-control-height: var(--v-input-control-height) !important;
          height: auto !important;
        }
      }

      .v-field {
        border-radius: 8px;
        background-color: #f6f6f6;
        border: solid 1px #e7e7e7;

        .v-field__field {
          align-items: center;
        }

        .v-field__append-inner {
          padding: 0;
          align-items: center;
          color: #1c2925;
        }

        .v-field__outline {
          display: none;
        }
      }
    }
  }

  .form-field-checkbox {
    label {
      font-weight: 400;
      font-size: 14px;
      line-height: 24px;
    }

    .v-icon {
      width: 24px;
      height: 24px;
    }
  }

  .forgot-pass {
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    color: #0d6e2d;
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
