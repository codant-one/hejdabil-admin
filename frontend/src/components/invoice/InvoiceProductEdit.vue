<!-- eslint-disable vue/no-mutating-props -->
<script setup>

import { requiredValidator } from '@validators'
import { toRaw } from 'vue'

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
  },
  isCreated: {
      type: Boolean,
      required: true
  }
})

const emit = defineEmits([
  'removeProduct',
  'deleteProduct',
  'editProduct'
])

const localProductData = ref(structuredClone(toRaw(props.data)))

watch(props.data, val => {
  console.log('val', val)
  localProductData.value = { ...val } // Copia profunda
});

const removeProduct = () => {
  if(localProductData.value.disabled)
    emit('removeProduct', props.id)
  else
    emit('deleteProduct', props.id)
}

</script>

<template>
  <!-- eslint-disable vue/no-mutating-props -->
  <div class="add-products-header d-none d-md-flex px-5">
    <table class="w-100">
      <thead>
          <tr>
              <template v-for="(invoice, index) in props.invoices" :key="invoice.id">
                <td :style="`width: ${invoice.type_id === 1 ? '40' : (60/(props.invoices.length - 1)) }%;`">
                    <span class="text-base font-weight-bold">
                      {{ invoice.name }}
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
                    :label="invoice.description"
                    :placeholder="invoice.description"
                    rows="3"
                    :readonly="localProductData.disabled"
                    :rules="[requiredValidator]"
                  />
                  <VTextField
                    v-if="invoice.type_id === 2"
                    v-model="localProductData[invoice.id]"
                    type="number"
                    :label="invoice.name"
                    :placeholder="invoice.name"
                    :min="1"
                    :readonly="localProductData.disabled"
                    :rules="[requiredValidator]"
                    @input="$emit('editProduct')"
                  />
                  <VTextField
                    v-if="invoice.type_id === 3"
                    v-model="localProductData[invoice.id]"
                    type="number"
                    :label="invoice.name"
                    :placeholder="invoice.name"
                    :min="0"
                    :step="0.01"
                    :readonly="localProductData.disabled"
                    @input="$emit('editProduct')"
                    :rules="[requiredValidator]"
                    :disabled="invoice.name === 'Belopp'"
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
        v-if="props.id > 0"
        icon="tabler-x"
        variant="text"
        @click="removeProduct">
      </VBtn>
    </div>
  </VCard>
</template>