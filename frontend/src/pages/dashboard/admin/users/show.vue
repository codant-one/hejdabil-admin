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

const { width: windowWidth } = useWindowSize()

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
        :fullscreen="windowWidth < 1024"
        persistent
        :scrim="windowWidth < 1024 ? false : true"
        :scrollable="windowWidth >= 1024"
        :class="windowWidth >= 1024 ? 'action-dialog' : 'action-dialog dialog-fullscreen'"
        :transition="windowWidth < 1024 ? 'dialog-bottom-transition' : undefined"
        :content-class="windowWidth < 1024 ? 'dialog-bottom-full-width' : undefined"
        width="560"
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
            class="card-form user-mobile"
            :class="windowWidth < 1024 ? 'h-100 d-flex flex-column' : ''"
        >
            <VCardText
                class="dialog-title-box flex-row"
                :class="windowWidth < 1024 ? 'mb-2 pb-2 flex-0' : ''"
                :style="windowWidth < 1024 ? '' : 'overflow-y: hidden;'"
            >
                <VIcon size="32" icon="custom-users" class="action-icon" />
                <div class="dialog-title">
                Användaruppgifter
                </div>
            </VCardText>

            <VDivider :class="windowWidth < 1024 ? '' : 'd-none'" />

            <VCardText
                class="dialog-text mb-4"
                :class="windowWidth < 1024 ? '' : 'dialog-scroll-content'"
                :style="windowWidth < 1024 ? 'overflow-y: auto; overflow-x: hidden;' : ''"
            >
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
                            class="user-roles-combobox"
                            chips
                            multiple
                            :items="rolesList"
                            readonly
                        />
                    </VCol>
                </VRow>
                <VCardText
                  class="d-flex justify-end gap-3 flex-wrap dialog-actions px-0 pb-0"
                >
                  <VBtn
                    class="btn-light"
                    @click="closeUserDetailDialog"
                  >
                    Avbryt
                  </VBtn>
                </VCardText>
            </VCardText>
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

  :deep(.card-form.user-mobile .v-input:not(.v-textarea) .v-input__control .v-field) {
    background-color: #f6f6f6 !important;
    height: 40px !important;
    min-height: 40px !important;

    .v-field__input {
      height: 40px !important;
      min-height: 40px !important;
      padding: 8px 16px !important;

      input {
        height: 40px !important;
        min-height: 40px !important;
      }
    }

    .v-field-label {
      top: 12px !important;
    }

    .v-field__append-inner {
      align-items: center;
      padding-top: 0;
    }

    .v-text-field__prefix {
      height: 40px;
    }
  }

  :deep(.card-form.user-mobile .v-combobox .v-field__input) {
    align-items: center;
    height: 40px !important;
    min-height: 40px !important;
    padding: 0 12px !important;
  }

  :deep(.card-form.user-mobile .v-combobox .v-field__field) {
    align-items: center;
    top: 0 !important;
  }

  :deep(.card-form.user-mobile .v-combobox .v-field__input > input) {
    align-self: center;
    height: 24px !important;
    min-height: 24px !important;
    padding: 0 !important;
  }

  :deep(.card-form.user-mobile .v-combobox__selection) {
    display: inline-flex;
    align-items: center;
    height: 24px !important;
    margin: 0 !important;
  }

  :deep(.card-form.user-mobile .v-combobox .v-chip) {
    top: -9px;
    height: 24px !important;
    min-height: 24px !important;
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