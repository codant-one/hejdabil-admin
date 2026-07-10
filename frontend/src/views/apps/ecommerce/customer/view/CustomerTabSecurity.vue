<script setup>

import { confirmedValidator, passwordValidator, requiredValidator } from '@/@core/utils/validators'
import { useUsersStores } from '@/stores/useUsers'

const props = defineProps({
  user_id: {
    type: Number,
    required: true
  }
})

const { width: windowWidth } = useWindowSize();
const usersStores = useUsersStores()

const refForm  = ref()
const password = ref()
const passwordConfirmation = ref()
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const emit = defineEmits([
  'alert',
  'loading'
])


const onSubmit = () => {
  refForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      let data = {
        password: password.value
      }

      emit("loading", true);

      usersStores.updatePasswordUser(data, props.user_id)
        .then(response => {
          window.scrollTo(0, 0)

          advisor.value.show = true
          advisor.value.type = 'success'
          advisor.value.message = 'Lösenord ändrat'
                    
          emit("loading", false);
          emit('alert', advisor)

          nextTick(() => {
            refForm.value?.reset()
            refForm.value?.resetValidation()
            password.value = null
            passwordConfirmation.value = null
          })

          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
            emit('alert', advisor)
          }, 5000)

      })
    }
  })
}
</script>

<template>
  <VCard class="security">
    <VCardText class="px-0 pb-0">
      <div class="title-tabs">
        Ändra lösenord
      </div>
    </VCardText>
    <VCardText class="px-0 pb-0">
      <VAlert
        class="mb-4 px-4 pb-3 alert"
        :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
      >
        <VAlertTitle class="mb-2">
          Se till att dessa krav är uppfyllda
        </VAlertTitle>
        <ul id="warning_card" class="list-style ms-4">
          <li class="items-alert">Minst 8 tecken</li>
          <li class="items-alert">Stora och små bokstäver</li>
          <li class="items-alert">Minst en siffra</li>
        </ul>
      </VAlert>

      <VForm
        ref="refForm"
        class="card-form"
        validate-on="submit"
        @submit.prevent="onSubmit"
        >
        <div 
            class="d-flex flex-wrap"
            :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
            :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
        >
          <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Nytt lösenord*" />
            <VTextField
              v-model="password"
              :type="isNewPasswordVisible ? 'text' : 'password'"
              :append-inner-icon="isNewPasswordVisible ? 'custom-eye-off' : 'custom-eye'"
              :rules="[requiredValidator, passwordValidator]"
              @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
              />
          </div>
          <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bekräfta lösenord*" />
            <VTextField
              v-model="passwordConfirmation"
              :type="isConfirmPasswordVisible ? 'text' : 'password'"
              :append-inner-icon="isConfirmPasswordVisible ? 'custom-eye-off' : 'custom-eye'"
              :rules="[requiredValidator, confirmedValidator(passwordConfirmation, password)]"
              @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
              />
          </div>

          <div class="mt-4" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
            <VBtn
              type="submit"
              :block="windowWidth < 1024"
              class="btn-gradient"
              :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
            >
              Ändra lösenord
            </VBtn>
          </div>
        </div>
      </VForm>
    </VCardText>
  </VCard>
</template>

<style lang="scss">

  .title-tabs {
      font-weight: 700;
      font-size: 20px;
      line-height: 100%;
      color: #454545;

      @media (max-width: 1023px) {
          font-size: 20px
      }
  }

  .alert.v-alert {
    border-radius: 8px !important;
    border: 1px solid #FFEC88 !important;
    background-color: #FFFCEB !important;
    box-shadow: none !important;
    color: #94430C !important;
  }

  .alert.v-alert {
    border-radius: 8px !important;
    border: 1px solid #FFEC88 !important;
    background-color: #FFFCEB !important;
    box-shadow: none !important;
    color: #94430C !important;
  }

  .items-alert {
      font-size: 12px;
      line-height: 100%;
      letter-spacing: 0px;
      margin-top: 4px;
  }

  .card-form {
        .v-input {
            .v-input__control {
                .v-field {
                    background-color: #f6f6f6 !important;
                    min-height: 48px !important;

                    .v-text-field__suffix {
                        padding: 12px 16px !important;
                    }

                    .v-field__input {
                        min-height: 48px !important;
                        padding: 12px 16px !important;

                        input {
                            min-height: 48px !important;
                        }
                    }

                    .v-field-label {
                        top: 12px !important;
                    }

                    .v-field__append-inner {
                        align-items: center;
                        padding-top: 0px;
                    }

                    .v-text-field__prefix {
                        height: 48px;
                        color: #33303CAD;
                    }
                }
            }
        }

        .v-input.always-show-prefix {
            .v-input__control {
                .v-field {
                    .v-field__input {
                        padding: 8px 0 !important;
                    }
                }
            }
        }

        .selector-country {
            .v-input__prepend {
                margin-inline-end: 6px !important;
            }
        }

        .v-select .v-field,
        .v-autocomplete .v-field {
            .v-select__selection,
            .v-autocomplete__selection {
                align-items: center;
            }

            .v-field__input > input {
                top: 0px;
                left: 0px;
            }
        }
    }

</style>

<style scope>
    .security.v-card--variant-elevated {
        box-shadow: none !important;
    }

    ol.list-style, ul.list-style {
      list-style: disc !important;
    }
</style>
