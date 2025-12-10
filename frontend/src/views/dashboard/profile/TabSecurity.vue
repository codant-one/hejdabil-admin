<script setup>

import { confirmedValidator, passwordValidator, requiredValidator } from '@/@core/utils/validators'
import { useProfileStores } from '@/stores/useProfile'
import { useAuthStores } from '@/stores/useAuth'
import { useConfigsStores } from '@/stores/useConfigs'
import { useSuppliersStores } from '@/stores/useSuppliers'
import AddAuthenticatorAppDialog from "@/components/dialogs/AddAuthenticatorAppDialog.vue";
import QRCode from 'qrcode-generator';

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
const crsUrl = ref('')
const keyUrl = ref('')
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
    crsUrl.value = supplierData.crs_url
    keyUrl.value = supplierData.key_url
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

const downloadFile = (url) => {
  if (!url) {
    isFileMissingDialogVisible.value = true
    return
  }
  window.open(url, '_blank')
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
    <VDialog
      v-model="isRequestOngoing"
      width="auto"
      persistent>
      <VProgressCircular
        indeterminate
        color="primary"
        class="mb-0"/>
    </VDialog>

    <VRow>
      <VCol cols="12" class="pb-0">
        <VCard title="Ändra lösenord">
          <VCardText>
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
              @submit.prevent="onSubmit"
            >
              <VRow>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="password"
                    label="Nytt lösenord"
                    :type="isNewPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                    :rules="[requiredValidator, passwordValidator]"
                    @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="passwordConfirmation"
                    label="Bekräfta lösenord"
                    :type="isConfirmPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                    :rules="[requiredValidator, confirmedValidator(passwordConfirmation, password)]"
                    @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                  />
                </VCol>

                <VCol cols="12">
                  <VBtn type="submit" class="w-100 w-md-auto">
                    Ändra lösenord
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
        <VCard title="Authenticator" class="mt-5">
          <VCardText>
            <VTable class="text-no-wrap rounded border">
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

        <VCard title="Säkerhetslösenord" class="mt-5" v-if="role !== 'User'">
          <VForm
              ref="refForm"
              v-model="isFormValid"
              @submit.prevent="onSubmitKey">

              <VCardText class="pt-0">
              <!-- 👉 Current Password -->
              <VRow class="mb-3">
                  <VCol
                      cols="12"
                      md="12"
                  >
                      <VTextField
                          v-model="masterPassword"
                          :type="isMasterPasswordVisible ? 'text' : 'password'"
                          :append-inner-icon="isMasterPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                          :rules="[requiredValidator]"
                          label="Säkerhetslösenord"
                          @click:append-inner="isMasterPasswordVisible = !isMasterPasswordVisible"
                  />
                  </VCol>
                  <VCol cols="12" class="py-0">
                      <VBtn type="submit"class="w-100 w-md-auto">
                          Spara
                      </VBtn>
                  </VCol>
              </VRow>

          
              </VCardText>
          </VForm>
      </VCard>

      <VCard title="Certifikat" class="mt-5" v-if="role === 'Supplier'">
        <VCardText>
          <div class="d-flex gap-4">
            <VBtn @click="downloadFile(crsUrl)">
              Ladda ner CRS
            </VBtn>
            <VBtn @click="downloadFile(keyUrl)">
              Ladda ner KEY
            </VBtn>
          </div>
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
</style>