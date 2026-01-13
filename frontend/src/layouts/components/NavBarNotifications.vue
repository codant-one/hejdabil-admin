<script setup>

import { useRouter } from 'vue-router'
import { onMounted } from 'vue'
import { useNotificationsStore } from '@/stores/useNotifications'
import Notifications from '@core/components/Notifications.vue'

const notificationsStore = useNotificationsStore()
const router = useRouter()

onMounted(async () => {
  // Obtener el usuario actual y su ID
  let userId = null
  
  // Intentar obtener el userId del localStorage si está almacenado
  const userData = localStorage.getItem('user_data')

  if (userData) {
    try {
      const user = JSON.parse(userData)
      userId = user.id
    } catch (e) {
      console.error('❌ Error parsing user data:', e)
    }
  } else {
    console.warn('⚠️ No user_data found in localStorage')
  }

  await notificationsStore.init(userId)
})

const onReadAll = async () => {
  await notificationsStore.markAllRead()
}

const onNotificationClick = async (notification) => {
  // Marcar como leída si tiene ID
  if (notification.id && !notification.read) {
    await notificationsStore.markAsRead(notification.id)
  }
  
  // Navegar a la ruta si existe
  if (notification.route) {
    router.push(notification.route)
  }
}
</script>

<template>
  <Notifications 
    :notifications="notificationsStore.notifications" 
    @click:readAllNotifications="onReadAll" 
    @click:notification="onNotificationClick"
  />  
</template>
