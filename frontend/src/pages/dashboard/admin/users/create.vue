<script setup>

import { emailValidator, requiredValidator } from '@/@core/utils/validators'
import { useUsersStores } from '@/stores/useUsers'

const props = defineProps({
  rolesList: {
    type: Object,
    required: true
  },
  type: {
    type: String,
    required: true
  }
})

const emit = defineEmits([
  'close',
  'alert',
  'data'
])

const usersStores = useUsersStores()

const refFormCreate = ref()

const isUserCreateDialog = ref(false)
const isPasswordVisible = ref(false)
const email = ref('')
const name = ref('')
const password = ref('')
const last_name = ref('')
const phone = ref('----')
const address = ref('----')
const assignedRoles = ref([])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const closeUserCreateDialog  = function(){
  isUserCreateDialog.value = false

  nextTick(() => {
    refFormCreate.value?.reset()
    refFormCreate.value?.resetValidation()
    email.value = ''
    name.value = ''
    password.value = ''
    last_name.value = ''
    phone.value = '----'
    address.value = '----'
    assignedRoles.value = []
  })
}

const onSubmitCreate = () => {
  refFormCreate.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      
      let data = {
        name: name.value,
        email: email.value,
        password: password.value,
        last_name: last_name.value,
        roles: assignedRoles.value
      }

      usersStores.addUser(data)
        .then(response => {
          closeUserCreateDialog()

          window.scrollTo(0, 0)
          
          advisor.value.show = true
          advisor.value.type = 'success'
          advisor.value.message = 'Skapad av användaren!'
          
          emit('alert', advisor)
          emit('data')
          emit('close')

          nextTick(() => {
            refFormCreate.value?.reset()
            refFormCreate.value?.resetValidation()
            email.value = ''
            name.value = ''
            password.value = ''
            last_name.value = ''
            phone.value = '----'
            address.value = '----'
            assignedRoles.value = []
          })

          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
            emit('alert', advisor)
          }, 5000)
        }).catch(error => {

          closeUserCreateDialog()
          window.scrollTo(0, 0)

          advisor.value.show = true
          advisor.value.type = 'error'
          
          if (error.feedback === 'params_validation_failed') {
            if(error.message.hasOwnProperty('email'))
              advisor.value.message = error.message.email[0]
          } else {
            advisor.value.message = 'Ett serverfel uppstod. Försök igen.'
          }

          emit('alert', advisor)
          emit('data')
          emit('close')

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
  <!-- 👉 CREATE USER -->
  <VDialog
    v-model="isUserCreateDialog"
    max-width="600"
    class="action-dialog"
    persistent
    >
    <template #activator="{ props }">
      <!-- <VBtn
        v-if="$can('create', 'users')"
        v-bind="props"
        prepend-icon="tabler-plus"
        class="w-100 w-md-auto"
        >
        Skapa användare
      </VBtn> -->
      <VBtn
        v-if="$can('create', 'users') && type === 'button'"
        v-bind="props"
        class="btn-gradient"
        block
      >
        <VIcon icon="custom-plus" size="24" />
        Skapa användare 
      </VBtn>

      <VBtn
        class="btn-ghost"
        v-bind="props"
        v-if="$can('create', 'users') && type === 'link'"
      >
        Skapa användare
        <VIcon icon="custom-arrow-right" size="24" />
      </VBtn>
    </template>

    <VBtn
      icon
      class="btn-white close-btn"
      @click="closeUserCreateDialog"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <VCard>
      <VCardText class="dialog-title-box">
        <!-- <VIcon size="32" icon="custom-warning-outlined" class="action-icon" /> -->
        <div class="dialog-title">
          Skapa användare
        </div>
      </VCardText>

      <VForm
        ref="refFormCreate"
        class="card-form user-mobile"
        @submit.prevent="onSubmitCreate"
        >
        <VCardText class="dialog-text">
          <VRow>
            <VCol md="6" cols="12">
              <VLabel
                class="mb-1 text-body-2 text-high-emphasis"
                text="Namn*"
              />
              <VTextField
                v-model="name"
                :rules="[requiredValidator]"
                />
            </VCol>
            <VCol md="6" cols="12">
              <VLabel
                class="mb-1 text-body-2 text-high-emphasis"
                text="Efternamn*"
              />
              <VTextField
                v-model="last_name"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" md="12">
              <VLabel
                class="mb-1 text-body-2 text-high-emphasis"
                text="E-post*"
              />
              <VTextField
                v-model="email"
                type="email"
                :rules="[requiredValidator,emailValidator]"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VLabel
                class="mb-1 text-body-2 text-high-emphasis"
                text="Lösenord*"
              />
              <VTextField
                v-model="password"
                :type="isPasswordVisible ? 'text' : 'password'"
                :rules="[requiredValidator]"
                :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
              />
            </VCol>
            <VCol cols="12" md="6" >
              <VLabel
                class="mb-1 text-body-2 text-high-emphasis"
                text="Telefon"
              />
              <VTextField
                v-model="phone"
                type="tel"
                placeholder="+(XX) XXXXXXXXX"
                disabled
              />
            </VCol>
            <VCol cols="12" md="12">
              <VLabel
                class="mb-1 text-body-2 text-high-emphasis"
                text="Adress"
              />
              <VTextarea
                v-model="address"
                rows="3"
                disabled
              />
            </VCol>
            <VCol cols="12">
              <VLabel
                class="mb-1 text-body-2 text-high-emphasis"
                text="Roller som tilldelats användaren*"
              />
              <VCombobox
                v-model="assignedRoles"
                chips
                clearable
                multiple
                closable-chips
                clear-icon="tabler-circle-x"
                :items="rolesList"
                :rules="[requiredValidator]"
                />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="closeUserCreateDialog">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" type="submit">
              Skapa
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
        padding: 28px 24px 40px !important;

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
    action: create
    subject: users
</route>
