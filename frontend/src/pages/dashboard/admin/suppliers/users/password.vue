<script setup>

import { requiredValidator } from '@/@core/utils/validators'
import { useUsersStores } from '@/stores/useUsers'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  user: {
    type: Object,
    required: true
  }

})

const usersStores = useUsersStores()

const emit = defineEmits([
  'update:isDrawerOpen',
  'alert',
  'data'
])

const refForm = ref()

const id = ref('')
const email = ref('')
const password = ref('')
const isPasswordVisible = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

watchEffect(() => {
    if (props.isDrawerOpen) {

        if (!(Object.entries(props.user).length === 0) && props.user.constructor === Object) {
            id.value = props.user.id
            email.value = props.user.email
        }
    }
})

const closeUserPasswordDialog = function(){
    emit('update:isDrawerOpen', false)

    nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
        password.value = ''
    })
}

const editUserPassword = function(){

    refForm.value?.validate().then(({ valid: isValid }) => {
        if (isValid) {
            let data = {
                password: password.value
            }
            //UPDATE USER PASSWORD
            usersStores.updatePasswordUser(data, id.value)
                .then(response => {
                    window.scrollTo(0, 0)

                    advisor.value.show = true
                    advisor.value.type = 'success'
                    advisor.value.message = 'Lösenord ändrat'
                    
                    emit('update:isDrawerOpen', false)
                    emit('alert', advisor)
                    emit('data')

                    nextTick(() => {
                        refForm.value?.reset()
                        refForm.value?.resetValidation()
                        password.value = ''
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
    <!-- DIALOG -->
    <VDialog
      v-model="props.isDrawerOpen"
      class="action-dialog"
      max-width="600"
      persistent
    >
        <!-- Dialog close btn -->
         <VBtn
            icon
            class="btn-white close-btn"
            @click="closeUserPasswordDialog"
            >
            <VIcon size="16" icon="custom-close" />
        </VBtn>

        <!-- Dialog Content -->
        <VForm
            ref="refForm"
            @submit.prevent="editUserPassword"
        >
            <VCard flat class="card-form">
                <VCardText class="dialog-title-box">
                <VIcon size="32" icon="custom-password" class="action-icon" />
                
                <div class="dialog-title">
                    Redigera användarens lösenord
                </div>
                </VCardText>
                <VCardText class="dialog-text">
                    <VLabel
                        class="mb-1 text-body-2 text-high-emphasis"
                        text="E-post"
                        />
                    <VTextField
                        v-model="email"
                        readonly
                    />
                </VCardText>
                <VCardText class="dialog-text mt-4">
                    <VLabel
                        class="mb-1 text-body-2 text-high-emphasis"
                        text="Nytt lösenord*"
                        />
                    <VTextField
                        v-model="password"
                        :type="isPasswordVisible ? 'text' : 'password'"
                        :rules="[requiredValidator]"
                        :append-inner-icon="isPasswordVisible ? 'custom-eye-off' : 'custom-eye'"
                        @click:append-inner="isPasswordVisible = !isPasswordVisible"
                    />
                </VCardText>
                <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
                    <VBtn
                        class="btn-light"
                        @click="closeUserPasswordDialog">
                        Avbryt
                    </VBtn>
                    <VBtn class="btn-gradient" type="submit">
                        Sticka
                    </VBtn>
                </VCardText>
            </VCard>
        </VForm>
    </VDialog>
</template>

<style lang="scss">
  .card-form {
    .v-list {
      padding: 28px 24px 40px !important;

      .v-list-item {
        margin-bottom: 0px;
        padding: 4px 0 !important;
        gap: 0px !important;

        .v-input--density-compact {
          --v-input-control-height: 48px !important;
        }

        .v-select .v-field,
        .v-autocomplete .v-field {

          .v-select__selection, .v-autocomplete__selection {
            align-items: center;
          }

          .v-field__input > input {
            top: 0px;
            left: 0px;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }

        .selector-user {
          .v-input__control {
            background: white !important;
            padding-top: 0 !important;
          }
          .v-input__prepend, .v-input__append {
            padding-top: 12px !important;
          }
        }

        .v-text-field {
          .v-input__control {
            padding-top: 0;
            input {
              min-height: 48px;
              padding: 12px 16px;
            }
          }
        }
      }
    }
    & .v-input {
      .v-input__prepend {
        padding-top: 12px !important;
      }
      & .v-input__control {
        .v-field {
          background-color: #f6f6f6;
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
        }
      }
    }
  }

  .dialog-bottom-full-width {
    .v-card {
      border-radius: 24px 24px 0 0 !important;
    }
  }
</style>
