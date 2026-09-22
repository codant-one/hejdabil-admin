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
import InlineBanner from '@/components/common/InlineBanner.vue'

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
const isDeleteConfirmDialogVisible = ref(false)
const isDeleteVerificationDialogVisible = ref(false)
const deletionForm = ref(null)
const deletionCode = ref('')
const deletionError = ref('')
const deletionOtpKey = ref(0)

const err = ref(null);
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);

const isUserRole = () => role.value === 'User'
const hasInactiveSupplierSubscription = () => {
  return role.value === 'Supplier' && Number(userData.value?.supplier?.is_subscription_active) === 0
}
const isDisabled = () => isUserRole() || hasInactiveSupplierSubscription()

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
      setAdvisor('error', 'Fel vid uppdatering av huvudlösenord')
      clearAdvisorLater(5000)
    } finally {
      isRequestOngoing.value = false
    }
  })
}

const deletionErrorMessage = error => error?.message ?? 'Ett serverfel uppstod. Försök igen.'

const resetDeletionForm = () => {
  deletionCode.value = ''
  deletionError.value = ''
  deletionOtpKey.value += 1
  deletionForm.value?.resetValidation()
}

const openDeleteConfirmation = () => {
  resetDeletionForm()
  isDeleteConfirmDialogVisible.value = true
}

const sendDeletionCode = async () => {
  const supplierId = userData.value?.supplier?.id

  if (!supplierId)
    return

  isRequestOngoing.value = true
  deletionError.value = ''

  try {
    await suppliersStores.sendDeletionCode(supplierId)
    isDeleteConfirmDialogVisible.value = false
    isDeleteVerificationDialogVisible.value = true
    setAdvisor('success', 'En verifieringskod har skickats till din e-postadress.')
    clearAdvisorLater(3000)
  } catch (error) {
    err.value = error;
    isDeleteConfirmDialogVisible.value = false
    inteSkapatsDialog.value = error?.response?.data?.feedback === "not_permission" ? false : true
    clearAdvisorLater(5000)
  } finally {
    isRequestOngoing.value = false
  }
}

const resendDeletionCode = async () => {
  await sendDeletionCode()

  if (isDeleteVerificationDialogVisible.value)
    setAdvisor('success', 'En ny verifieringskod har skickats.')
}

const handleDeletionOtp = value => {
  deletionCode.value = value
  deletionError.value = ''
}

const closeDeleteVerification = () => {
  isDeleteVerificationDialogVisible.value = false
  resetDeletionForm()
}

const confirmAccountDeletion = async () => {
  const { valid } = await deletionForm.value.validate()

  if (!valid)
    return

  if (deletionCode.value.length !== 6) {
    deletionError.value = 'Ange den sexsiffriga koden från e-postmeddelandet.'

    return
  }

  isRequestOngoing.value = true
  deletionError.value = ''

  try {
    await suppliersStores.requestDeletion(userData.value.supplier.id, {
      code: deletionCode.value,
    })

    closeDeleteVerification()
    skapatsDialog.value = true
  } catch (error) {
    deletionError.value = deletionErrorMessage(error)
  } finally {
    isRequestOngoing.value = false
  }
}

const showError = () => {
    inteSkapatsDialog.value = false;

    advisor.value.show = true;
    advisor.value.type = "error";
        const responseData = err.value?.response?.data;
    
        if (responseData?.message) {
            advisor.value.message = responseData.message;
        } else if (responseData?.errors) {
            advisor.value.message = Object.values(responseData.errors)
                .flat()
                .join("<br>");
        } else if (err.value?.message) {
            advisor.value.message = err.value.message;
    } else {
      advisor.value.message = "Ett serverfel uppstod. Försök igen.";
    }

    setTimeout(() => {
      advisor.value.show = false;
      advisor.value.type = "";
      advisor.value.message = "";
    }, 3000);

};

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
            :class="role === 'Supplier' && !isDisabled() ? 'border-bottom-settings' : ''">
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

        <VCardText class="pb-0" v-if="role === 'Supplier' && csrUrl !== null && !isDisabled()">
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

        <VCardText class="pb-0" v-if="role === 'Supplier' && csrUrl !== null && !isDisabled()">
          <div class="settings-layout border-bottom-settings pb-4">
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

        <VCardText class="card-delete-account">
          <div class="d-flex flex-column gap-4">
            <span class="subtitle-settings">Radera konto permanent</span>
            <span class="text-settings">
              Om du endast vill avsluta ditt abonnemang behöver du inte radera ditt konto.<br>
              Du kan istället säga upp abonnemanget separat. Att radera kontot är en permanent åtgärd som innebär att ditt konto och dina uppgifter tas bort och inte kan återställas. 
              Om du väljer att radera ditt konto sägs även ditt abonnemang upp automatiskt.<br><br>

              Enligt avtalet gäller 3 månaders uppsägningstid. Under denna period förblir ditt konto och abonnemang aktiva. 
              När uppsägningstiden har löpt ut avslutas abonnemanget och ditt konto raderas permanent.<br><br>

              Uppgifter som Bilflogg enligt lag är skyldigt att bevara kan komma att sparas under den tid som krävs.
            </span>
            
          </div>
          <VBtn 
              type="button" 
              :block="windowWidth < 1024"
              class="btn-error-2 mt-4 px-2"
              :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
              @click="openDeleteConfirmation"
            >
              Radera konto
            </VBtn>
        </VCardText>

        <VDialog
          v-model="isDeleteConfirmDialogVisible"
          persistent
          class="action-dialog"
        >
          <VBtn
            icon
            class="btn-white close-btn"
            @click="isDeleteConfirmDialogVisible = false"
          >
            <VIcon size="16" icon="custom-close" />
          </VBtn>

          <VCard>
            <VCardText class="dialog-title-box">
              <VIcon size="32" icon="custom-waste-outlined" class="action-icon" />
              <div class="dialog-title">Radera konto permanent?</div>
            </VCardText>

            <VCardText class="dialog-text">
              Att radera kontot är en permanent åtgärd som innebär att ditt konto och dina uppgifter tas bort och inte kan återställas.
            </VCardText>

            <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
              <VBtn class="btn-light" @click="isDeleteConfirmDialogVisible = false">
                Avbryt
              </VBtn>
              <VBtn class="btn-error-2" @click="sendDeletionCode">
                Radera konto
              </VBtn>
            </VCardText>
          </VCard>
        </VDialog>

        <VDialog
          v-model="isDeleteVerificationDialogVisible"
          :fullscreen="windowWidth < 1024"
          persistent
          :scrim="windowWidth < 1024 ? false : true"
          :scrollable="windowWidth >= 1024"
          :class="windowWidth >= 1024 ? 'action-dialog' : 'action-dialog dialog-fullscreen'"
          :transition="windowWidth < 1024 ? 'dialog-bottom-transition' : undefined"
          :content-class="windowWidth < 1024 ? 'dialog-bottom-full-width' : undefined"
        >
          <VBtn icon class="btn-white close-btn" @click="closeDeleteVerification">
            <VIcon size="16" icon="custom-close" />
          </VBtn>

          <VCard
            flat
            :class="windowWidth < 1024 ? 'h-100 d-flex flex-column' : ''"
          >
            <VCardText 
              class="dialog-title-box"
              :style="windowWidth < 1024 ? 'max-height: 115px;' : ''"
            >
              <VIcon size="32" icon="custom-email-outlined" class="action-icon" />
              <div class="dialog-title">Kontrollera din e-post</div>
            </VCardText>

            <VCardText
              class="dialog-text"
              :style="windowWidth < 1024 ? 'overflow-y: auto; overflow-x: hidden;' : ''"
            >

              <InlineBanner
                v-if="deletionError"
                variant="error"
                title="Koden har inte kunnat bekräftas"
                icon="custom-risk"
                class="alert-no-shrink mb-4"
                style="flex: none;"
              >
                {{ deletionError }}
              </InlineBanner>
                  
              Vi har skickat en verifieringskod till din e-postadress. Ange koden och ditt lösenord för att bekräfta att du vill radera ditt konto.
              
              <VForm ref="deletionForm" @submit.prevent="confirmAccountDeletion">
                
                <VCardText class="dialog-text deletion-verification-form p-0">
                  <AppOtpInput
                    :key="deletionOtpKey"
                    :show-label="false"
                    type="text"
                    class="deletion-otp"
                    @updateOtp="handleDeletionOtp"
                  />

                  <div class="deletion-resend mb-4">
                    Fick du inte koden?
                    <button type="button" @click="resendDeletionCode">Skicka koden igen</button>
                  </div>

                  <div 
                    class="card-delete-account deletion-resend mt-4" 
                    style="margin: 0!important; text-align: start !important;">
                    <span>Viktigt:</span> När du bekräftar kommer ditt konto och tillhörande uppgifter att raderas permanent efter 3 månader och kan inte återställas.
                  </div>
                </VCardText>

                <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0 px-0">
                  <VBtn class="btn-light" @click="closeDeleteVerification">Avbryt</VBtn>
                  <VBtn type="submit" class="btn-error-2">Radera konto</VBtn>
                </VCardText>
              </VForm>
            </VCardText>
          </VCard>
        </VDialog>

        <VDialog
          v-model="skapatsDialog"
          persistent
          class="action-dialog dialog-big-icon"
        >

        <VBtn
          icon
          class="btn-white close-btn"
          @click="skapatsDialog = !skapatsDialog"
        >
          <VIcon size="16" icon="custom-close" />
        </VBtn>

          <VCard>
            <VCardText class="dialog-title-box big-icon justify-center pb-0">
              <VIcon size="72" icon="custom-f-checkmark" />
            </VCardText>
            <VCardText class="dialog-title-box justify-center">
              <div class="dialog-title">
                  Ditt konto har raderats korrekt!
              </div>
            </VCardText>
            <VCardText class="dialog-text text-center">
              Ditt konto och tillhörande uppgifter har raderats.
              Du kommer inte längre att kunna komma åt ditt konto eller återställa den raderade informationen.
            </VCardText>
            <VCardText class="d-flex justify-center dialog-actions">
              <VBtn class="btn-gradient" @click="skapatsDialog = false">
                Stäng
              </VBtn>
            </VCardText>
          </VCard>
        </VDialog>

        <VDialog
            v-model="inteSkapatsDialog"
            persistent
            class="action-dialog dialog-big-icon"
        >
            <VBtn
                icon
                class="btn-white close-btn"
                @click="inteSkapatsDialog = !inteSkapatsDialog"
            >
                <VIcon size="16" icon="custom-close" />
            </VBtn>
            <VCard>
                <VCardText class="dialog-title-box big-icon justify-center pb-0">
                    <VIcon size="72" icon="custom-f-cancel" />
                </VCardText>
                <VCardText class="dialog-title-box justify-center">
                    <div class="dialog-title">Ett fel inträffade</div>
                </VCardText>
                <VCardText class="dialog-text text-center">                    
                    Raderingen av kontot har inte genomförts korrekt,
                    försök igen.
                </VCardText>

                <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
                    <VBtn class="btn-light" @click="showError">
                        Stäng
                    </VBtn>
                </VCardText>
            </VCard>
        </VDialog>

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
  .card-delete-account {
    border-radius: 16px;
    padding: 24px;
    gap: 24px;
    margin: 16px;
    background-color: #FFF1F1;
  }

  .two_class {
    grid-template-areas: none;
  }

  .deletion-verification-form {
    padding-top: 16px !important;
    padding-bottom: 16px !important;
  }

  .deletion-otp {
    .d-flex {
      justify-content: center !important;
    }
  }

  .deletion-resend {
    margin-top: 4px;
    color: #949494;
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;
    text-align: center;

    button, span {
      color: #9B191B;
    }
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
