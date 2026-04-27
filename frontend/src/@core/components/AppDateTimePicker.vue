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
const showSingleInlineHeader = computed(() => isInlinePicker.value && !isRangeMode.value)
const showSingleInlineTimeFields = computed(() => showSingleInlineHeader.value && props.config?.enableTime)
const isMobile = computed(() => props.isMobile)
const selectedHour = ref('00')
const selectedMinute = ref('00')

const datepickerConfig = computed(() => {
  const config = { ...props.config }

  config.disableMobile = true

  if (config.locale === undefined) {
    svLocale.sv.time_24hr = Boolean(config.time_24hr)
    config.locale = svLocale.sv
  }

  config.monthSelectorType = config.monthSelectorType ?? 'dropdown'

  if (showSingleInlineTimeFields.value) {
    config.enableTime = false
    config.dateFormat = 'Y-m-d'
  }

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

const normalizeTimePart = (value, max) => {
  const digits = String(value ?? '').replace(/\D/g, '').slice(0, 2)
  if (!digits)
    return '00'

  const numericValue = Number.parseInt(digits, 10)
  if (Number.isNaN(numericValue))
    return '00'

  return `${Math.min(Math.max(numericValue, 0), max)}`.padStart(2, '0')
}

const sanitizeTimePartInput = value => String(value ?? '').replace(/\D/g, '').slice(0, 2)

const toHm = value => {
  if (!value)
    return { hour: '00', minute: '00' }

  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    const hour = `${value.getHours()}`.padStart(2, '0')
    const minute = `${value.getMinutes()}`.padStart(2, '0')

    return { hour, minute }
  }

  if (typeof value === 'string') {
    const normalized = value.trim()
    const hmMatch = normalized.match(/(\d{1,2}):(\d{2})/)

    if (hmMatch)
      return {
        hour: normalizeTimePart(hmMatch[1], 23),
        minute: normalizeTimePart(hmMatch[2], 59),
      }
  }

  return { hour: '00', minute: '00' }
}

const getSingleModelValue = value => {
  if (Array.isArray(value))
    return value[0]

  return value
}

const buildSingleDateTimeValue = dateValue => {
  if (!dateValue)
    return ''

  const hour = normalizeTimePart(selectedHour.value, 23)
  const minute = normalizeTimePart(selectedMinute.value, 59)

  return `${dateValue} ${hour}:${minute}`
}

const singleInlineCalendarModel = computed(() => {
  if (!showSingleInlineTimeFields.value)
    return props.modelValue

  return toYmd(getSingleModelValue(props.modelValue))
})

watch(
  () => props.modelValue,
  value => {
    if (!showSingleInlineTimeFields.value)
      return

    const { hour, minute } = toHm(getSingleModelValue(value))
    selectedHour.value = hour
    selectedMinute.value = minute
  },
  { immediate: true },
)

const emitModelValue = val => {
  selectedPreset.value = null

  if (showSingleInlineTimeFields.value) {
    const dateValue = toYmd(getSingleModelValue(val))
    emit('update:modelValue', buildSingleDateTimeValue(dateValue))

    return
  }

  emit('update:modelValue', val)
}

const emitInlineDateTimeIfPossible = () => {
  if (!showSingleInlineTimeFields.value)
    return

  const dateValue = toYmd(getSingleModelValue(props.modelValue))

  if (!dateValue)
    return

  emit('update:modelValue', buildSingleDateTimeValue(dateValue))
}

const commitInlineTimePart = (part, value) => {
  const isHour = part === 'hour'
  const normalizedValue = normalizeTimePart(value, isHour ? 23 : 59)

  if (part === 'hour') {
    selectedHour.value = normalizedValue
  } else {
    selectedMinute.value = normalizedValue
  }

  emitInlineDateTimeIfPossible()
}

const setInlineTimePartDraft = (part, value) => {
  const sanitizedValue = sanitizeTimePartInput(value)

  if (part === 'hour') {
    selectedHour.value = sanitizedValue
  } else {
    selectedMinute.value = sanitizedValue
  }

  if (sanitizedValue.length === 2)
    commitInlineTimePart(part, sanitizedValue)
}

const onInlineTimePartBlur = part => {
  const rawValue = part === 'hour' ? selectedHour.value : selectedMinute.value
  commitInlineTimePart(part, rawValue)
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

const singleInlineValue = computed(() => {
  if (!props.modelValue)
    return ''

  if (props.modelValue instanceof Date && !Number.isNaN(props.modelValue.getTime())) {
    const year = props.modelValue.getFullYear()
    const month = `${props.modelValue.getMonth() + 1}`.padStart(2, '0')
    const day = `${props.modelValue.getDate()}`.padStart(2, '0')
    const hours = `${props.modelValue.getHours()}`.padStart(2, '0')
    const minutes = `${props.modelValue.getMinutes()}`.padStart(2, '0')

    return `${year}-${month}-${day} ${hours}:${minutes}`
  }

  if (Array.isArray(props.modelValue))
    return String(props.modelValue[0] ?? '').trim()

  return String(props.modelValue).trim()
})

const singleInlineDisplayValue = computed(() => singleInlineValue.value || props.placeholder || 'Datum')

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
    v-if="!isInlinePicker && !showSplitRangeInputs"
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
          </div>
        </template>
      </VField>
    </template>
  </VInput>

  <!-- flat picker for inline props -->
  <div
    v-if="isInlinePicker"
    class="app-inline-picker-layout"
    :class="{ 'has-range-presets': showRangePresets, 'is-single-inline': showSingleInlineHeader }"
  >
    <span v-if="showSingleInlineHeader && isMobile" class="app-inline-time-section__label">Välj ett datum och en tid för åtgärden.</span>
    <div v-if="showSingleInlineHeader" class="app-inline-single-header">
      <VIcon icon="custom-calendar-2" :size="isMobile ? 22 : 24" />
      <span class="app-inline-single-header__text">{{ singleInlineDisplayValue }}</span>
    </div>

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

    <div
      class="app-inline-picker-body"
      :class="[
        showRangePresets ? 'd-flex justify-between' : 'd-flex justify-center',
        isMobile ? 'gap-2' : 'gap-4',
      ]"
    >
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
        :model-value="showSingleInlineTimeFields ? singleInlineCalendarModel : modelValue"
        @update:model-value="emitModelValue"
        @on-open="isCalendarOpen = true"
        @on-close="isCalendarOpen = false"
      />
    </div>

    <div v-if="showSingleInlineTimeFields" class="app-inline-time-section">
      <div class="app-inline-time-section__label">Välj en tid</div>

      <div class="app-inline-time-fields">
        <input
          :value="selectedHour"
          type="text"
          inputmode="numeric"
          maxlength="2"
          class="app-inline-time-field"
          @input="setInlineTimePartDraft('hour', $event.target.value)"
          @blur="onInlineTimePartBlur('hour')"
        >

        <span class="app-inline-time-separator">:</span>

        <input
          :value="selectedMinute"
          type="text"
          inputmode="numeric"
          maxlength="2"
          class="app-inline-time-field"
          @input="setInlineTimePartDraft('minute', $event.target.value)"
          @blur="onInlineTimePartBlur('minute')"
        >
      </div>
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

.app-inline-picker-layout.is-single-inline {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.app-inline-single-header {
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

.app-inline-single-header__text {
  color: #5D5D5D;
  font-size: 16px;
  line-height: 20px;
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

.app-inline-picker-body {
  inline-size: 100%;
  overflow-x: hidden;
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

.app-inline-time-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.app-inline-time-section__label {
  color: #454545;
  font-size: 16px;
  line-height: 24px;
  letter-spacing: 0%;
}

.app-inline-time-fields {
  align-items: center;
  display: grid;
  gap: 10px;
  grid-template-columns: 1fr auto 1fr;
}

.app-inline-time-field {
  appearance: textfield;
  background: #F6F6F6;
  border: 1px solid #E7E7E7;
  border-radius: 12px;
  color: #5D5D5D;
  font-size: 16px;
  line-height: 20px;
  inline-size: 100%;
  min-block-size: 52px;
  outline: none;
  padding: 10px 14px;
  text-align: start;
}

.app-inline-time-field:focus {
  border-color: #6E9383;
}

.app-inline-time-separator {
  color: #5D5D5D;
  font-size: 24px;
  line-height: 1;
  padding-block-end: 3px;
}

.app-inline-time-field::-webkit-outer-spin-button,
.app-inline-time-field::-webkit-inner-spin-button {
  appearance: none;
  margin: 0;
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
        inline-size: 100%;
        min-inline-size: 0;
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
    background-color: #fff;
    border: 1px solid #E7E7E7;
    border-radius: 8px;
    color: #5D5D5D;
    font-size: 16px;
    font-weight: 400;
    height: 2.25rem;
    line-height: 100%;
    margin: 0;
    padding-inline: 0.75rem;
    transition: all 0.15s ease-out;

    span {
      display: none;
    }

    .flatpickr-monthDropdown-month {
      background-color: white;
    }

    .numInput.cur-year {
      font-size: 16px;
      font-weight: 400;
    }
  }

  .flatpickr-monthDropdown-months {
    background-image: url('@/assets/images/iconify-svg/chevron-down-timer.svg');
    background-position: calc(100% - 10px) 50%;
    background-repeat: no-repeat;
    background-size: 16px 16px;
    min-inline-size: 5.5rem;
    padding-inline-end: 1.7rem;
    text-transform: capitalize;
  }

  .numInputWrapper {
    min-inline-size: 4.25rem;
    padding-inline: 0.6rem;

    .arrowUp,
    .arrowDown {
      display: none;
    }

    .numInput.cur-year {
      appearance: textfield;
      background: transparent;
      border: 0;
      color: #5D5D5D;
      inline-size: 100%;
      min-block-size: 2rem;
      padding: 0;
      text-align: start;
      height: 36px;
    }

    .numInput.cur-year::-webkit-inner-spin-button,
    .numInput.cur-year::-webkit-outer-spin-button {
      appearance: none;
      margin: 0;
    }
  }
}

.app-inline-picker-layout .flatpickr-calendar .flatpickr-months {
  padding-block: 8px;
  padding-inline: 0.5rem;
  max-inline-size: 100%;
  position: relative;

  .flatpickr-prev-month,
  .flatpickr-next-month {
    align-items: center;
    background: transparent;
    background-position: center;
    background-repeat: no-repeat;
    background-size: 20px 20px;
    block-size: 2.5rem;
    border-radius: 0;
    color: transparent;
    display: inline-flex;
    inline-size: 2rem;
    inset-block-start: 0.4rem !important;
    justify-content: center;
    padding: 0;
  }

  .flatpickr-prev-month svg,
  .flatpickr-next-month svg {
    display: none;
  }

  .flatpickr-prev-month {
    background-image: url('@/assets/images/iconify-svg/chevron-left-timer.svg');
    inset-inline-start: 0.5rem !important;
    inset-inline-end: auto !important;
    left: 0 !important;
    right: auto !important;
  }

  .flatpickr-next-month {
    background-image: url('@/assets/images/iconify-svg/chevron-right-timer.svg');
    inset-inline-end: 0.5rem !important;
    inset-inline-start: auto !important;
    left: auto !important;
    right: 0 !important;
  }

  .flatpickr-month {
    block-size: 2.25rem;
    overflow: visible;
    position: relative;

    .flatpickr-current-month {
      align-items: center;
      block-size: 2.25rem;
      display: flex;
      gap: 0.5rem;
      inset-inline-start: auto;
      justify-content: center;
      left: auto;
      margin: 0 auto;
      position: relative;
      padding-block-start: 0;
      padding-inline: 1.5rem;
      top: 0;
      box-sizing: border-box;
      text-align: center;
      width: 100%;
    }
  }
}

.app-inline-picker-layout.is-single-inline .flatpickr-calendar.inline .flatpickr-day {
  max-inline-size: 2.125rem;
}

.app-inline-picker-layout .flatpickr-calendar .flatpickr-current-month {
  .flatpickr-monthDropdown-months,
  .numInputWrapper {
    background-color: #fff;
    border: 1px solid #E7E7E7;
    border-radius: 8px;
    color: #5D5D5D;
    font-size: 16px;
    font-weight: 400;
    height: 2.25rem;
    line-height: 100%;
    margin: 0;
    padding-inline: 0.75rem;
  }

  .flatpickr-monthDropdown-months {
    appearance: none;
    background-image: url('@/assets/images/iconify-svg/chevron-down-timer.svg');
    background-position: calc(100% - 10px) 50%;
    background-repeat: no-repeat;
    background-size: 16px 16px;
    min-inline-size: 5.5rem;
    padding-inline-end: 1.7rem;
    text-transform: capitalize;
  }

  .numInputWrapper {
    min-inline-size: 4.25rem;
    padding-inline: 0.6rem;

    .arrowUp,
    .arrowDown {
      display: none;
    }

    .numInput.cur-year {
      appearance: textfield;
      background: transparent;
      border: 0;
      color: #5D5D5D;
      font-size: 16px;
      font-weight: 400;
      inline-size: 100%;
      min-block-size: 2rem;
      padding: 0;
      text-align: start;
    }

    .numInput.cur-year::-webkit-inner-spin-button,
    .numInput.cur-year::-webkit-outer-spin-button {
      appearance: none;
      margin: 0;
    }
  }

  .cur-month {
    color: #5D5D5D;
    font-size: 1rem;
    font-weight: 500;
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
  padding-block: 8px;
  padding-inline: 0.5rem;
  position: relative;

  .flatpickr-prev-month,
  .flatpickr-next-month {
    align-items: center;
    background: transparent;
    background-position: center;
    background-repeat: no-repeat;
    background-size: 20px 20px;
    block-size: 2.5rem;
    border-radius: 0;
    color: transparent;
    display: inline-flex;
    inline-size: 2rem;
    inset-block-start: 0.4rem !important;
    justify-content: center;
    padding: 0;

    &:hover i,
    &:hover svg {
      fill: transparent;
    }
  }

  .flatpickr-prev-month svg,
  .flatpickr-next-month svg {
    display: none;
  }

  .flatpickr-prev-month {
    background-image: url('@/assets/images/iconify-svg/chevron-left-timer.svg');
    inset-inline-start: 0.5rem !important;
    inset-inline-end: auto !important;
    left: 0 !important;
    right: auto !important;
  }

  .flatpickr-next-month {
    background-image: url('@/assets/images/iconify-svg/chevron-right-timer.svg');
    inset-inline-end: 0.5rem !important;
    inset-inline-start: auto !important;
    left: auto !important;
    right: 0 !important;
  }

  .flatpickr-month {
    block-size: 2.25rem;
    overflow: visible;
    position: relative;

    .flatpickr-current-month {
      align-items: center;
      block-size: 2.25rem;
      box-sizing: border-box;
      display: flex;
      gap: 0.5rem;
      inset-inline-start: auto;
      justify-content: center;
      left: auto;
      margin: 0 auto;
      padding-block-start: 0;
      padding-inline: 1.5rem;
      position: relative;
      text-align: center;
      top: 0;
      width: 100%;
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

  .app-inline-single-header {
    min-block-size: 40px;
  }

  .app-inline-single-header__text {
    font-size: 12px;
  }

  .app-inline-time-section__label {
    font-size: 16px;
    line-height: 22px;
  }

  .app-inline-time-field {
    min-block-size: 40px;
    font-size: 12px;
  }

  .app-inline-picker-presets {
    border-inline-end: 0;
    border-block-end: 1px solid #F6F6F6;
    min-inline-size: 0;
    padding-block-end: 8px;
    padding-inline-end: 0;
    gap: 2px;
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
  }

  .app-inline-picker-layout.is-single-inline .flatpickr-calendar.inline .flatpickr-innerContainer,
  .app-inline-picker-layout.is-single-inline .flatpickr-calendar.inline .flatpickr-rContainer,
  .app-inline-picker-layout.is-single-inline .flatpickr-calendar.inline .flatpickr-weekdays,
  .app-inline-picker-layout.is-single-inline .flatpickr-calendar.inline .flatpickr-days,
  .app-inline-picker-layout.is-single-inline .flatpickr-calendar.inline .dayContainer {
    inline-size: min(100%, 16.625rem);
    max-inline-size: min(100%, 16.625rem);
    margin-inline: auto;
  }

  .flatpickr-calendar.arrowTop::before,
  .flatpickr-calendar.arrowTop::after,
  .flatpickr-calendar.arrowBottom::before,
  .flatpickr-calendar.arrowBottom::after {
    display: none !important;
  }
}
</style>
