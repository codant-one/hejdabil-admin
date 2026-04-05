<script setup>
import { useLayouts } from '@layouts'
import { config } from '@layouts/config'
import { can } from '@layouts/plugins/casl'
import { useBillingsStores } from '@/stores/useBillings'
import {
  getComputedNavLinkToProp,
  isNavLinkActive,
} from '@layouts/utils'

const emitter = inject("emitter")
const billingsStores = useBillingsStores()
const props = defineProps({
  item: {
    type: null,
    required: true,
  },
})

const route = useRoute()
const isSettingsRoute = computed(() => route.path.startsWith('/dashboard/settings'))

const handleClick = (item) => {
  const currentRoute = route.name;
  const targetRoute = item.to;
   
  if (currentRoute === targetRoute) {
    emitter.emit('cleanFilters', true)
  }

  billingsStores.cleanData()
}

const { width: windowWidth } = useWindowSize()
const { isVerticalNavMini, dynamicI18nProps } = useLayouts()
const hideTitleAndBadge = isVerticalNavMini(windowWidth)
</script>

<template>
  <li
    v-if="can(item.action, item.subject)"
    class="nav-link"
    :class="[{ disabled: item.disable }, item.class]"
  >
    <Component
      v-bind="getComputedNavLinkToProp(item)"
      :is="item.to ? 'RouterLink' : 'a'"
      :class="[
        isSettingsRoute ? 'nav-link-setting' : '',
        isSettingsRoute && isNavLinkActive(item, $router) ? 'nav-link-setting-active' : '',
      ]"
      :active-class="isSettingsRoute ? 'nav-link-setting-active' : undefined"
      :exact-active-class="isSettingsRoute ? 'nav-link-setting-active' : undefined"
      @click.prevent="handleClick(item)"
    >
      <Component
        :is="config.app.iconRenderer || 'div'"
        v-bind="item.icon || config.verticalNav.defaultNavItemIconProps"
        class="nav-item-icon"
        size="24"
      />
      <TransitionGroup name="transition-slide-x">
        <!-- 👉 Title -->
        <Component
          :is="config.app.enableI18n ? 'i18n-t' : 'span'"
          v-show="!hideTitleAndBadge"
          key="title"
          class="nav-item-title"
          v-bind="dynamicI18nProps(item.title, 'span')"
        >
          {{ item.title }}
        </Component>

        <!-- 👉 Badge -->
        <Component
          :is="config.app.enableI18n ? 'i18n-t' : 'span'"
          v-if="item.badgeContent"
          v-show="!hideTitleAndBadge"
          key="badge"
          class="nav-item-badge"
          :class="item.badgeClass"
          v-bind="dynamicI18nProps(item.badgeContent, 'span')"
        >
          {{ item.badgeContent }}
        </Component>
      </TransitionGroup>
    </Component>
  </li>
</template>

<style lang="scss">
.layout-vertical-nav {
  .nav-link a {
    display: flex;
    align-items: center;
  }

  .nav-link-setting {
    background: #FFFFFF;
    border-radius: 8px !important;
  }

  .nav-link-setting-active {
    background: #E7E7E7;
    border-radius: 8px !important;
  }
}
</style>
