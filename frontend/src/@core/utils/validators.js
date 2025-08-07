import { isEmpty, isEmptyArray, isNullOrUndefined } from './index'

// 👉 Required Validator
export const requiredValidator = value => {
  if (isNullOrUndefined(value) || isEmptyArray(value) || value === false)
    return 'krävs *'
  
  return !!String(value).trim().length || 'krävs *'
}

// 👉 Email Validator
export const emailValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
  if (Array.isArray(value))
    return value.every(val => re.test(String(val))) || 'E-postadressen måste vara en giltig e-postadress'
  
  return re.test(String(value)) || 'E-postadressen måste vara en giltig e-postadress'
}

// 👉 Password Validator
export const passwordValidator = password => {
  // const regExp = /(?=.*\d){0,1}(?=.*[a-z|A-Z]){8,}/
  const regExp = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&.,#^(_\-+=)`~{}/;'"|:<>\\\[\\\]])[A-Za-z\d$@$!%*?&.,#^(_\-+=)`~{}/;'"|:<>\\\[\\\]]{8,}$/

  const validPassword = regExp.test(password) && (password.length>=8)
  
  return (
    // eslint-disable-next-line operator-linebreak
    validPassword ||
        'Fältet måste innehålla versaler, gemener och siffror; med minst 8 tecken')
}

// 👉 Confirm Password Validator
export const confirmedValidator = (value, target) => value === target || 'Lösenorden stämmer inte överens.'

// 👉 Between Validator
export const betweenValidator = (value, min, max) => {
  const valueAsNumber = Number(value)
  
  return (Number(min) <= valueAsNumber && Number(max) >= valueAsNumber) || `Ange talet mellan ${min} och ${max}.`
}

// 👉 Integer Validator
export const integerValidator = value => {
  if (isEmpty(value))
    return true
  if (Array.isArray(value))
    return value.every(val => /^-?[0-9]+$/.test(String(val))) || 'Detta fält måste vara ett heltal'
  
  return /^-?[0-9]+$/.test(String(value)) || 'Detta fält måste vara ett heltal'
}

// 👉 Regex Validator
export const regexValidator = (value, regex) => {
  if (isEmpty(value))
    return true
  let regeX = regex
  if (typeof regeX === 'string')
    regeX = new RegExp(regeX)
  if (Array.isArray(value))
    return value.every(val => regexValidator(val, regeX))
  
  return regeX.test(String(value)) || 'Regex-fältets format är inte giltigt'
}

// 👉 Alpha Validator
export const alphaValidator = value => {
  if (isEmpty(value))
    return true
  
  return /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(String(value)) || 'Fältet kan endast innehålla alfabetiska tecken'
}

// 👉 URL Validator
export const urlValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^(http[s]?:\/\/){0,1}(www\.){0,1}[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,5}[\.]{0,1}/
  
  return re.test(String(value)) || 'URL är inte giltig'
}

// 👉 Length Validator
export const lengthValidator = (length) => {
  return (value) => {
    if (isEmpty(value)) return true
    return String(value).length === length || `Numret måste bestå av ${length} siffror.`
  }
}

// 👉 Alpha-dash Validator
export const alphaDashValidator = value => {
  if (isEmpty(value))
    return true
  const valueAsString = String(value)
  
  return /^[0-9A-Z_-]*$/i.test(valueAsString) || 'Alla tecken är ogiltiga'
}

// 👉 phone Validator
export const phoneValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s./0-9]*$/
  if (Array.isArray(value))
    return value.every(val => re.test(String(val))) || 'Fältet måste vara ett giltigt telefonnummer'
  
  return re.test(String(value)) || 'Fältet måste vara ett giltigt telefonnummer'
}

// 👉 file Validator
export const fileSizeValidator = value => {
  if (isEmpty(value))
    return true
  for (let i = 0; i < value.length; i++) {
    if (value[i].size > 1048576) {
      return 'The size of file "' + value[i].name + '" kan inte vara större än 1 MB' // Mensaje de error
    }
  }
  
  return true
}

// 👉 connection Validator
export const connectionValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^[\w-]+:[\w-]+@[\w.-]+:\d+\/[\w-]+$/
  if (Array.isArray(value))
    return value.every(val => re.test(String(val))) || 'Fältet måste matcha detta mönster USER_DATABASE:PASSWORD_DATABASE@HOST_DATABASE:PORT/NAME_DATABASE'
  
  return re.test(String(value)) || 'Fältet måste matcha detta mönster USER_DATABASE:PASSWORD_DATABASE@HOST_DATABASE:PORT/NAME_DATABASE'
  
}

export const fileMineValidator = value => {
  if (isEmpty(value))
    return true

  const allowedTypes = [
    'application/pdf',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  ]
    
  for (let i = 0; i < value.length; i++) {
    if (!allowedTypes.includes(value[0].type)) {
      return 'Endast Word-, Excel- och PDF-filer är tillåtna.'
      this.$refs.fileInput.value = ''
    }
  }
  
  return true
}

export const yearValidator = value => {
  if (!value) return true

  const currentYear = new Date().getFullYear()
  const year = Number(value)

  const isValid = Number.isInteger(year) &&
    value.toString().length === 4 &&
    year >= 1900 && year <= currentYear + 1

  return isValid || 'Året måste vara ett giltigt årtal'
}
