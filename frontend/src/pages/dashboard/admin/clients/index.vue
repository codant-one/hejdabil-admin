<script setup>

import { ref, toRaw } from "vue";
import { useDisplay } from "vuetify";
import { useSuppliersStores } from "@/stores/useSuppliers";
import { useClientsStores } from "@/stores/useClients";
import { excelParser } from "@/plugins/csv/excelParser";
import { themeConfig } from "@themeConfig";
import { avatarText, formatDateYMD, formatNumber } from "@/@core/utils/formatters";
import AddNewClientDrawer from "./AddNewClientDrawer.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import router from "@/router";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

import eyeIcon from "@/assets/images/icons/figma/eye.svg";
import editIcon from "@/assets/images/icons/figma/edit.svg";
import wasteIcon from "@/assets/images/icons/figma/waste.svg";
import AddNewClientMobile from "./AddNewClientMobile.vue";

const { width: windowWidth } = useWindowSize();

const clientsStores = useClientsStores();
const suppliersStores = useSuppliersStores();
const emitter = inject("emitter");

const suppliers = ref([]);
const client_types = ref([])
const clients = ref([]);
const searchQuery = ref("");
const rowPerPage = ref(10);
const currentPage = ref(1);
const totalPages = ref(1);
const totalClients = ref(0);
const isRequestOngoing = ref(true);
const isAddNewClientDrawerVisible = ref(false);
const isConfirmDeleteDialogVisible = ref(false);
const isItemsPendingDialogVisible = ref(false)
const isPendingDocumentsDialogVisible = ref(false)
const isMobile = ref(false);
const isPendingDocumentsMobileDialogVisible = ref(false)
const selectedClient = ref({});
const pendingItems = ref({
  pending_invoices: [],
  open_agreements: [],
});
const isDialogOpen = ref(false);
const addNewClientDrawerRef = ref(null);
const addNewClientMobileRef = ref(null);
const hasLoaded = ref(false);
const isClientFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const isFilterDialogVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | null

let nextRoute = null;

const filtreraMobile = ref(false);
const supplier_id = ref(null);
const state_id = ref(null);
const userData = ref(null);
const role = ref(null);

const sectionEl = ref(null);

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);
const isDuplicate = ref(false)
const isEdit = ref(false)
const err = ref(null);
const openedClientFormSource = ref('drawer');
const lastEditedClientDraft = ref(null);

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = clients.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    clients.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalClients.value} resultat`;
  // return `Visar ${firstIndex} till ${lastIndex} av ${totalClients.value} register`;
});

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value;

  if (!isAddNewClientDrawerVisible.value && !isDialogOpen.value && !skapatsDialog.value) {
    selectedClient.value = {};
  }
});

onMounted(async () => {
  userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
  role.value = userData.value.roles[0].name;

  if (role.value === "SuperAdmin" || role.value === "Administrator") {
    await suppliersStores.fetchSuppliers({ limit: -1, state_id: 2 });
    suppliers.value = toRaw(suppliersStores.getSuppliers);
  }

  if (role.value !== "Supplier" && role.value !== "User") {
    await suppliersStores.fetchSuppliers({ limit: -1, state_id: 2 });
    suppliers.value = toRaw(suppliersStores.getSuppliers);
  }
});

watchEffect(fetchData);

async function fetchData(cleanFilters = false) {
  if (cleanFilters === true) {
    searchQuery.value = "";
    rowPerPage.value = 10;
    currentPage.value = 1;
    supplier_id.value = null;
    state_id.value = null;
  }

  let data = {
    search: searchQuery.value,
    orderByField: "id",
    orderBy: "desc",
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value,
    state_id: state_id.value
  };

  // Ensure loader is shown during fetch to prevent empty-state flicker
  isRequestOngoing.value = searchQuery.value !== "" ? false : true;
  isFilterDialogVisible.value = false;
  
  await clientsStores.fetchClients(data);

  clients.value = clientsStores.getClients;
  client_types.value = clientsStores.client_types;
  totalPages.value = clientsStores.last_page;
  totalClients.value = clientsStores.clientsTotalCount;
  
  hasLoaded.value = true;
  isRequestOngoing.value = false;
}

watchEffect(registerEvents);

function registerEvents() {
  emitter.on("cleanFilters", fetchData);
}

const updateStateId = (newStateId) => {
  // Si ya está seleccionado, desmarcarlo (poner null)
  if (state_id.value === newStateId) {
    newStateId = null;
  }
  console.log("Updating state_id to:", newStateId);
  state_id.value = newStateId;
  filtreraMobile.value = false;
};

// Guard route changes if client creation form has unsaved changes
onBeforeRouteLeave((to, from, next) => {
  if (isClientFormEdited.value && (isAddNewClientDrawerVisible.value || isDialogOpen.value)) {
    isConfirmLeaveVisible.value = true;
    nextRoute = next;
    leaveContext.value = 'route';
  } else {
    next();
  }
});

const onClientFormEdited = (val) => {
  isClientFormEdited.value = !!val;
};

// Intercept mobile dialog outside-click close
const handleMobileDialogUpdate = (val) => {
  if (val === false && isClientFormEdited.value) {
    // keep dialog open and show confirm
    isDialogOpen.value = true;
    isConfirmLeaveVisible.value = true;
    leaveContext.value = 'mobile';
    return;
  }
  isDialogOpen.value = val;
};

const confirmLeave = () => {
  isConfirmLeaveVisible.value = false;
  if (leaveContext.value === 'route' && nextRoute) {
    isAddNewClientDrawerVisible.value = false;
    isDialogOpen.value = false;
    isClientFormEdited.value = false;
    const go = nextRoute;
    nextRoute = null;
    leaveContext.value = null;
    go();
    return;
  }
  if (leaveContext.value === 'mobile') {
    isDialogOpen.value = false;
    isClientFormEdited.value = false;
  }
  leaveContext.value = null;
};

const cancelLeave = () => {
  isConfirmLeaveVisible.value = false;
  if (leaveContext.value === 'route' && nextRoute) {
    nextRoute(false);
    nextRoute = null;
  }
  leaveContext.value = null;
};

const editClient = (clientData) => {
  isAddNewClientDrawerVisible.value = true;
  selectedClient.value = { ...clientData };
};

const editClientMobile = (clientData) => {
  isDialogOpen.value = true;
  selectedClient.value = { ...clientData };
};

const openAddNewClientDrawerMobile = () => {
  isDialogOpen.value = true;
  selectedClient.value = {};
};

const showDeleteDialog = (clientData, mobile = false) => {
  isMobile.value = mobile;
  isConfirmDeleteDialogVisible.value = true;
  selectedClient.value = { ...clientData };
};

const seeClient = (clientData) => {
  router.push({
    name: "dashboard-admin-clients-id",
    params: { id: clientData.id },
  });
};

const removeClient = async () => {

  isConfirmDeleteDialogVisible.value = false;

  clientsStores.deleteClient(selectedClient.value.id)
    .then((res) => {
        if (res.data.success) {
            selectedClient.value = {};

            advisor.value = {
              type: res.data.success ? "success" : "error",
              message: res.data.success ? "Kunden har tagits bort!" : res.data.message,
              show: true,
            };

            fetchData()
        }
        isRequestOngoing.value = false
    })
    .catch((err) => {


      if(err.feedback === 'client_has_open_items_pending') {
        isItemsPendingDialogVisible.value = true
      }

      advisor.value = {
        type: 'error',
        message: err.message,
        show: true,
      }

      isRequestOngoing.value = false
    })

  setTimeout(() => {
      advisor.value = {
          type: '',
          message: '',
          show: false
      }
  }, 3000)
};

const showDocuments = (clientData) => {
  isRequestOngoing.value = true;

  clientsStores.fetchPendingItems(clientData.id)
    .then((res) => {
      pendingItems.value = {
        pending_invoices: res.data?.data?.pending_invoices ?? [],
        open_agreements: res.data?.data?.open_agreements ?? [],
      };

      isItemsPendingDialogVisible.value = false;

      if(isMobile.value) {
        isPendingDocumentsMobileDialogVisible.value = true;
        return;
      }
      isPendingDocumentsDialogVisible.value = true;
    })
    .catch((err) => {
      advisor.value = {
        type: "error",
        message: err?.response?.data?.message ?? err.message,
        show: true,
      };
    })
    .finally(() => {
      isRequestOngoing.value = false;
    });
};

const showLoading = function (value) {
  isRequestOngoing.value = value;
};

const showAlert = function (alert) {
  advisor.value.show = alert.value.show;
  advisor.value.type = alert.value.type;
  advisor.value.message = alert.value.message;
};

const seeDocument = (agreement) => {
  router.push(`/dashboard/admin/agreements?file_id=${agreement.id}`)
};

const seeBilling = (invoice) => {
  router.push({
    name: "dashboard-admin-billings-id",
    params: { id: invoice.id },
  });
};

const resolveStatus = state => {
  if (state === 'created')
    return { 
      name: 'Skapad',
      class: 'info',
      icon: 'custom-star'
    }
  if (state === 'sent')
    return { 
      name: 'Skickad',
      class: 'success',
      icon: 'custom-forward'
    }
  if (state === 'signed')
    return { 
      name: 'Signerad',
      class: 'success',
      icon: 'custom-signature'
    }
  if (state === 'pending')
    return { 
      name: 'Skapad',
      class: 'info',
      icon: 'custom-star'
    }
  if (state === 'delivered')
    return { 
      name: 'Levererad',
      class: 'success',
      icon: 'custom-check-mark-2'
    }
  if (state === 'reviewed')
    return { 
      name: 'Granskad',
      class: 'info',
      icon: 'custom-eye'
    }
  if (state === 'delivery_issues')
    return { 
      name: 'Leveransproblem',
      class: 'pending',
      icon: 'custom-risk'
    }
  if (state === 'failed')
    return { 
      name: 'Misslyckades',
      class: 'error',
      icon: 'custom-close'
    }
}

const normalizeFormDataValue = (key, value) => {
  if (value === 'null' || value === 'undefined') return null

  if (key.endsWith('_id')) {
    if (value === '') return null
    const numericValue = Number(value)
    return Number.isNaN(numericValue) ? value : numericValue
  }

  return value
}

const buildClientDraftFromFormData = (formData) => {
  const draft = {}
  for (const [key, value] of formData.entries()) {
    draft[key] = normalizeFormDataValue(key, value)
  }
  return draft
}

const submitForm = async (client, method) => {
  isRequestOngoing.value = true;
  isDuplicate.value = false;
  openedClientFormSource.value = isDialogOpen.value ? 'mobile' : 'drawer';

  if (method === "update") {
    lastEditedClientDraft.value = {
      ...selectedClient.value,
      ...buildClientDraftFromFormData(client.data),
      id: client.id,
    };
    client.data.append("_method", "PUT");
    submitUpdate(client);
    return;
  }

  lastEditedClientDraft.value = null;
  submitCreate(client.data);
};

const submitCreate = (clientData) => {
  clientsStores
    .addClient(clientData)
    .then(async (res) => {
      if (res.data.success) {
        isEdit.value = false
        
        skapatsDialog.value = true;
        addNewClientDrawerRef.value?.reallyCloseAndReset?.();
        addNewClientMobileRef.value?.reallyCloseAndReset?.();

        await fetchData();
        
      }
      isRequestOngoing.value = false;
    })
    .catch((error) => {
      err.value = error;
      isDuplicate.value = error.message === 'organization_number.unique' ? true : false
      inteSkapatsDialog.value = true;
      isRequestOngoing.value = false
    });
};

const submitUpdate = (clientData) => {
  clientsStores
    .updateClient(clientData)
    .then(async (res) => {
      if (res.data.success) {

        skapatsDialog.value = true;
        isEdit.value = true
        addNewClientDrawerRef.value?.reallyCloseAndReset?.();
        addNewClientMobileRef.value?.reallyCloseAndReset?.();

        await fetchData();

      }
      isRequestOngoing.value = false;
    })
    .catch((error) => {
      err.value = error;
      isDuplicate.value = error.message === 'organization_number.unique' ? true : false
      inteSkapatsDialog.value = true;
      isRequestOngoing.value = false
    })
};

const closeDialog = () => {
  skapatsDialog.value = false;

  if (isEdit.value) {
    if (lastEditedClientDraft.value) {
      selectedClient.value = { ...lastEditedClientDraft.value }
    }
    if (openedClientFormSource.value === 'mobile') {
      isDialogOpen.value = true
    } else {
      isAddNewClientDrawerVisible.value = true
    }
    return
  }

  selectedClient.value = {}
  if (openedClientFormSource.value === 'mobile') {
    isDialogOpen.value = true
  } else {
    isAddNewClientDrawerVisible.value = true
  }
}

const showError = () => {
    inteSkapatsDialog.value = false;

    advisor.value.show = isDuplicate.value ? false : true;
    advisor.value.type = "error";
    const responseData = err.value?.response?.data;
    
    if (responseData?.message) {
      advisor.value.message = responseData.message;
    } else if (responseData?.errors) {
      advisor.value.message = Object.values(responseData.errors)
                .flat()
                .join("<br>");
    } else if (err.value?.message) {
      advisor.value.message = err.value.message;
    } else {
      advisor.value.message = "Ett serverfel uppstod. Försök igen.";
    }

    setTimeout(() => {
      advisor.value.show = false;
      advisor.value.type = "";
      advisor.value.message = "";
    }, 3000);

};

const downloadCSV = async () => {
  isRequestOngoing.value = true;

  let data = { limit: -1 };

  await clientsStores.fetchClients(data);

  let dataArray = [];

  clientsStores.getClients.forEach((element) => {
    let data = {
      ID: element.order_id,
      KONTAKT: element.fullname,
      E_POST: element.email,
      ORGANISATIONSNUMMER: element.organization_number ?? "",
    };

    dataArray.push(data);
  });

  excelParser().exportDataFromJSON(dataArray, "clients", "csv");

  isRequestOngoing.value = false;
};

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + "...";
  }
  return text;
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
          <h2>Kunder <span v-if="hasLoaded">({{ clients.length }})</span></h2>
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
            v-if="$can('create', 'clients') && windowWidth >= 1024"
            class="btn-gradient"
            block
            @click="isAddNewClientDrawerVisible = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny kund
          </VBtn>
          <VBtn
            v-if="windowWidth < 1024 && $can('create', 'clients')"
            class="btn-gradient"
            block
            @click="openAddNewClientDrawerMobile"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny kund
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.mdAndDown ? 'p-6' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
        
        <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-2'">
          <AppAutocomplete
            v-if="role !== 'Supplier' && role !== 'User' && hasLoaded"
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
        </div>

        <VBtn
          class="btn-white-2 px-3"
          @click="isFilterDialogVisible = true"
          :class="windowWidth > 1023 ? 'd-none' : 'd-flex'"
          v-if="role !== 'Supplier' && role !== 'User' && hasLoaded"
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
            <VListItem @click="updateStateId(1)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 1"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Inaktiv</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(2)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 2"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Aktiv</VListItemTitle>
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

      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="clients.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col">#ID</th>
            <th scope="col">Kontakt</th>
            <th scope="col" class="text-center">Organisationsnummer</th>
            <th scope="col" class="text-center">Telefon</th>
            <th scope="col" class="text-center">Adress</th>
            <th scope="col" v-if="role !== 'Supplier' && role !== 'User'">Leverantör</th>
            <th scope="col">Skapad av</th>
            <th scope="col" v-if="$can('edit', 'clients') || $can('delete', 'clients')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody v-show="clients.length">
          <tr v-for="client in clients" :key="client.id">
            <td>
              <span>{{ client.order_id }}</span>
            </td>
            <td>
              <div
                class="d-flex justify-between align-center font-weight-medium cursor-pointer text-aqua"
                @click="seeClient(client)"
              >
                <span class="flex-grow break-words">
                  {{ client.fullname }}
                </span>

                <VIcon
                  class="flex-shrink-0"
                  icon="custom-arrow-right"
                  size="22"
                />
              </div>
            </td>
            <td class="text-center">
              <span class="" v-if="client.organization_number">
                {{ client.organization_number ?? "" }}
              </span>
            </td>
            <td class="text-center">
              <span class="">
                {{ client.phone ?? "" }}
              </span>
            </td>
            <td class="text-center">
              <VTooltip 
                v-if="client.address && client.address.length > 20"
                location="bottom"
                max-width="300">
                <template #activator="{ props }">
                  <span v-bind="props" class="cursor-pointer">
                    {{ truncateText(client.address, 20) }}
                  </span>
                </template>
                <span>{{ client.address }}</span>
              </VTooltip>
              <span v-else>{{ client.address }}</span>
            </td>
            <td class="text-wrap" v-if="role === 'SuperAdmin' || role === 'Administrator'">
              <span v-if="client.supplier">
                {{ client.supplier.user.name }}
                {{ client.supplier.user.last_name ?? "" }}
              </span>
            </td>
            <td style="width: 1%; white-space: nowrap">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  :variant="client.user.avatar ? 'outlined' : 'tonal'"
                  size="38"
                >
                  <VImg
                    v-if="client.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + client.user.avatar"
                  />
                  <span v-else>{{ avatarText(client.user.name) }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ client.user.name }} {{ client.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip 
                      v-if="client.user.email && client.user.email.length > 20"
                      location="bottom">
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          {{ truncateText(client.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ client.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ client.user.email }}</span>
                  </span>
                </div>
              </div>
            </td>
            <!-- 👉 Actions -->
            <td
              class="text-center"
              style="width: 3rem"
              v-if="$can('edit', 'clients') || $can('delete', 'clients')"
            >
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem @click="seeClient(client)">
                    <template #prepend>
                      <img :src="eyeIcon" alt="See Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Se detaljer</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'clients') && client.deleted_at === null"
                    @click="editClient(client)"
                  >
                    <template #prepend>
                      <img :src="editIcon" alt="Edit Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('delete', 'clients') && client.deleted_at === null"
                    @click="showDeleteDialog(client, false)"
                  >
                    <template #prepend>
                      <img :src="wasteIcon" alt="Delete Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Ta bort</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </td>
          </tr>
        </tbody>
        <!-- 👉 table footer  -->
      </VTable>
      
      <div
        v-if="!isRequestOngoing && hasLoaded && !clients.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-user"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Du har inga kunder än</div>
          <div class="empty-state-text">
            Lägg till dina kunder här för att snabbt skapa fakturor och hålla
            ordning på dina kontakter.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'clients') && !$vuetify.display.mdAndDown"
          @click="isAddNewClientDrawerVisible = true"
        >
          Lägg till ny kund
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'clients')"
          @click="isDialogOpen = true"
        >
          Lägg till ny kund
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="clients.length && $vuetify.display.mdAndDown"
      >
        <VExpansionPanel v-for="client in clients" :key="client.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <span class="order-id">{{ client.order_id }}</span>
            <div class="order-title-box">
              <span class="title-panel">{{ client.fullname }}</span>
              <div class="title-organization">
                Org.Nr.
                <div class="text-black">{{ client.organization_number }}</div>
              </div>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Adress:</div>
              <div class="expansion-panel-item-value">
                {{ client.address ?? "" }}
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Telefon:</div>
              <div class="expansion-panel-item-value">
                {{ client.phone ?? "" }}
              </div>
            </div>
            <div class="mb-4 row-with-buttons" v-if="client.deleted_at === null">
              <VBtn
                v-if="$can('edit', 'clients')"
                class="btn-light"
                @click="editClientMobile(client)"
              >
                <VIcon icon="custom-pencil" size="24" />
                Redigera
              </VBtn>
              <VBtn
                v-if="$can('delete', 'clients')"
                class="btn-light"
                @click="showDeleteDialog(client, true)"
              >
                <VIcon icon="custom-waste" size="24" />
                Ta bort
              </VBtn>
            </div>
            <div class="mb-4 row-with-buttons" v-if="client.deleted_at !== null">
              <VBtn
                v-if="$can('delete', 'clients')"
                class="btn-light"
                @click="seeClient(client)"
              >
                <VIcon icon="custom-eye" size="24" />
                 Se detaljer
              </VBtn>
            </div>
            <div v-if="client.deleted_at === null">
              <VBtn class="btn-light w-100" @click="seeClient(client)">
                <VIcon icon="custom-eye" size="24" />
                Se detaljer
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="clients.length"
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
          :total-visible="4"
          :length="totalPages"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>
    </VCard>

    <!-- 👉 Add New Client -->
    <AddNewClientDrawer
      ref="addNewClientDrawerRef"
      v-model:isDrawerOpen="isAddNewClientDrawerVisible"
      :client="selectedClient"
      :suppliers="suppliers"
      :client_types="client_types"
      :is-duplicate="isDuplicate"
      @client-data="submitForm"
      @reset-duplicate="isDuplicate = false"
      @edited="onClientFormEdited"
      @alert="showAlert"
      @loading="showLoading"
    />

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->

      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filled-waste" class="action-icon" />
          <div class="dialog-title">
            Är du säker på att du vill radera kunden?
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Du är på väg att permanent radera "{{ selectedClient.fullname }}". All
          associerad data kommer att försvinna och åtgärden kan inte ångras.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmDeleteDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeClient"> Ja, radera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
    
    <!-- Confirm leave without saving (parent-level for mobile outside-click and route change) -->
    <VDialog
      v-model="isConfirmLeaveVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="cancelLeave"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
        <VCardText class="dialog-title-box">
          <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
          <div class="dialog-title">Avsluta utan att spara?</div>
        </VCardText>
        <VCardText class="dialog-text">
          <strong>Du har osparade ändringar.</strong> Om du lämnar den här vyn nu kommer informationen du har angett inte att sparas.
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="cancelLeave">Avbryt</VBtn>
          <VBtn class="btn-gradient" @click="confirmLeave">Ja, fortsätt</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Add New Client Mobile Dialog -->
    <VDialog
      v-model="isDialogOpen"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
      @update:model-value="handleMobileDialogUpdate"
    >
      <VCard>
        <AddNewClientMobile
          ref="addNewClientMobileRef"
          v-model:isDrawerOpen="isDialogOpen"
          :client="selectedClient"
          :suppliers="suppliers"
          :client_types="client_types"
          :is-duplicate="isDuplicate"
          @client-data="submitForm"
          @reset-duplicate="isDuplicate = false"
          @edited="onClientFormEdited"
          @alert="showAlert"
          @loading="showLoading"
        />
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
            class="selector-user"
            :menu-props="{ maxHeight: '400px' }"
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

    <!-- 👉 Items pending -->
    <VDialog
      v-model="isItemsPendingDialogVisible"
      persistent
      class="action-dialog dialog-big-icon"
    >
      
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isItemsPendingDialogVisible = !isItemsPendingDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-info" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title text-center">Kunden kan inte raderas!</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Du kan inte radera kunden eftersom det finns pågående avtal och/eller fakturor kopplade till kunden.
          <br>
          Alla dokument som inte är slutförda måste först färdigställas, skickas, signeras eller tas bort innan kunden kan raderas.
        </VCardText>
        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn 
            class="btn-light"  
            @click="isItemsPendingDialogVisible = false"
          >
            Avbryt
          </VBtn>
          <VBtn 
            class="btn-gradient" 
            @click="showDocuments(selectedClient)"
          >
            Visa pågående dokument
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Pending documents list -->
    <VDialog
      v-model="isPendingDocumentsDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isPendingDocumentsDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
          <div class="dialog-title">Pågående dokument</div>
        </VCardText>

        <VCardText v-if="pendingItems.open_agreements.length > 0" class="dialog-text font-weight-bold">Pågående avtal</VCardText>
        <VCardText v-if="pendingItems.open_agreements.length > 0" class="pt-2">
          <template 
           v-for="agreement in pendingItems.open_agreements"
            :key="`agreement-${agreement.id}`"
          >
            <div class="d-flex justify-between text-neutral-3">
              <div class="d-flex flex-column">
                <span class="font-weight-medium">{{ agreement.agreement_type.name }}</span>
                <span>
                  {{ agreement.agreement_type_id === 4 ?
                    `Offer nr: ${agreement.offer.offer_id}` : 
                    ( agreement.agreement_type_id === 3 ? 
                      `Avtalsnummer: ${agreement.commission.commission_id}` : 
                      `Avtalsnummer: ${agreement.agreement_id}`
                    )                    
                  }}
                </span>
                <span>
                  Datum: {{ formatDateYMD(agreement.created_at) }}
                </span>
              </div>
              <div class="d-flex gap-2 justify-center align-center">
                <VSpacer :class="windowWidth < 1024 ? 'd-flex' : 'd-none'" />
                <div
                  v-if="agreement.token"
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(agreement.token?.signature_status)?.class}`"
                >
                  <VIcon size="16" :icon="resolveStatus(agreement.token?.signature_status)?.icon" class="action-icon" />
                  {{ resolveStatus(agreement.token?.signature_status)?.name }}
                </div>
                <VBtn 
                  class="btn-ghost px-2" 
                  style="height: 32px !important;"
                  @click="seeDocument(agreement)">
                  <VIcon icon="custom-eye" size="24" />                
                </VBtn>
              </div>
            </div>
            <VDivider />
          </template>
        </VCardText>

        <VCardText v-if="pendingItems.pending_invoices.length > 0" class="dialog-text font-weight-bold">Fakturor att stänga</VCardText>
        <VCardText v-if="pendingItems.pending_invoices.length > 0" class="pt-2">
          <template 
            v-for="invoice in pendingItems.pending_invoices"
            :key="`invoice-${invoice.id}`"
          >
            <div class="d-flex justify-between text-neutral-3">
              <div class="d-flex flex-column">
                <span>
                  Faktura nr: {{ invoice.invoice_id}}
                </span>
                <span>
                  Förfallodatum: {{ formatDateYMD(invoice.invoice_date) }}
                </span>
                 <span>
                  Total: {{ formatNumber(invoice.total) }} kr
                </span>
              </div>
              <div class="d-flex gap-2 justify-center align-center">
                <VSpacer :class="windowWidth < 1024 ? 'd-flex' : 'd-none'" />
                <div
                  class="status-chip"
                  :class="`status-chip-${invoice.state_id === 4 ? 'pending' : 'error'}`"
                >
                  {{ invoice.state_id === 4 ? 'Obetald' : 'Förfallna' }}
                </div>
                <VBtn 
                  class="btn-ghost px-2" 
                  style="height: 32px !important;"
                  @click="seeBilling(invoice)">
                  <VIcon icon="custom-eye" size="24" />                
                </VBtn>
              </div>
            </div>
            <VDivider />
          </template>        
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isPendingDocumentsDialogVisible = false">
            Stäng
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Pending documents list mobile -->
    <VDialog
      v-model="isPendingDocumentsMobileDialogVisible"
      fullscreen
      persistent
      :scrim="false"
      transition="dialog-bottom-transition"
      class="action-dialog dialog-fullscreen"
      content-class="clients-pending-mobile-fullscreen">
      
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isPendingDocumentsMobileDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard class="h-100">
        <VCardText class="dialog-title-box mb-2 pb-0 flex-0">
          <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
          <div class="dialog-title">Pågående dokument</div>
        </VCardText>

        <VCardText 
          v-if="pendingItems.open_agreements.length > 0" 
          style="overflow-y: auto; overflow-x: hidden;"
          class="pt-4 pb-0 flex-none font-weight-bold">
          Pågående avtal
        </VCardText>
        <VCardText v-if="pendingItems.open_agreements.length > 0" class="pt-2">
          <template 
           v-for="agreement in pendingItems.open_agreements"
            :key="`agreement-${agreement.id}`"
          >
            <div class="d-flex justify-between text-neutral-3">
              <div class="d-flex flex-column">
                <span class="font-weight-medium">{{ agreement.agreement_type.name }}</span>
                <span>
                  {{ agreement.agreement_type_id === 4 ?
                    `Offer nr: ${agreement.offer.offer_id}` : 
                    ( agreement.agreement_type_id === 3 ? 
                      `Avtalsnummer: ${agreement.commission.commission_id}` : 
                      `Avtalsnummer: ${agreement.agreement_id}`
                    )                    
                  }}
                </span>
                <span>
                  Datum: {{ formatDateYMD(agreement.created_at) }}
                </span>
              </div>
              <div class="d-flex gap-2 justify-center align-center">
                <VSpacer :class="windowWidth < 1024 ? 'd-flex' : 'd-none'" />
                <div
                  v-if="agreement.token"
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(agreement.token?.signature_status)?.class}`"
                >
                  <VIcon size="16" :icon="resolveStatus(agreement.token?.signature_status)?.icon" class="action-icon" />
                  {{ resolveStatus(agreement.token?.signature_status)?.name }}
                </div>
                <VBtn 
                  class="btn-ghost px-2" 
                  style="height: 32px !important;"
                  @click="seeDocument(agreement)">
                  <VIcon icon="custom-eye" size="24" />                
                </VBtn>
              </div>
            </div>
            <VDivider />
          </template>
        </VCardText>

        <VCardText 
          v-if="pendingItems.pending_invoices.length > 0" 
          style="overflow-y: auto; overflow-x: hidden;"
          class="pt-4 pb-0 flex-none font-weight-bold">
          Fakturor att stänga
        </VCardText>
        <VCardText v-if="pendingItems.pending_invoices.length > 0" class="pt-2">
          <template 
            v-for="invoice in pendingItems.pending_invoices"
            :key="`invoice-${invoice.id}`"
          >
            <div class="d-flex justify-between text-neutral-3">
              <div class="d-flex flex-column">
                <span>
                  Faktura nr: {{ invoice.invoice_id}}
                </span>
                <span>
                  Förfallodatum: {{ formatDateYMD(invoice.invoice_date) }}
                </span>
                 <span>
                  Total: {{ formatNumber(invoice.total) }} kr
                </span>
              </div>
              <div class="d-flex gap-2 justify-center align-center">
                <VSpacer :class="windowWidth < 1024 ? 'd-flex' : 'd-none'" />
                <div
                  class="status-chip"
                  :class="`status-chip-pending`"
                >
                  Obetald
                </div>
                <VBtn 
                  class="btn-ghost px-2" 
                  style="height: 32px !important;"
                  @click="seeBilling(invoice)">
                  <VIcon icon="custom-eye" size="24" />                
                </VBtn>
              </div>
            </div>
            <VDivider />
          </template>        
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions flex-none mt-auto">
          <VBtn class="btn-light" @click="isPendingDocumentsMobileDialogVisible = false">
            Stäng
          </VBtn>
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
          <VListItem @click="updateStateId(1)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 1"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Inaktiv</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(2)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                 :model-value="state_id === 2"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Aktiv</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Dialogs Section -->

    <!-- 👉 Skapats Dialogs -->
    <VDialog
      v-model="skapatsDialog"
      persistent
      class="action-dialog dialog-big-icon"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="skapatsDialog = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-user" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title">
            {{ isEdit ? 'Kunden har uppdaterats!' : 'Kunden har lagts till!' }}
          </div>
        </VCardText>
        <VCardText class="dialog-text text-center">
            {{ isEdit ? 'Din kund har uppdaterats och ändringarna har sparats i din kundlista.' : 'Din kund har skapats och finns nu i din kundlista.' }}
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-gradient" @click="closeDialog">
             {{ isEdit ? 'Redigera kund' : 'Skapa en ny kund' }}
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="inteSkapatsDialog"
      persistent
      class="action-dialog dialog-big-icon"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="inteSkapatsDialog = !inteSkapatsDialog"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-cancel" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title">
            {{ isDuplicate ? 'Kunden finns redan registrerad.' : 'Kunde inte skapa avtalet' }}
          </div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          {{ isDuplicate ? 
            'En kund med detta personnummer eller organisationsnummer finns redan i systemet. För att undvika dubbletter kan du inte skapa en ny kund med samma uppgifter.' : 
            'Ett fel inträffade. Kontrollera att alla obligatoriska fält är korrekt ifyllda och försök igen.'
          }}
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="showError">
            Stäng
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss" scoped>

  :deep(.clients-pending-mobile-fullscreen) {
    inset: 0 !important;
    margin: 0 !important;
    width: 100vw !important;
    height: 100dvh !important;
    max-width: 100vw !important;
    max-height: 100dvh !important;
    border-radius: 0 !important;
  }

  :deep(.clients-pending-mobile-fullscreen > .v-card) {
    width: 100%;
    height: 100%;
    max-height: 100%;
    border-radius: 0 !important;
    overflow-y: auto;
  }

  .bottom-sheet-card {
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-height: 75vh;
    overflow-y: auto;
  }
</style>
<route lang="yaml">
meta:
  action: view
  subject: clients
</route>

