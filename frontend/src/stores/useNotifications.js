import { defineStore } from 'pinia'
import notificationsApi from '@/api/notifications'

// Callback global para notificaciones
let globalNotificationCallback = null

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    initialized: false,
    privateChannelSubscribed: false,
  }),
  actions: {
    async init(userId = null) {
      
      if (this.initialized)
        return
      this.initialized = true

      // Cargar notificaciones guardadas desde la base de datos
      try {
        const { data } = await notificationsApi.getAll()
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
          if (userId) {
            this.subscribeToPrivateChannel(userId)
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
      if (this.privateChannelSubscribed || !window.Echo) {
        return
      }

      try {
        // Actualizar el token de autorización en Echo antes de suscribirse
        const token = localStorage.getItem('accessToken')
        
        if (token && window.Echo.connector.pusher.config.auth) {
          window.Echo.connector.pusher.config.auth.headers.Authorization = `Bearer ${token}`
        }

        window.Echo.private(`notifications.${userId}`)
          .listen('.user-notification', data => {
            this.addFromBackend(data.message)
          })
          .error(error => {
            console.error('❌ Error in private notification channel:', error)
          })
          .subscribed(() => {
            //console.log('✅ Successfully subscribed to private channel:', `notifications.${userId}`)
          })

        this.privateChannelSubscribed = true
      } catch (err) {
        console.error('❌ Error subscribing to private notifications:', err)
      }
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
        await notificationsApi.markAsRead(notificationId)
        
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
        const response = await notificationsApi.send(payload)
        return response
      } catch (error) {
        console.error('Error sending notification:', error)
        throw error
      }
    },

    async markAllRead() {
      try {
        await notificationsApi.markAllAsRead()
        
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


