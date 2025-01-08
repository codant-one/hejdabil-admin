import { isEmpty, isEmptyArray, isNullOrUndefined } from './index'

// 👉 Required Validator
export const requiredValidator = value => {
  if (isNullOrUndefined(value) || isEmptyArray(value) || value === false)
    return 'required *'
  
  return !!String(value).trim().length || 'required *'
}

// 👉 Email Validator
export const emailValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
  if (Array.isArray(value))
    return value.every(val => re.test(String(val))) || 'Email must be a valid email'
  
  return re.test(String(value)) || 'Email must be a valid email'
}

// 👉 Password Validator
export const passwordValidator = password => {
  // const regExp = /(?=.*\d){0,1}(?=.*[a-z|A-Z]){8,}/
  const regExp = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&.,#^(_\-+=)`~{}/;'"|:<>\\\[\\\]])[A-Za-z\d$@$!%*?&.,#^(_\-+=)`~{}/;'"|:<>\\\[\\\]]{8,}$/

  const validPassword = regExp.test(password) && (password.length>=8)
  
  return (
    // eslint-disable-next-line operator-linebreak
    validPassword ||
        'The field must contain uppercase, lowercase and digits; with a minimum of 8 characters')
}

// 👉 Confirm Password Validator
export const confirmedValidator = (value, target) => value === target || 'Passwords do not match.'

// 👉 Between Validator
export const betweenValidator = (value, min, max) => {
  const valueAsNumber = Number(value)
  
  return (Number(min) <= valueAsNumber && Number(max) >= valueAsNumber) || `Enter the number between ${min} and ${max}`
}

// 👉 Integer Validator
export const integerValidator = value => {
  if (isEmpty(value))
    return true
  if (Array.isArray(value))
    return value.every(val => /^-?[0-9]+$/.test(String(val))) || 'This field must be an integer'
  
  return /^-?[0-9]+$/.test(String(value)) || 'This field must be an integer'
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
  
  return regeX.test(String(value)) || 'The Regex field format is not valid'
}

// 👉 Alpha Validator
export const alphaValidator = value => {
  if (isEmpty(value))
    return true
  
  return /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(String(value)) || 'The field can only contain alphabetic characters'
}

// 👉 URL Validator
export const urlValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^(http[s]?:\/\/){0,1}(www\.){0,1}[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,5}[\.]{0,1}/
  
  return re.test(String(value)) || 'URL is not valid'
}

// 👉 Length Validator
export const lengthValidator = (value, length) => {
  if (isEmpty(value))
    return true
  
  return String(value).length === length || `The Min Character field must be at least ${length} characters`
}

// 👉 Alpha-dash Validator
export const alphaDashValidator = value => {
  if (isEmpty(value))
    return true
  const valueAsString = String(value)
  
  return /^[0-9A-Z_-]*$/i.test(valueAsString) || 'All characters are invalid'
}

// 👉 phone Validator
export const phoneValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s./0-9]*$/
  if (Array.isArray(value))
    return value.every(val => re.test(String(val))) || 'The field must be a valid phone number'
  
  return re.test(String(value)) || 'The field must be a valid phone number'
}

// 👉 file Validator
export const fileSizeValidator = value => {
  if (isEmpty(value))
    return true
  for (let i = 0; i < value.length; i++) {
    if (value[i].size > 1048576) {
      return 'The size of file "' + value[i].name + '" cannot be greater than 1MB' // Mensaje de error
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
    return value.every(val => re.test(String(val))) || 'The field must match this pattern USER_DATABASE:PASSWORD_DATABASE@HOST_DATABASE:PORT/NAME_DATABASE'
  
  return re.test(String(value)) || 'The field must match this pattern USER_DATABASE:PASSWORD_DATABASE@HOST_DATABASE:PORT/NAME_DATABASE'
  
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
      return 'Only Word, Excel and PDF files are allowed.'
      this.$refs.fileInput.value = ''
    }
  }
  
  return true
}
