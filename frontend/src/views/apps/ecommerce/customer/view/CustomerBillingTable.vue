<script setup>
import { useBillingsStores } from "@/stores/useBillings";
import { formatNumber } from "@/@core/utils/formatters";
import { themeConfig } from "@themeConfig";
import router from "@/router";

const props = defineProps({
  client_id: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(["alert", "loading"]);
const billingsStores = useBillingsStores();

const billings = ref([]);
const searchQuery = ref("");
const rowPerPage = ref(10);
const currentPage = ref(1);
const totalPages = ref(1);
const totalBillings = ref(0);
const isConfirmStateDialogVisible = ref(false);
const isConfirmSendMailVisible = ref(false);
const emailDefault = ref(true);
const selectedTags = ref([]);
const existingTags = ref([]);
const selectedBilling = ref({});
const isValid = ref(false);
const userData = ref(null);
const role = ref(null);
const tabBilling = ref("fakturor");

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const paginationData = computed(() => {
  const firstIndex = billings.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    billings.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalBillings.value} resultat`;
  // return `Visar ${firstIndex} till ${lastIndex} av ${totalBillings.value} register`;
});

watchEffect(fetchData);

async function fetchData() {
  let data = {
    search: searchQuery.value,
    orderByField: "id",
    orderBy: "desc",
    limit: rowPerPage.value,
    page: currentPage.value,
    client_id: props.client_id,
  };

  await billingsStores.fetchBillings(data);

  billings.value = billingsStores.getBillings;
  totalPages.value = billingsStores.last_page;
  totalBillings.value = billingsStores.billingsTotalCount;

  userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
  role.value = userData.value.roles[0].name;

  billings.value.forEach((billing) => {
    billing.checked = false;
    billing.sent = false;
  });
}

const updateBilling = (billingData) => {
  isConfirmStateDialogVisible.value = true;
  selectedBilling.value = { ...billingData };
};

const showBilling = (billingData) => {
  router.push({
    name: "dashboard-admin-billings-id",
    params: { id: billingData.id },
  });
};

const editBilling = (billingData) => {
  router.push({
    name: "dashboard-admin-billings-edit-id",
    params: { id: billingData.id },
  });
};

const openLink = function (billingData) {
  window.open(themeConfig.settings.urlStorage + billingData.file);
};

const updateState = async () => {
  isConfirmStateDialogVisible.value = false;

  emit("loading", true);

  let res = await billingsStores.updateState(selectedBilling.value.id);

  emit("loading", false);
  selectedBilling.value = {};

  advisor.value = {
    type: res.data.success ? "success" : "error",
    message: res.data.success ? "Fakturan uppdaterad!" : res.data.message,
    show: true,
  };

  emit("alert", advisor);

  setTimeout(() => {
    advisor.value = {
      type: "",
      message: "",
      show: false,
    };

    emit("alert", advisor);
  }, 3000);

  await fetchData();

  return true;
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
  router.push({
    name: "dashboard-admin-billings-duplicate-id",
    params: { id: billing.id },
  });
};

const credit = (billing) => {
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
    emit("loading", true);

    let data = {
      id: selectedBilling.value.id,
      emailDefault: emailDefault.value,
      emails: selectedTags.value,
    };

    let res = await billingsStores.sendMails(data);

    emit("loading", false);

    advisor.value = {
      type: res.data.success ? "success" : "error",
      message: res.data.success ? "Fakturan är skickad!" : res.data.message,
      show: true,
    };

    emit("alert", advisor);

    setTimeout(() => {
      selectedTags.value = [];
      existingTags.value = [];
      emailDefault.value = true;

      advisor.value = {
        type: "",
        message: "",
        show: false,
      };

      emit("alert", advisor);
    }, 3000);

    await fetchData();

    return true;
  }
};
</script>

<template>
  <section>
    <VCard title="">
      <VCardText class="d-flex align-center flex-wrap gap-4 pa-0">
        <!-- <VSpacer class="d-none d-md-block" />

        <div class="d-flex align-center w-100 w-md-auto visa-select">
          <span class="text-no-wrap me-3">Visa:</span>
          <VSelect
            v-model="rowPerPage"
            density="compact"
            variant="outlined"
            class="w-100"
            :items="[10, 20, 30, 50]"
          />
        </div>

        <VSpacer class="d-none d-md-block" />

        <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto">

          <div style="width: 20rem">
            <VTextField
              v-model="searchQuery"
              placeholder="Sök"
              density="compact"
              clearable
            />
          </div>
        </div> -->
      </VCardText>

      <VCardText
        class="d-flex flex-column border rounded-lg pa-4 gap-6 billing-panel"
      >
        <div class="d-flex filter-bar">
          <VTabs v-model="tabBilling" class="billing-tabs" show-arrows="false">
            <VTab value="fakturor">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
              >
                <mask id="path-1-inside-1_901_411" fill="white">
                  <path
                    d="M17.76 7.68H12V1.92C12 1.65504 12.2146 1.44 12.48 1.44C12.7454 1.44 12.96 1.65504 12.96 1.92V6.72H17.76C18.0254 6.72 18.24 6.93504 18.24 7.2C18.24 7.46496 18.0254 7.68 17.76 7.68Z"
                  />
                </mask>
                <path
                  d="M17.76 7.68H12V1.92C12 1.65504 12.2146 1.44 12.48 1.44C12.7454 1.44 12.96 1.65504 12.96 1.92V6.72H17.76C18.0254 6.72 18.24 6.93504 18.24 7.2C18.24 7.46496 18.0254 7.68 17.76 7.68Z"
                  fill="#454545"
                />
                <path
                  d="M12 7.68H11V8.68H12V7.68ZM12.96 6.72H11.96V7.72H12.96V6.72ZM17.76 7.68V6.68H12V7.68V8.68H17.76V7.68ZM12 7.68H13V1.92H12H11V7.68H12ZM12 1.92H13C13 2.20668 12.7675 2.44 12.48 2.44V1.44V0.440002C11.6616 0.440002 11 1.10341 11 1.92H12ZM12.48 1.44V2.44C12.1925 2.44 11.96 2.20668 11.96 1.92H12.96H13.96C13.96 1.10341 13.2984 0.440002 12.48 0.440002V1.44ZM12.96 1.92H11.96V6.72H12.96H13.96V1.92H12.96ZM12.96 6.72V7.72H17.76V6.72V5.72H12.96V6.72ZM17.76 6.72V7.72C17.4725 7.72 17.24 7.48668 17.24 7.2H18.24H19.24C19.24 6.38341 18.5784 5.72 17.76 5.72V6.72ZM18.24 7.2H17.24C17.24 6.91333 17.4725 6.68 17.76 6.68V7.68V8.68C18.5784 8.68 19.24 8.0166 19.24 7.2H18.24Z"
                  fill="#454545"
                  mask="url(#path-1-inside-1_901_411)"
                />
                <mask id="path-3-inside-2_901_411" fill="white">
                  <path
                    d="M5.28027 10.56H12.0387L11.1939 11.52H5.28027V10.56Z"
                  />
                </mask>
                <path
                  d="M5.28027 10.56H12.0387L11.1939 11.52H5.28027V10.56Z"
                  fill="#454545"
                />
                <path
                  d="M5.28027 10.56V9.56H4.28027V10.56H5.28027ZM12.0387 10.56L12.7894 11.2206L14.2507 9.56H12.0387V10.56ZM11.1939 11.52V12.52H11.6459L11.9446 12.1806L11.1939 11.52ZM5.28027 11.52H4.28027V12.52H5.28027V11.52ZM5.28027 10.56V11.56H12.0387V10.56V9.56H5.28027V10.56ZM12.0387 10.56L11.288 9.89937L10.4432 10.8594L11.1939 11.52L11.9446 12.1806L12.7894 11.2206L12.0387 10.56ZM11.1939 11.52V10.52H5.28027V11.52V12.52H11.1939V11.52ZM5.28027 11.52H6.28027V10.56H5.28027H4.28027V11.52H5.28027Z"
                  fill="#454545"
                  mask="url(#path-3-inside-2_901_411)"
                />
                <mask id="path-5-inside-3_901_411" fill="white">
                  <path
                    d="M9.11067 16.32C9.18747 16.4736 9.28347 16.6128 9.39387 16.7472C9.29787 16.9248 9.21147 17.1024 9.15387 17.28H5.28027V16.32H9.11067Z"
                  />
                </mask>
                <path
                  d="M9.11067 16.32C9.18747 16.4736 9.28347 16.6128 9.39387 16.7472C9.29787 16.9248 9.21147 17.1024 9.15387 17.28H5.28027V16.32H9.11067Z"
                  fill="#454545"
                />
                <path
                  d="M9.11067 16.32L10.0051 15.8728L9.72871 15.32H9.11067V16.32ZM9.39387 16.7472L10.2736 17.2227L10.5931 16.6317L10.1666 16.1125L9.39387 16.7472ZM9.15387 17.28V18.28H9.88083L10.1051 17.5885L9.15387 17.28ZM5.28027 17.28H4.28027V18.28H5.28027V17.28ZM5.28027 16.32V15.32H4.28027V16.32H5.28027ZM9.11067 16.32L8.21625 16.7672C8.33451 17.0037 8.47625 17.2055 8.62115 17.3819L9.39387 16.7472L10.1666 16.1125C10.0907 16.0201 10.0404 15.9435 10.0051 15.8728L9.11067 16.32ZM9.39387 16.7472L8.51417 16.2717C8.40831 16.4675 8.28821 16.7077 8.20265 16.9715L9.15387 17.28L10.1051 17.5885C10.1347 17.4971 10.1874 17.3821 10.2736 17.2227L9.39387 16.7472ZM9.15387 17.28V16.28H5.28027V17.28V18.28H9.15387V17.28ZM5.28027 17.28H6.28027V16.32H5.28027H4.28027V17.28H5.28027ZM5.28027 16.32V17.32H9.11067V16.32V15.32H5.28027V16.32Z"
                  fill="#454545"
                  mask="url(#path-5-inside-3_901_411)"
                />
                <path
                  d="M5.28027 13.44H9.50427C9.23547 13.7136 9.04347 14.04 8.93307 14.4H5.28027V13.44Z"
                  fill="#454545"
                />
                <path
                  d="M17.7604 22.08H2.40043V1.92002H12.2836L17.7604 7.39682V9.14882C18.0964 8.93282 18.418 8.74562 18.7204 8.58242V7.00322L12.6772 0.960022H1.44043V23.04H18.7204V21.4176C18.3892 21.3312 18.0676 21.2064 17.7604 21.0528V22.08Z"
                  fill="#454545"
                />
                <path
                  d="M23.5586 15.984L23.0114 15.7968L20.6306 13.0704L20.4482 12.8592L20.0018 12.3456C20.0834 12.264 20.1602 12.1824 20.237 12.1056C20.2946 12.0432 20.3522 11.9856 20.405 11.9232C20.4962 11.8272 20.5826 11.736 20.6642 11.6448C20.7554 11.5392 20.8466 11.4336 20.933 11.3328C21.0194 11.232 21.101 11.1312 21.173 11.0352C21.2354 10.9536 21.2978 10.8768 21.3506 10.8C21.4274 10.6944 21.4946 10.5936 21.557 10.4976C21.6098 10.4112 21.6626 10.3296 21.701 10.2528C21.7442 10.176 21.7778 10.104 21.8114 10.032C21.845 9.96478 21.869 9.90238 21.8882 9.83998C21.9026 9.79198 21.9122 9.74877 21.9218 9.70557C21.9314 9.67197 21.941 9.63838 21.941 9.60958C21.9794 9.35998 21.9266 9.15838 21.7922 8.99038C21.7538 8.94718 21.7106 8.90398 21.6674 8.87038C21.653 8.85598 21.6386 8.84638 21.6194 8.84158C21.5858 8.81758 21.5522 8.79837 21.5186 8.78397C21.4994 8.77437 21.4754 8.76478 21.4562 8.76478C21.4178 8.75038 21.3794 8.74078 21.341 8.73118C21.2786 8.71678 21.2114 8.71198 21.1394 8.71198C21.0338 8.71198 20.9138 8.72638 20.789 8.75038C20.6594 8.77918 20.5202 8.81758 20.3714 8.87038C20.237 8.91838 20.0882 8.97598 19.9346 9.04798C19.7474 9.12958 19.541 9.23037 19.325 9.34557C19.133 9.44637 18.9314 9.56158 18.7202 9.68638C18.461 9.83998 18.1874 10.008 17.9042 10.1952C17.8562 10.2288 17.8082 10.2576 17.7602 10.2912C17.7026 10.3296 17.645 10.368 17.5874 10.4064C17.4098 10.5264 17.2322 10.6512 17.045 10.7856C17.0258 10.7808 17.0018 10.776 16.9778 10.7712C16.4786 10.7088 16.0562 10.6656 15.6338 10.6272C15.6098 10.6272 15.5858 10.6224 15.5618 10.6224C15.1298 10.584 14.6978 10.5456 14.1842 10.4784C14.1218 10.4736 14.0642 10.4688 14.0066 10.4688C13.8098 10.4688 13.613 10.512 13.4402 10.5984C13.2722 10.6704 13.1234 10.7808 12.9986 10.9248L12.4754 11.52L10.7858 13.44L10.277 14.0208C10.1522 14.1312 10.0514 14.2608 9.9746 14.4C9.8642 14.592 9.797 14.8032 9.7778 15.0288C9.7586 15.3264 9.821 15.624 9.9602 15.8784C10.0082 15.9696 10.0658 16.0512 10.133 16.1328C10.1906 16.2 10.253 16.2624 10.325 16.32C10.3682 16.3584 10.4114 16.3872 10.4594 16.416C10.5266 16.464 10.6034 16.5072 10.6802 16.536C10.6418 16.584 10.6082 16.632 10.5746 16.68C10.4786 16.8144 10.3922 16.944 10.3202 17.0688C10.277 17.1408 10.2386 17.2128 10.205 17.28C10.1906 17.304 10.1762 17.3328 10.1666 17.3568C10.157 17.3712 10.1522 17.3856 10.1474 17.4C10.1186 17.4576 10.0946 17.5152 10.0754 17.5728C10.0562 17.6256 10.037 17.6784 10.0226 17.736C10.0082 17.784 9.9986 17.8272 9.9938 17.8704C9.989 17.8992 9.9842 17.9232 9.9842 17.952C9.9794 17.9664 9.9794 17.9856 9.9794 18V18.048C9.9794 18.0864 9.9842 18.12 9.989 18.1488C9.989 18.1728 9.9938 18.1968 10.0034 18.2208L9.5522 18.5856C9.4514 18.672 9.437 18.8208 9.5186 18.9264C9.5666 18.984 9.6338 19.0128 9.7058 19.0128C9.7586 19.0128 9.8114 18.9984 9.8546 18.96L10.2914 18.6048C10.3826 18.6624 10.5122 18.7056 10.7042 18.7056C10.7522 18.7056 10.805 18.7008 10.8626 18.696C10.8866 18.6912 10.9106 18.6912 10.9346 18.6864C10.9538 18.6864 10.973 18.6816 10.997 18.6816C11.0018 18.6768 11.0114 18.6768 11.0162 18.6768C11.1506 18.648 11.309 18.6096 11.4914 18.5472C11.4962 18.5472 11.501 18.5424 11.5058 18.5424C11.5106 18.5424 11.5154 18.5376 11.525 18.5328C11.5826 18.5136 11.645 18.4944 11.7074 18.4656C11.7842 18.4368 11.8658 18.4032 11.9474 18.3648C12.0914 18.3024 12.245 18.2304 12.4082 18.144C12.5042 18.1008 12.6002 18.048 12.701 17.9904C12.797 17.9376 12.9026 17.88 13.0082 17.8176C13.0178 17.8128 13.0274 17.808 13.037 17.7984C13.0658 17.784 13.0946 17.7648 13.1234 17.7504C13.2002 17.7072 13.277 17.6592 13.3586 17.6064C13.4498 17.5584 13.5458 17.496 13.6418 17.4336C13.7186 17.3856 13.7954 17.3376 13.8722 17.2848C14.0114 17.1984 14.1554 17.1024 14.3042 17.0016C14.4242 16.92 14.549 16.8336 14.6786 16.7424L15.3218 17.472L16.805 19.1424C16.8914 19.2384 16.9826 19.3296 17.0738 19.4208C17.165 19.512 17.2658 19.5936 17.3666 19.6704C17.4338 19.728 17.501 19.776 17.573 19.8288C17.5874 19.8384 17.6018 19.848 17.6162 19.8576C17.6594 19.8864 17.6978 19.9152 17.741 19.9392C17.741 19.944 17.7458 19.944 17.7458 19.944C17.7506 19.9488 17.7554 19.9488 17.7602 19.9536C17.909 20.0496 18.0626 20.136 18.2258 20.2128C18.245 20.2224 18.2594 20.232 18.2786 20.2368L18.2834 20.2416C18.3218 20.256 18.3602 20.2752 18.3986 20.2896C18.4898 20.3328 18.5858 20.3664 18.6818 20.4C18.6962 20.4048 18.7058 20.4096 18.7202 20.4144C18.9554 20.496 19.2002 20.5584 19.4498 20.5968L22.301 21.0576C22.325 21.0624 22.3538 21.0672 22.3778 21.0672C22.6082 21.0672 22.8146 20.8992 22.853 20.664C22.8962 20.4 22.7186 20.1552 22.4546 20.112L19.6034 19.6512C19.5026 19.6368 19.4018 19.6128 19.3058 19.5888C19.2146 19.5648 19.1282 19.5408 19.0418 19.512C19.0322 19.5072 19.0178 19.5024 19.0082 19.4976C18.9986 19.4928 18.9938 19.4928 18.9842 19.488C18.9746 19.4832 18.965 19.4832 18.9554 19.4784C18.8786 19.4544 18.8066 19.4256 18.7346 19.392C18.7298 19.392 18.725 19.3872 18.7202 19.3872C18.5426 19.3056 18.3698 19.2096 18.2066 19.0992C18.0482 18.9936 17.8994 18.8736 17.7602 18.744C17.6786 18.6672 17.597 18.5904 17.525 18.504L16.0034 16.7952V16.7904L14.6978 15.3216C14.549 15.1296 14.549 14.9376 14.5634 14.8368C14.5778 14.712 14.6402 14.6016 14.7218 14.5392C14.789 14.4912 14.8754 14.4672 14.9666 14.4672C15.077 14.4672 15.197 14.5008 15.2978 14.5632L15.3218 14.5776L15.797 14.8848L17.405 15.9168L17.7602 16.1472L18.0434 16.3296L18.7202 15.8064L19.325 15.3408C19.5362 15.1776 19.5746 14.8752 19.4162 14.664C19.3922 14.6352 19.3634 14.6064 19.3346 14.5872C19.3106 14.568 19.2914 14.5536 19.2626 14.5392H19.2578C19.2482 14.5296 19.2338 14.5296 19.2242 14.5248C19.2098 14.5152 19.1906 14.5104 19.1762 14.5056C19.1666 14.5008 19.157 14.496 19.1426 14.496C19.133 14.496 19.1234 14.496 19.1138 14.4912C19.0898 14.4816 19.061 14.4816 19.0322 14.4816C18.9314 14.4816 18.8258 14.5104 18.7394 14.5776L18.7202 14.592L17.9954 15.1536L17.7602 15.0048L17.3426 14.7312C17.4866 14.616 17.621 14.5056 17.7602 14.3856C18.053 14.1408 18.3314 13.9008 18.6002 13.6608C18.605 13.656 18.6098 13.6512 18.6194 13.6464C18.6482 13.6224 18.6818 13.5936 18.7106 13.5696C18.7106 13.5648 18.7154 13.5648 18.7154 13.56L18.7202 13.5552C18.7586 13.5216 18.8018 13.488 18.8402 13.4496C19.0034 13.3056 19.1666 13.1616 19.3154 13.0176L19.9442 13.7376L22.4546 16.6224L23.2514 16.8912C23.3042 16.9104 23.3522 16.9152 23.405 16.9152C23.6066 16.9152 23.7938 16.7904 23.861 16.5888C23.9426 16.3392 23.8082 16.0656 23.5586 15.984ZM20.5874 9.80638L20.741 9.99838C20.6066 10.2096 20.4098 10.4736 20.141 10.7856L19.6946 10.2336C20.0546 10.032 20.3522 9.89758 20.5874 9.80638ZM10.9298 14.7264L13.7186 11.5584C13.757 11.52 13.8002 11.4864 13.8482 11.4624C13.9106 11.4336 13.9874 11.4192 14.0594 11.4288C14.2274 11.4528 14.3858 11.472 14.5394 11.4864C14.8706 11.5248 15.1778 11.5536 15.4802 11.5776C15.6242 11.592 15.773 11.6016 15.9218 11.6208C15.7826 11.7264 15.6434 11.8368 15.4994 11.952C15.3554 12.0624 15.2066 12.1824 15.0626 12.2976C14.9954 12.3552 14.9186 12.4176 14.8322 12.4848C14.8034 12.5088 14.7746 12.5328 14.741 12.5568C14.7074 12.5808 14.6738 12.6096 14.6402 12.6432C14.5874 12.6864 14.5346 12.7296 14.477 12.7776C14.3858 12.8544 14.285 12.936 14.1842 13.0272C14.1026 13.0944 14.0162 13.1664 13.9298 13.2432C13.7186 13.4256 13.493 13.6224 13.2674 13.8288C13.1762 13.9104 13.0802 13.9968 12.989 14.0832C12.893 14.1648 12.8018 14.256 12.7058 14.3424C12.6194 14.424 12.533 14.5104 12.4466 14.592C12.4274 14.6112 12.4082 14.6256 12.3938 14.6448C12.3458 14.688 12.3026 14.736 12.2546 14.7792C12.1826 14.8512 12.1106 14.9184 12.0434 14.9904C11.9714 15.0624 11.9042 15.1344 11.837 15.2016C11.765 15.2736 11.6978 15.3456 11.6354 15.4128C11.5442 15.5088 11.4578 15.6 11.3714 15.696H11.3666C11.3426 15.7056 11.3138 15.7056 11.2898 15.7056C11.2754 15.7056 11.2562 15.7056 11.2418 15.7008C11.093 15.6912 10.9586 15.624 10.8626 15.5088C10.7714 15.3984 10.7234 15.2544 10.7378 15.1056C10.7378 15.072 10.7426 15.0432 10.7522 15.0096C10.7618 14.976 10.7714 14.9472 10.7906 14.9136C10.8194 14.8416 10.8674 14.7792 10.9298 14.7264ZM11.2322 17.6208C11.165 17.6496 11.1026 17.6688 11.045 17.688C11.0594 17.664 11.0738 17.64 11.0882 17.616C11.117 17.568 11.1506 17.5152 11.189 17.4624C11.189 17.4576 11.1938 17.4528 11.1986 17.448V17.4432C11.3378 17.2368 11.5346 16.9824 11.789 16.6896L12.1826 17.1744C11.8226 17.3712 11.5346 17.5008 11.3138 17.592C11.309 17.592 11.2994 17.5968 11.2946 17.5968C11.2754 17.6064 11.2562 17.616 11.237 17.6208H11.2322ZM13.8338 16.1616C13.6322 16.296 13.445 16.4208 13.2674 16.5312C13.253 16.5408 13.2386 16.5504 13.229 16.5552C13.157 16.6032 13.0898 16.6464 13.0226 16.6848L12.4418 15.9648C12.5378 15.864 12.6434 15.7584 12.749 15.648C12.869 15.5328 12.9938 15.408 13.1234 15.2784C13.1474 15.2592 13.1714 15.2352 13.1954 15.2112C13.277 15.1296 13.3634 15.048 13.4546 14.9664C13.5026 14.9184 13.5506 14.8752 13.6034 14.8272C13.589 15.0384 13.6178 15.2448 13.6898 15.4464C13.7186 15.5328 13.757 15.6096 13.8002 15.6912C13.8482 15.7728 13.901 15.8544 13.9586 15.9312L14.0402 16.0224C13.9682 16.0704 13.901 16.1184 13.8338 16.1616ZM18.7202 12.2544C18.701 12.2688 18.6866 12.288 18.6674 12.3024C18.5762 12.3888 18.485 12.4752 18.389 12.5616C18.341 12.6048 18.293 12.648 18.245 12.6912C18.197 12.7344 18.1442 12.7776 18.0962 12.8256V12.8304C17.9906 12.9216 17.8802 13.0224 17.7602 13.1232C17.6018 13.2576 17.4386 13.4016 17.2658 13.5456C17.0402 13.7376 16.8002 13.9344 16.5506 14.136L16.493 14.1888L15.8114 13.752C15.7682 13.728 15.725 13.6992 15.677 13.68C15.6722 13.68 15.6674 13.6752 15.6626 13.6752C15.6242 13.6512 15.581 13.632 15.5426 13.6224C15.5042 13.6032 15.4658 13.5888 15.4274 13.5792C15.3746 13.56 15.3218 13.5456 15.2642 13.5408C15.2066 13.5264 15.149 13.5168 15.0962 13.5168C15.2786 13.3584 15.4658 13.2048 15.6626 13.0464C15.7826 12.9504 15.8978 12.8592 16.0082 12.7728C16.0754 12.7152 16.1426 12.6624 16.2098 12.6096C16.3682 12.4848 16.5266 12.3648 16.6802 12.2496C16.781 12.1728 16.877 12.096 16.973 12.0288C16.9922 12.0144 17.0066 12 17.021 11.9904C17.1506 11.8944 17.2754 11.7984 17.4002 11.712C17.5202 11.6208 17.645 11.5344 17.7602 11.4528C17.8562 11.3856 17.9522 11.3184 18.0482 11.2512C18.149 11.184 18.245 11.1168 18.341 11.0544C18.4754 10.968 18.6002 10.8864 18.7202 10.8096C18.7682 10.7808 18.8114 10.752 18.8546 10.7232L19.157 11.0928L19.4882 11.5008C19.2818 11.712 19.0514 11.9376 18.797 12.1824C18.773 12.2064 18.749 12.2304 18.7202 12.2544Z"
                  fill="#454545"
                />
              </svg>
              Fakturor
            </VTab>
            <VTab value="avtal">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
              >
                <path
                  d="M12 19.68H17.76"
                  stroke="#454545"
                  stroke-miterlimit="10"
                />
                <path
                  d="M6.71973 9.12H17.2797"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.71973 12H17.2797"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.71973 14.88H17.2797"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.71973 17.28H17.2797"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M8.63965 9.59998V17.28"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.71973 9.12V16.8"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M17.2803 9.12V16.8"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M14.8799 9.59998V17.28"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M3.36035 22.56V1.44H20.6404V22.56H3.36035Z"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.24023 6.23999H17.7602"
                  stroke="#454545"
                  stroke-miterlimit="10"
                />
              </svg>
              Avtal
            </VTab>
          </VTabs>

          <VSpacer class="d-none d-md-block" />

          <div class="search d-none d-md-block">
            <VTextField v-model="searchQuery" placeholder="Sök" clearable />
          </div>

          <VBtn class="btn-white d-block d-md-none">
            <VIcon icon="custom-filter" size="24" />
          </VBtn>
        </div>
        <VWindow v-model="tabBilling">
          <VWindowItem value="fakturor">
            <VTable v-if="!$vuetify.display.smAndDown" class="text-no-wrap">
              <!-- 👉 table head -->
              <thead>
                <tr>
                  <th scope="col"># Faktura</th>
                  <th scope="col">Kund</th>
                  <th scope="col" class="text-center">Summa</th>
                  <th scope="col" class="text-center">Fakturadatum</th>
                  <th scope="col" class="text-center">FÖrfaller</th>
                  <th scope="col" class="text-center">Status</th>
                  <!-- <th class="text-center" scope="col">Betald</th>
                  <th class="text-center" scope="col">Skickad</th> -->
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
                    <div
                      class="d-flex justify-between font-weight-medium cursor-pointer text-aqua"
                      @click="showBilling(billing)"
                    >
                      {{ billing.client.fullname ?? "" }}
                      <VIcon icon="custom-arrow-right" size="24" />
                    </div>
                  </td>
                  <td class="text-center">
                    {{ formatNumber(billing.total) ?? "0,00" }} kr
                  </td>
                  <td class="text-center">{{ billing.invoice_date }}</td>
                  <td class="text-center">{{ billing.due_date }}</td>
                  <td class="text-center">
                    <span class="status-pill">
                      {{ billing.state.name }}
                    </span>
                  </td>
                  <!-- <td class="text-center">
                    <VCheckbox
                      v-model="billing.checked"
                      color="info"
                      class="w-100 text-center d-flex justify-content-center"
                      :disabled="
                        billing.state_id === 7 || billing.state_id === 9
                      "
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
                  </td> -->
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
                          <VIcon icon="custom-dots-vertical" size="24" />
                        </VBtn>
                      </template>
                      <VList>
                        <VListItem
                          v-if="$can('edit', 'billing')"
                          @click="printInvoice(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-print" size="24" />
                          </template>
                          <VListItemTitle>Skriv ut</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('edit', 'billing')"
                          @click="openLink(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-pdf" size="24" />
                          </template>
                          <VListItemTitle>Visa som PDF</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('edit', 'billing')"
                          @click="duplicate(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-duplicate" size="24" />
                          </template>
                          <VListItemTitle>Duplicera</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="
                            $can('edit', 'billing') && billing.state_id === 8
                          "
                          @click="sendReminder(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-alarm" size="24" />
                          </template>
                          <VListItemTitle>Påminnelse</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('edit', 'billing')"
                          @click="send(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-paper-plane" size="24" />
                          </template>
                          <VListItemTitle>Skicka</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="
                            $can('edit', 'billing') &&
                            (billing.state_id === 4 || billing.state_id === 8)
                          "
                          @click="editBilling(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-edit" size="24" />
                          </template>
                          <VListItemTitle>Redigera</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="
                            $can('delete', 'billing') && billing.state_id === 7
                          "
                          @click="credit(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-waste" size="24" />
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
                    :colspan="role === 'Supplier' ? 8 : 9"
                    class="text-center"
                  >
                    Uppgifter ej tillgängliga
                  </td>
                </tr>
              </tfoot>
            </VTable>

            <VExpansionPanels
              class="expansion-panels"
              v-if="billings.length && $vuetify.display.smAndDown"
            >
              <VExpansionPanel v-for="billing in billings" :key="billing.id">
                <VExpansionPanelTitle
                  collapse-icon="custom-chevron-right"
                  expand-icon="custom-chevron-down"
                >
                  <span class="order-id">{{ billing.invoice_id }}</span>
                  <span class="title-panel">{{
                    billing.client.fullname ?? ""
                  }}</span>
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Summa</div>
                    <div class="expansion-panel-item-value">
                      {{ formatNumber(billing.total) ?? "0,00" }} kr
                    </div>
                  </div>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Fakturadatum</div>
                    <div class="expansion-panel-item-value">
                      {{ billing.invoice_date }}
                    </div>
                  </div>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Förfaller</div>
                    <div class="expansion-panel-item-value">
                      {{ billing.due_date }}
                    </div>
                  </div>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Status</div>
                    <div class="expansion-panel-item-value">
                      <span class="status-pill">{{ billing.state.name }}</span>
                    </div>
                  </div>
                  <div class="d-flex">
                    <VBtn
                      class="btn-light flex-1 mr-4"
                      @click="showBilling(billing)"
                    >
                      <VIcon icon="custom-eye" size="24" />
                      Se detaljer
                    </VBtn>

                    <VBtn
                      class="btn-light"
                      icon
                      @click="billing.actionDialog = true"
                    >
                      <VIcon icon="custom-dots-vertical" size="24" />
                    </VBtn>
                    <VDialog
                      v-model="billing.actionDialog"
                      transition="dialog-bottom-transition"
                      content-class="dialog-bottom-full-width"
                    >
                      <VCard>
                        <VList>
                          <VListItem
                            v-if="$can('edit', 'billing')"
                            @click="
                              printInvoice(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-print" size="24" />
                            </template>
                            <VListItemTitle>Skriv ut</VListItemTitle>
                          </VListItem>
                          <VListItem
                            v-if="$can('edit', 'billing')"
                            @click="
                              openLink(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-pdf" size="24" />
                            </template>
                            <VListItemTitle>Visa som PDF</VListItemTitle>
                          </VListItem>
                          <VListItem
                            v-if="$can('edit', 'billing')"
                            @click="
                              duplicate(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-duplicate" size="24" />
                            </template>
                            <VListItemTitle>Duplicera</VListItemTitle>
                          </VListItem>
                          <VListItem
                            v-if="
                              $can('edit', 'billing') && billing.state_id === 8
                            "
                            @click="
                              sendReminder(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-alarm" size="24" />
                            </template>
                            <VListItemTitle>Påminnelse</VListItemTitle>
                          </VListItem>
                          <VListItem
                            v-if="$can('edit', 'billing')"
                            @click="
                              send(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-paper-plane" size="24" />
                            </template>
                            <VListItemTitle>Skicka</VListItemTitle>
                          </VListItem>
                          <VListItem
                            v-if="
                              $can('edit', 'billing') &&
                              (billing.state_id === 4 || billing.state_id === 8)
                            "
                            @click="
                              editBilling(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-edit" size="24" />
                            </template>
                            <VListItemTitle>Redigera</VListItemTitle>
                          </VListItem>
                          <VListItem
                            v-if="
                              $can('delete', 'billing') &&
                              billing.state_id === 7
                            "
                            @click="
                              credit(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-waste" size="24" />
                            </template>
                            <VListItemTitle>Kreditera</VListItemTitle>
                          </VListItem>
                        </VList>
                      </VCard>
                    </VDialog>
                  </div>
                </VExpansionPanelText>
              </VExpansionPanel>
              <div v-if="!billings.length" class="text-center py-4">
                Uppgifter ej tillgängliga
              </div>
            </VExpansionPanels>

            <div
              class="d-block d-md-flex align-center flex-wrap mt-6 gap-4 pt-0 pb-4"
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
              />
            </div>
          </VWindowItem>
          <VWindowItem value="avtal">
            <VTable v-if="!$vuetify.display.smAndDown" class="text-no-wrap">
              <!-- 👉 table head -->
              <thead>
                <tr>
                  <th scope="col">Reg. NR</th>
                  <th scope="col">Inbytesfordon Reg. NR</th>
                  <th scope="col" class="text-center">Fakturadatum</th>
                  <th scope="col" class="text-center">Typ</th>
                  <th scope="col" class="text-center">Status</th>
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
                  <td>{{ billing.reg_nr }}</td>
                  <td>{{ billing.trade_in_reg_nr }}</td>
                  <td class="text-center">{{ billing.invoice_date }}</td>
                  <td class="text-center">{{ billing.type }}</td>
                  <td class="text-center">
                    <span class="status-pill">
                      {{ billing.state.name }}
                    </span>
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
                          <VIcon icon="custom-dots-vertical" size="24" />
                        </VBtn>
                      </template>
                      <VList>
                        <VListItem @click="showBilling(billing)">
                          <template #prepend>
                            <VIcon icon="custom-eye" size="24" />
                          </template>
                          <VListItemTitle>Se detaljer</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('edit', 'billing')"
                          @click="editBilling(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-edit" size="24" />
                          </template>
                          <VListItemTitle>Redigera</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('delete', 'billing')"
                          @click="credit(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-waste" size="24" />
                          </template>
                          <VListItemTitle>Ta bort</VListItemTitle>
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
                    :colspan="role === 'Supplier' ? 6 : 6"
                    class="text-center"
                  >
                    Uppgifter ej tillgängliga
                  </td>
                </tr>
              </tfoot>
            </VTable>

            <VExpansionPanels
              class="expansion-panels"
              v-if="billings.length && $vuetify.display.smAndDown"
            >
              <VExpansionPanel v-for="billing in billings" :key="billing.id">
                <VExpansionPanelTitle
                  collapse-icon="custom-chevron-right"
                  expand-icon="custom-chevron-down"
                >
                  <span class="order-id">{{ billing.id }}</span>
                  <span class="title-panel">{{
                    billing.client.fullname ?? ""
                  }}</span>
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Inbytesfordon Reg. NR</div>
                    <div class="expansion-panel-item-value">
                      {{ billing.trade_in_reg_nr }}
                    </div>
                  </div>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Fakturadatum</div>
                    <div class="expansion-panel-item-value">
                      {{ billing.invoice_date }}
                    </div>
                  </div>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Typ</div>
                    <div class="expansion-panel-item-value">
                      {{ billing.type }}
                    </div>
                  </div>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Status</div>
                    <div class="expansion-panel-item-value">
                      <span class="status-pill">{{ billing.state.name }}</span>
                    </div>
                  </div>
                  <div class="d-flex">
                    <VBtn
                      class="btn-light flex-1 mr-4"
                      @click="showBilling(billing)"
                    >
                      <VIcon icon="custom-eye" size="24" />
                      Se detaljer
                    </VBtn>

                    <VBtn
                      class="btn-light"
                      icon
                      @click="billing.actionDialog = true"
                    >
                      <VIcon icon="custom-dots-vertical" size="24" />
                    </VBtn>
                    <VDialog
                      v-model="billing.actionDialog"
                      transition="dialog-bottom-transition"
                      content-class="dialog-bottom-full-width"
                    >
                      <VCard>
                        <VList>
                          <VListItem
                            @click="
                              showBilling(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-eye" size="24" />
                            </template>
                            <VListItemTitle>Se detaljer</VListItemTitle>
                          </VListItem>
                          <VListItem
                            v-if="$can('edit', 'billing')"
                            @click="
                              editBilling(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-pencil" size="24" />
                            </template>
                            <VListItemTitle>Redigera</VListItemTitle>
                          </VListItem>
                          <VListItem
                            v-if="$can('delete', 'billing')"
                            @click="
                              credit(billing);
                              billing.actionDialog = false;
                            "
                          >
                            <template #prepend>
                              <VIcon icon="custom-waste" size="24" />
                            </template>
                            <VListItemTitle>Ta bort</VListItemTitle>
                          </VListItem>
                        </VList>
                      </VCard>
                    </VDialog>
                  </div>
                </VExpansionPanelText>
              </VExpansionPanel>
              <div v-if="!billings.length" class="text-center py-4">
                Uppgifter ej tillgängliga
              </div>
            </VExpansionPanels>

            <div
              class="d-block d-md-flex align-center flex-wrap mt-6 gap-4 pt-0 pb-4"
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
              />
            </div>
          </VWindowItem>
        </VWindow>
      </VCardText>
    </VCard>

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
          Är du säker på att du vill uppdatera fakturans status?
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
.text-center {
  text-align: center !important;
}

.justify-content-center {
  justify-content: center !important;
}

.v-input--disabled svg rect {
  fill: #28c76f !important;
}

.v-input--disabled {
  pointer-events: visible !important;
  cursor: no-drop !important;
}

.search {
  max-width: 350px;
}

@media (max-width: 768px) {
  .billing-panel {
    border: 0px !important;
    padding: 0 !important;
  }
}
.dialog-bottom-full-width {
  position: fixed !important;
  left: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  max-width: 100vw !important;
  margin: 0 !important;
  border-radius: 0 !important;
  box-shadow: none !important;
  .v-card {
    border-radius: 24px 24px 0 0 !important;
  }
}
</style>
