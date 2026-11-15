const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')
const Vue = require('vue')
const { renderToString } = require('@vue/server-renderer')
const { createPinia, setActivePinia, defineStore } = require('pinia')
const { parse, compileScript, compileTemplate, compileStyleAsync } = require('@vue/compiler-sfc')

const filename = path.join(__dirname, '../src/pages/dashboard/admin/roles/create/index.vue')
const { descriptor, errors } = parse(readFileSync(filename, 'utf8'), { filename })
const script = descriptor.scriptSetup.content.replace(/^import .+$/gm, '')

function createHarness({ addRole = async () => {}, allPermissions = async () => ({ permissions: [] }), valid = true, superAdmin = false, userRoles } = {}) {
  const saved = []
  const navigations = []
  const store = {
    creationAlert: null,
    getRoles: [],
    last_page: 1,
    rolesTotalCount: 0,
    fetchRoles: async () => {},
    addRole: async data => {
      saved.push(JSON.parse(JSON.stringify(data)))
      return addRole(data)
    },
  }
  const mounted = []
  const beforeUnmount = []
  const listeners = new Map()
  const viewport = {
    innerHeight: 900,
    addEventListener: (event, listener) => listeners.set(event, listener),
    removeEventListener: (event, listener) => {
      if (listeners.get(event) === listener) listeners.delete(event)
    },
  }
  let guard
  const state = vm.runInNewContext(`${script}
    ;({ name, assignedPermissions, permissionSections, getSectionPermissions, getModuleRows,
        selectablePermissions, selectedCount, isSectionSelected, isSectionIndeterminate,
        toggleSection, onSubmitCreate, refFormCreate, permissionError, advisor,
        isRequestOngoing, isConfirmLeaveVisible, confirmLeave, mdAndDown,
        fetchData, permissionLabels, permissionsLoaded, sectionEl, snackbarLocation,
        permissionLoadError, totalPermissions, requiredValidator, windowWidth, modalWarningIcon: '' })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    useRouter: () => ({ push: route => navigations.push(route) }),
    useRolesStores: () => store,
    usePermissionsStores: () => ({ allPermissions }),
    useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
    useWindowSize: () => ({ width: Vue.ref(1280) }),
    onMounted: callback => mounted.push(callback),
    onBeforeUnmount: callback => beforeUnmount.push(callback),
    onBeforeRouteLeave: callback => { guard = callback },
    requiredValidator: value => !!value?.trim() || 'krävs *',
    window: viewport,
    localStorage: {
      getItem: () => JSON.stringify({ roles: userRoles ?? [{ name: superAdmin ? 'SuperAdmin' : 'Administrator' }] }),
    },
  })

  state.refFormCreate.value = { validate: async () => ({ valid }) }
  state.permissionsLoaded.value = true

  return {
    state, saved, navigations, store, listeners, viewport,
    mount: () => Promise.all(mounted.map(callback => callback())),
    unmount: () => beforeUnmount.forEach(callback => callback()),
    guard: to => guard(to),
  }
}

async function render(state, sectionCheckboxes = [], permissionCheckboxes = []) {
  const app = Vue.createSSRApp({ setup: () => state, render: Vue.compile(descriptor.template.content) })
  const passthrough = { render() { return Vue.h('div', null, this.$slots.default?.()) } }
  for (const component of ['LoadingOverlay', 'Toaster', 'VSnackbar', 'VForm', 'VCard', 'VBtn', 'VIcon', 'VDivider', 'VTextField', 'VDialog', 'VCardText', 'VAlert', 'VSpacer', 'VLabel'])
    app.component(component, passthrough)
  app.component('VCheckbox', {
    props: ['modelValue', 'indeterminate', 'disabled', 'label', 'value'],
    emits: ['update:modelValue'],
    setup(props, { emit, attrs }) {
      const checkboxes = Array.isArray(props.modelValue) ? permissionCheckboxes : sectionCheckboxes
      checkboxes.push({
        ...props,
        setChecked: value => emit('update:modelValue', value),
      })

      return () => Vue.h('div', [
        Vue.h('input', {
          ...attrs,
          type: 'checkbox',
          value: props.value,
          checked: Array.isArray(props.modelValue) ? props.modelValue.includes(props.value) : props.modelValue,
          disabled: props.disabled,
        }),
        Vue.h('label', props.label),
      ])
    },
  })
  app.config.globalProperties.$vuetify = { display: { mdAndDown: state.mdAndDown.value } }

  return renderToString(app)
}

test('role creation script, template and scoped SCSS compile', async () => {
  assert.deepEqual(errors, [])
  assert.ok(compileScript(descriptor, { id: 'role-create' }).content)
  assert.deepEqual(compileTemplate({
    source: descriptor.template.content,
    filename,
    id: 'role-create',
  }).errors, [])
  assert.deepEqual((await compileStyleAsync({
    source: descriptor.styles[0].content,
    filename,
    id: 'data-v-role-create',
    scoped: true,
    preprocessLang: 'scss',
  })).errors, [])
})

test('permission catalog uses supported permissions and current help modules', () => {
  const { state } = createHarness({ superAdmin: true })
  const actual = state.permissionSections.flatMap(state.getSectionPermissions).map(item => item.value)
  const backend = readFileSync(path.join(__dirname, '../../backend/database/seeders/PermissionSeeder.php'), 'utf8')
  const supported = new Set([...backend.matchAll(/'name' => '([^']+)'/g)].map(match => match[1]))
  const expectedHelp = [...supported].filter(value => / help-(center|categories|videos)$/.test(value))

  assert.deepEqual([...actual].filter(value => / help-(center|categories|videos)$/.test(value)).sort(), expectedHelp.sort())
  assert.equal(new Set(actual).size, actual.length)
  assert.ok(actual.every(value => supported.has(value)))
})

test('permission labels are requested from the database and rendered without replacing permission names', async () => {
  let permissions
  let requestedParams
  const { state, saved } = createHarness({
    allPermissions: async params => {
      requestedParams = params
      return { permissions }
    },
  })
  permissions = state.permissionSections.flatMap(state.getSectionPermissions)
    .map(permission => ({ name: permission.value, label: `Databas: ${permission.value}` }))
  permissions.find(permission => permission.name === 'create clients').label = 'Registrera en kund'
  permissions.find(permission => permission.name === 'delete clients').label = 'Radera en kund'
  await state.fetchData()

  assert.equal(requestedParams.with_labels, true)
  assert.equal(state.permissionsLoaded.value, true)
  assert.equal(state.isRequestOngoing.value, false)
  state.assignedPermissions.value = ['create clients', 'delete clients']

  const html = await render(state)

  assert.match(html, /aria-label="Registrera en kund"/)
  assert.match(html, /aria-label="Radera en kund"/)
  assert.match(html, /<label>Registrera en kund<\/label>/)
  assert.match(html, /<label>Radera en kund<\/label>/)
  state.name.value = 'Sales'
  await state.onSubmitCreate()
  assert.deepEqual(saved[0].permissions, ['create clients', 'delete clients'])
})

test('failed or incomplete permission labels block saving and can be retried', async () => {
  let fail = true
  let permissions
  const { state, saved } = createHarness({
    allPermissions: async () => {
      if (fail) throw { message: 'Unavailable' }
      return { permissions }
    },
  })
  state.name.value = 'Sales'
  state.assignedPermissions.value = ['view clients']
  await state.fetchData()
  await state.onSubmitCreate()
  assert.equal(saved.length, 0)
  assert.equal(state.permissionsLoaded.value, false)
  assert.ok(state.permissionLoadError.value)
  assert.equal(state.isRequestOngoing.value, false)

  fail = false
  permissions = state.permissionSections.flatMap(state.getSectionPermissions)
    .map(permission => ({ name: permission.value, label: null }))
  await state.fetchData()
  assert.equal(state.permissionsLoaded.value, false)
  assert.match(state.permissionLoadError.value, /saknar etikett/)

  permissions.forEach(permission => { permission.label = `Databas: ${permission.name}` })
  await state.fetchData()
  assert.equal(state.permissionsLoaded.value, true)
  assert.equal(state.permissionLoadError.value, '')
  assert.equal(state.name.value, 'Sales')
  assert.deepEqual([...state.assignedPermissions.value], ['view clients'])
})

test('mounting loads permissions with the shared busy state and cleans up viewport resizing', async () => {
  let resolvePermissions
  let requests = 0
  const response = new Promise(resolve => { resolvePermissions = resolve })
  const { state, mount, unmount, listeners, viewport, guard, saved } = createHarness({
    allPermissions: () => {
      requests++
      return response
    },
  })
  state.sectionEl.value = { style: {}, getBoundingClientRect: () => ({ top: 100 }) }
  const mounting = mount()

  assert.equal(state.isRequestOngoing.value, true)
  assert.equal(state.permissionsLoaded.value, false)
  assert.equal(state.sectionEl.value.style.minHeight, '775px')
  assert.equal(guard({ fullPath: '/dashboard/admin/roles' }), false)
  await state.fetchData()
  await state.onSubmitCreate()
  assert.equal(requests, 1)
  assert.equal(saved.length, 0)

  const permissions = state.permissionSections.flatMap(state.getSectionPermissions)
    .map(permission => ({ name: permission.value, label: `Databas: ${permission.value}` }))
  resolvePermissions({ permissions })
  await mounting

  assert.equal(state.isRequestOngoing.value, false)
  assert.equal(state.permissionsLoaded.value, true)
  assert.equal(state.snackbarLocation.value, 'top end')
  state.mdAndDown.value = true
  assert.equal(state.snackbarLocation.value, '')

  viewport.innerHeight = 700
  listeners.get('resize')()
  assert.equal(state.sectionEl.value.style.minHeight, '575px')
  unmount()
  assert.equal(listeners.has('resize'), false)
})

test('section selection supports partial state without duplicates or restricted permissions', () => {
  const { state } = createHarness()
  const section = state.permissionSections[0]
  state.assignedPermissions.value = ['view clients', 'view roles']

  assert.equal(state.isSectionIndeterminate(section), true)
  state.toggleSection(section, true)
  state.toggleSection(section, true)
  assert.equal(state.isSectionSelected(section), true)
  assert.equal(state.isSectionIndeterminate(section), false)
  assert.equal(state.selectedCount(section), state.selectablePermissions(section).length)
  assert.equal(state.assignedPermissions.value.includes('delete roles'), false)
  assert.equal(new Set(state.assignedPermissions.value).size, state.assignedPermissions.value.length)

  state.toggleSection(section, false)
  assert.deepEqual([...state.assignedPermissions.value], ['view clients'])
  const admin = createHarness({ superAdmin: true }).state
  admin.toggleSection(admin.permissionSections[0], true)
  assert.equal(admin.assignedPermissions.value.includes('delete roles'), true)
})

test('section VCheckbox preserves partial state, toggles the section and disables while busy', async () => {
  const { state } = createHarness()
  state.assignedPermissions.value = ['view roles']
  const partial = []
  await render(state, partial)
  assert.equal(partial[0].modelValue, false)
  assert.equal(partial[0].indeterminate, true)
  partial[0].setChecked(true)
  assert.equal(state.isSectionSelected(state.permissionSections[0]), true)
  assert.equal(state.assignedPermissions.value.includes('delete roles'), false)

  const checked = []
  await render(state, checked)
  assert.equal(checked[0].modelValue, true)
  assert.equal(checked[0].indeterminate, false)
  checked[0].setChecked(false)
  assert.equal(state.assignedPermissions.value.length, 0)

  state.isRequestOngoing.value = true
  const busy = []
  await render(state, busy)
  assert.ok(busy.every(checkbox => checkbox.disabled))
})

test('individual VCheckbox updates selected permissions, clears validation and disables while busy', async () => {
  const { state } = createHarness()
  state.permissionError.value = 'Välj minst en behörighet.'
  const permissions = []
  await render(state, [], permissions)
  const clients = permissions.find(checkbox => checkbox.value === 'view clients')
  assert.deepEqual([...clients.modelValue], [])
  assert.equal(clients.disabled, false)
  clients.setChecked(['view clients'])
  assert.deepEqual([...state.assignedPermissions.value], ['view clients'])
  assert.equal(state.permissionError.value, '')
  assert.equal(state.isSectionIndeterminate(state.permissionSections[1]), true)

  state.isRequestOngoing.value = true
  const busy = []
  await render(state, [], busy)
  assert.ok(busy.length > 0)
  assert.ok(busy.every(checkbox => checkbox.disabled))
})

test('delete roles preserves the legacy primary-role restriction in the checkbox and bulk selection', async () => {
  for (const { roles, allowed } of [
    { roles: [{ name: 'SuperAdmin' }], allowed: true },
    { roles: [{ name: 'Administrator' }], allowed: false },
    { roles: [{ name: 'Administrator' }, { name: 'SuperAdmin' }], allowed: false },
    { roles: [], allowed: false },
  ]) {
    const { state, saved } = createHarness({ userRoles: roles })
    const permissions = state.permissionSections.flatMap(state.getSectionPermissions)
    state.permissionLabels.value = new Map(permissions.map(permission => [permission.value, `Databas: ${permission.value}`]))
    const disabled = permissions.filter(permission => permission.disabled).map(permission => permission.value)
    assert.deepEqual([...disabled], allowed ? [] : ['delete roles'])

    const html = await render(state)
    const checkbox = html.match(/<input\b[^>]*value="delete roles"[^>]*>/)?.[0]
    assert.ok(checkbox)
    assert.equal(/\sdisabled(?:\s|>|=)/.test(checkbox), !allowed)

    state.toggleSection(state.permissionSections[0], true)
    assert.equal(state.assignedPermissions.value.includes('delete roles'), allowed)
    state.name.value = 'Sales'
    await state.onSubmitCreate()
    assert.equal(saved[0].permissions.includes('delete roles'), allowed)
  }
})

test('submission sends only trimmed name and selected permissions then returns to roles', async () => {
  const { state, saved, navigations, store, guard } = createHarness()
  state.name.value = '  Sales  '
  state.assignedPermissions.value = ['view clients', 'create stock']

  await state.onSubmitCreate()

  assert.deepEqual(saved, [{ name: 'Sales', permissions: ['view clients', 'create stock'] }])
  assert.equal(navigations[0].name, 'dashboard-admin-roles')
  assert.equal(store.creationAlert.type, 'success')
  assert.equal(store.creationAlert.message, 'Roll skapad!')
  assert.equal(state.isRequestOngoing.value, false)
  assert.equal(guard({ fullPath: '/dashboard/admin/roles' }), true)
})

test('role index displays the creation alert after loading and consumes it only once', async () => {
  const { state, store } = createHarness()
  state.name.value = 'Sales'
  state.assignedPermissions.value = ['view clients']
  await state.onSubmitCreate()

  const indexFilename = path.join(__dirname, '../src/pages/dashboard/admin/roles/index.vue')
  const indexScript = parse(readFileSync(indexFilename, 'utf8')).descriptor.scriptSetup.content.replace(/^import .+$/gm, '')
  const createIndex = () => vm.runInNewContext(`${indexScript}\n;({ advisor, fetchData, isRequestOngoing })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    watchEffect: () => {},
    onMounted: () => {},
    onBeforeUnmount: () => {},
    useWindowSize: () => ({ width: Vue.ref(1280) }),
    useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
    useRolesStores: () => store,
    localStorage: { getItem: () => null },
  })
  const index = createIndex()
  let resolveList
  store.fetchRoles = () => new Promise(resolve => { resolveList = resolve })
  const loading = index.fetchData()
  assert.equal(index.isRequestOngoing.value, true)
  assert.equal(index.advisor.value.show, false)
  assert.ok(store.creationAlert)

  resolveList()
  await loading
  assert.equal(index.isRequestOngoing.value, false)
  assert.equal(index.advisor.value.show, true)
  assert.equal(index.advisor.value.type, 'success')
  assert.equal(index.advisor.value.message, 'Roll skapad!')
  assert.equal(store.creationAlert, null)

  store.fetchRoles = async () => {}
  index.advisor.value.show = false
  await index.fetchData()
  assert.equal(index.advisor.value.show, false)
  const revisited = createIndex()
  await revisited.fetchData()
  assert.equal(revisited.advisor.value.show, false)
})

test('invalid name or empty permissions prevent saving', async () => {
  const invalid = createHarness({ valid: false })
  invalid.state.assignedPermissions.value = ['view clients']
  await invalid.state.onSubmitCreate()
  assert.equal(invalid.saved.length, 0)

  const empty = createHarness()
  empty.state.name.value = 'Sales'
  await empty.state.onSubmitCreate()
  assert.equal(empty.saved.length, 0)
  assert.ok(empty.state.permissionError.value)
  assert.equal(empty.state.isRequestOngoing.value, false)
})

test('API failures keep user input and show validation or server errors', async () => {
  for (const error of [
    { feedback: 'params_validation_failed', message: { name: ['Namnet används redan.'] } },
    new Error('Network failed'),
  ]) {
    const { state, navigations, store } = createHarness({ addRole: async () => { throw error } })
    state.name.value = 'Sales'
    state.assignedPermissions.value = ['view clients']

    await state.onSubmitCreate()

    assert.equal(state.name.value, 'Sales')
    assert.deepEqual([...state.assignedPermissions.value], ['view clients'])
    assert.equal(state.advisor.value.show, true)
    assert.equal(state.advisor.value.message, error.feedback ? 'Namnet används redan.' : 'Ett serverfel uppstod. Försök igen.')
    assert.equal(state.isRequestOngoing.value, false)
    assert.equal(navigations.length, 0)
    assert.equal(store.creationAlert, null)
  }
})

test('saving blocks duplicate submissions and navigation, while dirty cancellation asks for confirmation', async () => {
  let resolveSave
  const response = new Promise(resolve => { resolveSave = resolve })
  const { state, saved, navigations, guard } = createHarness({ addRole: () => response })
  const destination = { fullPath: '/dashboard/admin/roles' }
  assert.equal(guard(destination), true)

  state.name.value = 'Sales'
  state.assignedPermissions.value = ['view clients']
  assert.equal(guard(destination), false)
  assert.equal(state.isConfirmLeaveVisible.value, true)

  state.isConfirmLeaveVisible.value = false
  const submitting = state.onSubmitCreate()
  await Vue.nextTick()
  await state.onSubmitCreate()
  assert.equal(saved.length, 1)
  assert.equal(guard(destination), false)
  assert.equal(state.isConfirmLeaveVisible.value, false)

  resolveSave()
  await submitting
  assert.equal(navigations.length, 1)

  const cancelled = createHarness()
  cancelled.state.name.value = 'Unsaved'
  cancelled.guard(destination)
  cancelled.state.confirmLeave()
  assert.equal(cancelled.navigations[0], destination.fullPath)
})

test('role store can create from a direct visit without fetching the role list first', async () => {
  const source = readFileSync(path.join(__dirname, '../src/stores/useRoles.js'), 'utf8')
  const role = { id: 8, name: 'Sales' }
  const useRolesStores = vm.runInNewContext(`${source.replace(/^import .+$/gm, '').replace('export const ', 'const ')}\n;useRolesStores`, {
    defineStore,
    Roles: { create: async () => ({ data: { data: { role } } }) },
  })
  setActivePinia(createPinia())
  const store = useRolesStores()

  await store.addRole({ name: 'Sales', permissions: ['view clients'] })

  assert.equal(store.roles[0].id, role.id)
  assert.equal(store.loading, false)
})

test('permission store forwards label query and clears loading after success or failure', async () => {
  const source = readFileSync(path.join(__dirname, '../src/stores/usePermissions.js'), 'utf8')
  let fail = false
  let requestedParams
  const usePermissionsStores = vm.runInNewContext(`${source.replace(/^import .+$/gm, '').replace('export const ', 'const ')}\n;usePermissionsStores`, {
    defineStore,
    Permissions: {
      all: async params => {
        requestedParams = params
        if (fail) throw new Error('Unavailable')
        return { data: { data: { permissions: [{ name: 'view roles', label: 'Etikett' }] } } }
      },
    },
  })
  setActivePinia(createPinia())
  const store = usePermissionsStores()
  const result = await store.allPermissions({ with_labels: true })

  assert.equal(requestedParams.with_labels, true)
  assert.equal(result.permissions[0].label, 'Etikett')
  assert.equal(store.loading, false)
  fail = true
  await assert.rejects(store.allPermissions({ with_labels: true }), /Unavailable/)
  assert.equal(store.loading, false)
})
