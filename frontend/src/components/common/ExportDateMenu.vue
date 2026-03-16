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
  isMobile: {
    type: Boolean,
    default: false,
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

const toDateString = value => {
  if (!value)
    return ''

  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    const year = value.getFullYear()
    const month = `${value.getMonth() + 1}`.padStart(2, '0')
    const day = `${value.getDate()}`.padStart(2, '0')

    return `${year}-${month}-${day}`
  }

  if (typeof value === 'string')
    return value.trim()

  return String(value)
}

const normalizeRangeValue = value => {
  if (!value)
    return value

  if (Array.isArray(value)) {
    const start = toDateString(value[0])
    const end = toDateString(value[1] ?? value[0])
    if (!start || !end)
      return value

    return [start, end]
  }

  if (typeof value === 'string') {
    const chunks = value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i).map(item => item.trim()).filter(Boolean)
    if (chunks.length >= 2)
      return [chunks[0], chunks[1]]

    const single = toDateString(value)
    return single ? [single, single] : value
  }

  if (value instanceof Date) {
    const single = toDateString(value)
    return single ? [single, single] : value
  }

  return value
}

const applyFilter = () => {
  emit('update:modelValue', normalizeRangeValue(pendingValue.value))
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
    v-if="!isMobile"
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
          class="btn-light px-3"
          @click="applyFilter"
          style="width: 264px;"
        >
          <VIcon :icon="buttonIcon" size="24" />
          <span class="d-none d-md-block">{{ buttonText }}</span>
        </VBtn>
      </div>
    </VCard>
  </VMenu>

  <VDialog
    v-model="resolvedMenuVisible"
    fullscreen
    persistent
    :scrim="false"
    transition="dialog-bottom-transition"
    class="action-dialog dialog-fullscreen"
    content-class="clients-pending-mobile-fullscreen"
    v-else
  >
    <VBtn
        icon
        class="btn-ghost close-btn me-2"
        @click="resolvedMenuVisible = false"
    >
        <VIcon size="16" icon="custom-close" />
    </VBtn>

    <VCard flat class="h-100 d-flex flex-column">
      <VCardText class="dialog-title-box mt-2 pb-0 flex-0">
        <div class="dialog-title"></div>
      </VCardText>

      <VCardText class="py-4 flex-grow-1" style="overflow-y: auto; overflow-x: hidden;">
        <AppDateTimePicker
          :key="pickerKey"
          :model-value="pendingValue"
          @update:modelValue="onPickerUpdate"
          :label="pickerLabel"
          :placeholder="pickerPlaceholder"
          :config="pickerConfig"
          :is-mobile="true"
        />

        <div class="d-flex justify-end mt-2 mt-auto">
          <VBtn
            class="btn-light px-3"
            block
            @click="applyFilter"
          >
            <VIcon :icon="buttonIcon" size="24" />
            {{ buttonText }}
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
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
