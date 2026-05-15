<script setup>

import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import { requiredValidator } from '@/@core/utils/validators'
import { useSettingsStore } from '@/stores/useSettings'
import { useConfigsStores } from '@/stores/useConfigs'

const DEFAULT_NOTIFY_VIA_SOUND = true
const DEFAULT_NOTIFY_VIA_EMAIL = false
const DEFAULT_SEND_REMINDERS = true
const DEFAULT_REMINDER_HOURS = 24
const DEFAULT_NOTIFY_ON_DOCUMENT_SIGNED = true
const DEFAULT_NOTIFY_ON_AGREEMENT_SIGNED = true

const { width: windowWidth } = useWindowSize()
const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')
const sectionEl = ref(null)
const settingsStore = useSettingsStore()
const configsStores = useConfigsStores()
const userData = ref(null)
const settingsData = ref(null)
const role = ref('')
const isAdminRole = computed(() => role.value === 'SuperAdmin' || role.value === 'Administrator')

const notifyViaSound = ref(DEFAULT_NOTIFY_VIA_SOUND)
const notifyViaEmail = ref(DEFAULT_NOTIFY_VIA_EMAIL)
const sendReminders = ref(DEFAULT_SEND_REMINDERS)
const notifyOnDocumentSigned = ref(DEFAULT_NOTIFY_ON_DOCUMENT_SIGNED)
const notifyOnAgreementSigned = ref(DEFAULT_NOTIFY_ON_AGREEMENT_SIGNED)


const hoursOptions = ref ([
  { id: 1, name: "1 timme före" },
  { id: 3, name: "3 timmar före" },
  { id: 24, name: "24 timmar före" }
])
const hours = ref(DEFAULT_REMINDER_HOURS)

const advisor = ref({
  message: '',
  show: false,
  type: '',
})

const isRequestOngoing = ref(false);

const resolveLoggedUserId = () => {
  if (role.value === 'User')
    return userData.value?.supplier?.boss?.user_id ?? userData.value?.supplier?.boss?.user?.id ?? null

  return userData.value?.id ?? userData.value?.user?.id ?? null
}

const resolveSettingsSupplierId = () => {
  if (role.value === 'User')
    return userData.value?.supplier?.boss_id ?? userData.value?.supplier?.boss?.id ?? null

  return userData.value?.supplier?.id ?? null
}

const syncNotificationSettingsLocalState = (settings, notificationPayload = null) => {
  settingsData.value = {
    ...(settingsData.value || {}),
    ...(settings || {}),
    notification: notificationPayload || settingsData.value?.notification || settingsData.value?.setting_notification || null,
  }
}

const getStoredNotificationSettings = () => {
  if (isAdminRole.value)
    return configsStores.getFeaturedConfig('notifications') ?? null

  return settingsData.value?.notification || settingsData.value?.setting_notification || null
}

const hydrateNotificationForm = () => {
  const notificationSettings = getStoredNotificationSettings()

  notifyViaSound.value = notificationSettings?.notify_via_sound !== undefined && notificationSettings?.notify_via_sound !== null
    ? Number(notificationSettings.notify_via_sound) === 1
    : DEFAULT_NOTIFY_VIA_SOUND

  notifyViaEmail.value = notificationSettings?.notify_via_email !== undefined && notificationSettings?.notify_via_email !== null
    ? Number(notificationSettings.notify_via_email) === 1
    : DEFAULT_NOTIFY_VIA_EMAIL

  sendReminders.value = notificationSettings?.send_reminders !== undefined && notificationSettings?.send_reminders !== null
    ? Number(notificationSettings.send_reminders) === 1
    : DEFAULT_SEND_REMINDERS

  hours.value = notificationSettings?.hours !== undefined && notificationSettings?.hours !== null
    ? Number(notificationSettings.hours)
    : DEFAULT_REMINDER_HOURS

  notifyOnDocumentSigned.value = notificationSettings?.notify_on_document_signed !== undefined && notificationSettings?.notify_on_document_signed !== null
    ? Number(notificationSettings.notify_on_document_signed) === 1
    : DEFAULT_NOTIFY_ON_DOCUMENT_SIGNED

  notifyOnAgreementSigned.value = notificationSettings?.notify_on_agreement_signed !== undefined && notificationSettings?.notify_on_agreement_signed !== null
    ? Number(notificationSettings.notify_on_agreement_signed) === 1
    : DEFAULT_NOTIFY_ON_AGREEMENT_SIGNED
}

const onSubmit = async () => {
  if (role.value === 'User')
    return

  isRequestOngoing.value = true

  try {
    const payload = {
      notify_via_sound: notifyViaSound.value ? 1 : 0,
      notify_via_email: notifyViaEmail.value ? 1 : 0,
      send_reminders: sendReminders.value ? 1 : 0,
      hours: hours.value ?? DEFAULT_REMINDER_HOURS,
      notify_on_document_signed: notifyOnDocumentSigned.value ? 1 : 0,
      notify_on_agreement_signed: notifyOnAgreementSigned.value ? 1 : 0,
    }

    if (isAdminRole.value) {
      await configsStores.postFeature({
        key: 'notifications',
        params: { value: payload },
      })

      configsStores.configs['notifications'] = payload

      advisor.value = { type: 'success', message: 'Uppdaterad konfiguration!', show: true }
    } else {
      const supplierId = resolveSettingsSupplierId()

      if (!supplierId)
        return

      const response = await settingsStore.notifications({
        id: supplierId,
        data: {
          ...payload,
          notification_id: settingsData.value?.setting_notification_id
            ?? settingsData.value?.notification?.id
            ?? settingsData.value?.setting_notification?.id
            ?? null,
        },
      })

      const settingNotificationId = response?.data?.data?.settings?.setting_notification_id
        ?? settingsData.value?.setting_notification_id
        ?? null

      const persistedNotification = { ...payload, id: settingNotificationId }

      advisor.value = {
        type: response.data.success ? 'success' : 'error',
        message: response.data.success ? 'Uppdaterad konfiguration!' : response.data.message,
        show: true,
      }

      syncNotificationSettingsLocalState(response?.data?.data?.settings, persistedNotification)
    }

    setTimeout(() => { advisor.value = { type: '', message: '', show: false } }, 3000)
  } finally {
    isRequestOngoing.value = false
  }
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(async () => {
  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value?.roles?.[0]?.name ?? ''
  isRequestOngoing.value = true

  try {
    if (isAdminRole.value) {
      await configsStores.getFeature('notifications')
    } else {
      const loggedUserId = resolveLoggedUserId()

      settingsData.value = loggedUserId
        ? await settingsStore.showSettings(loggedUserId)
        : null
    }
  } catch {
    settingsData.value = null
  } finally {
    isRequestOngoing.value = false
  }

  hydrateNotificationForm()
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
              Meddelanden
            </span>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Allmänt</span>
                <span class="text-settings">
                  Hantera grundläggande notiser för ditt konto.
                </span>
              </div>
            </div>
            <div class="settings-layout__content d-flex flex-column gap-6">
              <div class="d-flex gap-4 align-start card-form">
                <VSwitch
                  v-model="notifyViaSound"
                  class="reminders-switch"
                  hide-details
                  inset
                  :readonly="role === 'User'"
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">Ljud för aviseringar</span>
                      <span class="reminders-description">
                       Spela upp ett ljud vid nya händelser i systemet.
                      </span>
                    </div>
                  </template>
                </VSwitch>             
              </div>

              <div class="d-flex gap-4 align-start">
                <VSwitch
                  v-model="notifyViaEmail"
                  class="reminders-switch"
                  hide-details
                  inset
                  :readonly="role === 'User'"
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">E-postnotiser</span>
                      <span class="reminders-description">
                       Få uppdateringar skickade till din e-post.
                      </span>
                    </div>
                  </template>
                </VSwitch>             
              </div>

              <div class="d-flex gap-4 align-start">
                
                <VSwitch
                  v-model="sendReminders"
                  class="reminders-switch"
                  hide-details
                  inset
                  :readonly="role === 'User'"
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column card-form">
                      <span class="reminders-title">Uppgifter och påminnelser</span>
                      <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                      <AppAutocomplete
                        v-model="hours"
                        :items="hoursOptions"
                        :item-title="item => item.name"
                        :item-value="item => item.id"
                        :rules="[requiredValidator]"
                        :disabled="role === 'User'"
                        autocomplete="off"
                        clearable
                        clear-icon="tabler-x"
                      />
                      </div>
                      <span class="reminders-description">
                       Få påminnelser om aktiviteter som behöver göras.
                      </span>
                    </div>
                  </template>
                </VSwitch>        
              </div>
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Avtal & dokument</span>
                <span class="text-settings">
                  Hantera notiser för avtal & dokument. 
                </span>
              </div>
            </div>
            <div class="settings-layout__content d-flex flex-column gap-6">
              <div class="d-flex gap-4 align-start">
                <VSwitch
                  v-model="notifyOnDocumentSigned"
                  class="reminders-switch"
                  hide-details
                  inset
                  :readonly="role === 'User'"
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">Dokument signerade</span>
                      <span class="reminders-description">
                        Få notis när ett dokument har signerats.
                      </span>
                    </div>
                  </template>
                </VSwitch>             
              </div>

              <div class="d-flex gap-4 align-start">
                <VSwitch
                  v-model="notifyOnAgreementSigned"
                  class="reminders-switch"
                  hide-details
                  inset
                  :readonly="role === 'User'"
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">Avtal signerade</span>
                      <span class="reminders-description">
                        Få notis när ett avtal har signerats.
                      </span>
                    </div>
                  </template>
                </VSwitch>             
              </div>

              <!-- 👉 Form Actions -->
              <div 
                v-if="role !== 'User'"
                class="d-flex justify-start gap-3 flex-wrap dialog-actions"
                :class="windowWidth < 1024 ? 'pb-4' : ''"
              >              
                <VBtn 
                  type="submit" 
                  class="btn-gradient"
                  :class="windowWidth < 1024 ? 'w-100' : 'w-25'"
                  :disabled="role === 'User'"
                  @click="onSubmit"
                >
                  Spara
                </VBtn>
              </div>
            </div>
          </div>
        </VCardText>
      </VCard>
    </section>
</template>

<style lang="scss">
  .reminders-title {
    font-weight: 700;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;
    color: #454545;
  }

  .reminders-description {
    margin-top: 4px;
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;
    color: #454545;
  }

  .reminders-switch {
    width: 100%;
  }

  .reminders-switch .v-label {
    display: block;
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
  }

  @media (max-width: 1023px) {
    .reminders-switch .v-label {
      max-width: 100%;
    }
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
