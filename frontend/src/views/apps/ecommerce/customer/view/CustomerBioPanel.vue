<script setup>
import { themeConfig } from "@themeConfig";
import { avatarText, formatNumber } from "@/@core/utils/formatters";
import { toRaw } from "vue";
import {
  emailValidator,
  requiredValidator,
  phoneValidator,
} from "@/@core/utils/validators";
import { useSuppliersStores } from "@/stores/useSuppliers";

import avatarImg from "@/assets/images/sample-avatar.jpg";

const props = defineProps({
  customerData: {
    type: Object,
    required: true,
  },
  isSupplier: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits(["update"]);

const route = useRoute();
const suppliersStores = useSuppliersStores();

const suppliers = ref([])
const supplier_id = ref(null)
const organization_number = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const fullname = ref('')
const email = ref('')
const reference = ref('')
const comments = ref('')
const valueCount = ref(null)
const valueText = ref(null)
const icon = ref('tabler-shopping-cart')
const sales = ref(null)
const userData = ref(null)
const role = ref(null)
const refForm = ref()

const isUserEditDialog = ref(false);

watchEffect(fetchData);

async function fetchData() {
  if (!props.customerData) return; // <-- Add this line

  if (route.name.includes('clients')) {
    valueCount.value = props.customerData.orders_count ?? 0
    valueText.value = 'Beställningar'
    icon.value = 'tabler-shopping-cart'

    fullname.value = props.customerData.fullname
    email.value = props.customerData.email
    organization_number.value = props.customerData.organization_number
    address.value = props.customerData.address
    street.value = props.customerData.street
    postal_code.value = props.customerData.postal_code
    phone.value = props.customerData.phone
    reference.value = props.customerData.reference
    comments.value = props.customerData.comments

    fullname.value = props.customerData.fullname ?? "";
    email.value = props.customerData.email;
    organization_number.value = props.customerData.organization_number;
    address.value = props.customerData.address;
    street.value = props.customerData.street;
    postal_code.value = props.customerData.postal_code;
    phone.value = props.customerData.phone;
  } else {
    valueCount.value = props.customerData.product_count ?? 0;
    valueText.value = "Kunder";
    icon.value = "tabler-user";
    sales.value = null; //CALCULAR MAS ADELANTE
  }

  userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
  role.value = userData.value.roles[0].name;

  if(role.value === 'SuperAdmin' || role.value === 'Administrator' && route.name.includes('clients-id')) {
    await suppliersStores.fetchSuppliers({ limit: -1 , state_id: 2})
    suppliers.value = toRaw(suppliersStores.getSuppliers)

    supplier_id.value = props.customerData.supplier?.id ?? null
  }
}

const showUserEditDialog = (u) => {
  isUserEditDialog.value = true;
};

const closeUserEditDialog = () => {
  isUserEditDialog.value = false;
};

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData();

      formData.append('supplier_id', supplier_id.value)
      formData.append('supplier_id', supplier_id.value)
      formData.append('email', email.value)
      formData.append('fullname', fullname.value)
      formData.append('organization_number', organization_number.value)
      formData.append('address', address.value)
      formData.append('street', street.value)
      formData.append('postal_code', postal_code.value)
      formData.append('phone', phone.value)
      formData.append('reference', reference.value)
      formData.append('comments', comments.value)
      formData.append('_method', 'PUT')

      emit("update", { data: formData, id: props.customerData.id });

      closeUserEditDialog();
    }
  });
};
</script>

<template>
  <VRow>
    <!-- SECTION Customer Details -->
    <VCol cols="12" class="d-flex gap-4 bio-panel" v-if="props.customerData">
      <VAvatar v-if="props.isSupplier" rounded :size="250">
        <VImg
          v-if="props.customerData.user.avatar"
          :src="
            themeConfig.settings.urlStorage + props.customerData.user.avatar
          "
        />
        <span class="text-5xl font-weight-medium">
          {{ avatarText(props.customerData.fullname) }}
        </span>
        <h4 class="avatar-username" v-if="props.isSupplier">
          {{ props.customerData.user.name }}
          <br />
          {{ props.customerData.user.last_name ?? "" }}
        </h4>
        <h4 class="avatar-username" v-else>
          {{ props.customerData.fullname }}
        </h4>
        <!-- <span class="text-sm">
          {{ props.isSupplier ? "Leverantör" : "Klient" }} ID #{{
            props.customerData.id
          }}</span> -->
      </VAvatar>

      <VAvatar
        v-else
        rounded
        :size="250"
        color="primary"
        class="position-relative"
      >
        <VImg :src="avatarImg" />
        <h4 class="avatar-username" v-if="props.isSupplier">
          {{ props.customerData.user.name }}
          <br />
          {{ props.customerData.user.last_name ?? "" }}
        </h4>
        <h4 class="avatar-username" v-else>
          {{ props.customerData.fullname }}
        </h4>
        <!-- <span class="text-sm">
          {{ props.isSupplier ? "Leverantör" : "Klient" }} ID #{{
            props.customerData.id
          }}</span> -->
      </VAvatar>

      <!-- 👉 Customer fullName -->
      <!-- <h4 class="text-h4 mt-4" v-if="props.isSupplier">
        {{ props.customerData.user.name }}
        {{ props.customerData.user.last_name ?? "" }}
      </h4>
      <h4 class="text-h4 mt-4" v-else>
        {{ props.customerData.fullname }}
      </h4>
      <span class="text-sm">
        {{ props.isSupplier ? "Leverantör" : "Klient" }} ID #{{
          props.customerData.id
        }}</span
      > -->

      <div class="user-bio">
        <VRow no-gutters class="user-bio-row w-100">
          <VCol cols="12" md="4" class="first-bio">
            <div class="bio-item">
              <div class="bio-label">E-post</div>
              <div class="bio-value">
                {{ props.isSupplier ? props.customerData.user.email : props.customerData.email }}
              </div>
            </div>
            <div class="bio-item">
              <div class="bio-label">Telefon</div>
              <div class="bio-value">
                {{ props.isSupplier ? props.customerData.user.user_detail.phone : props.customerData.phone }}
              </div>
            </div>
            <div class="bio-item">
              <div class="bio-label">Adress</div>
              <div class="bio-value">{{ props.customerData.address }}</div>
            </div>
          </VCol>
          <VCol cols="12" md="4" class="second-bio">
            <div class="bio-item">
              <div class="bio-label">Postnummer</div>
              <div class="bio-value">{{ props.customerData.postal_code }}</div>
            </div>
            <div class="bio-item">
              <div class="bio-label">Stad</div>
              <div class="bio-value">{{ props.customerData.street }}</div>
            </div>
            <div class="bio-item">
              <div class="bio-label">Organisationsnummer</div>
              <div class="bio-value">{{ props.customerData.organization_number }}</div>
            </div>
          </VCol>
          <VCol cols="12" md="4" class="third-bio">
            <div class="bio-item">
              <div class="bio-label">Produktbeskrivning</div>
              <div class="bio-value">{{ props.customerData.comments ?? "" }}</div>
            </div>
          </VCol>
        </VRow>
      </div>

      <!-- <div class="d-flex justify-center gap-x-5 mt-6">
        <div class="d-flex align-center">
          <VAvatar variant="tonal" color="primary" rounded class="me-3">
            <VIcon :icon="icon" />
          </VAvatar>
          <div class="d-flex flex-column align-start">
            <span class="text-body-1 font-weight-medium">
              {{ valueCount }}
            </span>
            <span class="text-body-2">{{ valueText }}</span>
          </div>
        </div>
        <div class="d-flex align-center">
          <VAvatar variant="tonal" color="primary" rounded class="me-3">
            <VIcon icon="tabler-currency-dollar" />
          </VAvatar>
          <div class="d-flex flex-column align-start">
            <span class="text-body-1 font-weight-medium"
              >{{ formatNumber(sales) ?? "0,00" }} kr</span
            >
            <span class="text-body-2">Total försäljning</span>
          </div>
        </div>
      </div> -->

      <!-- <VDivider class="my-4" />
      <div class="text-disabled text-uppercase text-sm">Detaljer</div> -->

      <!-- <VList class="card-list mt-2">
        <VListItem>
          <VListItemTitle>
            <h6 class="text-base font-weight-semibold">
              Namn:
              <span class="text-body-2">
                {{
                  props.isSupplier
                    ? props.customerData.user.name
                    : props.customerData.fullname
                }}
              </span>
            </h6>
          </VListItemTitle>
          <VListItemTitle v-if="props.isSupplier">
            <h6 class="text-base font-weight-semibold">
              Efternamn:
              <span class="text-body-2">
                {{ props.customerData.user.last_name ?? "" }}
              </span>
            </h6>
          </VListItemTitle>
          <VListItemTitle>
            <h6 class="text-base font-weight-semibold">
              E-post:
              <span class="text-body-2">
                {{
                  props.isSupplier
                    ? props.customerData.user.email
                    : props.customerData.email
                }}
              </span>
            </h6>
          </VListItemTitle>
          <VListItemTitle>
            <h6 class="text-base font-weight-semibold">
              Telefon:
              <span class="text-body-2">
                {{
                  props.isSupplier
                    ? props.customerData.user.user_detail.phone
                    : props.customerData.phone
                }}
              </span>
            </h6>
          </VListItemTitle>
          <VListItemTitle>
            <h6 class="text-base font-weight-semibold">
              Adress:
              <span class="text-body-2">
                {{ props.customerData.address }}
              </span>
            </h6>
          </VListItemTitle>
          <VListItemTitle>
            <h6 class="text-base font-weight-semibold">
              Postnummer:
              <span class="text-body-2">
                {{ props.customerData.postal_code }}
              </span>
            </h6>
          </VListItemTitle>
          <VListItemTitle>
            <h6 class="text-base font-weight-semibold">
              Stad:
              <span class="text-body-2">
                {{ props.customerData.street }}
              </span>
            </h6>
          </VListItemTitle>
          <VListItemTitle>
            <h6 class="text-base font-weight-semibold">
              Organisationsnummer:
              <span class="text-body-2">
                {{ props.customerData.organization_number }}
              </span>
            </h6>
          </VListItemTitle>
          <VListItemTitle
            v-if="
              role !== 'Supplier' &&
              route.name.includes('clients-id') &&
              suppliers.length > 0 &&
              props.customerData.supplier_id !== null
            "
          >
            <h6 class="text-base font-weight-semibold">
              Leverantör:
              <span class="text-body-2">
                {{ props.customerData.supplier.user.name }}
                {{ props.customerData.supplier.user.last_name }}
              </span>
            </h6>
          </VListItemTitle>
        </VListItem>
      </VList> -->
      <!-- 
      <VCardText
        class="d-flex justify-center"
        v-if="route.name.includes('clients')"
      >
        <VBtn
          variant="elevated"
          class="me-3 w-100 w-md-auto"
          @click="showUserEditDialog()"
        >
          Redigera
        </VBtn>
      </VCardText> -->
    </VCol>
    <!-- Optionally, show a loader or message if customerData is not loaded -->
    <VCol cols="12" v-else>
      <div>Laddar kunddata...</div>
    </VCol>

    <!-- DIALOG Edit personal information -->
    <VDialog v-model="isUserEditDialog" max-width="800" persistent>
      <!-- Dialog close btn -->
      <DialogCloseBtn @click="closeUserEditDialog" />

      <!-- Dialog Content -->
      <VCard title="Uppdatera klient">
        <VDivider class="mt-4" />
        <VForm ref="refForm" @submit.prevent="onSubmit">
          <VCardText class="pt-2 mt-6">
            <VRow>
              <VCol cols="12" md="12" v-if="role === 'SuperAdmin' || role === 'Administrator'">
                <VSelect                  
                  v-model="supplier_id"
                  placeholder="Leverantörer"
                  :items="suppliers"
                  :item-title="(item) => item.full_name"
                  :item-value="(item) => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"
                  :menu-props="{ maxHeight: '300px' }"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="fullname"
                  label="Fullständigt namn"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="email"
                  :rules="[emailValidator, requiredValidator]"
                  label="E-post"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="organization_number"
                  label="Organisationsnummer"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="address"
                  :rules="[requiredValidator]"
                  label="Adress"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="postal_code"
                  :rules="[requiredValidator]"
                  label="Postnummer"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="street"
                  :rules="[requiredValidator]"
                  label="Stad"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="phone"
                  :rules="[requiredValidator, phoneValidator]"
                  label="Telefon"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField v-model="reference" label="Vår referens" />
              </VCol>
              <!-- 👉 Form Actions -->
              <VCol cols="12" class="d-flex flex-wrap gap-4 justify-center">
                <VBtn type="submit"> Spara ändringar </VBtn>
              </VCol>
            </VRow>
          </VCardText>
        </VForm>
      </VCard>
    </VDialog>
  </VRow>
</template>

<style lang="scss" scoped>
.avatar-username {
  position: absolute;
  bottom: 16px;
  left: 16px;
  font-weight: 700;
  font-size: 32px;
  line-height: 100%;
  color: #fff;
}

.user-bio {
  display: flex;
  border-radius: 8px;
  gap: 16px;
  padding: 24px;
  border: 1px solid #e7e7e7;
  width: 100%;

  .bio-item {
    padding: 0;
    margin-bottom: 16px;
    line-clamp: initial;
    -webkit-line-clamp: initial;
  }

  .bio-label {
    margin-bottom: 8px;
    font-weight: 400;
    font-size: 14px;
    color: #878787;
  }

  .bio-value {
    font-weight: 400;
    font-size: 14px;
    color: #454545;
    line-clamp: initial;
    -webkit-line-clamp: initial;
  }
}

.card-list {
  --v-card-list-gap: 0.75rem;
}

.current-plan {
  background: linear-gradient(
    45deg,
    rgb(var(--v-theme-primary)) 0%,
    #9e95f5 100%
  );
  color: #fff;
}

@media (max-width: 768px) {
  .bio-panel {
    gap: 25px;
    flex-direction: column;

    .v-avatar {
      width: 100% !important;
      height: 170px !important;
    }

    .first-bio,
    .second-bio {
      flex: 1 1;
    }
    .third-bio {
      flex-basis: 100%;
      width: 100%;

      .bio-value {
        font-size: 12px;
      }
    }
    .user-bio {
      flex-wrap: wrap;
      padding: 16px !important;
    }
  }
}
</style>
