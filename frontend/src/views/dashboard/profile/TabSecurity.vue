<script setup>

import { themeConfig } from "@themeConfig";
import { confirmedValidator, passwordValidator, requiredValidator } from '@/@core/utils/validators'
import { useProfileStores } from '@/stores/useProfile'
import { useAuthStores } from '@/stores/useAuth'
import { useConfigsStores } from '@/stores/useConfigs'
import { useSuppliersStores } from '@/stores/useSuppliers'
import AddAuthenticatorAppDialog from "@/components/dialogs/AddAuthenticatorAppDialog.vue";
import QRCode from 'qrcode-generator';
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const { width: windowWidth } = useWindowSize();

const profileStores = useProfileStores()
const authStores = useAuthStores()
const configsStores = useConfigsStores()
const suppliersStores = useSuppliersStores()

const refVForm = ref()
const password = ref()
const passwordConfirmation = ref()
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const isRequestOngoing = ref(true)

const refForm = ref()
const isFormValid = ref(false)
const isMasterPasswordVisible = ref(false)
const masterPassword = ref('')
const csrUrl = ref('')
const isFileMissingDialogVisible = ref(false)
const setting = ref([])

const userData = ref(null)
const role = ref(null)

const isDialogVisible = ref(false)
const is_2fa = ref(false)
const data = ref(null)
const qr = ref(null)
const token = ref(null)

const advisor = ref({
    message: '',
    show: false,  
    type: '',
})

const emit = defineEmits([
  'alert'
])

const enabled2fa = () => {
  isDialogVisible.value = true
}

watchEffect(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  data.value = await authStores.generateQR()

  const typeNumber = 0;
  const errorCorrectionLevel = 'L';
  const qr_ = QRCode(typeNumber, errorCorrectionLevel);
  qr_.addData(data.value.qr);
  qr_.make();

  qr.value = qr_.createDataURL(4)
  token.value = data.value.token
  is_2fa.value = data.value.is_2fa

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  if(role.value === 'Supplier') {
    const supplierData = await suppliersStores.getMasterPassword(userData.value.supplier.id)
    masterPassword.value = supplierData.master_password
    csrUrl.value = supplierData.csr_url
  } else {
    await configsStores.getFeature('setting')
    setting.value = configsStores.getFeaturedConfig('setting')

    masterPassword.value = setting.value.master_password
  }

  isRequestOngoing.value = false
}

const chance2fa = (code) => {

  let data = {
    panel: true,
    token_2fa: code,
    token: token.value
  }

  isRequestOngoing.value = true

  authStores.validate(data)
    .then(response => {
      advisor.value.show = true
      advisor.value.message = 'Aktuell information'
      advisor.value.type = 'success' 
      
      emit('alert', advisor)
      fetchData()
      isRequestOngoing.value = false
    }).catch(err => {

      window.scrollTo(0, 0)

      if(err.message === 'invalid_code'){
        advisor.value.show = true
        advisor.value.type = 'error'
        advisor.value.message = err.errors
      }

      console.error(err.message)
      emit('alert', advisor)

      isRequestOngoing.value = false
    })

  setTimeout(() => {
    advisor.value.show = false
    advisor.value.type = ''
    advisor.value.message = ''
    emit('alert', advisor)
  }, 5000)

}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if(isValid) {
            
      let data = { password: password.value}

      isRequestOngoing.value = true

      profileStores.updatePassword(data)
        .then(response => {

          window.scrollTo(0, 0)

          advisor.value.show = true
          advisor.value.message = 'Lösenord ändrat'
          advisor.value.type = 'success'

          emit('alert', advisor)

          localStorage.setItem('user_data', JSON.stringify(response.user_data))
                
          password.value = undefined
          passwordConfirmation.value = undefined

          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
            emit('alert', advisor)
          }, 5000)

          isRequestOngoing.value = false

        }).catch(error =>{

          window.scrollTo(0, 0)

          advisor.value.show = true
          advisor.value.message = 'Ett serverfel uppstod. Försök igen.'
          advisor.value.type = 'error'
                
          emit('alert', advisor)

          password.value = undefined
          passwordConfirmation.value = undefined
                
          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''    
            emit('alert', advisor)  
          }, 5000)

          isRequestOngoing.value = false
        })
    }
  })
}

const downloadFile = async (url) => {
  if (!url) {
    isFileMissingDialogVisible.value = true
    return
  }

   try {
    const response = await fetch(
      themeConfig.settings.urlbase + "proxy-image?url=" + themeConfig.settings.urlStorage + url
    );
    const blob = await response.blob();

    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = blobUrl;
    a.download = url.split("/").pop();
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  } catch (error) {
    console.error("Error:", error);
  }
}

const onSubmitKey = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {

      isRequestOngoing.value = true

      try {
        if (role.value === 'Supplier') {
          // Llamar al endpoint de suppliers
          const data = {
            master_password: masterPassword.value
          }
          await suppliersStores.masterPassword(userData.value.supplier.id, data)
        } else {
          // Llamar al endpoint de configs
          const data = {
            key: 'setting',
            params: {
              value: {
                master_password: masterPassword.value
              }
            }
          }
          await configsStores.postFeature(data)
        }

        await fetchData()

        advisor.value.show = true
        advisor.value.message = 'Aktuell information'
        advisor.value.type = 'success'
        emit('alert', advisor)

        setTimeout(() => {
          advisor.value.show = false
          advisor.value.type = ''
          advisor.value.message = ''
          emit('alert', advisor)
        }, 5000)
      } catch (error) {
        console.error(error)
        advisor.value.show = true
        advisor.value.message = 'Fel vid uppdatering av huvudlösenord'
        advisor.value.type = 'error'
        emit('alert', advisor)

        setTimeout(() => {
          advisor.value.show = false
          advisor.value.type = ''
          advisor.value.message = ''
          emit('alert', advisor)
        }, 5000)
      } finally {
        isRequestOngoing.value = false
      }
      
    }
  })
}

</script>

<template>
  <section>
    <LoadingOverlay :is-loading="isRequestOngoing" />

    <VRow>
      <VCol cols="12" class="pb-0">
        <VCard>
          <VCardText>
            <div class="title-tabs mb-5">
              Ändra lösenord
            </div>

            <VAlert
              variant="tonal"
              color="warning"
              class="mb-4"
            >
              <VAlertTitle class="mb-1">
                Se till att dessa krav är uppfyllda
              </VAlertTitle>
              <span>Minst 8 tecken, stora och små bokstäver samt siffror</span>
            </VAlert>

            <VForm
              ref="refVForm"
              class="card-form"
              @submit.prevent="onSubmit"
            >
              <div 
                  class="d-flex flex-wrap"
                  :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                  :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
              >
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Nytt lösenord" />
                  <VTextField
                    v-model="password"
                    :type="isNewPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isNewPasswordVisible ? 'custom-eye-off' : 'custom-eye'"
                    :rules="[requiredValidator, passwordValidator]"
                    @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                  />
                </div>
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bekräfta lösenord" />
                  <VTextField
                    v-model="passwordConfirmation"
                    :type="isConfirmPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isConfirmPasswordVisible ? 'custom-eye-off' : 'custom-eye'"
                    :rules="[requiredValidator, confirmedValidator(passwordConfirmation, password)]"
                    @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                  />
                </div>

                <VCardText class="p-0 d-flex w-100">
                  <div class="d-flex mt-4" :class="windowWidth < 1024 ? 'w-100 gap-2' : 'gap-4'">
                      <VBtn 
                          type="submit" 
                          :block="windowWidth < 1024"
                          class="btn-gradient"
                          :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                      >
                          Ändra lösenord
                      </VBtn>
                  </div>
                </VCardText>
              </div>
            </VForm>
          </VCardText>
        </VCard>

        <VDivider :class="windowWidth < 1024 ? 'mb-4' : 'mb-4'" />

        <VCard>
          <VCardText>
            <div class="title-tabs mb-5">
              Authenticator
            </div>
            <VTable class="text-no-wrap rounded">
              <thead>
                <tr>
                  <th scope="col"> TYP </th>
                  <th scope="col" class="w-5 text-end">AKTIVERA</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td> Tvåfaktorsautentisering (2FA)  </td>
                  <td>
                    <VCheckbox 
                      v-model="is_2fa"
                      class="two_class"
                      @update:model-value="enabled2fa" />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>

        <VDivider  v-if="role === 'Supplier'" :class="windowWidth < 1024 ? 'my-4' : 'my-4'" />

        <VCard class="mt-8" v-if="role === 'Supplier'">
          <VForm
              ref="refForm"
              class="card-form"
              v-model="isFormValid"
              @submit.prevent="onSubmitKey">

              <VCardText class="pt-0">
                <div class="title-tabs mb-5">
                  Säkerhetslösenord
                </div>
                <!-- 👉 Current Password -->
                <div 
                    class="d-flex flex-wrap"
                    :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                  <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Säkerhetslösenord" />
                    <VTextField
                      v-model="masterPassword"
                      :type="isMasterPasswordVisible ? 'text' : 'password'"
                      :append-inner-icon="isMasterPasswordVisible ? 'custom-eye-off' : 'custom-eye'"
                      :rules="[requiredValidator]"
                      @click:append-inner="isMasterPasswordVisible = !isMasterPasswordVisible"
                    />
                  </div>

                  <VCardText class="p-0 d-flex w-100">
                    <div class="d-flex mt-4" :class="windowWidth < 1024 ? 'w-100 gap-2' : 'gap-4'">
                        <VBtn 
                            type="submit" 
                            :block="windowWidth < 1024"
                            class="btn-gradient"
                            :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                        >
                            Spara
                        </VBtn>
                    </div>
                  </VCardText>
                </div>
              </VCardText>
          </VForm>
        </VCard>

        <VDivider  v-if="role === 'Supplier'" :class="windowWidth < 1024 ? 'my-4' : 'my-4'" />

        <VCard class="mt-5 mb-8" v-if="role === 'Supplier'">
          <VCardText>
            <div class="title-tabs mb-5">
              Certifikat
            </div>
            <VCardText class="p-0 d-flex w-100">
              <div class="d-flex mt-4" :class="windowWidth < 1024 ? 'w-100 gap-2' : 'gap-4'">
                  <VBtn 
                      type="button" 
                      :block="windowWidth < 1024"
                      class="btn-light"
                      :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                      @click="downloadFile(csrUrl)"
                  >
                      Ladda ner CSR
                  </VBtn>
              </div>
            </VCardText>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VDialog v-model="isFileMissingDialogVisible" width="500">
      <VCard title="Information">
        <VCardText>
          Systemet har inte genererat filen ännu, gör en begäran för att kunna generera den.
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="primary" @click="isFileMissingDialogVisible = false">
            Stäng
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <AddAuthenticatorAppDialog
      v-model:isDialogVisible="isDialogVisible"
      :qr="qr"
      :token="token"
      :is_2fa="is_2fa"
      @submit="chance2fa"
      @close="fetchData"
    />
  </section>
</template>

<style lang="scss">
  .two_class {
    grid-template-areas: none;
  }

  .bg-alert {
    background: linear-gradient(90deg, #EAFFF1 0%, #EAFFF8 50%, #ECFFFF 100%);
    border-radius: 16px;
    gap: 16px;
    opacity: 1;
    padding-top: 16px;
    padding-right: 24px;
    padding-bottom: 16px;
    padding-left: 24px;
  }

  .card-info {
        background-color: #F6F6F6;
        border-radius: 16px;
    }

    .title-tabs {
        font-weight: 700;
        font-size: 24px;
        line-height: 100%;
        color: #454545;

        @media (max-width: 1023px) {
            font-size: 16px
        }
    }

    .list-kopare {
        font-size: 16px;
        line-height: 100%;
        font-weight: 700;

        span {
            font-weight: 400;
            font-size: 16px;
        }
    }
    
    .title-kopare {
        font-weight: 700;
        font-size: 24px;
        line-height: 100%;
        color: #878787;

        @media (max-width: 1023px) {
            font-size: 16px
        }
    }

    .title-page {
        font-weight: 700;
        font-size: 32px;
        line-height: 100%;
        color: #1C2925;

        @media (max-width: 1023px) {
            font-size: 24px
        }
    }

    .subtitle-page {
        font-weight: 400;
        font-size: 24px;
        line-height: 100%;
        color: #878787;
    }

    .v-btn--disabled {
        opacity: 1 !important;
    }

    .border-bottom-secondary {
        border-bottom: 1px solid #d9d9d9;
        padding-bottom: 10px;
    }

    .justify-content-end {
        justify-content: end !important;
    }

    .info-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;

        .info-item {
            flex: 0 0 calc(100% / 7 - 14px);
            min-width: 0;

            span  {
                font-weight: 400;
                font-size: 16px;
                line-height: 24px;
                color: #454545;
            }

            .value-field {
                background-color: #F6F6F6;
                border-radius: 8px;
                border: 1px solid #E7E7E7;
                padding: 16px;
                height: 48px !important;
                align-items: center;
                display: flex;
                font-weight: 400;
                font-size: 12px;
                line-height: 24px;
                color: #5D5D5D;
            }

            @media (max-width: 1023px) {
                flex: 0 0 calc(50% - 8px);
            }
        }
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
                        @media (max-width: 991px) {
                            top: 12px !important;
                        }
                    }

                    .v-field__append-inner {
                        align-items: center;
                        padding-top: 0px;
                    }
                }
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