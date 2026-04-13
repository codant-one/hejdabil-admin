<script setup>

import { requiredValidator } from '@/@core/utils/validators'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import agreement1 from '@images/agreements/1.svg'
import agreement2 from '@images/agreements/2.svg'
import agreement3 from '@images/agreements/3.svg'

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
const selectedagreementTemplate = ref('classic')

const due_date = ref(7);
const terms_and_conditions_purchase = ref('Fordonet säljs i befintligt skick om inget annat avtalats. Säljaren intygar att denne är rättmätig ägare och att fordonet är fritt från skulder och andra belastningar, om inget annat anges. Köparen har haft möjlighet att undersöka fordonet och godkänner dess skick. Äganderätten övergår först när full betalning har erlagts.');
const terms_and_conditions_sales = ref('Fordonet säljs i befintligt skick med eventuella garantier enligt avtalet. Köparen ansvarar för att kontrollera fordonet vid leverans. Reklamation ska ske inom skälig tid. Vid försenad betalning kan avgifter tillkomma. Äganderätten kvarstår hos säljaren tills full betalning skett.');
const terms_and_conditions_mediation = ref('Förmedlaren säljer fordonet för uppdragsgivarens räkning. Fordonet ägs av uppdragsgivaren tills försäljning genomförts. Uppdragsgivaren ansvarar för fordonets skick och uppgifter. Förmedlaren har rätt till provision enligt avtal.');
const terms_and_conditions_business = ref('Offerten är giltig under angiven period och är inte bindande förrän den accepterats. Priset baseras på tillgänglig information och kan justeras. Säljaren har rätt att återkalla offerten innan accept.');

const automaticRemindersEnabled = ref(true)
const deliveryMethod = ref('email')

const isRequestOngoing = ref(false);
const agreementPreviewSources = ref({
  classic: agreement1,
  modern: agreement2,
  compact: agreement3,
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

const resolveAgreementPreviewColors = () => {
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

const loadAgreementPreviewSources = async () => {
  const { primaryColor, secondaryColor } = resolveAgreementPreviewColors()

  try {
    isRequestOngoing.value = true

    const [classic, modern, compact] = await Promise.all([
      buildRecoloredSvgDataUrl(agreement1, primaryColor, secondaryColor),
      buildRecoloredSvgDataUrl(agreement2, primaryColor, secondaryColor),
      buildRecoloredSvgDataUrl(agreement3, primaryColor, secondaryColor),
    ])

    agreementPreviewSources.value = { classic, modern, compact }
  } catch {
    agreementPreviewSources.value = { classic: agreement1, modern: agreement2, compact: agreement3 }
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
  loadAgreementPreviewSources();
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
              Avtal
            </span>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Avtalsutseende</span>
                <span class="text-settings">
                  Välj en design för avtal och prisförslag. Förhandsgranska hur dokumentet visas för kunden.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <span class="avatar-text">
                Välj avtalsmall
              </span>

              <div class="d-flex gap-4 mt-2 agreement-options">
                <button
                  type="button"
                  class="agreement-option d-flex flex-column gap-2"
                  :class="{ 'agreement-option--selected': selectedagreementTemplate === 'classic' }"
                  @click="selectedagreementTemplate = 'classic'"
                >
                  <div class="agreement-option__preview">
                    <img :src="agreementPreviewSources.classic" alt="Agreement 1" />
                  </div>
                  <span class="avatar-text text-neutral-3 ps-3">Klassisk</span>
                </button>
                <button
                  type="button"
                  class="agreement-option d-flex flex-column gap-2"
                  :class="{ 'agreement-option--selected': selectedagreementTemplate === 'modern-1' }"
                  @click="selectedagreementTemplate = 'modern-1'"
                >
                  <div class="agreement-option__preview">
                    <img :src="agreementPreviewSources.modern" alt="Agreement 2" />
                  </div>
                  <span class="avatar-text text-neutral-3 ps-3">Modern</span>
                </button>
                <button
                  type="button"
                  class="agreement-option d-flex flex-column gap-2"
                  :class="{ 'agreement-option--selected': selectedagreementTemplate === 'modern-2' }"
                  @click="selectedagreementTemplate = 'modern-2'"
                >
                  <div class="agreement-option__preview">
                    <img :src="agreementPreviewSources.compact" alt="Agreement 3" />
                  </div>
                  <span class="avatar-text text-neutral-3 ps-3">Kompakt</span>
                </button>
              </div>
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Standardvillkor</span>
                <span class="text-settings">
                  Ställ in förvalda villkor för dina avtal. Fylls i automatiskt och kan justeras vid behov.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <div class="d-flex flex-column gap-6 card-form">
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                    <VLabel class="mb-1 text-body-2 me-2 text-high-emphasis" text="Villkor för inköpsavtal*" />
                    <VTooltip location="bottom" max-width="200"> 
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          <VIcon icon="custom-circle-help" size="24" />
                        </span>
                      </template>
                      Dessa villkor fylls i automatiskt i avtalet. Kan justeras vid behov.
                    </VTooltip>
                    <VTextField
                      v-model="terms_and_conditions_purchase"
                      :rules="[requiredValidator]"
                    />
                </div>
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                    <VLabel class="mb-1 text-body-2 me-2 text-high-emphasis" text="Villkor för försäljningsavtal*" />
                    <VTooltip location="bottom" max-width="200"> 
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          <VIcon icon="custom-circle-help" size="24" />
                        </span>
                      </template>
                      Dessa villkor fylls i automatiskt i avtalet. Kan justeras vid behov.
                    </VTooltip>
                    <VTextField
                      v-model="terms_and_conditions_sales"
                      :rules="[requiredValidator]"
                    />
                </div>
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                    <VLabel class="mb-1 text-body-2 me-2 text-high-emphasis" text="Villkor för förmedlingsavtal*" />
                    <VTooltip location="bottom" max-width="200"> 
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          <VIcon icon="custom-circle-help" size="24" />
                        </span>
                      </template>
                      Dessa villkor fylls i automatiskt i avtalet. Kan justeras vid behov.
                    </VTooltip>
                    <VTextField
                      v-model="terms_and_conditions_mediation"
                      :rules="[requiredValidator]"
                    />
                </div>
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                    <VLabel class="mb-1 text-body-2 me-2 text-high-emphasis" text="Villkor för prisförslag*" />
                    <VTooltip location="bottom" max-width="200"> 
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          <VIcon icon="custom-circle-help" size="24" />
                        </span>
                      </template>
                      Dessa villkor fylls i automatiskt i avtalet. Kan justeras vid behov.
                    </VTooltip>
                    <VTextField
                      v-model="terms_and_conditions_business"
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
                <span class="subtitle-settings">Påminnelser för signering</span>
                <span class="text-settings">
                  Skicka påminnelser till kunder som ännu inte har signerat sina avtal.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <div class="d-flex flex-column gap-6 card-form">
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Skicka påminnelse efter (dagar)*" />
                    <VTextField
                      type="number"
                      v-model="due_date"
                      min="1"
                      :rules="[requiredValidator]"
                    />
                </div>

                <div class="d-flex gap-4 align-start">
                  <VSwitch
                    v-model="automaticRemindersEnabled"
                    class="reminders-switch"
                    hide-details
                    inset
                  >
                    <template v-slot:label>
                      <span class="reminders-description">
                        En påminnelse skickas via vald leveransmetod om avtalet inte har signerats inom angivet antal dagar.
                      </span>
                    </template>
                  </VSwitch>                
                </div>
              </div>
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Leveransmetod för signering</span>
                <span class="text-settings">
                  Välj hur avtal skickas till kunder för digital signering.
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
                        Avtalet skickas som en säker länk till kundens e-post för signering. Standardalternativ utan extra kostnad.
                      </span>
                    </div>
                  </template>
                </VRadio>

                <VRadio value="email-sms" class="delivery-method-option">
                  <template #label>
                    <div class="delivery-method-content">
                      <span class="delivery-method-title">E-post + SMS</span>
                      <span class="delivery-method-description">
                        Avtalet skickas via e-post och avisering via SMS. Extra kostnad tillkommer.
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

  .agreement-options {
    flex-wrap: wrap;
  }

  .agreement-option {
    padding: 0;
    border: 0;
    background: transparent;
    transition: background-color 0.2s ease;
    cursor: pointer;
    text-align: left;
  }

  .agreement-option__preview {
    padding: 8px;
    border-radius: 13px;
    border: 2px solid transparent;
    overflow: hidden;
    transition: border-color 0.2s ease, background-color 0.2s ease;
  }

  .agreement-option__preview img {
    display: block;
    width: 100%;
    height: auto;
  }

  .agreement-option:hover .agreement-option__preview {
    background-color: #ffffff;
  }

  .agreement-option--selected .agreement-option__preview {
    border-color: #BFBFBF;
    background-color: #ffffff;
  }

  /*.agreement-option--selected .avatar-text.ps-3 {
    padding-left: 0 !important;
  }*/

  .agreement-option:focus-visible {
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
    .agreement-options {
      flex-wrap: nowrap;
      overflow-x: auto;
      overflow-y: hidden;
      padding-bottom: 4px;
      -ms-overflow-style: none;
      scrollbar-width: none;
      -webkit-overflow-scrolling: touch;
    }

    .agreement-options::-webkit-scrollbar {
      display: none;
    }

    .agreement-option {
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
