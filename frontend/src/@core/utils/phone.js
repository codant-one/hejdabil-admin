const normalizeText = value =>
  String(value ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')

export const PHONE_INPUT_DEFAULTS = Object.freeze({
  defaultCountryId: 205,
  defaultPhoneCode: '46',
  defaultPhoneDigits: 9,
  stripLeadingZeroCountryId: 205,
})

const resolvePhoneOptions = options => ({
  ...PHONE_INPUT_DEFAULTS,
  ...(options ?? {}),
})

export const resolvePhoneCountry = (countries, country) => {
  if (!country || !Array.isArray(countries)) return null

  if (typeof country === 'object')
    return countries.find(item => String(item.id) === String(country.id)) || null

  return countries.find(item => String(item.id) === String(country))
    || countries.find(item => normalizeText(item.name) === normalizeText(country))
}

export const getPhoneInputConfig = (countries, country = null, options = {}) => {
  const phoneOptions = resolvePhoneOptions(options)
  const resolvedCountry = country ?? phoneOptions.defaultCountryId
  const selectedCountry = resolvePhoneCountry(countries, resolvedCountry)
  const phonecode = String(selectedCountry?.phonecode ?? phoneOptions.defaultPhoneCode).replace(/\D/g, '') || phoneOptions.defaultPhoneCode
  const parsedPhoneDigits = Number(selectedCountry?.phone_digits)

  const phoneDigits = Number.isFinite(parsedPhoneDigits) && parsedPhoneDigits > 0
    ? parsedPhoneDigits
    : phoneOptions.defaultPhoneDigits

  return {
    selectedCountry,
    phonecode,
    phoneDigits,
  }
}

export const shouldStripPhoneLeadingZero = (countries, country = null, options = {}) => {
  const phoneOptions = resolvePhoneOptions(options)
  const resolvedCountry = country ?? phoneOptions.defaultCountryId
  const selectedCountry = resolvePhoneCountry(countries, resolvedCountry)
  const selectedCountryId = selectedCountry?.id ?? resolvedCountry

  return String(selectedCountryId) === String(phoneOptions.stripLeadingZeroCountryId)
}

export const normalizePhoneInput = (value, countries, country = null, options = {}) => {
  const digits = String(value ?? '').replace(/\D/g, '')
  const { phonecode, phoneDigits } = getPhoneInputConfig(countries, country, options)
  const normalizedDigits = shouldStripPhoneLeadingZero(countries, country, options)
    ? digits.replace(/^0+/, '')
    : digits

  if (!normalizedDigits)
    return ''

  if (phonecode && normalizedDigits.startsWith(phonecode) && normalizedDigits.length - phonecode.length === phoneDigits)
    return normalizedDigits.slice(phonecode.length)

  return normalizedDigits.slice(0, phoneDigits)
}

export const formatPhonePayload = (value, countries, country = null, options = {}) => {
  const localNumber = normalizePhoneInput(value, countries, country, options)
  const { phonecode } = getPhoneInputConfig(countries, country, options)

  return localNumber ? `+${phonecode}${localNumber}` : ''
}