<script setup>

import { toRaw } from "vue";
import { useDisplay } from "vuetify";
import { useSuppliersStores } from "@/stores/useSuppliers";
import { useClientsStores } from "@/stores/useClients";
import { excelParser } from "@/plugins/csv/excelParser";
import { themeConfig } from "@themeConfig";
import { avatarText } from "@/@core/utils/formatters";
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
const clients = ref([]);
const searchQuery = ref("");
const rowPerPage = ref(10);
const currentPage = ref(1);
const totalPages = ref(1);
const totalClients = ref(0);
const isRequestOngoing = ref(true);
const isAddNewClientDrawerVisible = ref(false);
const isConfirmDeleteDialogVisible = ref(false);
const selectedClient = ref({});
const isDialogOpen = ref(false);
const hasLoaded = ref(false);
const isClientFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const isFilterDialogVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | null

let nextRoute = null;

const supplier_id = ref(null);
const userData = ref(null);
const role = ref(null);

const sectionEl = ref(null);

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

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

  if (!isAddNewClientDrawerVisible.value) selectedClient.value = {};
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
  }

  let data = {
    search: searchQuery.value,
    orderByField: "id",
    orderBy: "desc",
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value,
  };

  // Ensure loader is shown during fetch to prevent empty-state flicker
  isRequestOngoing.value = searchQuery.value !== "" ? false : true;
  isFilterDialogVisible.value = false;
  
  await clientsStores.fetchClients(data);

  clients.value = clientsStores.getClients;
  totalPages.value = clientsStores.last_page;
  totalClients.value = clientsStores.clientsTotalCount;
  
  hasLoaded.value = true;
  isRequestOngoing.value = false;
}

watchEffect(registerEvents);

function registerEvents() {
  emitter.on("cleanFilters", fetchData);
}

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

const showDeleteDialog = (clientData) => {
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
  let res = await clientsStores.deleteClient(selectedClient.value.id);
  selectedClient.value = {};

  advisor.value = {
    type: res.data.success ? "success" : "error",
    message: res.data.success ? "Kunden har tagits bort!" : res.data.message,
    show: true,
  };

  await fetchData();

  setTimeout(() => {
    advisor.value = {
      type: "",
      message: "",
      show: false,
    };
  }, 3000);

  return true;
};

const submitForm = async (client, method) => {
  isRequestOngoing.value = true;

  if (method === "update") {
    client.data.append("_method", "PUT");
    submitUpdate(client);
    return;
  }

  submitCreate(client.data);
};

const submitCreate = (clientData) => {
  clientsStores
    .addClient(clientData)
    .then((res) => {
      if (res.data.success) {
        advisor.value = {
          type: "success",
          message: "Kunden har lagts till!",
          show: true,
        };
        fetchData();
      }
      isRequestOngoing.value = false;
    })
    .catch((err) => {
      advisor.value = {
        type: "error",
        message: err.message,
        show: true,
      };
      isRequestOngoing.value = false;
    });

  setTimeout(() => {
    advisor.value = {
      type: "",
      message: "",
      show: false,
    };
  }, 3000);
};

const submitUpdate = (clientData) => {
  clientsStores
    .updateClient(clientData)
    .then((res) => {
      if (res.data.success) {
        advisor.value = {
          type: "success",
          message: "Ändringarna har sparats!",
          show: true,
        };
        fetchData();
      }
      isRequestOngoing.value = false;
    })
    .catch((err) => {
      advisor.value = {
        type: "error",
        message: err.message,
        show: true,
      };
      isRequestOngoing.value = false;
    });

  setTimeout(() => {
    advisor.value = {
      type: "",
      message: "",
      show: false,
    };
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
        class="d-flex align-center justify-space-between gap-2"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
        
        <VBtn 
          class="btn-white-2" 
          v-if="role !== 'Supplier' && role !== 'User'"
          @click="isFilterDialogVisible = true"
        >
          <VIcon icon="custom-filter" size="24" />
          <span :class="windowWidth < 1024 ? 'd-none' : 'd-flex'">Filtrera efter</span>
        </VBtn>

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
              <span class="">
                <VTooltip location="bottom" v-if="client.address && client.address.length > 20">
                  <template #activator="{ props }">
                    <span v-bind="props">
                      {{ truncateText(client.address, 20) }}
                    </span>
                  </template>
                  <span>{{ client.address }}</span>
                </VTooltip>
                <span v-else>{{ client.address }}</span>
              </span>
            </td>
            <td style="width: 1%; white-space: nowrap" v-if="role !== 'Supplier' && role !== 'User'">
              <div class="d-flex align-center gap-x-1" v-if="client.supplier">
                <VAvatar
                  :variant="client.supplier.user.avatar ? 'outlined' : 'tonal'"
                  size="38"
                >
                  <VImg
                    v-if="client.supplier.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + client.supplier.user.avatar"
                  />
                  <span v-else>{{
                    avatarText(client.supplier.user.name)
                  }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ client.supplier.user.name }} {{ client.supplier.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip location="bottom" v-if="client.supplier.user.email && client.supplier.user.email.length > 20">
                      <template #activator="{ props }">
                        <span v-bind="props">
                          {{ truncateText(client.supplier.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ client.supplier.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ client.supplier.user.email }}</span>
                  </span>
                </div>
              </div>
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
                    <VTooltip location="bottom" v-if="client.user.email && client.user.email.length > 20">
                      <template #activator="{ props }">
                        <span v-bind="props">
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
            <!-- 👉 Acciones -->
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
                    v-if="$can('edit', 'clients')"
                    @click="editClient(client)"
                  >
                    <template #prepend>
                      <img :src="editIcon" alt="Edit Icon" class="mr-2" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('delete', 'clients')"
                    @click="showDeleteDialog(client)"
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
              <div class="expansion-panel-item-label">Organisationsnummer:</div>
              <div class="expansion-panel-item-value">
                {{ client.organization_number ?? "" }}
              </div>
            </div>
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
            <div class="mb-4 row-with-buttons">
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
                @click="showDeleteDialog(client)"
              >
                <VIcon icon="custom-waste" size="24" />
                Ta bort
              </VBtn>
            </div>
            <div>
              <VBtn class="btn-light w-100" @click="seeClient(client)">
                <VIcon icon="custom-eye" size="24" />
                Se detaljer
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
        <div v-if="!clients.length" class="text-center py-4">
          Uppgifter ej tillgängliga
        </div>
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
          :total-visible="5"
          :length="totalPages"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>
    </VCard>

    <!-- 👉 Add New Client -->
    <AddNewClientDrawer
      v-model:isDrawerOpen="isAddNewClientDrawerVisible"
      :client="selectedClient"
      :suppliers="suppliers"
      @client-data="submitForm"
      @edited="onClientFormEdited"
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
          <!-- Är du säker att du vill ta bort klienten
          <strong>{{ selectedClient.fullname }}</strong
          >? -->
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
          v-model:isDrawerOpen="isDialogOpen"
          :client="selectedClient"
          :suppliers="suppliers"
          @client-data="submitForm"
          @edited="onClientFormEdited"
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
          <VAutocomplete
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
  </section>
</template>

<style lang="scss" scoped>
  .dialog-bottom-full-width {
    .v-card {
      border-radius: 24px 24px 0 0 !important;
    }
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

