<script setup>

import { themeConfig } from '@themeConfig'
import { useBillingsStores } from '@/stores/useBillings'
import { formatNumber } from '@/@core/utils/formatters'
import logoBlack from '@images/logo_black.png'
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'

const billingsStores = useBillingsStores()
const route = useRoute()
const emitter = inject("emitter")

const types = ref([])
const invoices = ref([])
const invoice = ref(null)
const isRequestOngoing = ref(true)
const file = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

watchEffect(fetchData)

async function fetchData() {

  if(Number(route.params.id) && route.name === 'dashboard-admin-billings-credit-id') {

    isRequestOngoing.value = true

    let response = await billingsStores.all()
    types.value = response.data.data.invoices

    invoice.value = await billingsStores.showBilling(Number(route.params.id))
    file.value = themeConfig.settings.urlStorage + invoice.value.file

    JSON.parse(invoice.value.detail).forEach(row => {
        invoices.value?.push(row)   
    });

    isRequestOngoing.value = false
  }
}

const credit = async () => {
    isRequestOngoing.value = true

    billingsStores.credit(Number(route.params.id))
        .then((res) => {
            let data = {
                message: 'Framgångsrik kredit',
                error: false
            }
            
            isRequestOngoing.value = false
            
            router.push({ name : 'dashboard-admin-billings-id', params: { id: res.data.data.billing.id } })
            emitter.emit('toast', data)
        })
        .catch((err) => {
            advisor.value.show = true
            advisor.value.type = 'error'
            advisor.value.message = Object.values(err.message).flat().join('<br>')

            setTimeout(() => { 
                advisor.value.show = false
                advisor.value.type = ''
                advisor.value.message = ''
            }, 3000)
        
            isRequestOngoing.value = false
        })
}
</script>

<template>
  <section>
    <Toaster />
    <VDialog
      v-model="isRequestOngoing"
      width="auto"
      persistent>
      <VProgressCircular
        indeterminate
        color="primary"
        class="mb-0"/>
    </VDialog>
    <VAlert
      v-if="advisor.show"
      :type="advisor.type"
      class="mb-6">  
      {{ advisor.message }}
    </VAlert>
    <VRow v-if="invoice">
      <VCol
        cols="12"
        md="9"
        class="order-2 order-md-1"
      >
        <VCard class="pa-10" id="invoice-detail">
          <VCardText class="d-flex flex-wrap justify-space-between flex-column flex-sm-row print-row rounded invoice-background">
            <div class="ma-sm-4 d-flex flex-column">
              <div class="d-flex align-center mb-6">
                <VImg
                    v-if="!invoice.supplier"
                    :src="logoBlack"
                    class="me-3"
                />
                <div v-else>
                    <VImg
                        v-if="invoice.supplier.logo" 
                        width="150"
                        :src="themeConfig.settings.urlStorage + invoice.supplier.logo"
                    />
                    <VImg
                        v-else
                        :src="logoBlack"
                        class="me-3"
                    />
                </div>
              </div>
              <h6 class="d-flex align-center font-weight-medium justify-sm-start text-xl mb-0">
                <span class="me-2 text-start w-50 text-h6">
                   Faktura nr:
                </span>
                <span class="text-h6">{{ invoice.invoice_id }}</span>
                
              </h6>
              <p class="d-flex align-center justify-sm-start mb-0 text-right">
                <span class="me-2 text-start w-50">Kund nr:</span>
                 {{ invoice.client.order_id }}
              </p>
              <!-- 👉 Issue Date -->
              <p class="d-flex align-center justify-sm-start mb-0 text-right">
                <span class="me-2 text-start w-50">Fakturadatum: </span>
                <span>{{ new Date().toLocaleDateString('en-GB') }}</span>
              </p>

              <!-- 👉 Due Date -->
              <p class="d-flex align-center justify-sm-start mb-0 text-right">
                <span class="me-2 text-start w-50">Förfallodatum: </span>
                <span>{{ new Date().toLocaleDateString('en-GB') }}</span>
              </p>

              <p class="d-flex align-center justify-sm-start mb-0 text-right">
                <span class="me-2 text-start w-50">Betalningsvillkor: </span>
                <span>0 dagar netto</span>
              </p>
              <p class="d-flex align-center justify-sm-start mb-0 text-right" v-if="invoice.reference !== null">
                <span class="me-2 text-start w-50">Vår referens:</span> {{ invoice.reference ?? '' }}
              </p>    
              <p class="mt-5 mb-0 text-xs">Efter förfallodagen debiteras ränta enligt räntelagen.</p>           
            </div>

            <div class="ma-sm-4 text-right d-flex flex-column">
              <h1 class="mb-0 text-center faktura">
                KREDIT FAKTURA
              </h1>
              <h3 class="mb-0 mt-2">
                {{ invoice.client.fullname }}
              </h3>
              <p class="mb-0 mt-auto">
                <span class="text-h6 font-weight-medium mb-6">
                    Faktureringsadress
                </span>
                <span class="d-flex flex-column">
                  <span>{{ invoice.client.address }}</span>
                  <span>{{ invoice.client.postal_code }}</span>
                  <span>{{ invoice.client.street }}</span>
                </span>
              </p>
            </div>
          </VCardText>

          <!-- 👉 Table -->
          <VTable class="invoice-preview-table border mt-5" style="border-radius: 8px !important">
            <thead class="invoice-background">
              <tr>
                <template v-for="(invoice, index) in types" :key="invoice.id">
                    <td :style="`width: ${invoice.type_id === 1 ? '40' : (60/(types.length - 1)) }%;`">
                        <span class="text-base font-weight-bold">
                        {{ invoice.name }}
                        </span>
                    </td>
                </template>
              </tr>
            </thead>

            <tbody>
              <tr v-for="(row, rowIndex) in invoices" :key="'row-' + rowIndex">
                <td v-for="(column, colIndex) in row" :key="'col-' + colIndex" class="py-2">
                    <span :class="column.id === 1 ? 'font-weight-bold': 'vertical-top'">
                        <span v-if="column.id === 3 || column.id === 4">-</span>
                        {{ column.value }}
                    </span>
                    <span class="font-weight-bold" v-if="column.note"> 
                        {{column.note}}
                    </span>         
                </td>
              </tr>
            </tbody>
          </VTable>

          <!-- Total -->
          <VCardText class="d-flex flex-column print-column px-0" style="margin-top: auto !important;">
            <div class="my-2">
              <table class="d-flex justify-end align-end">
                <tbody>
                  <tr>
                    <td class="text-end">
                      <div class="me-5">
                        <p class="mb-0">
                          Netto:
                        </p>
                        <p class="mb-0">
                          Moms:
                        </p>
                        <p class="mb-0">
                          Total:
                        </p>
                      </div>
                    </td>

                    <td class="font-weight-medium text-high-emphasis text-end">
                      <p class="mb-0">
                        -{{ formatNumber(invoice.subtotal) }} kr
                      </p>
                      <p class="mb-0">
                        -{{ formatNumber(invoice.tax) }} %
                      </p>
                      <p class="mb-0">
                        -{{ formatNumber(invoice.total) }} kr
                      </p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          <div class="px-0 border-divider">
            <VRow class="mt-3">
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Adress
                    </span>
                    <span class="d-flex flex-column">
                        <span class="text-footer">{{ supplier.user_details.address }}</span>
                        <span class="text-footer">{{ supplier.user_details.postal_code }}</span>
                        <span class="text-footer">{{ supplier.user_details.street }}</span>
                        <span class="text-footer">{{ supplier.user_details.phone }}</span>
                    </span>
                    <span class="me-2 text-h6 mt-2">
                        Bolagets säte
                    </span>
                    <span class="text-footer"> Stockholm, Sweden </span>
                    <span class="me-2 text-h6 mt-2" v-if="supplier.user_details.swish">
                        Swish
                    </span>
                    <span class="text-footer" v-if="supplier.user_details.swish"> {{ supplier.user_details.swish }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Org.nr.
                    </span>
                    <span class="text-footer"> {{ supplier.user_details.organization_number }} </span>
                    <span class="me-2 text-h6 mt-2" v-if="supplier.user_details.vat">
                        Vat
                    </span>
                    <span class="text-footer"> {{ supplier.user_details.vat }} </span>
                    <span class="me-2 text-h6 mt-2" v-if="supplier.user_details.bic">
                        BIC
                    </span>
                    <span class="text-footer" v-if="supplier.user_details.bic"> {{ supplier.user_details.bic }} </span>

                    <span class="me-2 text-h6 mt-2" v-if="supplier.user_details.plus_spin">
                        Plusgiro
                    </span>
                    <span class="text-footer" v-if="supplier.user_details.plus_spin"> {{ supplier.user_details.plus_spin }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Webbplats
                    </span>
                    <span class="text-footer"> {{ supplier.user_details.link }} </span>
                    <span class="me-2 text-h6 mt-2">
                        Företagets e-post
                    </span>
                    <span class="text-footer"> {{ supplier.email }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6" v-if="supplier.user_details.bank">
                      Bank
                    </span>
                    <span class="text-footer" v-if="supplier.user_details.bank"> {{ supplier.user_details.bank }} </span>

                    <span class="me-2 text-h6 mt-2" v-if="supplier.user_details.iban">
                        Bankgiro
                    </span>
                    <span class="text-footer"> {{ supplier.user_details.iban }} </span>

                    <span class="me-2 text-h6 mt-2" v-if="supplier.user_details.account_number">
                        Kontonummer
                    </span>
                    <span class="text-footer" v-if="supplier.user_details.account_number"> {{ supplier.user_details.account_number }} </span>
                    
                    <span class="me-2 text-h6 mt-2" v-if="supplier.user_details.iban_number">
                      Iban nummer
                    </span>
                    <span class="text-footer" v-if="supplier.user_details.iban_number"> {{ supplier.user_details.iban_number }} </span>

                </VCol>
            </VRow>
          </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="3"
        class="order-1 order-md-2 d-print-none"
      >
        <VCard>
          <VCardText>
            <VBtn
              block
              prepend-icon="tabler-send"
              class="mb-2"
              @click="credit"
            >
              Skapa faktura
            </VBtn>

            <VBtn
              block
              color="secondary"
              variant="tonal"
              class="mb-2"
              :to="{ name: 'dashboard-admin-billings' }">
              Tillbaka
            </VBtn>

          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style lang="scss">
  .vertical-top {
    vertical-align: top;
  }

  .faktura {
    font-size: 32px;
    color: #9966FF;
    border-top: 2px solid #9966FF;
    border-bottom: 2px solid #9966FF;
  }

  .invoice-preview-table {
    --v-table-row-height: 44px !important;
  }

  .invoice-background {
    background-color: #F2EFFF;
  }

  .border-divider {
    border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .text-footer {
    font-size: 0.75rem !important;
  }

  @media print {
    .v-theme--dark {
      --v-theme-surface: 255, 255, 255;
      --v-theme-on-surface: 94, 86, 105;
    }

    .invoice-background {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      background-color: #F2EFFF !important;
    }

    .print-column {
      display: flex;
      flex-wrap: wrap;
      page-break-inside: avoid;
      position: fixed;
      bottom: 0;
      width: 90%;

      .v-col-md-3 {
        flex: 0 0 25%;
        max-width: 25%;
        padding-right: 5px !important;
      }
    }

    @page { margin: 0; size: auto; }

    .layout-page-content,
    .v-row,
    .v-col-md-10, .v-col-md-3 {
      padding: 0;
      margin: 0;
    }

    .product-buy-now {
      display: none;
    }

    .v-navigation-drawer,
    .layout-vertical-nav,
    .app-customizer-toggler,
    .layout-footer,
    .layout-navbar,
    .layout-navbar-and-nav-container {
      display: none;
    }

    .v-card {
      box-shadow: none !important;

      .print-row {
        flex-direction: row !important;
      }
    }

    .layout-content-wrapper {
      padding-inline-start: 0 !important;
    }

    .v-table__wrapper {
      overflow: hidden !important;
    }
}
</style>
<route lang="yaml">
  meta:
    action: delete
    subject: billing
</route>
