const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')
const Vue = require('vue')
const { parse, compileScript, compileTemplate } = require('@vue/compiler-sfc')

const filename = path.join(__dirname, '../src/pages/dashboard/admin/roles/index.vue')
const { descriptor, errors } = parse(readFileSync(filename, 'utf8'), { filename })
const script = descriptor.scriptSetup.content.replace(/^import .+$/gm, '')

function createHarness({ deleteRole = async () => {}, fetchRoles = async () => {} } = {}) {
  const deletedIds = []
  const fetched = []
  const scrolls = []
  const store = {
    creationAlert: null,
    updateAlert: null,
    getRoles: [],
    last_page: 1,
    rolesTotalCount: 0,
    deleteRole: async id => {
      deletedIds.push(id)
      return deleteRole(id)
    },
    fetchRoles: async params => {
      fetched.push(params)
      return fetchRoles(params)
    },
  }
  const state = vm.runInNewContext(`${script}
    ;({ showDeleteDialog, removeRole, selectedRol, isConfirmDeleteDialogVisible,
        isMobileActionDialogVisible, isDeleting, isRequestOngoing, advisor, roles, totalRoles })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    watchEffect: () => {},
    onMounted: () => {},
    onBeforeUnmount: () => {},
    useWindowSize: () => ({ width: Vue.ref(1280) }),
    useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
    useRolesStores: () => store,
    localStorage: { getItem: () => null },
    window: { scrollTo: (...args) => scrolls.push(args) },
  })

  return { state, store, deletedIds, fetched, scrolls }
}

test('role list and its integrated deletion dialog compile', () => {
  assert.deepEqual(errors, [])
  assert.ok(compileScript(descriptor, { id: 'role-index' }).content)
  assert.deepEqual(compileTemplate({
    source: descriptor.template.content,
    filename,
    id: 'role-index',
  }).errors, [])
})

test('opening deletion selects the role and dismisses mobile actions without deleting', () => {
  const { state, deletedIds } = createHarness()
  const role = { id: 8, name: 'Sales' }
  state.isMobileActionDialogVisible.value = true
  state.showDeleteDialog(role)
  assert.equal(state.isConfirmDeleteDialogVisible.value, true)
  assert.equal(state.isMobileActionDialogVisible.value, false)
  assert.equal(state.selectedRol.value.id, 8)
  state.selectedRol.value.name = 'Selected copy'
  assert.equal(role.name, 'Sales')
  assert.deepEqual(deletedIds, [])
})

test('confirmation deletes once, awaits list refresh and shows the existing success message', async () => {
  let resolveDelete
  let resolveList
  const deletion = new Promise(resolve => { resolveDelete = resolve })
  const listing = new Promise(resolve => { resolveList = resolve })
  const { state, store, deletedIds, fetched, scrolls } = createHarness({
    deleteRole: () => deletion,
    fetchRoles: () => listing,
  })
  state.showDeleteDialog({ id: 8, name: 'Sales' })
  const removing = state.removeRole()
  await state.removeRole()
  assert.deepEqual(deletedIds, [8])
  assert.equal(state.isConfirmDeleteDialogVisible.value, false)
  assert.equal(state.isDeleting.value, true)
  assert.equal(state.isRequestOngoing.value, true)
  assert.equal(state.advisor.value.show, false)
  assert.equal(fetched.length, 0)

  resolveDelete()
  await Vue.nextTick()
  await new Promise(setImmediate)
  assert.equal(fetched.length, 1)
  assert.equal(state.isDeleting.value, true)
  assert.equal(state.advisor.value.show, false)
  store.getRoles = [{ id: 9, name: 'Another role' }]
  store.rolesTotalCount = 1
  resolveList()
  await removing

  assert.equal(state.roles.value[0].id, 9)
  assert.equal(state.totalRoles.value, 1)
  assert.equal(state.selectedRol.value.id, undefined)
  assert.equal(state.isDeleting.value, false)
  assert.equal(state.isRequestOngoing.value, false)
  assert.equal(state.advisor.value.type, 'success')
  assert.equal(state.advisor.value.message, 'Roll raderad!')
  assert.equal(state.advisor.value.show, true)
  assert.deepEqual(scrolls, [[0, 0]])
})

test('deletion failures show normalized permission, not-found or server errors without refreshing', async () => {
  for (const { error, message } of [
    { error: { response: { status: 403 } }, message: 'Du har inte tillräckligt med behörigheter' },
    { error: { message: 'User does not have the right permissions.' }, message: 'Du har inte tillräckligt med behörigheter' },
    { error: { feedback: 'not_found', message: 'Rollen hittades inte' }, message: 'Rollen hittades inte' },
    { error: new Error('Network failed'), message: 'Ett serverfel uppstod. Försök igen.' },
  ]) {
    const { state, fetched, deletedIds } = createHarness({ deleteRole: async () => { throw error } })
    state.showDeleteDialog({ id: 8, name: 'Sales' })
    await state.removeRole()
    assert.deepEqual(deletedIds, [8])
    assert.equal(fetched.length, 0)
    assert.equal(state.advisor.value.type, 'error')
    assert.equal(state.advisor.value.message, message)
    assert.equal(state.advisor.value.show, true)
    assert.equal(state.isDeleting.value, false)
    assert.equal(state.isRequestOngoing.value, false)
    assert.equal(state.isConfirmDeleteDialogVisible.value, false)
    assert.equal(state.selectedRol.value.id, 8)
  }
})
