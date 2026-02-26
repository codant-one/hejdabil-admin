import { defineStore } from 'pinia'
import Notifications from '@/api/notifications'
import router from '@/router'

// Callback global para notificaciones
let globalNotificationCallback = null

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    initialized: false,
    privateChannelSubscribed: false,
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
          this.notifications = this.notifications.filter(item => item.user_id !== userId)
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

        return user?.id ?? null
      } catch (error) {
        return null
      }
    },

    async init(userId = null) {
      const resolvedUserId = userId ?? this.getStoredUserId()

      if (this.initialized) {
        if (
          resolvedUserId
          && (
            !this.privateChannelSubscribed
            || this.privateChannelUserId !== resolvedUserId
          )
        )
          this.subscribeToPrivateChannel(resolvedUserId)

        return
      }
      this.initialized = true

      // Cargar notificaciones guardadas desde la base de datos
      try {
        const { data } = await Notifications.listRecent()
        if (data.success && Array.isArray(data.data)) {
          this.notifications = data.data.map(notification => ({
            id: notification.id,
            title: notification.title,
            subtitle: notification.subtitle,
            time: this.formatTime(notification.created_at),
            text: notification.text,
            route: notification.route,
            read: notification.read === 1,
            color: notification.color ?? 'primary',
            icon: notification.icon ?? 'tabler-bell',
          }))
        }
      } catch (err) {
        console.error('Error loading notifications from database:', err)
      }

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

      if (this.privateChannelSubscribed && this.privateChannelUserId === userId) {
        return
      }

      try {
        if (this.privateChannelUserId) {
          window.Echo.leaveChannel(`private-notifications.${this.privateChannelUserId}`)
          this.privateChannelSubscribed = false
        }

        // Actualizar el token de autorización en Echo antes de suscribirse
        const token = localStorage.getItem('accessToken')
        
        if (token && window.Echo.connector.pusher.config.auth) {
          window.Echo.connector.pusher.config.auth.headers.Authorization = `Bearer ${token}`
        }

        window.Echo.private(`notifications.${userId}`)
          .listen('.user-notification', data => {
            this.addFromBackend(data.message)
          })
          .listen('.force-logout', data => {
            this.forceLogout(data?.message)
          })
          .error(error => {
            console.error('❌ Error in private notification channel:', error)
          })
          .subscribed(() => {
            //console.log('✅ Successfully subscribed to private channel:', `notifications.${userId}`)
          })

        this.privateChannelSubscribed = true
        this.privateChannelUserId = userId
      } catch (err) {
        console.error('❌ Error subscribing to private notifications:', err)
      }
    },

    forceLogout(message = 'Ditt konto har inaktiverats.') {
      if (window?.Echo && this.privateChannelUserId) {
        try {
          window.Echo.leaveChannel(`private-notifications.${this.privateChannelUserId}`)
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
      this.privateChannelUserId = null
      this.initialized = false

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
      
      this.notifications.unshift(mapped)
      
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
        
        // Actualizar en el store local
        const notification = this.notifications.find(n => n.id === notificationId)
        if (notification) {
          notification.read = true
        }
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
        
        // Actualizar todas como leídas en el store local
        this.notifications.forEach(n => {
          n.read = true
        })
      } catch (error) {
        console.error('Error marking all notifications as read:', error)
      }
    },
  },
})


