<script setup>

import AppDateTimePicker from '@/@core/components/AppDateTimePicker.vue'

const props = defineProps({
  modelValue: {
    type: [String, Array, Date, Object, null],
    default: null,
  },
  menuVisible: {
    type: Boolean,
    default: undefined,
  },
  buttonText: {
    type: String,
    default: 'Exportera',
  },
  buttonIcon: {
    type: String,
    default: 'custom-export',
  },
  pickerLabel: {
    type: String,
    default: 'Inline',
  },
  pickerPlaceholder: {
    type: String,
    default: 'Select Date',
  },
  pickerConfig: {
    type: Object,
    default: () => ({
      inline: true,
      mode: 'range',
      rangePresets: true,
    }),
  },
  showActivator: {
    type: Boolean,
    default: true,
  },
  activator: {
    type: [String, Object, Function],
    default: undefined,
  },
})

const emit = defineEmits([
  'update:modelValue',
  'update:menuVisible',
  'update:filtrera',
])

const internalMenuVisible = ref(false)
const pickerKey = ref(0)
const filtrera = ref(false)
const pendingValue = ref(props.modelValue)

const applyFilter = () => {
  emit('update:modelValue', pendingValue.value)
  filtrera.value = true
  emit('update:filtrera', true)
}

const resolvedMenuVisible = computed({
  get: () => (props.menuVisible === undefined ? internalMenuVisible.value : props.menuVisible),
  set: value => {
    if (props.menuVisible === undefined)
      internalMenuVisible.value = value

    emit('update:menuVisible', value)
  },
})

watch(resolvedMenuVisible, isVisible => {
  if (!isVisible)
    return

  pickerKey.value += 1
  pendingValue.value = null
})

watch(() => props.modelValue, value => {
  pendingValue.value = value
})

const onPickerUpdate = value => {
  pendingValue.value = value
}
</script>

<template>
  <VMenu
    v-model="resolvedMenuVisible"
    :close-on-content-click="false"
    :activator="activator"
    :open-on-click="showActivator"
    location="bottom"
    origin="top center"
    :offset="8"
  >
    <template
      v-if="showActivator"
      #activator="{ props: activatorProps }"
    >
      <VBtn
        class="btn-light w-auto"
        block
        v-bind="activatorProps"
      >
        <VIcon :icon="buttonIcon" size="24" />
        {{ buttonText }}
      </VBtn>
    </template>

    <VCard class="export-date-menu-card">
      <AppDateTimePicker
        :key="pickerKey"
        :model-value="pendingValue"
        @update:modelValue="onPickerUpdate"
        :label="pickerLabel"
        :placeholder="pickerPlaceholder"
        :config="pickerConfig"
      />
      <div class="d-flex justify-end mt-2">
        <VBtn
          class="btn-white-2 px-3"
          @click="applyFilter"
        >
          <VIcon icon="custom-filter" size="24" />
          <span class="d-none d-md-block">Filtrera efter datum</span>
        </VBtn>
      </div>
    </VCard>
  </VMenu>
</template>

<style scoped>
  .export-date-menu-card {
    width: 544px;
    height: 388px;
    border-radius: 8px !important;
    opacity: 1;
    padding: 16px;
    gap: 16px;
    box-shadow: 0px 0px 40px 0px rgba(0, 0, 0, 0.15) !important;
  }
</style>
