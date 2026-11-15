const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')
const Vue = require('vue')
const { renderToString } = require('@vue/server-renderer')
const { createPinia, setActivePinia, defineStore } = require('pinia')
const { parse, compileScript, compileTemplate, compileStyleAsync } = require('@vue/compiler-sfc')

const filename = path.join(__dirname, '../src/pages/dashboard/admin/roles/edit/[id].vue')
const { descriptor, errors } = parse(readFileSync(filename, 'utf8'), { filename })
const script = descriptor.scriptSetup.content.replace(/^import .+$/gm, '')

function createHarness({ showRole, allPermissions, updateRole = async () => {}, valid = true } = {}) {
  const saved = []
  const navigations = []
  const requestedIds = []
  const route = Vue.reactive({ params: { id: '8' } })
  const store = {
    creationAlert: null,
    updateAlert: null,
    getRoles: [],
    last_page: 1,
    rolesTotalCount: 0,
    fetchRoles: async () => {},
    showRole: async id => {
      requestedIds.push(id)
      return showRole ? showRole(id) : {
        id: Number(id),
        name: 'Sales',
        permissions: [{ name: 'view clients' }, { name: 'delete roles' }, { name: 'view dashboard' }],
      }
    },
    updateRole: async (data, id) => {
      saved.push({ id, ...JSON.parse(JSON.stringify(data)) })
      return updateRole(data, id)
    },
  }
  const mounted = []
  let leaveGuard
  let updateGuard
  const state = vm.runInNewContext(`${script}
    ;({ name, assignedPermissions, permissionSections, getSectionPermissions, getModuleRows,
        selectablePermissions, selectedCount, isSectionSelected, isSectionIndeterminate,
        toggleSection, onSubmitEdit, refFormEdit, permissionError, advisor,
        isRequestOngoing, isConfirmLeaveVisible, confirmLeave, mdAndDown, isFormEdited,
        fetchData, permissionLabels, permissionsLoaded, sectionEl, snackbarLocation,
        permissionLoadError, totalPermissions, selectedPermissionsCount, requiredValidator, windowWidth, modalWarningIcon: '' })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    watch: Vue.watch,
    useRouter: () => ({ push: destination => navigations.push(destination) }),
    useRoute: () => route,
    useRolesStores: () => store,
    usePermissionsStores: () => ({
      allPermissions: async params => allPermissions ? allPermissions(params) : ({
        permissions: state.permissionSections.flatMap(state.getSectionPermissions)
          .map(permission => ({ name: permission.value, label: `Databas: ${permission.value}` })),
      }),
    }),
    useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
    useWindowSize: () => ({ width: Vue.ref(1280) }),
    onMounted: callback => mounted.push(callback),
    onBeforeUnmount: () => {},
    onBeforeRouteLeave: callback => { leaveGuard = callback },
    onBeforeRouteUpdate: callback => { updateGuard = callback },
    requiredValidator: value => !!value?.trim() || 'krävs *',
    window: { addEventListener: () => {}, removeEventListener: () => {} },
    localStorage: { getItem: () => JSON.stringify({ roles: [{ name: 'Administrator' }] }) },
  })

  state.refFormEdit.value = { validate: async () => ({ valid }) }

  return {
    state, saved, navigations, store, requestedIds, route,
    mount: () => Promise.all(mounted.map(callback => callback())),
    guard: to => leaveGuard(to),
    updateGuard: to => updateGuard({ params: {}, ...to }),
  }
}

async function render(state, checkboxes = []) {
  const app = Vue.createSSRApp({ setup: () => state, render: Vue.compile(descriptor.template.content) })
  const passthrough = { render() { return Vue.h('div', null, this.$slots.default?.()) } }
  for (const component of ['LoadingOverlay', 'Toaster', 'VSnackbar', 'VForm', 'VCard', 'VBtn', 'VIcon', 'VDivider', 'VTextField', 'VDialog', 'VCardText', 'VAlert', 'VSpacer', 'VLabel'])
    app.component(component, passthrough)
  app.component('VCheckbox', {
    props: ['modelValue', 'indeterminate', 'disabled', 'label', 'value'],
    setup(props) {
      checkboxes.push({ ...props })
      return () => Vue.h('label', props.label)
    },
  })
  app.config.globalProperties.$vuetify = { display: { mdAndDown: state.mdAndDown.value } }

  return renderToString(app)
}

test('role editing script, template and styles compile', async () => {
  assert.deepEqual(errors, [])
  assert.ok(compileScript(descriptor, { id: 'role-edit' }).content)
  assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename, id: 'role-edit' }).errors, [])
  assert.deepEqual((await compileStyleAsync({
    source: descriptor.styles[0].content,
    filename,
    id: 'role-edit',
    preprocessLang: 'scss',
  })).errors, [])
})

test('direct editing loads the full existing selection and labels without marking the form dirty', async () => {
  const { state, requestedIds, mount, guard, updateGuard } = createHarness()
  await mount()

  assert.deepEqual(requestedIds, ['8'])
  assert.equal(state.name.value, 'Sales')
  assert.deepEqual([...state.assignedPermissions.value], ['view clients', 'delete roles', 'view dashboard'])
  assert.equal(state.permissionsLoaded.value, true)
  assert.equal(state.isFormEdited.value, false)
  assert.equal(guard({ fullPath: '/dashboard/admin/roles' }), true)

  const checkboxes = []
  const html = await render(state, checkboxes)
  assert.match(html, /Redigera roll/)
  assert.match(html, /Databas: view clients/)
  assert.match(html, /selection-summary[\s\S]*?<strong[^>]*>1<\/strong>/)
  const locked = checkboxes.find(checkbox => checkbox.value === 'delete roles')
  assert.equal(locked.disabled, true)
  assert.ok(locked.modelValue.includes('delete roles'))

  state.assignedPermissions.value.reverse()
  assert.equal(state.isFormEdited.value, false)
  state.name.value = 'Changed'
  assert.equal(state.isFormEdited.value, true)
  assert.equal(updateGuard({ params: { id: '8' }, fullPath: '/dashboard/admin/roles/edit/8?tab=permissions' }), true)
  assert.equal(state.isConfirmLeaveVisible.value, false)
  assert.equal(guard({ fullPath: '/dashboard/admin/roles' }), false)
  state.confirmLeave()
  assert.equal(state.isConfirmLeaveVisible.value, false)
})

test('load failures remain visible without the form, block updates and allow retry', async () => {
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
            .map(permission => ({ name: permission.value, label: fail ? null : `Databas: ${permission.value}` })),
        }
      },
    })
    state = harness.state
    await state.fetchData()
    assert.equal(state.permissionsLoaded.value, false)
    assert.ok(state.permissionLoadError.value)
    const html = await render(state)
    assert.ok(html.includes(state.permissionLoadError.value))
    assert.match(html, /Försök igen/)
    assert.doesNotMatch(html, /permission-section-header/)
    await state.onSubmitEdit()
    assert.equal(harness.saved.length, 0)
    assert.equal(harness.navigations.length, 0)

    fail = false
    await state.fetchData()
    assert.equal(state.permissionsLoaded.value, true)
    assert.equal(state.permissionLoadError.value, '')
    assert.equal(state.name.value, 'Sales')
    assert.equal(state.isFormEdited.value, false)
  }
})

test('bulk deselection preserves locked and unlisted permissions in the update payload', async () => {
  const { state, saved, navigations, store, guard } = createHarness()
  await state.fetchData()
  state.name.value = '  Updated sales  '
  state.permissionSections.forEach(section => {
    state.toggleSection(section, true)
    state.toggleSection(section, false)
  })
  state.assignedPermissions.value.push('create clients')
  await state.onSubmitEdit()

  assert.deepEqual(saved, [{
    id: 8,
    name: 'Updated sales',
    permissions: ['delete roles', 'view dashboard', 'create clients'],
  }])
  assert.equal(navigations[0].name, 'dashboard-admin-roles')
  assert.equal(store.updateAlert.message, 'Uppdaterad roll!')
  assert.equal(store.updateAlert.type, 'success')
  assert.equal(store.creationAlert, null)
  assert.equal(state.isRequestOngoing.value, false)
  assert.equal(guard({ fullPath: '/dashboard/admin/roles' }), true)
})

test('invalid input blocks updates and API failures preserve edits without navigating', async () => {
  const invalid = createHarness({ valid: false })
  await invalid.state.fetchData()
  await invalid.state.onSubmitEdit()
  assert.equal(invalid.saved.length, 0)

  const empty = createHarness()
  await empty.state.fetchData()
  empty.state.assignedPermissions.value = []
  await empty.state.onSubmitEdit()
  assert.equal(empty.saved.length, 0)
  assert.ok(empty.state.permissionError.value)

  for (const error of [
    { feedback: 'params_validation_failed', message: { name: ['Namnet används redan.'] } },
    { feedback: 'not_found', message: 'Rollen hittades inte' },
    new Error('Network failed'),
  ]) {
    const { state, store, navigations } = createHarness({ updateRole: async () => { throw error } })
    await state.fetchData()
    state.name.value = 'Changed'
    await state.onSubmitEdit()
    assert.equal(state.name.value, 'Changed')
    assert.equal(state.assignedPermissions.value.length, 3)
    assert.equal(state.advisor.value.show, true)
    assert.equal(state.advisor.value.message, error.feedback === 'params_validation_failed'
      ? 'Namnet används redan.'
      : error.feedback === 'not_found' ? error.message : 'Ett serverfel uppstod. Försök igen.')
    assert.equal(state.isRequestOngoing.value, false)
    assert.equal(store.updateAlert, null)
    assert.equal(navigations.length, 0)
    assert.equal(state.isFormEdited.value, true)
  }
})

test('loading and saving block duplicate requests and both route navigation guards', async () => {
  let resolveRole
  let resolveSave
  const roleResponse = new Promise(resolve => { resolveRole = resolve })
  const saveResponse = new Promise(resolve => { resolveSave = resolve })
  const { state, saved, requestedIds, guard, updateGuard } = createHarness({
    showRole: () => roleResponse,
    updateRole: () => saveResponse,
  })
  const destination = { fullPath: '/dashboard/admin/roles/edit/9' }
  const loading = state.fetchData()
  await state.fetchData()
  await state.onSubmitEdit()
  assert.equal(requestedIds.length, 1)
  assert.equal(saved.length, 0)
  assert.equal(guard(destination), false)
  assert.equal(updateGuard(destination), false)
  resolveRole({ id: 8, name: 'Sales', permissions: [{ name: 'view clients' }] })
  await loading
  assert.equal(updateGuard(destination), true)

  state.name.value = 'Changed'
  assert.equal(updateGuard(destination), false)
  assert.equal(state.isConfirmLeaveVisible.value, true)
  state.isConfirmLeaveVisible.value = false
  const saving = state.onSubmitEdit()
  await Vue.nextTick()
  await state.onSubmitEdit()
  assert.equal(saved.length, 1)
  assert.equal(guard(destination), false)
  assert.equal(updateGuard(destination), false)
  assert.equal(state.isConfirmLeaveVisible.value, false)
  resolveSave()
  await saving
})

test('reusing the edit route reloads the requested ID and resets the dirty baseline', async () => {
  const { state, route, requestedIds } = createHarness()
  await state.fetchData()
  route.params.id = '9'
  await Vue.nextTick()
  await new Promise(setImmediate)
  assert.deepEqual(requestedIds, ['8', '9'])
  assert.equal(state.isFormEdited.value, false)
  await state.onSubmitEdit()
})

test('index edit action uses the new page and consumes the update alert after list loading only once', async () => {
  const { state, store, navigations } = createHarness()
  await state.fetchData()
  await state.onSubmitEdit()
  const indexFilename = path.join(__dirname, '../src/pages/dashboard/admin/roles/index.vue')
  const indexScript = parse(readFileSync(indexFilename, 'utf8')).descriptor.scriptSetup.content.replace(/^import .+$/gm, '')
  const createIndex = () => vm.runInNewContext(`${indexScript}\n;({ advisor, fetchData, editRole })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    watchEffect: () => {},
    onMounted: () => {},
    onBeforeUnmount: () => {},
    useWindowSize: () => ({ width: Vue.ref(1280) }),
    useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
    useRolesStores: () => store,
    router: { push: destination => navigations.push(destination) },
    localStorage: { getItem: () => null },
  })
  const index = createIndex()
  let resolveList
  store.fetchRoles = () => new Promise(resolve => { resolveList = resolve })
  const loading = index.fetchData()
  assert.equal(index.advisor.value.show, false)
  assert.ok(store.updateAlert)
  resolveList()
  await loading
  assert.equal(index.advisor.value.message, 'Uppdaterad roll!')
  assert.equal(index.advisor.value.show, true)
  assert.equal(store.updateAlert, null)
  store.fetchRoles = async () => {}
  const revisited = createIndex()
  await revisited.fetchData()
  assert.equal(revisited.advisor.value.show, false)
  revisited.editRole({ id: 8 })
  assert.equal(navigations.at(-1).name, 'dashboard-admin-roles-edit-id')
  assert.equal(navigations.at(-1).params.id, 8)
})

test('role store loads directly, propagates load errors and updates without an existing list entry', async () => {
  const source = readFileSync(path.join(__dirname, '../src/stores/useRoles.js'), 'utf8')
  const role = { id: 8, name: 'Changed', permissions: [{ name: 'view clients' }] }
  let fail = false
  const useRolesStores = vm.runInNewContext(`${source.replace(/^import .+$/gm, '').replace('export const ', 'const ')}\n;useRolesStores`, {
    defineStore,
    Roles: {
      show: async id => {
        assert.equal(id, 8)
        if (fail) throw new Error('Unavailable')
        return { data: { data: { role } } }
      },
      update: async () => ({ data: { data: { role } } }),
    },
  })
  setActivePinia(createPinia())
  const store = useRolesStores()
  assert.equal((await store.showRole(8)).id, 8)
  assert.equal(store.loading, false)
  await store.updateRole({ name: role.name, permissions: ['view clients'] }, 8)
  assert.equal(store.roles.length, 0)
  assert.equal(Object.hasOwn(store.roles, '-1'), false)
  store.roles = [{ id: 8, name: 'Old' }]
  await store.updateRole({ name: role.name, permissions: ['view clients'] }, 8)
  assert.equal(store.roles[0].name, 'Changed')
  fail = true
  await assert.rejects(store.showRole(8), /Unavailable/)
  assert.equal(store.loading, false)
})
