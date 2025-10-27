<script setup>
import { useAppAbility } from "@/plugins/casl/useAppAbility";
import { useAlertStore } from "@/stores/useAlerts.js";
import { VForm } from "vuetify/components";
import { emailValidator, requiredValidator } from "@validators";
import { useAuthStores } from "@/stores/useAuth";

import people from "@images/pages/login/login_image.jpg";
import logo from "@images/logos/billogg-logo.svg";

const alertStore = useAlertStore();
const authStores = useAuthStores();

const isPasswordVisible = ref(false);
const route = useRoute();
const router = useRouter();
const ability = useAppAbility();

const errors = ref({
  email: undefined,
  password: undefined,
});

const refVForm = ref();
const email = ref("");
const password = ref("");
const remember_me = ref(true);
const load = ref(false);

watchEffect(fetchData);

async function fetchData() {
  remember_me.value =
    localStorage.getItem("remember_me") === "true" ? true : false;
  email.value = localStorage.getItem("email") ?? "";
  password.value = localStorage.getItem("password") ?? "";
}

const inputChange = () => {
  errors.value = {
    email: undefined,
    password: undefined,
  };
};

const login = async () => {
  load.value = true;

  let data = {
    email: email.value,
    password: password.value,
  };

  authStores
    .login(data)
    .then((response) => {
      load.value = false;

      const { qr, token, accessToken, user_data, userAbilities } =
        response.data;
      const two_factor = {
        generate_qr: response.message === "2fa-generate" ? true : false,
      };
      const is_2fa = { status: user_data.is_2fa === 0 ? false : true };

      ability.update(userAbilities);

      localStorage.setItem("userAbilities", JSON.stringify(userAbilities));
      localStorage.setItem("user_data", JSON.stringify(user_data));
      localStorage.setItem("accessToken", accessToken);
      localStorage.setItem("qr", qr);
      localStorage.setItem("token", token);
      localStorage.setItem("two_factor", JSON.stringify(two_factor));
      localStorage.setItem("remember_me", remember_me.value);
      localStorage.setItem("is_2fa", JSON.stringify(is_2fa));

      if (remember_me.value) {
        localStorage.setItem("email", email.value);
        localStorage.setItem("password", password.value);
      } else {
        localStorage.setItem("email", "");
        localStorage.setItem("password", "");
      }

      // Redirect to `to` query if exist or redirect to index route
      router.replace(
        route.query.to
          ? String(route.query.to)
          : response.message === "login_success"
          ? "/info"
          : "/"
      );
    })
    .catch((err) => {
      load.value = false;

      errors.value = {
        email: err.errors,
        password: "",
      };

      console.error(err.message);
    });
};

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) login();
  });
};
</script>

<template>
  <div class="d-flex flex-column">
    <VRow no-gutters>
      <VCol
        cols="12"
        md="6"
        class="d-flex flex-column flex-row-md align-center justify-center px-6 bg-white"
      >
        <div class="d-flex logo-box">
          <img :src="logo" width="121" height="40" />
        </div>
        <div class="d-block">
          <VAlert v-if="alertStore.message" :type="alertStore.type">
            {{ alertStore.message }}
          </VAlert>
          <VCardText class="p-0">
            <h2 class="login-title mb-6">Välkommen till din panel!</h2>
            <VForm ref="refVForm" @submit.prevent="onSubmit" class="auth-form">
              <div class="form-field d-flex flex-column gap-4 mb-6">
                <label>E-post</label>
                <VTextField
                  class="login"
                  v-model="email"
                  type="email"
                  placeholder="billogg@gmail.com"
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                  @input="inputChange()"
                />
              </div>

              <div class="form-field d-flex flex-column gap-4 mb-6">
                <label>Lösenord</label>
                <VTextField
                  class="login"
                  v-model="password"
                  placeholder="Ange ditt lösenord"
                  :error-messages="errors.password"
                  :rules="[requiredValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="
                    isPasswordVisible ? 'custom-eye' : 'custom-eye'
                  "
                  @input="inputChange()"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </div>
              <div
                class="d-flex align-center flex-wrap justify-space-between mb-6 gap-6"
              >
                <div
                  class="form-field form-field-checkbox d-flex align-center gap-2"
                >
                  <label>Kom ihåg mig</label>
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

              <VBtn class="btn-gradient w-100" type="submit">
                Logga in
              </VBtn>
            </VForm>
          </VCardText>
        </div>
      </VCol>
      <VCol cols="12" md="6" class="d-none d-lg-flex">
        <img :src="people" class="w-100 login-background" />
      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth.scss";

.login-title {
  font-weight: 700;
  font-size: 32px;
  line-height: 56px;
  letter-spacing: 0%;
  text-align: center;
  color: #454545;
}

.login-background {
  height: 100vh;
  object-fit: cover;
}

@media (max-width: 991px) {
  .logo-box {
    position: relative;
    top: auto;
    left: auto;
    margin-top: 84px;
    margin-bottom: 41px;
  }

  .login-title {
    font-size: 24px;
    line-height: 56px;
  }

  .form-field {
    label {
    }
    .v-input {
      &.v-text-field {
        min-width: auto;
        max-width: 345px;
      }
      .v-field {
        height: 48px;
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
      font-size: 16px;
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

  .login-background {
    height: 100vh;
    object-fit: cover;
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
