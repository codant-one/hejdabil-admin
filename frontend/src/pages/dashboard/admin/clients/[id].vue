<script setup>
import { themeConfig } from "@themeConfig";
import { useClientsStores } from "@/stores/useClients";
import { format, parseISO } from "date-fns";
import { bg, es } from "date-fns/locale";
import { useClipboard } from "@vueuse/core";

import Toaster from "@/components/common/Toaster.vue";
import CustomerBioPanel from "@/views/apps/ecommerce/customer/view/CustomerBioPanel.vue";
import CustomerBillingTable from "@/views/apps/ecommerce/customer/view/CustomerBillingTable.vue";

import bestallningarIcon from "@/assets/images/icons/figma/bestallningar.svg";
import forsaljningIcon from "@/assets/images/icons/figma/forsaljning.svg";
import fakturorIcon from "@/assets/images/icons/figma/fakturor.svg";
import receiptDeclinedIcon from "@/assets/images/icons/figma/receiptDeclined.svg";
import deleteReceiptIcon from "@/assets/images/icons/figma/deleteReceipt.svg";

const route = useRoute();
const clientsStores = useClientsStores();

const client = ref(null);
const online = ref(null);

const isRequestOngoing = ref(true);

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const cardItems = [
  {
    title: "Beställningar",
    value: "238",
    icon: bestallningarIcon,
    bgCustomColor: "bg-netto",
  },
  {
    title: "Total försäljning",
    value: "120.000 Kr",
    icon: forsaljningIcon,
    bgCustomColor: "bg-moms",
  },
  {
    title: "Totalt i fakturor",
    value: "120.000 Kr",
    icon: fakturorIcon,
    bgCustomColor: "bg-summa",
  },
  {
    title: "Utestående fakturor",
    value: "120.000 Kr",
    icon: receiptDeclinedIcon,
    bgCustomColor: "bg-fakturor",
  },
  {
    title: "Förfallna fakturor",
    value: "120.000 Kr",
    icon: deleteReceiptIcon,
    bgCustomColor: "bg-forfallna",
  },
];

watchEffect(fetchData);

async function fetchData() {
  isRequestOngoing.value = true;

  if (Number(route.params.id) && route.name === "dashboard-admin-clients-id") {
    client.value = await clientsStores.showClient(Number(route.params.id));
  }

  isRequestOngoing.value = false;
}

const showAlert = function (alert) {
  advisor.value.show = alert.value.show;
  advisor.value.type = alert.value.type;
  advisor.value.message = alert.value.message;
};

const showLoading = function (value) {
  isRequestOngoing.value = value;
};

const update = (clientData) => {
  isRequestOngoing.value = true;

  clientsStores
    .updateClient(clientData, false)
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
</script>

<template>
  <VCard title="">
    <VCardText class="d-flex flex-column pa-4 gap-4">
      <CustomerBioPanel
        :customer-data="client"
        :is-supplier="false"
        @update="update"
      />
      <div class="w-100 d-block d-md-none">Svep för att se mer</div>
      <div class="d-flex gap-4 billing-items">
        <div
          v-for="item in cardItems"
          class="billing-item"
          :class="item.bgCustomColor"
        >
          <img :src="item.icon" alt="icon" style="width: 24px; height: 24px" />
          <div class="billing-item-title">{{ item.title }}</div>
          <div class="billing-item-text">{{ item.value }}</div>
        </div>
      </div>
      <CustomerBillingTable
        :client_id="Number(route.params.id)"
        @alert="showAlert"
        @loading="showLoading"
      />
    </VCardText>
  </VCard>
  <div>
    <VRow>
      <VDialog v-model="isRequestOngoing" width="auto" persistent>
        <VProgressCircular indeterminate color="primary" class="mb-0" />
      </VDialog>

      <VCol cols="12">
        <VAlert v-if="advisor.show" :type="advisor.type" class="mb-6">
          {{ advisor.message }}
        </VAlert>
        <Toaster />
      </VCol>
    </VRow>

    <!-- 👉 Header  -->
    <div
      v-if="client"
      class="-block d-md-flex justify-space-between align-center flex-wrap gap-y-4 mb-6"
    >
      <div>
        <div>
          <span class="text-body-1" v-if="online">
            {{
              format(parseISO(online), "MMMM d, yyyy, H:mm", {
                locale: es,
              }).replace(/(^|\s)\S/g, (char) => char.toUpperCase())
            }}
            <span class="text-xs"> (Sista anslutningen) </span>
          </span>
        </div>
      </div>
      <!-- <div class="d-flex gap-4">
        <VBtn
          variant="tonal"
          color="secondary"
          class="mb-2 w-100 w-md-auto"
          :to="{ name: 'dashboard-admin-clients' }"
        >
          Tillbaka
        </VBtn>
      </div> -->
    </div>
  </div>
</template>

<route lang="yaml">
meta:
  action: view
  subject: clients
</route>

<style lang="scss" scoped>
.billing-items {
  overflow: auto;
}
.billing-item {
  flex: 1 1;
  border-radius: 8px;
  padding: 16px;

  @media (max-width: 768px) {
    min-width: 165px;
  }

  img {
    margin-bottom: 10px;    
  }

  .billing-item-title {
    margin-bottom: 5px;
    font-weight: 400;
    font-size: 14px;
    line-height: 100%;
    color: #0c5b27;
  }

  .billing-item-text {
    font-weight: 700;
    font-size: 20px;
    line-height: 100%;
    color: #04585D;
  }
}

.row-with-buttons {
  display: flex;
  gap: 16px;
  > * {
    flex: 1 1;
  }
}
</style>
