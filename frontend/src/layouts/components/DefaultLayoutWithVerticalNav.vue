<script setup>
import Footer from '@/layouts/components/Footer.vue'
import navItems from '@/navigation/vertical'
import { useThemeConfig } from '@core/composable/useThemeConfig'

// Components
import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'

// @layouts plugin
import { VerticalNavLayout } from '@layouts'
import NavBarNotifications from '@/layouts/components/NavBarNotifications.vue'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

import kopIcon from '@/assets/images/icons/figma/car_down.svg'
import saljIcon from '@/assets/images/icons/figma/car_up.svg'
import settingsIcon from '@/assets/images/icons/figma/settings.svg'

const { appRouteTransition, isLessThanOverlayNavBreakpoint } = useThemeConfig()
const { width: windowWidth } = useWindowSize()
</script>

<template>
  <VerticalNavLayout
    :nav-items="navItems"
  >
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">

        <VBtn
          v-if="isLessThanOverlayNavBreakpoint(windowWidth)"
          icon
          variant="text"
          color="default"
          class="ms-n3"
          size="small"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon icon="tabler-menu-2" size="24" />
        </VBtn>
        

        <RouterLink to="/" class="d-flex align-center ms-3">
          <VNodeRenderer :nodes="themeConfig.app.logoFull" />
        </RouterLink>

        <VSpacer />


        <div class="d-flex align-center gap-x-3"> <!-- Aumentamos el gap un poco -->
          <VBtn class="btn-custom btn-kop" variant="flat" height="48">
            Köp
            <img :src="kopIcon" alt="Köp Icon" class="ms-2" />
          </VBtn>
          <VBtn class="btn-custom btn-salj" variant="flat" height="48">
            Sälj
            <img :src="saljIcon" alt="Sälj Icon" class="ms-2" />
          </VBtn>
        </div>

        <VSpacer />

        <!-- Iconos a la Derecha -->
        <div class="navbar-actions-group d-flex align-center">
          <NavBarNotifications />
          <VBtn icon variant="flat" class="btn-settings" height="48" width="48">
            <img :src="settingsIcon" alt="Settings Icon" width="24" />
          </VBtn>
          <UserProfile class="ms-2" />
          <VIcon icon="tabler-chevron-down" size="20" class="ms-n1" />
        </div>
      </div>
    </template>


    <!-- 👉 Pages -->
    <RouterView v-slot="{ Component }">
      <Transition
        :name="appRouteTransition"
        mode="out-in"
      >
        <Component :is="Component" />
      </Transition>
    </RouterView>

    
    <!--  👉 Footer -->
    <template #footer>
      <Footer />
    </template>
    

    <!-- 👉 Customizer -->
    <!-- <TheCustomizer /> -->
  </VerticalNavLayout>
</template>
<style lang="scss" scoped>

:deep(.layout-wrapper.layout-nav-type-vertical .layout-navbar .navbar-content-container) {
  
  background: transparent !important;
  box-shadow: none !important;
  border: none !important;
  

  -webkit-backdrop-filter: none !important;
  backdrop-filter: none !important;
}


:deep(.layout-navbar) {
  background: transparent !important;
  box-shadow: none !important;
}

:deep(.btn-custom) {
  border-radius: 48px !important;
  padding-inline: 16px !important;
  text-transform: none;
  

  .v-btn__content {
    font-family: 'DM Sans', sans-serif;
    font-weight: 500;
    font-size: 16px;
    line-height: 16px;
    letter-spacing: 0;
  }
}


:deep(.btn-kop) {
  background-color: #3AF8FF !important;
  color: #1C2925 !important; 
}


:deep(.btn-salj) {
  background-color: #79FCA2 !important;
  color: #1C2925 !important; 
}



.navbar-actions-group {
  background-color: #FFFFFF;
  border-radius: 64px; 
  padding-inline: 16px 8px; 
  padding-block: 4px;
  gap: 8px; 
}

</style>