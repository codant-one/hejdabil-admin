<script setup>
import {
  injectionKeyIsVerticalNavHovered,
  useLayouts,
} from '@layouts'
import {
  TransitionExpand,
  VerticalNavLink,
} from '@layouts/components'
import { config } from '@layouts/config'
import { canViewNavMenuGroup } from '@layouts/plugins/casl'
import {
  isNavGroupActive,
  openGroups,
} from '@layouts/utils'

const props = defineProps({
  item: {
    type: null,
    required: true,
  },
})

defineOptions({ name: 'VerticalNavGroup' })

const route = useRoute()
const router = useRouter()
const { width: windowWidth } = useWindowSize()
const { isVerticalNavMini, dynamicI18nProps } = useLayouts()
const hideTitleAndBadge = isVerticalNavMini(windowWidth)
const isVerticalNavHovered = inject(injectionKeyIsVerticalNavHovered, ref(false))
const autoHideMenu = isVerticalNavMini(windowWidth, isVerticalNavHovered)

// })
const isGroupActive = ref(false)
const isGroupOpen = ref(false)

const isAnyChildOpen = children => {
  return children.some(child => {
    let result = openGroups.value.includes(child.title)
    if ('children' in child)
      result = isAnyChildOpen(child.children) || result
    
    return result
  })
}

const collapseChildren = children => {
  children.forEach(child => {
    if ('children' in child)
      collapseChildren(child.children)
    openGroups.value = openGroups.value.filter(group => group !== child.title)
  })
}

watch(() => route.path, () => {
  const isActive = isNavGroupActive(props.item.children, router)

  // Don't open group if vertical nav is collapsed and window size is more than overlay nav breakpoint
  if(autoHideMenu.value)
    isGroupOpen.value = false
  else
    isGroupOpen.value = openGroups.value.includes(props.item.title)
  isGroupActive.value = isActive
}, { immediate: true })
watch(isGroupOpen, val => {

  // Find group index for adding/removing group from openGroups array
  const grpIndex = openGroups.value.indexOf(props.item.title)

  // // If group is opened => Add it to `openGroups` array
  if (val && grpIndex === -1) {
    openGroups.value.push(props.item.title)
    axios.post('menu/update',{ menus: openGroups.value.join(',') })
    if(autoHideMenu.value){
      isGroupOpen.value = false
    }
  } else if (!val && grpIndex !== -1 && !autoHideMenu.value) {
    openGroups.value.splice(grpIndex, 1)
    collapseChildren(props.item.children)
    axios.post('menu/update',{ menus: openGroups.value.join(',') })
  }
}, { immediate: true })
watch(openGroups, val => {
  if(autoHideMenu.value)
    isGroupOpen.value = false
  else
    isGroupOpen.value = openGroups.value.includes(props.item.title)
}, { deep: true })

// ℹ️ Previously instead of below watcher we were using two individual watcher for `isVerticalNavHovered`, `isVerticalNavCollapsed` & `isLessThanOverlayNavBreakpoint`
watch(isVerticalNavMini(windowWidth, isVerticalNavHovered), val => {
  isGroupOpen.value = val ? false : isGroupActive.value
})
</script>

<template>
  <li
    v-if="canViewNavMenuGroup(item)"
    class="nav-group"
    :class="[
      {
        active: isGroupActive,
        open: isGroupOpen,
        disabled: item.disable,
      },
    ]"
  >
    <div
      class="nav-group-label"
      @click="isGroupOpen = !isGroupOpen"
    >
      <Component
        :is="config.app.iconRenderer || 'div'"
        v-bind="item.icon || config.verticalNav.defaultNavItemIconProps"
        class="nav-item-icon"
      />
      <TransitionGroup name="transition-slide-x">
        <!-- 👉 Title -->
        <Component
          :is=" config.app.enableI18n ? 'i18n-t' : 'span'"
          v-bind="dynamicI18nProps(item.title, 'span')"
          v-show="!hideTitleAndBadge"
          key="title"
          class="nav-item-title"
        >
          {{ item.title }}
        </Component>

        <!-- 👉 Badge -->
        <Component
          :is="config.app.enableI18n ? 'i18n-t' : 'span'"
          v-bind="dynamicI18nProps(item.badgeContent, 'span')"
          v-show="!hideTitleAndBadge"
          v-if="item.badgeContent"
          key="badge"
          class="nav-item-badge"
          :class="item.badgeClass"
        >
          {{ item.badgeContent }}
        </Component>
        <Component
          :is="config.app.iconRenderer || 'div'"
          v-show="!hideTitleAndBadge"
          v-bind="config.icons.chevronRight"
          key="arrow"
          class="nav-group-arrow"
        />
      </TransitionGroup>
    </div>
    <TransitionExpand>
      <ul
        v-show="isGroupOpen"
        class="nav-group-children"
      >
        <Component
          :is="'children' in child ? 'VerticalNavGroup' : VerticalNavLink"
          v-for="child in item.children"
          :key="child.title"
          :item="child"
        />
      </ul>
    </TransitionExpand>
  </li>
</template>

<style lang="scss">
.layout-vertical-nav {
  .nav-group {
    &-label {
      display: flex;
      align-items: center;
      cursor: pointer;
    }
  }
}
</style>
