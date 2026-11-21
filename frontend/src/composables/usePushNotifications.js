import { Capacitor } from '@capacitor/core'
import { FirebaseMessaging, Importance } from '@capacitor-firebase/messaging'
import { Preferences } from '@capacitor/preferences'
import { readonly, ref } from 'vue'
import axios from '@axios'
import router from '@/router'
import { isUserLoggedIn } from '@/router/utils'
import { useNotificationsStore } from '@/stores/useNotifications'

const deviceIdKey = 'device_id'
const pendingRouteKey = 'push_pending_route'
const requestConfig = { skipAuthRedirect: true }
const listeners = new Map()
let operations = Promise.resolve()
let deviceId
let currentToken
let registrationKey
let sessionActive = true
const notificationsEnabled = ref()
let preferenceSession

const bestEffort = async operation => {
  try {
    return await operation()
  } catch (error) {
    // Push failures must not interrupt login or navigation.
    console.warn('[usePushNotifications]', error?.message ?? 'Operation failed')
  }
}

// Share lifecycle state across components and serialize concurrent init/cleanup.
const runNative = operation => {
  if (!Capacitor.isNativePlatform())
    return Promise.resolve()

  const result = operations.then(() => bestEffort(operation))

  operations = result

  return result
}

const readDeviceId = async () => {
  if (deviceId)
    return deviceId

  const { value } = await Preferences.get({ key: deviceIdKey })
  const id = value || crypto.randomUUID()

  if (!value)
    await Preferences.set({ key: deviceIdKey, value: id })

  deviceId = id

  return id
}

const hasSession = () => isUserLoggedIn()
  && !JSON.parse(localStorage.getItem('is_2fa') || 'null')?.status

const registerToken = async (token, force = false) => {
  currentToken = token
  if (!hasSession())
    return

  const accessToken = localStorage.getItem('accessToken')
  const key = `${accessToken}:${token}`

  if (!force && registrationKey === key)
    return

  const id = await readDeviceId()
  if (localStorage.getItem('accessToken') !== accessToken)
    return

  if (preferenceSession !== accessToken) {
    preferenceSession = accessToken
    notificationsEnabled.value = undefined
    registrationKey = undefined
  }

  const { data } = await axios.post('push-tokens', {
    token,
    platform: Capacitor.getPlatform(),
    device_id: id,
  }, requestConfig)
  if (localStorage.getItem('accessToken') !== accessToken)
    return

  // POST reactivates sessions; preserve explicitly disabled state on rotation.
  if (!sessionActive)
    await axios.patch('push-tokens/session', { device_id: id }, requestConfig)
  const savedPreference = data?.data?.notifications_enabled
  if (registrationKey && registrationKey !== key && notificationsEnabled.value !== undefined) {
    await axios.patch('push-tokens/preferences', {
      device_id: id,
      notifications_enabled: notificationsEnabled.value,
    }, requestConfig)
  } else if (typeof savedPreference === 'boolean') {
    notificationsEnabled.value = savedPreference
  }

  registrationKey = key
}

const receiveNotification = async ({ notification }) => {
  const accessToken = localStorage.getItem('accessToken')
  if (!sessionActive || (preferenceSession === accessToken && notificationsEnabled.value === false) || !hasSession())
    return

  const data = notification?.data
  const rawId = data?.notification_id

  if (rawId === undefined || rawId === null || rawId === '')
    return

  const store = useNotificationsStore()

  await store.syncWithStoredUser({ refreshList: false })

  if (!hasSession() || localStorage.getItem('accessToken') !== accessToken)
    return

  const numericId = Number(rawId)

  store.addFromBackend({
    id: Number.isSafeInteger(numericId) ? numericId : String(rawId),
    title: notification.title,
    subtitle: notification.subtitle,
    text: notification.body,
    route: data.route,
    icon: data.type,
    created_at: new Date().toISOString(),
  })
}

const openNotification = async ({ notification }) => {
  const route = notification?.data?.route

  if (!route)
    return
  if (typeof route !== 'string' || !route.startsWith('/') || route.startsWith('//')) {
    console.warn('[usePushNotifications] Invalid notification route')

    return
  }

  if (hasSession()) {
    await router.push(route)
  } else {
    // Persist before redirecting; the route survives native app restarts.
    await bestEffort(() => Preferences.set({ key: pendingRouteKey, value: route }))
    await router.push({ path: '/login', query: { to: route } })
  }
}

const addListener = async (name, callback) => {
  if (listeners.has(name)) {
    listeners.get(name).active = true

    return
  }

  const entry = { active: true }

  entry.handle = await FirebaseMessaging.addListener(name, event => {
    void runNative(() => {
      if (entry.active && listeners.get(name) === entry)
        return callback(event)
    })
  })
  listeners.set(name, entry)
}

const detachListeners = async () => {
  for (const [name, entry] of listeners) {
    entry.active = false
    await bestEffort(async () => {
      await entry.handle.remove()
      listeners.delete(name)
    })
  }
}

const init = () => runNative(async () => {
  if (Capacitor.getPlatform() === 'android') {
    await bestEffort(() => FirebaseMessaging.createChannel({
      id: 'default',
      name: 'default',
      importance: Importance.High,
    }))
  }

  let permission = await FirebaseMessaging.checkPermissions()

  if (permission.receive === 'prompt' || permission.receive === 'prompt-with-rationale')
    permission = await FirebaseMessaging.requestPermissions()
  if (permission.receive !== 'granted')
    return

  await addListener('tokenReceived', ({ token }) => registerToken(token))
  await addListener('notificationReceived', receiveNotification)
  await addListener('notificationActionPerformed', openNotification)

  const { token } = await FirebaseMessaging.getToken()

  await registerToken(token)
})

const setSessionActive = active => runNative(async () => {
  sessionActive = Boolean(active)
  registrationKey = undefined
  if (!hasSession())
    return

  if (sessionActive) {
    // The existing PATCH only deactivates; POST is the reactivation API.
    const token = currentToken || (await FirebaseMessaging.getToken()).token

    await registerToken(token, true)
  } else {
    notificationsEnabled.value = undefined
    preferenceSession = undefined
    await axios.patch('push-tokens/session', { device_id: await readDeviceId() }, requestConfig)
  }
})

const setNotificationsEnabled = enabled => runNative(async () => {
  if (!hasSession())
    return

  const accessToken = localStorage.getItem('accessToken')
  const id = await readDeviceId()
  if (localStorage.getItem('accessToken') !== accessToken)
    return

  await axios.patch('push-tokens/preferences', {
    device_id: id,
    notifications_enabled: Boolean(enabled),
  }, requestConfig)

  if (localStorage.getItem('accessToken') === accessToken) {
    preferenceSession = accessToken
    notificationsEnabled.value = Boolean(enabled)

    return true
  }
})

const unregister = () => runNative(async () => {
  await detachListeners()
  registrationKey = undefined
  currentToken = undefined
  notificationsEnabled.value = undefined
  preferenceSession = undefined
  await bestEffort(async () => {
    if (hasSession())
      await axios.delete('push-tokens', { ...requestConfig, data: { device_id: await readDeviceId() } })
  })
  await bestEffort(() => FirebaseMessaging.deleteToken())
})

export const usePushNotifications = () => ({
  init,
  setSessionActive,
  notificationsEnabled: readonly(notificationsEnabled),
  setNotificationsEnabled,
  unregister,
  getDeviceId: () => runNative(readDeviceId),
  getPendingRoute: () => runNative(async () => {
    const { value } = await Preferences.get({ key: pendingRouteKey })

    return value
  }),
  clearPendingRoute: () => runNative(() => Preferences.remove({ key: pendingRouteKey })),
  removeListeners: () => runNative(detachListeners),
})
