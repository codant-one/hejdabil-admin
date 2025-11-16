<script setup>

import { useDisplay } from "vuetify";
import { useAuthStores } from "@/stores/useAuth";
import { emailValidator, requiredValidator } from "@validators";
const authStores = useAuthStores();
const router = useRouter();

import logo from "@images/logos/billogg-logo.svg";

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const email = ref("");
const load = ref(false);
const refVForm = ref();

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const errors = ref();

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      load.value = true;

      // ENVIA CONTRASE#A PARA VALIDAR
      let data = { email: email.value };

      authStores
        .forgot_password(data)
        .then((response) => {
          advisor.value.show = true;
          advisor.value.type = response.success ? "success" : "error";
          advisor.value.message = response.data.register_success;

          setTimeout(() => {
            advisor.value.show = false;
            advisor.value.type = "";
            advisor.value.message = "";
            router.push({ name: "login" });
          }, 5000);

          load.value = false;
        })
        .catch((err) => {
          load.value = false;

          if (err.message === "error") {
            advisor.value.show = true;
            advisor.value.type = "error";
            advisor.value.message = err.data.register_success;
          } else if (err.message === "not_found") {
            advisor.value.show = true;
            advisor.value.type = "error";
            advisor.value.message = err.errors;
          } else {
            advisor.value.show = true;
            advisor.value.type = "error";
            advisor.value.message = "Ett fel har inträffat...! (Serverfel)";
          }

          setTimeout(() => {
            advisor.value.show = false;
            advisor.value.type = "";
            advisor.value.message = "";
          }, 5000);

          console.error(err.message);
        });
    }
  });
};
</script>

<template>
  <div class="v-application__wrap bg-gradient d-flex justify-md-center pa-6">
    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert"
    >
      {{ advisor.message }}
    </VSnackbar> 

    <div class="d-flex logo-box mt-2 mt-md-0">
      <RouterLink to="/login">
        <img :src="logo" width="121" height="40" />
      </RouterLink>
    </div>

    <div class="d-flex flex-column align-center">
      <VIcon icon="custom-f-forgot-password" size="120" class="mx-auto mb-6"></VIcon>

      <h2 class="login-title mb-6">Glömt lösenord</h2>

      <p class="mb-6 letter">
        Oroa dig inte, vi skickar instruktionerna till din e-postadress.
      </p>
      <VForm ref="refVForm" @submit.prevent="onSubmit" class="d-flex flex-column gap-4" style="max-width: 442px; width: 100%;">
        <!-- email -->
        <div class="form-field d-flex flex-column gap-1">
          <label>E-post</label>
          <VTextField
            v-model="email"
            placeholder="billogg@gmail.com"
            type="email"
            :rules="[requiredValidator, emailValidator]"
            :error-messages="errors"
          />
        </div>

        <!-- reset password -->
        <VBtn class="btn-gradient w-100" type="submit">
          Skicka återställningslänk
          <VProgressCircular
              v-if="load"
              indeterminate
              color="#fff"
            />
        </VBtn>

        <!-- back to login -->

        <div class="d-flex justify-center gap-4">
          <RouterLink
            class="d-flex align-center justify-center gray-link gap-2"
            :to="{ name: 'login' }"
          >
            <VIcon icon="custom-arrow-left" />
            <span>Återgå till inloggningen</span>
          </RouterLink>

          <RouterLink
            class="d-none align-center justify-center green-link"
            :to="{ name: 'login' }"
          >
            <span>Vidarebefordra</span>
          </RouterLink>
        </div>
      </VForm>
    </div>
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth.scss";

.login-title {
  font-weight: 700;
  font-size: 32px;
  line-height: 100%;
  text-align: center;
  color: #454545;
}

.letter {
  font-weight: 400;
  font-size: 16px;
  line-height: 24px;
  text-align: center;
  color: #878787;
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
