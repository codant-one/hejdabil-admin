<script setup>
import Notifications from '@core/components/Notifications.vue'
import { onMounted } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'

const notificationsStore = useNotificationsStore()

onMounted(async () => {
  await notificationsStore.init()
})

const onReadAll = () => {
  notificationsStore.markAllRead()
}
</script>

<template>
  <Notifications :notifications="notificationsStore.notifications" @click:readAllNotifications="onReadAll" />
  
</template>
