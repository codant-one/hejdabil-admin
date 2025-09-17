<script setup>
import { ref } from "vue";
import { config } from "@layouts/config";
import { useWindowSize } from "@vueuse/core";
import Component from "vue-flatpickr-component";
import { getComputedNavLinkToProp, isNavLinkActive } from "@layouts/utils";

const showMenu = ref(false);
const { width } = useWindowSize();
const MOBILE_BREAKPOINT = 768; // px

const props = defineProps({
  navItems: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <VBottomNavigation v-if="width < MOBILE_BREAKPOINT" height="88" class="mobile-bottom-bar">
    <VBtn>
      <VIcon icon="custom-home" size="28" />
      <span>Hem</span>
    </VBtn>
    <VBtn class="btn-green">
      <VIcon icon="custom-car-close" size="28" />
      <span>Köp</span>
    </VBtn>
    <VBtn class="btn-blue">
      <VIcon icon="custom-car-open" size="28" />
      <span>Sälj</span>
    </VBtn>
    <VMenu
      v-model="showMenu"
      :close-on-content-click="false"
      transition="scale-transition"
    >
      <template #activator="{ props: menuProps }">
        <!-- Keep the menu button as activator -->
        <VBtn v-bind="menuProps" aria-label="Open menu">
          <VIcon icon="tabler-menu-2" size="28" />
          <span>Meny</span>
        </VBtn>
      </template>
      <div class="d-flex mobile-nav-sheet">
        <span class="mb-4">Meny</span>
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
    </VMenu>
  </VBottomNavigation>
</template>

<style scoped></style>
