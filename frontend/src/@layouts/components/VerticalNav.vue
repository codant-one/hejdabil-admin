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
import { openGroups } from "@layouts/utils";

import toggleNav from "@/assets/images/icons/figma/toggleNav.svg";

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
const isHovered = useElementHover(refNav);
const titleAPP = ref(import.meta.env.VITE_APP_TITLE);

provide(injectionKeyIsVerticalNavHovered, isHovered);

const {
  isVerticalNavCollapsed: isCollapsed,
  isLessThanOverlayNavBreakpoint,
  isVerticalNavMini,
  isAppRtl,
} = useLayouts();

const hideTitleAndIcon = isVerticalNavMini(windowWidth, isHovered);

const wasCollapsed = ref(isCollapsed.value);


watch(isHovered, val => {
  if (val) {
    wasCollapsed.value = isCollapsed.value;
    isCollapsed.value = false;
  } else {
    isCollapsed.value = wasCollapsed.value;
  }
});

const resolveNavItemComponent = (item) => {
  if ("heading" in item) return VerticalNavSectionTitle;
  if ("children" in item) return VerticalNavGroup;

  return VerticalNavLink;
};

const route = useRoute();

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

const closeAll = () => {
  openGroups.value = [];
  axios.post("menu/update", { menus: openGroups.value.join(",") });
};
</script>

<template>
  <Component
    :is="props.tag"
    ref="refNav"
    class="layout-vertical-nav"
    :class="[
      {
        'overlay-nav': isLessThanOverlayNavBreakpoint(windowWidth),
        hovered: isHovered,
        visible: isOverlayNavActive,
        scrolled: isVerticalNavScrolled,
      },
    ]"
  >
    <!-- 👉 Header -->
    <div class="nav-header">
      <slot name="nav-header">
        <!-- <RouterLink
          to="/info"
          class="app-logo d-flex align-center gap-x-1 app-title-wrapper"
        >
        <VNodeRenderer :nodes="(hideTitleAndIcon) ? config.app.logoWhite : config.app.logoFull" /> -->

        <!-- <Transition name="vertical-nav-app-title">
            <h4
              v-show="!hideTitleAndIcon"
              class="app-title font-weight-bold leading-normal"
            >
            {{ titleAPP }}
            </h4>
          </Transition> -->
        <!-- </RouterLink> -->
        <!-- Show toggle collapsible in >md and close button in <md -->
        <span>Meny</span>
        <!-- <VIcon
          icon="tabler-arrows-minimize"
          size="small"
          class="me-2"
          @click="closeAll"
        /> -->

        <!-- 👉 Vertical nav actions -->
        <!-- Show toggle collapsible in >md and close button in <md -->
        <template v-if="!isLessThanOverlayNavBreakpoint(windowWidth)">
          <VBtn
            class="btn-header-action"
            aria-label="toggle vertical navigation"
            v-show="!hideTitleAndIcon"
            @click="isCollapsed = !isCollapsed"
          >
            <img :src="toggleNav" alt="Toggle Nav Icon" class="" />
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
    </div>
    <!-- <slot name="before-nav-items">
      <div class="vertical-nav-items-shadow" />
    </slot> -->
    <slot
      name="nav-items"
      :update-is-vertical-nav-scrolled="updateIsVerticalNavScrolled"
    >
      <PerfectScrollbar
        :key="isAppRtl"
        tag="ul"
        class="nav-items"
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
  z-index: 1;
  margin-top: 100px;
  display: flex;
  flex-direction: column;
  block-size: 100%;
  inline-size: variables.$layout-vertical-nav-width;
  inset-block-start: 0;
  inset-inline-start: 0;
  transition: transform 0.25s ease-in-out, inline-size 0.25s ease-in-out,
    box-shadow 0.25s ease-in-out;
  will-change: transform, inline-size;

  .nav-header {
    margin: 4px 24px 24px 24px;
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
