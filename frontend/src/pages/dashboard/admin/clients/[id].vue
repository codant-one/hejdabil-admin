<script setup>

import { useClientsStores } from "@/stores/useClients";
import { useDisplay } from "vuetify";
import { formatNumber } from '@/@core/utils/formatters'

import Toaster from "@/components/common/Toaster.vue";
import CustomerBioPanel from "@/views/apps/ecommerce/customer/view/CustomerBioPanel.vue";
import CustomerBillingTable from "@/views/apps/ecommerce/customer/view/CustomerBillingTable.vue";

import bestallningarIcon from "@/assets/images/icons/figma/bestallningar.svg";
import forsaljningIcon from "@/assets/images/icons/figma/forsaljning.svg";
import fakturorIcon from "@/assets/images/icons/figma/fakturor.svg";
import receiptDeclinedIcon from "@/assets/images/icons/figma/receiptDeclined.svg";
import deleteReceiptIcon from "@/assets/images/icons/figma/deleteReceipt.svg";

defineProps({
  id: [String, Number],
});

const route = useRoute();
const clientsStores = useClientsStores();

const client = ref(null);
const sectionEl = ref(null)

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const isRequestOngoing = ref(true);

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const cardItems = ref([
  {
    title: "Inköpta fordon",
    value: "238",
    icon: bestallningarIcon,
    bgCustomColor: "bg-netto",
  },
  {
    title: "Sålda fordon",
    value: "120.000 Kr",
    icon: forsaljningIcon,
    bgCustomColor: "bg-moms",
  },
  {
    title: "Totalt fakturor",
    value: "120.000 Kr",
    icon: fakturorIcon,
    bgCustomColor: "bg-summa",
  },
  {
    title: "Obetalda fakturor",
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
])

watchEffect(fetchData);

async function fetchData() {
  isRequestOngoing.value = true;

  if (Number(route.params.id) && route.name === "dashboard-admin-clients-id") {
    client.value = await clientsStores.showClient(Number(route.params.id));

    cardItems.value[0].value = client.value.carsPurchased;
    cardItems.value[1].value = formatNumber(client.value.carsForSale) + " Kr"
    cardItems.value[2].value = formatNumber(client.value.totalBilling) + " Kr"
    cardItems.value[3].value = formatNumber(client.value.totalPending) + " Kr"
    cardItems.value[4].value = formatNumber(client.value.totalExpired) + " Kr"
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

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value
  if (!el) return

  const rect = el.getBoundingClientRect()
  const remaining = Math.max(0, window.innerHeight - rect.top - 25)
  el.style.minHeight = `${remaining}px`
}

onMounted(() => {
  resizeSectionToRemainingViewport()
  window.addEventListener('resize', resizeSectionToRemainingViewport)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
})
</script>

<template>
  <section class="page-section" ref="sectionEl">
    <VDialog v-model="isRequestOngoing" width="auto" persistent>
      <VProgressCircular indeterminate color="primary" class="mb-0" />
    </VDialog>

    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
    >
      {{ advisor.message }}
    </VSnackbar> 
      
    <Toaster />

    <VCard class="client-slug card-fill">
      <VCardText
        class="d-flex flex-column pa-4 gap-4"
        :class="$vuetify.display.smAndDown ? 'pa-6 gap-6 pt-8' : ''"
      >
        <CustomerBioPanel
          v-if="client && id"
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
  </section>
</template>

<route lang="yaml">
meta:
  action: view
  subject: clients
</route>

<style lang="scss" scoped>

.page-section {
  display: flex;
  flex-direction: column;
}

.card-fill {
  flex: 1 1 auto;
  padding-bottom: 0;

  @media (max-width: 768px) {
    padding-bottom: 60px;
    border-radius: 0 !important;
  }
}

.client-slug {
  @media (max-width: 768px) {
    border-radius: 0px !important;
  }
}

.billing-items {
  overflow: auto;
}

.billing-items::-webkit-scrollbar {
  display: none;
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
