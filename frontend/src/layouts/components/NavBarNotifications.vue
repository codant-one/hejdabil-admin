<script setup>

import { useRouter } from 'vue-router'
import { onMounted } from 'vue'
import { useNotificationsStore } from '@/stores/useNotifications'
import Notifications from '@core/components/Notifications.vue'

const notificationsStore = useNotificationsStore()
const router = useRouter()

const emitDashboardRemindersScroll = () => {
  if (typeof window === 'undefined')
    return

  window.dispatchEvent(new CustomEvent('dashboard-scroll-to-reminders'))
}

onMounted(async () => {
  // init() resuelve el usuario desde user_data y resincroniza canal, listado y sonido
  // si el usuario cambió (logout/login en esta u otra pestaña).
  await notificationsStore.init()
})

const onMenuOpen = () => {
  notificationsStore.fetchRecent()
}

const onReadAll = async () => {
  router.push({ name : 'dashboard-notifications' })
}

const onNotificationClick = async (notification) => {
  // Marcar como leída si tiene ID
  if (notification.id && !notification.read) {
    await notificationsStore.markAsRead(notification.id)
  }
  
  // Navegar a la ruta si existe
  if (notification.route) {
    const targetRoute = router.resolve(notification.route)
    const isRemindersTarget = targetRoute.hash === '#reminders'
    const isAlreadyOnTarget = router.currentRoute.value.fullPath === targetRoute.fullPath

    if (isRemindersTarget && isAlreadyOnTarget) {
      emitDashboardRemindersScroll()
      return
    }

    await router.push(notification.route)
  }
}
</script>

<template>
  <Notifications 
    :notifications="notificationsStore.recentNotifications" 
    :loading="notificationsStore.recentLoading"
    @open="onMenuOpen"
    @click:readAllNotifications="onReadAll" 
    @click:notification="onNotificationClick"
  />  
</template>
