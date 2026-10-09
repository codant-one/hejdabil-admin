<script setup>

import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useThemeConfig } from '@core/composable/useThemeConfig'
import { hexToRgb } from '@layouts/utils'
import { useTheme } from 'vuetify'
import { useAuthStores } from '@/stores/useAuth'
import { useNotificationsStore } from '@/stores/useNotifications'
import GlobalEvents from "@/components/GlobalEvents.vue";

const ability = useAppAbility()
const authStores = useAuthStores()
const notificationsStore = useNotificationsStore()

const {
  syncInitialLoaderTheme,
  syncVuetifyThemeWithTheme: syncConfigThemeWithVuetifyTheme,
  isAppRtl,
} = useThemeConfig()

const { global } = useTheme()
const route = useRoute()
const appBackgroundStyle = computed(() => route.path.startsWith('/dashboard/settings')
  ? 'background: #fff !important; background-color: #fff !important;'
  : undefined)

// ℹ️ Sync current theme with initial loader theme
syncInitialLoaderTheme()
syncConfigThemeWithVuetifyTheme()

const setupUserDataStorageSync = () => {
  if (window.__userDataStorageSyncInitialized)
    return

  const originalSetItem = window.localStorage.setItem.bind(window.localStorage)
  const originalRemoveItem = window.localStorage.removeItem.bind(window.localStorage)
  const originalClear = window.localStorage.clear.bind(window.localStorage)

  window.localStorage.setItem = (key, value) => {
    originalSetItem(key, value)

    if (key !== 'user_data')
      return

    let parsedUserData = null

    try {
      parsedUserData = JSON.parse(value)
    } catch (error) {
      console.error('Failed to parse user_data value from localStorage.setItem:', error)
    }

    window.dispatchEvent(new CustomEvent('user-data-updated', { detail: parsedUserData }))
  }

  window.localStorage.removeItem = key => {
    originalRemoveItem(key)

    if (key === 'user_data')
      window.dispatchEvent(new CustomEvent('user-data-updated', { detail: null }))
  }

  window.localStorage.clear = () => {
    originalClear()
    window.dispatchEvent(new CustomEvent('user-data-updated', { detail: null }))
  }

  window.__userDataStorageSyncInitialized = true
}

const me = async () => {
  if (route.path.startsWith('/sign/'))
    return

  if(localStorage.getItem('user_data')){
    try {
      const userData = localStorage.getItem('user_data')
      const userDataJ = JSON.parse(userData)

      const { user_data, userAbilities } = await authStores.me(userDataJ)

      localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

      ability.update(userAbilities)

      localStorage.setItem('user_data', JSON.stringify(user_data))

      await notificationsStore.init(user_data?.id ?? user_data?.user?.id ?? null)
    } catch (error) {
      console.error('Error refreshing authenticated user context:', error)
    }

  }
}

setupUserDataStorageSync()
me()

</script>

<template>
   <section>
    <GlobalEvents />
    <VLocaleProvider :rtl="isAppRtl">
      <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
      <VApp :class="{ 'settings-route': route.path.startsWith('/dashboard/settings') }" :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}; ${appBackgroundStyle || ''}`">
        <RouterView :key="$route.fullPath"/>
      </VApp>
    </VLocaleProvider>
  </section>
</template>
