<script setup>

import { useRouter } from 'vue-router'
import { useNotificationsStore } from '@/stores/useNotifications'

import logo from "@images/logos/billogg-logo.svg";

const notificationsStore = useNotificationsStore()
const router = useRouter()

const { width: windowWidth } = useWindowSize()

onMounted(async () => {
  await notificationsStore.markAllRead()
})

const redirectTo = (path) => {
  router.push({
    name: path,
  });
};


</script>

<template>
  <VCard class="bg-gradient pa-4 d-flex flex-column" style="min-height: 100vh;">
    <div 
        class="d-flex align-center flex-0 cursor-pointer" 
        :class="windowWidth < 1024 ? 'justify-center' : ''"
        @click="redirectTo('dashboard-panel')"
    >
      <img :src="logo" width="121" height="40" alt="Billogg" />
    </div>

    
  </VCard>
</template>

<route lang="yaml">
    meta:
        layout: blank
        action: view
        subject: dashboard
</route>
