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

  <!-- 👉 Payment Methods -->
  <VRow>
    <VCol cols="12" v-if="props.isSupplier">
      <VCard class="facturing" title="title">
        <VCardText class="d-flex flex-column gap-y-4">
          ???
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style>
  .iconsButton .v-btn--icon.v-btn--density-default {
    width: calc(var(--v-btn-height) + 0px) !important;
    height: 25px !important;
  }
  .facturing.v-card--variant-elevated {
      box-shadow: none !important;
  }
</style>
