<!-- eslint-disable vue/no-mutating-props -->
<script setup>

import { requiredValidator } from '@validators'
import draggable from 'vuedraggable'

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
  notes: {
      type: Object,
      required: false
  },
  isCreated: {
      type: Boolean,
      required: true
  }
})

const emit = defineEmits([
  'removeProduct',
  'deleteProduct',
  'editProduct',
  'editNote'
])

const localProductData = ref(props.data)
const notes = ref(props.isCreated ? [] : props.notes);

const fetchData =() => {
  localProductData.value = props.data
}

watch(() => props.notes, (val) => {
  notes.value = val
})

watchEffect(fetchData)

const addNote = () => {
  const lastOrderId = (notes.value && notes.value.length > 0) ? notes.value[notes.value.length - 1].order_id : 0;

  notes.value.push({ 
    id: props.id, 
    order_id: lastOrderId + 1, 
    note: '' 
  });

  emit('editNote', {id: props.id, notes: notes.value})
};

const onStart = async (e) => {
  // console.log('oldIndex',e.oldIndex)
}

const onEnd = async (e) => {
  notes.value.forEach((element, index)  => {
      element.order_id = index + 1
  });

  emit('editNote', {id: props.id, notes: notes.value})
}

const removeNote = (id) => {
  notes.value?.splice(id, 1)

  notes.value.forEach((element, index)  => {
      element.order_id = index + 1
  });

  emit('editNote', {id: props.id, notes: notes.value})

}

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
            <tr>
              <td :colspan="props.invoices.length" class="pt-1">
                <draggable 
                  v-model="notes" 
                  tag="div" 
                  item-key="order_id" 
                  @start="onStart" 
                  @end="onEnd" 
                  handle=".drag-handle">
                  <template #item="{ element }">
                    <div class="draggable-item py-2 px-2 d-flex">
                      <span class="drag-handle px-3 d-flex align-center">☰</span>
                      <VTextarea 
                        v-model="element.note" 
                        label="Notera" 
                        placeholder="Notera" 
                        rows="2" 
                        class="mt-1"
                        @input="$emit('editNote', {id: props.id, notes: notes})"/>
                        <VBtn
                          icon="tabler-x"
                          variant="text"
                          @click="removeNote(element.order_id - 1)">
                        </VBtn>
                    </div>
                  </template>
                </draggable>
              </td>
            </tr>
            <tr>
              <td :colspan="props.invoices.length" class="pt-1">
                <VBtn @click="addNote">
                    Lägg till anmärkning
                </VBtn>
              </td>
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

<style scope>
  .draggable-item:hover {
    background-color: #e9ecef;
    cursor: move;
    border-radius: 8px;
  }
</style>
