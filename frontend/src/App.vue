<script setup>

import router from "@/router";
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useThemeConfig } from '@core/composable/useThemeConfig'
import { hexToRgb } from '@layouts/utils'
import { useTheme } from 'vuetify'
import { useAuthStores } from '@/stores/useAuth'
import GlobalEvents from "@/components/GlobalEvents.vue";

const ability = useAppAbility()
const authStores = useAuthStores()

const {
  syncInitialLoaderTheme,
  syncVuetifyThemeWithTheme: syncConfigThemeWithVuetifyTheme,
  isAppRtl,
} = useThemeConfig()

const { global } = useTheme()

// ℹ️ Sync current theme with initial loader theme
syncInitialLoaderTheme()
syncConfigThemeWithVuetifyTheme()

const redirectTo = (path) => {
  router.push({
    name: path,
  });
};

const me = async () => {

  if(localStorage.getItem('user_data')){
    const userData = localStorage.getItem('user_data')
    const userDataJ = JSON.parse(userData)

    const { user_data, userAbilities } = await authStores.me(userDataJ)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

    ability.update(userAbilities)

    localStorage.setItem('user_data', JSON.stringify(user_data))

  }
}

me()

</script>

<template>
   <section>
    <GlobalEvents />
    <VLocaleProvider :rtl="isAppRtl">
      <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
      <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}`">
        <div class="sticky-container d-none d-md-flex">
          <div class="d-flex gap-x-3 buttons-center">
            <VBtn
              class="btn-blue px-6"
              @click="redirectTo('dashboard-admin-stock')"
            >
              Köp
              <VIcon icon="custom-car-close" size="24" />
            </VBtn>
            <VBtn
              class="btn-green px-6"
              @click="redirectTo('dashboard-admin-sold')"
            >
              Sälj
              <VIcon icon="custom-car-open" size="24" />
            </VBtn>
          </div>
        </div>
        <RouterView :key="$route.fullPath"/>
      </VApp>
    </VLocaleProvider>
  </section>
</template>

<style>
  .sticky-container {
    position: sticky;
    top: 2.5%;      
    z-index: 9999;
  }

  .buttons-center {
    position: absolute;
    left: 50%;                 
    transform: translateX(-50%);
  }
</style>
