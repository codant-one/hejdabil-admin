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
const DEFAULT_NOTIFY_VIA_SOUND = true
const SOUND_DEDUP_WINDOW_MS = 1200
const playedNotificationSoundAt = new Map()

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

const canPlayNotificationSoundForId = notificationId => {
  if (notificationId === undefined || notificationId === null)
    return true

  const now = Date.now()
  const lastPlayedAt = playedNotificationSoundAt.get(notificationId) ?? 0

  if (now - lastPlayedAt < SOUND_DEDUP_WINDOW_MS)
    return false

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
      playPromise.catch(() => {})
    }
  } catch (error) {
  }
}

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    recentNotifications: [],
    notificationSoundEnabled: DEFAULT_NOTIFY_VIA_SOUND,
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
              this.notifications.splice(index, 1)
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
      const userData = localStorage.getItem('user_data')

      if (!userData)
        return null

      try {
        const user = JSON.parse(userData)

        return user?.id ?? user?.user?.id ?? null
      } catch (error) {
        return null
      }
    },

    getStoredUserData() {
      const userData = localStorage.getItem('user_data')

      if (!userData)
        return null

      try {
        return JSON.parse(userData)
      } catch (error) {
        return null
      }
    },

    resolveSettingsUserId() {
      const user = this.getStoredUserData()
      const role = user?.roles?.[0]?.name ?? ''

      if (role === 'User')
        return user?.supplier?.boss?.user_id ?? user?.supplier?.boss?.user?.id ?? null

      return user?.id ?? user?.user?.id ?? null
    },

    async loadNotificationSoundPreference() {
      this.notificationSoundEnabled = DEFAULT_NOTIFY_VIA_SOUND

      try {
        const user = this.getStoredUserData()
        const role = user?.roles?.[0]?.name ?? ''
        const isAdminRole = role === 'SuperAdmin' || role === 'Administrator'

        const settingsUserId = this.resolveSettingsUserId()

        if (settingsUserId) {
          const settingsResponse = await Settings.get(settingsUserId)
          const settings = settingsResponse?.data?.data?.settings
          const notification = settings?.notification ?? settings?.setting_notification ?? null

          if (notification?.notify_via_sound !== undefined && notification?.notify_via_sound !== null) {
            this.notificationSoundEnabled = resolveBooleanFlag(notification.notify_via_sound, DEFAULT_NOTIFY_VIA_SOUND)
            return
          }
        }

        if (isAdminRole) {
          const response = await Configs.get('notifications')
          const rawValue = response?.data?.config?.value
          let config = null

          if (typeof rawValue === 'string') {
            try {
              config = JSON.parse(rawValue)
            } catch (error) {
              config = null
            }
          } else if (rawValue && typeof rawValue === 'object') {
            config = rawValue
          }

          if (config?.notify_via_sound !== undefined && config?.notify_via_sound !== null) {
            this.notificationSoundEnabled = resolveBooleanFlag(config.notify_via_sound, DEFAULT_NOTIFY_VIA_SOUND)
          }

          return
        }
      } catch (error) {
        this.notificationSoundEnabled = DEFAULT_NOTIFY_VIA_SOUND
      }
    },

    async init(userId = null) {
      const resolvedUserId = userId ?? this.getStoredUserId()

      if (this.initialized) {
        await this.loadNotificationSoundPreference()

        if (
          resolvedUserId
          && (
            (!this.privateChannelSubscribed && !this.privateChannelSubscribing)
            || this.privateChannelUserId !== resolvedUserId
          )
        )
          this.subscribeToPrivateChannel(resolvedUserId)

        return
      }
      this.initialized = true

      // Suscribirse a eventos en tiempo real
      try {
        
        if (window && window.Echo) {
          
          // Canal público (notificaciones generales)
          window.Echo.channel('notifications-channel')
            .listen('.notifications-channel', data => {
              this.addFromBackend(data.message)
            })
            .error(error => {
              console.error('❌ Error in notification channel:', error)
            })
          
          // Canal privado (notificaciones específicas del usuario)
          if (resolvedUserId) {
            this.subscribeToPrivateChannel(resolvedUserId)
          } else {
            //console.warn('⚠️ No userId provided, skipping private channel subscription')
          }
          
        } else {
          console.warn('❌ Echo not available. Please check initialization in notifications.js')
        }
      } catch (err) {
        console.error('❌ Error subscribing to notifications:', err)
      }

      await this.loadNotificationSoundPreference()

      // Cargar notificaciones guardadas desde la base de datos
      try {
        const { data } = await Notifications.listRecent()
        if (data.success && Array.isArray(data.data)) {
          this.recentNotifications = data.data.map(notification => ({
            id: notification.id,
            title: notification.title,
            subtitle: notification.subtitle,
            time: this.formatTime(notification.created_at),
            text: notification.text,
            route: notification.route,
            read: false,
            color: notification.color ?? 'primary',
            icon: notification.icon ?? 'tabler-bell',
          }))
        }
      } catch (err) {
        console.error('Error loading notifications from database:', err)
      }
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
     * @param {number} userId - ID del usuario
     */
    subscribeToPrivateChannel(userId) {
      if (!userId || !window.Echo) {
        return
      }

      if (
        this.privateChannelUserId === userId
        && (this.privateChannelSubscribed || this.privateChannelSubscribing)
      ) {
        return
      }

      try {
        if (this.privateChannelUserId && this.privateChannelUserId !== userId) {
          window.Echo.leave(`notifications.${this.privateChannelUserId}`)
          this.privateChannelSubscribed = false
          this.privateChannelSubscribing = false
          this.privateChannelUserId = null
        }

        const token = localStorage.getItem('accessToken')

        if (!token) {
          return
        }

        // Mark the channel as "in-flight" before attaching listeners to avoid duplicate binds.
        this.privateChannelSubscribed = false
        this.privateChannelSubscribing = true
        this.privateChannelUserId = userId

        window.syncEchoAuthorization?.()

        window.Echo.private(`notifications.${userId}`)
          .listen('.user-notification', data => {
            this.addFromBackend(data.message)
          })
          .listen('.force-logout', data => {
            this.forceLogout(data?.message)
          })
          .error(error => {
            this.privateChannelSubscribed = false
            this.privateChannelSubscribing = false
            if (this.privateChannelUserId === userId)
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
            this.privateChannelSubscribed = true
            this.privateChannelSubscribing = false
            this.privateChannelUserId = userId
          })
      } catch (err) {
        this.privateChannelSubscribed = false
        this.privateChannelSubscribing = false
        if (this.privateChannelUserId === userId)
          this.privateChannelUserId = null

        console.error('❌ Error subscribing to private notifications:', err)
      }
    },

    forceLogout(message = 'Ditt konto har inaktiverats.') {
      if (window?.Echo && this.privateChannelUserId) {
        try {
          window.Echo.leave(`notifications.${this.privateChannelUserId}`)
        } catch (error) {
        }
      }

      localStorage.removeItem('user_data')
      localStorage.removeItem('userAbilities')
      localStorage.removeItem('accessToken')

      if (window?.Echo) {
        try {
          window.Echo.disconnect()
        } catch (error) {
        }
      }

      this.privateChannelSubscribed = false
      this.privateChannelSubscribing = false
      this.privateChannelUserId = null
      this.initialized = false

      if (router.currentRoute.value?.name === 'login')
        return

      const loginRoute = { name: 'login' }
      const query = { reason: 'force_logout', message }

      router.push({ ...loginRoute, query })
    },

    addFromBackend(data) {
  
      const mapped = {
        id: data?.id,
        title: data?.title ?? 'Ny avisering',
        subtitle: data?.subtitle ?? '',
        time: data?.time ?? 'Nyss',
        img: data?.img,
        color: data?.color,
        icon: data?.icon,
        text: data?.text,
        route: data?.route ?? '/dashboard',
        read: data?.read ?? false,
      }

      if (this.notificationSoundEnabled && canPlayNotificationSoundForId(mapped.id))
        playNotificationSound()

      if (
        mapped.id !== undefined
        && mapped.id !== null
        && this.recentNotifications.some(notification => notification.id === mapped.id)
      ) {
        return
      }
      
      this.recentNotifications.unshift(mapped)

      // Mantener solo las últimas 4 no leídas
      if (this.recentNotifications.length > 4) {
        this.recentNotifications = this.recentNotifications.slice(0, 4)
      }
      
      // Llamar al callback global si existe
      if (globalNotificationCallback) {
        globalNotificationCallback(mapped)
      } else {
        //console.warn('⚠️ No global callback registered')
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
        
        // Eliminar del dropdown
        this.recentNotifications = this.recentNotifications.filter(n => n.id !== notificationId)
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
        this.recentNotifications = []
      } catch (error) {
        console.error('Error marking all notifications as read:', error)
      }
    },
  },
})


