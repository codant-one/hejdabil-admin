<script setup>

import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { useBillingsStores } from '@/stores/useBillings'
import { formatNumber } from '@/@core/utils/formatters'
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'

const billingsStores = useBillingsStores()
const route = useRoute()

const types = ref([])
const invoices = ref([])
const notes = ref([])
const invoice = ref(null)
const isRequestOngoing = ref(true)
const isConfirmSendMailVisible = ref(false)
const emailDefault = ref(true)
const selectedTags = ref([])
const existingTags = ref([])
const isValid = ref(false)
const file = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

watchEffect(fetchData)

async function fetchData() {

  if(Number(route.params.id) && route.name === 'dashboard-admin-billings-id') { 

    isRequestOngoing.value = true

    let response = await billingsStores.all()
    types.value = response.data.data.invoices

    invoice.value = await billingsStores.showBilling(Number(route.params.id))
    file.value = themeConfig.settings.urlStorage + invoice.value.file

    JSON.parse(invoice.value.detail).forEach(row => {
        invoices.value?.push(row)   
    });

    if(invoice.value.notes) {
      JSON.parse(invoice.value.notes).forEach(row => {
          notes.value?.push(row)   
      });
    }

    isRequestOngoing.value = false
  }
}

const addTag = (event) => {
  const newTag = event.target.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (newTag && emailRegex.test(newTag)) {
    if (!selectedTags.value.includes(newTag)) {
      selectedTags.value.push(newTag);

      if (!existingTags.value.includes(newTag)) {
        existingTags.value.push(newTag);
      }

    }
  } else {
    isValid.value = true
    selectedTags.value.pop();
  }
};

const sendMails = async () => {
  isConfirmSendMailVisible.value = false
  isRequestOngoing.value = true

  let data = {
    id: invoice.value.id,
    emailDefault: emailDefault.value,
    emails: selectedTags.value
  }

  let res = await billingsStores.sendMails(data)

  isRequestOngoing.value = false

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Fakturan är skickad!' : res.data.message,
    show: true
  }

  setTimeout(() => {
    selectedTags.value = []
    existingTags.value = []
    emailDefault.value = true 

    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const printInvoice = async() => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + file.value);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);
    
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = blobUrl;
    
    iframe.onload = () => {
      iframe.contentWindow.print();
    };
    
    document.body.appendChild(iframe);
  } catch (error) {
    console.error('Error:', error);
  }
}

const duplicate = () => {
  router.push({ name : 'dashboard-admin-billings-duplicate-id', params: { id: Number(route.params.id) } })
}

const download = async() => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + file.value);
    const blob = await response.blob();
    
    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = 'invoice-' + invoice.value.invoice_id + '.pdf'; 
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

  } catch (error) {
    console.error('Error:', error);
  }
};
</script>

<template>
  <section>
    <Toaster />
    <VDialog
      v-model="isRequestOngoing"
      width="300"
      persistent>        
      <VCard
        color="primary"
        width="300">
                        
        <VCardText class="pt-3">
         Lastning
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
        md="9"
      >
        <VCard class="pa-10" id="invoice-detail">
          <VCardText class="d-flex flex-wrap justify-space-between flex-column flex-sm-row print-row rounded invoice-background">
            <div class="ma-sm-4 d-flex flex-column">
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
                <span>{{ new Date(`${invoice.invoice_date}T00:00:00`).toLocaleDateString('en-GB') }}</span>
              </p>

              <!-- 👉 Due Date -->
              <p class="d-flex align-center justify-sm-start mb-0 text-right">
                <span class="me-2 text-start w-50">Förfallodag: </span>
                <span>{{ new Date(`${invoice.due_date}T00:00:00`).toLocaleDateString('en-GB') }}</span>
              </p>

              <p class="d-flex align-center justify-sm-start mb-0 text-right">
                <span class="me-2 text-start w-50">Betalningsvillkor: </span>
                <span>{{ invoice.payment_terms }}</span>
              </p>
              <p class="d-flex align-center justify-sm-start mb-0 text-right" v-if="invoice.reference !== null">
                <span class="me-2 text-start w-50">Vår referens:</span> {{ invoice.reference ?? '' }}
              </p>    
              <p class="mt-5 mb-0 text-xs">Efter förfallodagen debiteras ränta enligt räntelagen.</p>           
            </div>

            <div class="ma-sm-4 text-right d-flex flex-column">
              <h1 class="mb-0 text-center faktura">
                {{ 
                  invoice.state_id === 9 ? 
                  'KREDIT FAKTURA' : 
                  (
                    invoice.payment_terms === '0 dagar netto' ?
                    'KONTANT FAKTURA' :
                    'FAKTURA' 
                  )
                }}
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
                <td v-for="(column, colIndex) in row" :key="'col-' + colIndex" class="py-2" :class="notes.lenght > 0 ? 'vertical-top' : ''">
                  <span :class="column.id === 1 ? 'font-weight-bold': 'vertical-top'">{{ column.value }} </span>                
                  <span v-if="column.id === 1"> 
                    <span v-for="(value, index) in notes[rowIndex]" :key="index">
                      <span class="d-flex flex-column"> 
                        {{value}}
                      </span>
                    </span> 
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
                        {{ formatNumber(invoice.subtotal) }} kr
                      </p>
                      <p class="mb-0">
                        {{ formatNumber(invoice.tax) }} %
                      </p>
                      <p class="mb-0">
                        {{ formatNumber(invoice.total) }} kr
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
                  <span class="text-footer" v-if="!invoice.supplier">
                    Abrahamsbergsvägen 47 <br>
                    16830 BROMMA <br>
                    Hejdå Bil AB
                  </span>
                  <span v-else class="d-flex flex-column">
                    <span class="text-footer">{{ invoice.supplier.address }}</span>
                    <span class="text-footer">{{ invoice.supplier.postal_code }}</span>
                    <span class="text-footer">{{ invoice.supplier.street }}</span>
                  </span>
                  <span class="me-2 text-h6 mt-2">
                      Bolagets säte
                  </span>
                  <span class="text-footer"> Stockholm, Sweden </span>
                  <span class="me-2 text-h6 mt-2" v-if="invoice.supplier?.swish">
                      Swish
                  </span>
                  <span class="text-footer" v-if="invoice.supplier?.swish"> {{ invoice.supplier?.swish }} </span>
              </VCol>
              <VCol cols="12" md="3" class="d-flex flex-column">
                  <span class="me-2 text-h6">
                      Org.nr.
                  </span>
                  <span class="text-footer" v-if="!invoice.supplier"> 559374-0268 </span>
                  <span class="text-footer" v-else> {{ invoice.supplier.organization_number }} </span>
                  <span class="me-2 text-h6 mt-2" v-if="!invoice.supplier || invoice.supplier?.vat">
                      Momsreg.nr.
                  </span>
                  <span class="text-footer" v-if="!invoice.supplier"> SE559374026801 </span>
                  <span class="text-footer" v-else> {{ invoice.supplier.vat }} </span>
              </VCol>
              <VCol cols="12" md="3" class="d-flex flex-column">
                  <span class="me-2 text-h6">
                      Webbplats
                  </span>
                  <span class="text-footer" v-if="!invoice.supplier"> www.hejdabil.se </span>
                  <span class="text-footer" v-else> {{ invoice.supplier.link }} </span>
                  <span class="me-2 text-h6 mt-2">
                      Företagets e-post
                  </span>
                  <span class="text-footer" v-if="!invoice.supplier"> info@hejdabil.se </span>
                  <span class="text-footer" v-else> {{ invoice.supplier.user.email }} </span>
              </VCol>
              <VCol cols="12" md="3" class="d-flex flex-column">
                  <span class="me-2 text-h6" v-if="!invoice.supplier || invoice.supplier?.account_number">
                      Kontonummer
                  </span>
                  <span class="text-footer" v-if="!invoice.supplier"> 9960 1821054721 </span>
                  <span class="text-footer" v-else> {{ invoice.supplier.account_number }} </span>
                  <span class="me-2 text-h6 mt-2" v-if="!invoice.supplier || invoice.supplier?.iban">
                      Bankgiro
                  </span>
                  <span class="text-footer" v-if="!invoice.supplier"> 5886-4976 </span>
                  <span class="text-footer" v-else> {{ invoice.supplier.iban }} </span>
              </VCol>
            </VRow>
          </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="3"
        class="d-print-none"
      >
        <VCard>
          <VCardText>
            <VBtn
              v-if="invoice.state_id !== 9"
              block
              prepend-icon="mdi-content-copy"
              class="mb-2"
              @click="duplicate"
            >
              Duplicera
            </VBtn>

            <VBtn
              block
              prepend-icon="mdi-cloud-download-outline"
              class="mb-2"
              @click="download"
            >
              Nedladdningar
            </VBtn>

            <VBtn
              block
              prepend-icon="mdi-email-fast"
              class="mb-2"
              @click="isConfirmSendMailVisible = true"
            >
              Skicka
            </VBtn>

            <VBtn
              block
              variant="tonal"
              color="secondary"
              class="mb-2"
              @click="printInvoice"
            >
              Skriv ut
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
     <!-- 👉 Confirm Delete -->
     <VDialog
      v-model="isConfirmSendMailVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmSendMailVisible = !isConfirmSendMailVisible" />

      <!-- Dialog Content -->
      <VCard title="Skicka fakturan via e-post">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker på att du vill skicka fakturor till följande e-postadresser?
        </VCardText>
        <VCardText class="d-flex flex-column gap-2">
          <VCheckbox
            v-model="emailDefault"
            :label="invoice.client.email"
          />

          <VCombobox
            v-model="selectedTags"
            :items="existingTags"
            label="Ange e-post för att skicka fakturor"
            multiple
            chips
            deletable-chips
            clearable
            @blur="addTag"
            @keydown.enter.prevent="addTag"
            @input="isValid = false"
          />
          <span class="text-xs text-error" v-if="isValid">E-postadressen måste vara en giltig e-postadress</span>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmSendMailVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="sendMails">
              Skicka
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
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
    action: view
    subject: billing
</route>
