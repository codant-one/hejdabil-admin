const assert = require('node:assert/strict')
const { readFile } = require('node:fs/promises')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')

async function setup({ native = true } = {}) {
  const storage = new Map([
    ['accessToken', 'jwt'],
    ['user_data', '{"id":7}'],
  ])
  let serverCount = 12
  let requests = 0
  let onStateChange
  const stubs = {
    pinia: {
      defineStore: (_, { state, actions }) => () => Object.assign(state(), actions),
    },
    '@capacitor/app': {
      App: { addListener: async (name, callback) => {
        assert.equal(name, 'appStateChange')
        onStateChange = callback
      } },
    },
    '@capacitor/core': { Capacitor: { isNativePlatform: () => native } },
    '@/api/notifications': {
      default: {
        listRecent: async () => ({ data: { success: true, data: [] } }),
        unreadCount: async () => {
          requests++
          return { data: { count: serverCount } }
        },
        markAsRead: async () => { serverCount-- },
        markAllAsRead: async () => { serverCount = 0 },
      },
    },
    '@/api/settings': { default: { get: async () => ({}) } },
    '@/api/configs': { default: {} },
    '@/router': { default: {} },
  }
  const context = vm.createContext({
    localStorage: { getItem: key => storage.get(key) ?? null },
    window: { addEventListener() {} },
    document: { addEventListener() {} },
    console: { warn() {}, error: console.error },
  })
  const content = await readFile(path.join(__dirname, '..', 'src', 'stores', 'useNotifications.js'), 'utf8')
  const module = new vm.SourceTextModule(content, { context })
  await module.link(name => {
    const values = stubs[name]
    return new vm.SyntheticModule(Object.keys(values), function () {
      for (const [key, value] of Object.entries(values))
        this.setExport(key, value)
    }, { context })
  })
  await module.evaluate()

  return {
    store: module.namespace.useNotificationsStore(),
    storage,
    setCount: count => { serverCount = count },
    requests: () => requests,
    changeState: async isActive => {
      onStateChange?.({ isActive })
      await new Promise(resolve => setImmediate(resolve))
    },
  }
}

test('2FA gates the initial count; native foreground refreshes the total', async () => {
  const { store, storage, requests, setCount, changeState } = await setup()
  storage.set('is_2fa', '{"status":true}')
  await store.init()
  assert.equal(requests(), 0)
  storage.delete('is_2fa')
  await store.init()
  assert.equal(store.unreadCount, 12)
  setCount(6)
  await changeState(false)
  assert.equal(requests(), 1)
  await changeState(true)
  assert.equal(store.unreadCount, 6)
  storage.delete('accessToken')
  await changeState(true)
  assert.equal(store.unreadCount, 0)
  assert.equal(requests(), 2)
})

test('Echo/push IDs dedupe even after dropdown eviction; reading updates the total', async () => {
  const { store, setCount, changeState, requests } = await setup({ native: false })
  await store.init()
  for (let id = 1; id <= 5; id++)
    store.addFromBackend({ id })
  store.addFromBackend({ id: '1' })
  assert.equal(store.unreadCount, 17)
  setCount(17)
  await store.markAsRead('1')
  assert.equal(store.unreadCount, 16)
  store.addFromBackend({ id: 1 })
  assert.equal(store.unreadCount, 16)
  await store.markAllRead()
  assert.equal(store.unreadCount, 0)
  const currentRequests = requests()
  await changeState(true)
  assert.equal(requests(), currentRequests)
})
