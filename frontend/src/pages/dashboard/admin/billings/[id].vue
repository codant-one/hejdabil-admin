<script setup>

import { themeConfig } from '@themeConfig'
import { useBillingsStores } from '@/stores/useBillings'
import VuePdfEmbed from 'vue-pdf-embed'
import Toaster from "@/components/common/Toaster.vue";
import router from '@/router'

const billingsStores = useBillingsStores()
const route = useRoute()

const types = ref([])
const invoices = ref([])
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

    isRequestOngoing.value = false
  }
}

const addTag = (event) => {
  const newTag = event.target.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (newTag && emailRegex.test(newTag)) {
    // no hago nada, sino invalido
  } else {
    isValid.value = true
    selectedTags.value.pop();
  }
};

const sendMails = async () => {

  if(!isValid.value) {
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
        md="8"
        class="order-2 order-md-1"
      >
        <VCard class="p-0" id="invoice-detail">
          <VuePdfEmbed
            :source="themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + invoice.file" 
            class="d-flex justify-content-center w-auto m-auto"/>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="4"
        class="order-1 order-md-2 d-print-none"
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
              Ladda ner som PDF
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

  .justify-content-center {
    justify-content: center !important;
  }

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
