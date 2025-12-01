<script setup>
import { themeConfig } from "@themeConfig";
import { useBillingsStores } from "@/stores/useBillings";
import VuePdfEmbed from "vue-pdf-embed";
import Toaster from "@/components/common/Toaster.vue";
import router from "@/router";

import sampleFaktura from "@images/sample-faktura.jpg";

const billingsStores = useBillingsStores();
const route = useRoute();

const types = ref([]);
const invoices = ref([]);
const invoice = ref(null);
const isRequestOngoing = ref(true);
const isConfirmSendMailVisible = ref(false);
const emailDefault = ref(true);
const selectedTags = ref([]);
const existingTags = ref([]);
const isValid = ref(false);
const file = ref(false);
const actionDialog = ref(false);
const skickaDialog = ref(false);
const inteSkapatsDialog = ref(false);

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

watchEffect(fetchData);

async function fetchData() {
  if (Number(route.params.id) && route.name === "dashboard-admin-billings-id") {
    isRequestOngoing.value = true;

    let response = await billingsStores.all();
    types.value = response.data.data.invoices;

    invoice.value = await billingsStores.showBilling(Number(route.params.id));
    file.value = themeConfig.settings.urlStorage + invoice.value.file;

    JSON.parse(invoice.value.detail).forEach((row) => {
      invoices.value?.push(row);
    });

    isRequestOngoing.value = false;
  }
}

const addTag = (event) => {
  const newTag = event.target.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (newTag && emailRegex.test(newTag)) {
    // no hago nada, sino invalido
  } else {
    isValid.value = true;
    selectedTags.value.pop();
  }
};

const sendMails = async () => {
  if (!isValid.value) {
    isConfirmSendMailVisible.value = false;
    isRequestOngoing.value = true;

    let data = {
      id: invoice.value.id,
      emailDefault: emailDefault.value,
      emails: selectedTags.value,
    };

    let res = await billingsStores.sendMails(data);

    isRequestOngoing.value = false;

    advisor.value = {
      type: res.data.success ? "success" : "error",
      message: res.data.success ? "Fakturan är skickad!" : res.data.message,
      show: true,
    };

    setTimeout(() => {
      selectedTags.value = [];
      existingTags.value = [];
      emailDefault.value = true;

      advisor.value = {
        type: "",
        message: "",
        show: false,
      };
    }, 3000);

    return true;
  }
};

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

const duplicate = () => {
  router.push({
    name: "dashboard-admin-billings-duplicate-id",
    params: { id: Number(route.params.id) },
  });
};

const editBilling = () => {
  router.push({
    name: "dashboard-admin-billings-edit-id",
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
</script>

<template>
  <section>
    <Toaster />
    <VDialog v-model="isRequestOngoing" width="auto" persistent>
      <VProgressCircular indeterminate color="primary" class="mb-0" />
    </VDialog>
    <!-- <VAlert v-if="advisor.show" :type="advisor.type" class="mb-6">
      {{ advisor.message }}
    </VAlert> -->
    <VRow v-if="invoice" class="row-fill">
      <VCol
        cols="12"
        md="8"
        class="order-2 order-md-1"
        :class="$vuetify.display.smAndDown ? 'p-0' : ''"
      >
        <VCard class="p-0" id="invoice-detail">
          <VuePdfEmbed
            :source="
              themeConfig.settings.urlbase +
              'proxy-image?url=' +
              themeConfig.settings.urlStorage +
              invoice.file
            "
            class="d-flex justify-content-center w-auto m-auto"
          />
        </VCard>
        <VCard
          class="pa-0"
          :class="$vuetify.display.smAndDown ? 'rounded-0' : ''"
        >
          <VCardTitle
            class="d-flex justify-space-between"
            :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
          >
            <div class="d-flex align-center w-100 w-md-auto font-blauer">
              <h2>Faktura 4</h2>
            </div>
          </VCardTitle>

          <VDivider
            :class="$vuetify.display.smAndDown ? 'd-none' : 'mt-2 mx-4'"
          />

          <VCardText v-if="$vuetify.display.smAndDown" class="py-0">
            <div class="d-flex gap-4">
              <VBtn
                class="btn-light"
                :to="{ name: 'dashboard-admin-billings' }"
              >
                <template #prepend>
                  <VIcon icon="custom-return" size="24" />
                </template>
                Tillbaka
              </VBtn>

              <VBtn class="btn-light" @click="editBilling">
                <template #prepend>
                  <VIcon icon="custom-pencil" size="24" />
                </template>
                Redigera
              </VBtn>

              <VBtn class="btn-light" icon @click="actionDialog = true">
                <VIcon icon="custom-dots-vertical" size="24" />
              </VBtn>

              <VDialog
                v-model="actionDialog"
                transition="dialog-bottom-transition"
                content-class="dialog-bottom-full-width"
              >
                <VCard>
                  <VList>
                    <VListItem
                      v-if="$can('edit', 'billings')"
                      @click="printInvoice()"
                    >
                      <template #prepend>
                        <VIcon icon="custom-print" size="24" />
                      </template>
                      <VListItemTitle>Skriv ut</VListItemTitle>
                    </VListItem>
                    <VListItem v-if="$can('edit', 'billings')">
                      <template #prepend>
                        <VIcon icon="custom-pdf" size="24" />
                      </template>
                      <VListItemTitle>Visa som PDF</VListItemTitle>
                    </VListItem>
                    <VListItem v-if="$can('edit', 'billings')">
                      <template #prepend>
                        <VIcon icon="custom-duplicate" size="24" />
                      </template>
                      <VListItemTitle>Duplicera</VListItemTitle>
                    </VListItem>
                    <VListItem v-if="$can('edit', 'billings')">
                      <template #prepend>
                        <VIcon icon="custom-alarm" size="24" />
                      </template>
                      <VListItemTitle>Påminnelse</VListItemTitle>
                    </VListItem>
                  </VList>
                </VCard>
              </VDialog>
            </div>
            <div class="mt-4">
              <VBtn class="btn-gradient w-100" @click="skickaDialog = true">
                <template #prepend>
                  <VIcon icon="custom-paper-plane" size="24" />
                </template>
                Skicka
              </VBtn>
            </div>
          </VCardText>
          <img
            :src="sampleFaktura"
            class="w-100 pa-6"
            v-if="$vuetify.display.smAndDown"
          />
          <section
            class="invoice-panel border rounded-lg pa-4 mx-6"
            v-if="!$vuetify.display.smAndDown"
          >
            <div>
              <VCardText class="d-flex invoice-box">
                <div class="w-100 w-md-50">
                  <div class="invoice-logo-box d-flex align-center mb-6">
                    <!-- 👉 Logo -->
                    <div class="gray-box"></div>
                  </div>
                  <!-- 👉 Invoice Id -->
                  <div
                    class="d-block d-md-flex align-center justify-sm-start text-right mb-2"
                  >
                    <span class="me-2 text-start w-40 text-black"
                      >Faktura nr</span
                    >
                    <span>
                      <div class="form-field">Lorem Ipsum</div>
                    </span>
                  </div>
                  <div
                    class="d-block d-md-flex align-center justify-sm-start mb-2 text-right"
                    v-if="client"
                  >
                    <span class="me-2 text-start w-40 text-black">Kund nr</span>
                    <span>
                      <div class="form-field">Lorem Ipsum</div>
                    </span>
                  </div>
                  <!-- 👉 Issue Date -->
                  <div
                    class="d-block d-md-flex align-center justify-sm-start mb-2 md:text-right"
                  >
                    <span class="me-2 text-start w-40 text-black"
                      >Fakturadatum</span
                    >

                    <span style="inline-size: 10.5rem">
                      <div class="form-field">Lorem Ipsum</div>
                    </span>
                  </div>

                  <!-- 👉 Due Date -->
                  <div
                    class="d-block d-md-flex align-center justify-sm-start mb-0"
                  >
                    <span class="me-2 text-start w-40 text-black"
                      >Förfallodatum</span
                    >

                    <span style="min-inline-size: 10.5rem">
                      <div class="form-field">Lorem Ipsum</div>
                    </span>
                  </div>

                  <!-- 👉 Days -->
                  <div
                    class="d-block d-md-flex align-center justify-sm-start mb-0 mt-2"
                  >
                    <span class="me-2 text-start w-40 text-black">
                      Betalningsvillkor:
                    </span>

                    <span style="width: 10.5rem">
                      <div class="form-field">Lorem Ipsum</div>
                    </span>
                  </div>
                  <p class="mt-5 mb-0 text-sm" v-if="client">
                    Efter förfallodagen debiteras ränta enligt räntelagen.
                  </p>
                </div>
                <div class="d-flex flex-column w-50 text-right">
                  <h1 class="mt-4 mb-7 text-center faktura mt-5 ml-auto">
                    FAKTURA
                  </h1>
                  <div>
                    <span class="me-2 text-bold text-footer"> Netto </span>
                    <span class="d-flex flex-column">
                      <span class="text-footer">Abrahamsbergsvägen 47</span>
                      <span class="text-footer">168 30</span>
                      <span class="text-footer">Bromma</span>
                      <span class="text-footer">073-663 11 41</span>
                    </span>
                  </div>
                </div>
              </VCardText>

              <VDivider
                :class="$vuetify.display.smAndDown ? 'm-0' : 'my-6 mx-0'"
              />

              <!-- 👉 Add purchased products -->
              <VCardText class="my-6 py-0 px-0">
                <VTable
                  class="text-no-wrap"
                  style="border-radius: 0 !important"
                >
                  <!-- 👉 table head -->
                  <thead>
                    <tr>
                      <th scope="col">Produkt / tjanst</th>
                      <th scope="col">Antal</th>
                      <th scope="col">A-pris</th>
                      <th scope="col">Belopp</th>
                    </tr>
                  </thead>
                  <!-- 👉 table body -->
                  <tbody>
                    <tr style="height: 3rem">
                      <td>Lorem Ipsum</td>
                      <td>1,00</td>
                      <td>Lorem Ipsum</td>
                      <td>Lorem Ipsum</td>
                    </tr>
                  </tbody>
                </VTable>
              </VCardText>

              <!-- 👉 Total Amount -->
              <VCardText
                class="d-flex justify-space-between flex-wrap flex-column flex-sm-row p-0"
              >
                <VSpacer />
                <div class="my-0">
                  <table class="w-100 text-black">
                    <tbody>
                      <tr>
                        <td class="pe-4">Netto</td>
                        <td
                          class="text-bold"
                          :class="
                            $vuetify.locale.isRtl ? 'text-start' : 'text-end'
                          "
                        >
                          0,00 kr
                        </td>
                      </tr>
                      <tr>
                        <td class="pe-4">Moms</td>
                        <td
                          :class="
                            $vuetify.locale.isRtl ? 'text-start' : 'text-end'
                          "
                        >
                          <div class="d-flex gap-4 align-center justify-center">
                            <div class="form-field">
                              <VSelect
                                v-model="selectedTax"
                                :items="taxOptions"
                                label="Moms"
                                @update:modelValue="handleTaxChange"
                                style="width: 150px"
                              />

                              <VTextField
                                v-if="isCustomTax"
                                v-model.number="invoice.tax"
                                class="mt-2"
                                type="number"
                                label="Customized Moms"
                                :min="0"
                                :step="0.01"
                                suffix="%"
                                style="width: 150px"
                              />
                            </div>
                            %
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>

                  <VDivider class="m-0 my-4" />

                  <table class="w-100 text-black">
                    <tbody>
                      <tr>
                        <td class="pe-4">Summa</td>
                        <td
                          class="text-bold"
                          :class="
                            $vuetify.locale.isRtl ? 'text-start' : 'text-end'
                          "
                        >
                          0,00 kr
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </VCardText>

              <VDivider
                :class="$vuetify.display.smAndDown ? 'm-0' : 'my-6 mx-0'"
              />

              <VCardText class="mb-sm-4 p-0 text-black">
                <VRow>
                  <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-bold text-footer"> Netto </span>
                    <span class="d-flex flex-column">
                      <span class="text-footer">Abrahamsbergsvägen 47</span>
                      <span class="text-footer">168 30</span>
                      <span class="text-footer">Bromma</span>
                      <span class="text-footer">073-663 11 41</span>
                    </span>

                    <span class="me-2 mt-4 text-bold text-footer">
                      Bolagets säte
                    </span>
                    <span class="text-footer"> Stockholm, Sweden </span>

                    <span class="me-2 mt-4 text-bold text-footer"> Swish </span>
                    <span class="text-footer"> 123-596 23 29 </span>
                  </VCol>
                  <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-bold text-footer"> Org.nr. </span>
                    <span class="text-footer">5593740268</span>

                    <span class="me-2 mt-4 text-bold text-footer"> Vat </span>
                    <span class="text-footer">SE559374026801</span>

                    <span class="me-2 mt-4 text-bold text-footer"> BIC </span>
                    <span class="text-footer"> NDEASESS </span>

                    <span class="me-2 mt-4 text-bold text-footer">
                      Plusgiro
                    </span>
                    <span class="text-footer"> 5886-4976 </span>
                  </VCol>
                  <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-bold text-footer"> Webbplats </span>
                    <span class="text-footer"> hejdabil.se </span>

                    <span class="me-2 mt-4 text-bold text-footer">
                      Företagets e-post
                    </span>
                    <span class="text-footer"> info@hejdabil.se </span>
                  </VCol>
                  <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-bold text-footer"> Bank </span>
                    <span class="text-footer"> Nordea </span>

                    <span class="me-2 mt-4 text-bold text-footer">
                      Bankgiro
                    </span>
                    <span class="text-footer"> 5886-4976 </span>

                    <span class="me-2 mt-4 text-bold text-footer">
                      Kontonummer
                    </span>
                    <span class="text-footer"> 9960 1821054721 </span>

                    <span class="me-2 mt-4 text-bold text-footer">
                      Iban nummer
                    </span>
                    <span class="text-footer">
                      SE06 9500 0099<br />6018 2105 4721
                    </span>
                  </VCol>
                </VRow>
              </VCardText>
            </div>
          </section>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="4"
        class="order-1 order-md-2 d-print-none"
        v-if="!$vuetify.display.smAndDown"
      >
        <VCard>
          <VCardText :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'">
            <VBtn class="btn-gradient w-100 mb-4" @click="editBilling">
              <template #prepend>
                <VIcon icon="custom-pencil" size="24" />
              </template>
              Redigera
            </VBtn>

            <VBtn
              class="btn-light w-100 mb-4"
              :to="{ name: 'dashboard-admin-billings' }"
            >
              <template #prepend>
                <VIcon icon="custom-return" size="24" />
              </template>
              Tillbaka
            </VBtn>

            <VDivider class="mb-4" />

            <VBtn
              class="btn-light w-100 mb-4"
              @click="isConfirmSendMailVisible = true"
            >
              <template #prepend>
                <VIcon icon="custom-paper-plane" size="24" />
              </template>
              Skicka
            </VBtn>

            <VBtn class="btn-light w-100 mb-4" @click="download">
              <template #prepend>
                <VIcon icon="custom-pdf" size="24" />
              </template>
              Ladda ner som PDF
            </VBtn>

            <VBtn class="btn-light w-100 mb-4" @click="printInvoice">
              <template #prepend>
                <VIcon icon="custom-print" size="24" />
              </template>
              Skriv ut
            </VBtn>

            <VBtn
              v-if="invoice.state_id !== 9"
              class="btn-light w-100 mb-4"
              @click="duplicate"
            >
              <template #prepend>
                <VIcon icon="custom-duplicate" size="24" />
              </template>
              Duplicera
            </VBtn>

            <VBtn
              v-if="invoice.state_id !== 9"
              class="btn-light w-100"
              @click="duplicate"
            >
              <template #prepend>
                <VIcon icon="custom-alarm" size="24" />
              </template>
              Påminnelse
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    <!-- 👉 Confirm Delete -->
    <VDialog v-model="isConfirmSendMailVisible" persistent class="v-dialog-sm">
      <!-- Dialog close btn -->

      <DialogCloseBtn
        @click="isConfirmSendMailVisible = !isConfirmSendMailVisible"
      />

      <!-- Dialog Content -->
      <VCard title="Skicka fakturan via e-post">
        <VDivider class="mt-4" />
        <VCardText>
          Är du säker på att du vill skicka fakturor till följande
          e-postadresser?
        </VCardText>
        <VCardText class="d-flex flex-column gap-2">
          <VCheckbox v-model="emailDefault" :label="invoice.client.email" />

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
          <span class="text-xs text-error" v-if="isValid"
            >E-postadressen måste vara en giltig e-postadress</span
          >
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmSendMailVisible = false"
          >
            Avbryt
          </VBtn>
          <VBtn @click="sendMails"> Skicka </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="skickaDialog"
      persistent
      class="action-dialog dialog-big-icon"
    >
      <!-- Dialog close btn -->

      <VBtn
        icon
        class="btn-white close-btn"
        @click="skickaDialog = !skickaDialog"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-checkmark" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title">Skickat!n</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Fakturan har skickats till "Kundnamn". Du hittar den nu i din lista
          över skickade fakturor.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="skickaDialog = false">
            Gå till fakturalistan
          </VBtn>
          <VBtn class="btn-gradient" @click=""> Skapa en ny faktura </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="inteSkapatsDialog"
      persistent
      class="action-dialog dialog-big-icon"
    >
      <!-- Dialog close btn -->

      <VBtn
        icon
        class="btn-white close-btn"
        @click="inteSkapatsDialog = !inteSkapatsDialog"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-cancel" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title">Fakturan kunde inte skickas</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Ett problem uppstod. Kontrollera kundens e-postadress och din internetanslutning och försök igen.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="inteSkapatsDialog = false">
            Stäng
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
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
.row-fill {
  @media (max-width: 768px) {
    padding-bottom: 60px;
    margin: 0px;
  }
}
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
  padding: 16px !important;
  border: solid 1px #e7e7e7;
}

.invoice-box {
  border-radius: 16px;
  padding: 16px;
  gap: 24px;
  background-color: #f6f6f6;
}

.invoice-logo-box {
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

.justify-content-center {
  justify-content: center !important;
}

.vertical-top {
  vertical-align: top;
}

.invoice-preview-table {
  --v-table-row-height: 44px !important;
}

.invoice-background {
  background-color: #f2efff;
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
    background-color: #f2efff !important;
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
  subject: billings
</route>
