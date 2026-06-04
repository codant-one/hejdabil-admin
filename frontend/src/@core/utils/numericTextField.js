export const numericTextFieldProps = Object.freeze({
  type: 'text',
  inputmode: 'numeric',
})

export const decimalTextFieldProps = Object.freeze({
  type: 'text',
  inputmode: 'decimal',
})

export const normalizeNumericTextInput = value => String(value ?? '').replace(/\D/g, '')

export const normalizeDecimalTextInput = value => {
  const normalizedValue = String(value ?? '').replace(/,/g, '.').replace(/[^\d.]/g, '')
  const [integerPart = '', ...fractionParts] = normalizedValue.split('.')

  if (!fractionParts.length)
    return integerPart

  return `${integerPart}.${fractionParts.join('')}`
}

export const numericRangeValidator = ({ min = null, max = null } = {}) => value => {
  const normalizedValue = String(value ?? '').trim()

  if (!normalizedValue)
    return true

  if (!/^\d+$/.test(normalizedValue))
    return 'Faltet far endast innehalla siffror'

  const numericValue = Number(normalizedValue)

  if (min !== null && numericValue < min)
    return `Vardet maste vara minst ${min}`

  if (max !== null && numericValue > max)
    return `Vardet far inte vara storre an ${max}`

  return true
}

export const decimalRangeValidator = ({ min = null, max = null } = {}) => value => {
  const normalizedValue = String(value ?? '').trim().replace(/,/g, '.')

  if (!normalizedValue)
    return true

  if (!/^\d+(\.\d+)?$/.test(normalizedValue))
    return 'Faltet far endast innehalla siffror'

  const numericValue = Number(normalizedValue)

  if (min !== null && numericValue < min)
    return `Vardet maste vara minst ${min}`

  if (max !== null && numericValue > max)
    return `Vardet far inte vara storre an ${max}`

  return true
}

export const handleNumericTextFieldKeydown = event => {
  const allowedKeys = [
    'Backspace',
    'Delete',
    'Tab',
    'Enter',
    'Escape',
    'ArrowLeft',
    'ArrowRight',
    'ArrowUp',
    'ArrowDown',
    'Home',
    'End',
  ]

  if (allowedKeys.includes(event.key)) return

  if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase())) return

  if (/^\d$/.test(event.key)) return

  event.preventDefault()
}

export const handleDecimalTextFieldKeydown = event => {
  const allowedKeys = [
    'Backspace',
    'Delete',
    'Tab',
    'Enter',
    'Escape',
    'ArrowLeft',
    'ArrowRight',
    'ArrowUp',
    'ArrowDown',
    'Home',
    'End',
  ]

  if (allowedKeys.includes(event.key)) return

  if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase())) return

  if (/^\d$/.test(event.key)) return

  if ((event.key === '.' || event.key === ',') && !String(event.target?.value ?? '').includes('.') && !String(event.target?.value ?? '').includes(',')) return

  event.preventDefault()
}
