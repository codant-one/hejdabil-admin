<script setup>
import { useBillingsStores } from "@/stores/useBillings";
import { excelParser } from "@/plugins/csv/excelParser";
import { themeConfig } from "@themeConfig";
import { formatNumber, formatNumberInteger } from "@/@core/utils/formatters";
import { avatarText } from "@/@core/utils/formatters";
import router from "@/router";
import Toaster from "@/components/common/Toaster.vue";

const billingsStores = useBillingsStores();
const emitter = inject("emitter");

const clients = ref([]);
const suppliers = ref([]);
const billings = ref([]);
const billingSelector = ref('Kunder');
const showMobileFilters = ref(false);
const searchQuery = ref("");
const rowPerPage = ref(10);
const currentPage = ref(1);
const totalPages = ref(1);
const totalBillings = ref(0);
const isRequestOngoing = ref(true);
const isConfirmStateDialogVisible = ref(false);
const isConfirmSendMailVisible = ref(false);
const isConfirmSendMailReminder = ref(false);
const emailDefault = ref(true);
const selectedTags = ref([]);
const existingTags = ref([]);
const isValid = ref(false);
const selectedBilling = ref({});

const supplier_id = ref(null);
const client_id = ref(null);
const state_id = ref(null);
const userData = ref(null);
const role = ref(null);
const totalSum = ref(0);
const totalTax = ref(0);
const totalNeto = ref(0);
const sum = ref(0);
const tax = ref(0);
const totalPending = ref(0);
const totalPaid = ref(0);
const totalExpired = ref(0);
const pendingTax = ref(0);
const paidTax = ref(0);
const expiredTax = ref(0);
const bgColor = ref("bg-light-secondary");
const textColor = ref("text-secondary");
const classTab = ref("border-bottom-secondary");

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = billings.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    billings.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalBillings.value} resultat`;
  // return `Visar ${firstIndex} till ${lastIndex} av ${totalBillings.value} fakturor`;
});

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value;
});

onMounted(async () => {
  state_id.value = billingsStores.getStateId ?? state_id.value;
  updateStateId(state_id.value);

  await loadData();

  if (role.value === "SuperAdmin" || role.value === "Administrator") {
    suppliers.value = billingsStores.suppliers;
  }
});

watchEffect(fetchData);

async function fetchData(cleanFilters = false) {
  if (cleanFilters === true) {
    searchQuery.value = "";
    rowPerPage.value = 10;
    currentPage.value = 1;
    supplier_id.value = null;
    client_id.value = null;
    state_id.value = null;
    bgColor.value = "bg-light-secondary";
    textColor.value = "text-secondary";
    classTab.value = "border-bottom-secondary";
  }

  let data = {
    search: searchQuery.value,
    orderByField: "id",
    orderBy: "desc",
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value,
    client_id: client_id.value,
    state_id: billingsStores.getStateId ?? state_id.value,
  };

  isRequestOngoing.value = searchQuery.value !== "" ? false : true;

  await billingsStores.fetchBillings(data);

  billings.value = billingsStores.getBillings;
  totalPages.value = billingsStores.last_page;
  totalBillings.value = billingsStores.billingsTotalCount;
  totalSum.value = billingsStores.totalSum;
  totalTax.value = billingsStores.totalTax;
  totalNeto.value = billingsStores.totalNeto;

  userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
  role.value = userData.value.roles[0].name;

  billings.value.forEach((billing) => {
    billing.checked = false;
    billing.sent = false;
  });

  isRequestOngoing.value = false;
}

watchEffect(registerEvents);

// 👉 show mobile filters when billingSelector is set
watchEffect(() => {
  showMobileFilters.value = billingSelector.value === "Kunder";
});

function registerEvents() {
  emitter.on("cleanFilters", fetchData);
}

const loadData = async () => {
  await billingsStores.info();

  sum.value = billingsStores.sum;
  tax.value = billingsStores.tax;
  totalPending.value = billingsStores.totalPending;
  totalPaid.value = billingsStores.totalPaid;
  totalExpired.value = billingsStores.totalExpired;
  pendingTax.value = billingsStores.pendingTax;
  paidTax.value = billingsStores.paidTax;
  expiredTax.value = billingsStores.expiredTax;

  clients.value = billingsStores.clients;
};

const addInvoice = () => {
  router.push({ name: "dashboard-admin-billings-add" });
};

const updateBilling = (billingData) => {
  isConfirmStateDialogVisible.value = true;
  selectedBilling.value = { ...billingData };
};

const showBilling = (billingData) => {
  billingsStores.setStateId(state_id.value);
  router.push({
    name: "dashboard-admin-billings-id",
    params: { id: billingData.id },
  });
};

const editBilling = (billingData) => {
  billingsStores.setStateId(state_id.value);
  router.push({
    name: "dashboard-admin-billings-edit-id",
    params: { id: billingData.id },
  });
};

const updateStateId = (newStateId) => {
  billingsStores.setStateId(newStateId);
  state_id.value = newStateId;

  switch (newStateId) {
    case 4:
      bgColor.value = "bg-light-warning";
      textColor.value = "text-warning";
      classTab.value = "border-bottom-warning";
      break;
    case 7:
      bgColor.value = "bg-light-info";
      textColor.value = "text-info";
      classTab.value = "border-bottom-info";
      break;
    case 8:
      bgColor.value = "bg-light-error";
      textColor.value = "text-error";
      classTab.value = "border-bottom-error";
      break;
    case null:
      bgColor.value = "bg-light-secondary";
      textColor.value = "text-secondary";
      classTab.value = "border-bottom-secondary";
      break;
  }
};

const updateState = async () => {
  isConfirmStateDialogVisible.value = false;
  let res = await billingsStores.updateState(selectedBilling.value.id);
  selectedBilling.value = {};

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

  await loadData();
  await fetchData();

  return true;
};

const openLink = function (billingData) {
  window.open(themeConfig.settings.urlStorage + billingData.file);
};

const printInvoice = async (billing) => {
  try {
    const response = await fetch(
      themeConfig.settings.urlbase +
        "proxy-image?url=" +
        themeConfig.settings.urlStorage +
        billing.file
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

const duplicate = (billing) => {
  billingsStores.setStateId(state_id.value);
  router.push({
    name: "dashboard-admin-billings-duplicate-id",
    params: { id: billing.id },
  });
};

const reminder = async () => {
  isRequestOngoing.value = true;
  isConfirmSendMailReminder.value = false;

  billingsStores
    .reminder(Number(selectedBilling.value.id))
    .then((res) => {
      isRequestOngoing.value = false;
      selectedBilling.value = {};

      advisor.value = {
        type: res.data.success ? "success" : "error",
        message: res.data.success
          ? "Påminnelse skickad framgångsrikt"
          : res.data.message,
        show: true,
      };

      setTimeout(() => {
        advisor.value = {
          type: "",
          message: "",
          show: false,
        };
      }, 3000);
    })
    .catch((err) => {
      advisor.value = {
        type: "error",
        message: err.message,
        show: true,
      };

      setTimeout(() => {
        advisor.value = {
          type: "",
          message: "",
          show: false,
        };
      }, 3000);

      isRequestOngoing.value = false;
    });
};

const sendReminder = (billingData) => {
  isConfirmSendMailReminder.value = true;
  selectedBilling.value = { ...billingData };
};

const credit = (billing) => {
  billingsStores.setStateId(state_id.value);
  router.push({
    name: "dashboard-admin-billings-credit-id",
    params: { id: billing.id },
  });
};

const send = (billingData) => {
  isConfirmSendMailVisible.value = true;
  selectedBilling.value = { ...billingData };
};

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
      id: selectedBilling.value.id,
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

const downloadCSV = async () => {
  isRequestOngoing.value = true;

  let data = { limit: -1 };

  await billingsStores.fetchBillings(data);

  let dataArray = [];

  billingsStores.getBillings.forEach((element) => {
    let data = {
      FAKTURANS_ID: element.invoice_id,
      STATUS: element.state.name,
      KUND: element.client.fullname,
      KUNDENS_E_POST: element.client.email,
      LEVERANTÖR: element.supplier
        ? element.supplier.user.name + " " + element.supplier.user.last_name
        : "",
      LEVERANTÖRENS_E_POST: element.supplier ? element.supplier.user.email : "",
      FAKTURADATUM: element.invoice_date,
      FÖRFALLER: element.due_date,
      Summa: element.total + " kr",
    };

    dataArray.push(data);
  });

  excelParser().exportDataFromJSON(dataArray, "billings", "csv");

  isRequestOngoing.value = false;
};
</script>

<template>
  <section>
    <Toaster />
    <VRow>
      <VDialog v-model="isRequestOngoing" width="auto" persistent>
        <VProgressCircular indeterminate color="primary" class="mb-0" />
      </VDialog>

      <VCol cols="12">
        <VAlert v-if="advisor.show" :type="advisor.type" class="mb-6">
          {{ advisor.message }}
        </VAlert>

        <VCard class="card-fill">
          <VCardTitle
            class="d-flex justify-space-between"
            :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
          >
            <div class="d-flex align-center w-100 w-md-auto font-blauer">
              <h2>Fakturor ({{ billings.length }})</h2>
            </div>

            <div class="d-flex gap-4 title-action-buttons">
              <VBtn class="btn-light w-100 w-md-auto" @click="downloadCSV">
                <VIcon icon="custom-export" size="24" />
                Exportera
              </VBtn>

              <VBtn
                v-if="$can('create', 'billings') && !$vuetify.display.smAndDown"
                class="btn-gradient w-100 w-md-auto"
                @click="addInvoice"
              >
                <VIcon icon="custom-plus" size="24" />
                Ny faktura
              </VBtn>

              <VBtn
                v-if="$vuetify.display.smAndDown && $can('create', 'billings')"
                class="btn-gradient w-100 w-md-auto"
                @click="addInvoice"
              >
                <VIcon icon="custom-plus" size="24" />
                Ny faktura
              </VBtn>
              <VDialog
                v-model="isDialogOpen"
                transition="dialog-bottom-transition"
                content-class="dialog-bottom-full-width"
                @update:model-value="handleMobileDialogUpdate"
              >
                <VCard>
                  <AddNewClientMobile
                    v-model:isDrawerOpen="isDialogOpen"
                    :client="selectedClient"
                    :suppliers="suppliers"
                    @client-data="submitForm"
                    @edited="onClientFormEdited"
                  />
                </VCard>
              </VDialog>
            </div>
          </VCardTitle>

          <VDivider :class="$vuetify.display.smAndDown ? 'm-0' : 'mt-2 mx-4'" />

          <VCardText
            class="d-flex align-center justify-space-between gap-4 filter-bar"
            :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
          >
            <!-- 👉 Search  -->
            <div class="search">
              <VTextField v-model="searchQuery" placeholder="Sök" clearable />
            </div>

            <!-- <VAutocomplete
              v-if="role !== 'Supplier'"
              v-model="supplier_id"
              placeholder="Leverantörer"
              :items="suppliers"
              :item-title="(item) => item.full_name"
              :item-value="(item) => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              style="width: 200px"
              :menu-props="{ maxHeight: '300px' }"
            /> -->

            <VSpacer class="d-none d-md-block" />

            <VSelect
              class="billing-selector"
              prepend-icon="custom-profile"
              :items="['Kunder']"
              v-model="billingSelector"
            />

            <VBtn
              class="btn-white-2"
              v-if="role !== 'Supplier' && role !== 'User'"
            >
              <VIcon icon="custom-filter" size="24" />
              <span class="d-none d-md-block">Filtrera efter</span>
            </VBtn>

            <div
              v-if="!$vuetify.display.smAndDown"
              class="d-flex align-center visa-select"
            >
              <span class="text-no-wrap pr-4">Visa:</span>
              <VSelect
                v-model="rowPerPage"
                class="custom-select-hover"
                :items="[10, 20, 30, 50]"
              />
            </div>
          </VCardText>

          <VCardText :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'">
            <div class="d-flex gap-4 billings-pills">
              <div
                v-for="{
                  title,
                  stateId,
                  tax,
                  value,
                  icon,
                  color,
                  background,
                } in [
                  {
                    title: 'Netto',
                    value: formatNumberInteger(sum ?? '0,00') + ' kr',
                    icon: 'custom-coins',
                    color: '#0C5B27',
                    background: '#D8FFE4',
                  },
                  {
                    title: 'Moms',
                    value: formatNumberInteger(totalPending ?? '0,00') + ' kr',
                    icon: 'custom-buy-cash',
                    color: '#00624E',
                    background: '#C6FFEB',
                  },
                  {
                    title: 'Summa',
                    value: formatNumberInteger(totalPaid ?? '0,00') + ' kr',
                    icon: 'custom-money-transfer',
                    color: '#04585D',
                    background: '#C0FEFF',
                  },
                ]"
                :key="title"
              >
                <div
                  class="billings-pill"
                  :style="{ backgroundColor: background, color: color }"
                >
                  <VIcon :icon="icon" :color="color" size="24" class="mr-2" />
                  <div class="billings-pill-title">{{ title }}</div>
                  <div class="billings-pill-value">{{ value }}</div>
                </div>
              </div>
            </div>
            <!-- <VRow>
              <div class="d-flex gap-4">
                <div
                  v-for="{ title, stateId, tax, value, icon, color } in [
                    {
                      title: 'Alla',
                      stateId: null,
                      tax: formatNumberInteger(tax ?? '0,00') + ' kr',
                      value: formatNumberInteger(sum ?? '0,00') + ' kr',
                      icon: 'mdi-invoice-list-outline',
                      color: 'secondary',
                    },
                    {
                      title: 'Obetalda',
                      stateId: 4,
                      tax: formatNumberInteger(pendingTax ?? '0,00') + ' kr',
                      value:
                        formatNumberInteger(totalPending ?? '0,00') + ' kr',
                      icon: 'mdi-invoice-text-clock',
                      color: 'warning',
                    },
                    {
                      title: 'Betalda',
                      stateId: 7,
                      tax: formatNumberInteger(paidTax ?? '0,00') + ' kr',
                      value: formatNumberInteger(totalPaid ?? '0,00') + ' kr',
                      icon: 'mdi-invoice-text-check',
                      color: 'info',
                    },
                    {
                      title: 'Förfallna',
                      stateId: 8,
                      tax: formatNumberInteger(expiredTax ?? '0,00') + ' kr',
                      value:
                        formatNumberInteger(totalExpired ?? '0,00') + ' kr',
                      icon: 'mdi-invoice-text-remove',
                      color: 'error',
                    },
                  ]"
                  :key="title"
                ></div>
              </div>
              <VCol
                cols="12"
                md="10"
                class="d-flex justify-content-between align-center"
                :class="$vuetify.display.mdAndUp ? 'border-e' : 'border-b'"
              >
                <div
                  class="d-flex justify-space-between flex-wrap w-100 flex-column flex-md-row gap-3"
                >
                  <div
                    v-for="{ title, stateId, tax, value, icon, color } in [
                      {
                        title: 'Alla',
                        stateId: null,
                        tax: formatNumberInteger(tax ?? '0,00') + ' kr',
                        value: formatNumberInteger(sum ?? '0,00') + ' kr',
                        icon: 'mdi-invoice-list-outline',
                        color: 'secondary',
                      },
                      {
                        title: 'Obetalda',
                        stateId: 4,
                        tax: formatNumberInteger(pendingTax ?? '0,00') + ' kr',
                        value:
                          formatNumberInteger(totalPending ?? '0,00') + ' kr',
                        icon: 'mdi-invoice-text-clock',
                        color: 'warning',
                      },
                      {
                        title: 'Betalda',
                        stateId: 7,
                        tax: formatNumberInteger(paidTax ?? '0,00') + ' kr',
                        value: formatNumberInteger(totalPaid ?? '0,00') + ' kr',
                        icon: 'mdi-invoice-text-check',
                        color: 'info',
                      },
                      {
                        title: 'Förfallna',
                        stateId: 8,
                        tax: formatNumberInteger(expiredTax ?? '0,00') + ' kr',
                        value:
                          formatNumberInteger(totalExpired ?? '0,00') + ' kr',
                        icon: 'mdi-invoice-text-remove',
                        color: 'error',
                      },
                    ]"
                    :key="title"
                  >
                    <div
                      class="d-flex cursor-pointer"
                      @click="updateStateId(stateId)"
                      :class="stateId === state_id ? classTab : ''"
                    >
                      <VAvatar
                        variant="tonal"
                        :color="color"
                        rounded
                        size="65"
                        class="me-2"
                      >
                        <VIcon :icon="icon" size="45" />
                      </VAvatar>
                      <div>
                        <h5
                          class="text-h5 font-weight-medium"
                          :class="`text-${color}`"
                        >
                          {{ title }}
                        </h5>
                        <h6 class="text-h6" :class="`text-${color}`">
                          {{ value }}
                        </h6>
                        <span class="text-sm" :class="`text-${color}`">
                          varav moms {{ tax }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="2" class="d-flex flex-column">
                <VAutocomplete
                  v-model="client_id"
                  :items="clients"
                  :item-title="(item) => item.fullname"
                  :item-value="(item) => item.id"
                  placeholder="Kunder"
                  class="mb-2"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"
                />

                <VAutocomplete
                  v-if="role === 'SuperAdmin' || role === 'Administrator'"
                  v-model="supplier_id"
                  placeholder="Leverantörer"
                  :items="suppliers"
                  :item-title="(item) => item.full_name"
                  :item-value="(item) => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"
                />

                <VTextField
                  v-else
                  v-model="searchQuery"
                  placeholder="Sök"
                  density="compact"
                  clearable
                />
              </VCol>
            </VRow> -->
          </VCardText>
          <!-- <VDivider /> -->
          <!-- <VCardText class="d-flex align-center flex-wrap gap-4">
            <div class="d-flex align-center w-100 w-md-auto">
              <span class="text-no-wrap me-3">Visa:</span>
              <VSelect
                v-model="rowPerPage"
                density="compact"
                variant="outlined"
                class="w-100"
                :items="[10, 20, 30, 50]"
              />
            </div>

            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-file-export"
              class="w-100 w-md-auto"
              @click="downloadCSV"
            >
              Exportera
            </VBtn>

            <VSpacer class="d-none d-md-block" />

            <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto">

              <div
                class="search"
                v-if="role === 'SuperAdmin' || role === 'Administrator'"
              >
                <VTextField
                  v-model="searchQuery"
                  placeholder="Sök"
                  density="compact"
                  clearable
                />
              </div>
              <VBtn
                v-if="$can('create', 'billing')"
                prepend-icon="tabler-plus"
                class="w-100 w-md-auto"
                @click="addInvoice"
              >
                Ny faktura
              </VBtn>
            </div>
          </VCardText>

          <VDivider /> -->

          <VTable
            class="pt-2 px-4 pb-6 text-no-wrap"
            style="border-radius: 0 !important"
          >
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"># Faktura</th>
                <th scope="col">Kund</th>
                <th
                  scope="col"
                  v-if="role === 'SuperAdmin' || role === 'Administrator'"
                >
                  Leverantör
                </th>
                <th class="text-end" scope="col">Summa</th>
                <th scope="col">Fakturadatum</th>
                <th scope="col">Förfaller</th>
                <th class="text-center" scope="col">Betald</th>
                <th class="text-center" scope="col">Skickad</th>
                <th scope="col">Skapad av</th>
                <th class="text-center" scope="col">Status</th>
                <th
                  class="text-center"
                  scope="col"
                  v-if="$can('edit', 'billing') || $can('delete', 'billing')"
                ></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr
                v-for="billing in billings"
                :key="billing.id"
                style="height: 3rem"
              >
                <td>{{ billing.invoice_id }}</td>
                <td class="text-wrap">
                  <span
                    class="d-flex justify-between align-center font-weight-medium cursor-pointer text-aqua"
                    @click="showBilling(billing)"
                  >
                    {{ billing.client.fullname ?? "" }}
                  </span>
                </td>
                <td
                  class="text-wrap"
                  v-if="role === 'SuperAdmin' || role === 'Administrator'"
                >
                  <span v-if="billing.supplier">
                    {{ billing.supplier.user.name }}
                    {{ billing.supplier.user.last_name ?? "" }}
                  </span>
                </td>
                <td class="text-end">
                  {{
                    formatNumber(billing.total + billing.amount_discount) ??
                    "0,00"
                  }}
                  kr
                </td>
                <td>{{ billing.invoice_date }}</td>
                <td>{{ billing.due_date }}</td>
                <td class="text-center">
                  <!-- 
                      4: pendiente  => warning
                      7: pagado => info
                      8: expirado => error
                      9: credito => error
                    -->
                  <VCheckbox
                    v-model="billing.checked"
                    color="info"
                    class="w-100 text-center d-flex justify-content-center"
                    :disabled="billing.state_id === 7 || billing.state_id === 9"
                    :value="
                      billing.state_id === 7 || billing.state_id === 9
                        ? false
                        : true
                    "
                    @click.prevent="updateBilling(billing)"
                  />
                </td>
                <td class="text-center">
                  <VCheckbox
                    v-model="billing.sent"
                    color="info"
                    class="w-100 text-center d-flex justify-content-center"
                    :disabled="billing.is_sent === 1"
                    :value="billing.is_sent === 1 ? false : true"
                    @click.prevent="send(billing)"
                  />
                </td>
                <td class="text-wrap">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      :variant="billing.user.avatar ? 'outlined' : 'tonal'"
                      size="38"
                    >
                      <VImg
                        v-if="billing.user.avatar"
                        style="border-radius: 50%"
                        :src="
                          themeConfig.settings.urlStorage + billing.user.avatar
                        "
                      />
                      <span v-else>{{ avatarText(billing.user.name) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ billing.user.name }}
                        {{ billing.user.last_name ?? "" }}
                      </span>
                      <span class="text-sm text-disabled">{{
                        billing.user.email
                      }}</span>
                    </div>
                  </div>
                </td>
                <!-- 😵 Statuses -->
                <td class="text-center text-wrap">
                  <div
                    :color="billing.state.color"
                    class="billing-chip text-capitalize"
                  >
                    {{ billing.state.name }}
                  </div>
                </td>
                <!-- 👉 Acciones -->
                <td
                  class="text-center"
                  style="width: 3rem"
                  v-if="$can('edit', 'billing') || $can('delete', 'billing')"
                >
                  <VMenu>
                    <template #activator="{ props }">
                      <VBtn
                        v-bind="props"
                        icon
                        variant="text"
                        class="btn-white"
                      >
                        <VIcon icon="custom-dots-vertical" size="22" />
                      </VBtn>
                    </template>
                    <VList>
                      <VListItem
                        v-if="
                          $can('edit', 'billing') &&
                          (billing.state_id === 4 || billing.state_id === 8)
                        "
                        @click="editBilling(billing)"
                      >
                        <template #prepend>
                          <VIcon icon="custom-pencil" size="24" class="mr-2" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('edit', 'billing')"
                        @click="printInvoice(billing)"
                      >
                        <template #prepend>
                          <VIcon icon="custom-print" size="24" class="mr-2" />
                        </template>
                        <VListItemTitle>Skriv ut</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('edit', 'billing')"
                        @click="openLink(billing)"
                      >
                        <template #prepend>
                          <VIcon icon="custom-pdf" size="24" class="mr-2" />
                        </template>
                        <VListItemTitle>Visa som PDF</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('edit', 'billing')"
                        @click="duplicate(billing)"
                      >
                        <template #prepend>
                          <VIcon
                            icon="custom-duplicate"
                            size="24"
                            class="mr-2"
                          />
                        </template>
                        <VListItemTitle>Duplicera</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('edit', 'billing') && billing.state_id === 8"
                        @click="sendReminder(billing)"
                      >
                        <template #prepend>
                          <VIcon icon="custom-alarm" size="24" class="mr-2" />
                        </template>
                        <VListItemTitle>Påminnelse</VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="$can('edit', 'billing')"
                        @click="send(billing)"
                      >
                        <template #prepend>
                          <VIcon
                            icon="custom-paper-plane"
                            size="24"
                            class="mr-2"
                          />
                        </template>
                        <VListItemTitle>Skicka</VListItemTitle>
                      </VListItem>

                      <VListItem
                        v-if="
                          $can('delete', 'billing') && billing.state_id === 7
                        "
                        @click="credit(billing)"
                      >
                        <template #prepend>
                          <VIcon
                            icon="custom-cancel-contract"
                            size="24"
                            class="mr-2"
                          />
                        </template>
                        <VListItemTitle>Kreditera</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </td>
              </tr>
            </tbody>
            <!-- 👉 table footer  -->
            <tfoot v-show="!billings.length">
              <tr>
                <td
                  :colspan="role === 'Supplier' ? 12 : 13"
                  class="text-center"
                >
                  Uppgifter ej tillgängliga
                </td>
              </tr>
            </tfoot>
          </VTable>

          <VCardText
            class="d-block d-md-flex text-center align-center flex-wrap gap-4 py-3"
          >
            <span class="text-pagination-results">
              {{ paginationData }}
            </span>

            <VSpacer class="d-none d-md-block" />

            <!-- <span class="d-block d-md-flex text-sm text-disabled">
              <strong class="d-block me-md-5"
                >NETTO: {{ formatNumber(totalNeto ?? 0) }} kr</strong
              >
              <strong class="d-block me-md-5"
                >MOMS: {{ formatNumber(totalTax ?? 0) }} kr</strong
              >
              <strong class="d-block"
                >Summa: {{ formatNumber(totalSum ?? 0) }} kr</strong
              >
            </span>

            <VSpacer class="d-none d-md-block" /> -->

            <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="5"
              :length="totalPages"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 👉 Confirm send -->
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
          <VCheckbox
            v-model="emailDefault"
            :label="selectedBilling.client.email"
          />

          <VCombobox
            v-model="selectedTags"
            :items="existingTags"
            label="Ange e-postadresser för att skicka fakturan"
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

    <VDialog v-model="isConfirmSendMailReminder" persistent class="v-dialog-sm">
      <!-- Dialog close btn -->

      <DialogCloseBtn
        @click="isConfirmSendMailReminder = !isConfirmSendMailReminder"
      />

      <!-- Dialog Content -->
      <VCard title="Skicka påminnelse via e-post">
        <VDivider class="mt-4" />
        <VCardText>
          Vill du skicka ett påminnelsemeddelande för faktura
          <strong>#{{ selectedBilling.invoice_id }}</strong
          >?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmSendMailReminder = false"
          >
            Avbryt
          </VBtn>
          <VBtn @click="reminder"> Skicka </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Update State -->
    <VDialog
      v-model="isConfirmStateDialogVisible"
      persistent
      class="v-dialog-sm"
    >
      <!-- Dialog close btn -->

      <DialogCloseBtn
        @click="isConfirmStateDialogVisible = !isConfirmStateDialogVisible"
      />

      <!-- Dialog Content -->
      <VCard title="Uppdatera status">
        <VDivider class="mt-4" />
        <VCardText>
          Är du säker på att du vill uppdatera fakturans status
          <strong>#{{ selectedBilling.invoice_id }}</strong> till betalda?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmStateDialogVisible = false"
          >
            Avbryt
          </VBtn>
          <VBtn @click="updateState"> Acceptera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss" scope>
.border-bottom-secondary {
  border-bottom: 2px solid #2e0684;
  padding-bottom: 5px;
}

.border-bottom-warning {
  border-bottom: 2px solid #ffc549;
  padding-bottom: 5px;
}

.border-bottom-info {
  border-bottom: 2px solid #28c76f;
  padding-bottom: 5px;
}

.border-bottom-error {
  border-bottom: 2px solid #ea5455;
  padding-bottom: 5px;
}

.v-input--disabled svg rect {
  fill: #28c76f !important;
}

.v-input--disabled {
  pointer-events: visible !important;
  cursor: no-drop !important;
}

.search {
  width: 484px !important;
}

.justify-content-center {
  justify-content: center !important;
}

.billing-selector {
  max-width: 132px;
  .v-input__prepend {
    margin-right: 0px;
    color: #454545;

    .v-icon {
      opacity: 1 !important;
    }
  }

  .v-input__control {
    width: 110px;
    .v-field {
      padding-right: 0px;

      .v-field__input {
        padding-inline-start: 8px;
        opacity: 1 !important;
        color: #454545;
      }
    }
    .v-field__append-inner {
      .v-icon {
        opacity: 1 !important;
        color: #454545;
      }
    }
    .v-field__outline {
      .v-field__outline__start,
      .v-field__outline__end {
        border: 0px !important;
      }
    }
  }
}

.billings-pills > div {
  flex: 1 1;
}

.billings-pill {
  display: flex;
  align-items: center;
  padding: 16px;
  border-radius: 8px;
}

.billings-pill-title {
  font-family: "Blauer Nue";
  font-weight: 400;
  font-size: 16px;
  line-height: 100%;
  margin-right: 4px;
}

.billings-pill-value {
  font-family: "Blauer Nue";
  font-weight: 700;
  font-style: Bold;
  font-size: 16px;
  line-height: 100%;
}

.billing-chip {
  border-radius: 32px;
  padding: 8px 16px;
  border: solid 1px #454545;

  font-weight: 400;
  font-size: 14px;
  line-height: 16px;
  text-align: center;
  color: #454545;
}

@media (min-width: 991px) {
  .search {
    width: 30rem !important;
  }
}
</style>
<route lang="yaml">
meta:
  action: view
  subject: billing
</route>
