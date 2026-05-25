import Settings from '@/api/settings'
import Configs from '@/api/configs'

const DEFAULT_SEND_NOTIFICATIONS_ACTION_ENABLED = false
const SEND_NOTIFICATIONS_SOURCES = {
  billings: {
    configKey: 'billings',
    settingsKeys: ['billing', 'setting_billing'],
  },
  agreements: {
    configKey: 'agreements',
    settingsKeys: ['agreement', 'setting_agreement'],
  },
  documents: {
    configKey: 'documents',
    settingsKeys: ['document', 'setting_document'],
  },
}

const resolveBooleanFlag = (value, fallback = false) => {
  if (value === undefined || value === null)
    return fallback

  if (typeof value === 'boolean')
    return value

  if (typeof value === 'number')
    return value === 1

  if (typeof value === 'string') {
    const normalizedValue = value.trim().toLowerCase()

    if (['1', 'true', 'yes', 'on'].includes(normalizedValue))
      return true

    if (['0', 'false', 'no', 'off'].includes(normalizedValue))
      return false
  }

  return fallback
}

const parseConfigValue = rawValue => {
  if (typeof rawValue === 'string') {
    try {
      const parsed = JSON.parse(rawValue)

      return typeof parsed === 'string' ? JSON.parse(parsed) : parsed
    } catch (error) {
      return null
    }
  }

  return rawValue && typeof rawValue === 'object' ? rawValue : null
}

const resolveFeatureSettings = (settings, featureKey) => {
  const source = SEND_NOTIFICATIONS_SOURCES[featureKey]

  if (!source)
    return null

  return source.settingsKeys
    .map(key => settings?.[key] ?? null)
    .find(value => value && typeof value === 'object') ?? null
}

export const resolveSettingsVisibilityUserId = user => {
  const role = user?.roles?.[0]?.name ?? ''

  if (role === 'User')
    return user?.supplier?.boss?.user_id ?? user?.supplier?.boss?.user?.id ?? null

  return user?.id ?? user?.user?.id ?? null
}

export const resolveBillingSettingsUserId = resolveSettingsVisibilityUserId

export const getSendNotificationsVisibilityCacheKey = (user, featureKey) => {
  const role = user?.roles?.[0]?.name ?? ''
  const settingsUserId = resolveSettingsVisibilityUserId(user)

  return `${featureKey}:${role}:${settingsUserId ?? 'none'}`
}

export const loadSendNotificationsActionPreference = async (user, featureKey, fallback = DEFAULT_SEND_NOTIFICATIONS_ACTION_ENABLED) => {
  const source = SEND_NOTIFICATIONS_SOURCES[featureKey]

  if (!source)
    return fallback

  const role = user?.roles?.[0]?.name ?? ''
  const isAdminRole = role === 'SuperAdmin' || role === 'Administrator'
  const settingsUserId = resolveSettingsVisibilityUserId(user)

  if (settingsUserId) {
    try {
      const settingsResponse = await Settings.get(settingsUserId)
      const settings = settingsResponse?.data?.data?.settings
      const featureSettings = resolveFeatureSettings(settings, featureKey)

      if (featureSettings?.send_notifications !== undefined && featureSettings?.send_notifications !== null)
        return resolveBooleanFlag(featureSettings.send_notifications, fallback)
    } catch (error) {
    }
  }

  if (isAdminRole) {
    try {
      const response = await Configs.get(source.configKey)
      const config = parseConfigValue(response?.data?.config?.value)

      if (config?.send_notifications !== undefined && config?.send_notifications !== null)
        return resolveBooleanFlag(config.send_notifications, fallback)
    } catch (error) {
    }
  }

  return fallback
}

export const getBillingSmsVisibilityCacheKey = user => getSendNotificationsVisibilityCacheKey(user, 'billings')
export const loadBillingSmsActionPreference = user => loadSendNotificationsActionPreference(user, 'billings')
export const getAgreementSmsVisibilityCacheKey = user => getSendNotificationsVisibilityCacheKey(user, 'agreements')
export const loadAgreementSmsActionPreference = user => loadSendNotificationsActionPreference(user, 'agreements')
export const getDocumentSmsVisibilityCacheKey = user => getSendNotificationsVisibilityCacheKey(user, 'documents')
export const loadDocumentSmsActionPreference = user => loadSendNotificationsActionPreference(user, 'documents')