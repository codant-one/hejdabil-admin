<script setup>

import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();

const props = defineProps({
  customerData: {
    type: Object,
    required: false,
  },
  isSupplier: {
    type: Boolean,
    required: true,
  }
})

const emit = defineEmits([
  ''
])

const company = ref('')
const organization_number = ref('')
const link = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const bank = ref('')
const account_number = ref('')
const name = ref('')
const last_name = ref('')
const email = ref('')

watchEffect(fetchData)

async function fetchData() {
  if(props.isSupplier) {

    //company
    company.value = props.customerData.company
    organization_number.value = props.customerData.organization_number
    link.value = props.customerData.link
    address.value = props.customerData.address
    street.value = props.customerData.street
    postal_code.value = props.customerData.postal_code
    phone.value = props.customerData.phone

    //bank
    bank.value = props.customerData.bank
    account_number.value = props.customerData.account_number

    // contact
    name.value  = props.customerData.user.name
    last_name.value = props.customerData.user.last_name 
    email.value = props.customerData.user.email
  }
}

</script>

<template>
  <!-- eslint-disable vue/no-v-html -->
  <!-- 👉 Payment Methods -->
  <VRow>
    <VCol cols="12" v-if="props.isSupplier">
      <VCard class="company">
        <VCardText class="pb-0 d-flex flex-column gap-y-4">
          <VRow class="my-1">
            <div class="title-tabs">
              Ändra lösenord
            </div>
          </VRow>
          
        </VCardText>

        <VCardText class="d-flex flex-column">
          <VRow>
            <VCol cols="12" class="mt-2" :class="windowWidth < 1024 ? '' : 'px-0'">
              <div class="title-section mb-5">
                  Företagsinformation
              </div>

              <VDivider :class="windowWidth < 1024 ? 'mb-4' : 'mb-8'" />

              <div 
                  class="d-flex flex-wrap"
                  :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                  :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
              >
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Företag
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.company ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Organisationsnummer
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.organization_number ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Hemsida
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.link ?? '-' }}
                  </span>
                </div>

                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Adress
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.address ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Postnummer
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.postal_code ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Stad
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.street ?? '-' }}
                  </span>
                </div>

                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Telefon
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.phone ?? '-' }}
                  </span>
                </div>
               
                
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Swish
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.swish ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    SMS Sender
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.sms_sender ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    VAT reg. no
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.vat ?? '-' }}
                  </span>
                </div>
              </div>
            </VCol>

            <VCol cols="12" class="mt-8" :class="windowWidth < 1024 ? '' : 'px-0'">
              <div class="title-section mb-5">
                  Bankinformation
              </div>

              <VDivider :class="windowWidth < 1024 ? 'mb-4' : 'mb-8'" />

              <div 
                  class="d-flex flex-wrap"
                  :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                  :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
              >
                 <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Bank
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.bank ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Bankgiro
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.iban ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Kontonummer
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.account_number ?? '-' }}
                  </span>
                </div>

                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    IBAN
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.iban_number ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    BIC
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.bic ?? '-' }}
                  </span>
                </div>
                <div class="d-flex"
                  :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
                  :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
                >
                  <span class="text-title">
                    Plusgiro
                  </span>
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.plus_spin ?? '-' }}
                  </span>
                </div>
              </div>
            </VCol>


            <VCol cols="12" md="6" v-if="false && props.customerData.user.user_detail.logo">
              <VImg
                :src="themeConfig.settings.urlStorage + props.customerData.user.user_detail.logo"
                class="img-logo"
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style>
  .img-logo {
    width: 100%;
    border-radius: 16px;
  }

  .iconsAddress .v-btn--icon.v-btn--density-default {
    width: calc(var(--v-btn-height) + 0px) !important;
    height: calc(var(--v-btn-height) + 0px) !important;
  }

  .company.v-card--variant-elevated {
      box-shadow: none !important;
  }

  .title-tabs {
      font-weight: 700;
      font-size: 20px;
      line-height: 100%;
      color: #454545;

      @media (max-width: 1023px) {
          font-size: 20px
      }
  }

  .title-section {
      font-weight: 400;
      font-size: 16px;
      line-height: 100%;
      color: #878787;

      @media (max-width: 1023px) {
          font-size: 16px
      }
  }

  .text-title {
      font-weight: 700 !important;
      font-size: 14px !important;
      line-height: 24px;
      letter-spacing: 0 !important;
      color: #5A7065 !important;
  }

</style>
