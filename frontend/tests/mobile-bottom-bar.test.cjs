const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')
const Vue = require('vue')
const { parse, compileStyle } = require('@vue/compiler-sfc')

const filename = path.join(__dirname, '../src/layouts/components/MobileBottomBar.vue')
const source = readFileSync(filename, 'utf8')
const { descriptor } = parse(source, { filename })
const groupState = descriptor.scriptSetup.content.match(/const openGroups = ref\(\{\}\)[\s\S]*?(?=\nconst props = defineProps)/)[0]
const activeGroupWatcher = descriptor.scriptSetup.content.match(/watch\(\[\(\) => route\.path[\s\S]*?\}, \{ immediate: true \}\)/)[0]
const adminSource = readFileSync(path.join(__dirname, '../src/navigation/vertical/admin.js'), 'utf8')
const adminItems = vm.runInNewContext(adminSource.replace('export default', ''))
const mainSource = readFileSync(path.join(__dirname, '../src/navigation/vertical/main.js'), 'utf8')
const mainItems = vm.runInNewContext(mainSource.replace('export default', ''))
const utilsSource = readFileSync(path.join(__dirname, '../src/@layouts/utils.js'), 'utf8')
const activeUtils = vm.runInNewContext(`${utilsSource.slice(utilsSource.indexOf('export const resolveNavLinkRouteName'), utilsSource.indexOf('/**\n * Convert Hex')).replaceAll('export const ', 'const ')}\n;({ isNavGroupActive })`)

function createHarness(denied = [], initialRoute = '', navItems = adminItems) {
  const can = (action, subject) => !denied.includes(subject)
  const state = vm.runInNewContext(`${groupState}\n;({ openGroups, openChildGroups, toggleGroup, toggleChildGroup })`, { ref: Vue.ref })
  const route = Vue.reactive({ path: initialRoute })
  const router = { currentRoute: Vue.computed(() => ({ matched: [{ name: route.path, meta: {} }] })) }
  const props = { navItems }
  vm.runInNewContext(activeGroupWatcher, { ...state, route, router, props, watch: Vue.watch, isNavGroupActive: activeUtils.isNavGroupActive })
  const showMenu = Vue.ref(true)
  const renderer = Vue.createRenderer({
    createElement: type => ({ type, children: [], props: {}, style: {} }),
    createText: text => ({ text }),
    createComment: text => ({ text }),
    setText: (node, text) => { node.text = text },
    setElementText: (node, text) => { node.text = text; node.children = [] },
    parentNode: node => node.parent,
    nextSibling: () => null,
    insert: (node, parent, anchor) => {
      if (node.parent) {
        const index = node.parent.children.indexOf(node)
        if (index !== -1)
          node.parent.children.splice(index, 1)
      }
      node.parent = parent
      const index = anchor ? parent.children.indexOf(anchor) : -1
      parent.children.splice(index < 0 ? parent.children.length : index, 0, node)
    },
    remove: node => {
      const index = node.parent?.children.indexOf(node)
      if (index >= 0)
        node.parent.children.splice(index, 1)
    },
    patchProp: (node, key, previous, value) => { node.props[key] = value },
  })
  const passthrough = { render() { return Vue.h('div', null, this.$slots.default?.()) } }
  const app = renderer.createApp({
    setup() {
      return {
        ...state,
        showMenu,
        props,
        width: 375,
        MOBILE_BREAKPOINT: 1024,
        themeConfig: { app: {}, icons: { chevronRight: {} }, verticalNav: { defaultNavItemIconProps: {} } },
        can,
        canWithPlan: () => false,
        canShowSwishaButton: false,
        canViewNavMenuGroup: item => can(item.action, item.subject) && item.children.some(child => can(child.action, child.subject)),
        getComputedNavLinkToProp: () => ({}),
        isNavLinkActive: () => false,
        redirectTo: () => {},
        redirectToPayoutsAndOpenDialog: () => {},
      }
    },
    render: Vue.compile(descriptor.template.content),
  })
  for (const component of ['VBottomNavigation', 'VBtn', 'VIcon', 'VDialog', 'VCard', 'RouterLink'])
    app.component(component, passthrough)
  app.config.globalProperties.$router = {}
  const root = { children: [] }
  app.mount(root)

  function find(predicate, node = root) {
    if (predicate(node))
      return node
    for (const child of node.children || []) {
      const result = find(predicate, child)
      if (result)
        return result
    }
  }
  function text(node) {
    return (node.text || '') + (node.children || []).map(text).join('')
  }
  function group(title) {
    return find(node => node.props?.role === 'button' && text(node).includes(title))
  }
  function click(node) {
    assert.ok(node)
    node.props.onClick({ stopPropagation() {}, preventDefault() {} })
  }
  return { root, find, group, click, state, showMenu, route }
}

test('nested groups open independently and collapse with their parent', async () => {
  const h = createHarness()
  h.click(h.group('Administration'))
  await Vue.nextTick()
  assert.equal(h.group('Attribut').props['aria-expanded'], false)
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], false)
  const submenus = []
  function collectSubmenus(node) {
    if (node.props?.class?.includes('nav-subchildren'))
      submenus.push(node)
    for (const child of node.children || [])
      collectSubmenus(child)
  }
  collectSubmenus(h.root)
  assert.equal(submenus.length, 2)
  assert.equal(submenus[0].style.display, 'none')
  assert.equal(submenus[1].style.display, 'none')
  h.click(h.group('Attribut'))
  await Vue.nextTick()
  assert.equal(h.group('Attribut').props['aria-expanded'], true)
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], false)
  assert.notEqual(submenus[0].style.display, 'none')
  assert.equal(submenus[1].style.display, 'none')
  assert.ok(h.find(node => node.props?.class?.includes('nav-item-icon'), submenus[0]))
  assert.equal(h.showMenu.value, true)
  h.click(h.group('Hjälpcenter'))
  await Vue.nextTick()
  assert.equal(h.group('Attribut').props['aria-expanded'], true)
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], true)
  h.click(h.group('Attribut'))
  await Vue.nextTick()
  assert.equal(submenus[0].style.display, 'none')
  assert.notEqual(submenus[1].style.display, 'none')
  h.click(h.group('Administration'))
  h.click(h.group('Administration'))
  await Vue.nextTick()
  assert.equal(h.group('Attribut').props['aria-expanded'], false)
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], false)
})

test('nested groups respect permissions and leaf links close the menu', async () => {
  const restricted = createHarness(['help-categories'])
  assert.equal(restricted.group('Hjälpcenter'), undefined)

  const h = createHarness()
  h.click(h.group('Administration'))
  h.click(h.group('Hjälpcenter'))
  await Vue.nextTick()
  const videoLink = h.find(node => node.type === 'div' && node.props?.onClick && (node.children || []).some(child => child.text === 'Videor'))
  h.click(videoLink)
  await Vue.nextTick()
  assert.equal(h.showMenu.value, false)
})

test('active mobile groups open on initial load and route changes but remain manually collapsible', async () => {
  const h = createHarness([], 'dashboard-admin-help-videos')
  assert.equal(h.group('Administration').props['aria-expanded'], true)
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], true)
  assert.equal(h.group('Attribut').props['aria-expanded'], false)

  h.click(h.group('Hjälpcenter'))
  await Vue.nextTick()
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], false)

  h.route.path = 'dashboard-admin-invoices'
  await Vue.nextTick()
  assert.equal(h.group('Administration').props['aria-expanded'], true)
  assert.equal(h.group('Attribut').props['aria-expanded'], true)
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], false)

  h.route.path = 'dashboard-admin-currencies'
  await Vue.nextTick()
  assert.equal(h.group('Administration').props['aria-expanded'], true)
  assert.equal(h.group('Attribut').props['aria-expanded'], false)

  h.route.path = 'dashboard-panel'
  await Vue.nextTick()
  assert.equal(h.group('Administration').props['aria-expanded'], false)

  h.route.path = 'dashboard-admin-invoices'
  await Vue.nextTick()

  h.click(h.group('Administration'))
  await Vue.nextTick()
  assert.equal(h.group('Administration').props['aria-expanded'], false)
  h.route.path = 'dashboard-admin-help-categories'
  await Vue.nextTick()
  assert.equal(h.group('Administration').props['aria-expanded'], true)
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], true)
})

test('switching between top-level groups closes the previous mobile group', async () => {
  const inventory = mainItems.find(item => item.title === 'Mitt Fordonslager')
  const h = createHarness([], 'dashboard-admin-help-videos', [adminItems[0], inventory])
  assert.equal(h.group('Administration').props['aria-expanded'], true)
  assert.equal(h.group('Mitt Fordonslager').props['aria-expanded'], false)
  h.route.path = 'dashboard-admin-brands'
  await Vue.nextTick()
  assert.equal(h.group('Administration').props['aria-expanded'], false)
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], false)
  assert.equal(h.group('Mitt Fordonslager').props['aria-expanded'], true)
  h.route.path = 'dashboard-admin-help-videos'
  await Vue.nextTick()
  assert.equal(h.group('Administration').props['aria-expanded'], true)
  assert.equal(h.group('Hjälpcenter').props['aria-expanded'], true)
  assert.equal(h.group('Mitt Fordonslager').props['aria-expanded'], false)
})

test('desktop groups open for the current route and can still be manually closed', async () => {
  const desktopSource = readFileSync(path.join(__dirname, '../src/@layouts/components/VerticalNavGroup.vue'), 'utf8')
  const script = parse(desktopSource).descriptor.scriptSetup.content
  const stateCode = script.slice(script.indexOf('const isGroupActive ='), script.indexOf('</script>') === -1 ? undefined : script.indexOf('</script>'))
  const route = Vue.reactive({ path: 'dashboard-admin-help-videos' })
  const router = { currentRoute: Vue.computed(() => ({ matched: [{ name: route.path, meta: {} }] })) }
  const openGroups = Vue.ref([])
  const autoHideMenu = Vue.ref(false)
  const mini = Vue.ref(false)
  const makeGroup = item => vm.runInNewContext(`${stateCode}\n;({ isGroupOpen, isGroupActive })`, {
    ref: Vue.ref,
    watch: Vue.watch,
    props: { item },
    route,
    router,
    openGroups,
    autoHideMenu,
    isNavGroupActive: activeUtils.isNavGroupActive,
    isVerticalNavMini: () => mini,
    windowWidth: Vue.ref(1200),
    isVerticalNavHovered: Vue.ref(false),
  })
  const parent = makeGroup(adminItems[0])
  const child = makeGroup(adminItems[0].children[1])
  const otherChild = makeGroup(adminItems[0].children[0])
  const inventory = makeGroup(mainItems.find(item => item.title === 'Mitt Fordonslager'))
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, true)
  assert.equal(child.isGroupOpen.value, true)
  parent.isGroupOpen.value = false
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, false)
  assert.equal(child.isGroupOpen.value, false)
  route.path = 'dashboard-admin-help-categories'
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, true)
  assert.equal(child.isGroupOpen.value, true)
  route.path = 'dashboard-admin-invoices'
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, true)
  assert.equal(child.isGroupOpen.value, false)
  assert.equal(otherChild.isGroupOpen.value, true)
  route.path = 'dashboard-admin-currencies'
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, true)
  assert.equal(otherChild.isGroupOpen.value, false)
  route.path = 'dashboard-panel'
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, false)
  assert.equal(child.isGroupOpen.value, false)
  assert.equal(otherChild.isGroupOpen.value, false)

  route.path = 'dashboard-admin-brands'
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, false)
  assert.equal(inventory.isGroupOpen.value, true)
  route.path = 'dashboard-admin-help-categories'
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, true)
  assert.equal(child.isGroupOpen.value, true)
  assert.equal(inventory.isGroupOpen.value, false)

  autoHideMenu.value = true
  mini.value = true
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, false)
  route.path = 'dashboard-admin-invoices'
  await Vue.nextTick()
  autoHideMenu.value = false
  mini.value = false
  await Vue.nextTick()
  assert.equal(parent.isGroupOpen.value, true)
  assert.equal(child.isGroupOpen.value, false)
  assert.equal(otherChild.isGroupOpen.value, true)
})

test('active groups recognize routes using navActiveLink', () => {
  const router = {
    currentRoute: Vue.ref({
      matched: [{ name: 'video-detail', meta: { navActiveLink: 'dashboard-admin-help-videos' } }],
    }),
  }
  assert.equal(activeUtils.isNavGroupActive(adminItems[0].children, router), true)
  assert.equal(activeUtils.isNavGroupActive(adminItems[0].children[1].children, router), true)
  assert.equal(activeUtils.isNavGroupActive(adminItems[0].children[0].children, router), false)
})

test('nested icon styles match the scoped sheet and mobile navigation does not hide them', () => {
  const style = compileStyle({
    source: descriptor.styles[0].content,
    filename,
    id: 'data-v-test',
    scoped: true,
    preprocessLang: 'scss',
  })
  assert.deepEqual(style.errors, [])
  assert.match(style.code, /\.mobile-nav-sheet\[data-v-test\] \.nav-subchildren \.nav-item-icon/)
  assert.match(style.code, /\.mobile-nav-sheet\[data-v-test\] \.nav-group-label > \.nav-item-icon/)
  assert.match(style.code, /font-size: 1\.375rem !important/)
  assert.match(style.code, /\.nav-group-label > \.nav-item-icon\s*\{\s*color: #6e9383;/)

  const navStyles = readFileSync(path.join(__dirname, '../src/@core/scss/base/_vertical-nav.scss'), 'utf8')
  const [desktopStyles, mobileStyles] = navStyles.split('.mobile-nav-sheet {')
  assert.match(desktopStyles, /@extend %vertical-nav-items-icon-after-2nd-level/)
  assert.doesNotMatch(mobileStyles, /@extend %vertical-nav-items-icon-after-2nd-level/)
})
