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
   agreements: [
    'agreement_type_id',
    
    objectPropertyField('offer_number', 'offer_id', { sourceKey: 'offer_id', label: 'Offertnummer' }),
    objectPropertyField('commission_id', 'commission_id', { sourceKey: 'commission_id', label: 'Avtalsnummer' }),
    //fieldConfig('agreement_id', { label: 'Avtalsnummer' }),

    conditionalField('agreement_id', context => {
      const actionType = String(context?.activity?.action_type || '').toLowerCase();
      return !actionType.includes('send');
    }, { label: 'Avtalsnummer' }),

    // Datos del Cliente (se leen directo del nivel principal)
    relationField('client'),
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

    // Datos del Vehículo / Oferta (se leen directo del nivel principal)
    fieldConfig('car_name', { sourceKey: 'car_name', label: 'Bilnamn' }),
    fieldConfig('reg_num', { label: 'Reg nr' }),
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
    booleanField('service_book', ['Ja', 'Nej'], { label: 'Servicebok finns?' }),
    booleanField('summer_tire', ['Ja', 'Nej'], { label: 'Sommardäck finns?' }),
    booleanField('winter_tire', ['Ja', 'Nej'], { label: 'Vinterdäck finns?' }),
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
    fieldConfig('comments', { label: 'Anmärkning' }),

    // Inbytesfordon (snapshot från vehicle_interchange)
    fieldConfig('car_name_interchange', { label: 'Bilnamn (inbytesfordon)' }),
    fieldConfig('reg_num_interchange', { label: 'Reg nr (inbytesfordon)' }),
    fieldConfig('model_name_interchange', { sourceKey: 'model_id_interchange', label: 'Modell (inbytesfordon)' }),
    fieldConfig('year_interchange', { label: 'Årsmodell (inbytesfordon)' }),
    fieldConfig('color_interchange', { label: 'Färg (inbytesfordon)' }),
    fieldConfig('mileage_interchange', { label: 'Miltal (inbytesfordon)', formatter: formatNumberInteger, suffix: ' Mil' }),
    fieldConfig('generation_interchange', { label: 'Generation (inbytesfordon)' }),
    fieldConfig('car_body_interchange', { sourceKey: 'car_body_id_interchange', label: 'Kaross (inbytesfordon)' }),
    fieldConfig('purchase_date_interchange', { label: 'Inköpsdatum (inbytesfordon)' }),
    fieldConfig('chassis_interchange', { label: 'Chassinummer (inbytesfordon)' }),
    fieldConfig('control_inspection_interchange', { label: 'Kontrollbesiktning (inbytesfordon)' }),
    fieldConfig('fuel_name_interchange', { sourceKey: 'fuel_id_interchange', label: 'Drivmedel (inbytesfordon)' }),
    fieldConfig('gearbox_name_interchange', { sourceKey: 'gearbox_id_interchange', label: 'Växellåda (inbytesfordon)' }),
    fieldConfig('engine_interchange', { label: 'Motor (inbytesfordon)' }),
    fieldConfig('number_keys_interchange', { label: 'Antal nycklar (inbytesfordon)' }),
    booleanField('service_book_interchange', ['Ja', 'Nej'], { label: 'Servicebok finns? (inbytesfordon)' }),
    booleanField('summer_tire_interchange', ['Ja', 'Nej'], { label: 'Sommardäck finns? (inbytesfordon)' }),
    booleanField('winter_tire_interchange', ['Ja', 'Nej'], { label: 'Vinterdäck finns? (inbytesfordon)' }),
    optionField('dist_belt_interchange', ['Ja', 'Nej', 'Kamkedja', 'Vet ej'], { label: 'Kamrem bytt? (inbytesfordon)' }),
    compoundField('last_service_info_interchange', ['last_service_interchange', 'last_service_date_interchange'], value => {
      const mileage = value?.last_service_interchange
      const rawServiceDate = value?.last_service_date_interchange
      const serviceDate = typeof rawServiceDate === 'string' && ['null', 'undefined', ''].includes(rawServiceDate.trim().toLowerCase())
        ? null
        : rawServiceDate

      if ((mileage === null || mileage === undefined || mileage === '') && !serviceDate)
        return null

      return `${String(mileage ?? 0).replace(/,/g, '')} Mil / ${serviceDate ?? '0000-00-00'}`
    }, { label: 'Senaste service Mil/datum (inbytesfordon)' }),
    compoundField('last_dist_belt_info_interchange', ['last_dist_belt_interchange', 'last_dist_belt_date_interchange'], value => {
      const mileage = value?.last_dist_belt_interchange
      const rawDistBeltDate = value?.last_dist_belt_date_interchange
      const distBeltDate = typeof rawDistBeltDate === 'string' && ['null', 'undefined', ''].includes(rawDistBeltDate.trim().toLowerCase())
        ? null
        : rawDistBeltDate

      if ((mileage === null || mileage === undefined || mileage === '') && !distBeltDate)
        return null

      return `${String(mileage ?? 0).replace(/,/g, '')} Mil / ${distBeltDate ?? '0000-00-00'}`
    }, { label: 'Kamrem bytt vid Mil/datum (inbytesfordon)' }),
    fieldConfig('comments_interchange', { sourceKey: 'comments_interchange', label: 'Anteckningar (inbytesfordon)' }),

    // Resto de campos del acuerdo
    'send_reminder',
    'sale_date',
    currencyField('trade_price'),
    'residual_price',
    currencyField('fair_value'),
    currencyField('price'),
    'iva_id',
    currencyField('iva_sale_amount'),
    currencyField('iva_sale_exclusive'),
    currencyField('iva_purchase_amount'),
    currencyField('iva_purchase_exclusive'),
    currencyField('discount'),

    agreementTypeField(booleanField('guaranty', ['Ingen garanti', 'Ja'], { label: 'Garanti' }), [1]),
    agreementTypeField(fieldConfig('guaranty_description', { label: 'Garantibeskrivning' }), [1]),
    agreementTypeField(fieldConfig('guaranty_type_id', { label: 'Typ av garanti' }), [1]),
    agreementTypeField(booleanField('insurance_company', ['Ingen försäkring', 'Ja'], { label: 'Försäkring' }), [1]),
    agreementTypeField(fieldConfig('insurance_company_description', { label: 'Beskrivning av försäkringsbolag' }), [1]),
    agreementTypeField(fieldConfig('insurance_type_id', { label: 'Försäkringstyp' }), [1]),

    agreementTypeField(currencyField('registration_fee'), [1]),
    currencyField('total_sale'),
    currencyField('middle_price'),
    'payment_type',
    currencyField('payment_received'),
    'payment_method_forcash',
    currencyField('installment_amount'),
  
    fieldConfig('payment_type_id', { label: 'Typ av utbetalning till säljaren' }),

    'advance_id',
    'vehicle_payment_id',
      
    objectPropertyField('commission_number', 'commission_id', { sourceKey: 'commission_type_id', label: 'Typ av provision' }),
    objectPropertyField('commision_fee', 'commission_fee', { sourceKey: 'commission_fee', label: 'Provisionsavgift', formatter: formatNumber, suffix: ' kr' }),
    booleanField('outstanding_debt', ['Ja', 'Nej'], { label: 'Har fordonet restskuld' }),
    agreementTypeField(booleanField('residual_debt', ['Bilhandlare', 'Kund'], { label: 'Restskulden löses av' }), [1, 3]),
    objectPropertyField('remaining_debt', 'remaining_debt', { sourceKey: 'remaining_debt', label: 'Restskuld', formatter: formatNumber, suffix: ' kr' }),
    objectPropertyField('paid_bank', 'paid_bank', { sourceKey: 'paid_bank', label: 'Restskuld betalas till'}),
    objectPropertyField('bank_name', 'paid_bank', { sourceKey: 'bank_name', label: 'Bankens namn'}),
    objectPropertyField('account_number', 'account_number', { sourceKey: 'account_number', label: 'Kontonummer'}),
    objectPropertyField('payment_days', 'payment_days', { sourceKey: 'payment_days', label: 'Utbetalning antal bankdagar efter försäljning'}),
    'payment_description',
    fieldConfig('start_date', { label: 'Startdatum' }),
    fieldConfig('end_date', { label: 'Slutdatum' }),

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
    fieldConfig('purchase_client', { sourceKey: 'purchase_client', label: 'Säljaren' }),
    fieldConfig('purchase_email', { label: 'E-post (säljare)' }),
    fieldConfig('purchase_client_type_id', { label: 'Klienttyp (säljare)' }),
    fieldConfig('purchase_organization_number', { label: 'Org.nr (säljare)' }),
    fieldConfig('purchase_country_id', { label: 'Land (säljare)' }),
    fieldConfig('purchase_num_iva', { label: 'Momsnummer (säljare)' }),
    fieldConfig('purchase_phone', { label: 'Mobilnummer (säljare)' }),
    fieldConfig('purchase_landline', { label: 'Telefon (säljare)' }),
    fieldConfig('purchase_address', { label: 'Adress (säljare)' }),
    fieldConfig('purchase_street', { label: 'Stad (säljare)' }),
    fieldConfig('purchase_postal_code', { label: 'Postnummer (säljare)' }),
    fieldConfig('sale_client', { sourceKey: 'sale_client', label: 'Köparen' }),
    fieldConfig('sale_email', { label: 'E-post (köpare)' }),
    fieldConfig('sale_client_type_id', { label: 'Klienttyp (köpare)' }),    
    fieldConfig('sale_organization_number', { label: 'Org.nr (köpare)' }),
    fieldConfig('sale_country_id', { label: 'Land (köpare)' }),
    fieldConfig('sale_num_iva', { label: 'Momsnummer (köpare)' }),
    fieldConfig('sale_phone', { label: 'Mobilnummer (köpare)' }),
    fieldConfig('sale_landline', { label: 'Telefon(köpare)' }),
    fieldConfig('sale_address', { label: 'Adress (köpare)' }),
    fieldConfig('sale_street', { label: 'Stad (köpare)' }),
    fieldConfig('sale_postal_code', { label: 'Postnummer (köpare)' }),
    
  ],
}

export function getActivityVisibleFields(entityType) {
  return activityVisibleFieldsByModule[entityType] ?? null
}