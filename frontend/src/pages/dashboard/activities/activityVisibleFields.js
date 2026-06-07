import { formatDateTime, formatDateYMD, formatNumber, formatNumberInteger } from '@/@core/utils/formatters'

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

const resolveFieldConfig = (fieldOrKey, options = {}) => {
  if (fieldOrKey && typeof fieldOrKey === 'object' && !Array.isArray(fieldOrKey)) {
    return {
      ...fieldOrKey,
      ...options,
    }
  }

  return {
    key: fieldOrKey,
    ...options,
  }
}

export const fieldConfig = (key, options = {}) => resolveFieldConfig(key, options)

export const textField = (key, options = {}) => fieldConfig(key, options)

export const customField = (key, formatter, options = {}) => fieldConfig(key, {
  formatter,
  ...options,
})

export const compoundField = (key, sourceKeys, formatter, options = {}) => fieldConfig(key, {
  sourceKeys,
  formatter,
  ...options,
})

export const numberField = (key, formatterOrOptions = formatNumber, options = {}) => fieldConfig(
  key,
  resolveFormatterOptions(formatNumber, formatterOrOptions, options),
)

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

export const optionField = (key, labels = [], options = {}) => fieldConfig(key, {
  formatter: value => {
    const normalizedValue = typeof value === 'string' ? value.trim().toLowerCase() : value

    if (Array.isArray(labels) && labels.length) {
      if (typeof normalizedValue === 'number' && labels[normalizedValue] !== undefined)
        return labels[normalizedValue]

      if (typeof normalizedValue === 'string' && /^\d+$/.test(normalizedValue)) {
        const index = Number(normalizedValue)

        if (labels[index] !== undefined)
          return labels[index]
      }
    }

    return value
  },
  ...options,
})

export const booleanField = (key, trueLabel = 'Ja', falseLabel = 'Nej', options = {}) => {
  if (Array.isArray(trueLabel))
    return optionField(key, trueLabel, falseLabel ?? {})

  return fieldConfig(key, {
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
}

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
    fieldConfig('car_name', { sourceKey: 'car_name', label: 'Bilinfo' }),
    'reg_num',
    'brand_name',
    'model_name',
    'year',
    'color',
    suffixField(numberField('mileage', formatNumberInteger), ' Mil'),
    'generation',
    relationField('car_body'),
    'purchase_date',
    'chassis',
    'control_inspection',
    'fuel_name',
    'gearbox_name',
    'engine',
    'number_keys',
    booleanField('service_book', ['Ja', 'Nej']),
    booleanField('summer_tire', ['Ja', 'Nej']),
    booleanField('winter_tire', ['Ja', 'Nej']),
    optionField('dist_belt', ['Ja', 'Nej', 'Kamkedja', 'Vet ej'], { label: 'Kamrem bytt?' }),
    compoundField('last_service_info', ['last_service', 'last_service_date'], value => {
      const mileage = value?.last_service
      const rawServiceDate = value?.last_service_date
      const serviceDate = typeof rawServiceDate === 'string' && ['null', 'undefined', ''].includes(rawServiceDate.trim().toLowerCase())
        ? null
        : rawServiceDate

      if ((mileage === null || mileage === undefined || mileage === '') && !serviceDate)
        return null

      return `${String(mileage ?? 0).replace(/,/g, '')} Mil / ${serviceDate ?? '0000-00-00'}`
    }, { label: 'Senaste service Mil/datum' }),
    compoundField('last_dist_belt_info', ['last_dist_belt', 'last_dist_belt_date'], value => {
      const mileage = value?.last_dist_belt
      const rawDistBeltDate = value?.last_dist_belt_date
      const distBeltDate = typeof rawDistBeltDate === 'string' && ['null', 'undefined', ''].includes(rawDistBeltDate.trim().toLowerCase())
        ? null
        : rawDistBeltDate

      if ((mileage === null || mileage === undefined || mileage === '') && !distBeltDate)
        return null

      return `${String(mileage ?? 0).replace(/,/g, '')} Mil / ${distBeltDate ?? '0000-00-00'}`
    }, { label: 'Kamrem bytt vid Mil/datum' }),
    'comments',
    currencyField('purchase_price'),
    currencyField('costs', { label: 'Kostnader' }),
    'iva_purchase_name',
    'state_name',
    fieldConfig('purchase_client', { sourceKey: 'purchase_client', label: 'Säljaren' }),
    fieldConfig('sale_client', { sourceKey: 'sale_client', label: 'Köparen' }),
    'iva_sale_name',
    currencyField('sale_price'),
    'sale_date',
    'sale_comments',
    currencyField('iva_sale_amount'),
    currencyField('iva_sale_exclusive'),
    'iva_purchase_amount',
    'iva_purchase_exclusive',
    currencyField('discount'),
    currencyField('registration_fee'),
    currencyField('total_sale'),
  ],
}

export function getActivityVisibleFields(entityType) {
  return activityVisibleFieldsByModule[entityType] ?? null
}