import { defineStore } from 'pinia'
import Notifications from '@/api/notifications'
import Settings from '@/api/settings'
import Configs from '@/api/configs'
import router from '@/router'

const SESSION_ENDED_MESSAGE = 'Din session har avslutats. Logga in igen för att fortsätta.'

// Callback global para notificaciones
let globalNotificationCallback = null
let notificationAudio = null
const NOTIFICATION_SOUND_PATH = '/sounds/notification-2.wav'

// Mismo valor por defecto que el switch "notifyViaSound" de Inställningar > Meddelanden
const DEFAULT_NOTIFY_VIA_SOUND = true
const RECENT_NOTIFICATIONS_LIMIT = 4
const SOUND_DEDUP_WINDOW_MS = 1200
const SOUND_CROSS_TAB_KEY = 'notification_sound_last_played'
const VISIBILITY_REFRESH_THROTTLE_MS = 15000

const playedNotificationSoundAt = new Map()

let windowListenersRegistered = false
let publicChannelSubscribed = false
let recentRequestSeq = 0
let soundPreferenceRequestSeq = 0
let lastVisibilityRefreshAt = 0

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

const normalizeUserId = userId => (
  userId === undefined || userId === null || userId === '' ? null : String(userId)
)

const readStoredUserData = () => {
  const userData = localStorage.getItem('user_data')

  if (!userData)
    return null

  try {
    return JSON.parse(userData)
  } catch (error) {
    return null
  }
}

const isTwoFactorPending = () => {
  try {
    return JSON.parse(localStorage.getItem('is_2fa') || 'null')?.status === true
  } catch (error) {
    return false
  }
}

// Evita que el mismo evento suene dos veces (en esta pestaña o en otra pestaña del mismo usuario).
const canPlayNotificationSoundForId = notificationId => {
  if (notificationId === undefined || notificationId === null)
    return true

  const now = Date.now()
  const lastPlayedAt = playedNotificationSoundAt.get(notificationId) ?? 0

  if (now - lastPlayedAt < SOUND_DEDUP_WINDOW_MS)
    return false

  try {
    const [lastId, lastTime] = String(localStorage.getItem(SOUND_CROSS_TAB_KEY) || '').split(':')

    if (lastId === String(notificationId) && now - Number(lastTime) < SOUND_DEDUP_WINDOW_MS * 5)
      return false

    localStorage.setItem(SOUND_CROSS_TAB_KEY, `${notificationId}:${now}`)
  } catch (error) {
  }

  playedNotificationSoundAt.set(notificationId, now)

  if (playedNotificationSoundAt.size > 500) {
    const oldestEntry = playedNotificationSoundAt.keys().next().value
    if (oldestEntry !== undefined)
      playedNotificationSoundAt.delete(oldestEntry)
  }

  return true
}

const playNotificationSound = () => {
  if (typeof window === 'undefined') return

  try {
    if (!notificationAudio) {
      notificationAudio = new Audio(NOTIFICATION_SOUND_PATH)
      notificationAudio.preload = 'auto'
    }

    notificationAudio.currentTime = 0
    const playPromise = notificationAudio.play()

    if (playPromise && typeof playPromise.catch === 'function') {
      // El navegador puede bloquear el audio sin interacción previa del usuario.
      playPromise.catch(() => {})
    }
  } catch (error) {
  }
}

// Echo.disconnect() (logout forzado / 401) deja pusher desconectado; al volver a
// iniciar sesión hay que reconectarlo para que las suscripciones lleguen al servidor.
const ensureEchoConnected = () => {
  const pusher = window?.Echo?.connector?.pusher

  if (!pusher?.connection)
    return

  if (['disconnected', 'failed', 'unavailable'].includes(pusher.connection.state)) {
    try {
      pusher.connect()
    } catch (error) {
      console.error('❌ Error reconnecting Echo:', error)
    }
  }
}

const mapNotification = (notification, formatTime) => ({
  id: notification?.id,
  title: notification?.title ?? 'Ny avisering',
  subtitle: notification?.subtitle ?? '',
  time: notification?.created_at ? formatTime(notification.created_at) : 'Nyss',
  img: notification?.img,
  text: notification?.text,
  route: notification?.route ?? '/dashboard',
  read: resolveBooleanFlag(notification?.read, false),
  color: notification?.color ?? 'primary',
  icon: notification?.icon ?? 'tabler-bell',
})

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    recentNotifications: [],
    // Usuario cuyo listado/canal/preferencia están cargados en el store.
    currentUserId: null,
    // false hasta conocer la preferencia del usuario actual: nunca suena "por defecto".
    notificationSoundEnabled: false,
    recentLoading: false,
    initialized: false,
    privateChannelSubscribed: false,
    privateChannelSubscribing: false,
    privateChannelUserId: null,
    loading: false,
    last_page: 1,
    notificationsTotalCount: 6
  }),
  getters:{
    getNotifications(){
        return this.notifications
    },
    unreadRecentCount() {
      return this.recentNotifications.filter(notification => !notification.read).length
    },
  },
  actions: {
    setLoading(payload){
      this.loading = payload
    },
    fetchNotifications(params) {
      this.setLoading(true)
      
      return Notifications.get(params)
          .then((response) => {
              this.notifications = response.data.data.notifications.data
              this.last_page = response.data.data.notifications.last_page
              this.notificationsTotalCount = response.data.data.notificationsTotalCount
          })
          .catch(error => console.log(error))
          .finally(() => {
              this.setLoading(false)
          })
      
    },
    deleteNotification(id) {
      this.setLoading(true)

      return Notifications.delete(id)
          .then((response) => {
              let index = this.notifications.findIndex((item) => item.id === id)
              if (index !== -1)
                this.notifications.splice(index, 1)

              this.recentNotifications = this.recentNotifications.filter(item => item.id !== id)

              return Promise.resolve(response)
          })
          .catch(error => Promise.reject(error))
          .finally(() => {
              this.setLoading(false)
          })  
    },
    clearAllNotificationsByUser(userId) {
      this.setLoading(true)

      return Notifications.clearAll({ user_id: userId })
        .then((response) => {
          const hasUserIdInItems = this.notifications.some(item => item?.user_id !== undefined && item?.user_id !== null)

          if (hasUserIdInItems)
            this.notifications = this.notifications.filter(item => item.user_id !== userId)
          else
            this.notifications = []

          // Keep dropdown in sync with bulk-delete action.
          this.recentNotifications = []

          return Promise.resolve(response)
        })
        .catch(error => Promise.reject(error))
        .finally(() => {
          this.setLoading(false)
        })
    },
    getStoredUserId() {
      const user = readStoredUserData()

      return user?.id ?? user?.user?.id ?? null
    },

    getStoredUserData() {
      return readStoredUserData()
    },

    resolveSettingsUserId() {
      const user = this.getStoredUserData()
      const role = user?.roles?.[0]?.name ?? ''

      if (role === 'User')
        return user?.supplier?.boss?.user_id ?? user?.supplier?.boss?.user?.id ?? null

      return user?.id ?? user?.user?.id ?? null
    },

    // Llamado desde Inställningar > Meddelanden al guardar, para aplicar el cambio sin recargar.
    setNotificationSoundEnabled(value) {
      soundPreferenceRequestSeq++
      this.notificationSoundEnabled = resolveBooleanFlag(value, DEFAULT_NOTIFY_VIA_SOUND)
    },

    // Lee notify_via_sound de la misma fuente que usa la página de ajustes:
    // admins -> config global 'notifications'; resto -> settings del proveedor (o de su jefe).
    async loadNotificationSoundPreference() {
      const requestSeq = ++soundPreferenceRequestSeq
      const userId = this.currentUserId

      if (!userId) {
        this.notificationSoundEnabled = false
        return
      }

      let enabled = DEFAULT_NOTIFY_VIA_SOUND

      try {
        const user = this.getStoredUserData()
        const role = user?.roles?.[0]?.name ?? ''
        const isAdminRole = role === 'SuperAdmin' || role === 'Administrator'
        let rawValue

        if (isAdminRole) {
          const response = await Configs.get('notifications')
          const configValue = response?.data?.config?.value
          let config = null

          if (typeof configValue === 'string') {
            try {
              config = JSON.parse(configValue)
            } catch (error) {
              config = null
            }
          } else if (configValue && typeof configValue === 'object') {
            config = configValue
          }

          rawValue = config?.notify_via_sound
        } else {
          const settingsUserId = this.resolveSettingsUserId()

          if (settingsUserId) {
            const settingsResponse = await Settings.get(settingsUserId)
            const settings = settingsResponse?.data?.data?.settings
            const notification = settings?.notification ?? settings?.setting_notification ?? null

            rawValue = notification?.notify_via_sound
          }
        }

        enabled = resolveBooleanFlag(rawValue, DEFAULT_NOTIFY_VIA_SOUND)
      } catch (error) {
        // Sin poder confirmar la preferencia, no se reproduce sonido.
        enabled = false
      }

      if (requestSeq === soundPreferenceRequestSeq && this.currentUserId === userId)
        this.notificationSoundEnabled = enabled
    },

    // Últimas notificaciones no leídas del usuario autenticado (desde la BD).
    async fetchRecent() {
      const userId = this.currentUserId

      if (!userId)
        return

      const requestSeq = ++recentRequestSeq

      this.recentLoading = true

      try {
        const { data } = await Notifications.listRecent()

        // Ignorar respuestas viejas o de otro usuario (cambio de sesión durante la petición).
        if (requestSeq !== recentRequestSeq || this.currentUserId !== userId)
          return

        if (data?.success && Array.isArray(data.data)) {
          this.recentNotifications = data.data
            .map(notification => mapNotification(notification, this.formatTime))
            .slice(0, RECENT_NOTIFICATIONS_LIMIT)
        }
      } catch (err) {
        console.error('Error loading notifications from database:', err)
      } finally {
        if (requestSeq === recentRequestSeq)
          this.recentLoading = false
      }
    },

    registerWindowListeners() {
      if (windowListenersRegistered || typeof window === 'undefined')
        return

      windowListenersRegistered = true

      // Se difiere para que login/logout terminen de escribir user_data y accessToken.
      const scheduleSync = () => setTimeout(() => this.syncWithStoredUser({ refreshList: false }), 0)

      // Misma pestaña (App.vue emite 'user-data-updated' al cambiar user_data).
      window.addEventListener('user-data-updated', scheduleSync)

      // Otras pestañas del mismo navegador (login/logout con otro usuario).
      window.addEventListener('storage', event => {
        if (event.key === null || event.key === 'user_data' || event.key === 'accessToken')
          scheduleSync()
      })

      // Al volver a la pestaña se resincroniza por si se perdieron eventos del websocket.
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState !== 'visible')
          return

        const now = Date.now()

        if (now - lastVisibilityRefreshAt < VISIBILITY_REFRESH_THROTTLE_MS)
          return

        lastVisibilityRefreshAt = now
        this.syncWithStoredUser({ refreshList: true, refreshSound: true })
      })
    },

    subscribeToPublicChannel() {
      if (publicChannelSubscribed || !window?.Echo)
        return

      try {
        window.Echo.channel('notifications-channel')
          .listen('.notifications-channel', data => {
            this.addFromBackend(data.message)
          })
          .error(error => {
            console.error('❌ Error in notification channel:', error)
          })

        publicChannelSubscribed = true
      } catch (err) {
        console.error('❌ Error subscribing to notifications:', err)
      }
    },

    leavePrivateChannel() {
      if (window?.Echo && this.privateChannelUserId) {
        try {
          window.Echo.leave(`notifications.${this.privateChannelUserId}`)
        } catch (error) {
        }
      }

      this.privateChannelSubscribed = false
      this.privateChannelSubscribing = false
      this.privateChannelUserId = null
    },

    // Limpia todo lo que pertenece al usuario anterior.
    resetUserState(userId = null) {
      recentRequestSeq++
      soundPreferenceRequestSeq++

      this.leavePrivateChannel()
      this.currentUserId = normalizeUserId(userId)
      this.recentNotifications = []
      this.recentLoading = false
      this.notificationSoundEnabled = false
    },

    /**
     * Alinea el store con el usuario guardado en localStorage (canal privado, listado y sonido).
     */
    async syncWithStoredUser({ userId = null, refreshList = true, refreshSound = false } = {}) {
      const resolvedUserId = normalizeUserId(userId ?? this.getStoredUserId())
      const hasToken = !!localStorage.getItem('accessToken')

      if (!resolvedUserId || !hasToken || isTwoFactorPending()) {
        if (this.currentUserId || this.privateChannelUserId || this.recentNotifications.length)
          this.resetUserState(null)

        return
      }

      ensureEchoConnected()
      this.subscribeToPublicChannel()

      if (this.currentUserId !== resolvedUserId) {
        this.resetUserState(resolvedUserId)
        this.subscribeToPrivateChannel(resolvedUserId)

        await Promise.all([
          this.loadNotificationSoundPreference(),
          this.fetchRecent(),
        ])

        return
      }

      this.subscribeToPrivateChannel(resolvedUserId)

      await Promise.all([
        refreshSound ? this.loadNotificationSoundPreference() : null,
        refreshList ? this.fetchRecent() : null,
      ])
    },

    async init(userId = null) {
      this.initialized = true

      if (!window?.Echo)
        console.warn('❌ Echo not available. Please check initialization in main.js')

      this.registerWindowListeners()

      await this.syncWithStoredUser({ userId, refreshList: true })
    },

    formatTime(dateString) {
      const date = new Date(dateString)
      const now = new Date()
      const diffInSeconds = Math.floor((now - date) / 1000)
      
      if (diffInSeconds < 60) return 'Nyss'
      if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} min sedan`
      if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} tim sedan`
      if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} dag sedan`
      
      return date.toLocaleDateString('sv-SE')
    },

    /**
     * Suscribirse al canal privado de notificaciones del usuario
     * @param {number|string} userId - ID del usuario
     */
    subscribeToPrivateChannel(userId) {
      const channelUserId = normalizeUserId(userId)

      if (!channelUserId || !window.Echo) {
        return
      }

      if (
        this.privateChannelUserId === channelUserId
        && (this.privateChannelSubscribed || this.privateChannelSubscribing)
      ) {
        return
      }

      try {
        if (this.privateChannelUserId && this.privateChannelUserId !== channelUserId)
          this.leavePrivateChannel()

        const token = localStorage.getItem('accessToken')

        if (!token) {
          return
        }

        // Mark the channel as "in-flight" before attaching listeners to avoid duplicate binds.
        this.privateChannelSubscribed = false
        this.privateChannelSubscribing = true
        this.privateChannelUserId = channelUserId

        window.syncEchoAuthorization?.()
        ensureEchoConnected()

        window.Echo.private(`notifications.${channelUserId}`)
          .listen('.user-notification', data => {
            // Descarta eventos que lleguen tras un cambio de usuario.
            if (this.currentUserId !== channelUserId)
              return

            this.addFromBackend(data.message)
          })
          .listen('.force-logout', data => {
            if (this.currentUserId !== channelUserId)
              return

            this.forceLogout(data?.message)
          })
          .error(error => {
            if (this.privateChannelUserId !== channelUserId)
              return

            this.privateChannelSubscribed = false
            this.privateChannelSubscribing = false
            this.privateChannelUserId = null

            const errorMessage = String(
              error?.error?.data?.message
              || error?.message
              || ''
            ).toLowerCase()

            if (
              errorMessage.includes('user is not logged in')
              || errorMessage.includes('unauthenticated')
              || errorMessage.includes('invalid token')
              || errorMessage.includes('ogiltig token')
            ) {
              this.forceLogout(SESSION_ENDED_MESSAGE)
              return
            }

            console.error('❌ Error in private notification channel:', error)
          })
          .subscribed(() => {
            if (this.privateChannelUserId !== channelUserId)
              return

            this.privateChannelSubscribed = true
            this.privateChannelSubscribing = false
          })
      } catch (err) {
        this.privateChannelSubscribed = false
        this.privateChannelSubscribing = false
        if (this.privateChannelUserId === channelUserId)
          this.privateChannelUserId = null

        console.error('❌ Error subscribing to private notifications:', err)
      }
    },

    forceLogout(message = 'Ditt konto har inaktiverats.') {
      this.resetUserState(null)

      localStorage.removeItem('user_data')
      localStorage.removeItem('userAbilities')
      localStorage.removeItem('accessToken')

      if (window?.Echo) {
        try {
          window.Echo.disconnect()
        } catch (error) {
        }
      }

      if (router.currentRoute.value?.name === 'login')
        return

      const loginRoute = { name: 'login' }
      const query = { reason: 'force_logout', message }

      router.push({ ...loginRoute, query })
    },

    addFromBackend(data) {
      // Sin sesión activa no se muestra ni suena nada.
      if (!this.currentUserId)
        return

      const mapped = {
        ...mapNotification(data, this.formatTime),
        time: 'Nyss',
        route: data?.route ?? '/dashboard',
      }

      const isDuplicate = mapped.id !== undefined
        && mapped.id !== null
        && this.recentNotifications.some(notification => notification.id === mapped.id)

      if (isDuplicate)
        return

      // Solo si "notifyViaSound" está activado en la configuración.
      if (this.notificationSoundEnabled === true && canPlayNotificationSoundForId(mapped.id))
        playNotificationSound()

      this.recentNotifications = [mapped, ...this.recentNotifications].slice(0, RECENT_NOTIFICATIONS_LIMIT)
      
      // Llamar al callback global si existe
      if (globalNotificationCallback) {
        globalNotificationCallback(mapped)
      }
    },

    // Método para registrar el callback de notificaciones
    onNotificationReceived(callback) {
      globalNotificationCallback = callback
    },

    // Método para limpiar el callback
    offNotificationReceived() {
      globalNotificationCallback = null
    },

    async markAsRead(notificationId) {
      try {
        await Notifications.markAsRead(notificationId)
        
        // Eliminar del dropdown y completar con la siguiente más reciente
        this.recentNotifications = this.recentNotifications.filter(n => n.id !== notificationId)
        this.fetchRecent()
      } catch (error) {
        console.error('Error marking notification as read:', error)
        throw error
      }
    },

    async send(payload) {
      try {
        const response = await Notifications.send(payload)
        return response
      } catch (error) {
        console.error('Error sending notification:', error)
        throw error
      }
    },

    async markAllRead() {
      try {
        await Notifications.markAllAsRead()
        
        // Limpiar el dropdown
        recentRequestSeq++
        this.recentNotifications = []
      } catch (error) {
        console.error('Error marking all notifications as read:', error)
      }
    },
  },
})
