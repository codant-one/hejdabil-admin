<script setup>

import { themeConfig } from '@themeConfig'
import { avatarText, formatNumber } from '@/@core/utils/formatters'
import { toRaw } from 'vue'
import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'

const props = defineProps({
  customerData: {
    type: Object,
    required: true,
  },
  isSupplier: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits([
    'update'
])

const route = useRoute()
const suppliersStores = useSuppliersStores()

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
const valueCount = ref(null)
const valueText = ref(null)
const icon = ref('tabler-shopping-cart')
const sales = ref(null)
const userData = ref(null)
const role = ref(null)
const refForm = ref()

const isUserEditDialog = ref(false)

watchEffect(fetchData)

async function fetchData() {

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

  } else {
    valueCount.value = props.customerData.product_count ?? 0
    valueText.value = 'Kunder'
    icon.value = 'tabler-user'
    sales.value = null //CALCULAR MAS ADELANTE
  }

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  if(role.value !== 'Supplier' && route.name.includes('clients')) {
    await suppliersStores.fetchSuppliers({ limit: -1 , state_id: 2})
    suppliers.value = toRaw(suppliersStores.getSuppliers)

    supplier_id.value = props.customerData.supplier.id
  }
}

const showUserEditDialog = u =>{
  isUserEditDialog.value = true
}

const closeUserEditDialog = ()=>{
  isUserEditDialog.value = false
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

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
      formData.append('_method', 'PUT')

      emit('update', { data: formData, id: props.customerData.id} )

      closeUserEditDialog()
    }
  })
}

</script>

<template>
  <VRow>
    <!-- SECTION Customer Details -->
    <VCol cols="12">
      <VCard v-if="props.customerData">
        <VCardText class="text-center pt-15">
          <!-- 👉 Avatar -->
          <VAvatar
            v-if="props.isSupplier"
            rounded
            :size="100"
            :color="!props.customerData.customer ? 'primary' : undefined"
            :variant="!props.customerData.user.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="props.customerData.user.avatar"
              :src="themeConfig.settings.urlStorage + props.customerData.user.avatar"
            />
            <span
              v-else
              class="text-5xl font-weight-medium"
            >
              {{ avatarText(props.customerData.user.name) }}
            </span>
          </VAvatar>

          <VAvatar
            v-else
            rounded
            :size="100"
            color="primary"
            variant="tonal"
          >
            <span
              class="text-5xl font-weight-medium"
            >
              {{ avatarText(props.customerData.fullname) }}
            </span>
          </VAvatar>

          <!-- 👉 Customer fullName -->
          <h4 class="text-h4 mt-4" v-if="props.isSupplier">
            {{ props.customerData.user.name }}  {{ props.customerData.user.last_name ?? '' }}
          </h4>
          <h4 class="text-h4 mt-4" v-else>
            {{ props.customerData.fullname }}
          </h4>
          <span class="text-sm"> {{ props.isSupplier ? 'Leverantör' : 'Klient' }} ID #{{ props.customerData.id }}</span>

          <div class="d-flex justify-center gap-x-5 mt-6">
            <div class="d-flex align-center">
              <VAvatar
                variant="tonal"
                color="primary"
                rounded
                class="me-3"
              >
                <VIcon :icon="icon" />
              </VAvatar>
              <div class="d-flex flex-column align-start">
                <span class="text-body-1 font-weight-medium"> {{ valueCount }} </span>
                <span class="text-body-2">{{ valueText }}</span>
              </div>
            </div>
            <div class="d-flex align-center">
              <VAvatar
                variant="tonal"
                color="primary"
                rounded
                class="me-3"
              >
                <VIcon icon="tabler-currency-dollar" />
              </VAvatar>
              <div class="d-flex flex-column align-start">
                <span class="text-body-1 font-weight-medium">{{ formatNumber(sales) ?? '0.00' }} kr</span>
                <span class="text-body-2">Total försäljning</span>
              </div>
            </div>
          </div>
        </VCardText>

        <!-- 👉 Customer Details -->
        <VCardText>
          <VDivider class="my-4" />
          <div class="text-disabled text-uppercase text-sm">
            Detaljer
          </div>

          <VList class="card-list mt-2">
            <VListItem>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  Namn:
                  <span class="text-body-2">
                    {{ props.isSupplier ? props.customerData.user.name : props.customerData.fullname }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle v-if="props.isSupplier">
                <h6 class="text-base font-weight-semibold">
                  Efternamn:
                  <span class="text-body-2">
                    {{ props.customerData.user.last_name ?? '' }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  E-post:
                  <span class="text-body-2">
                    {{ props.isSupplier ? props.customerData.user.email : props.customerData.email }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  Telefon:
                  <span class="text-body-2">
                    {{ props.isSupplier ? props.customerData.user.user_detail.phone : props.customerData.phone }}
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
                    {{ props.customerData.postal_code}}
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
              <VListItemTitle v-if="role !== 'Supplier' && route.name.includes('clients') && suppliers.length > 0">
                <h6 class="text-base font-weight-semibold">
                  Leverantör:
                  <span class="text-body-2">
                    {{ props.customerData.supplier.user.name }} {{ props.customerData.supplier.user.last_name }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>

        <VCardText class="d-flex justify-center" v-if="route.name.includes('clients')">
          <VBtn
            variant="elevated"
            class="me-3"
            @click="showUserEditDialog()"
          >
          Redigera
          </VBtn>
  
      </VCardText>
    </VCard>
  </VCol>

    <!-- DIALOG Edit personal information -->
    <VDialog
      v-model="isUserEditDialog"
      max-width="800"
      persistent
    >
      <!-- Dialog close btn -->
      <DialogCloseBtn @click="closeUserEditDialog" />

      <!-- Dialog Content -->
      <VCard title="Uppdatera klient">    
        <VDivider class="mt-4"/>  
        <VForm
          ref="refForm"
          @submit.prevent="onSubmit"
        >      
          <VCardText class="pt-2 mt-6">
            <VRow>
              <VCol cols="12" md="12">
                <VSelect
                  v-if="role !== 'Supplier'"
                  v-model="supplier_id"
                  placeholder="Leverantörer"
                  :items="suppliers"
                  :item-title="item => item.full_name"
                  :item-value="item => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"
                  :menu-props="{ maxHeight: '300px' }"/>
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
                <VTextField
                    v-model="reference"
                    label="Vår referens"
                />
              </VCol>
              <!-- 👉 Form Actions -->
              <VCol
                cols="12"
                class="d-flex flex-wrap gap-4 justify-center"
              >
                <VBtn type="submit">
                  Spara ändringar
                </VBtn>
              </VCol>
            </VRow>
          </VCardText>
        </VForm>
      </VCard>      
    </VDialog> 

  </VRow>
</template>

<style lang="scss" scoped>
  .card-list {
    --v-card-list-gap: 0.75rem;
  }

  .current-plan{
    background: linear-gradient(45deg, rgb(var(--v-theme-primary)) 0%, #9E95F5 100%);
    color: #fff;
  }
</style>