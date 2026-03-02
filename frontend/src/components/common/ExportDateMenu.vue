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
})

const emit = defineEmits([
  'update:modelValue',
  'update:menuVisible',
])

const internalMenuVisible = ref(false)
const pickerKey = ref(0)

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
  emit('update:modelValue', null)
})

const onPickerUpdate = value => {
  emit('update:modelValue', value)
}
</script>

<template>
  <VMenu
    v-model="resolvedMenuVisible"
    :close-on-content-click="false"
  >
    <template #activator="{ props: activatorProps }">
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
        :model-value="modelValue"
        @update:modelValue="onPickerUpdate"
        :label="pickerLabel"
        :placeholder="pickerPlaceholder"
        :config="pickerConfig"
      />
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
