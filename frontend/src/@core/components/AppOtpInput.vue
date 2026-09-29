<script setup>

const props = defineProps({
  totalInput: {
    type: Number,
    required: false,
    default: 6,
  },
  default: {
    type: String,
    required: false,
    default: '',
  },
  showLabel: {
    type: Boolean,
    required: false,
    default: true,
  },
  type: {
    type: String,
    required: false,
    default: 'password',
  }
})

const emit = defineEmits(['updateOtp'])
const { width: windowWidth } = useWindowSize()

const digits = ref([])
const refOtpComp = ref(null)

digits.value = props.default.split('')

const defaultStyle = { style: 'max-width: 40px; text-align: center;' }

const focusInput = index => {
  if (refOtpComp.value !== null && index > 0 && index <= refOtpComp.value.children.length) {
    const inputEl = refOtpComp.value.children[index - 1].querySelector('input')
    if (inputEl)
      inputEl.focus()
  }
}

const handleInputUpdate = (value, index) => {
  const sanitizedValue = String(value ?? '').replace(/\D/g, '').slice(-1)
  digits.value[index - 1] = sanitizedValue

  if (sanitizedValue && index < props.totalInput)
    focusInput(index + 1)

  emit('updateOtp', digits.value.join(''))
}

// eslint-disable-next-line sonarjs/cognitive-complexity
const handleKeyDown = (event, index) => {
  const allowedControlCodes = ['Tab', 'ArrowRight', 'ArrowLeft', 'Backspace', 'Delete']
  const isNumericKey = /^([0-9])$/.test(event.key)

  if (!allowedControlCodes.includes(event.code) && !isNumericKey)
    event.preventDefault()

  if (event.code === 'Backspace') {
    digits.value[index - 1] = ''
    if (index > 1)
      focusInput(index - 1)
  }

  emit('updateOtp', digits.value.join(''))
}
</script>

<template>
  <div>
    <label
      v-if="props.showLabel"
      class="text-base font-weight-bold mb-4 d-none d-md-block"
    >
      Verifieringskod
    </label>
    <div
      ref="refOtpComp"
      class="d-flex align-center otp-form"
      :class="windowWidth < 1024 ? 'justify-center gap-4' : 'justify-start gap-2'"
    >
      <VTextField
        v-for="i in props.totalInput"
        :type="props.type"
        :key="i"
        :model-value="digits[i - 1]"
        inputmode="numeric"
        pattern="[0-9]*"
        v-bind="defaultStyle"
        maxlength="1"
        @keydown="handleKeyDown($event, i)"
        @update:model-value="handleInputUpdate($event, i)"
      />
    </div>
  </div>
</template>

<style lang="scss">
  .otp-form {
    .v-input {
      .v-input__control {
        .v-field {
          border: 1px solid #E7E7E7;
          background-color: #f6f6f6;

          .v-field__outline {
            color: #454545 !important;
          }

          .v-field__input {
            padding: 0;
            text-align: center;
          }

          .v-field__outline__start,
          .v-field__outline__notch,
          .v-field__outline__end {
            border-color: #e7e7e7 !important;
          }
        }
      }
    }
  }
</style>
