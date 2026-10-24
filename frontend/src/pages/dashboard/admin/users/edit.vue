<script setup>

import { requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { useUsersStores } from '@/stores/useUsers'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  user: {
    type: Object,
    required: true
  },
  rolesList: {
    type: Object,
    required: true
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'close',
  'alert',
  'data'
])

const usersStores = useUsersStores()

const refFormEdit = ref()

const id = ref('')
const email = ref('')
const name = ref('')
const last_name = ref('')
const phone = ref('')
const address = ref('')
const assignedRoles = ref([])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

onMounted(async () => {
  fetchData()
})

watchEffect(fetchData)

async function fetchData() {
  if (props.isDrawerOpen) {
    if (!(Object.entries(props.user).length === 0) && props.user.constructor === Object) {
      id.value = props.user.id
      email.value = props.user.email
      name.value = props.user.name
      last_name.value = props.user.last_name
      phone.value = props.user.user_detail?.personal_phone
      address.value = props.user.user_detail?.personal_address

      assignedRoles.value = props.user.assignedRoles

    }
  }
}

const closeUserEditDialog = function(){
    emit('update:isDrawerOpen', false)
    emit('close')
}

const onSubmitEdit = () =>{
  refFormEdit.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {

        let data = {
          name: name.value,
          email: email.value,
          last_name: last_name.value,
          personal_phone: phone.value,
          personal_address: address.value,
          roles: assignedRoles.value
        }

        usersStores.updateUser(data, id.value)
            .then(response => {
                closeUserEditDialog()

                window.scrollTo(0, 0)

                advisor.value.show = true
                advisor.value.type = 'success'
                advisor.value.message = 'Användaren uppdaterad!'

                emit('alert', advisor)
                emit('data')
                emit('close')

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
    <!-- DIALOG-->
    <VDialog
        :model-value="props.isDrawerOpen"
        max-width="600"
        class="action-dialog"
        persistent
        >
        <!-- Dialog close btn -->
        <VBtn
          icon
          class="btn-white close-btn"
          @click="closeUserEditDialog"
        >
          <VIcon size="16" icon="custom-close" />
        </VBtn>

        <!-- Dialog Content -->
        <VCard>
          <VCardText class="dialog-title-box">
            <!-- <VIcon size="32" icon="custom-warning-outlined" class="action-icon" /> -->
            <div class="dialog-title">
              Redigera användare
            </div>
          </VCardText>
          
          <VForm
            ref="refFormEdit"
            class="card-form user-mobile"
            @submit.prevent="onSubmitEdit"
          >
            <VCardText class="dialog-text">
              <VRow>
                <VCol
                  md="6"
                  cols="12"
                >
                  <VLabel
                    class="mb-1 text-body-2 text-high-emphasis"
                    text="Namn*"
                  />
                  <VTextField
                    v-model="name"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <VCol
                  md="6"
                  cols="12"
                >
                  <VLabel
                    class="mb-1 text-body-2 text-high-emphasis"
                    text="Efternamn*"
                  />
                  <VTextField
                    v-model="last_name"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="12"
                >
                  <VLabel
                    class="mb-1 text-body-2 text-high-emphasis"
                    text="E-post"
                  />
                  <VTextField
                    v-model="email"
                    disabled
                  />
                </VCol>
                <VCol md="6" cols="12">
                  <VLabel
                    class="mb-1 text-body-2 text-high-emphasis"
                    text="Lösenord"
                  />
                  <VTextField
                    v-model="phone"
                    type="password"
                    disabled
                  />
                </VCol> 
                <VCol
                  md="6"
                  cols="12"
                >
                  <VLabel
                    class="mb-1 text-body-2 text-high-emphasis"
                    text="Telefon*"
                  />
                  <VTextField
                    v-model="phone"
                    type="tel"
                    placeholder="+(XX) XXXXXXXXX"
                    :rules="[phoneValidator, requiredValidator]"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="12"
                >
                  <VLabel
                    class="mb-1 text-body-2 text-high-emphasis"
                    text="Adress*"
                  />
                  <VTextarea
                    v-model="address"
                    rows="3"
                    :rules="[requiredValidator]"
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
                @click="closeUserEditDialog">
                  Avbryt
              </VBtn>
              <VBtn class="btn-gradient" type="submit">
                  Redigera
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
    action: edit
    subject: users
</route>
