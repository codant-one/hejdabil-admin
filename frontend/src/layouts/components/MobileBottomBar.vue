<script setup>

import { computed, getCurrentInstance, ref } from "vue";
import { themeConfig } from "@themeConfig";
import { useWindowSize } from "@vueuse/core";
import { getComputedNavLinkToProp, isNavGroupActive, isNavLinkActive } from "@layouts/utils";
import { can, canWithPlan, canViewNavMenuGroup } from "@layouts/plugins/casl";
import router from "@/router";

const showMenu = ref(false);
const { width } = useWindowSize();
const MOBILE_BREAKPOINT = 1024; // px
const route = useRoute();
const emitter = inject("emitter");
const vm = getCurrentInstance();

const canShowSwishaButton = computed(() => {
  const hasPermission = vm?.proxy?.$can ? vm.proxy.$can("create", "payouts") : true;

  if (!hasPermission)
    return false;

  const userData = JSON.parse(localStorage.getItem("user_data") || "null");
  if (!userData)
    return false;

  const userRole = userData.roles?.[0]?.name;

  if (userRole !== "Supplier" && userRole !== "User")
    return false;

  if (userRole === "Supplier")
    return userData.supplier?.is_payout === 1;

  return true;
});

const openGroups = ref({})
const openChildGroups = ref({})

const toggleGroup = idx => {
  openGroups.value[idx] = !openGroups.value[idx]
  if (!openGroups.value[idx])
    delete openChildGroups.value[idx]
}

const toggleChildGroup = (parentIdx, childIdx) => {
  if (!openChildGroups.value[parentIdx])
    openChildGroups.value[parentIdx] = {}

  openChildGroups.value[parentIdx][childIdx] = !openChildGroups.value[parentIdx][childIdx]
}

const props = defineProps({
  navItems: {
    type: Array,
    required: true,
  },
});

watch([() => route.path, () => props.navItems], () => {
  openGroups.value = {}
  openChildGroups.value = {}
  props.navItems.forEach((item, index) => {
    if (!Array.isArray(item.children) || !isNavGroupActive(item.children, router))
      return

    openGroups.value[index] = true
    item.children.forEach((child, childIndex) => {
      if (Array.isArray(child.children) && isNavGroupActive(child.children, router)) {
        if (!openChildGroups.value[index])
          openChildGroups.value[index] = {}
        openChildGroups.value[index][childIndex] = true
      }
    })
  })
}, { immediate: true })

const redirectTo = (path) => {
  router.push({
    name: path
  });
};

const redirectToPayoutsAndOpenDialog = () => {
  if (route.name === "dashboard-admin-payouts") {
    emitter.emit("open-payout-dialog");

    return;
  }

  router.push({
    name: "dashboard-admin-payouts",
    query: { open_payout: "true" },
  });
};
</script>

<template>
  <VBottomNavigation
    v-show="width < MOBILE_BREAKPOINT"
    height="88"
    class="mobile-bottom-bar"
  >
    <VBtn @click="redirectTo('dashboard-panel')" class="bg-transparent">
      <VIcon icon="custom-home" size="24" />
      <span>Hem</span>
    </VBtn>
    <VBtn 
      v-if="canWithPlan('create', 'agreements')" 
      class="btn-green" 
      @click="redirectTo('dashboard-admin-agreements-purchase')"
    >
      <VIcon icon="custom-car-close" size="24" />
      <span>Köp</span>
    </VBtn>
    <VBtn 
      v-if="canWithPlan('create', 'agreements')" 
      class="btn-blue" 
      @click="redirectTo('dashboard-admin-agreements-sales')"
    >
      <VIcon icon="custom-car-open" size="24" />
      <span>Sälj</span>
    </VBtn>
    <VBtn
      v-if="canShowSwishaButton"
      class="btn-gradient-2 bg-transparent" 
      @click="redirectToPayoutsAndOpenDialog"
    >
      <VIcon icon="custom-swish-outlined" size="24" />
      <span>Swisha</span>
    </VBtn>
    <VBtn
      ref="menuBtn"
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
      @after-leave="$refs.menuBtn?.$el?.blur()"
    >
      <VCard>
        <div class="d-flex mobile-nav-sheet">
          <span class="mobile-menu-title">Meny</span>
          <template v-for="(item, index) in props.navItems" :key="index">
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
              <div
                v-else
                class="nav-group-label"
                :class="item.class"
                role="button"
                tabindex="0"
                :aria-expanded="!!openGroups[index]"
                @click.stop.prevent="toggleGroup(index)"
                @keydown.enter.prevent="toggleGroup(index)"
                @keydown.space.prevent="toggleGroup(index)"
              >
                <component
                  :is="themeConfig.app.iconRenderer || 'div'"
                  v-bind="item.icon || themeConfig.verticalNav.defaultNavItemIconProps"
                  class="nav-item-icon"
                />
                <div class="d-flex w-100 justify-between align-center pe-2">
                <span>{{ item.title }}</span>
                <component
                  :is="themeConfig.app.iconRenderer || 'div'"
                  v-bind="themeConfig.icons.chevronRight"
                  class="nav-group-arrow"
                />
                </div>
              </div>

              <div class="nav-group-children ps-7 py-2" v-show="openGroups[index] === true">
                <div v-for="(child, cIdx) in item.children" :key="cIdx" class="w-100">
                  <div
                    v-if="Array.isArray(child.children) && child.children.length && canViewNavMenuGroup(child)"
                    class="nav-group"
                    :class="{ open: openChildGroups[index]?.[cIdx] }"
                  >
                    <div
                      class="nav-group-label"
                      :class="child.class"
                      role="button"
                      tabindex="0"
                      :aria-expanded="!!openChildGroups[index]?.[cIdx]"
                      @click.stop.prevent="toggleChildGroup(index, cIdx)"
                      @keydown.enter.prevent="toggleChildGroup(index, cIdx)"
                      @keydown.space.prevent="toggleChildGroup(index, cIdx)"
                    >
                      <component
                        :is="themeConfig.app.iconRenderer || 'div'"
                        v-bind="child.icon || themeConfig.verticalNav.defaultNavItemIconProps"
                        class="nav-item-icon"
                      />
                      <div class="d-flex w-100 justify-between align-center pe-2">
                        <span>{{ child.title }}</span>
                        <component
                          :is="themeConfig.app.iconRenderer || 'div'"
                          v-bind="themeConfig.icons.chevronRight"
                          class="nav-group-arrow"
                        />
                      </div>
                    </div>
                    <div
                      v-show="openChildGroups[index]?.[cIdx]"
                      class="nav-subchildren ps-7 py-1"
                    >
                      <div
                        v-for="(gchild, gIdx) in child.children"
                        :key="gIdx"
                      >
                        <div
                          v-if="can(gchild.action, gchild.subject)"
                          class="nav-link grandchild"
                        >
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
                  <div
                    v-else-if="(!Array.isArray(child.children) || child.children.length === 0) && can(child.action, child.subject)"
                    class="nav-link child"
                  >
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
                </div>
              </div>
            </div>
          </template>
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

.mobile-nav-sheet :deep(.nav-group-label > .nav-item-icon),
.mobile-nav-sheet :deep(.nav-group .nav-link .nav-item-icon),
.mobile-nav-sheet :deep(.nav-group-children .nav-link .nav-item-icon),
.mobile-nav-sheet :deep(.nav-subchildren .nav-item-icon) {
  font-size: 1.375rem !important;
  margin-inline-start: 0 !important;
  margin-inline-end: 0.5rem !important;
}

.mobile-nav-sheet :deep(.nav-group-label > .nav-item-icon) {
  color: #6e9383;
}
</style>
