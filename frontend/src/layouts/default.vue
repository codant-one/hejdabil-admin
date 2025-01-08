<script setup>
import { useSkins } from '@core/composable/useSkins'
import { useThemeConfig } from '@core/composable/useThemeConfig'

// @layouts plugin
import { AppContentLayoutNav } from '@layouts/enums'

import navItems from '@/navigation/vertical'
import axios from '@axios'
import { openGroups } from '@layouts/utils'

const menus = ref([])

const getNavItems = ()=>{
  navItems.forEach(element => {
    if ('children' in element){
      menus.value.push(element.title)        
      element.children.forEach(element2=>{
        if ('children' in element2){
          menus.value.push(element2.title)
        }
      })
    }
  })  
  axios.get('menu').then(resp=>{
    if(resp.data.menus)
      openGroups.value = resp.data.menus.split(',')
    else
      openGroups.value = []
  }).catch(error=>{
    openGroups.value = menus.value
    axios.post('menu/add',{ menus:menus.value.join(',') })
    
  })
}

getNavItems()

const DefaultLayoutWithHorizontalNav = defineAsyncComponent(() => import('./components/DefaultLayoutWithHorizontalNav.vue'))
const DefaultLayoutWithVerticalNav = defineAsyncComponent(() => import('./components/DefaultLayoutWithVerticalNav.vue'))
const { width: windowWidth } = useWindowSize()
const { appContentLayoutNav, switchToVerticalNavOnLtOverlayNavBreakpoint } = useThemeConfig()

// Remove below composable usage if you are not using horizontal nav layout in your app
switchToVerticalNavOnLtOverlayNavBreakpoint(windowWidth)

const { layoutAttrs, injectSkinClasses } = useSkins()

injectSkinClasses()
</script>

<template>
  <template v-if="appContentLayoutNav === AppContentLayoutNav.Vertical">
    <DefaultLayoutWithVerticalNav v-bind="layoutAttrs" />
  </template>
  <template v-else>
    <DefaultLayoutWithHorizontalNav v-bind="layoutAttrs" />
  </template>
</template>

<style lang="scss">
// As we are using `layouts` plugin we need its styles to be imported
@use "@layouts/styles/default-layout";
</style>
