<script setup>

import { onMounted } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'
import Notifications from '@core/components/Notifications.vue'

const notificationsStore = useNotificationsStore()

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

const onReadAll = () => {
  notificationsStore.markAllRead()
}
</script>

<template>
  <Notifications 
    :notifications="notificationsStore.notifications" 
    @click:readAllNotifications="onReadAll" 
  />  
</template>
