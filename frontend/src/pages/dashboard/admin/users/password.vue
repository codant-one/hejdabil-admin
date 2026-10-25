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
                password: password.value,
                type: 'users_admin'
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
      max-width="600"
      class="action-dialog"
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
        <VCard>
            <VCardText class="dialog-title-box">
                <!-- <VIcon size="32" icon="custom-warning-outlined" class="action-icon" /> -->
                <div class="dialog-title">
                Redigera användarens lösenord
                </div>
            </VCardText>

            <VForm
            ref="refForm"
            class="card-form user-mobile"
            @submit.prevent="editUserPassword"
            >
                <VCardText class="dialog-text">
                    <VRow>
                        <VCol cols="12">
                            <VLabel
                                class="mb-1 text-body-2 text-high-emphasis"
                                text="E-post"
                            />
                            <VTextField
                                v-model="email"
                                readonly
                            />
                        </VCol>
                        <VCol cols="12" >
                            <VLabel
                                class="mb-1 text-body-2 text-high-emphasis"
                                text="Nytt lösenord*"
                            />
                            <VTextField
                                v-model="password"
                                :type="isPasswordVisible ? 'text' : 'password'"
                                :rules="[requiredValidator]"
                                :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                                @click:append-inner="isPasswordVisible = !isPasswordVisible"
                            />
                        </VCol>
                    </VRow>
                </VCardText>
                
                <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
                    <VBtn
                        class="btn-light"
                        @click="closeUserPasswordDialog">
                        Avbryt
                    </VBtn>
                    <VBtn class="btn-gradient" type="submit">
                        Redigera lösenord
                    </VBtn>
                </VCardText>
            </VForm>
        </VCard>
    </VDialog>
</template>

<style scoped>
  @media (max-width: 1023px) {
    .card-form.user-mobile {
      .v-list {
        padding: 28px 24px 24px !important;

        .v-list-item {
          margin-bottom: 0px;
          padding: 4px 0 !important;
          gap: 0px !important;

          .v-input--density-compact {
            --v-input-control-height: 48px !important;
          }

          .v-select .v-field {
            .v-select__selection {
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

          .selector-country {
            .v-input__prepend {
              margin-inline-end: 6px !important;
            }
          }

          .v-autocomplete .v-field {
            .v-autocomplete__selection-text {
              align-items: center;
              display: flex;
            }

            .v-field__append-inner {
              align-items: center;
              padding-top: 0px;
            }
          }

          .v-text-field {
            .v-input__control {
              padding-top: 0;

              .v-field {
                .v-text-field__prefix {
                  height: 48px;
                  color: #454545;
                }
              }

              input {
                min-height: 48px !important;
                height: 48px !important;
                padding: 12px 16px;
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
        }
      }
      & .v-input:not(.v-textarea) {
        & .v-input__control {
          .v-field {
            background-color: #f6f6f6;
            min-height: 48px !important;
            height: 48px !important;
            .v-field-label {
              top: 12px !important;
            }
          }
        }
      }
    }
  }

  .v-dialog .v-overlay__content {
    max-height: calc(100dvh - 48px) !important;
  }

  :deep(.vue-simple-handler) {
      background: #57F287 !important;
  }
  :deep(.cropper-preview-circle) {
      border: dashed 1px #57F287
  }
  :deep(.cropper-background),
  :deep(.vue-advanced-cropper__foreground) {
      background-color: transparent !important;
  }
</style>
<route lang="yaml">
  meta:
    action: edit
    subject: users
</route>
