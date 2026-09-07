<script setup>

import { useDisplay } from "vuetify";
import { themeConfig } from "@themeConfig";
import { useSupplierInvoicesStores } from "@/stores/useSupplierInvoices";
import { loadBillingSmsActionPreference } from '@/@core/utils/smsVisibility'
import VuePdfEmbed from "vue-pdf-embed";
import Toaster from "@/components/common/Toaster.vue";
import router from "@/router";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import "/node_modules/vue-pdf-embed/dist/styles/textLayer.css";

const supplierInvoicesStores = useSupplierInvoicesStores();
const route = useRoute();
const emitter = inject("emitter");
const userData = ref(null);

const invoices = ref([]);
const invoice = ref(null);
const isRequestOngoing = ref(true);
const file = ref(false);
const pdfCacheBuster = ref(Date.now())

const isMobileActionDialogVisible = ref(false);
const isConfirmStateDialogVisible = ref(false);
const isConfirmKreditera = ref(false);
const replaceFileInput = ref(null)
const billingToReplaceFile = ref(null)

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const sectionEl = ref(null);
const canShowBillingSmsAction = ref(false)

const pdfScale = computed(() => {
  const dpr = typeof window !== "undefined" ? (window.devicePixelRatio || 1) : 1;
  return Math.min(2.25, Math.max(1.75, dpr * 1.05));
});
const pdfViewportEl = ref(null);
const pdfViewportWidth = ref(0);
let pdfResizeObserver;

const pdfWidth = computed(() => {
  if (pdfViewportWidth.value > 0)
    return Math.max(280, Math.round(pdfViewportWidth.value));

  if (windowWidth.value < 1024)
    return Math.max(280, windowWidth.value - 32);

  return 700;
});

const backToSupplierBillingRoute = computed(() => {
  const supplierId = invoice.value?.supplier_id

  if (!supplierId)
    return { name: 'dashboard-admin-suppliers' }

  return {
    name: 'dashboard-admin-suppliers-id',
    params: { id: supplierId },
    query: { tab: 'billing' },
  }
})

const pdfSource = computed(() => {
  if (!invoice.value?.file)
    return ''

  return `${themeConfig.settings.urlbase}proxy-image?url=${themeConfig.settings.urlStorage}${invoice.value.file}&v=${pdfCacheBuster.value}`
})

const updatePdfViewportWidth = () => {
  const el = pdfViewportEl.value;
  if (!el)
    return;

  pdfViewportWidth.value = el.clientWidth;
};

const initPdfResizeObserver = () => {
  const el = pdfViewportEl.value;
  if (!el || typeof ResizeObserver === "undefined")
    return;

  if (pdfResizeObserver)
    pdfResizeObserver.disconnect();

  pdfResizeObserver = new ResizeObserver(() => updatePdfViewportWidth());
  pdfResizeObserver.observe(el);
  updatePdfViewportWidth();
};

watchEffect(fetchData);
watchEffect(async () => {
  if (!invoice.value)
    return;

  await nextTick();
  initPdfResizeObserver();
});

async function fetchData() {
  if (Number(route.params.id) && route.name === "dashboard-admin-suppliers-billings-id") {
    isRequestOngoing.value = true;
    invoices.value = [];
    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    canShowBillingSmsAction.value = await loadBillingSmsActionPreference(userData.value)

    invoice.value = await supplierInvoicesStores.showSupplierInvoice(Number(route.params.id));
    file.value = themeConfig.settings.urlStorage + invoice.value.file;

    JSON.parse(invoice.value.detail).forEach((row) => {
      invoices.value?.push(row);
    });

    isRequestOngoing.value = false;
  }
}

const replaceFile = billing => {
  if (!billing?.id)
    return

  billingToReplaceFile.value = billing
  replaceFileInput.value?.click()
}

const onReplaceFileSelected = async event => {
  const target = event?.target
  const file = target?.files?.[0]

  if (!file || !billingToReplaceFile.value?.id) {
    if (target)
      target.value = ''

    return
  }

  const isPdfFile = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')

  if (!isPdfFile) {
    advisor.value = {
      type: 'error',
      message: 'Endast PDF-filer är tillåtna',
      show: true,
    }

    target.value = ''
    return
  }

  const formData = new FormData()
  formData.append('file', file)

  try {
    isRequestOngoing.value = true;

    const response = await supplierInvoicesStores.replaceFile({
      id: billingToReplaceFile.value.id,
      data: formData,
    })

    advisor.value = {
      type: response?.data?.success ? 'success' : 'error',
      message: response?.data?.success ? 'Fakturan har ersatts' : (response?.data?.message || 'Något gick fel'),
      show: true,
    }

    await fetchData();

    if (response?.data?.success)
      pdfCacheBuster.value = Date.now()

  } finally {
    isRequestOngoing.value = false;
    billingToReplaceFile.value = null
    target.value = ''
  }
}

const credit = () => {
  isConfirmKreditera.value = true;
};

const kreditera = () => {
  isRequestOngoing.value = true;
  isConfirmKreditera.value = false;

  supplierInvoicesStores.credit(Number(invoice.value.id))
    .then((res) => {
      let data = {
        message: 'Framgångsrik kredit',
        error: false,
      };

      isRequestOngoing.value = false;

      router.push({
        name: 'dashboard-admin-suppliers-billings-id',
        params: { id: res.data.data.billing.id },
      });
      emitter.emit('toast', data);
    })
    .catch((err) => {
      advisor.value.show = true;
      advisor.value.type = 'error';
      advisor.value.message = Object.values(err.message).flat().join('<br>');

      setTimeout(() => {
        advisor.value.show = false;
        advisor.value.type = '';
        advisor.value.message = '';
      }, 3000);

      isRequestOngoing.value = false;
    });
};

const updateBilling = () => {
  isConfirmStateDialogVisible.value = true;
};

const updateState = async () => {
  isConfirmStateDialogVisible.value = false;
  isRequestOngoing.value = true;
  let res = await supplierInvoicesStores.updateState(invoice.value.id);

  isRequestOngoing.value = false;
  advisor.value = {
    type: res.data.success ? "success" : "error",
    message: res.data.success ? "Fakturan uppdaterad!" : res.data.message,
    show: true,
  };

  setTimeout(() => {
    advisor.value = {
      type: "",
      message: "",
      show: false,
    };
  }, 3000);

  await fetchData();

  return true;
};

const resolveStatus = state_id => {
  if (state_id === 4)
    return { class: 'pending' }
  if (state_id === 7)
    return { class: 'success' }   
  if (state_id === 8)
    return { class: 'error' }
  if (state_id === 9)
    return { class: 'error' }
}

const printInvoice = async () => {
  try {
    const response = await fetch(
      themeConfig.settings.urlbase + "proxy-image?url=" + file.value
    );
    const blob = await response.blob();

    const blobUrl = URL.createObjectURL(blob);

    const iframe = document.createElement("iframe");
    iframe.style.display = "none";
    iframe.src = blobUrl;

    iframe.onload = () => {
      iframe.contentWindow.print();
    };

    document.body.appendChild(iframe);
  } catch (error) {
    console.error("Error:", error);
  }
};

const editBilling = () => {
  router.push({
    name: "dashboard-admin-suppliers-billings-edit-id",
    params: { id: Number(route.params.id) },
  });
};

const download = async () => {
  try {
    const response = await fetch(
      themeConfig.settings.urlbase + "proxy-image?url=" + file.value
    );
    const blob = await response.blob();

    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = blobUrl;
    a.download = file.value.split("/").pop();
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  } catch (error) {
    console.error("Error:", error);
  }
};

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);

  if (pdfResizeObserver)
    pdfResizeObserver.disconnect();
});
</script>

<template>
  <section class="page-section" :class="windowWidth < 1024 ? 'pa-4' : ''" ref="sectionEl">
    <Toaster />
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
    <div v-if="invoice" :class="windowWidth < 1024 ? 'd-flex justify-between' : 'd-none'">
      <VBtn
        class="btn-light mb-4"
        :to="backToSupplierBillingRoute"
      >
        <template #prepend>
          <VIcon icon="custom-return" size="24" />
        </template>
        Tillbaka
      </VBtn>

      <VBtn
        v-if="
          $can('view', 'suppliers') &&
          (invoice.state_id === 4 || invoice.state_id === 8) &&
          invoice.user_id !== null
        "
        class="btn-light mb-4"
        :to="{ name: 'dashboard-admin-suppliers-billings-edit-id', params: { id: Number(route.params.id) } }"
      >
        <template #prepend>
          <VIcon icon="custom-pencil" size="24" />
        </template>
        Redigera
      </VBtn>

      <VBtn class="btn-light" icon @click="isMobileActionDialogVisible = true">
        <VIcon icon="custom-dots-vertical" size="24" />
      </VBtn>

    </div>
    <VRow no-gutters v-if="invoice" class="card-fill w-100">
      <VCol
        :cols="windowWidth < 1024 ? 12 : 8"
        class="order-2 order-md-1"
        :class="windowWidth < 1024 ? 'p-0' : 'pr-2 mb-5'"
      >
        <VCard id="invoice-detail">
          <VCardTitle
            class="d-flex gap-4 bg-white align-center flex-row flex-nowrap"
            :class="windowWidth < 1024 ? 'pa-6 pb-0 justify-between' : 'pa-4'"
          >
            <div class="d-flex align-center font-blauer">
              <h2 class="faktura-title">Faktura #{{ invoice.invoice_id }}</h2>
            </div>
            <div
              class="status-chip"
              :class="`status-chip-${resolveStatus(invoice.state.id)?.class}`"
            >
              {{ invoice.state.name }}
            </div>
          </VCardTitle>

          <VDivider class="mt-2 mx-4" />

          <div ref="pdfViewportEl" class="invoice-panel">
            <div class="pdf-host" :style="{ width: `${pdfWidth}px` }">
              <VuePdfEmbed
                :key="pdfSource"
                text-layer
                :width="pdfWidth"
                :scale="pdfScale"
                :source="pdfSource"
                class="w-100 m-auto"
              />
            </div>
          </div>
        </VCard>
      </VCol>
      <VCol
        :cols="windowWidth < 1024 ? 12 : 4"
        class="order-1 order-md-2 d-print-none"
        v-if="windowWidth >= 1024"
      >
        <VCard>
          <VCardText :class="windowWidth < 1024 ? 'pa-6' : 'pa-4'">
            <VBtn 
              v-if="
                $can('view', 'suppliers') &&
                (invoice.state_id === 4 || invoice.state_id === 8) &&
                invoice.user_id !== null
              "
              class="btn-gradient w-100 mb-4" 
              @click="editBilling">
              <template #prepend>
                <VIcon icon="custom-pencil" size="24" />
              </template>
              Redigera
            </VBtn>

            <VBtn
              class="btn-light w-100 mb-4"
              :to="backToSupplierBillingRoute"
            >
              <template #prepend>
                <VIcon icon="custom-return" size="24" />
              </template>
              Tillbaka
            </VBtn>

            <VDivider class="mb-4" />

            <VBtn
              v-if="$can('edit', 'suppliers') && (invoice.state_id === 4 || invoice.state_id === 8)"
              class="btn-light w-100 mb-4"
              @click="updateBilling"
            >
              <template #prepend>
                <VIcon icon="custom-bribery" size="24" />
              </template>
              Markera som betald
            </VBtn>

            <VBtn
              v-if="$can('edit', 'suppliers') && invoice.state_id === 7 && invoice.is_credit === 0"
              class="btn-light w-100 mb-4"
              @click="updateBilling"
            >
              <template #prepend>
                <VIcon icon="custom-money-transfer" size="24" />
              </template>
              Markera som obetald
            </VBtn>

            <VBtn 
              v-if="$can('view', 'suppliers')"
              class="btn-light w-100 mb-4"
              @click="download">
              <template #prepend>
                <VIcon icon="custom-download" size="24" />
              </template>
              Ladda ner som PDF
            </VBtn>

            <VBtn 
              v-if="$can('view', 'suppliers')"
              class="btn-light w-100 mb-4"
              @click="replaceFile(invoice)">
              <template #prepend>
                <VIcon icon="custom-refresh" size="24" />
              </template>
              Ersätta faktura
            </VBtn>

            <VBtn 
              v-if="$can('view', 'suppliers')"
              class="btn-light w-100 mb-4" 
              @click="printInvoice">
              <template #prepend>
                <VIcon icon="custom-print" size="24" />
              </template>
              Skriv ut
            </VBtn>

            <VBtn
              v-if="$can('edit', 'suppliers') && invoice.state_id !== 9 && invoice.is_credit === 0"
              class="btn-light w-100"
              @click="credit(invoice)"
            >
              <template #prepend>
                <VIcon icon="custom-cancel-contract" size="24" />
              </template>
              Kreditera
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 👉 Mobile Action Dialog -->
    <VDialog
      v-model="isMobileActionDialogVisible"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem
            v-if="$can('edit', 'suppliers') && (invoice.state_id === 4 || invoice.state_id === 8)"
            @click="updateBilling(); isMobileActionDialogVisible = false"
          >
            <template #prepend>
              <VIcon icon="custom-bribery" size="24" />
            </template>
            <VListItemTitle>Markera som betald</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'suppliers') && invoice.state_id === 7 && invoice.is_credit === 0"
            @click="updateBilling(); isMobileActionDialogVisible = false"
          >
            <template #prepend>
              <VIcon icon="custom-money-transfer" size="24" />
            </template>
            <VListItemTitle>Markera som obetald</VListItemTitle>
          </VListItem>

          <VListItem
            v-if="$can('view', 'suppliers')"
            @click="printInvoice(); isMobileActionDialogVisible = false"
          >
            <template #prepend>
              <VIcon icon="custom-print" size="24" />
            </template>
            <VListItemTitle>Skriv ut</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'suppliers')"
            @click="download(); isMobileActionDialogVisible = false"
          >
            <template #prepend>
              <VIcon icon="custom-download" size="24" />
            </template>
            <VListItemTitle>Ladda ner som PDF</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'suppliers')"
            @click="replaceFile(invoice); isMobileActionDialogVisible = false"
          >
            <template #prepend>
              <VIcon icon="custom-refresh" size="24" />
            </template>
            <VListItemTitle>Ersätta faktura</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'suppliers') && invoice.state_id !== 9 && invoice.is_credit === 0"
            @click="credit(invoice); isMobileActionDialogVisible = false"
          >
            <template #prepend>
              <VIcon icon="custom-cancel-contract" size="24" />
            </template>
            <VListItemTitle>Kreditera</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Update State -->
    <VDialog
      v-model="isConfirmStateDialogVisible"
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmStateDialogVisible = !isConfirmStateDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-cash-2" class="action-icon" />
          <div class="dialog-title">
            Uppdatera status
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill uppdatera fakturans status
          <strong>#{{ invoice.invoice_id }}</strong> till 
          {{ invoice.state_id === 7 ? 'obetald' : 'betald' }}?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmStateDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="updateState"> Acceptera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm kreditera -->
    <VDialog 
      v-model="isConfirmKreditera" 
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmKreditera = !isConfirmKreditera"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-cancel-contract" class="action-icon" />
          <div class="dialog-title">
            Kreditera faktura
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          En hel kreditering innebär att du tar bort din fordran på kunden till fullo. 
          Är du säker på att du vill kreditera fakturan
          <strong>#{{ invoice.invoice_id }}</strong
          >?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmKreditera = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="kreditera"> Kreditera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <input
      ref="replaceFileInput"
      type="file"
      accept="application/pdf,.pdf"
      style="display: none"
      @change="onReplaceFileSelected"
    >
  </section>
</template>

<style lang="css">
.gray-box {
  width: 216px;
  height: 72px;
  border-radius: 8px;
  background-color: #e7e7e7;
}

.form-field {
  .v-input {
    &.v-text-field {
      .v-field {
        height: 40px;
      }
    }

    &.v-textarea {
      .v-field {
        --v-textarea-control-height: var(--v-input-control-height) !important;
        height: auto !important;
      }
    }

    .v-field {
      border-radius: 8px;
      background-color: #fff !important;
      border: solid 1px #e7e7e7;
    }
  }
}

.invoice-box {
  .form-field {
    .v-input {
      .v-field {
        background-color: #f6f6f6 !important;
      }
    }
  }
}
</style>

<style lang="scss">
.draggable-item {
  margin-top: 5px;
  display: flex;
  align-items: center;
  gap: 16px;
  border-radius: 8px;
  border: solid 1px #e7e7e7;
  padding: 16px;
}
.draggable-item:hover {
  cursor: move;
}

.add-products-header {
  color: #454545;
  font-size: 12px;
}

.invoice-panel {
  margin: 24px 16px 16px;
  border-radius: 8px !important;
  opacity: 1;
  border: solid 1px #e7e7e7;
  overflow: hidden;
  background-color: #fff;

  .pdf-host {
    max-width: 100%;
  }
  
  .vue-pdf-embed {
    display: block;
    width: 100%;
    max-width: 100%;
  }

  .vue-pdf-embed > div {
    width: 100% !important;
  }

  .vue-pdf-embed > div + div {
    margin-top: 12px;
  }

  .vue-pdf-embed__page {
    margin: 0 auto;
  }
  
  canvas {
    display: block;
    width: 100% !important;
    height: auto !important;
  }
}

.invoice-box {
  border-radius: 16px;
  padding: 16px;
  gap: 24px;
  background-color: #f6f6f6;
}

.faktura {
  max-width: 190px;
  padding: 1px 24px;
  font-size: 32px;
  font-weight: 600;
  font-size: 32px;
  line-height: 100%;

  color: #454545;
  border-top: 2px solid #454545;
  border-bottom: 2px solid #454545;
}

.w-70 {
  width: 70% !important;
}

.text-footer {
  font-size: 0.75rem !important;
}

@media (max-width: 767px) {
  .faktura {
    font-size: 16px;
  }
}

.vertical-top {
  vertical-align: top;
}

.invoice-preview-table {
  --v-table-row-height: 44px !important;
}

.invoice-background {
  background-color: #F6F6F6;
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
    background-color: #F6F6F6 !important;
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

  @page {
    margin: 0;
    size: auto;
  }

  .layout-page-content,
  .v-row,
  .v-col-md-10,
  .v-col-md-3 {
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
  subject: suppliers
</route>
