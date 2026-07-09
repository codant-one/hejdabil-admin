<script setup>

import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'

const { width: windowWidth } = useWindowSize();

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
  <VCard class="company" v-if="props.isSupplier">
    <VCardText class="px-0 pb-0">
      <div class="title-tabs">
        Ändra lösenord
      </div>
    </VCardText>

    <VCardText class="px-0 d-flex flex-column">
      <div class="title-section mb-2">
          Företagsinformation
      </div>

      <VDivider class="mb-2" />

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
            Företagsnamn
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
            Mobilnummer
          </span>
          <span class="text-body-2">
            {{ props.customerData.user.user_detail.landline ?? '-' }}
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
        <div class="d-flex"
          :class="windowWidth < 1024 ? 'flex-row justify-space-between' : 'flex-column'"
          :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33% - 14px);'"
        >
          <span class="text-title">
            Payout number
          </span>
          <span class="text-body-2">
            {{ props.customerData.payout_number ?? '-' }}
          </span>
        </div>
      </div>
    
      <div class="title-section mt-6 mb-2">
          Bankinformation
      </div>

      <VDivider class="mb-2" />

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

      <VImg
        :src="themeConfig.settings.urlStorage + props.customerData.user.user_detail.logo"
        class="img-logo d-none"
      />
    </VCardText>
  </VCard>
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
      line-height: 15px;
      letter-spacing: 0%;
      color: #878787;
  }

  .text-title {
      font-weight: 700;
      font-size: 14px;
      line-height: 15px;
      letter-spacing: 0%;
      color: #454545 !important;
  }

</style>
