<script setup>
import { useDisplay } from "vuetify";
import { useBillingsStores } from "@/stores/useBillings";
import { excelParser } from "@/plugins/csv/excelParser";
import { themeConfig } from "@themeConfig";
import { formatNumber, formatNumberInteger } from "@/@core/utils/formatters";
import { avatarText } from "@/@core/utils/formatters";
import router from "@/router";
import Toaster from "@/components/common/Toaster.vue";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const billingsStores = useBillingsStores();
const emitter = inject("emitter");

const { width: windowWidth } = useWindowSize();

const clients = ref([]);
const suppliers = ref([]);
const billings = ref([]);
const billingSelector = ref("Kunder");
const showMobileFilters = ref(false);
const searchQuery = ref("");
const rowPerPage = ref(10);
const currentPage = ref(1);
const totalPages = ref(1);
const totalBillings = ref(0);
const isRequestOngoing = ref(true);
const hasLoaded = ref(false);
const isConfirmStateDialogVisible = ref(false);
const isConfirmSendMailVisible = ref(false);
const isConfirmSendMailReminder = ref(false);
const isConfirmKreditera = ref(false)
const emailDefault = ref(true);
const selectedTags = ref([]);
const existingTags = ref([]);
const isValid = ref(false);
const selectedBilling = ref({});
const selectedBillingForAction = ref({});
const isMobileActionDialogVisible = ref(false);

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
const filtreraMobile = ref(false);
const isFilterDialogVisible = ref(false);

const sectionEl = ref(null);

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => (mdAndDown.value ? "" : "top end"));

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

  userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
  role.value = userData.value.roles[0].name;

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
  isFilterDialogVisible.value = false;

  await billingsStores.fetchBillings(data);

  billings.value = billingsStores.getBillings;
  totalPages.value = billingsStores.last_page;
  totalBillings.value = billingsStores.billingsTotalCount;
  totalSum.value = billingsStores.totalSum;
  totalTax.value = billingsStores.totalTax;
  totalNeto.value = billingsStores.totalNeto;

  billings.value.forEach((billing) => {
    billing.checked = false;
    billing.sent = false;
  });

  hasLoaded.value = true;
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
  // Si ya está seleccionado, desmarcarlo (poner null)
  if (state_id.value === newStateId) {
    newStateId = null;
  }

  billingsStores.setStateId(newStateId);
  state_id.value = newStateId;
  filtreraMobile.value = false;
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

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
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

const kreditera = () => {
  isRequestOngoing.value = true
  isConfirmKreditera.value = false;

  billingsStores.credit(Number(selectedBilling.value.id))
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

const credit = (billingData) => {
  isConfirmKreditera.value = true;
  selectedBilling.value = { ...billingData };
  billingsStores.setStateId(state_id.value);
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
});
</script>

<template>
  <section class="page-section" ref="sectionEl">
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

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
        <div class="align-center font-blauer">
          <h2>
            Fakturor <span v-if="hasLoaded">({{ billings.length }})</span>
          </h2>
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

        <div class="d-flex gap-4">
          <VBtn 
            class="btn-light w-auto" 
            block
            @click="downloadCSV">
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

          <VBtn
            v-if="$can('create', 'billings')"
            class="btn-gradient"
            block
            @click="addInvoice"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny faktura
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.mdAndDown ? 'p-6 pb-0' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search" style="width: 480px !important">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-2'">
          <AppAutocomplete
            v-if="role !== 'Supplier' && hasLoaded"
            prepend-icon="custom-profile"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="(item) => item.full_name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate"
          />

          <AppAutocomplete
            prepend-icon="custom-profile"
            v-model="client_id"
            :items="clients"
            :item-title="(item) => item.fullname"
            :item-value="(item) => item.id"
            placeholder="Kunder"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate"
          />
        </div>

        <VBtn
          class="btn-white-2 px-3"
          @click="isFilterDialogVisible = true"
          :class="windowWidth > 1023 ? 'd-none' : 'd-flex'"
        >
          <VIcon icon="custom-profile" size="24" />
        </VBtn>

        <VBtn
          class="btn-white-2 px-3"
          @click="filtreraMobile = true"
          v-if="$vuetify.display.mdAndDown"
        >
          <VIcon icon="custom-filter" size="24" />
          <span class="d-none d-md-block">Filtrera efter</span>
        </VBtn>

        <VMenu v-if="!$vuetify.display.mdAndDown">
          <template #activator="{ props }">
            <VBtn class="btn-white-2 px-2" v-bind="props">
              <VIcon icon="custom-filter" size="24" />
              <span class="d-none d-md-block">Filtrera efter</span>
            </VBtn>
          </template>
          <VList>
            <VListItem @click="updateStateId(7)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 7"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Betalda</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(4)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 4"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Obetalda</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(8)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 8"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Förfallna</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(9)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 9"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Krediterad</VListItemTitle>
            </VListItem>
          </VList>
        </VMenu>

        <div
          v-if="!$vuetify.display.mdAndDown"
          class="d-flex align-center visa-select"
        >
          <span class="text-no-wrap pr-4">Visa</span>
          <VSelect
            v-model="rowPerPage"
            class="custom-select-hover"
            :items="[10, 20, 30, 50]"
          />
        </div>
      </VCardText>

      <VCardText :class="$vuetify.display.mdAndDown ? 'px-6 py-4' : 'pa-4'">
        <div class="d-flex gap-4 billings-pills">
          <div
            v-for="{ title, stateId, tax, value, icon, color, background } in [
              {
                title: 'Netto',
                value: formatNumberInteger(totalNeto ?? '0,00') + ' kr',
                icon: 'custom-coins',
                color: '#0C5B27',
                background: '#D8FFE4',
              },
              {
                title: 'Moms',
                value: formatNumberInteger(totalTax ?? '0,00') + ' kr',
                icon: 'custom-buy-cash',
                color: '#00624E',
                background: '#C6FFEB',
              },
              {
                title: 'Summa',
                value: formatNumberInteger(totalSum ?? '0,00') + ' kr',
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
      </VCardText>

      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="billings.length"
        class="pt-2 px-4 pb-6 text-no-wrap"
        style="border-radius: 0 !important"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col"># Faktura</th>
            <th scope="col">Kund</th>
            <th scope="col" v-if="role === 'SuperAdmin' || role === 'Administrator'">Leverantör</th>
            <th class="text-center" scope="col">Summa</th>
            <th class="text-center" scope="col">Fakturadatum</th>
            <th class="text-center" scope="col">Förfaller</th>
            <th class="text-center" scope="col">Status</th>
            <th scope="col">Skapad av</th>
            <th scope="col" v-if="$can('edit', 'billings') || $can('delete', 'billings')"></th>
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
            <td class="text-wrap" v-if="role === 'SuperAdmin' || role === 'Administrator'">
              <span v-if="billing.supplier">
                {{ billing.supplier.user.name }}
                {{ billing.supplier.user.last_name ?? "" }}
              </span>
            </td>
            <td class="text-center">
              {{ formatNumber(billing.total + billing.amount_discount) ?? "0,00" }} kr
            </td>
            <td class="text-center">{{ billing.invoice_date }}</td>
            <td class="text-center">{{ billing.due_date }}</td>
            <!-- 😵 Statuses -->
            <td class="text-center text-wrap d-flex justify-center align-center">
              <div
                class="status-chip"
                :class="`status-chip-${resolveStatus(billing.state.id)?.class}`"
              >
                {{ billing.state.name }}
              </div>
            </td>
            <td style="width: 1%; white-space: nowrap">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  :variant="billing.user.avatar ? 'outlined' : 'tonal'"
                  size="38"
                >
                  <VImg
                    v-if="billing.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + billing.user.avatar"
                  />
                  <span v-else>{{ avatarText(billing.user.name) }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ billing.user.name }} {{ billing.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="billing.user.email && billing.user.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(billing.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ billing.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ billing.user.email }}</span>
                  </span>
                </div>
              </div>
            </td>            
            <!-- 👉 Actions -->
            <td
              class="text-center"
              style="width: 3rem"
              v-if="$can('edit', 'billings') || $can('delete', 'billings')"
            >
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                    v-if="$can('view', 'billings')"
                    @click="showBilling(billing)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-eye" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Se detaljer</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'billings') && (billing.state_id === 4 || billing.state_id === 8)"
                    @click="updateBilling(billing)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-cash-2" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Betala</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="
                      $can('view', 'billings') &&
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
                    v-if="$can('view', 'billings')"
                    @click="printInvoice(billing)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-print" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Skriv ut</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('view', 'billings')"
                    @click="openLink(billing)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-pdf" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Visa som PDF</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'billings')"
                    @click="duplicate(billing)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-duplicate" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Duplicera</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'billings') && billing.state_id === 8"
                    @click="sendReminder(billing)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-alarm" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Påminnelse</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('view', 'billings')"
                    @click="send(billing)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-paper-plane" size="24" class="mr-2" />
                    </template>
                    <VListItemTitle>Skicka</VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="$can('edit', 'billings') && billing.state_id !== 9"
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
      </VTable>
      <div
        v-if="!isRequestOngoing && hasLoaded && !billings.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-order"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga fakturor skapade än</div>
          <div class="empty-state-text">
            Här kommer alla dina skapade fakturor att listas. Skapa din första
            för att komma igång med din försäljning.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'clients') && !$vuetify.display.mdAndDown"
          @click="isAddNewClientDrawerVisible = true"
        >
          Skapa ny faktura
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'clients')"
          @click="isDialogOpen = true"
        >
           Skapa ny faktura
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="billings.length && $vuetify.display.mdAndDown"
      >
        <VExpansionPanel v-for="billing in billings" :key="billing.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <span class="order-id">{{ billing.invoice_id }}</span>
            <div class="order-title-box">
              <span class="title-panel">
                {{ billing.client.fullname ?? "" }}</span
              >
              <div class="title-organization">
                Summa
                <div class="text-black">
                  {{
                    formatNumber(billing.total + billing.amount_discount) ??
                    "0,00"
                  }}
                  kr
                </div>
              </div>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Fakturadatum:</div>
              <div class="expansion-panel-item-value">
                {{ billing.invoice_date }}
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Forfaller:</div>
              <div class="expansion-panel-item-value">
                {{ billing.due_date }}
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Status:</div>
              <div class="expansion-panel-item-value">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(billing.state.id)?.class}`"
                >
                  {{ billing.state.name }}
                </div>
              </div>
            </div>
            <div class="d-flex gap-4">
              <VBtn class="btn-light flex-1" @click="showBilling(billing)">
                <VIcon icon="custom-eye" size="24" />
                Se detaljer
              </VBtn>

              <VBtn class="btn-light" icon @click="selectedBillingForAction = billing; isMobileActionDialogVisible = true">
                <VIcon icon="custom-dots-vertical" size="24" />
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="billings.length"
        :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
        class="align-center flex-wrap gap-4 pt-0 px-6"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
        
        <VPagination
          v-model="currentPage"
          size="small"
          :total-visible="5"
          :length="totalPages"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>
    </VCard>

    <!-- 👉 Confirm send -->
    <VDialog 
      v-model="isConfirmSendMailVisible" 
      persistent 
      class="action-dialog">
      <!-- Dialog close btn -->

      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmSendMailVisible = !isConfirmSendMailVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-paper-plane" class="action-icon" />
          <div class="dialog-title">
            Skicka fakturan via e-post
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill skicka fakturor till följande
          e-postadresser?
        </VCardText>
        <VCardText class="d-flex flex-column gap-2">
          <VCheckbox
            v-model="emailDefault"
            :label="selectedBilling.client.email"
            class="ml-2"
          />
          <VLabel class="text-body-2 text-high-emphasis" text="Ange e-postadresser för att skicka fakturan" />
          <VCombobox
            v-model="selectedTags"
            :items="existingTags"
            multiple
            chips
            deletable-chips
            clearable
            @blur="addTag"
            @keydown.enter.prevent="addTag"
            @input="isValid = false"
          />
          <span 
            class="text-xs text-error" 
            v-if="isValid">
            E-postadressen måste vara en giltig e-postadress
          </span>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0">
          <VBtn class="btn-light" @click="isConfirmSendMailVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="sendMails"> Skicka </VBtn>
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
          <strong>#{{ selectedBilling.invoice_id }}</strong
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

    <!-- 👉 Confirm send reminder -->
    <VDialog 
      v-model="isConfirmSendMailReminder" 
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmSendMailReminder = !isConfirmSendMailReminder"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
         <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-alarm" class="action-icon" />
          <div class="dialog-title">
            Skicka påminnelse via e-post
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Vill du skicka ett påminnelsemeddelande för faktura
          <strong>#{{ selectedBilling.invoice_id }}</strong
          >?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmSendMailReminder = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="reminder"> Skicka </VBtn>
        </VCardText>
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
          <strong>#{{ selectedBilling.invoice_id }}</strong> till betalda?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmStateDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn  class="btn-gradient" @click="updateState"> Acceptera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile Filter Dialog -->
    <VDialog
      v-model="filtreraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem @click="updateStateId(7)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 7"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Betalda</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(4)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                 :model-value="state_id === 4"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Obetalda</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(8)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 8"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Förfallna</VListItemTitle>
          </VListItem>

           <VListItem @click="updateStateId(9)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 9"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Krediterad</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Filter Dialog -->
    <VDialog
      v-model="isFilterDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isFilterDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filter" class="action-icon" />
          <div class="dialog-title">Filtrera efter</div>
        </VCardText>
        
        <VCardText class="pt-0">
          <AppAutocomplete
            v-if="role !== 'Supplier'"
            prepend-icon="custom-profile"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="(item) => item.full_name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate mb-3"
          />

          <AppAutocomplete
            prepend-icon="custom-profile"
            v-model="client_id"
            :items="clients"
            :item-title="(item) => item.fullname"
            :item-value="(item) => item.id"
            placeholder="Kunder"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate w-auto"
          />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-10">
          <VBtn class="btn-light" @click="isFilterDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="isFilterDialogVisible = false">
            Stäng
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile Action Dialog -->
    <VDialog
      v-model="isMobileActionDialogVisible"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem
            v-if="$can('edit', 'billings') && (selectedBillingForAction.state_id === 4 || selectedBillingForAction.state_id === 8)"
            @click="updateBilling(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-cash-2" size="24" />
            </template>
            <VListItemTitle>Betala</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'billings') &&  (selectedBillingForAction.state_id === 4 || selectedBillingForAction.state_id === 8)"
            @click="editBilling(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'billings')"
            @click="printInvoice(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-print" size="24" />
            </template>
            <VListItemTitle>Skriv ut</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'billings')"
            @click="openLink(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-pdf" size="24" />
            </template>
            <VListItemTitle>Visa som PDF</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'billings')"
            @click="duplicate(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-duplicate" size="24" />
            </template>
            <VListItemTitle>Duplicera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'billings') && selectedBillingForAction.state_id === 8"
            @click="sendReminder(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-alarm" size="24" />
            </template>
            <VListItemTitle>Påminnelse</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'billings')"
            @click="send(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-paper-plane" size="24" />
            </template>
            <VListItemTitle>Skicka</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'billings') && selectedBillingForAction.state_id !== 9"
            @click="credit(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-cancel-contract" size="24" />
            </template>
            <VListItemTitle>Kreditera</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss" scope>
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

  @media (max-width: 991px) {
    .billings-pills {
      flex-direction: column;
      gap: 8px;
    }

    .billings-pill {
      padding: 8px 16px;
    }

    .title-panel {
      color: #6E9383 !important;
    }

    .v-checkbox-btn .v-selection-control__input .v-icon.iconify--custom {
      block-size: 24px !important;
      font-size: 24px !important;
      inline-size: 24px !important;
      color: #454545 !important;
    }
  }
</style>

<route lang="yaml">
meta:
  action: view
  subject: billings
</route>
