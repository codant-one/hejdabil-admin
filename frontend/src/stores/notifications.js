import { defineStore } from 'pinia'
import notificationsApi from '@/api/notifications'

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

      // Cargar notificaciones iniciales desde el backend si aplica
      try {
        if (window && window.axios) {
          // Descomenta y ajusta el endpoint si tienes API para iniciales
          // const { data } = await window.axios.get('/api/notifications')
          // this.notifications = Array.isArray(data) ? data : []
        }
      } catch (err) {
        // eslint-disable-next-line no-console
        console.error('Error loading initial notifications:', err)
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
            console.warn('⚠️ No userId provided, skipping private channel subscription')
          }
          
        } else {
          console.warn('❌ Echo not available. Please check initialization in notifications.js')
        }
      } catch (err) {
        console.error('❌ Error subscribing to notifications:', err)
      }
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
      //console.log('➕ Adding notification to store:', data)
      
      const mapped = {
        title: data?.title ?? 'Ny avisering',
        subtitle: data?.subtitle ?? '',
        time: data?.time ?? 'Ahora',
        img: data?.img,
        color: data?.color,
        icon: data?.icon,
        text: data?.text,
      }
      this.notifications.unshift(mapped)
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

    markAllRead() {
      // Ajusta según política de lectura (vaciar o marcar como leídas)
      this.notifications = []
    },
  },
})


