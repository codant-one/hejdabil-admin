<script setup>
import { toRaw } from "vue";
import { useSuppliersStores } from "@/stores/useSuppliers";
import { useClientsStores } from "@/stores/useClients";
import { excelParser } from "@/plugins/csv/excelParser";
import { themeConfig } from "@themeConfig";
import { avatarText, formatNumber } from "@/@core/utils/formatters";
import AddNewClientDrawer from "./AddNewClientDrawer.vue";
import router from "@/router";

import searchIcon from "@/assets/images/icons/figma/searchIcon.svg";
import eyeIcon from "@/assets/images/icons/figma/eye.svg";
import editIcon from "@/assets/images/icons/figma/edit.svg";
import wasteIcon from "@/assets/images/icons/figma/waste.svg";
import AddNewClientMobile from "./AddNewClientMobile.vue";

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

const supplier_id = ref(null);
const userData = ref(null);
const role = ref(null);

const sectionEl = ref(null);

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

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

  isRequestOngoing.value = searchQuery.value !== "" ? false : true;

  await clientsStores.fetchClients(data);

  clients.value = clientsStores.getClients;
  totalPages.value = clientsStores.last_page;
  totalClients.value = clientsStores.clientsTotalCount;

  isRequestOngoing.value = false;
}

watchEffect(registerEvents);

function registerEvents() {
  emitter.on("cleanFilters", fetchData);
}

const editClient = (clientData) => {
  isAddNewClientDrawerVisible.value = true;
  selectedClient.value = { ...clientData };
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
    message: res.data.success ? "Klient raderad!" : res.data.message,
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
          message: "Kund skapad! ",
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
          message: "Klienten uppdaterad!",
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
    <VDialog v-model="isRequestOngoing" width="auto" persistent>
      <VProgressCircular indeterminate color="primary" class="mb-0" />
    </VDialog>

    <VAlert v-if="advisor.show" :type="advisor.type" class="mb-6">
      {{ advisor.message }}
    </VAlert>

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex justify-space-between"
        :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
      >
        <div class="d-flex align-center w-100 w-md-auto font-blauer">
          <h2>Kunder</h2>
        </div>

        <div class="d-flex gap-4 title-action-buttons">
          <VBtn class="btn-light w-100 w-md-auto" @click="downloadCSV">
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

          <VBtn
            v-if="$can('create', 'clients') && !$vuetify.display.smAndDown"
            class="btn-gradient w-100 w-md-auto"
            @click="isAddNewClientDrawerVisible = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny kund
          </VBtn>

          <VBtn
            v-if="$vuetify.display.smAndDown && $can('create', 'clients')"
            class="btn-gradient w-100 w-md-auto"
            @click="isDialogOpen = true"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny kund
          </VBtn>
          <VDialog
            v-model="isDialogOpen"
            transition="dialog-bottom-transition"
            content-class="dialog-bottom-full-width"
          >
            <VCard>
              <AddNewClientMobile
                :client="selectedClient"
                :suppliers="suppliers"
                @client-data="handleClientData"
                @close="isDialogOpen = false"
              />
            </VCard>
          </VDialog>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.smAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center flex-wrap gap-4 filter-bar"
        :class="$vuetify.display.smAndDown ? 'pa-6' : 'pa-4'"
      >
        <!-- <div class="d-flex align-center flex-wrap gap-4"> -->
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
        <!-- </div> -->

        <VSpacer class="d-none d-md-block" />
        <VBtn class="btn-white-2">
          <VIcon icon="custom-filter" size="24" />
          <span class="d-none d-md-block">Filtrera efter</span>
        </VBtn>
        <div
          v-if="!$vuetify.display.smAndDown"
          class="d-flex align-center w-100 w-md-auto visa-select"
        >
          <span class="text-no-wrap pr-4">Visa:</span>
          <VSelect
            v-model="rowPerPage"
            class="w-100 custom-select-hover"
            :items="[10, 20, 30, 50]"
          />
        </div>
      </VCardText>

      <VTable
        v-if="!$vuetify.display.smAndDown"
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
            <th scope="col" v-if="role !== 'Supplier' && role !== 'User'">
              Leverantör
            </th>
            <th scope="col">Skapad av</th>
            <th
              scope="col"
              v-if="$can('edit', 'clients') || $can('delete', 'clients')"
            ></th>
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
                <VTooltip location="bottom">
                  <template #activator="{ props }">
                    <span v-bind="props" v-if="client.address">
                      {{ truncateText(client.address) }}
                    </span>
                  </template>
                  <span>{{ client.address }}</span>
                </VTooltip>
              </span>
            </td>
            <td class="text-wrap" v-if="role !== 'Supplier' && role !== 'User'">
              <div class="d-flex align-center gap-x-3" v-if="client.supplier">
                <VAvatar
                  :variant="client.supplier.user.avatar ? 'outlined' : 'tonal'"
                  size="38"
                >
                  <VImg
                    v-if="client.supplier.user.avatar"
                    style="border-radius: 50%"
                    :src="
                      themeConfig.settings.urlStorage +
                      client.supplier.user.avatar
                    "
                  />
                  <span v-else>{{
                    avatarText(client.supplier.user.name)
                  }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ client.supplier.user.name }}
                    {{ client.supplier.user.last_name ?? "" }}
                  </span>
                  <span class="">{{ client.supplier.user.email }}</span>
                </div>
              </div>
            </td>
            <td class="text-wrap">
              <div class="d-flex align-center gap-x-3">
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
                  <span class="text-sm text-disabled">{{
                    client.user.email
                  }}</span>
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
      <div v-if="!clients.length" class="empty-state">
        <VIcon size="120" icon="custom-f-user" />
        <div class="empty-state-title">Du har inga kunder än</div>
        <div class="empty-state-text">
          Lägg till dina kunder här för att snabbt skapa fakturor och hålla
          ordning på dina kontakter.
        </div>
        <VBtn class="btn-ghost">
          Lägg till ny kund
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>
      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="clients.length && $vuetify.display.smAndDown"
      >
        <VExpansionPanel v-for="client in clients" :key="client.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <span class="order-id">{{ client.order_id }}</span>
            <span class="title-panel">{{ client.fullname }}</span>
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
                @click="editClient(client)"
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
        class="d-block d-md-flex align-center flex-wrap gap-4 pt-0 px-6 pb-16"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer class="d-none d-md-block" />

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

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn class="btn-light" @click="isConfirmDeleteDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeClient"> Ja, radera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss" scoped>
.page-section {
  display: flex;
  flex-direction: column;
}

.card-fill {
  flex: 1 1 auto;
  padding-bottom: 32px;
}

.search {
  width: 100% !important;
  .v-field__input {
    background: url(~@/assets/images/icons/figma/searchIcon.svg) no-repeat left
      1rem center !important;
  }
}

@media (min-width: 991px) {
  .search {
    max-width: 36rem !important;
  }

  .card-fill {
    padding-bottom: 0;
  }
}

@media (max-width: 991px) {
  .card-fill {
    border-radius: 0 !important;
  }
}

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
