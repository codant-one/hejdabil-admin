<script setup>

import AppDateTimePicker from '@/@core/components/AppDateTimePicker.vue'
import { requiredValidator } from '@validators'

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
  resetOnOpen: {
    type: Boolean,
    default: true,
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
const validationError = ref('')
const singleDateTimeMenuWidth = 332

const formatSingleDateTimePart = value => `${value}`.padStart(2, '0')

const getDefaultSingleDateTimeValue = () => {
  const currentDate = new Date()
  const year = currentDate.getFullYear()
  const month = formatSingleDateTimePart(currentDate.getMonth() + 1)
  const day = formatSingleDateTimePart(currentDate.getDate())
  const hour = formatSingleDateTimePart(currentDate.getHours())
  const minute = formatSingleDateTimePart(currentDate.getMinutes())

  return `${year}-${month}-${day} ${hour}:${minute}`
}

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

const isRangePickerMode = computed(() => (props.pickerConfig?.mode ?? 'range') === 'range')
const isSingleDateTimePickerMode = computed(() => !isRangePickerMode.value && Boolean(props.pickerConfig?.enableTime))

const getPendingValueOnOpen = () => {
  const nextValue = props.resetOnOpen ? null : props.modelValue

  if (!nextValue && isSingleDateTimePickerMode.value)
    return getDefaultSingleDateTimeValue()

  return nextValue
}

const resolveModelValue = value => {
  if (!isRangePickerMode.value)
    return value

  return normalizeRangeValue(value)
}

const hasRequiredValue = value => {
  if (Array.isArray(value))
    return value.length > 0 && value.every(item => requiredValidator(item) === true)

  return requiredValidator(value) === true
}

const applyFilter = () => {
  const nextValue = resolveModelValue(pendingValue.value)

  if (!hasRequiredValue(nextValue)) {
    validationError.value = requiredValidator('')

    return
  }

  validationError.value = ''
  emit('update:modelValue', nextValue)
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
  if (!isVisible) {
    validationError.value = ''

    return
  }

  pickerKey.value += 1
  pendingValue.value = getPendingValueOnOpen()
  validationError.value = ''
})

watch(() => props.modelValue, value => {
  pendingValue.value = value
})

const onPickerUpdate = value => {
  pendingValue.value = value

  const nextValue = resolveModelValue(value)
  if (validationError.value && hasRequiredValue(nextValue))
    validationError.value = ''
}
</script>

<template>
  <VMenu
    v-model="resolvedMenuVisible"
    :close-on-content-click="false"
    :activator="activator"
    :open-on-click="showActivator"
    :min-width="isSingleDateTimePickerMode ? singleDateTimeMenuWidth : undefined"
    :max-width="isSingleDateTimePickerMode ? singleDateTimeMenuWidth : undefined"
    location="bottom start"
    origin="top start"
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

    <VCard :class="['export-date-menu-card', { 'export-date-menu-card--single-time': isSingleDateTimePickerMode }]">
      <AppDateTimePicker
        :key="pickerKey"
        :model-value="pendingValue"
        @update:modelValue="onPickerUpdate"
        :label="pickerLabel"
        :placeholder="pickerPlaceholder"
        :config="pickerConfig"
      />

      <div v-if="validationError" class="export-date-menu-error">
        {{ validationError }}
      </div>

      <div :class="isSingleDateTimePickerMode ? 'd-flex mt-3' : 'd-flex justify-end mt-2'">
        <VBtn
          :class="isSingleDateTimePickerMode ? 'btn-light px-3 w-100' : 'btn-light px-3'"
          @click="applyFilter"
          :style="isSingleDateTimePickerMode ? undefined : 'width: 264px;'"
        >
          <VIcon :icon="buttonIcon" size="24" />
          <span :class="isSingleDateTimePickerMode ? '' : 'd-none d-md-block'">{{ buttonText }}</span>
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

      <VCardText class="py-4 d-flex flex-column" style="overflow-y: auto; overflow-x: hidden;">
        <AppDateTimePicker
          :key="pickerKey"
          :model-value="pendingValue"
          @update:modelValue="onPickerUpdate"
          :label="pickerLabel"
          :placeholder="pickerPlaceholder"
          :config="pickerConfig"
          :is-mobile="true"
        />

        <div v-if="validationError" class="export-date-menu-error mt-2">
          {{ validationError }}
        </div>

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
    /*width: 544px;*/
    height: 388px;
    border-radius: 8px !important;
    opacity: 1;
    padding: 16px;
    gap: 16px;
    box-shadow: 0px 0px 40px 0px rgba(0, 0, 0, 0.15) !important;
  }

  .export-date-menu-error {
    color: #E65100;
    font-size: 12px;
    line-height: 16px;
    margin-top: 6px;
  }

  .export-date-menu-card--single-time {
    height: auto;
    min-height: 0;
    width: 300px;
  }
</style>
