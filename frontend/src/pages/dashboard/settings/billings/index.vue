<script setup>

import { requiredValidator } from '@/@core/utils/validators'
import { useSettingsStore } from '@/stores/useSettings'
import { useConfigsStores } from '@/stores/useConfigs'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import billing1 from '@images/billings/1.svg'
import billing2 from '@images/billings/2.svg'
import billing3 from '@images/billings/3.svg'
import billing4 from '@images/billings/4.svg'

const DEFAULT_PRIMARY_COLOR = '#29ABE2'
const DEFAULT_SECONDARY_COLOR = '#E2F2FC'
const DEFAULT_BILLING_DUE_DATES = 5
const DEFAULT_BILLING_TERMS_AND_CONDITIONS = 'Efter förfallodagen debiteras ränta enligt räntelagen'
const DEFAULT_BILLING_SEND_REMINDER = true
const DEFAULT_BILLING_DELIVERY_METHOD = 'email'
const SECONDARY_TINT_STRENGTH = 0.13
const brandColorOptions = [
  '#C1272D',
  '#F15A24',
  '#FBB03B',
  '#39B54A',
  '#29ABE2',
  '#0071BC',
  '#662D91',
  '#9E005D',
  '#ED1E79',
]

const { width: windowWidth } = useWindowSize()
const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')

const sectionEl = ref(null)
const selectedBillingTemplate = ref('classic')
const automaticRemindersEnabled = ref(DEFAULT_BILLING_SEND_REMINDER)
const deliveryMethod = ref(DEFAULT_BILLING_DELIVERY_METHOD)
const settingsStore = useSettingsStore()
const configsStores = useConfigsStores()
const userData = ref(null)
const settingsData = ref(null)
const role = ref('')
const isAdminRole = computed(() => role.value === 'SuperAdmin' || role.value === 'Administrator')
const isBillingPreviewReady = ref(false)

const advisor = ref({
  message: '',
  show: false,
  type: '',
})

const due_date = ref(null)
const terms_and_conditions = ref('')
const isTermsDialogVisible = ref(false)
const termsDialogDraft = ref('')

const isRequestOngoing = ref(true);
const billingPreviewSources = ref({
  classic: '',
  modern1: '',
  modern2: '',
  compact: '',
})

const hexToRgb = hex => {
  const normalized = (hex || '').replace('#', '')

  return {
    r: Number.parseInt(normalized.slice(0, 2), 16),
    g: Number.parseInt(normalized.slice(2, 4), 16),
    b: Number.parseInt(normalized.slice(4, 6), 16),
  }
}

const rgbToHex = (r, g, b) => `#${[r, g, b].map(channel => channel.toString(16).padStart(2, '0')).join('')}`.toUpperCase()

const normalizeHexColor = value => {
  if (typeof value !== 'string')
    return ''

  const normalized = value.trim().toUpperCase()

  return /^#[0-9A-F]{6}$/.test(normalized) ? normalized : ''
}

const getSecondaryColorFromPrimary = primary => {
  const { r, g, b } = hexToRgb(primary)
  const blendWithWhite = channel => Math.round((channel * SECONDARY_TINT_STRENGTH) + (255 * (1 - SECONDARY_TINT_STRENGTH)))

  return rgbToHex(blendWithWhite(r), blendWithWhite(g), blendWithWhite(b))
}

const resolveLoggedUserId = () => {
  if (role.value === 'User')
    return userData.value?.supplier?.boss?.user_id ?? userData.value?.supplier?.boss?.user?.id ?? null

  return userData.value?.id ?? userData.value?.user?.id ?? null
}

const resolveBillingPreviewColors = () => {
  const colorSource = isAdminRole.value
    ? configsStores.getFeaturedConfig('color') ?? {}
    : settingsData.value ?? {}

  const settingColorId = Number(colorSource?.setting_color_id)

  if (Number.isInteger(settingColorId) && settingColorId >= 1 && settingColorId <= brandColorOptions.length) {
    const primaryColor = brandColorOptions[settingColorId - 1]

    return {
      primaryColor,
      secondaryColor: getSecondaryColorFromPrimary(primaryColor),
    }
  }

  const primaryColor = normalizeHexColor(colorSource?.primary_color)
  const secondaryColor = normalizeHexColor(colorSource?.secondary_color)

  if (primaryColor) {
    return {
      primaryColor,
      secondaryColor: secondaryColor || getSecondaryColorFromPrimary(primaryColor),
    }
  }

  return {
    primaryColor: DEFAULT_PRIMARY_COLOR,
    secondaryColor: DEFAULT_SECONDARY_COLOR,
  }
}

const buildRecoloredSvgDataUrl = async (assetUrl, primaryColor, secondaryColor) => {
  const response = await fetch(assetUrl)
  const svgMarkup = await response.text()
  const updatedSvgMarkup = svgMarkup
    .replace(/#29ABE2/gi, primaryColor)
    .replace(/#E2F2FC/gi, secondaryColor)

  return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(updatedSvgMarkup)}`
}

const loadBillingPreviewSources = async () => {
  const { primaryColor, secondaryColor } = resolveBillingPreviewColors()
  isBillingPreviewReady.value = false

  try {
    isRequestOngoing.value = true

    const [classic, modern1, modern2, compact] = await Promise.all([
      buildRecoloredSvgDataUrl(billing1, primaryColor, secondaryColor),
      buildRecoloredSvgDataUrl(billing2, primaryColor, secondaryColor),
      buildRecoloredSvgDataUrl(billing3, primaryColor, secondaryColor),
      buildRecoloredSvgDataUrl(billing4, primaryColor, secondaryColor),
    ])

    billingPreviewSources.value = { classic, modern1, modern2, compact }
  } catch {
    billingPreviewSources.value = { classic: billing1, modern1: billing2, modern2: billing3, compact: billing4 }
  } finally {
    isBillingPreviewReady.value = true
    isRequestOngoing.value = false
  }
}

const getSelectedBillingType = () => {
  const mapping = {
    classic: 1,
    'modern-1': 2,
    'modern-2': 3,
    compact: 4,
  }

  return mapping[selectedBillingTemplate.value] || 1
}

const getBillingTemplateByType = type => {
  const mapping = {
    1: 'classic',
    2: 'modern-1',
    3: 'modern-2',
    4: 'compact',
  }

  return mapping[Number(type)] || 'classic'
}

const resolveSettingsSupplierId = () => {
  if (role.value === 'User')
    return userData.value?.supplier?.boss_id ?? userData.value?.supplier?.boss?.id ?? null

  return userData.value?.supplier?.id ?? null
}

const syncBillingSettingsLocalState = (settings, billingPayload = null) => {
  settingsData.value = {
    ...(settingsData.value || {}),
    ...(settings || {}),
    billing: billingPayload || settingsData.value?.billing || settingsData.value?.setting_billing || null,
  }
}

const getStoredBillingSettings = () => {
  if (isAdminRole.value)
    return configsStores.getFeaturedConfig('billings') ?? null

  return settingsData.value?.billing || settingsData.value?.setting_billing || null
}

const hydrateBillingForm = () => {
  const billingSettings = getStoredBillingSettings()

  selectedBillingTemplate.value = getBillingTemplateByType(billingSettings?.type ?? 1)

  due_date.value = billingSettings?.due_dates !== undefined && billingSettings?.due_dates !== null
    ? Number(billingSettings.due_dates) || DEFAULT_BILLING_DUE_DATES
    : DEFAULT_BILLING_DUE_DATES

  terms_and_conditions.value = typeof billingSettings?.terms_and_conditions === 'string' && billingSettings.terms_and_conditions.trim()
    ? billingSettings.terms_and_conditions
    : DEFAULT_BILLING_TERMS_AND_CONDITIONS

  automaticRemindersEnabled.value = billingSettings?.send_reminder !== undefined && billingSettings?.send_reminder !== null
    ? Number(billingSettings.send_reminder) === 1
    : DEFAULT_BILLING_SEND_REMINDER

  deliveryMethod.value = billingSettings?.send_notifications !== undefined && billingSettings?.send_notifications !== null
    ? (Number(billingSettings.send_notifications) === 1 ? 'email-sms' : 'email')
    : DEFAULT_BILLING_DELIVERY_METHOD
}

const onSubmit = async () => {
  if (role.value === 'User')
    return

  isRequestOngoing.value = true

  try {
    const payload = {
      type: getSelectedBillingType(),
      due_dates: Number(due_date.value) || 1,
      terms_and_conditions: terms_and_conditions.value,
      send_reminder: automaticRemindersEnabled.value ? 1 : 0,
      send_notifications: deliveryMethod.value === 'email-sms' ? 1 : 0,
    }

    if (isAdminRole.value) {
      await configsStores.postFeature({
        key: 'billings',
        params: { value: payload },
      })

      configsStores.configs['billings'] = payload

      advisor.value = { type: 'success', message: 'Uppdaterad konfiguration!', show: true }
    } else {
      const supplierId = resolveSettingsSupplierId()

      if (!supplierId)
        return

      const response = await settingsStore.billings({
        id: supplierId,
        data: {
          ...payload,
          billing_id: settingsData.value?.setting_billing_id
            ?? settingsData.value?.billing?.id
            ?? settingsData.value?.setting_billing?.id
            ?? null,
        },
      })

      const settingBillingId = response?.data?.data?.settings?.setting_billing_id
        ?? settingsData.value?.setting_billing_id
        ?? null

      const persistedBilling = { ...payload, id: settingBillingId }

      advisor.value = {
        type: response.data.success ? 'success' : 'error',
        message: response.data.success ? 'Uppdaterad konfiguration!' : response.data.message,
        show: true,
      }

      syncBillingSettingsLocalState(response?.data?.data?.settings, persistedBilling)
    }

    setTimeout(() => { advisor.value = { type: '', message: '', show: false } }, 3000)
  } finally {
    isRequestOngoing.value = false
  }
}

const openTermsDialog = () => {
  if (role.value === 'User')
    return

  termsDialogDraft.value = terms_and_conditions.value || ''
  isTermsDialogVisible.value = true
}

const closeTermsDialog = () => {
  isTermsDialogVisible.value = false
}

const saveTermsDialog = () => {
  terms_and_conditions.value = termsDialogDraft.value
  closeTermsDialog()
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

  try {
    if (isAdminRole.value) {
      await Promise.all([
        configsStores.getFeature('billings'),
        configsStores.getFeature('color'),
      ])
    } else {
      const loggedUserId = resolveLoggedUserId()

      settingsData.value = loggedUserId
        ? await settingsStore.showSettings(loggedUserId)
        : null
    }
  } catch {
    settingsData.value = null
  }

  hydrateBillingForm()
  loadBillingPreviewSources()
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
              Fakturor
            </span>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Fakturautseende</span>
                <span class="text-settings">
                  Välj en design som representerar ditt företag. Förhandsgranska hur fakturan visas för kunden.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <span class="avatar-text">
                Välj fakturamall
              </span>

              <div v-if="isBillingPreviewReady" class="d-flex gap-4 mt-2 billing-options">
                <button
                  type="button"
                  class="billing-option d-flex flex-column gap-2"
                  :disabled="role === 'User'"
                  :class="{ 'billing-option--selected': selectedBillingTemplate === 'classic' }"
                  @click="selectedBillingTemplate = 'classic'"
                >
                  <div class="billing-option__preview">
                    <img :src="billingPreviewSources.classic" alt="Billing 1" />
                  </div>
                  <span class="avatar-text text-neutral-3 ps-3">Klassisk</span>
                </button>
                <button
                  type="button"
                  class="billing-option d-flex flex-column gap-2"
                  :disabled="role === 'User'"
                  :class="{ 'billing-option--selected': selectedBillingTemplate === 'modern-1' }"
                  @click="selectedBillingTemplate = 'modern-1'"
                >
                  <div class="billing-option__preview">
                    <img :src="billingPreviewSources.modern1" alt="Billing 2" />
                  </div>
                  <span class="avatar-text text-neutral-3 ps-3">Modern 1</span>
                </button>
                <button
                  type="button"
                  class="billing-option d-flex flex-column gap-2"
                  :disabled="role === 'User'"
                  :class="{ 'billing-option--selected': selectedBillingTemplate === 'modern-2' }"
                  @click="selectedBillingTemplate = 'modern-2'"
                >
                  <div class="billing-option__preview">
                    <img :src="billingPreviewSources.modern2" alt="Billing 3" />
                  </div>
                  <span class="avatar-text text-neutral-3 ps-3">Modern 2</span>
                </button>
                <button
                  type="button"
                  class="billing-option d-flex flex-column gap-2"
                  :disabled="role === 'User'"
                  :class="{ 'billing-option--selected': selectedBillingTemplate === 'compact' }"
                  @click="selectedBillingTemplate = 'compact'"
                >
                  <div class="billing-option__preview">
                    <img :src="billingPreviewSources.compact" alt="Billing 4" />
                  </div>
                  <span class="avatar-text text-neutral-3 ps-3">Kompakt</span>
                </button>
              </div>
              <div v-else class="mt-2" style="min-height: 180px;" />
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-6">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Betalningsvillkor & Ränta</span>
                <span class="text-settings">
                  Ställ in betalningsvillkor och dröjsmålsränta för dina fakturor.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <div class="d-flex flex-column gap-6 card-form">
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Betalningsvillkor (Dagar)*" />
                    <VTextField
                      type="number"
                      v-model="due_date"
                      :disabled="role === 'User'"
                      min="1"
                      :rules="[requiredValidator]"
                    />
                </div>
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                    <VLabel class="mb-1 text-body-2 me-2 text-high-emphasis" text="Dröjsmålsränta*" />
                    <VTooltip location="bottom" max-width="200"> 
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          <VIcon icon="custom-circle-help" size="24" />
                        </span>
                      </template>
                      Den ränta som debiteras vid sen betalning enligt dina villkor.
                    </VTooltip>
                    <VTextField
                      v-model="terms_and_conditions"
                      :disabled="role === 'User'"
                      :rules="[requiredValidator]"
                      readonly
                      class="terms-trigger-field"
                      @click="openTermsDialog"
                      @keydown.enter.prevent="openTermsDialog"
                    />
                </div>
              </div>
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Automatiska påminnelser</span>
                <span class="text-settings">
                  Skicka påminnelser automatiskt till kunden när en faktura passerar förfallodatum.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <div class="d-flex gap-4 align-start">
                <VSwitch
                  v-model="automaticRemindersEnabled"
                  :readonly="role === 'User'"
                  class="reminders-switch"
                  hide-details
                  inset
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">Aktivera påminnelser</span>
                      <span class="reminders-description">
                        En påminnelse skickas till kunden via vald leveransmetod en dag efter förfallodatum.
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
                <span class="subtitle-settings">Leveransmetod</span>
                <span class="text-settings">
                  Välj hur fakturor skickas till dina kunder.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <VRadioGroup
                v-model="deliveryMethod"
                :disabled="role === 'User'"
                hide-details
                false-icon="custom-settings-checkbox-false"
                true-icon="custom-settings-checkbox-true"
                class="delivery-method-group"
              >
                <VRadio value="email" class="delivery-method-option">
                  <template #label>
                    <div class="delivery-method-content">
                      <span class="delivery-method-title">Endast E-post</span>
                      <span class="delivery-method-description">
                        Fakturan skickas som en PDF-lank till kundens e-post. Standardalternativ utan extra kostnad.
                      </span>
                    </div>
                  </template>
                </VRadio>

                <VRadio value="email-sms" class="delivery-method-option" disabled>
                  <template #label>
                    <div class="delivery-method-content">
                      <span class="delivery-method-title">E-post + SMS</span>
                      <span class="delivery-method-description">
                        Fakturan skickas via e-post och avisering skickas via SMS. SMS medfor en extra kostnad.
                      </span>
                    </div>
                  </template>
                </VRadio>
              </VRadioGroup>

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
                  @click="onSubmit"
                >
                  Spara
                </VBtn>
              </div>
            </div>
          </div>
        </VCardText>

      </VCard>

      <VDialog
        v-model="isTermsDialogVisible"
        persistent
        class="action-dialog"
      >
        <VBtn
          icon
          class="btn-white close-btn"
          @click="closeTermsDialog"
        >
          <VIcon size="16" icon="custom-close" />
        </VBtn>

        <VCard>
          <VCardText class="dialog-title-box">
            <div class="dialog-title">Textredigering</div>
          </VCardText>

          <VCardText class="dialog-text card-form">
            <VTextarea
              v-model="termsDialogDraft"
              rows="6"
              :max-rows="windowWidth < 1024 ? 10 : 14"
              auto-grow
              no-resize
              class="terms-editor-textarea"
              :rules="[requiredValidator]"
            />
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn
              class="btn-gradient"
              @click="saveTermsDialog"
            >
              Spara
            </VBtn>
          </VCardText>
        </VCard>
      </VDialog>
    </section>
</template>

<style lang="scss">
  .avatar-text {
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;
    color: #454545;
  }

  .billing-options {
    flex-wrap: wrap;
  }

  .billing-option {
    padding: 0;
    border: 0;
    background: transparent;
    transition: background-color 0.2s ease;
    cursor: pointer;
    text-align: left;
  }

  .billing-option:disabled {
    cursor: not-allowed;
    opacity: 0.6;
  }

  .billing-option__preview {
    padding: 8px;
    border-radius: 13px;
    border: 2px solid transparent;
    overflow: hidden;
    transition: border-color 0.2s ease, background-color 0.2s ease;
  }

  .billing-option__preview img {
    display: block;
    width: 100%;
    height: auto;
  }

  .billing-option:hover .billing-option__preview {
    background-color: #ffffff;
  }

  .billing-option--selected .billing-option__preview {
    border-color: #BFBFBF;
    background-color: #ffffff;
  }

  /*.billing-option--selected .avatar-text.ps-3 {
    padding-left: 0 !important;
  }*/

  .billing-option:focus-visible {
    outline: 2px solid #22a9e1;
    outline-offset: 2px;
  }

  .terms-trigger-field .v-field__input {
    cursor: pointer;
  }

  .terms-editor-textarea .v-field {
    background-color: #f6f6f6;
  }

  .terms-editor-textarea textarea {
    overflow-y: auto !important;
  }

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

  .delivery-method-group .v-selection-control {
    align-items: start !important;
  }

  .delivery-method-group .v-radio.v-selection-control--dirty .v-selection-control__input .iconify--custom, .v-radio-btn.v-selection-control--dirty .v-selection-control__input .iconify--custom {
    box-shadow: none !important;
  }

  .delivery-method-group .v-radio .v-selection-control__input .iconify--custom, .v-radio-btn .v-selection-control__input .iconify--custom {
    block-size: 24px !important;
    font-size: 24px !important;
    inline-size: 24px!important;
  }

  .delivery-method-group {
    width: 100%;
  }

  .delivery-method-option {
    margin-bottom: 24px;
  }

  .delivery-method-option .v-selection-control {
    align-items: flex-start;
  }

  .delivery-method-option .v-label {
    display: block;
    flex: 1;
    min-width: 0;
    max-width: 100%;
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
  }

  .delivery-method-content {
    display: flex;
    flex-direction: column;
    width: 100%;
    margin-left: 16px;
  }

  .delivery-method-title {
    font-weight: 700;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;
    color: #454545;
  }

  .delivery-method-description {
    margin-top: 2px;
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;

    color: #454545;
    white-space: normal;
    word-break: break-word;
  }

  @media (max-width: 1023px) {
    .billing-options {
      flex-wrap: nowrap;
      overflow-x: auto;
      overflow-y: hidden;
      padding-bottom: 4px;
      -ms-overflow-style: none;
      scrollbar-width: none;
      -webkit-overflow-scrolling: touch;
    }

    .billing-options::-webkit-scrollbar {
      display: none;
    }

    .billing-option {
      flex: 0 0 auto;
    }

    .reminders-switch .v-label {
      max-width: 100%;
    }

    .delivery-method-description {
      font-size: 14px;
      line-height: 22px;
    }
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
