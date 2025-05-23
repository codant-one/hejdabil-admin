<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { requiredValidator } from '@/@core/utils/validators'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  invoice: {
    type: Object,
    required: false
  },
  types: {
    type: Object,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'invoiceData',
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(0)
const type_id = ref(null)
const name = ref('')
const description = ref('')
const isEdit = ref(false)

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera fakturaattribut': 'Lägg till fakturaattribut'
})

watchEffect(async() => {
  if (props.isDrawerOpen) {

    if (!(Object.entries(props.invoice).length === 0) && props.invoice.constructor === Object) {

      isEdit.value = true
      id.value = props.invoice.id
      type_id.value = props.invoice.type_id
      name.value = props.invoice.name
      description.value = props.invoice.description
    }
  }
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    name.value = null
    description.value = null
    
    isEdit.value = false 
    id.value = 0
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

      formData.append('type_id', type_id.value)
      formData.append('name', name.value)
      formData.append('description', description.value)

      emit('invoiceData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

</script>

<template>
  <VNavigationDrawer
    temporary
    :width="550"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ getTitle }}
      </h6>

      <VSpacer />

      <!-- 👉 Close btn -->
      <VBtn
        variant="tonal"
        color="default"
        icon
        size="32"
        class="rounded btn-close-invoice"
        @click="closeNavigationDrawer"
      >
        <VIcon
          size="18"
          icon="tabler-x"
        />
      </VBTn>
    </div>
    
    <VDivider class="mt-4"/>

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat class="card-invoice">
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
          <VRow>
            <VCol cols="12" md="12">
              <VSelect
                  v-model="type_id"
                  placeholder="Typer"
                  :items="types"
                  :item-title="item => item.name"
                  :item-value="item => item.id"
                  autocomplete="off"
                  clearable
                  clear-icon="tabler-x"
                  :menu-props="{ maxHeight: '300px' }"
                  :rules="[requiredValidator]"/>
            </VCol>
            <VCol cols="12" md="12">
                <VTextField
                    v-model="name"
                    label="Namn"
                    :rules="[requiredValidator]"
                />
            </VCol>
            <VCol cols="12" md="12">
                <VTextarea
                    v-model="description"
                    label="Beskrivning"
                />
            </VCol>
              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  {{ isEdit ? 'Uppdatering': 'Lägg till' }}
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="secondary"
                  @click="closeNavigationDrawer"
                >
                  Avbryt
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style scoped>
  .btn-close-invoice {
    height: 32px !important;
  }
  .card-invoice {
    border-radius: 0 !important;
  }
  .border-img {
      border: 1.8px solid rgba(var(--v-border-color), var(--v-border-opacity));
      border-radius: 6px;
  }
  .border-img .v-img__img--contain {
      padding: 10px;
  }
</style>
