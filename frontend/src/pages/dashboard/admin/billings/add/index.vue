<script setup>

import { useDisplay } from "vuetify";
import { onBeforeRouteLeave } from "vue-router";
import { useAppAbility } from "@/plugins/casl/useAppAbility";
import { useAuthStores } from "@/stores/useAuth";
import { useBillingsStores } from "@/stores/useBillings";
import { useConfigsStores } from "@/stores/useConfigs";
import InvoiceEditable from "@/views/apps/invoice/InvoiceEditable.vue";
import router from "@/router";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const configsStores = useConfigsStores();
const authStores = useAuthStores();
const billingsStores = useBillingsStores();
const ability = useAppAbility();
const emitter = inject("emitter");

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const sectionEl = ref(null);

// 👉 Default Blank Data
const validate = ref();
const invoiceData = ref([]);
const band = ref(true);
const total = ref(0);
const isRequestOngoing = ref(true);
const invoice = ref([]);
const invoices = ref([]);
const suppliers = ref([]);
const clients = ref([]);
const invoice_id = ref(0);

const billing_id = ref(null);
const err = ref(null);
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);
const isConfirmLeaveVisible = ref(false);

const userData = ref(null);
const role = ref(null);
const company = ref([]);

const discount = ref(0);
const rabattApplied = ref(false);
const amount_discount = ref(0);

const seeDialogRemove = ref(false);
const selectedInvoice = ref({});

const initialInvoiceData = ref(null);
const allowNavigation = ref(false);
const nextRoute = ref(null);

const currentInvoiceData = computed(() => ({
  invoice: invoice.value,
  invoiceData: invoiceData.value,
  discount: discount.value,
}));

const isDirty = computed(() => {
  if (!initialInvoiceData.value) return false;
  try {
    return JSON.stringify(currentInvoiceData.value) !== JSON.stringify(initialInvoiceData.value);
  } catch (e) {
    return true;
  }
});

watchEffect(fetchData);

async function fetchData() {
  isRequestOngoing.value = true;

  let response = await billingsStores.all();

  clients.value = response.data.data.clients;
  suppliers.value = response.data.data.suppliers;
  invoices.value = response.data.data.invoices;
  invoice_id.value = response.data.data.invoice_id;

  userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
  role.value = userData.value.roles[0].name;

  const { user_data, userAbilities } = await authStores.me(userData.value);

  localStorage.setItem("userAbilities", JSON.stringify(userAbilities));

  ability.update(userAbilities);

  localStorage.setItem("user_data", JSON.stringify(user_data));

  if (role.value === "Supplier") {
    company.value = user_data.user_detail;
    company.value.email = user_data.email;
    company.value.billings = user_data.supplier.billings;
  } else if (role.value === "User") {
    company.value = user_data.supplier.boss.user.user_detail;
    company.value.email = user_data.supplier.boss.user.email;
    company.value.billings = user_data.supplier.boss.billings;
  } else {
    await configsStores.getFeature("company");
    await configsStores.getFeature("logo");

    company.value = configsStores.getFeaturedConfig("company");
    company.value.billings = response.data.data.billings;
    company.value.logo = configsStores.getFeaturedConfig("logo").logo;
  }

  var item = {};
  invoices.value.forEach((element) => {
    var value = "";
    switch (element.type_id) {
      case 1:
        value = "";
        break;
      case 2:
        value = 1;
        break;
      case 3:
        value = "0.00";
        break;
    }
    item[parseInt(element.id)] = value;
  });

  item[5] = 0;
  item[6] = false;

  invoiceData.value?.push(item);

  isRequestOngoing.value = false;
  
  // Capture initial state
  nextTick(() => {
    initialInvoiceData.value = JSON.parse(JSON.stringify(currentInvoiceData.value));
  });
}

const data = (data) => {
  invoice.value = data;
  invoiceData.value = data.details;
};

const addProduct = (value) => {
  invoiceData.value?.push(value);
};

const removeProduct = (id) => {
  seeDialogRemove.value = true;
  selectedInvoice.value = { ...invoiceData.value[id] };
};

const deleteProduct = (id) => {
  if (id > 0) {
    invoiceData.value?.splice(id, 1);

    editProduct();
  }
};

const applyDiscount = (value) => {
  discount.value = value;

  if (value === 0) {
    invoiceData.value.forEach((element) => {
      if (element?.note === undefined) {
        element[6] = false;
      }
    });
  }

  editProduct();
};

const editProduct = () => {
  total.value = 0;
  amount_discount.value = 0;

  invoiceData.value.forEach((element) => {
    if (element?.note === undefined) {
      let amount = element[3] === "" ? "0.00" : element[3];
      let rabatt = (parseFloat(amount) * element[5]) / 100;
      let price = parseFloat(amount) - rabatt;
      let result = (Number(element[2]) * price).toFixed(2);
      total.value += parseFloat(result);
      element[4] = result;

      let lineDiscount = element[6]
        ? (parseFloat(result) * discount.value) / 100
        : 0;
      amount_discount.value += parseFloat(lineDiscount);
    }
  });
};

const showError = () => {
  inteSkapatsDialog.value = false;

  advisor.value.show = true;
  advisor.value.type = "error";
  
  if (err.value && err.value.response && err.value.response.data && err.value.response.data.errors) {
    advisor.value.message = Object.values(err.value.response.data.errors)
              .flat()
              .join("<br>");
  } else {
    advisor.value.message = "Ett serverfel uppstod. Försök igen.";
  }

  setTimeout(() => {
    advisor.value.show = false;
    advisor.value.type = "";
    advisor.value.message = "";
  }, 3000);

};

const createBilling = () => {
  let data = {
    message: "Fakturan skapades framgångsrikt",
    error: false,
  };

  router.push({
    name: "dashboard-admin-billings"
  });

  emitter.emit("toast", data);
};

const reloadPage = () => {
  window.location.reload();
};

const confirmLeave = () => {
  isConfirmLeaveVisible.value = false;
  allowNavigation.value = true;
  
  if (nextRoute.value) {
    router.push(nextRoute.value);
  }
};

const onSubmit = () => {
  validate.value?.validate().then(async ({ valid: isValid }) => {
    if (isValid) {
      let formData = new FormData();

      rabattApplied.value = invoiceData.value.some(
        (element) => element?.note === undefined && element[5] > 0
      );

      formData.append("client_id", invoice.value.client_id);
      formData.append("due_date", invoice.value.due_date);
      formData.append("invoice_id", invoice.value.id);
      formData.append("invoice_date", invoice.value.invoice_date);
      formData.append("subtotal", invoice.value.subtotal);
      formData.append("supplier_id", invoice.value.supplier_id);
      formData.append("tax", invoice.value.tax);
      formData.append("total", invoice.value.total);
      formData.append("rabatt", rabattApplied.value === true ? 1 : 0);
      formData.append("discount", discount.value);
      formData.append("amount_discount", amount_discount.value);
      formData.append("reference", invoice.value.reference);
      formData.append("payment_terms", invoice.value.days);

      invoice.value.details.forEach((element, index) => {
        formData.append(`details[]`, JSON.stringify(element));
      });

      isRequestOngoing.value = true;

      billingsStores
        .addBilling(formData)
        .then((res) => {
          billing_id.value = res.data.billing.id;
          isRequestOngoing.value = false;
          allowNavigation.value = true; // Allow navigation after success
          skapatsDialog.value = true;
        })
        .catch((error) => {
          err.value = error;
          inteSkapatsDialog.value = true;
          isRequestOngoing.value = false;
        });
    }
  });
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

// Intercept all navigation attempts
onBeforeRouteLeave((to, from, next) => {
  if (allowNavigation.value || !isDirty.value) {
    next();
  } else {
    nextRoute.value = to;
    isConfirmLeaveVisible.value = true;
    next(false);
  }
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

    <VForm ref="validate" @submit.prevent="onSubmit">
      <VRow no-gutters v-if="band" class="card-fill w-100">
        <!-- 👉 InvoiceEditable -->
        <VCol
          :cols="windowWidth < 1024 ? 12 : 9"
          class="order-1"
          :class="windowWidth < 1024 ? 'p-0' : 'pr-2 mb-5'"
        >
          <InvoiceEditable
            v-if="userData"
            :data="invoiceData"
            :clients="clients"
            :suppliers="suppliers"
            :invoices="invoices"
            :invoice_id="invoice_id"
            :userData="userData"
            :role="role"
            :company="company"
            :total="total"
            :amount_discount="amount_discount"
            :isCreated="true"
            :isCredit="false"
            @push="addProduct"
            @remove="removeProduct"
            @delete="deleteProduct"
            @edit="editProduct"
            @data="data"
            @discount="applyDiscount"
          />
        </VCol>

        <!-- 👉 Right Column: Invoice Action -->
        <VCol
          :cols="windowWidth < 1024 ? 12 : 3"
          class="order-1 order-md-2"
          :class="windowWidth < 1024 ? 'p-0' : ''"
        >
          <VCard :class="windowWidth < 1024 ? 'rounded-0' : ''">
            <VCardText
              :class="windowWidth < 1024 ? 'pa-6 d-flex gap-4' : 'pa-4'"
            >
              <!-- 👉 Send Invoice -->
              <VBtn
                class="btn-gradient mb-4"
                :class="windowWidth < 1024 ? 'flex-1' : 'w-100'"
                type="submit"
              >
                <template #prepend>
                  <VIcon icon="custom-send" size="24" v-if="windowWidth >= 1024" />
                  <VIcon icon="custom-send" size="24" v-if="windowWidth < 1024" />
                </template>
                Skapa faktura
              </VBtn>

              <!-- 👉 Preview -->
              <VBtn
                class="btn-light"
                :class="windowWidth < 1024 ? 'flex-1' : 'w-100'"
                :to="{ name: 'dashboard-admin-billings' }"
              >
                <template #prepend>
                  <VIcon icon="custom-return" size="24" />
                </template>
                Tillbaka
              </VBtn>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VForm>

    <!-- 👉 Dialogs Section -->
    <VDialog
      v-model="skapatsDialog"
      persistent
      class="action-dialog dialog-big-icon"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="router.push({
          name: 'dashboard-admin-billings-id',
          params: { id: billing_id },
        })"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-create-order" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title">Fakturan är skapad!</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Din faktura har sparats som ett utkast. Du kan nu skicka den till kunden.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="createBilling">
            Gå till fakturalistan
          </VBtn>
          <VBtn class="btn-gradient" @click="reloadPage"> Skapa ny faktura </VBtn>
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
          <div class="dialog-title">Fakturan kunde inte skapas</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Ett fel inträffade. Kontrollera att alla obligatoriska fält är korrekt ifyllda och försök igen.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="showError">
            Stäng
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog 
      v-model="isConfirmLeaveVisible" 
      persistent 
      class="action-dialog">
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmLeaveVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
          <div class="dialog-title">Du har osparade ändringar</div>
        </VCardText>
        <VCardText class="dialog-text">
          Om du lämnar sidan nu kommer dina ändringar inte att sparas.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="confirmLeave">Lämna sidan</VBtn>
          <VBtn class="btn-gradient" @click="isConfirmLeaveVisible = false">Stanna kvar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>
<style lang="scss" scoped>
.fix-bottom-menu {
  position: fixed;
  bottom: 70px;
  width: 100%;
  background: linear-gradient(
    90deg,
    #eafff1 0%,
    #eafff8 50%,
    #ecffff 100%
  ) !important;
  z-index: 1;
}
@media (max-width: 768px) {
  .mobile-gradient-card {
    .v-card {
      background: none !important;

      .v-card-text {
        flex-direction: row-reverse;
      }
    }
  }
}
</style>

<route lang="yaml">
meta:
  action: create
  subject: billings
</route>
