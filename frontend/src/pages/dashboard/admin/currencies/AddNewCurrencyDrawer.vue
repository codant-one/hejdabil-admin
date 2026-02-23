<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { requiredValidator } from '@/@core/utils/validators'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  currency: {
    type: Object,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'currencyData',
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(0)
const name = ref('')
const code = ref('')
const flag = ref('')
const isEdit = ref(false)

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera valuta': 'Lägg till valuta'
})

watchEffect(async() => {
  if (props.isDrawerOpen) {

    if (!(Object.entries(props.currency).length === 0) && props.currency.constructor === Object) {

      isEdit.value = true
      id.value = props.currency.id
      name.value = props.currency.name
      code.value = props.currency.code
      flag.value = props.currency.flag
    }
  }
})

const isValidFlagUrl = computed(() => {
  const pattern = /^https:\/\/hatscripts\.github\.io\/circle-flags\/flags\/[a-z]{2}\.svg$/i
  return pattern.test(flag.value)
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    name.value = null
    code.value = null
    flag.value = null
    
    isEdit.value = false 
    id.value = 0
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

      formData.append('name', name.value)
      formData.append('code', code.value)
      formData.append('flag', flag.value)

      emit('currencyData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

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
        class="rounded btn-close-currency"
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
      <VCard flat class="card-currency">
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
          <VRow>
            <VCol cols="12" md="12">
              <small class="form-text text-muted">
                Usa una URL del sitio <a href="https://hatscripts.github.io/circle-flags" target="_blank">https://hatscripts.github.io/circle-flags</a>.<br>
                La bandera debe corresponder al país de origen de la moneda.<br>
                Por ejemplo: Para el dólar estadounidense (<strong>USD</strong>), usa:<br>
                <code class="p-0">https://hatscripts.github.io/circle-flags/flags/us.svg</code>
              </small>
              <div class="mt-2">
                <img src="https://hatscripts.github.io/circle-flags/flags/us.svg" alt="US Flag" width="32" height="32" />
              </div>
            </VCol>

            <VCol cols="12" md="6">
                <VTextField
                    v-model="name"
                    label="Namnet"
                    :rules="[requiredValidator]"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="code"
                    :rules="[requiredValidator]"
                    label="Kod"
                />
            </VCol>
            <VCol :cols="isValidFlagUrl ? 10 : 12" :md="isValidFlagUrl ? 11 : 12">
                <VTextField
                    v-model="flag"
                    :rules="[requiredValidator]"
                    label="Flagga"
                />
            </VCol>
            <VCol cols="2" md="1">
              <img v-if="isValidFlagUrl" :src="flag" alt="US Flag" width="32" height="32" />
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
  .btn-close-currency {
    height: 32px !important;
  }
  .card-currency {
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
