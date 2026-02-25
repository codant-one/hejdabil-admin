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
      </VAvatar>

      <VAvatar
        v-else
        rounded
        :size="250"
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
      </VAvatar>

      <div class="user-bio">
        <div class="bio-grid">
          <div class="bio-left">
            <div class="bio-fields">
              <div class="bio-item">
                <div class="bio-label">E-post</div>
                <div class="bio-value">
                  {{ props.isSupplier ? props.customerData.user.email : props.customerData.email }}
                </div>
              </div>
              <div class="bio-item">
                <div class="bio-label">Postnummer</div>
                <div class="bio-value">{{ props.customerData.postal_code }}</div>
              </div>
              <div class="bio-item">
                <div class="bio-label">Telefon</div>
                <div class="bio-value">
                  {{ props.isSupplier ? props.customerData.user.user_detail.phone : props.customerData.phone }}
                </div>
              </div>
              <div class="bio-item">
                <div class="bio-label">Stad</div>
                <div class="bio-value">{{ props.customerData.street }}</div>
              </div>
              <div class="bio-item">
                <div class="bio-label">Adress</div>
                <div class="bio-value">{{ props.customerData.address }}</div>
              </div>
              <div class="bio-item">
                <div class="bio-label">Organisationsnummer</div>
                <div class="bio-value">{{ props.customerData.organization_number }}</div>
              </div>
            </div>
          </div>
          <div class="bio-right">
            <div class="bio-item" v-if="props.customerData.client_type_id === 3">
                <div class="bio-label">Land</div>
                <div class="bio-value">{{ props.customerData.country.name }}</div>
              </div>
              <div class="bio-item" v-if="props.customerData.client_type_id === 2">
                <div class="bio-label">Momsreg. nr.</div>
                <div class="bio-value">{{ props.customerData.num_iva ?? '---'}}</div>
              </div>
              <div class="bio-item">
                <div class="bio-label">Kundbeskrivning</div>
                <div class="bio-value">{{ props.customerData.comments ?? "---" }}</div>
              </div>
          </div>
        </div>
      </div>

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
  text-align: start;
}

.user-bio {
  display: flex;
  border-radius: 8px;
  gap: 16px;
  padding: 24px;
  border: 1px solid #e7e7e7;
  width: 100%;

  .bio-grid {
    display: grid;
    grid-template-columns: 2fr 1fr; // left 8/12, right 4/12 approximately
    gap: 16px;
    width: 100%;
  }

  .bio-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  .bio-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .bio-label {
    font-weight: 400;
    font-size: 14px;
    color: #878787;
  }

  .bio-value {
    font-weight: 400;
    font-size: 14px;
    color: #454545;
  }

  .bio-right {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
}

@media (max-width: 991px) {
  .user-bio {
    .bio-grid {
      grid-template-columns: 1fr;
    }
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

@media (max-width: 767px) {
  .bio-panel {
    .v-avatar {
      height: 170px !important;
    }

  }
}

@media (max-width: 991px) {
  .bio-panel {
    gap: 25px;
    flex-direction: column;

    .v-avatar {
      height: 250px;
      width: 100% !important;
    }

    .user-bio {
      flex-wrap: wrap;
      padding: 16px !important;
    }
  }
}
</style>
