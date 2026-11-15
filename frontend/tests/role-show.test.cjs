const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')
const Vue = require('vue')
const { renderToString } = require('@vue/server-renderer')
const { parse, compileScript, compileTemplate, compileStyleAsync } = require('@vue/compiler-sfc')

const filename = path.join(__dirname, '../src/pages/dashboard/admin/roles/[id].vue')
const { descriptor, errors } = parse(readFileSync(filename, 'utf8'), { filename })
const script = descriptor.scriptSetup.content.replace(/^import .+$/gm, '')

function createHarness({ showRole, allPermissions } = {}) {
  const requestedIds = []
  const writes = []
  const mounted = []
  const beforeUnmount = []
  const listeners = new Map()
  const route = Vue.reactive({ params: { id: '8' } })
  const store = {
    showRole: async id => {
      requestedIds.push(id)
      return showRole ? showRole(id) : {
        id: Number(id),
        name: `Sales ${id}`,
        permissions: [{ name: 'view clients' }, { name: 'delete roles' }, { name: 'view dashboard' }],
      }
    },
    updateRole: (...args) => writes.push(args),
    addRole: (...args) => writes.push(args),
  }
  let updateGuard
  const state = vm.runInNewContext(`${script}
    ;({ id, name, assignedPermissions, permissionSections, getSectionPermissions, getModuleRows,
        selectedCount, isSectionSelected, isSectionIndeterminate, isRequestOngoing,
        fetchData, permissionLabels, permissionsLoaded, sectionEl, windowWidth,
        permissionLoadError, totalPermissions, selectedPermissionsCount })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    watch: Vue.watch,
    useRoute: () => route,
    useRolesStores: () => store,
    usePermissionsStores: () => ({
      allPermissions: async params => allPermissions ? allPermissions(params) : ({
        permissions: state.permissionSections.flatMap(state.getSectionPermissions)
          .map(permission => ({ name: permission.value, label: `Databas: ${permission.value}` })),
      }),
    }),
    useWindowSize: () => ({ width: Vue.ref(1280) }),
    onMounted: callback => mounted.push(callback),
    onBeforeUnmount: callback => beforeUnmount.push(callback),
    onBeforeRouteUpdate: callback => { updateGuard = callback },
    window: {
      innerHeight: 900,
      addEventListener: (event, listener) => listeners.set(event, listener),
      removeEventListener: (event, listener) => {
        if (listeners.get(event) === listener) listeners.delete(event)
      },
    },
    localStorage: { getItem: () => JSON.stringify({ roles: [{ name: 'Administrator' }] }) },
  })

  return {
    state, store, requestedIds, writes, route, listeners,
    mount: () => Promise.all(mounted.map(callback => callback())),
    unmount: () => beforeUnmount.forEach(callback => callback()),
    updateGuard: to => updateGuard(to),
  }
}

async function render(state, checkboxes = [], textFields = []) {
  const app = Vue.createSSRApp({ setup: () => state, render: Vue.compile(descriptor.template.content) })
  const passthrough = { render() { return Vue.h('div', null, this.$slots.default?.()) } }
  for (const component of ['LoadingOverlay', 'Toaster', 'VCard', 'VBtn', 'VIcon', 'VDivider', 'VCardText', 'VAlert', 'VSpacer', 'VLabel'])
    app.component(component, passthrough)
  for (const [component, controls] of [['VCheckbox', checkboxes], ['VTextField', textFields]]) {
    app.component(component, {
      props: ['modelValue', 'readonly', 'indeterminate', 'disabled', 'label', 'value'],
      emits: ['update:modelValue'],
      setup(props, { emit }) {
        controls.push({
          ...props,
          emitValue: value => emit('update:modelValue', value),
        })
        return () => Vue.h('div', props.label)
      },
    })
  }
  app.config.globalProperties.$vuetify = { display: { mdAndDown: false } }

  return renderToString(app)
}

test('role details script, template and SCSS compile', async () => {
  assert.deepEqual(errors, [])
  assert.ok(compileScript(descriptor, { id: 'role-show' }).content)
  assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename, id: 'role-show' }).errors, [])
  assert.deepEqual((await compileStyleAsync({
    source: descriptor.styles[0].content,
    filename,
    id: 'role-show',
    preprocessLang: 'scss',
  })).errors, [])
})

test('details load by ID and every field and checkbox is readonly with no mutation binding', async () => {
  const { state, requestedIds, writes, mount, unmount, listeners } = createHarness()
  await mount()
  assert.deepEqual(requestedIds, ['8'])
  assert.equal(state.name.value, 'Sales 8')
  assert.equal(state.id.value, 8)
  assert.equal(state.permissionsLoaded.value, true)
  const checkboxes = []
  const textFields = []
  const html = await render(state, checkboxes, textFields)
  const sectionCheckboxes = checkboxes.filter(control => !Array.isArray(control.modelValue))
  const permissionCheckboxes = checkboxes.filter(control => Array.isArray(control.modelValue))
  assert.equal(sectionCheckboxes.length, state.permissionSections.length)
  assert.equal(permissionCheckboxes.length, state.totalPermissions)
  assert.equal(textFields.length, 1)
  assert.equal(textFields[0].modelValue, 'Sales 8')
  assert.ok([...checkboxes, ...textFields].every(control => control.readonly === ''))
  assert.match(html, /Detalj roll/)
  assert.match(html, /Databas: view clients/)
  assert.doesNotMatch(html, /<form|type="submit"/)

  const locked = permissionCheckboxes.find(control => control.value === 'delete roles')
  assert.equal(locked.disabled, true)
  assert.ok(locked.modelValue.includes('delete roles'))
  assert.equal(state.selectedCount(state.permissionSections[0]), 1)
  assert.equal(state.selectedPermissionsCount.value, 2)
  assert.equal(state.isSectionIndeterminate(state.permissionSections[0]), true)
  assert.equal(state.isSectionSelected(state.permissionSections[0]), false)

  checkboxes.forEach(control => control.emitValue(Array.isArray(control.modelValue) ? [] : true))
  textFields.forEach(control => control.emitValue('Changed'))
  await Vue.nextTick()
  assert.equal(state.id.value, 8)
  assert.equal(state.name.value, 'Sales 8')
  assert.deepEqual([...state.assignedPermissions.value], ['view clients', 'delete roles', 'view dashboard'])
  assert.deepEqual(writes, [])
  assert.equal(listeners.has('resize'), true)
  unmount()
  assert.equal(listeners.has('resize'), false)
})

test('role or permission loading errors remain visible, hide stale details and support retry', async () => {
  for (const failure of ['role', 'labels', 'incomplete']) {
    let fail = true
    let state
    const harness = createHarness({
      showRole: async () => {
        if (fail && failure === 'role')
          throw { feedback: 'not_found', message: 'Rollen hittades inte' }
        return { id: 8, name: 'Sales', permissions: [{ name: 'view clients' }] }
      },
      allPermissions: async params => {
        assert.equal(params.with_labels, true)
        if (fail && failure === 'labels') throw new Error('Unavailable')
        return {
          permissions: state.permissionSections.flatMap(state.getSectionPermissions)
            .map(permission => ({ name: permission.value, label: fail ? '' : `Databas: ${permission.value}` })),
        }
      },
    })
    state = harness.state
    await state.fetchData()
    assert.equal(state.permissionsLoaded.value, false)
    assert.equal(state.isRequestOngoing.value, false)
    const html = await render(state)
    assert.ok(html.includes(state.permissionLoadError.value))
    assert.match(html, /Försök igen/)
    assert.match(html, /Tillbaka/)
    assert.doesNotMatch(html, /permission-section-header/)
    fail = false
    await state.fetchData()
    assert.equal(state.permissionsLoaded.value, true)
    assert.equal(state.permissionLoadError.value, '')
    assert.equal(state.name.value, 'Sales')
    assert.deepEqual(harness.writes, [])
  }
})

test('loading prevents duplicate fetches and reusing the route loads the correct role', async () => {
  let resolveRole
  let first = true
  const response = new Promise(resolve => { resolveRole = resolve })
  const { state, requestedIds, route, updateGuard } = createHarness({
    showRole: id => first ? response : Promise.resolve({ id: Number(id), name: 'Another role', permissions: [] }),
  })
  const loading = state.fetchData()
  await state.fetchData()
  assert.deepEqual(requestedIds, ['8'])
  assert.equal(updateGuard({ params: { id: '9' } }), false)
  resolveRole({ id: 8, name: 'Sales', permissions: [{ name: 'view clients' }] })
  await loading
  assert.equal(updateGuard({ params: { id: '9' } }), true)
  first = false
  route.params.id = '9'
  await Vue.nextTick()
  await new Promise(setImmediate)
  assert.deepEqual(requestedIds, ['8', '9'])
  assert.equal(state.id.value, 9)
  assert.equal(state.name.value, 'Another role')
  assert.equal(state.assignedPermissions.value.length, 0)
  assert.equal(state.selectedPermissionsCount.value, 0)
})

test('index details actions navigate to the new read-only page for desktop and mobile', () => {
  const source = parse(readFileSync(path.join(__dirname, '../src/pages/dashboard/admin/roles/index.vue'), 'utf8')).descriptor
  const navigations = []
  const index = vm.runInNewContext(`${source.scriptSetup.content.replace(/^import .+$/gm, '')}
    ;({ showRole })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    watchEffect: () => {},
    onMounted: () => {},
    onBeforeUnmount: () => {},
    useWindowSize: () => ({ width: Vue.ref(375) }),
    useDisplay: () => ({ mdAndDown: Vue.ref(true) }),
    useRolesStores: () => ({}),
    router: { push: destination => navigations.push(destination) },
    localStorage: { getItem: () => null },
  })
  index.showRole({ id: 8 })
  assert.equal(navigations[0].name, 'dashboard-admin-roles-id')
  assert.equal(navigations[0].params.id, 8)
  assert.match(source.template.content, /@click="showRole\(rol\)"/)
  assert.match(source.template.content, /@click="showRole\(selectedRol\); isMobileActionDialogVisible = false;"/)
})
