<script setup>
import { ref } from "vue";
import { themeConfig } from "@themeConfig";
import { useWindowSize } from "@vueuse/core";
import { getComputedNavLinkToProp, isNavLinkActive } from "@layouts/utils";
import { can, canViewNavMenuGroup } from "@layouts/plugins/casl";
import router from "@/router";

const showMenu = ref(false);
const { width } = useWindowSize();
const MOBILE_BREAKPOINT = 992; // px

// Track open/closed state per top-level item index
const openGroups = ref({})

const toggleGroup = (idx) => {
  openGroups.value[idx] = !openGroups.value[idx]
}

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
    v-show="width < MOBILE_BREAKPOINT"
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
          <div v-for="(item, index) in props.navItems" :key="index" class="w-100">
            <!-- Leaf item -->
            <div
              v-if="(!Array.isArray(item.children) || item.children.length === 0) && can(item.action, item.subject)"
              class="nav-link"
            >
              <component
                :is="item.to ? 'RouterLink' : 'a'"
                v-bind="getComputedNavLinkToProp(item)"
                :class="[
                  isNavLinkActive(item, $router) && 'router-link-active router-link-exact-active',
                  item.class,
                ]"
                @click="showMenu = false"
              >
              <component
                :is="themeConfig.app.iconRenderer || 'div'"
                v-bind="item.icon || themeConfig.verticalNav.defaultNavItemIconProps"
                class="nav-item-icon"
              />
                <span>{{ item.title }}</span>
              </component>
            </div>

            <!-- Group item with children -->
            <div
              v-else-if="Array.isArray(item.children) && canViewNavMenuGroup(item)"
              class="nav-group"
              :class="{ open: openGroups[index] }"
            >
              <!-- Group label should be the first child to match SCSS expectations -->
              <div :class="['nav-group-label', item.class]" v-if="item.to">
                <template v-if="item.to">
                  <component
                    :is="'RouterLink'"
                    v-bind="getComputedNavLinkToProp(item)"
                    :class="[
                      isNavLinkActive(item, $router) && 'router-link-active router-link-exact-active',
                      item.class,
                    ]"
                    @click="showMenu = false"
                  >
                    <component
                      :is="themeConfig.app.iconRenderer || 'div'"
                      v-bind="item.icon || themeConfig.verticalNav.defaultNavItemIconProps"
                      class="nav-item-icon"
                    />
                    <span>{{ item.title }}</span>
                    <component
                      :is="themeConfig.app.iconRenderer || 'div'"
                      v-bind="themeConfig.icons.chevronRight"
                      class="nav-group-arrow"
                    />
                  </component>
                </template>
              </div>
              <div :class="['nav-group-label', item.class]" v-else @click.stop.prevent="toggleGroup(index)">
                <component
                  :is="themeConfig.app.iconRenderer || 'div'"
                  v-bind="item.icon || themeConfig.verticalNav.defaultNavItemIconProps"
                  class="nav-item-icon"
                />
                <span>{{ item.title }}</span>
                <component
                  :is="themeConfig.app.iconRenderer || 'div'"
                  v-bind="themeConfig.icons.chevronRight"
                  class="nav-group-arrow"
                />
              </div>

              <div class="nav-group-children ps-7 py-2" v-show="openGroups[index] === true">
                <div v-for="(child, cIdx) in item.children" :key="cIdx" class="w-100">
                  <div class="nav-link child" v-if="can(child.action, child.subject)">
                    <component
                      :is="child.to ? 'RouterLink' : 'a'"
                      v-bind="getComputedNavLinkToProp(child)"
                      :class="[
                        isNavLinkActive(child, $router) && 'router-link-active router-link-exact-active',
                        child.class,
                      ]"
                      @click="showMenu = false"
                    >
                    <component
                      :is="themeConfig.app.iconRenderer || 'div'"
                      v-bind="child.icon || themeConfig.verticalNav.defaultNavItemIconProps"
                      class="nav-item-icon"
                    />
                      <span>{{ child.title }}</span>
                    </component>
                  </div>

                  <!-- Grandchildren (2nd level) -->
              <div v-if="child.children && child.children.length" class="nav-subchildren ps-7 py-1">
                <div v-for="(gchild, gIdx) in child.children" :key="gIdx">
                  <div class="nav-link grandchild" v-if="can(gchild.action, gchild.subject)">
                    <component
                      :is="gchild.to ? 'RouterLink' : 'a'"
                      v-bind="getComputedNavLinkToProp(gchild)"
                      :class="[
                        isNavLinkActive(gchild, $router) && 'router-link-active router-link-exact-active',
                        gchild.class,
                      ]"
                      @click="showMenu = false"
                    >
                      <component
                        :is="themeConfig.app.iconRenderer || 'div'"
                        v-bind="gchild.icon || themeConfig.verticalNav.defaultNavItemIconProps"
                        class="nav-item-icon"
                      />
                      <span>{{ gchild.title }}</span>
                    </component>
                  </div>
                </div>
              </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </VCard>
    </VDialog>
  </VBottomNavigation>
</template>

<style lang="scss" scoped>
.mobile-bottom-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
  /* Safe area for devices with notches (iOS Safari) */
  padding-bottom: constant(safe-area-inset-bottom);
  padding-bottom: env(safe-area-inset-bottom);
  /* Prevent jumping/flicker on mobile browsers */
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
  transform: translateZ(0);
  will-change: transform;
}

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

/* Make subitem icons match top-level size and reset nested margins */
:deep(.mobile-nav-sheet .nav-group .nav-link .nav-item-icon),
:deep(.mobile-nav-sheet .nav-group .nav-group .nav-item-icon),
:deep(.mobile-nav-sheet .nav-group-children .nav-link .nav-item-icon),
:deep(.mobile-nav-sheet .nav-subchildren .nav-item-icon) {
  font-size: 1.375rem !important;
  margin-inline-start: 0 !important;
  margin-inline-end: 0.5rem !important;
}
</style>
