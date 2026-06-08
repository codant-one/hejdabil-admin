import { formatDateTime, formatDateYMD, formatNumber, formatNumberInteger, formatCommentDate } from '@/@core/utils/formatters'

const formatterRegistry = {
  formatDateTime,
  formatDateYMD,
  formatNumber,
  formatNumberInteger,
  formatCommentDate,
}

const resolveNamedFormatter = formatterName => {
  if (typeof formatterName !== 'string')
    return null

  return typeof formatterRegistry[formatterName] === 'function' ? formatterRegistry[formatterName] : null
}

const resolveFormatterOptions = (defaultFormatter, formatterOrOptions, options = {}) => {
  const namedFormatter = resolveNamedFormatter(formatterOrOptions)

  if (typeof formatterOrOptions === 'function' || typeof namedFormatter === 'function') {
    return {
      formatter: typeof formatterOrOptions === 'function' ? formatterOrOptions : namedFormatter,
      ...options,
    }
  }

  const resolvedOptions = formatterOrOptions && typeof formatterOrOptions === 'object' && !Array.isArray(formatterOrOptions)
    ? {
        ...formatterOrOptions,
        formatter: resolveNamedFormatter(formatterOrOptions.formatter) ?? formatterOrOptions.formatter,
      }
    : {}

  return {
    formatter: defaultFormatter,
    ...resolvedOptions,
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

const normalizeComparableValue = value => String(value ?? '').trim().toLowerCase()

const agreementTypeAliases = {
  1: ['1', 'försäljningsavtal', 'sales-agreement', 'sales'],
  2: ['2', 'inköpsavtal', 'purchase-agreement', 'purchase'],
  3: ['3', 'förmedlingsavtal', 'mediation-agreement', 'mediation'],
  4: ['4', 'prisförslag', 'business-proposal', 'business', 'proposal'],
}

const resolveAgreementTypeMatchers = agreementTypes => {
  const requestedTypes = (Array.isArray(agreementTypes) ? agreementTypes : [agreementTypes])
    .map(normalizeComparableValue)
    .filter(Boolean)

  const allowedTypes = new Set()

  requestedTypes.forEach(requestedType => {
    allowedTypes.add(requestedType)

    Object.values(agreementTypeAliases).forEach(aliases => {
      const normalizedAliases = aliases.map(normalizeComparableValue)

      if (normalizedAliases.includes(requestedType))
        normalizedAliases.forEach(alias => allowedTypes.add(alias))
    })
  })

  return allowedTypes
}

const getAgreementTypeCandidates = ({ oldValues = {}, newValues = {} } = {}) => {
  return [
    newValues?.agreement_type_id,
    newValues?.agreement_type,
    oldValues?.agreement_type_id,
    oldValues?.agreement_type,
  ]
    .map(normalizeComparableValue)
    .filter(Boolean)
}

export const fieldConfig = (key, options = {}) => resolveFieldConfig(key, options)

export const textField = (key, options = {}) => fieldConfig(key, options)

export const conditionalField = (key, visibleWhen, options = {}) => fieldConfig(key, {
  visibleWhen,
  ...options,
})

export const agreementTypeField = (key, agreementTypes, options = {}) => {
  const allowedTypes = resolveAgreementTypeMatchers(agreementTypes)

  return conditionalField(key, context => {
    const currentAgreementTypes = getAgreementTypeCandidates(context)

    return currentAgreementTypes.some(type => allowedTypes.has(type))
  }, options)
}

export const customField = (key, formatter, options = {}) => fieldConfig(key, {
  formatter,
  ...options,
})

export const objectPropertyField = (key, property, options = {}) => {
  const fieldOptions = options && typeof options === 'object' && !Array.isArray(options) ? options : {}
  const optionFormatter = typeof fieldOptions.formatter === 'function' ? fieldOptions.formatter : null

  return fieldConfig(key, {
    ...fieldOptions,
    formatter: value => {
      const extractedValue = value && typeof value === 'object' && value[property] !== undefined
        ? value[property]
        : value

      return optionFormatter ? optionFormatter(extractedValue) : extractedValue
    },
  })
}

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

export const objectBooleanField = (key, property, trueLabel = 'Ja', falseLabel = 'Nej', options = {}) => objectPropertyField(key, property, {
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

export const objectOptionField = (key, property, labels = [], options = {}) => objectPropertyField(key, property, {
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

const noteActivityFields = [
  'reg_num',
  currencyField('note'),
  'name',
  'phone',
  'landline',
  'email',
  'comment',
  'comments_note',
  'user_name',
  dateField('comments_date', 'formatCommentDate'),
]

export const activityVisibleFieldsByModule = {
  /*agreements: [
    'agreement_type_id',
    'vehicle_client_id',
    'vehicle_interchange_id',
    agreementTypeField('guaranty_type_id', [1]),
    agreementTypeField('insurance_type_id', [1]),
    'payment_type_id',
    'iva_id',
    'advance_id',
    'vehicle_payment_id',
    objectPropertyField('offer_number', 'offer_id', { sourceKey: 'offer_id', label: 'Offertnummer' }),
    relationField('client'),
    objectPropertyField('offer_reg_num', 'reg_num', { sourceKey: 'offer_id', label: 'Reg nr' }),
    objectPropertyField('offer_car_name', 'car_name', { sourceKey: 'offer_id', label: 'Bilnamn' }),
    objectPropertyField('offer_brand_name', 'brand_name', { sourceKey: 'offer_id', label: 'Märke' }),
    objectPropertyField('offer_model_name', 'model_name', { sourceKey: 'offer_id', label: 'Modell' }),
    objectPropertyField('offer_year', 'year', { sourceKey: 'offer_id', label: 'Årsmodell' }),
    objectPropertyField('offer_color', 'color', { sourceKey: 'offer_id', label: 'Färg' }),
    objectPropertyField('offer_mileage', 'mileage', { sourceKey: 'offer_id', label: 'Miltal', formatter: formatNumberInteger, suffix: ' Mil' }),
    objectPropertyField('offer_generation', 'generation', { sourceKey: 'offer_id', label: 'Generation' }),
    objectPropertyField('offer_car_body', 'car_body', { sourceKey: 'offer_id', label: 'Kaross' }),
    objectPropertyField('offer_purchase_date', 'purchase_date', { sourceKey: 'offer_id', label: 'Inköpsdatum' }),
    objectPropertyField('offer_chassis', 'chassis', { sourceKey: 'offer_id', label: 'Chassinummer' }),
    objectPropertyField('offer_control_inspection', 'control_inspection', { sourceKey: 'offer_id', label: 'Kontrollbesiktning gäller tom' }),
    objectPropertyField('offer_fuel_name', 'fuel_name', { sourceKey: 'offer_id', label: 'Drivmedel' }),
    objectPropertyField('offer_gearbox_name', 'gearbox_name', { sourceKey: 'offer_id', label: 'Växellåda' }),
    objectPropertyField('offer_engine', 'engine', { sourceKey: 'offer_id', label: 'Motor' }),
    objectPropertyField('offer_number_keys', 'number_keys', { sourceKey: 'offer_id', label: 'Antal nycklar' }),
    objectOptionField('offer_service_book', 'service_book', ['Ja', 'Nej'], { sourceKey: 'offer_id', label: 'Servicebok finns?' }),
    objectOptionField('offer_summer_tire', 'summer_tire', ['Ja', 'Nej'], { sourceKey: 'offer_id', label: 'Sommardäck finns?' }),
    objectOptionField('offer_winter_tire', 'winter_tire', ['Ja', 'Nej'], { sourceKey: 'offer_id', label: 'Vinterdäck finns?' }),
    objectOptionField('offer_dist_belt', 'dist_belt', ['Ja', 'Nej', 'Kamkedja', 'Vet ej'], { sourceKey: 'offer_id', label: 'Kamrem bytt?' }),
    compoundField('offer_last_service_info', ['last_service', 'last_service_date'], value => {
      const mileage = value?.last_service
      const rawServiceDate = value?.last_service_date
      const serviceDate = typeof rawServiceDate === 'string' && ['null', 'undefined', ''].includes(rawServiceDate.trim().toLowerCase())
        ? null
        : rawServiceDate

      if ((mileage === null || mileage === undefined || mileage === '') && !serviceDate)
        return null

      return `${String(mileage ?? 0).replace(/,/g, '')} Mil / ${serviceDate ?? '0000-00-00'}`
    }, { label: 'Senaste service Mil/datum', sourceKey: 'offer_id' }), // <-- Aquí está el sourceKey en lugar de usar objectPropertyField

    compoundField('offer_last_dist_belt_info', ['last_dist_belt', 'last_dist_belt_date'], value => {
      const mileage = value?.last_dist_belt
      const rawDistBeltDate = value?.last_dist_belt_date
      const distBeltDate = typeof rawDistBeltDate === 'string' && ['null', 'undefined', ''].includes(rawDistBeltDate.trim().toLowerCase())
        ? null
        : rawDistBeltDate

      if ((mileage === null || mileage === undefined || mileage === '') && !distBeltDate)
        return null

      return `${String(mileage ?? 0).replace(/,/g, '')} Mil / ${distBeltDate ?? '0000-00-00'}`
    }, { label: 'Kamrem bytt vid Mil/datum', sourceKey: 'offer_id' }), // <-- sourceKey mapeado a offer_id
    objectPropertyField('offer_comment', 'comment', { sourceKey: 'offer_id', label: 'Anmärkning' }),

    'commission_id',
    fieldConfig('agreement_id', { sourceKey: 'agreement_id', label: 'asdasd' }),
    'send_reminder',
    'sale_date',
    'trade_price',
    agreementTypeField('residual_debt', [1, 3]),
    'residual_price',
    'fair_value',
    currencyField('price'),
    'iva_sale_amount',
    'iva_sale_exclusive',
    'iva_purchase_amount',
    'iva_purchase_exclusive',
    'discount',
    'registration_fee',
    'total_sale',
    'middle_price',
    'payment_type',
    'payment_received',
    'payment_method_forcash',
    'installment_amount',
    'installment_contract_upon_delivery',
    agreementTypeField('guaranty', [1]),
    agreementTypeField('guaranty_description', [1]),
    agreementTypeField('insurance_company', [1]),
    agreementTypeField('insurance_company_description', [1]),
    'payment_description',
    'terms_other_conditions',
    'terms_other_information',
  ],*/
   agreements: [
    'agreement_type_id',
    'vehicle_client_id',
    'vehicle_interchange_id',
    agreementTypeField('guaranty_type_id', [1]),
    agreementTypeField('insurance_type_id', [1]),
    'payment_type_id',
    'iva_id',
    'advance_id',
    'vehicle_payment_id',
    
    objectPropertyField('offer_number', 'offer_id', { sourceKey: 'offer_id', label: 'Offertnummer' }),
    // Datos del Cliente (se leen directo del nivel principal)
    relationField('client'),
    
    // Datos del Vehículo / Oferta (se leen directo del nivel principal)
    fieldConfig('reg_num', { label: 'Reg nr' }),
    fieldConfig('car_name', { label: 'Bilnamn' }),
    fieldConfig('brand_name', { label: 'Märke' }),
    fieldConfig('model_name', { label: 'Modell' }),
    fieldConfig('year', { label: 'Årsmodell' }),
    fieldConfig('color', { label: 'Färg' }),
    fieldConfig('mileage', { label: 'Miltal', formatter: formatNumberInteger, suffix: ' Mil' }),
    fieldConfig('generation', { label: 'Generation' }),
    fieldConfig('car_body', { label: 'Kaross' }),
    fieldConfig('purchase_date', { label: 'Inköpsdatum' }),
    fieldConfig('chassis', { label: 'Chassinummer' }),
    fieldConfig('control_inspection', { label: 'Kontrollbesiktning gäller tom' }),
    fieldConfig('fuel_name', { label: 'Drivmedel' }),
    fieldConfig('gearbox_name', { label: 'Växellåda' }),
    fieldConfig('engine', { label: 'Motor' }),
    fieldConfig('number_keys', { label: 'Antal nycklar' }),
    booleanField('service_book', 'Ja', 'Nej', { label: 'Servicebok finns?' }),
    booleanField('summer_tire', 'Ja', 'Nej', { label: 'Sommardäck finns?' }),
    booleanField('winter_tire', 'Ja', 'Nej', { label: 'Vinterdäck finns?' }),
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
    
    fieldConfig('comment', { label: 'Anmärkning' }),

    // Resto de campos del acuerdo
    'commission_id',
    fieldConfig('agreement_id', { label: 'Avtals-ID' }),
    'send_reminder',
    'sale_date',
    'trade_price',
    agreementTypeField('residual_debt', [1, 3]),
    'residual_price',
    'fair_value',
    currencyField('price'),
    'iva_sale_amount',
    'iva_sale_exclusive',
    'iva_purchase_amount',
    'iva_purchase_exclusive',
    'discount',
    'registration_fee',
    'total_sale',
    'middle_price',
    'payment_type',
    'payment_received',
    'payment_method_forcash',
    'installment_amount',
    'installment_contract_upon_delivery',
    agreementTypeField('guaranty', [1]),
    agreementTypeField('guaranty_description', [1]),
    agreementTypeField('insurance_company', [1]),
    agreementTypeField('insurance_company_description', [1]),
    'payment_description',
    'terms_other_conditions',
    'terms_other_information',
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
    currencyField('total'),
    'email',
    'phone'
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
    'order_id',
    'send_reminder',
    'title',
    'description',
    'email',
    'phone'
  ],
  notes: noteActivityFields,
  comment_notes: noteActivityFields,
  payouts: [
    'swish_id',
    'fullname',
    'reference',
    currencyField('amount'),
    'payee_alias',
    'payee_ssn',   
    'payout_state_name',
    'message',
    'error_message',
    'error_code'
  ],
  vehicles: [
    fieldConfig('car_name', { sourceKey: 'car_name', label: 'Bilnamn' }),
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