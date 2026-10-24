<script setup>

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
  'close'
])

const email = ref('')
const name = ref('')
const password = ref('')
const last_name = ref('')
const phone = ref('')
const isPhone = ref(false)
const address = ref('')
const isAddress = ref(false)

const assignedRoles = ref([])

watchEffect(() => {
    if (props.isDrawerOpen) {

        if (!(Object.entries(props.user).length === 0) && props.user.constructor === Object) {
            email.value = props.user.email
            password.value = props.user.password
            name.value = props.user.name
            last_name.value = props.user.last_name
            phone.value = props.user.user_detail?.personal_phone ?? '----'
            isPhone.value = (props.user.user_detail?.personal_phone === null) ? true : false
            address.value = props.user.user_detail?.personal_address ?? '----'
            isAddress.value = (props.user.user_detail?.personal_address === null) ? true : false

            assignedRoles.value = props.user.assignedRoles
        }
    }
})

const closeUserDetailDialog = function() {
    emit('update:isDrawerOpen', false)
    emit('close')
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
            @click="closeUserDetailDialog"
        >
            <VIcon size="16" icon="custom-close" />
        </VBtn>

        <!-- Dialog Content -->
        <VCard
            flat 
            class="card-form user-mobile mb-4"
        >
            <VCardText class="dialog-title-box">
                <!-- <VIcon size="32" icon="custom-filled-waste" class="action-icon" /> -->
                <div class="dialog-title">
                Användaruppgifter
                </div>
            </VCardText>

            <VCardText class="dialog-text mb-4">
                <VRow>
                    <VCol md="6" cols="12">
                        <VLabel
                            class="mb-1 text-body-2 text-high-emphasis"
                            text="Namn"
                        />
                        <VTextField
                            v-model="name"
                            readonly
                        />
                    </VCol>
                    <VCol md="6" cols="12">
                        <VLabel
                            class="mb-1 text-body-2 text-high-emphasis"
                            text="Efternamn"
                        />
                        <VTextField
                            v-model="last_name"
                            readonly
                        />
                    </VCol>
                    <VCol md="12" cols="12">
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
                    <VCol md="6" cols="12">
                        <VLabel
                            class="mb-1 text-body-2 text-high-emphasis"
                            text="Telefon"
                        />
                        <VTextField
                            v-model="phone"
                            type="tel"
                            :readonly="!isPhone"
                            :disabled="isPhone"
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
                            :readonly="!isAddress"
                            :disabled="isAddress"
                            />
                    </VCol>               
                    <VCol md="12" cols="12">
                        <VLabel
                            class="mb-1 text-body-2 text-high-emphasis"
                            text="Roller som tilldelats användaren"
                        />
                        <VCombobox
                            v-model="assignedRoles"
                            chips
                            multiple
                            :items="rolesList"
                            readonly
                        />
                    </VCol>
                </VRow>
            </VCardText>
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