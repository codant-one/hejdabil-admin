<script setup>

import { themeConfig } from '@themeConfig'

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
  'copy',
  'download'
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

const download = (file, type) => {
  let data = {
    icon: type === 'nit' ? document_nit.value.split('.')[1] : document_rut.value.split('.')[1],
    document: file
  }
  emit('download', data)
}

const copy = (account) => {
  emit('copy', account)
}

</script>

<template>
  <!-- eslint-disable vue/no-v-html -->
  <!-- 👉 Payment Methods -->
  <VRow>
    <VCol cols="12" v-if="props.isSupplier">
      <VCard class="company" title="Allmän information">
        <VCardText class="d-flex flex-column gap-y-4">
          <VRow>
            <VCol cols="12" md="6">
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Företag:
                      <span class="text-body-2">
                        {{ props.customerData.company }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Organisationsnummer:
                      <span class="text-body-2">
                        {{ props.customerData.organization_number }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Hemsida:
                      <span class="text-body-2">
                          {{ props.customerData.link }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Adress:
                      <span class="text-body-2">
                          {{ props.customerData.address }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Postnummer:
                      <span class="text-body-2">
                          {{ props.customerData.postal_code }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Stad:
                      <span class="text-body-2">
                          {{ props.customerData.street }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Telefon:
                      <span class="text-body-2">
                          {{ props.customerData.phone }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Bank:
                      <span class="text-body-2">
                          {{ props.customerData.bank }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Bankgiro:
                      <span class="text-body-2">
                          {{ props.customerData.iban }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Kontonummer:
                      <span class="text-body-2">
                          {{ props.customerData.account_number }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Iban nummer:
                      <span class="text-body-2">
                          {{ props.customerData.iban_number }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      BIC:
                      <span class="text-body-2">
                          {{ props.customerData.bic }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Plusgiro:
                      <span class="text-body-2">
                          {{ props.customerData.plus_spin }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      Swish:
                      <span class="text-body-2">
                          {{ props.customerData.swish }}
                      </span>
                    </h6>
                  </VListItemTitle>
                  <VListItemTitle>
                    <h6 class="text-base font-weight-semibold">
                      VAT reg. no:
                      <span class="text-body-2">
                          {{ props.customerData.vat }}
                      </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol cols="12" md="6" v-if="props.customerData.logo">
              <VImg
                :src="themeConfig.settings.urlStorage + props.customerData.logo"
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

</style>
