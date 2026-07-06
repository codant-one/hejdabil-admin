<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { handleNumericTextFieldKeydown, handleDecimalTextFieldKeydown, normalizeNumericTextInput, decimalRangeValidator, normalizeDecimalTextInput, numericRangeValidator, numericTextFieldProps } from '@/@core/utils/numericTextField'
import { requiredValidator, minLengthNonDigitsValidator } from '@/@core/utils/validators'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  plan: {
    type: Object,
    required: false
  },
  features: {
    type: Array,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'planData',
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(0)
const name = ref('')
const description = ref('')
const price_month = ref(0)
const price_annual = ref(0)
const selectedFeatures = ref([])
const features = ref([])
const isEdit = ref(false)
const nonNegativeNumericRules = [numericRangeValidator({ min: 0 })]
const nonNegativeDecimalRules = [decimalRangeValidator({ min: 0 })];

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera plan': 'Lägg till plan'
})

watchEffect(async() => {
  refForm.value?.resetValidation()

  name.value = ''
  description.value = ''
  price_month.value = 0
  price_annual.value = 0
  isFormValid.value = false
  features.value = []
  selectedFeatures.value = []
  
  isEdit.value = false 
  id.value = 0

  if (props.isDrawerOpen) {

    if (!(Object.entries(props.plan).length === 0) && props.plan.constructor === Object) {

      isEdit.value = true
      id.value = props.plan.id
      name.value = props.plan.name
      description.value = props.plan.description
      price_month.value = props.plan.price_month
      price_annual.value = props.plan.price_annual
      var sFeatures = props.plan.feature_plans

      selectedFeatures.value = (sFeatures !== undefined && sFeatures.length > 0) ? sFeatures.map(feature => feature.feature_id) : [];
    }

    features.value = props.features
  }
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.resetValidation()

    name.value = ''
    description.value = ''
    price_month.value = 0
    price_annual.value = 0
    isFormValid.value = false
    features.value = []
    selectedFeatures.value = []
    
    isEdit.value = false 
    id.value = 0
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

      formData.append('name', name.value)
      formData.append('description', description.value)
      formData.append('price_month', price_month.value)
      formData.append('price_annual', price_annual.value)
      formData.append('features', JSON.stringify(selectedFeatures.value)) 

      emit('planData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

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
        class="rounded btn-close-plan"
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
      <VCard flat class="card-plan">
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            validate-on="submit"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12" md="12">
                  <VTextField
                      v-model="name"
                      label="Namnet"
                      :rules="[requiredValidator]"
                  />
              </VCol>
              <VCol cols="12" md="12">
                  <VTextarea
                      v-model="description"
                      rows="3"
                      label="Beskrivning"
                      :rules="[requiredValidator]"
                  />
              </VCol>
              <VCol cols="12" md="6">
                  <VTextField
                      v-model="price_month"
                      label="Månadpris"
                      type="number"
                      :rules="[requiredValidator, ...nonNegativeDecimalRules]"
                      @input="price_month = normalizeDecimalTextInput(price_month)"
                      @keydown="handleDecimalTextFieldKeydown"
                  />
              </VCol>
              <VCol cols="12" md="6">
                  <VTextField
                      v-model="price_annual"
                      label="Årspris"
                      type="number"
                      :rules="[requiredValidator, ...nonNegativeDecimalRules]"
                      @input="price_annual = normalizeDecimalTextInput(price_annual)"
                      @keydown="handleDecimalTextFieldKeydown"
                  />
              </VCol>
              <VCol cols="12" md="6">
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Features" />
                  <VCheckbox
                      v-for="feature in features"
                      v-model="selectedFeatures"
                      :label="feature.name"
                      :value="feature.id"
                      hide-details
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
  .btn-close-plan {
    height: 32px !important;
  }
  .card-plan {
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
