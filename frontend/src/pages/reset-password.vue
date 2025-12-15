<script setup>
import {
  confirmedValidator,
  passwordValidator,
  requiredValidator,
} from "@/@core/utils/validators";
import { useAuthStores } from "@/stores/useAuth";
import { useDisplay } from "vuetify";
import logo from "@images/logos/billogg-logo.svg";

const route = useRoute();
const router = useRouter();
const authStores = useAuthStores();

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const user = route.query.user;

const load = ref(false);
const refVForm = ref();
const password = ref();
const passwordConfirmation = ref();
const isNewPasswordVisible = ref(false);
const isConfirmPasswordVisible = ref(false);

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

watchEffect(fetchData);

async function fetchData() {
  if (typeof route.query.token !== "undefined") {
    authStores
      .find(route.query.token)
      .then((response) => {})
      .catch((err) => {
        if (err.message === "not_found") {
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
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      load.value = true;

      let data = {
        token: route.query.token,
        password: password.value,
      };

      authStores
        .change(data)
        .then((response) => {
          advisor.value.show = true;
          advisor.value.type = response.success ? "success" : "error";
          advisor.value.message = response.data;

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
            advisor.value.message = err.errors;
          } else {
            advisor.value.show = true;
            advisor.value.type = "error";
            advisor.value.message = "Ett serverfel uppstod. Försök igen.git a";
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

    <div class="d-flex flex-column align-center">
      <VIcon
        icon="custom-f-reset-password"
        size="120"
        class="mx-auto mb-6"
      ></VIcon>

      <h2 class="login-title mb-6">Återställ lösenord</h2>

      <VForm ref="refVForm" @submit.prevent="onSubmit" class="d-flex flex-column gap-4" style="max-width: 442px; width: 100%;">
        <!-- Password -->
        <div class="form-field d-flex flex-column gap-1">
          <label>Nytt lösenord</label>
          <VTextField
            v-model="password"
            placeholder="Nytt lösenord"
            :type="isNewPasswordVisible ? 'text' : 'password'"
            :rules="[requiredValidator, passwordValidator]"
            :append-inner-icon="isNewPasswordVisible ? 'custom-eye' : 'custom-eye-off'"
            @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
          >
            <!-- <template #append-inner>
              <VIcon
                size="22"
                icon="custom-check-mark-filled"
                color="#0FD84E"
              />
            </template> -->
          </VTextField>
        </div>

        <!-- Confirm Password -->
        <div class="form-field d-flex flex-column gap-1">
          <label>Skriv in lösenordet igen</label>
          <VTextField
            v-model="passwordConfirmation"
            placeholder="Bekräfta lösenord"
            :type="isConfirmPasswordVisible ? 'text' : 'password'"
            :rules="[
              requiredValidator,
              confirmedValidator(passwordConfirmation, password),
            ]"
            @click:append-inner="
              isConfirmPasswordVisible = !isConfirmPasswordVisible
            "
            :append-inner-icon="isConfirmPasswordVisible ? 'custom-eye' : 'custom-eye-off'"
          >
            <!-- <template #append-inner>
              <VIcon
                size="22"
                icon="custom-check-mark-filled"
                color="#0FD84E"
              /> -->
            <!-- </template> -->
          </VTextField>
        </div>

        <!-- reset password -->
        <VBtn class="btn-gradient w-100" type="submit">
          Uppdatera lösenord
          <VProgressCircular
              v-if="load"
              indeterminate
              color="#fff"
            />
        </VBtn>

        <!-- back to login -->
        <div class="d-flex justify-center">
          <RouterLink
            class="gray-link d-flex align-center justify-center gap-2"
            :to="{ name: 'login' }"
          >
            <VIcon icon="custom-arrow-left" />
            <span>Återgå till inloggningen</span>
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
