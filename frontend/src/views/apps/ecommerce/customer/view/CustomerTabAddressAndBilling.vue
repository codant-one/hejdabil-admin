<script setup>

import { formatNumber } from '@/@core/utils/formatters'
import {requiredValidator} from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'
// import AddEditAddressDialog from "@/components/dialogs/AddEditAddressDialog.vue";

const refForm = ref()
const isFormValid = ref(false)
const cant_commission = ref(0)
const who_commission = ref(0)
const ser_commission = ref(0)
const total_balance = ref(0)
const settings = ref(0)
const who_settings = ref(0)
const ser_settings = ref(0)
const route = useRoute()
const suppliersStores = useSuppliersStores()

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const props = defineProps({
  addresses: {
    type: Object,
    required: false
  },
  customerData: {
    type: Object,
    required: false,
  },
  isSupplier: {
    type: Boolean,
    required: true,
  }
})

const emit = defineEmits([
  'submit',
  'delete',
  'copy',
  'download',
  'alert',
  'updateBalance'
])

const show = ref([
  true,
  false,
  false,
])

const isEditAddressDialogVisible = ref(false)
const selectedAddress = ref({})
const addresses_ = ref(props.addresses)

const accountTypes = [
  {
    icon: {
      icon: 'mdi-cash-multiple',
      size: '40',
    },
    title: 'Cuenta Corriente',
    value: '1',
  },
  {
    icon: {
      icon: 'tabler-pig-money',
      size: '40',
    },
    title: 'Cuenta de Ahorros',
    value: '2',
  }
]

const type_account = ref('1')
const document = ref(null)
const icon_type = ref(null)

watch(() =>  
  props.addresses, (addreses_) => {
    addresses_.value = addreses_
  });

watchEffect(() => {
  if (!isEditAddressDialogVisible.value)
    selectedAddress.value = {}
})

watchEffect(fetchData)

async function fetchData() {

  if(Number(route.params.id)) {
    if(props.isSupplier && props.customerData.account !== null) {
     
    }
  }
}

const download = (file) => {
  let data = {
    icon: document.value.split('.')[1],
    document: file
  }
  emit('download', data)
}

const copy = (account) => {
  emit('copy', account)
}

const editAddress = addressData => {

  addressData.addresses_type_id = addressData.addresses_type_id.toString()
  addressData.default = (addressData.default) === 1 ? true : false
  addressData.country_id = addressData.province.country.name
  addressData.provinceOld_id = addressData.province.id
  addressData.province_id = addressData.province.name

  isEditAddressDialogVisible.value = true
  selectedAddress.value = { ...addressData }
}

const showDeleteDialog = addressData => {
  emit('delete', addressData)
}

const onSubmit = (address, method) => {
  emit('submit', address, method)
}


</script>

<template>
  <!-- eslint-disable vue/no-v-html -->

  <!-- 👉 Address Book -->
  <VCard class="mb-6" v-if="!props.isSupplier">
    <VCardText>
      <div class="d-flex justify-space-between mb-6 flex-wrap align-center gap-y-4 gap-x-6">
        <h5 class="text-h5">
          Direcciones
        </h5>
        <VBtn
          variant="tonal"
          @click="isEditAddressDialogVisible = !isEditAddressDialogVisible"
        >
          Agregar
        </VBtn>
      </div>
      <template
        v-for="(address, index) in addresses_"
        :key="index"
      >
        <div class="d-flex justify-space-between mb-4 gap-y-2 flex-wrap align-center">
          <div class="d-flex align-center gap-x-1">
            <VBtn
              icon
              variant="text"
              color="default"
              size="x-small"
              @click="show[index] = !show[index]"
            >
              <VIcon
                :icon="show[index] ? 'tabler-chevron-down' : 'tabler-chevron-right'"
                class="flip-in-rtl"
              />
            </VBtn>

            <div>
              <div class="d-flex">
                <h6 class="text-h6 me-2">
                  {{ address.type.name }}
                </h6>
                <VChip
                  v-if="address.default"
                  color="success"
                  label
                >
                  Dirección por defecto
                </VChip>
              </div>
              <span class="text-body-2 text-disabled">{{ address.title }}</span>
            </div>
          </div>

          <div class="ms-5 iconsButton">
            <VBtn
              icon
              variant="text"
              color="default"
              @click="editAddress(address)">
              <VIcon
                icon="tabler-pencil"
                class="flip-in-rtl"
              />
            </VBtn>
            <VBtn
              icon
              variant="text"
              color="default"
              @click="showDeleteDialog(address)">
              <VIcon
                icon="tabler-trash"
                class="flip-in-rtl"
              />
            </VBtn>
          </div>
        </div>
        <VExpandTransition>
          <div
            v-show="show[index]"
            class="px-10"
          >
            <h6 class="mb-1 text-h6">
              {{ address.address }}
            </h6>
            <div
              class="text-body-1"
              v-html="address.street"
            />
            <div
              class="text-body-1"
              v-html="address.city"
            />
            <div
              class="text-body-1"
              v-html="address.postal_code"
            />
            <div
              class="text-body-1"
              v-html="address.phone"
            />
          </div>
        </VExpandTransition>
        <VDivider
          v-if="index !== addresses_.length - 1"
          class="my-4"
        />
      </template>
    </VCardText>
  </VCard>

  <!-- 👉 Payment Methods -->
  <VRow>
    <VCol cols="12" v-if="props.isSupplier">
      <VCard title="title">
        <VCardText class="d-flex flex-column gap-y-4">
          ???
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
  
  <!-- <AddEditAddressDialog 
    v-model:isDialogVisible="isEditAddressDialogVisible"
    :billing-address="selectedAddress"
    @submit="onSubmit"/> -->
</template>

<style>
  .iconsButton .v-btn--icon.v-btn--density-default {
    width: calc(var(--v-btn-height) + 0px) !important;
    height: 25px !important;
  }
</style>
