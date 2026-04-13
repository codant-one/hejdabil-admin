<script setup>

import { requiredValidator } from '@/@core/utils/validators'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import billing1 from '@images/billings/1.svg'
import billing2 from '@images/billings/2.svg'
import billing3 from '@images/billings/3.svg'
import billing4 from '@images/billings/4.svg'

const DEFAULT_PRIMARY_COLOR = '#29ABE2'
const DEFAULT_SECONDARY_COLOR = '#E2F2FC'
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
const sectionEl = ref(null)
const selectedBillingTemplate = ref('classic')
const automaticRemindersEnabled = ref(true)
const deliveryMethod = ref('email')

const due_date = ref(5);
const terms_and_conditions = ref('Efter förfallodagen debiteras ränta enligt räntelagen');

const isRequestOngoing = ref(false);
const billingPreviewSources = ref({
  classic: billing1,
  modern1: billing2,
  modern2: billing3,
  compact: billing4,
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

const resolveBillingPreviewColors = () => {
  const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
  const supplierSettings = userData?.supplier?.settings
  const settingColorId = Number(supplierSettings?.setting_color_id)

  if (Number.isInteger(settingColorId) && settingColorId >= 1 && settingColorId <= brandColorOptions.length) {
    const primaryColor = brandColorOptions[settingColorId - 1]

    return {
      primaryColor,
      secondaryColor: getSecondaryColorFromPrimary(primaryColor),
    }
  }

  const primaryColor = normalizeHexColor(supplierSettings?.primary_color)
  const secondaryColor = normalizeHexColor(supplierSettings?.secondary_color)

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

onMounted(() => {
  loadBillingPreviewSources();
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

              <div class="d-flex gap-4 mt-2 billing-options">
                <button
                  type="button"
                  class="billing-option d-flex flex-column gap-2"
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
                  :class="{ 'billing-option--selected': selectedBillingTemplate === 'compact' }"
                  @click="selectedBillingTemplate = 'compact'"
                >
                  <div class="billing-option__preview">
                    <img :src="billingPreviewSources.compact" alt="Billing 4" />
                  </div>
                  <span class="avatar-text text-neutral-3 ps-3">Kompakt</span>
                </button>
              </div>
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
                      :rules="[requiredValidator]"
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

                <VRadio value="email-sms" class="delivery-method-option">
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
                class="d-flex justify-start gap-3 flex-wrap dialog-actions"
              >
              
                <VBtn 
                  type="submit" 
                  class="btn-gradient"
                  :class="windowWidth < 1024 ? 'w-100' : 'w-25'"
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
