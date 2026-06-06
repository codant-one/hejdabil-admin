import { formatDateTime, formatDateYMD, formatNumber } from '@/@core/utils/formatters'

const resolveFormatterOptions = (defaultFormatter, formatterOrOptions, options = {}) => {
  if (typeof formatterOrOptions === 'function') {
    return {
      formatter: formatterOrOptions,
      ...options,
    }
  }

  return {
    formatter: defaultFormatter,
    ...(formatterOrOptions ?? {}),
  }
}

export const fieldConfig = (key, options = {}) => ({
  key,
  ...options,
})

export const textField = (key, options = {}) => fieldConfig(key, options)

export const customField = (key, formatter, options = {}) => fieldConfig(key, {
  formatter,
  ...options,
})

export const relationField = (key, options = {}) => fieldConfig(`${key}_name`, options)

export const dateField = (key, formatterOrOptions = formatDateYMD, options = {}) => fieldConfig(
  key,
  resolveFormatterOptions(formatDateYMD, formatterOrOptions, options),
)

export const dateTimeField = (key, formatterOrOptions = formatDateTime, options = {}) => fieldConfig(
  key,
  resolveFormatterOptions(formatDateTime, formatterOrOptions, options),
)

export const currencyField = (key, options = {}) => fieldConfig(key, {
  formatter: formatNumber,
  suffix: ' kr',
  ...options,
})

export const suffixField = (key, suffix, options = {}) => fieldConfig(key, {
  suffix,
  ...options,
})

export const prefixField = (key, prefix, options = {}) => fieldConfig(key, {
  prefix,
  ...options,
})

export const percentField = (key, options = {}) => suffixField(key, ' %', options)

export const booleanField = (key, trueLabel = 'Ja', falseLabel = 'Nej', options = {}) => fieldConfig(key, {
  formatter: value => {
    const normalizedValue = typeof value === 'string' ? value.trim().toLowerCase() : value

    if (normalizedValue === true || normalizedValue === 1 || normalizedValue === '1' || normalizedValue === 'true')
      return trueLabel

    if (normalizedValue === false || normalizedValue === 0 || normalizedValue === '0' || normalizedValue === 'false')
      return falseLabel

    return value
  },
  ...options,
})

export const activityVisibleFieldsByModule = {
  agreements: [
    'agreement_id',
    currencyField('amount'),
    'comments',
    'description',
    'email',
    'fullname',
    currencyField('installment_amount'),
    'notes',
    'offer_id',
    'payment_method',
    'phone',
    'reference',
    'state_id',
    'street',
    'title',
  ],
  billings: [
    'invoice_id',
    relationField('client'),
    'reference',
    relationField('state'),
    'invoice_date',
    'due_date',
    suffixField('payment_terms', ' dagar netto'),
    fieldConfig('detail', { renderType: 'billing-detail' }),
    percentField('discount'),
    currencyField('amount_discount'),
    currencyField('amount_tax'),
    percentField('tax'),
    currencyField('subtotal'),
    currencyField('total')
  ],
  clients: [
    'fullname',
    'email',
    relationField('client_type'),
    'organization_number',
    relationField('country'),
    'num_iva',
    'phone',
    'landline',
    'address',
    'street',
    'postal_code',
    'reference',
    'comments',
  ],
  documents: [
    'comments',
    'description',
    'email',
    'notes',
    'phone',
    'reference',
    'state_id',
    'title',
  ],
  notes: [
    'comments',
    'description',
    'notes',
    'title',
  ],
  payouts: [
    'account',
    currencyField('amount'),
    'comments',
    'description',
    'reference',
    'state_id',
    'title',
  ],
  vehicles: [
    currencyField('amount'),
    'comments',
    'description',
    'notes',
    'num_iva',
    'payment_method',
    'reference',
    'reg_num',
    'state_id',
    'title',
  ],
}

export function getActivityVisibleFields(entityType) {
  return activityVisibleFieldsByModule[entityType] ?? null
}