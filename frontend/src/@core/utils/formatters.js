import { isToday } from './index'

export const avatarText = value => {
  if (!value)
    return ''
  const nameArray = value.split(' ')
  
  return nameArray.map(word => word.charAt(0).toUpperCase()).join('')
}

// TODO: Try to implement this: https://twitter.com/fireship_dev/status/1565424801216311297
export const kFormatter = num => {
  const regex = /\B(?=(\d{3})+(?!\d))/g
  
  return Math.abs(num) > 9999 ? `${Math.sign(num) * +((Math.abs(num) / 1000).toFixed(1))}k` : Math.abs(num).toFixed(0).replace(regex, ',')
}

/**
 * Format and return date in Humanize format
 * Intl docs: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/DateTimeFormat/format
 * Intl Constructor: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/DateTimeFormat/DateTimeFormat
 * @param {String} value date to format
 * @param {Intl.DateTimeFormatOptions} formatting Intl object to format with
 */
export const formatDate = (value, formatting = { month: 'short', day: 'numeric', year: 'numeric' }) => {
  if (!value)
    return value
  
  return new Intl.DateTimeFormat('en-US', formatting).format(new Date(value))
}

/**
 * Format date to YYYY/MM/DD format
 * @param {String} dateString - Date string to format (ISO format or any valid Date string)
 * @returns {String} Formatted date in YYYY/MM/DD format or empty string if no date provided
 * @example
 * formatDateYMD('2026-01-21T10:30:00') // Returns '2026/01/21'
 * formatDateYMD('2026-01-21') // Returns '2026/01/21'
 * formatDateYMD(null) // Returns ''
 */
export const formatDateYMD = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}/${month}/${day}`;
};

/**
 * Format date to YYYY/MM/DD HH:mm format (with time)
 * @param {String} dateString - Date string to format (ISO format or any valid Date string)
 * @returns {String} Formatted date in YYYY/MM/DD HH:mm format or empty string if no date provided
 * @example
 * formatDateTime('2026-01-21T10:30:00') // Returns '2026/01/21 10:30'
 * formatDateTime('2026-01-21T08:05:00') // Returns '2026/01/21 08:05'
 * formatDateTime(null) // Returns ''
 */
export const formatDateTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${year}/${month}/${day} ${hours}:${minutes}`;
};

/**
 * Return short human friendly month representation of date
 * Can also convert date to only time if date is of today (Better UX)
 * @param {String} value date to format
 * @param {Boolean} toTimeForCurrentDay Shall convert to time if day is today/current
 */
export const formatDateToMonthShort = (value, toTimeForCurrentDay = true) => {
  const date = new Date(value)
  let formatting = { month: 'short', day: 'numeric' }
  if (toTimeForCurrentDay && isToday(date))
    formatting = { hour: 'numeric', minute: 'numeric' }
  
  return new Intl.DateTimeFormat('en-US', formatting).format(new Date(value))
}

export const prefixWithPlus = value => value > 0 ? `+${value}` : value

export const formatNumber = (value) => {
  if (value === null || value === undefined) return value;

  const numberString = value.toString().replace(/,/g, '');
  const [integer, decimal] = numberString.split('.');

  const formattedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

  return `${formattedInteger}${decimal ? ',' + decimal : ',00'}`;
}

export const formatNumberInteger = (value) => {
  if (value === null || value === undefined) return value;

  const numberString = value.toString().replace(/,/g, '');
  const [integer] = numberString.split('.');

  const formattedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

  return formattedInteger;
}
