<script setup>
import { ref } from "vue";
import { config } from "@layouts/config";
import { useWindowSize } from "@vueuse/core";
import Component from "vue-flatpickr-component";
import { getComputedNavLinkToProp, isNavLinkActive } from "@layouts/utils";
import router from "@/router";

const showMenu = ref(false);
const { width } = useWindowSize();
const MOBILE_BREAKPOINT = 768; // px

const props = defineProps({
  navItems: {
    type: Array,
    required: true,
  },
});

const redirectTo = (path) => {
  router.push({
    name: path
  });
};
</script>

<template>
  <VBottomNavigation
    v-if="width < MOBILE_BREAKPOINT"
    height="88"
    class="mobile-bottom-bar"
  >
    <VBtn @click="redirectTo('dashboard-panel')">
      <VIcon icon="custom-home" size="24" />
      <span>Hem</span>
    </VBtn>
    <VBtn class="btn-green" @click="redirectTo('dashboard-admin-stock')">
      <VIcon icon="custom-car-close" size="24" />
      <span>Köp</span>
    </VBtn>
    <VBtn class="btn-blue" @click="redirectTo('dashboard-admin-sold')">
      <VIcon icon="custom-car-open" size="24" />
      <span>Sälj</span>
    </VBtn>
    <VBtn
      aria-label="Open mobile nav menu"
      @click="showMenu = true"
      class="btn-ghost"
    >
      <VIcon icon="custom-menu" size="24" />
      <span>Meny</span>
    </VBtn>
    <VDialog
      v-model="showMenu"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
      class="mobile-nav-menu"
    >
      <VCard>
        <div class="d-flex mobile-nav-sheet">
          <span class="mobile-menu-title">Meny</span>
          <div v-for="(item, index) in props.navItems" class="nav-link">
            <Component
              :is="item.to ? 'RouterLink' : 'a'"
              v-bind="getComputedNavLinkToProp(item)"
              :class="{
                'router-link-active router-link-exact-active': isNavLinkActive(
                  item,
                  $router
                ),
              }"
              :key="index"
              @click="showMenu = false"
            >
              <Component
                :is="config.app.iconRenderer || 'div'"
                v-bind="item.icon || config.verticalNav.defaultNavItemIconProps"
                class="nav-item-icon"
              />
              <span>{{ item.title }}</span>
            </Component>
          </div>
        </div>
      </VCard>
    </VDialog>
  </VBottomNavigation>
</template>

<style lang="scss" scoped>
.mobile-menu-title {
  font-family: DM Sans;
  font-weight: 500;
  font-style: Medium;
  font-size: 16px;
  line-height: 16px;
  letter-spacing: 0;
  color: #1C2925 !important;
  margin-bottom: 18px;
}
</style>
