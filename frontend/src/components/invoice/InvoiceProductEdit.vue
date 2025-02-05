<!-- eslint-disable vue/no-mutating-props -->
<script setup>

import { requiredValidator } from '@validators'

const props = defineProps({
  id: {
    type: Number,
    required: true,
  },
  data: {
    type: Object,
    required: true
  },
  invoices: {
      type: Object,
      required: true,
  }
})

const emit = defineEmits([
  'removeProduct',
  'deleteProduct',
  'editProduct'
])

const localProductData = ref(props.data)


const fetchData =() => {
  localProductData.value = props.data
}

watchEffect(fetchData)

const removeProduct = () => {
  if(localProductData.value.disabled)
    emit('removeProduct', props.id)
  else
    emit('deleteProduct', props.id)
}

</script>

<template>
  <!-- eslint-disable vue/no-mutating-props -->
  <div class="add-products-header mb-4 d-none d-md-flex ps-5 pe-16">
    <table class="w-100">
      <thead>
          <tr>
              <template v-for="(invoice, index) in props.invoices" :key="invoice.id">
                <td :style="`width: ${invoice.type_id === 1 ? '40' : (60/(props.invoices.length - 1)) }%;`">
                    <span class="text-base font-weight-bold">
                      {{ invoice.name_en }}
                    </span>
                </td>
              </template>
          </tr>
      </thead>
    </table>
  </div>

  <VCard
    flat
    border
    class="d-flex flex-row"
    style="box-shadow: none !important; border-radius: 12px !important;"
  >
    <!-- 👉 Left Form -->
    <div class="pa-5 flex-grow-1">
      <table class="w-100">
        <thead>
            <tr>
              <template v-for="(invoice, index) in props.invoices" :key="invoice.id">
                <td :style="`width: ${invoice.type_id === 1 ? '40' : (60/(props.invoices.length - 1)) }%;`" class="pe-2" style="vertical-align: top;">
                  <VTextarea
                    v-if="invoice.type_id === 1"
                    v-model="localProductData[invoice.id]"
                    :label="invoice.description_en"
                    :placeholder="invoice.description_en"
                    rows="3"
                    :readonly="localProductData.disabled"
                    :rules="[requiredValidator]"
                  />
                  <VTextField
                    v-if="invoice.type_id === 2"
                    v-model="localProductData[invoice.id]"
                    type="number"
                    :label="invoice.name_en"
                    :placeholder="invoice.name_en"
                    :min="1"
                    :readonly="localProductData.disabled"
                    :rules="[requiredValidator]"
                    @input="$emit('editProduct')"
                  />
                  <VTextField
                    v-if="invoice.type_id === 3"
                    v-model="localProductData[invoice.id]"
                    type="number"
                    :label="invoice.name_en"
                    :placeholder="invoice.name_en"
                    :min="0"
                    :step="0.01"
                    :readonly="localProductData.disabled"
                    @input="$emit('editProduct')"
                    :rules="[requiredValidator]"
                    :disabled="invoice.name_en === 'Amount'"
                  />
                </td>
              </template>
            </tr>
        </thead>
      </table>
    </div>

    <!-- 👉 Item Actions -->
    <div class="d-flex flex-column justify-space-between border-s pa-0">
      <VBtn 
        icon="tabler-x"
        variant="text"
        @click="removeProduct">
      </VBtn>
    </div>
  </VCard>
</template>
