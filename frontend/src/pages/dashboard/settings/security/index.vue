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

const { width: windowWidth } = useWindowSize()
const sectionEl = ref(null)
const profileStores = useProfileStores()
const authStores = useAuthStores()
const configsStores = useConfigsStores()
const suppliersStores = useSuppliersStores()

const refVForm = ref()
const password = ref()
const passwordConfirmation = ref()
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)

const refForm = ref()
const isFormValid = ref(false)
const isMasterPasswordVisible = ref(false)
const masterPassword = ref('')
const csrUrl = ref(null)
const isFileMissingDialogVisible = ref(false)
const setting = ref([])

const userData = ref(null)
const role = ref(null)

const isDialogVisible = ref(false)
const is2faEnabled = ref(false)
const qr = ref(null)
const token = ref(null)

const isRequestOngoing = ref(false);
const advisor = ref({
  type: '',
  message: '',
  show: false,
})

const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')

const setAdvisor = (type, message) => {
  advisor.value.type = type
  advisor.value.message = message
  advisor.value.show = true
}

const clearAdvisorLater = delay => {
  setTimeout(() => {
    advisor.value.show = false
    advisor.value.message = ''
  }, delay)
}

const open2faDialog = () => {
  isDialogVisible.value = true
}

async function fetchData() {
  isRequestOngoing.value = true

  try {
    const qrData = await authStores.generateQR()
    const qrCode = QRCode(0, 'L')

    qrCode.addData(qrData.qr)
    qrCode.make()

    qr.value = qrCode.createDataURL(4)
    token.value = qrData.token
    is2faEnabled.value = qrData.is_2fa

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value?.roles?.[0]?.name ?? null

    if (role.value === 'Supplier') {
      const supplierId = userData.value?.supplier?.id

      if (supplierId) {
        const supplierData = await suppliersStores.getMasterPassword(supplierId)

        masterPassword.value = supplierData?.master_password ?? ''
        csrUrl.value = supplierData?.csr_url ?? null
      }
    } else {
      await configsStores.getFeature('setting')
      setting.value = configsStores.getFeaturedConfig('setting')
      masterPassword.value = setting.value?.master_password ?? ''
      csrUrl.value = null
    }
  } catch (error) {
    console.error(error)
    setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
    clearAdvisorLater(5000)
  } finally {
    isRequestOngoing.value = false
  }
}

const submit2faCode = code => {
  const payload = {
    panel: true,
    token_2fa: code,
    token: token.value,
  }

  isRequestOngoing.value = true

  authStores.validate(payload)
    .then(() => {
      setAdvisor('success', is2faEnabled.value ? '2FA är aktiverad' : '2FA är avaktiverad')
      clearAdvisorLater(5000)
      fetchData()
    })
    .catch(error => {
      if (error?.message === 'invalid_code')
        setAdvisor('error', error.errors)
      else
        setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')

      clearAdvisorLater(5000)
      console.error(error?.message ?? error)
    })
    .finally(() => {
      isRequestOngoing.value = false
    })
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (!isValid)
      return

    const payload = { password: password.value }

    isRequestOngoing.value = true

    profileStores.updatePassword(payload)
      .then(response => {
        setAdvisor('success', 'Lösenord ändrat')
        clearAdvisorLater(5000)

        localStorage.setItem('user_data', JSON.stringify(response.user_data))
        password.value = undefined
        passwordConfirmation.value = undefined
      })
      .catch(() => {
        setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
        clearAdvisorLater(5000)

        password.value = undefined
        passwordConfirmation.value = undefined
      })
      .finally(() => {
        isRequestOngoing.value = false
      })
  })
}

const downloadFile = async url => {
  if (!url) {
    isFileMissingDialogVisible.value = true

    return
  }

  try {
    const response = await fetch(
      themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + url,
    )
    const blob = await response.blob()
    const blobUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = blobUrl
    link.download = url.split('/').pop()
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Error:', error)
    setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
    clearAdvisorLater(5000)
  }
}

const onSubmitKey = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (!valid)
      return

    isRequestOngoing.value = true

    try {
      if (role.value === 'Supplier') {
        const supplierId = userData.value?.supplier?.id
        const payload = {
          master_password: masterPassword.value,
        }

        if (supplierId)
          await suppliersStores.masterPassword(supplierId, payload)
      } else {
        const payload = {
          key: 'setting',
          params: {
            value: {
              master_password: masterPassword.value,
            },
          },
        }

        await configsStores.postFeature(payload)
      }

      await fetchData()
      setAdvisor('success', 'Aktuell information')
      clearAdvisorLater(5000)
    } catch (error) {
      console.error(error)
      setAdvisor('error', 'Fel vid uppdatering av huvudlösenord')
      clearAdvisorLater(5000)
    } finally {
      isRequestOngoing.value = false
    }
  })
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  fetchData();
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});
</script>

<template>
    <section class="page-section bg-white" ref="sectionEl">
      <LoadingOverlay :is-loading="isRequestOngoing" />
      <VSnackbar
        v-model="advisor.show"
        transition="scroll-y-reverse-transition"
        :location="snackbarLocation"
        :color="advisor.type"
        class="snackbar-alert snackbar-dashboard"
      >
        {{ advisor.message }}
      </VSnackbar>

      <VCard class="card-fill">
        <VCardText class="pb-0" v-if="windowWidth < 1024">
          <div class="d-flex flex-column gap-4 flex-1">
            <VBtn
              class="btn-light"
              style="width: 120px;"
              :to="{ name: 'dashboard-settings' }"
            >
              <VIcon icon="custom-return" size="24" />
              Tillbaka
            </VBtn>

            <span class="title-settings pb-4 border-bottom-settings">
              Säkerhet
            </span>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Byt lösenord</span>
                <span class="text-settings">
                  Uppdatera ditt lösenord för att hålla ditt konto säkert.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
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
                    <div class="d-flex" :class="windowWidth < 1024 ? 'w-100 gap-2' : 'gap-4'">
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
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div 
            class="settings-layout pb-4" 
            :class="role === 'Supplier' ? 'border-bottom-settings' : ''">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Tvåfaktorsautentisering (2FA)</span>
                <span class="text-settings">
                  Skydda ditt konto med ett extra säkerhetssteg vid inloggning med en kod från din mobil.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <VTable class="text-no-wrap rounded">
                <thead>
                  <tr>
                    <th scope="col">Typ</th>
                    <th scope="col" class="w-5 text-end">Aktivera</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Tvåfaktorsautentisering (2FA)</td>
                    <td>
                      <VCheckbox
                        v-model="is2faEnabled"
                        class="two_class"
                        @update:model-value="open2faDialog"
                      />
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0" v-if="role === 'Supplier' && csrUrl !== null">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Säkerhetslösenord</span>
                <span class="text-settings">
                  Ange eller uppdatera ditt säkerhetslösenord.
                  Används för att godkänna Swish-utbetalningar.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <VForm
                ref="refForm"
                class="card-form"
                v-model="isFormValid"
                @submit.prevent="onSubmitKey"
              >
                <div 
                  class="d-flex flex-wrap"
                  :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                  :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                  <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Säkerhetslösenord*" />
                    <VTextField
                      v-model="masterPassword"
                      :type="isMasterPasswordVisible ? 'text' : 'password'"
                      :append-inner-icon="isMasterPasswordVisible ? 'custom-eye-off' : 'custom-eye'"
                      :rules="[requiredValidator]"
                      @click:append-inner="isMasterPasswordVisible = !isMasterPasswordVisible"
                    />
                  </div>

                  <VCardText class="p-0 d-flex w-100">
                    <div class="d-flex" :class="windowWidth < 1024 ? 'w-100 gap-2' : 'gap-4'">
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
              </VForm>
            </div>
          </div>
        </VCardText>

        <VCardText :class="windowWidth < 1024 ? '' : 'pb-0'" v-if="role === 'Supplier' && csrUrl !== null">
          <div class="settings-layout">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Nedladdning av certifikat</span>
                <span class="text-settings">
                  Ladda ner certifikat som används för säkra integrationer med andra tjänster.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <div class="subtitle-settings" :class="windowWidth < 1024 ? 'mb-4' : 'mb-6'">
                Certifikat
              </div>

              <VCardText class="p-0 d-flex w-100">
                <div class="d-flex" :class="windowWidth < 1024 ? 'w-100 gap-2' : 'gap-4'">
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
            </div>
          </div>
        </VCardText>

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
          :is_2fa="is2faEnabled"
          @submit="submit2faCode"
          @close="fetchData"
        />
      </VCard>
    </section>
</template>

<style lang="scss">
  .two_class {
    grid-template-areas: none;
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
