<script setup>

import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { useBillingsStores } from '@/stores/useBillings'
import { formatNumber } from '@/@core/utils/formatters'
import html2pdf from 'html2pdf.js';

const billingsStores = useBillingsStores()
const route = useRoute()

const types = ref([])
const invoices = ref([])
const invoice = ref(null)
const isRequestOngoing = ref(true)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

watchEffect(fetchData)

async function fetchData() {

  if(Number(route.params.id)) { 

    isRequestOngoing.value = true

    let response = await billingsStores.all()
    types.value = response.data.data.invoices

    invoice.value = await billingsStores.showBilling(Number(route.params.id))
    
    JSON.parse(invoice.value.detail).forEach(row => {
        invoices.value?.push(row)   
    });

    isRequestOngoing.value = false
  }
}

const printInvoice = () => {
  window.print()
}

const download = () => {
  const element = document.getElementById('invoice-detail');

  const options = {
    margin: 0,
    filename: 'invoice-' + Number(route.params.id) + '.pdf',
    image: { type: 'jpeg', quality: 0.95 },
    html2canvas: { scale: 4, allowTaint: true },
    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' },
    pagebreak: { mode: 'css' } 
  };

  console.log('aaa', element)
  html2pdf().set(options).from(element).save();
};
</script>

<template>
  <section>
    <VDialog
      v-model="isRequestOngoing"
      width="300"
      persistent>        
      <VCard
        color="primary"
        width="300">
                        
        <VCardText class="pt-3">
          Loading
          <VProgressLinear
            indeterminate
            color="white"
            class="mb-0"/>
        </VCardText>
      </VCard>
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
        md="10"
      >
        <VCard class="pa-10" id="invoice-detail">
          <VCardText class="d-flex flex-wrap justify-space-between flex-column flex-sm-row print-row rounded" style="background-color:  #F2EFFF;">
            <div class="ma-sm-4">
              <div class="d-flex align-center mb-6">
                <VNodeRenderer
                    v-if="!invoice.supplier"
                    :nodes="themeConfig.app.logoBlack"
                    class="me-3"
                />
                <div v-else>
                    <VImg
                        v-if="invoice.supplier.logo" 
                        width="150"
                        :src="themeConfig.settings.urlStorage + invoice.supplier.logo"
                    />
                    <VNodeRenderer
                        v-else
                        :nodes="themeConfig.app.logoBlack"
                        class="me-3"
                    />
                </div>
              </div>
              <p class="mb-0">
                    Client No: {{ invoice.client.id }}
                </p>
                <p class="mb-0">
                    Name:  {{ invoice.client.fullname }}
                </p>
                <p class="mb-0">
                   E-mail: {{ invoice.client.email }}
                </p>
                <p class="mb-0">
                    Organization number: {{ invoice.client.organization_number ?? '' }}
                </p>
                <p class="mb-0">
                    Reference: {{ invoice.client.reference ?? '' }}
                </p>    
                <p class="mt-5 mb-0 text-sm">After the due date, interest is charged according to the Interest Act.</p>           
            </div>

            <div class="mt-4 ma-sm-4 text-right">
              <h6 class="font-weight-medium text-h6">
                Invoice No #{{ invoice.id }}
              </h6>

              <!-- 👉 Issue Date -->
              <p class="mt-12 mb-0">
                <span>Invoice Date </span>
                <span>{{ new Date(invoice.invoice_date).toLocaleDateString('en-GB') }}</span>
              </p>

              <!-- 👉 Due Date -->
              <p class="mb-0">
                <span>Due date: </span>
                <span>{{ new Date(invoice.due_date).toLocaleDateString('en-GB') }}</span>
              </p>
              <p class="mb-0">
                <span>Payment Terms: </span>
                <span>{{ invoice.payment_terms }}</span>
              </p>
            </div>
          </VCardText>

          <!-- 👉 Payment Details -->
          <VCardText class="d-flex justify-space-between flex-wrap flex-column flex-sm-row print-row px-0">
            <div class="my-sm-4"></div>

            <div class="mt-4 my-sm-4">
                <h6 class="text-h6 font-weight-medium mb-6">
                    Billing Address
                </h6>
                <span class="d-flex flex-column">
                    <span class="font-weight-bold">{{ invoice.client.address }}</span>
                    <span>{{ invoice.client.street }}</span>
                    <span>{{ invoice.client.postal_code }}</span>
                </span>
            </div>
          </VCardText>

          <!-- 👉 Table -->
          <VTable class="invoice-preview-table border" style="border-radius: 8px !important">
            <thead>
              <tr>
                <template v-for="(invoice, index) in types" :key="invoice.id">
                    <td :style="`width: ${invoice.type_id === 1 ? '40' : (60/(types.length - 1)) }%;`">
                        <span class="text-base font-weight-bold">
                        {{ invoice.name_en }}
                        </span>
                    </td>
                </template>
              </tr>
            </thead>

            <tbody>
                <tr v-for="(row, rowIndex) in invoices" :key="'row-' + rowIndex">
                    <td v-for="(column, colIndex) in row" :key="'col-' + colIndex">
                    {{ column.value }}
                    </td>
                </tr>
            </tbody>
          </VTable>

          <!-- Total -->
          <VCardText class="d-flex justify-space-between flex-column flex-sm-row print-row px-0">
            <VSpacer />

            <div class="my-2">
              <table>
                <tbody>
                  <tr>
                    <td class="text-end">
                      <div class="me-5">
                        <p class="mb-0">
                          Subtotal:
                        </p>
                        <p class="mb-0">
                            Tax:
                        </p>
                        <p class="mb-0">
                          Total:
                        </p>
                      </div>
                    </td>

                    <td class="font-weight-medium text-high-emphasis text-end">
                      <p class="mb-0">
                        {{ formatNumber(invoice.subtotal) }} KR
                      </p>
                      <p class="mb-0">
                        {{ formatNumber(invoice.tax) }} %
                      </p>
                      <p class="mb-0">
                        {{ formatNumber(invoice.total) }} KR
                      </p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </VCardText>

          <VDivider class="mt-4 mb-3" />
         
          <VCardText class="mb-sm-4 px-0">
            <VRow>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Address
                    </span>
                    <span  v-if="!invoice">
                        Hejdå Bil AB
                        Abrahamsbergsvägen 47
                        16830 BROMMA
                    </span>
                    <span v-else class="d-flex flex-column">
                        <span>{{ invoice.supplier.address }}</span>
                        <span>{{ invoice.supplier.street }}</span>
                        <span>{{ invoice.supplier.postal_code }}</span>
                    </span>
                    <span class="me-2 text-h6 mt-2">
                        Registered office of the company
                    </span>
                    <span> Stockholm, Sweden </span>
                    <span class="me-2 text-h6 mt-2">
                        Swish
                    </span>
                    <span> ?? </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Org.nr.
                    </span>
                    <span v-if="!invoice.supplier"> 559374-0268 </span>
                    <span v-else> {{ invoice.supplier.organization_number }} </span>
                    <span class="me-2 text-h6 mt-2">
                        VAT reg. no.
                    </span>
                    <span v-if="!invoice.supplier"> SE559374026801 ?? </span>
                    <span v-else> ?? </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Website
                    </span>
                    <span v-if="!invoice.supplier"> www.hejdabil.se </span>
                    <span v-else> {{ invoice.supplier.link }} </span>
                    <span class="me-2 text-h6 mt-2">
                        Company e-mail
                    </span>
                    <span v-if="!invoice.supplier"> info@hejdabil.se </span>
                    <span v-else> {{ invoice.supplier.user.email }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Bank account number
                    </span>
                    <span v-if="!invoice.supplier"> 9960 1821054721 </span>
                    <span v-else> {{ invoice.supplier.account_number }} </span>
                    <span class="me-2 text-h6 mt-2">
                        Bankgiro
                    </span>
                    <span v-if="!invoice.supplier"> 5886-4976 </span>
                    <span v-else> ?? </span>
                </VCol>
            </VRow>
        </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="2"
        class="d-print-none"
      >
        <VCard>
          <VCardText>
            <!-- 👉 Send Invoice Trigger button -->
            <VBtn
              block
              prepend-icon="mdi-cloud-download-outline"
              class="mb-2"
              @click="download"
            >
              Download
            </VBtn>

            <VBtn
              block
              variant="tonal"
              color="secondary"
              class="mb-2"
              @click="printInvoice"
            >
              Print
            </VBtn>

            <VBtn
              block
              color="secondary"
              variant="tonal"
              class="mb-2"
              :to="{ name: 'dashboard-admin-billings' }">
              Back
            </VBtn>

          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style lang="scss">
  .invoice-preview-table {
    --v-table-row-height: 44px !important;
  }

  @media print {
    .v-theme--dark {
      --v-theme-surface: 255, 255, 255;
      --v-theme-on-surface: 94, 86, 105;
    }

    body {
      background: none !important;
    }

    @page { margin: 0; size: auto; }

    .layout-page-content,
    .v-row,
    .v-col-md-9 {
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
