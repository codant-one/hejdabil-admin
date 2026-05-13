<script setup>

import { requiredValidator } from '@/@core/utils/validators'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const { width: windowWidth } = useWindowSize()
const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')

const sectionEl = ref(null)
const userData = ref(null)
const settingsData = ref(null)
const role = ref('')
const deliveryMethod = ref('email')

const isRequestOngoing = ref(false);

const advisor = ref({
  message: '',
  show: false,
  type: '',
})

const sms_signing_message = ref('Du har fått ett dokument från {Företagsnamn} för digital signering.');

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
              Avtal
            </span>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-6">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">SMS för signering</span>
                <span class="text-settings">
                  Detta SMS skickas automatiskt till kunden när ett dokument skickas för digital signering via SMS.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <div class="d-flex flex-column gap-6 card-form">
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                    <VLabel class="mb-1 text-body-2 me-2 text-high-emphasis" text="SMS-meddelande" />
                    <VTooltip location="bottom" max-width="200"> 
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          <VIcon icon="custom-circle-help" size="24" />
                        </span>
                      </template>
                      Meddelandet används vid digital signering och kan inte ändras.
                    </VTooltip>
                    <VTextField
                      v-model="sms_signing_message"
                      readonly
                      :rules="[requiredValidator]"
                    />
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
                  Välj hur dokument skickas till kunder för digital signering.
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
                        Dokumentet skickas som en säker länk till kundens e-post för signering. Standardalternativ utan extra kostnad.
                      </span>
                    </div>
                  </template>
                </VRadio>

                <VRadio value="email-sms" class="delivery-method-option" disabled>
                  <template #label>
                    <div class="delivery-method-content">
                      <span class="delivery-method-title">E-post + SMS</span>
                      <span class="delivery-method-description">
                        Dokumentet skickas via e-post och en avisering skickas via SMS. Extra kostnad tillkommer.
                      </span>
                    </div>
                  </template>
                </VRadio>
              </VRadioGroup>

              <!-- 👉 Form Actions -->
              <div 
                class="d-flex justify-start gap-3 flex-wrap dialog-actions"
                :class="windowWidth < 1024 ? 'pb-4' : ''"
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

  .agreement-option:disabled {
    cursor: not-allowed;
    opacity: 0.6;
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
