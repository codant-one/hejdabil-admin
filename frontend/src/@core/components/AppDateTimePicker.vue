<script setup>
import FlatPickr from 'vue-flatpickr-component'
import { useTheme } from 'vuetify'
import {
  filterFieldProps,
  makeVFieldProps,
} from 'vuetify/lib/components/VField/VField'
import {
  filterInputProps,
  makeVInputProps,
} from 'vuetify/lib/components/VInput/VInput'

import { filterInputAttrs } from 'vuetify/lib/util/helpers'
import { useThemeConfig } from '@core/composable/useThemeConfig'
import svLocale  from 'flatpickr/dist/l10n/sv';

const props = defineProps({
  ...makeVInputProps({
    density: 'compact',
    hideDetails: 'auto',
  }),
  ...makeVFieldProps({
    variant: 'outlined',
    color: 'secondary',
  }),
  config: {
    type: Object,
    default: () => ({}),
  },
  isMobile: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits([
  'update:modelValue',
  'click:clear',
])

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
const [rootAttrs, compAttrs] = filterInputAttrs(attrs)

const [{
  modelValue: _,
  ...inputProps
}] = filterInputProps(props)

const [fieldProps] = filterFieldProps(props)
const refFlatPicker = ref()
const { focused } = useFocus(refFlatPicker)
const isCalendarOpen = ref(false)
const isInlinePicker = ref(false)
const selectedPreset = ref(null)

const isRangeMode = computed(() => props.config?.mode === 'range')
const showRangePresets = computed(() => isInlinePicker.value && isRangeMode.value && props.config?.rangePresets !== false)
const showSplitRangeInputs = computed(() => isInlinePicker.value && isRangeMode.value && props.config?.splitRangeInputs !== false)
const isMobile = computed(() => props.isMobile)

const datepickerConfig = computed(() => {
  const config = { ...props.config }

  svLocale.sv.time_24hr = false
  config.locale = svLocale.sv
  config.disableMobile = true

  return config
})

if (props.config.inline) {
  isInlinePicker.value = props.config.inline
}

const datepickerAttrs = computed(() => {
  const customAttrs = { ...compAttrs }

  if (props.config.inline) {
    customAttrs.altInputClass = 'inlinePicker'
  }

  return customAttrs
})

const onClear = el => {
  el.stopPropagation()
  nextTick(() => {
    emit('update:modelValue', '')
    emit('click:clear', el)
  })
}

const { theme } = useThemeConfig()
const vuetifyTheme = useTheme()
const vuetifyThemesName = Object.keys(vuetifyTheme.themes.value)

const updateThemeClassInCalendar = activeTheme => {

  // ℹ️ Flatpickr don't render it's instance in mobile and device simulator
  if (!refFlatPicker.value.fp.calendarContainer)
    return
  vuetifyThemesName.forEach(t => {
    refFlatPicker.value.fp.calendarContainer.classList.remove(`v-theme--${ t }`)
  })
  refFlatPicker.value.fp.calendarContainer.classList.add(`v-theme--${ activeTheme }`)
}

watch(theme, updateThemeClassInCalendar)
onMounted(() => {
  updateThemeClassInCalendar(vuetifyTheme.name.value)
})

const emitModelValue = val => {
  selectedPreset.value = null
  emit('update:modelValue', val)
}

const toYmd = value => {
  if (!value)
    return ''

  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    const year = value.getFullYear()
    const month = `${value.getMonth() + 1}`.padStart(2, '0')
    const day = `${value.getDate()}`.padStart(2, '0')

    return `${year}-${month}-${day}`
  }

  if (typeof value === 'string') {
    const normalized = value.trim()
    const ymdMatch = normalized.match(/^\d{4}-\d{2}-\d{2}/)
    if (ymdMatch)
      return ymdMatch[0]
  }

  return ''
}

const splitRangeValue = computed(() => {
  if (!props.modelValue)
    return { start: '', end: '' }

  if (Array.isArray(props.modelValue)) {
    const start = toYmd(props.modelValue[0])
    const end = toYmd(props.modelValue[1] ?? props.modelValue[0])

    return { start, end }
  }

  if (typeof props.modelValue === 'string') {
    const chunks = props.modelValue.split(/\s+to\s+|\s+till\s+/i)
    if (chunks.length >= 2)
      return { start: toYmd(chunks[0]), end: toYmd(chunks[1]) }

    const single = toYmd(props.modelValue)
    return { start: single, end: single }
  }

  const single = toYmd(props.modelValue)
  return { start: single, end: single }
})

const formatDateForPreset = date => {
  const year = date.getFullYear()
  const month = `${date.getMonth() + 1}`.padStart(2, '0')
  const day = `${date.getDate()}`.padStart(2, '0')

  return `${year}-${month}-${day}`
}

const parsePresetDate = value => {
  if (!value)
    return null

  const parsed = value instanceof Date ? new Date(value) : new Date(value)

  if (Number.isNaN(parsed.getTime()))
    return null

  return parsed
}

const applyPreset = preset => {
  if (!refFlatPicker.value?.fp)
    return

  const calendarConfig = refFlatPicker.value.fp.config ?? {}
  const today = new Date()
  const maxFromConfig = parsePresetDate(calendarConfig.maxDate ?? props.config?.maxDate)
  const minFromConfig = parsePresetDate(calendarConfig.minDate ?? props.config?.minDate)
  const end = maxFromConfig ?? new Date(today)
  let start = new Date(today)
  let resolvedEnd = new Date(end)

  if (preset === 'today') {
    start = new Date(today)
    resolvedEnd = new Date(today)
  } else if (preset === 'lastWeek') {
    start.setDate(today.getDate() - 6)
  } else if (preset === 'lastMonth') {
    start.setDate(today.getDate() - 29)
  } else if (preset === 'lastYear') {
    start = minFromConfig ?? new Date(today)

    if (!minFromConfig)
      start.setFullYear(today.getFullYear() - 1)
  }

  selectedPreset.value = preset

  const startValue = formatDateForPreset(start)
  const endValue = formatDateForPreset(resolvedEnd)

  refFlatPicker.value.fp.setDate(
    [startValue, endValue],
    false,
  )

  emitModelValue([startValue, endValue])
}
</script>

<template>
  <!-- v-input -->
  <VInput
    v-if="!showSplitRangeInputs"
    v-bind="{ ...inputProps, ...rootAttrs }"
    :model-value="modelValue"
    :hide-details="props.hideDetails"
    class="position-relative"
  >
    <template #default="{ isDirty, isValid, isReadonly }">
      <!-- v-field -->
      <VField
        v-bind="fieldProps"
        :active="focused || isDirty.value || isCalendarOpen"
        :focused="focused || isCalendarOpen"
        role="textbox"
        :dirty="isDirty.value || props.dirty"
        :error="isValid.value === false"
        @click:clear="onClear"
      >
      <template v-if="fieldProps.label" #label>
        {{ fieldProps.label }}
      </template>

        <template #default="{ props: vFieldProps }">
          <div v-bind="vFieldProps">
            <!-- flat-picker  -->
            <FlatPickr
              v-if="!isInlinePicker"
              v-bind="datepickerAttrs"
              :config="datepickerConfig"
              ref="refFlatPicker"
              :model-value="modelValue"
              class="flat-picker-custom-style"
              :disabled="isReadonly.value"
              @on-open="isCalendarOpen = true"
              @on-close="isCalendarOpen = false"
              @update:model-value="emitModelValue"
            />

            <!-- simple input for inline prop -->
            <input
              v-if="isInlinePicker"
              :value="modelValue"
              class="flat-picker-custom-style"
              type="text"
            >
          </div>
        </template>
      </VField>
    </template>
  </VInput>

  <!-- flat picker for inline props -->
  <div
    v-if="isInlinePicker"
    class="app-inline-picker-layout"
    :class="{ 'has-range-presets': showRangePresets }"
  >
    <div v-if="showSplitRangeInputs && !isMobile" class="app-inline-range-header">
      <div class="app-inline-range-field"> 
        <VIcon icon="custom-calendar-2" size="24" />
        <span class="app-inline-range-text">{{ splitRangeValue.start || 'Startdatum' }}</span>
      </div>

      <div class="app-inline-range-field">
        <VIcon icon="custom-calendar-2" size="24" />
        <span class="app-inline-range-text">{{ splitRangeValue.end || 'Slutdatum' }}</span>
      </div>
    </div>

    <div class="d-flex justify-between" :class="isMobile ? 'gap-2' : 'gap-4'">
      <div v-if="showRangePresets" class="app-inline-picker-presets">
        <button
          type="button"
          class="app-inline-picker-preset"
          :class="{ active: selectedPreset === 'today' }"
          @click="applyPreset('today')"
        >
          Idag
        </button>
        <button
          type="button"
          class="app-inline-picker-preset"
          :class="{ active: selectedPreset === 'lastWeek' }"
          @click="applyPreset('lastWeek')"
        >
          Senaste 7 dagar
        </button>
        <button
          type="button"
          class="app-inline-picker-preset"
          :class="{ active: selectedPreset === 'lastMonth' }"
          @click="applyPreset('lastMonth')"
        >
          Senaste 30 dagar
        </button>
        <button
          type="button"
          class="app-inline-picker-preset"
          :class="{ active: selectedPreset === 'lastYear' }"
          @click="applyPreset('lastYear')"
        >
          Hela perioden
        </button>
      </div>

      <div v-if="showSplitRangeInputs && isMobile" class="app-inline-range-header mb-0">
        <div class="app-inline-range-field"> 
          <VIcon icon="custom-calendar-2" size="22" />
          <span class="app-inline-range-text">{{ splitRangeValue.start || 'Startdatum' }}</span>
        </div>

        <div class="app-inline-range-field">
          <VIcon icon="custom-calendar-2" size="22" />
          <span class="app-inline-range-text">{{ splitRangeValue.end || 'Slutdatum' }}</span>
        </div>
      </div>

      <FlatPickr
        v-bind="datepickerAttrs"
        :config="datepickerConfig"
        ref="refFlatPicker"
        :model-value="modelValue"
        @update:model-value="emitModelValue"
        @on-open="isCalendarOpen = true"
        @on-close="isCalendarOpen = false"
      />
    </div>
  </div>
</template>

<style lang="scss">
/* stylelint-disable no-descending-specificity */
@use "flatpickr/dist/flatpickr.css";

.flat-picker-custom-style {
  position: absolute;
  color: inherit;
  inline-size: 100%;
  inset: 0;
  outline: none;
  padding-block: 0;
  padding-inline: 12px;
}

$heading-color: rgba(var(--v-theme-on-background), var(--v-high-emphasis-opacity));
$body-color: #5D5D5D;
$disabled-color: rgba(var(--v-theme-on-background), var(--v-disabled-opacity));

// hide the input when your picker is inline
input[altinputclass="inlinePicker"] {
  display: none;
}

.app-inline-picker-layout {
  display: block;
}

.app-inline-range-header {
  display: grid;
  gap: 8px;
  grid-template-columns: 1fr 1fr;
  margin-block-end: 12px;
}

.app-inline-range-field {
  align-items: center;
  background: #F6F6F6;
  border: 1px solid #E7E7E7;
  border-radius: 8px;
  color: #5D5D5D;
  display: flex;
  gap: 8px;
  min-block-size: 52px;
  padding-inline: 16px;
}

.app-inline-range-text {
  color: #5D5D5D;
  font-size: 16px;
  line-height: 20px;
}

.app-inline-picker-layout.has-range-presets {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.app-inline-picker-presets {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  min-inline-size: 180px;
  border-inline-end: 1px solid #F6F6F6;
  padding-inline-end: 12px;
}

.app-inline-picker-preset {
  background: transparent;
  border: 0;
  border-radius: 8px;
  color: #5D5D5D;
  cursor: pointer;
  font-size: 16px;
  padding: 10px 8px;
  text-align: start;
}

.app-inline-picker-preset:hover,
.app-inline-picker-preset.active {
  background-color: #F6F6F6;
  border-radius: 64px;
  color: #454545;
}

.flatpickr-calendar {
  background-color: white !important;
  box-shadow: none !important;
  border: 1px solid #F6F6F6 !important;
  border-radius: 8px !important;  
  inline-size: 16.625rem;
  margin-block-start: 0;

  .flatpickr-rContainer {
    .flatpickr-weekdays {
      padding-inline: 0.8rem;
    }

    .flatpickr-days {
      min-inline-size: 16.625rem;

      .dayContainer {
        justify-content: center !important;
        inline-size: 16.625rem;
        min-inline-size: 16.625rem;
        padding-block-end: 0.5rem;
        padding-block-start: 0;
        gap: 1px;

        .flatpickr-day {
          block-size: 2.125rem;
          line-height: 2.125rem;
          margin-block-start: 0 !important;
          max-inline-size: 2.125rem;
        }
      }
    }
  }

  .flatpickr-day {
    color: $body-color;

    &.today {
      border-radius: 8px !important;
      border-color: #454545;
      background: #454545;
      color: white;

      &:hover {
        border-color: rgb(var(--v-theme-secondary));
        background: transparent;
        color: $body-color;
      }
    }

    &.selected,
    &.selected:hover {
      border: 0;
      border-radius: 8px !important;
      border-color: transparent !important;
      background: linear-gradient(90deg, #57F287 0%, #00EEB0 50%, #00FFFF 100%);
      color: #454545 !important;
    }

    &.inRange,
    &.inRange:hover {
      border: none;
      background: linear-gradient(90deg, #EAFFF1 0%, #EAFFF8 50%, #ECFFFF 100%) !important;
      box-shadow: none !important;
      color: #5D5D5D !important;
    }

    &.startRange {
      box-shadow: none;
    }

    &.endRange {
      box-shadow: none;
    }

    &.startRange,
    &.endRange,
    &.startRange:hover,
    &.endRange:hover {
      border: 0;
      border-radius: 8px !important;
      border-color: transparent !important;
      background: linear-gradient(90deg, #57F287 0%, #00EEB0 50%, #00FFFF 100%) !important;
      color: #454545 !important;
    }

    &.selected.startRange + .endRange:not(:nth-child(7n + 1)),
    &.startRange.startRange + .endRange:not(:nth-child(7n + 1)),
    &.endRange.startRange + .endRange:not(:nth-child(7n + 1)) {
      box-shadow: -10px 0 0 rgb(var(--v-theme-secondary));
    }

    &.flatpickr-disabled,
    &.prevMonthDay:not(.startRange,.inRange),
    &.nextMonthDay:not(.endRange,.inRange) {
      opacity: var(--v-disabled-opacity);
    }

    &:hover {
      color: #5D5D5D !important;
      border-radius: 8px !important;
      border-color: transparent !important;
      background: linear-gradient(90deg, #EAFFF1 0%, #EAFFF8 50%, #ECFFFF 100%) !important;
    }
  }

  .flatpickr-weekday {
    color: $heading-color;
    font-size: 0.875rem;
    font-weight: 500;
  }

  .flatpickr-days {
    inline-size: 16.625rem;
  }

  &::after,
  &::before {
    display: none;
  }

  .flatpickr-months {

    .flatpickr-prev-month,
    .flatpickr-next-month {
      fill: $body-color;

      &:hover i,
      &:hover svg {
        fill: $body-color;
      }
    }
  }

  .flatpickr-current-month span.cur-month {
    font-weight: 300;
  }
   
  &.open {
    z-index: 9999;
  }

  &.hasTime.open {
    .flatpickr-time {
      border-color: rgba(var(--v-border-color), var(--v-border-opacity));
      block-size: auto;
    }
  }
}

// Time picker hover & focus bg color
.flatpickr-time input:hover,
.flatpickr-time .flatpickr-am-pm:hover,
.flatpickr-time input:focus,
.flatpickr-time .flatpickr-am-pm:focus {
  background: transparent;
}

// Time picker
.flatpickr-time {
  .flatpickr-am-pm,
  .flatpickr-time-separator,
  input {
    color: $body-color;
  }

  .numInputWrapper {
    span {
      &.arrowUp {
        &::after {
          border-block-end-color: rgb(var(--v-border-color));
        }
      }

      &.arrowDown {
        &::after {
          border-block-start-color: rgb(var(--v-border-color));
        }
      }
    }
  }
}

//  Added bg color for flatpickr input only as it has default readonly attribute
.flatpickr-input[readonly],
.flatpickr-input ~ .form-control[readonly],
.flatpickr-human-friendly[readonly] {
  background-color: inherit;
  opacity: 1 !important;
}

// Month and year section
.flatpickr-current-month {
  .flatpickr-monthDropdown-months {
    appearance: none;
  }

  .flatpickr-monthDropdown-months,
  .numInputWrapper {
    height: 28px;
    margin: 0 2px 0 0;
    padding: 2px;
    border-radius: 4px;
    color: $heading-color;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.15s ease-out;

    span {
      display: none;
    }

    .flatpickr-monthDropdown-month {
      background-color: white;
    }

    .numInput.cur-year {
      font-weight: 500;
    }
  }
}

.flatpickr-day.flatpickr-disabled,
.flatpickr-day.flatpickr-disabled:hover {
  color: $body-color;
}

// removing box shadow of calendar in dark and added a border
.v-theme--dark.flatpickr-calendar {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  box-shadow: none;
}

.flatpickr-months {
  padding-block: 0.75rem;
  padding-inline: 1rem;

  .flatpickr-prev-month,
  .flatpickr-next-month {
    background: rgba(var(--v-theme-surface-variant), var(--v-selected-opacity));
    block-size: 1.75rem;
    border-radius: 5rem;
    inline-size: 1.75rem;
    inset-block-start: 0.75rem !important;
    padding-block: 0.25rem;
    padding-inline: 0.4375rem;
  }

  .flatpickr-next-month {
    inset-inline-end: 1.05rem !important;
  }

  .flatpickr-prev-month {
    /* stylelint-disable-next-line liberty/use-logical-spec */
    right: 3.5rem;
    left: unset !important;
  }

  .flatpickr-month {
    block-size: 1.75rem;

    .flatpickr-current-month {
      block-size: 1.75rem;
      inset-inline-start: 0;
      padding-block-start: 0.2rem;
      text-align: start;
    }
  }
}

@media (min-width: 1024px) {
  .flatpickr-calendar {
    &.inline {
      top: 0 !important;
    }
  }
}

// Mobile calendar positioning fix for dialogs/modals
@media (max-width: 1023px) {

  .app-inline-range-field {
    min-block-size: 40px;
  }

  .app-inline-range-text {
    font-size: 12px;
  }

  .app-inline-picker-layout .d-flex.justify-between.gap-4,
  .app-inline-picker-layout .d-flex.justify-between.gap-2 {
    flex-direction: column;
  }

  .app-inline-picker-presets {
    border-inline-end: 0;
    border-block-end: 1px solid #F6F6F6;
    min-inline-size: 0;
    padding-block-end: 8px;
    padding-inline-end: 0;
    gap: 4px;
  }

  .app-inline-picker-layout .flatpickr-calendar.inline {
    inline-size: 16.625rem;
    margin-inline: auto;
    position: relative !important;
    inset-block-start: auto !important;
    inset-inline-start: auto !important;
    transform: none !important;
    z-index: auto !important;
    width: 100%;
  }

  .flatpickr-calendar:not(.inline) {
    position: fixed !important;
    inset-block-start: 50% !important;
    inset-inline-start: 50% !important;
    transform: translate(-50%, -50%) !important;
    z-index: 99999 !important;
  }

  .flatpickr-innerContainer {
    justify-content: center;
  }

  .app-inline-picker-layout .flatpickr-calendar .flatpickr-months {
    max-inline-size: 17rem;
    margin-inline: auto;
    padding-inline: 1rem;
    position: relative;

    .flatpickr-prev-month,
    .flatpickr-next-month {
      inset-block-start: 0.75rem !important;
      left: unset !important;
    }

    .flatpickr-next-month {
      position: absolute !important;
      right: 1rem !important;
    }

    .flatpickr-prev-month {
      position: absolute !important;
      right: 3.5rem !important;
    }
  }

  .flatpickr-calendar.arrowTop::before,
  .flatpickr-calendar.arrowTop::after,
  .flatpickr-calendar.arrowBottom::before,
  .flatpickr-calendar.arrowBottom::after {
    display: none !important;
  }
}
</style>
