<script setup>

import { useThemeConfig } from "@core/composable/useThemeConfig";
import MobileBottomBar from "@/layouts/components/MobileBottomBar.vue";
import navItems from "@/navigation/vertical";

// Components
import UserProfile from "@/layouts/components/UserProfile.vue";

// @layouts plugin
import { VerticalNavLayout } from "@layouts";
import { VNodeRenderer } from "@layouts/components/VNodeRenderer";
import { themeConfig } from "@themeConfig";
import NavBarNotifications from "@/layouts/components/NavBarNotifications.vue";

const { appRouteTransition, isLessThanOverlayNavBreakpoint } = useThemeConfig();
const { width: windowWidth } = useWindowSize();

</script>

<template>
  <VerticalNavLayout :nav-items="navItems">
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex'" class="sticky-container">
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
      <div class="d-flex h-100 align-center">
        <RouterLink to="/" :class="windowWidth < 1024 ? 'd-flex' : 'd-none'" class="align-center md-ms-3 header-logo">
          <VNodeRenderer :nodes="themeConfig.app.logoFull" />
        </RouterLink>

        <VSpacer />

        <div class="d-flex align-center gap-x-2">
          <NavBarNotifications />
          <VBtn
            variant="flat"
            :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"
            class="btn-white-3"
            height="48"
            width="48"
          >
            <VIcon icon="custom-settings" size="24" />
          </VBtn>
          <UserProfile />
        </div>
      </div>
    </template>

    <!-- 👉 Pages -->
    <RouterView v-slot="{ Component }">
      <Transition :name="appRouteTransition" mode="out-in">
        <Component :is="Component" />
      </Transition>
    </RouterView>

    <!-- 👉 Mobile Bottom Bar -->
    <MobileBottomBar :nav-items="navItems" />
  </VerticalNavLayout>
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
<style lang="scss" scoped>
  :deep(
    .layout-wrapper.layout-nav-type-vertical
    .layout-navbar
     .navbar-content-container
  ) {
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
      font-family: "DM Sans", sans-serif;
      font-weight: 500;
      font-size: 16px;
      line-height: 16px;
      letter-spacing: 0;
    }
  }

  :deep(.btn-custom-settings) {
    width: 48px !important;
    height: 48px !important;
    min-width: auto !important;
    border-radius: 50% !important;
    background-color: #fff !important;
    padding: 0 !important;
  }

  .navbar-actions-group {
    background-color: #ffffff;
    border-radius: 64px;
    padding-inline: 16px 8px;
    padding-block: 4px;
    gap: 8px;
  }
</style>
