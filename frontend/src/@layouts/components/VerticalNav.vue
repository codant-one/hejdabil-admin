<script setup>

import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { VNodeRenderer } from "./VNodeRenderer";
import { injectionKeyIsVerticalNavHovered, useLayouts } from "@layouts";
import {
  VerticalNavGroup,
  VerticalNavLink,
  VerticalNavSectionTitle,
} from "@layouts/components";
import { config } from "@layouts/config";

import toggleNav from "@/assets/images/icons/figma/toggleNav.svg";
import toggleNavClose from "@/assets/images/icons/figma/toggleNavClose.svg";

const props = defineProps({
  tag: {
    type: [String, null],
    required: false,
    default: "aside",
  },
  navItems: {
    type: null,
    required: true,
  },
  isOverlayNavActive: {
    type: Boolean,
    required: true,
  },
  toggleIsOverlayNavActive: {
    type: Function,
    required: true,
  },
});

const refNav = ref();
const { width: windowWidth } = useWindowSize();

// Provide hover state so children can inject it
const isHovered = ref(false)
provide(injectionKeyIsVerticalNavHovered, isHovered)

const {
  isVerticalNavCollapsed: isCollapsed,
  isLessThanOverlayNavBreakpoint,
  isVerticalNavMini,
  isAppRtl,
} = useLayouts();

const hideTitleAndIcon = isVerticalNavMini(windowWidth, isHovered);

const resolveNavItemComponent = (item) => {
  if ("heading" in item) return VerticalNavSectionTitle;
  if ("children" in item) return VerticalNavGroup;

  return VerticalNavLink;
};

const route = useRoute();

const isSettingsRoute = computed(() => route.path.startsWith("/dashboard/settings"));

watch(
  () => route.name,
  () => {
    props.toggleIsOverlayNavActive(false);
  }
);

const isVerticalNavScrolled = ref(false);
const updateIsVerticalNavScrolled = (val) =>
  (isVerticalNavScrolled.value = val);

const handleNavScroll = (evt) => {
  isVerticalNavScrolled.value = evt.target.scrollTop > 0;
};

</script>

<template>
  <Component
    :is="props.tag"
    ref="refNav"
    class="layout-vertical-nav"
    :class="[
      {
        'settings-route': isSettingsRoute,
        'overlay-nav': isLessThanOverlayNavBreakpoint(windowWidth),
        visible: isOverlayNavActive,
        scrolled: isVerticalNavScrolled,
        hovered: isHovered,
      },
    ]"
  >
    <!-- 👉 Header -->
    <div :class="isSettingsRoute ? 'nav-header-logo-settings' : 'nav-header-logo'">
      <RouterLink
        to="/info"
        :class="hideTitleAndIcon ? 'justify-center' : ''"
        class="d-flex h-100 app-logo align-center gap-x-1 app-title-wrapper"
      >
        <VNodeRenderer :nodes="(hideTitleAndIcon) ? config.app.logoWhite : config.app.logoFull" />
      </RouterLink>
    </div>
    <div class="nav-header" :class="isSettingsRoute ? 'nav-items-settings' : ''">
      <slot name="nav-header" v-if="!isSettingsRoute">
        <span v-show="!hideTitleAndIcon">Meny</span>

        <!-- 👉 Vertical nav actions -->
        <!-- Show toggle collapsible in >md and close button in <md -->
        <template v-if="!isLessThanOverlayNavBreakpoint(windowWidth)">
          <VBtn
            class="btn-header-action"
            aria-label="toggle vertical navigation"
            @click="isCollapsed = !isCollapsed"
          >
            <img :src="isCollapsed ? toggleNavClose : toggleNav" alt="Toggle Nav Icon" />
          </VBtn>
        </template>
        <template v-else>
          <Component
            :is="config.app.iconRenderer || 'div'"
            class="header-action"
            v-bind="config.icons.close"
            @click="toggleIsOverlayNavActive(false)"
          />
        </template>
      </slot>
      <slot name="nav-header" v-else>
        <div class="d-flex flex-column gap-4">
          <VBtn
            class="btn-light w-auto"
            :to="{ name: 'dashboard-panel' }"
          >
            <VIcon icon="custom-return" size="24" />
            Tillbaka
          </VBtn>

          <span class="title-settings">
            Inställningar
          </span>
        </div>
      </slot>
    </div>

    <slot
      name="nav-items"
      :update-is-vertical-nav-scrolled="updateIsVerticalNavScrolled"
    >
      <PerfectScrollbar
        :key="isAppRtl"
        tag="ul"
        class="nav-items"
        :class="isSettingsRoute ? 'nav-items-settings' : ''"
        :options="{ wheelPropagation: false }"
        @ps-scroll-y="handleNavScroll"
      >
        <Component
          :is="resolveNavItemComponent(item)"
          v-for="(item, index) in navItems"
          :key="index"
          :item="item"
        />
      </PerfectScrollbar>
    </slot>
  </Component>
</template>

<style lang="scss">
@use "@configured-variables" as variables;
@use "@layouts/styles/mixins";

// 👉 Vertical Nav
.layout-vertical-nav {
  position: fixed;
  z-index: 999;
  display: flex;
  flex-direction: column;
  block-size: 100%;
  inline-size: variables.$layout-vertical-nav-width;
  inset-block-start: 0;
  inset-inline-start: 0;
  will-change: transform, inline-size;
  background-color: transparent !important;
  box-shadow: none !important;

  &.settings-route {
    background-color: #fff !important;
  }

  .nav-header-logo {
    margin: 30px 24px 0 24px;
  }

  .nav-header-logo-settings {
    padding: 30px 24px 26px 24px;
    border-bottom: 1px solid #E7E7E7;
  }

  .nav-items-settings {
    border-right: 1px solid #E7E7E7;

    .nav-item-icon {
      color: #454545 !important;
    }

    a:hover {
      background-color: #E7E7E7 !important;
    }

    a:hover span {
      color: #454545 !important;
    }
  }

  .title-settings {
    font-weight: 500;
    font-size: 16px;
    line-height: 16px;
    letter-spacing: 0;
    color: #1C2925;
  }

  .nav-header {
    padding: 30px 24px 24px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 40px;

    span {
      font-weight: 500;
      font-size: 16px;
      line-height: 16px;
      color: #1c2925;
    }

    .header-action {
      width: 16px;
      height: 16px;
      cursor: pointer;
    }
  }

  .app-title-wrapper {
    margin-inline-end: auto;
  }

  .nav-items {
    block-size: 100%;
    padding-bottom: 19px;
    display: flex;
    flex-direction: column;

    > .help-button {
      margin-top: auto;
    }

    // ℹ️ We no loner needs this overflow styles as perfect scrollbar applies it
    // overflow-x: hidden;

    // // ℹ️ We used `overflow-y` instead of `overflow` to mitigate overflow x. Revert back if any issue found.
    // overflow-y: auto;
  }

  .nav-item-title {
    overflow: hidden;
    margin-inline-end: auto;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  // 👉 Collapsed
  .layout-vertical-nav-collapsed & {
    &:not(.hovered) {
      inline-size: 120px;
    }

    &.layout-vertical-nav:not(.hovered) {
      width: 96px;
    }
  }

  // 👉 Overlay nav
  &.overlay-nav {
    &:not(.visible) {
      transform: translateX(-#{variables.$layout-vertical-nav-width});

      @include mixins.rtl {
        transform: translateX(variables.$layout-vertical-nav-width);
      }
    }
  }
}
</style>
