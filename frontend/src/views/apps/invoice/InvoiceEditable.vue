<script setup>

import { themeConfig } from "@themeConfig";
import { formatNumber } from "@/@core/utils/formatters";
import { requiredValidator } from "@validators";
import sampleFaktura from "@images/sample-faktura.jpg";
import VuePdfEmbed from "vue-pdf-embed";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import draggable from "vuedraggable";

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
  clients: {
    type: Object,
    required: true,
  },
  suppliers: {
    type: Object,
    required: true,
  },
  invoices: {
    type: Object,
    required: true,
  },
  invoice_id: {
    type: Number,
    required: true,
  },
  role: {
    type: String,
    required: true,
  },
  userData: {
    type: Object,
    required: true,
  },
  company: {
    type: Object,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
  amount_discount: {
    type: Number,
    required: true,
  },
  billing: {
    type: Object,
    required: false,
  },
  isCreated: {
    type: Boolean,
    required: true,
  },
  isCredit: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    required: false,
    default: "Skapa fakturan"
  },
  hasUnsavedChanges: {
    type: Boolean,
    required: false,
    default: false
  },
  days: {
    type: [Number, String],
    required: false,
    default: 5
  },
  terms: {
    type: String,
    required: false,
    default: 'Efter förfallodagen debiteras ränta enligt räntelagen.'
  }
});

const emit = defineEmits([
  "push",
  "remove",
  "delete",
  "setting",
  "data",
  "edit",
  "discount",
  "requestPreview",
]);

const { width: windowWidth } = useWindowSize();
const route = useRoute();

const clients = ref(props.clients);
const client = ref(null);
const suppliers = computed(() => Array.isArray(props.suppliers) ? props.suppliers : []);
const company = ref(props.company);
const subtotal = ref(props.total);
const total = ref("0.00");
const taxOptions = ref([0, 6, 12, 25, "Custom"]);
const selectedTax = ref('0');
const selectedDiscount = ref(0);
const selectedDiscountTemp = ref('0');
const discountOptions = ref([0, 20, 30, 50]);
const discountApplied = ref(false);
const amountDiscount = ref(props.amount_discount);
const isCustomTax = computed(() => selectedTax.value === "Custom");
const canEditInvoiceId = computed(() => {
  if (!props.isCreated) return false;
  if (props.role !== "Supplier") return false;
  if (props.billing) return false;

  // Prevent transient true while billings are still loading.
  if (!Array.isArray(company.value?.billings)) return false;

  return company.value.billings.length === 0;
});
const isInvoiceIdAlertVisible = ref(false);
const isMobile = ref(false);
const controlledTab = ref("redigera");
const actionDialog = ref(false);

const deletedSupplierLabel = '(Borttagen)'

const getSupplierBaseName = supplier => {
  const fromFullName = typeof supplier?.full_name === 'string' ? supplier.full_name.trim() : ''
  const fromDirectUser = [supplier?.name, supplier?.last_name].filter(Boolean).join(' ').trim()
  const fromRelatedUser = [supplier?.user?.name, supplier?.user?.last_name].filter(Boolean).join(' ').trim()
  const fromCompany = String(supplier?.user?.user_detail?.company ?? supplier?.user?.userDetail?.company ?? supplier?.company ?? '').trim()
  const fromId = supplier?.id != null ? `Leverantor #${supplier.id}` : ''

  return fromFullName || fromDirectUser || fromRelatedUser || fromCompany || fromId
}

const getSupplierDisplayName = supplier => {
  const explicitDisplayName = String(supplier?.display_full_name ?? '').trim()

  return explicitDisplayName || getSupplierBaseName(supplier)
}

const getSupplierOptionSource = supplierOption => {
  return supplierOption?.raw ?? supplierOption
}

const isSupplierInactive = supplier => {
  return !!supplier?.deleted_at || !!supplier?.user?.deleted_at
}

const shouldStyleInactiveSupplier = supplier => {
  return !!props.billing && isSupplierInactive(supplier)
}

const supplierOptions = computed(() => {
  const activeSuppliers = suppliers.value.filter(supplier => !isSupplierInactive(supplier))

  const mappedActiveSuppliers = activeSuppliers.map(supplier => ({
    ...supplier,
    display_full_name: getSupplierDisplayName(supplier),
  }))

  if (!props.billing)
    return mappedActiveSuppliers

  const currentSupplier = props.billing?.supplier

  if (!currentSupplier?.id)
    return mappedActiveSuppliers

  const alreadyIncluded = mappedActiveSuppliers.some(item => String(item?.id) === String(currentSupplier.id))

  if (alreadyIncluded)
    return mappedActiveSuppliers

  const supplierName = getSupplierBaseName(currentSupplier)
  const shouldMarkDeleted = isSupplierInactive(currentSupplier)
  const displayName = shouldMarkDeleted
    ? `${String(supplierName ?? '').trim()} ${deletedSupplierLabel}`.trim()
    : getSupplierDisplayName(currentSupplier)

  return [
    {
      ...currentSupplier,
      full_name: supplierName,
      display_full_name: displayName,
    },
    ...mappedActiveSuppliers,
  ]
})

const isConfirmDiscountVisible = ref(false);
const isAlertDiscountVisible = ref(false);
const isAlertPreviewVisible = ref(false);
const previousTab = ref("redigera");
const pdfCacheKey = ref(Date.now());

const pdfSource = computed(() => {
  if (!props.billing?.file) return null;
  return themeConfig.settings.urlbase +
    'proxy-image?url=' +
    themeConfig.settings.urlStorage +
    props.billing.file +
    '&t=' + pdfCacheKey.value;
});

const handleTabChange = (newTab) => {
  if (newTab === 'forhandsgranska' && (!props.billing?.file || props.hasUnsavedChanges)) {
    // Revert to previous tab and show alert
    nextTick(() => {
      controlledTab.value = previousTab.value;
    });
    isAlertPreviewVisible.value = true;
  } else {
    previousTab.value = newTab;
  }
};

watch(controlledTab, (newVal) => {
  handleTabChange(newVal);
});

const isAddBillingRoute = computed(() => {
  const routeName = String(route.name ?? '');

  if (routeName.includes('billings-add'))
    return true;

  return /\/billings\/add(?:\/|$)/.test(String(route.path ?? ''));
});

watch(
  canEditInvoiceId,
  (isEditable, wasEditable) => {
    if (!isEditable) {
      isInvoiceIdAlertVisible.value = false;
      return;
    }

    const isInitialEvaluation = wasEditable === undefined;
    const becameEditable = wasEditable === false;
    const shouldShowOnAddEntry = isInitialEvaluation && isAddBillingRoute.value;

    if (becameEditable || shouldShowOnAddEntry)
      isInvoiceIdAlertVisible.value = true;
  },
  { immediate: true }
);

const setPreviewTab = () => {
  pdfCacheKey.value = Date.now(); // Force PDF refresh
  previousTab.value = 'forhandsgranska';
  controlledTab.value = 'forhandsgranska';
};

defineExpose({
  setPreviewTab,
  controlledTab,
});

const invoice = ref({
  id: 1,
  days: props.days,
  terms: props.terms,
  client_id: null,
  supplier_id: null,
  invoice_date: null,
  due_date: null,
  subtotal: 0,
  tax: 0,
  total: 0,
  reference: null,
  details: structuredClone(toRaw(props.data)),
});

const selectedSupplierOption = computed(() => {
  const matchedSupplier = supplierOptions.value.find(option => String(option?.id) === String(invoice.value.supplier_id))

  if (matchedSupplier)
    return matchedSupplier

  if (props.billing?.supplier && String(props.billing?.supplier_id) === String(invoice.value.supplier_id))
    return props.billing.supplier

  return null
})

const compareSupplierIds = (a, b) => {
  if (a == null || b == null)
    return a === b

  return String(a) === String(b)
}

watch(
  [supplierOptions, () => invoice.value.supplier_id],
  ([options, supplierId]) => {
    if (supplierId == null || !options.length)
      return

    const matchedSupplier = options.find(option => String(option?.id) === String(supplierId))

    if (!matchedSupplier)
      return

    if (invoice.value.supplier_id !== matchedSupplier.id)
      invoice.value.supplier_id = matchedSupplier.id
  },
  { immediate: true }
)

const extractDaysFromNetTermSplit = (term) => {
  const parts = term.split(/\s+/);
  const daysIndex = parts.findIndex((part) => /dagar?/i.test(part));
  return daysIndex > -1 ? parseInt(parts[daysIndex - 1]) : null;
};

const getInitialInvoiceId = (billings = [], configuredInvoiceId = 0) => {
  const maxFromBillings = Array.isArray(billings)
    ? billings.reduce((maxValue, billingItem) => {
        const currentId = Number(billingItem?.invoice_id);
        return Number.isFinite(currentId) && currentId > maxValue
          ? currentId
          : maxValue;
      }, 0)
    : 0;

  const nextAvailableId = maxFromBillings + 1;
  const configuredId = Number(configuredInvoiceId);

  if (Number.isFinite(configuredId) && configuredId > 0)
    return Math.max(nextAvailableId, configuredId);

  return nextAvailableId;
};

watch(
  [() => props.company, () => props.invoice_id],
  ([companyValue]) => {
    company.value = companyValue;

    if (props.billing) {
      invoice.value.id = route.path.includes("/duplicate/")
        ? getInitialInvoiceId(company.value?.billings, props.invoice_id)
        : props.billing.invoice_id;
    } else {
      invoice.value.id = getInitialInvoiceId(company.value?.billings, props.invoice_id);
    }
  },
  { immediate: true }
);

watch(
  () => props.days,
  (val) => {
    if (props.billing) return;

    const parsedDays = Number(val);
    if (Number.isFinite(parsedDays) && parsedDays > 0) {
      invoice.value.days = parsedDays;
    }
  }
);

watch(
  () => props.terms,
  (val) => {
    //if (props.billing) return;

    invoice.value.terms = typeof val === "string" && val.trim()
      ? val
      : invoice.value.terms;

    emit("data", invoice.value);
  }
);

watch(props.data, (val) => {
  invoice.value.details = { ...val };
});

watch(
  () => props.amount_discount,
  (val) => {
    amountDiscount.value = val;

    var tax = invoice.value.tax / 100;

    total.value = (
      subtotal.value * tax +
      subtotal.value -
      amountDiscount.value
    ).toFixed(2);
    invoice.value.total = (
      subtotal.value * tax +
      subtotal.value -
      amountDiscount.value
    ).toFixed(2);
  }
);

watch(
  () => props.total,
  (val) => {
    subtotal.value = val;

    var tax = invoice.value.tax / 100;
    total.value = (val * tax + val - amountDiscount.value).toFixed(2);

    invoice.value.total = (val * tax + val - amountDiscount.value).toFixed(2);
    invoice.value.subtotal = val;
  }
);

watch(
  () => invoice.value.tax,
  (val) => {
    var tax = val / 100;

    total.value = (
      subtotal.value * tax +
      subtotal.value -
      amountDiscount.value
    ).toFixed(2);
    invoice.value.total = (
      subtotal.value * tax +
      subtotal.value -
      amountDiscount.value
    ).toFixed(2);
    invoice.value.subtotal = subtotal.value.toFixed(2);
  }
);

watch(
  () => invoice.value.days,
  () => {
    calculateDueDate();
  }
);

watch(
  () => invoice.value.invoice_date,
  () => {
    calculateDueDate();
  }
);

onMounted(() => {
  checkIfMobile();

  window.addEventListener("resize", checkIfMobile);

  fetchData();
});

const checkIfMobile = () => {
  isMobile.value = window.innerWidth < 768;
};

async function fetchData() {
  if (props.billing) {
    invoice.value.id = route.path.includes("/duplicate/")
      ? getInitialInvoiceId(company.value?.billings, props.invoice_id)
      : props.billing.invoice_id;
    invoice.value.reference = props.billing.reference;
    invoice.value.invoice_date = route.path.includes("/duplicate/") ? new Date().toLocaleDateString('sv-SE') : props.billing.invoice_date;
    invoice.value.due_date = props.billing.due_date;
    invoice.value.days = extractDaysFromNetTermSplit(
      props.billing.payment_terms
    );
    invoice.value.supplier_id = props.billing.supplier_id ?? null;
    invoice.value.client_id = props.billing.client_id;
    invoice.value.tax = props.billing.tax;

    selectedTax.value = taxOptions.value.includes(props.billing.tax)
      ? props.billing.tax
      : "Custom";
    client.value = props.billing.client;

    selectedDiscount.value = props.billing.discount;
    selectedDiscountTemp.value = props.billing.discount;
  } else {
    const invoice_date = new Date();
    const year = invoice_date.getFullYear();
    const month = String(invoice_date.getMonth() + 1).padStart(2, "0");
    const day = String(invoice_date.getDate()).padStart(2, "0");

    invoice.value.invoice_date = `${year}-${month}-${day}`;

    invoice.value.id = getInitialInvoiceId(company.value?.billings, props.invoice_id);
  }
}

const handleTaxChange = () => {
  if (!isCustomTax.value) invoice.value.tax = selectedTax.value;
  else invoice.value.tax = 0;
};

const calculateDueDate = () => {
  if (invoice.value.invoice_date && invoice.value.days) {
    const invoiceDateUTC = new Date(`${invoice.value.invoice_date}T00:00:00Z`);

    const daysToAdd = parseInt(invoice.value.days);
    const dueDateUTC = new Date(invoiceDateUTC);
    dueDateUTC.setUTCDate(invoiceDateUTC.getUTCDate() + daysToAdd);

    const year = dueDateUTC.getUTCFullYear();
    const month = String(dueDateUTC.getUTCMonth() + 1).padStart(2, "0");
    const day = String(dueDateUTC.getUTCDate()).padStart(2, "0");
    const formattedDueDate = `${year}-${month}-${day}`;

    invoice.value.due_date = formattedDueDate;

    emit("data", invoice.value);
  }
};

const startDateTimePickerConfig = computed(() => {
  const now = new Date();
  const tomorrow = new Date(now);
  tomorrow.setDate(now.getDate() + 1);

  const formatToISO = (date) => date.toISOString().split("T")[0];

  const config = {
    dateFormat: "Y-m-d",
    position: "auto right",
    disable: [
      {
        from: formatToISO(tomorrow),
        to: "2099-12-31", // Una fecha futura lejana para bloquear indefinidamente
      },
    ],
  };

  return config;
});

const selectSupplier = async () => {
  const selected = supplierOptions.value.find(
    element => String(element?.id) === String(invoice.value.supplier_id)
  );

  if (selected) {
    company.value = selected.user.user_detail;
    company.value.email = selected.user.email;
    company.value.billings = selected.billings;
    invoice.value.id = getInitialInvoiceId(selected.billings, props.invoice_id);
    clients.value = props.clients.filter(
      item => String(item?.supplier_id) === String(invoice.value.supplier_id)
    );
  } else {
    company.value = props.company;
    invoice.value.id = getInitialInvoiceId(props.company?.billings, props.invoice_id);
    clients.value = props.clients;
  }

  invoice.value.client_id = null;

  emit("data", invoice.value);
};

const selectClient = async () => {
  var selected = clients.value.filter(
    (element) => element.id === invoice.value.client_id
  )[0];

  if (selected) {
    client.value = selected;
    invoice.value.reference = client.value.reference;
  } else client.value = null;

  emit("data", invoice.value);
};

// 👉 Add item function
const addItem = () => {
  var item = {};
  
  props.invoices.forEach((element) => {
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

  actionDialog.value = false;
  emit("push", item);
};

const addNote = () => {
  actionDialog.value = false;
  emit("push", { note: "" });
};

const editNote = () => {
  emit("edit");
};

const removeNote = (id) => {
  emit("delete", id);
};

const onStart = async (e) => {
  // console.log('oldIndex',e.oldIndex)
};

const onEnd = async (e) => {
  emit("data", invoice.value);
};

const removeProduct = (id) => {
  emit("remove", id);
};

const deleteProduct = (id) => {
  emit("delete", id);
};

const inputData = () => {
  emit("data", invoice.value);
};

const discount = () => {
  actionDialog.value = false;
  isConfirmDiscountVisible.value = true;
};

const cancelDiscount = () => {
  isConfirmDiscountVisible.value = false;
};

const saveDiscount = () => {
  discountApplied.value = false;

  invoice.value.details.forEach((element) => {
    if (element?.note === undefined) {
      if (element[5] > 0) discountApplied.value = true;
    }
  });

  if (discountApplied.value) {
    isAlertDiscountVisible.value = true;
    selectedDiscountTemp.value = 0;
  } else {
    selectedDiscount.value = selectedDiscountTemp.value;
    emit("discount", selectedDiscount.value);
  }

  isConfirmDiscountVisible.value = false;
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

const handleBlur = (element) => {
  const defaults = {
    2: 1,
    3: "0.00",
    5: 0,
  };

  Object.entries(defaults).forEach(([index, defaultValue]) => {
    const i = parseInt(index);
    if (element[i] === "" || element[i] === null || isNaN(Number(element[i]))) {
      element[i] = defaultValue;
    }
  });

  // Formatear campos decimales (type_id 3) a 2 decimales
  props.invoices.forEach((inv) => {
    if (inv.type_id === 3 && element[inv.id] !== undefined && element[inv.id] !== "") {
      const numValue = parseFloat(element[inv.id]);
      if (!isNaN(numValue)) {
        element[inv.id] = numValue.toFixed(2);
      }
    }
  });

  // Limitar el campo de descuento (índice 5) a un máximo de 100
  if (element[5] !== undefined && element[5] > 100) {
    element[5] = 100;
  }
};

const handleFocus = (element, fieldId) => {
  const value = element[fieldId];
  // Clear the field if it's the default "0.00" or 0 to allow easy editing
  if (value === "0.00" || value === 0 || value === "0") {
    element[fieldId] = "";
  }
};
</script>

<template>
  <VCard
    class="pa-0"
    v-if="invoice.details.length > 0"
    :class="windowWidth < 1024 ? 'rounded-0 bg-none' : ''"
  >
    <VCardTitle
      class="d-flex justify-space-between bg-white"
      :class="windowWidth < 1024 ? 'flex-column pa-6 pb-0 fix-header' : 'pa-4'"
    >
      <div class="d-flex align-center w-100 w-md-auto font-blauer">
        <h2 class="faktura-title">{{ title }}</h2>
      </div>
      <VTabs
        v-model="controlledTab"
        grow
        :show-arrows="false"
        class="mt-6 equal-tabs"
        :class="windowWidth < 1024 ? 'd-flex' : 'd-none'"
      >
        <VTab value="redigera">Redigera</VTab>
        <VTab value="forhandsgranska">Förhandsgranska</VTab>
      </VTabs>
    </VCardTitle>

    <VDivider :class="windowWidth < 1024 ? 'd-none' : 'mt-2 mx-4'" />

    <section
      class="invoice-panel border rounded-lg pa-4"
      v-if="windowWidth >= 1024"
    >
      <VCardText class="d-flex invoice-box">
        <div class="w-100 w-md-50">
          <div 
            class="invoice-logo-box d-flex align-center mb-6"
            :class="company.logo ? 'invoice-logo' : ''"
          >
            <!-- 👉 Logo -->
            <img
              v-if="company.logo"
              class="invoice-logo-img"
              :src="themeConfig.settings.urlStorage + company.logo"
            />
            <div v-else>    
              <h1 class="mb-0">{{ company.company }}</h1>   
              <span class="me-2 text-start w-40 text-black">
                {{ company.name }} {{ company.last_name }} <br />
                {{ company.email }}
              </span>
            </div>
          </div>
          <!-- 👉 Invoice Id -->
          <div
            class="d-block d-md-flex align-center justify-sm-start text-right mb-2"
          >
            <span class="me-2 text-start w-40 text-black">Faktura nr</span>
            <span>
              <div class="form-field">
                <VTextField
                  v-model="invoice.id"
                  :disabled="!canEditInvoiceId"
                  :rules="canEditInvoiceId ? [requiredValidator] : []"
                  type="number"
                  :min="1"
                  prefix="#"
                  @input="inputData"
                  style="inline-size: 10.5rem"
                />
              </div>
            </span>
          </div>
          <div
            class="d-block d-md-flex align-center justify-sm-start mb-2 text-right"
            v-if="client"
          >
            <span class="me-2 text-start w-40 text-black">Kund nr</span>
            <span>
              <div class="form-field">
                <VTextField
                  v-model="client.order_id"
                  disabled
                  prefix="#"
                  style="inline-size: 10.5rem"
                />
              </div>
            </span>
          </div>
          <!-- 👉 Issue Date -->
          <div
            class="d-block d-md-flex align-center justify-sm-start mb-2 md:text-right"
          >
            <span class="me-2 text-start w-40 text-black">Fakturadatum</span>

            <span style="inline-size: 10.5rem">
              <div class="form-field">
                <VTextField
                  v-if="props.isCredit"
                  v-model="invoice.invoice_date"
                  disabled
                  style="inline-size: 10.5rem"
                />
                <AppDateTimePicker
                  v-else
                  :key="JSON.stringify(startDateTimePickerConfig)"
                  v-model="invoice.invoice_date"
                  placeholder="YYYY-MM-DD"
                  :rules="[requiredValidator]"
                  :config="startDateTimePickerConfig"
                  @input="inputData"
                  clearable
                />
              </div>
            </span>
          </div>

          <!-- 👉 Due Date -->
          <div class="d-block d-md-flex align-center justify-sm-start mb-0">
            <span class="me-2 text-start w-40 text-black">Förfallodatum</span>

            <span style="min-inline-size: 10.5rem">
              <div class="form-field">
                <VTextField
                  v-if="props.isCredit"
                  v-model="invoice.due_date"
                  disabled
                  style="inline-size: 10.5rem"
                />
                <AppDateTimePicker
                  v-else
                  v-model="invoice.due_date"
                  placeholder="YYYY-MM-DD"
                  readonly
                  class="cursor-none"
                />
              </div>
            </span>
          </div>

          <!-- 👉 Days -->
          <div
            class="d-block d-md-flex align-center justify-sm-start mb-0 mt-2"
          >
            <span class="me-2 text-start w-40 text-black">
              Betalningsvillkor
            </span>

            <span style="width: 10.5rem">
              <div class="form-field">
                <VTextField
                  v-model="invoice.days"
                  type="number"
                  label="Dagar"
                  :disabled="props.isCredit"
                  :min="0"
                />
              </div>
            </span>
          </div>
          <p class="mt-5 mb-0 text-sm" v-if="client">
            {{ invoice.terms }}
          </p>
        </div>
        <div class="text-right d-flex flex-column w-100 w-md-50">
          <h1 class="mt-4 mb-0 text-center faktura mt-5 mt-md-0 ml-auto">
            {{
              invoice.state_id === 9
                ? "KREDIT FAKTURA"
                : parseInt(invoice.days) === 0
                ? "KONTANT FAKTURA"
                : "FAKTURA"
            }}
          </h1>
          <h3 class="mb-0 mt-2" v-if="client">
            {{ client.fullname }}
          </h3>
          <span class="d-flex flex-column w-100"  v-if="client">
            <span>Org.nr. {{ client.organization_number }}</span>
          </span>
          <p class="mb-0 mt-2 form-field" v-if="client" style="min-width: 250px">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vår referens" />
            <VTextField
              v-model="invoice.reference"
              :disabled="props.isCredit"
              @input="$emit('data', invoice)"
            />
          </p>
          <div
            class="d-flex flex-column align-center justify-sm-end mb-0 mt-auto"
            v-if="client"
          >
            <span class="text-h6 font-weight-medium w-100 my-3">
              Faktureringsadress
            </span>
            <span class="d-flex flex-column w-100">
              <span>{{ client.address }}</span>
              <span>{{ client.postal_code }}, {{ client.street }}</span>
            </span>
          </div>
        </div>
      </VCardText>

      <VCardText
        class="d-flex flex-wrap justify-space-between flex-column flex-sm-row mt-6 p-0 w-100"
      >
        <div class="rouded-select">
          <AppAutocomplete
            v-if="props.role === 'SuperAdmin' || props.role === 'Administrator'"
            v-model="invoice.supplier_id"
            :items="supplierOptions"
            item-title="display_full_name"
            :item-value="(item) => item.id"
            :value-comparator="compareSupplierIds"
            :disabled="props.isCredit"
            placeholder="Leverantörer"
            class="mb-4 w-100"
            :class="{ 'deleted-supplier-selection': shouldStyleInactiveSupplier(selectedSupplierOption) }"
            @update:modelValue="selectSupplier"
            clearable
            menu-icon="custom-chevron-down"
          >
            <template #item="{ props: itemProps, item }">
              <VListItem
                v-bind="itemProps"
                :class="{ 'deleted-supplier-option': shouldStyleInactiveSupplier(getSupplierOptionSource(item)) }"
              >
                <template #title>
                  <span :class="{ 'deleted-supplier-text': shouldStyleInactiveSupplier(getSupplierOptionSource(item)) }">
                    {{ getSupplierDisplayName(getSupplierOptionSource(item)) }}
                  </span>
                </template>
              </VListItem>
            </template>
          </AppAutocomplete>
        </div>
        <div class="rouded-select">
          <AppAutocomplete
            v-model="invoice.client_id"
            :items="clients"
            :item-title="(item) => item.fullname"
            :item-value="(item) => item.id"
            :disabled="props.isCredit"
            placeholder="Kunder"
            class="w-100"
            :rules="[requiredValidator]"
            @update:modelValue="selectClient"
            clearable
            menu-icon="custom-chevron-down"
          />
        </div>
      </VCardText>

      <VDivider :class="windowWidth < 1024 ? 'm-0' : 'my-6 mx-0'" />

      <!-- 👉 Add purchased products -->
      <VCardText class="add-products-form mt-2 py-0 px-0">
        <draggable
          class="mb-4"
          v-model="invoice.details"
          tag="div"
          item-key="index"
          @start="onStart"
          @end="onEnd"
        >
          <template #item="{ element, index }">
            <div class="draggable-item">
              <VIcon icon="custom-grabber" size="24" />
              <div class="d-flex w-100" v-if="element?.note !== undefined">
                <div class="form-field w-100">
                  <VTextarea
                    v-model="element.note"
                    label="Notera"
                    placeholder="Notera"
                    rows="2"
                    class="mt-1 w-100"
                    @input="editNote"
                  />
                </div>
                <VBtn @click="removeNote(index)">
                  <VIcon icon="custom-close" size="16" />
                </VBtn>
              </div>
              <template v-else>
                <div
                  class="d-flex flex-column justify-space-between p-0"
                  v-if="selectedDiscount > 0"
                >
                  <VCheckbox
                    v-if="selectedDiscount > 0"
                    class="pe-2"
                    v-model="element[6]"
                    color="primary"
                    @update:modelValue="$emit('edit')"
                  />
                </div>
                <div class="w-100">
                  <div class="add-products-header d-none d-md-flex w-100">
                    <table class="w-100">
                      <thead>
                        <tr>
                          <template
                            v-for="(invoice, index) in invoices"
                            :key="invoice.id"
                          >
                            <td
                              :style="`width: ${
                                invoice.type_id === 1 ? '40' : '15'
                              }%;`"
                            >
                              <span class="">
                                {{ invoice.name }}
                              </span>
                            </td>
                          </template>
                          <td style="width: 15%">
                            <span class=""> Rabatt </span>
                          </td>
                        </tr>
                      </thead>
                    </table>
                  </div>
                  <!-- 👉 Left Form -->
                  <div class="add-products-form flex-grow-1">
                    <table class="w-100">
                      <thead>
                        <tr>
                          <template
                            v-for="(invoice, index) in invoices"
                            :key="invoice.id"
                          >
                            <td
                              :style="`width: ${
                                invoice.type_id === 1 ? '40' : '15'
                              }%;`"
                              class="pe-2"
                              style="vertical-align: top"
                            >
                              <div class="form-field">
                                <VTextarea
                                  v-if="invoice.type_id === 1"
                                  v-model="element[invoice.id]"
                                  rows="2"
                                  :placeholder="invoice.description"
                                  persistent-placeholder
                                  :readonly="element.disabled"
                                  :rules="[requiredValidator]"
                                />
                                <VTextField
                                  v-if="invoice.type_id === 2"
                                  v-model="element[invoice.id]"
                                  type="number"
                                  :min="1"
                                  :readonly="element.disabled"
                                  :rules="[requiredValidator]"
                                  @input="$emit('edit')"
                                  @blur="() => handleBlur(element)"
                                />
                                <VTextField
                                  v-if="invoice.type_id === 3"
                                  v-model="element[invoice.id]"
                                  type="number"
                                  :min="0"
                                  :step="0.01"
                                  :readonly="element.disabled"
                                  @input="$emit('edit')"
                                  :rules="[requiredValidator]"
                                  :disabled="invoice.name === 'Belopp'"
                                  @focus="() => handleFocus(element, invoice.id)"
                                  @blur="() => handleBlur(element)"
                                />
                              </div>
                            </td>
                          </template>
                          <td
                            style="width: 15%; vertical-align: top"
                            class="pe-2"
                          >
                            <div class="form-field">
                              <VTextField
                                :disabled="selectedDiscount > 0"
                                v-model="element[5]"
                                type="number"
                                :min="0"
                                :max="100"
                                :readonly="element.disabled"
                                @input="$emit('edit')"
                                @blur="() => handleBlur(element)"
                              />
                            </div>
                          </td>
                        </tr>
                      </thead>
                    </table>
                  </div>
                  <!-- 👉 Item Actions -->
                </div>
                <div class="d-flex flex-column justify-space-between p-0">
                  <VBtn
                    :disabled="index === 0 ? true : false"
                    @click="deleteProduct(index)"
                  >
                    <VIcon icon="custom-close" size="16" />
                  </VBtn>
                </div>
              </template>
            </div>
          </template>
        </draggable>
        <div class="mt-6">
          <VMenu>
            <template #activator="{ props }">
              <VBtn v-bind="props" class="btn-gradient btn-wide">
                Lägg till
              </VBtn>
            </template>
            <VList>
              <VListItem @click="addItem">
                <template #prepend>
                  <VIcon icon="custom-plus" size="24" />
                </template>
                <VListItemTitle>Ny produktrad</VListItemTitle>
              </VListItem>
              <VListItem @click="addNote">
                <template #prepend>
                  <VIcon icon="custom-plus" size="24" />
                </template>
                <VListItemTitle>Ny textrad</VListItemTitle>
              </VListItem>
              <VListItem @click="discount">
                <template #prepend>
                  <VIcon icon="custom-plus" size="24" />
                </template>
                <VListItemTitle>Skatteavdrag för ROT / RUT</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>
        </div>
      </VCardText>

      <VDivider :class="windowWidth < 1024 ? 'm-0' : 'my-6 mx-0'" />

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
                  :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'"
                >
                  {{ formatNumber(subtotal) }} kr
                </td>
              </tr>
              <tr>
                <td class="pe-4">Moms</td>
                <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
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
              <tr v-if="selectedDiscount > 0">
                <td class="pe-4">
                  Preliminär skattereduktion {{ selectedDiscount }}% av
                  {{ formatNumber(subtotal) }} kr:
                </td>
                <td
                  class="text-bold"
                  :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'"
                >
                  - {{ formatNumber(amountDiscount) }} kr
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
                  :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'"
                >
                  {{ formatNumber(total) }} kr
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </VCardText>

      <VDivider :class="windowWidth < 1024 ? 'm-0' : 'my-6 mx-0'" />

      <VCardText class="mb-sm-4 p-0 text-black">
        <VRow>
          <VCol cols="12" md="3" class="d-flex flex-column">
            <span class="me-2 text-bold text-footer" v-if="company.company"> Företagsnamn </span>
            <span class="text-footer" v-if="company.company">
              {{ company.company }}
            </span>
            <span class="me-2 mt-4 text-bold text-footer" v-if="company.phone"> Telefon </span>
            <span class="text-footer" v-if="company.phone">
              {{ company.phone }}
            </span>
            <span class="me-2 mt-4 text-bold text-footer"> Adress </span>
            <span class="d-flex flex-column">
              <span class="text-footer">{{ company.address }}</span>
              <span class="text-footer">{{ company.postal_code }}, {{ company.street }}</span>
            </span>
            <span class="me-2 mt-4 text-bold text-footer" v-if="company.swish">
              Swish
            </span>
            <span class="text-footer" v-if="company.swish">
              {{ company.swish }}
            </span>
          </VCol>
          <VCol cols="12" md="3" class="d-flex flex-column">
            <span class="me-2 text-bold text-footer"> Org.nr. </span>
            <span class="text-footer"> {{ company.organization_number }} </span>
            <span class="me-2 mt-4 text-bold text-footer" v-if="company.vat">
              Vat
            </span>
            <span class="text-footer"> {{ company.vat }} </span>
            <span class="me-2 mt-4 text-bold text-footer" v-if="company.bic">
              BIC
            </span>
            <span class="text-footer" v-if="company.bic">
              {{ company.bic }}
            </span>

            <span
              class="me-2 mt-4 text-bold text-footer"
              v-if="company.plus_spin"
            >
              Plusgiro
            </span>
            <span class="text-footer" v-if="company.plus_spin">
              {{ company.plus_spin }}
            </span>
          </VCol>
          <VCol cols="12" md="3" class="d-flex flex-column">
            <span class="me-2 text-bold text-footer" v-if="company.link"> Webbplats </span>
            <span class="text-footer" v-if="company.link">
              {{ company.link }}
            </span>
            <span class="me-2 mt-4 text-bold text-footer">
              Företagets e-post
            </span>
            <span class="text-footer"> {{ company.email }} </span>
          </VCol>
          <VCol cols="12" md="3" class="d-flex flex-column">
            <span class="me-2 text-bold text-footer" v-if="company.bank">
              Bank
            </span>
            <span class="text-footer" v-if="company.bank">
              {{ company.bank }}
            </span>

            <span class="me-2 mt-4 text-bold text-footer" v-if="company.iban">
              Bankgiro
            </span>
            <span class="text-footer"> {{ company.iban }} </span>

            <span
              class="me-2 mt-4 text-bold text-footer"
              v-if="company.account_number"
            >
              Kontonummer
            </span>
            <span class="text-footer" v-if="company.account_number">
              {{ company.account_number }}
            </span>

            <span
              class="me-2 mt-4 text-bold text-footer"
              v-if="company.iban_number"
            >
              Iban nummer
            </span>
            <span class="text-footer" v-if="company.iban_number">
              {{ company.iban_number }}
            </span>
          </VCol>
        </VRow>
      </VCardText>
    </section>

    <section v-if="windowWidth < 1024" class="margin-top-fill">
      <VCardText class="pa-0">
        <VWindow
          v-model="controlledTab"
          class="disable-tab-transition"
          :touch="false"
        >
          <VWindowItem
            class="d-flex flex-column gap-6 bg-white pa-6"
            value="redigera"
          >
            <div class="w-100 invoice-box d-flex flex-column pa-6 gap-2">
              <!-- 👉 Invoice Id -->
              <div class="">
                <span class="mb-2 me-2 text-start w-40 text-black"
                  >Faktura nr</span
                >
                <span>
                  <div class="form-field">
                    <VTextField
                      v-model="invoice.id"
                      :disabled="!canEditInvoiceId"
                      :rules="canEditInvoiceId ? [requiredValidator] : []"
                      type="number"
                      :min="1"
                      prefix="#"
                      @input="inputData"
                    />
                  </div>
                </span>
              </div>
              <div
                class=""
                v-if="client"
              >
                <span class="mb-2 me-2 text-start w-40 text-black"
                  >Kund nr</span
                >
                <span>
                  <div class="form-field">
                    <VTextField
                      v-model="client.order_id"
                      disabled
                      prefix="#"
                      style="inline-size: 10.5rem"
                    />
                  </div>
                </span>
              </div>
              <!-- 👉 Issue Date -->
              <div
                class="d-block d-md-flex align-center justify-sm-start mb-2 md:text-right"
              >
                <span class="mb-2 me-2 text-start w-40 text-black"
                  >Fakturadatum</span
                >

                <span style="inline-size: 10.5rem">
                  <div class="form-field">
                    <VTextField
                      v-if="props.isCredit"
                      v-model="invoice.invoice_date"
                      disabled
                      style="inline-size: 10.5rem"
                    />
                    <AppDateTimePicker
                      v-else
                      :key="JSON.stringify(startDateTimePickerConfig)"
                      v-model="invoice.invoice_date"
                      placeholder="YYYY-MM-DD"
                      :rules="[requiredValidator]"
                      :config="startDateTimePickerConfig"
                      @input="inputData"
                      clearable
                    />
                  </div>
                </span>
              </div>

              <!-- 👉 Due Date -->
              <div class="d-block d-md-flex align-center justify-sm-start mb-0">
                <span class="mb-2 me-2 text-start w-40 text-black"
                  >Förfallodatum</span
                >

                <span style="min-inline-size: 10.5rem">
                  <div class="form-field">
                    <VTextField
                      v-if="props.isCredit"
                      v-model="invoice.due_date"
                      disabled
                      style="inline-size: 10.5rem"
                    />
                    <AppDateTimePicker
                      v-else
                      v-model="invoice.due_date"
                      placeholder="YYYY-MM-DD"
                      readonly
                      class="cursor-none"
                    />
                  </div>
                </span>
              </div>

              <!-- 👉 Days -->
              <div
                class="d-block d-md-flex align-center justify-sm-start mb-0 mt-2"
              >
                <span class="me-2 text-start w-40 text-black">
                  Betalningsvillkor
                </span>

                <span style="width: 10.5rem">
                  <div class="form-field">
                    <VTextField
                      v-model="invoice.days"
                      type="number"
                      label="Dagar"
                      :disabled="props.isCredit"
                      :min="0"
                    />
                  </div>
                </span>
              </div>

              <p class="mt-5 mb-0 text-sm" v-if="client">
                {{ invoice.terms }}
              </p>

              <div class="text-right d-flex flex-column w-100 w-md-50">
                <h3 class="mb-0 mt-2" v-if="client">
                  {{ client.fullname }}
                </h3>
                <span class="d-flex flex-column w-100"  v-if="client">
                  <span>Org.nr. {{ client.organization_number }}</span>
                </span>
                <p class="mb-0 mt-2 form-field" v-if="client" style="min-width: 250px">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vår referens" />
                  <VTextField
                    v-model="invoice.reference"
                    :disabled="props.isCredit"
                    @input="$emit('data', invoice)"
                  />
                </p>
                <div
                  class="d-flex flex-column align-center justify-sm-end mb-0 mt-auto"
                  v-if="client"
                >
                  <span class="text-h6 font-weight-medium w-100 my-3">
                    Faktureringsadress
                  </span>
                  <span class="d-flex flex-column w-100">
                    <span>{{ client.address }}</span>
                    <span>{{ client.postal_code }}, {{ client.street }}</span>
                  </span>
                </div>
              </div>
            </div>

            <div 
              class="rouded-select" 
              v-if="
                props.role === 'SuperAdmin' || props.role === 'Administrator'
              "
            >
              <AppAutocomplete
                v-model="invoice.supplier_id"
                :items="supplierOptions"
                item-title="display_full_name"
                :item-value="(item) => item.id"
                :value-comparator="compareSupplierIds"
                :disabled="props.isCredit"
                placeholder="Leverantörer"
                class="w-100"
                :class="{ 'deleted-supplier-selection': shouldStyleInactiveSupplier(selectedSupplierOption) }"
                @update:modelValue="selectSupplier"
                clearable
                menu-icon="custom-chevron-down"
              >
                <template #item="{ props: itemProps, item }">
                  <VListItem
                    v-bind="itemProps"
                    :class="{ 'deleted-supplier-option': shouldStyleInactiveSupplier(getSupplierOptionSource(item)) }"
                  >
                    <template #title>
                      <span :class="{ 'deleted-supplier-text': shouldStyleInactiveSupplier(getSupplierOptionSource(item)) }">
                        {{ getSupplierDisplayName(getSupplierOptionSource(item)) }}
                      </span>
                    </template>
                  </VListItem>
                </template>
              </AppAutocomplete>
            </div>
            <div class="rouded-select">
              <AppAutocomplete
                v-model="invoice.client_id"
                :items="clients"
                :item-title="(item) => item.fullname"
                :item-value="(item) => item.id"
                :disabled="props.isCredit"
                placeholder="Kunder"
                class="w-100"
                :rules="[requiredValidator]"
                @update:modelValue="selectClient"
                clearable
                menu-icon="custom-chevron-down"
              />
            </div>
            <VDivider class="ghost-divider" />
            <VBtn
              v-bind="props"
              class="btn-gradient w-100"
              @click="actionDialog = true"
            >
              Lägg till
            </VBtn>
            <VDialog
              v-model="actionDialog"
              transition="dialog-bottom-transition"
              content-class="dialog-bottom-full-width"
            >
              <VCard>
                <VList>
                  <VListItem @click="addItem">
                    <template #prepend>
                      <VIcon icon="custom-plus" size="24" />
                    </template>
                    <VListItemTitle>Ny produktrad</VListItemTitle>
                  </VListItem>
                  <VListItem @click="addNote">
                    <template #prepend>
                      <VIcon icon="custom-plus" size="24" />
                    </template>
                    <VListItemTitle>Ny textrad</VListItemTitle>
                  </VListItem>
                  <VListItem @click="discount">
                    <template #prepend>
                      <VIcon icon="custom-plus" size="24" />
                    </template>
                    <VListItemTitle>Skatteavdrag för ROT / RUT</VListItemTitle>
                  </VListItem>
                </VList>
              </VCard>
            </VDialog>

            <span class="d-flex align-center gap-1 text-black">
              <VIcon icon="custom-circle-help" size="16" />
              Svep åt vänster för att se allt.
            </span>

            <draggable
              class="draggable-mobile mb-4"
              v-model="invoice.details"
              tag="div"
              item-key="index"
              handle=".drag-handle"
              @start="onStart"
              @end="onEnd"
            >
              <template #item="{ element, index }">
                <div class="draggable-item draggable-item-mobile mb-4">
                  <VIcon icon="custom-grabber" size="24" class="drag-handle" />
                  <div class="d-flex w-100" v-if="element?.note !== undefined">
                    <div class="form-field w-100">
                      <label class="text-sm">Notera</label>
                      <VTextarea
                        v-model="element.note"
                        label="Notera"
                        placeholder="Notera"
                        rows="2"
                        class="mt-1 w-100"
                        @input="editNote"
                      />
                    </div>
                    <VBtn @click="removeNote(index)">
                      <VIcon icon="custom-close" size="16" />
                    </VBtn>
                  </div>
                  <template v-else>
                    <div
                      class="d-flex flex-column justify-space-between p-0"
                      v-if="selectedDiscount > 0"
                    >
                      <VCheckbox
                        v-if="selectedDiscount > 0"
                        class="pe-2"
                        v-model="element[6]"
                        color="primary"
                        @update:modelValue="$emit('edit')"
                      />
                    </div>
                    <div class="w-100">
                      <div class="add-products-header d-md-flex w-100">
                        <table class="w-100">
                          <thead>
                            <tr>
                              <template
                                v-for="(invoice, index) in invoices"
                                :key="invoice.id"
                              >
                                <td
                                  :style="`width: ${
                                    invoice.type_id === 1 ? '40' : '15'
                                  }%;`"
                                >
                                  <span class="">
                                    {{ invoice.name }}
                                  </span>
                                </td>
                              </template>
                              <td style="width: 15%">
                                <span class=""> Rabatt </span>
                              </td>
                            </tr>
                          </thead>
                        </table>
                      </div>
                      <!-- 👉 Left Form -->
                      <div class="add-products-form flex-grow-1">
                        <table class="w-100">
                          <thead>
                            <tr>
                              <template
                                v-for="(invoice, index) in invoices"
                                :key="invoice.id"
                              >
                                <td
                                  :style="`width: ${
                                    invoice.type_id === 1 ? '40' : '15'
                                  }%;`"
                                  class="pe-2"
                                  style="vertical-align: top"
                                >
                                  <div class="form-field">
                                    <VTextarea
                                      v-if="invoice.type_id === 1"
                                      v-model="element[invoice.id]"
                                      rows="2"
                                      :placeholder="invoice.description"
                                      persistent-placeholder
                                      :readonly="element.disabled"
                                      :rules="[requiredValidator]"
                                    />
                                    <VTextField
                                      v-if="invoice.type_id === 2"
                                      v-model="element[invoice.id]"
                                      type="number"
                                      :min="1"
                                      :readonly="element.disabled"
                                      :rules="[requiredValidator]"
                                      @input="$emit('edit')"
                                      @blur="() => handleBlur(element)"
                                    />
                                    <VTextField
                                      v-if="invoice.type_id === 3"
                                      v-model="element[invoice.id]"
                                      type="number"
                                      :min="0"
                                      :step="0.01"
                                      :readonly="element.disabled"
                                      @input="$emit('edit')"
                                      :rules="[requiredValidator]"
                                      :disabled="invoice.name === 'Belopp'"
                                      @focus="() => handleFocus(element, invoice.id)"
                                      @blur="() => handleBlur(element)"
                                    />
                                  </div>
                                </td>
                              </template>
                              <td
                                style="width: 15%; vertical-align: top"
                                class="pe-2"
                              >
                                <div class="form-field">
                                  <VTextField
                                    :disabled="selectedDiscount > 0"
                                    v-model="element[5]"
                                    type="number"
                                    :min="0"
                                    :max="100"
                                    :readonly="element.disabled"
                                    @input="$emit('edit')"
                                    @blur="() => handleBlur(element)"
                                  />
                                </div>
                              </td>
                            </tr>
                          </thead>
                        </table>
                      </div>
                      <!-- 👉 Item Actions -->
                    </div>
                    <div class="d-flex flex-column justify-space-between p-0">
                      <VBtn
                        :disabled="index === 0 ? true : false"
                        @click="deleteProduct(index)"
                      >
                        <VIcon icon="custom-close" size="16" />
                      </VBtn>
                    </div>
                  </template>
                </div>
              </template>
            </draggable>

            <div class="my-0 invoice-box">
              <table class="w-100 text-black">
                <tbody>
                  <tr>
                    <td class="pe-4 pb-4">Netto</td>
                    <td
                      class="text-bold pb-4"
                      :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'"
                    >
                      {{ formatNumber(subtotal) }} kr
                    </td>
                  </tr>
                  <tr>
                    <td class="pe-4">Moms</td>
                    <td
                      :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'"
                    >
                      <div class="d-flex gap-4 align-center justify-end">
                        <div class="form-field">
                          <VSelect
                            v-model="selectedTax"
                            :items="taxOptions"
                            label="Moms"
                            @update:modelValue="handleTaxChange"
                            style="width: 125px"
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
                            style="width: 125px"
                          />
                        </div>
                        %
                      </div>
                    </td>
                  </tr>
                  <tr v-if="selectedDiscount > 0">
                    <td class="pe-4">
                      Preliminär skattereduktion {{ selectedDiscount }}% av
                      {{ formatNumber(subtotal) }} kr:
                    </td>
                    <td
                      class="text-bold"
                      :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'"
                    >
                      - {{ formatNumber(amountDiscount) }} kr
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
                      :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'"
                    >
                      {{ formatNumber(total) }} kr
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <VDivider class="ma-0 ghost-divider" />

            <VRow class="gap-4">
              <VCol cols="6" class="d-flex flex-column flex-1">
                <span class="me-2 text-bold text-footer"> Org.nr. </span>
                <span class="text-footer">
                  {{ company.organization_number }}
                </span>

                <span
                  class="me-2 mt-4 text-bold text-footer"
                  v-if="company.vat"
                >
                  Vat
                </span>

                <span class="text-footer"> {{ company.vat }} </span>
                <span
                  class="me-2 mt-4 text-bold text-footer"
                  v-if="company.bic"
                >
                  BIC
                </span>

                <span class="text-footer" v-if="company.bic">
                  {{ company.bic }}
                </span>

                <span class="me-2 mt-4 text-bold text-footer" v-if="company.bank">
                  Bank
                </span>
                <span class="text-footer" v-if="company.bank">
                  {{ company.bank }}
                </span>

                <span
                  class="me-2 mt-4 text-bold text-footer"
                  v-if="company.iban"
                >
                  Bankgiro
                </span>
                <span class="text-footer"> {{ company.iban }} </span>

                <span
                  class="me-2 mt-4 text-bold text-footer"
                  v-if="company.plus_spin"
                >
                  Plusgiro
                </span>
                <span class="text-footer" v-if="company.plus_spin">
                  {{ company.plus_spin }}
                </span>

                <span
                  class="me-2 mt-4 text-bold text-footer"
                  v-if="company.account_number"
                >
                  Kontonummer
                </span>
                <span class="text-footer" v-if="company.account_number">
                  {{ company.account_number }}
                </span>

              </VCol>

              <VCol cols="6" class="d-flex flex-column flex-1">
                <span
                  class="me-2 text-bold text-footer"
                  v-if="company.iban_number"
                >
                  Iban nummer
                </span>
                <span class="text-footer" v-if="company.iban_number">
                  {{ company.iban_number }}
                </span>

                <span
                  class="me-2 mt-4 text-bold text-footer"
                  v-if="company.company"
                >
                  Företagsnamn
                </span>
                <span class="text-footer" v-if="company.company">
                  {{ company.company }}
                </span>

                <span
                  class="me-2 mt-4 text-bold text-footer"
                  v-if="company.phone"
                >
                  Telefon
                </span>
                <span class="text-footer" v-if="company.phone">
                  {{ company.phone }}
                </span>

                <span class="me-2 mt-4 text-bold text-footer"> Adress </span>
                  <span class="d-flex flex-column">
                    <span class="text-footer">{{ company.address }}</span>
                    <span class="text-footer">{{ company.postal_code }}, {{ company.street }}</span>
                  </span>
                <span
                  class="me-2 mt-4 text-bold text-footer"
                  v-if="company.swish"
                >
                  Swish
                </span>
                <span class="text-footer" v-if="company.swish">
                  {{ company.swish }}
                </span>

                <span class="me-2 mt-4 text-bold text-footer" v-if="company.link">
                  Webbplats
                </span>

                <span class="text-footer" v-if="company.link">
                  {{ company.link }}
                </span>

                <span class="me-2 mt-4 text-bold text-footer">
                  Företagets e-post
                </span>
                <span class="text-footer"> {{ company.email }} </span>
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem
            class="d-flex flex-column gap-2 pa-6 pb-0 w-100"
            value="forhandsgranska"
          >
            <div class="d-flex flex-column gap-2 bg-white pa-4 mb-6 rounded-2" v-if="windowWidth < 1024 && billing?.file">
              <div class="d-flex gap-4 bg-white align-center flex-row flex-nowrap pb-0 justify-between">
                <div class="d-flex align-center font-blauer" v-if="windowWidth < 1024">
                  <h2 class="faktura-title">Faktura #{{ billing.invoice_id }}</h2>
                </div>
                <div
                  v-if="windowWidth < 1024 && billing?.file"
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(billing.state.id)?.class}`"
                >
                  {{ billing.state.name }}
                </div>
              </div>
              <VDivider v-if="windowWidth < 1024" />
              <div v-if="windowWidth < 1024" class="invoice-panel">
                
                <VuePdfEmbed
                  v-if="billing?.file"
                  :source="pdfSource"
                  class="w-auto m-auto"
                />
                <img
                  v-else
                  :src="sampleFaktura"
                  class="w-100"
                />
              </div>
            </div>
          </VWindowItem>
        </VWindow>
      </VCardText>
    </section>
  </VCard>

  <VDialog
    v-if="canEditInvoiceId"
    v-model="isInvoiceIdAlertVisible"
    persistent
    class="action-dialog"
  >
    <VBtn
      icon
      class="btn-white close-btn px-1"
      @click="isInvoiceIdAlertVisible = false"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <VCard>
      <VCardText class="dialog-title-box d-flex" :style="windowWidth < 1024 ? '' : 'gap: 4px'">
        <VIcon size="32" icon="custom-factura-outlined" class="action-icon" />
        <div class="dialog-title">
          Välj ditt startnummer för fakturor
        </div>
      </VCardText>
      <VCardText class="dialog-text">
        Du kan själv välja startnummer för dina fakturor. 
        Om inget nummer anges börjar numreringen från 01. Detta kan ändras i inställningarna.
      </VCardText>

      <VCardText class="dialog-text my-4">
        <div class="billing-empty">
          <div class="d-flex align-center gap-2">
            <span>#</span>
            <div class="d-flex flex-column">
              <span>Faktura nr</span>
              <span class="text-black">1</span>
            </div>
          </div>
          <div class="status-chip status-chip-disabled">
            Standard
          </div>
        </div>
        
      </VCardText>

      <VCardText class="dialog-text">
        Detta meddelande visas bara en gång.
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn class="btn-light" :to="{ name: 'dashboard-settings-billings' }">
          Gå till inställningar
        </VBtn>

        <VBtn class="btn-gradient" @click="isInvoiceIdAlertVisible = false">
          Förstår
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Alert: Must save before preview -->
  <VDialog 
    v-model="isAlertPreviewVisible" 
    persistent
    class="action-dialog"
  >
    <VBtn
      icon
      class="btn-white close-btn"
      @click="isAlertPreviewVisible = false"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <VCard>
      <VCardText class="dialog-title-box">
        <VIcon size="32" icon="custom-alarm" class="action-icon" />
        <div class="dialog-title">
          Spara fakturan först
        </div>
      </VCardText>
      <VCardText class="dialog-text">
        Du måste spara fakturan innan du kan förhandsgranska den. Klicka på "Skapa faktura" för att spara och förhandsgranska.
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn class="btn-gradient" @click="isAlertPreviewVisible = false">
          Okej
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Cancel discount -->
  <VDialog 
    v-model="isConfirmDiscountVisible" 
    persistent
    class="action-dialog"
  >
    <VBtn
      icon
      class="btn-white close-btn"
      @click="cancelDiscount"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <!-- Dialog Content -->
    <VCard>
      <VCardText class="dialog-title-box">
        <VIcon size="32" icon="custom-discount-1" class="action-icon" />
        <div class="dialog-title">
          Tillämpa skatteavdrag
        </div>
      </VCardText>
      <VCardText class="dialog-text d-flex" :class="windowWidth < 1024 ? 'flex-column gap-1' : ''">
        Välj preliminär skatteavdrag

        <VSpacer />

        <div class="form-field">
          <VSelect
            v-model="selectedDiscountTemp"
            :items="discountOptions"
            label="Skattereduktion"
            append-icon="tabler-percentage"
          />
        </div>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn class="btn-light" @click="cancelDiscount">
          Avbryt
        </VBtn>
        <VBtn class="btn-gradient" @click="saveDiscount"> Spara </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Confirm discount -->
  <VDialog 
    v-model="isAlertDiscountVisible"
    persistent
    class="action-dialog"
  >
    <!-- Dialog close btn -->
    <VBtn
      icon
      class="btn-white close-btn"
      @click="isAlertDiscountVisible = false"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <!-- Dialog Content -->
    <VCard>
      <VCardText class="dialog-title-box">
        <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
        <div class="dialog-title">Tillämpa rabatt</div>
      </VCardText>
      <VCardText class="dialog-text">
        Du kan inte tillämpa två rabatter på en faktura.
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-gradient" @click="isAlertDiscountVisible = false"> Okej </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>

.billing-empty {
  display: flex;
  justify-content: space-between;
  border-radius: 8px;
  padding-top: 8px;
  padding-right: 16px;
  padding-bottom: 8px;
  padding-left: 16px;
  background-color: #F6F6F6;
  border: 1px solid #E7E7E7;
  color: #878787;
  align-items: center;
  font-size: 14px;
}

:deep(.deleted-supplier-option .deleted-supplier-text),
.deleted-supplier-text {
  text-decoration: line-through;
  opacity: 0.75;
}

:deep(.deleted-supplier-selection .v-autocomplete__selection-text) {
  text-decoration: line-through;
  opacity: 0.75;
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

.draggable-mobile {
  overflow: auto;
  -webkit-overflow-scrolling: touch;
}

.draggable-item-mobile {
  width: 700px;
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

:deep(.invoice-panel .vue-pdf-embed) {
  display: block;
  width: 100%;
  max-width: 100%;
}

:deep(.invoice-panel .vue-pdf-embed > div) {
  width: 100% !important;
}

:deep(.invoice-panel .vue-pdf-embed > div + div) {
  margin-top: 12px;
}

:deep(.invoice-panel .vue-pdf-embed__page) {
  margin: 0 auto;
}

:deep(.invoice-panel .vue-pdf-embed canvas) {
  display: block;
  width: 100% !important;
  height: auto !important;
}

.invoice-box {
  border-radius: 16px;
  padding: 16px;
  gap: 8px;
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

@media (max-width: 1023px) {
  .invoice-panel {
    margin: 0;
    padding: 0 !important;
  }
}

@media (max-width: 767px) {
  .faktura {
    font-size: 16px;
  }
}
</style>
<style lang="css">
.invoice-logo {
  width: 200px;
  height: 100px;
  background: white;
  border-radius: 8px;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.invoice-logo .invoice-logo-img {
  display: block;
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
  object-fit: contain;
}
@media (max-width: 1024px) {
  .fix-header {
    position: fixed;
    z-index: 1;
    width: 100%;
  }
}

.margin-top-fill {
  margin-top: 120px;
}

.v-tabs.v-tabs--horizontal:not(.v-tabs-pill) .v-btn {
  flex: 1 1;
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
.ghost-divider {
  border-color: #f6f6f6 !important;
}
</style>
