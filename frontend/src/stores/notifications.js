import { defineStore } from 'pinia'

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    initialized: false,
  }),
  actions: {
    async init() {
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
          window.Echo.channel('notifications-channel')
            .listen('.notifications-channel', data => {
              //console.log('Received notification:', data)
              this.addFromBackend(data.message)
            })
            .error(error => {
              // eslint-disable-next-line no-console
              console.error('Error in notification channel:', error)
            })
          // eslint-disable-next-line no-console
          //console.log('Subscribed to notification channel: notifications-channel')
        } else {
          // eslint-disable-next-line no-console
          console.warn('Echo not available. Please check initialization in notifications.js')
        }
      } catch (err) {
        // eslint-disable-next-line no-console
        console.error('Error subscribing to notifications:', err)
      }
    },

    addFromBackend(data) {
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

    markAllRead() {
      // Ajusta según política de lectura (vaciar o marcar como leídas)
      this.notifications = []
    },
  },
})


