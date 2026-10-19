const assert = require('node:assert/strict')
const { readFile } = require('node:fs/promises')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')

async function createHarness({ native = true, query = {} } = {}) {
  const calls = []
  const values = new Map()
  const preferences = new Map()
  let savedCredentials
  let secureReadError
  let storageError
  let networkError
  let httpStatus = 200
  let message = 'login_success'
  let strongBiometry = true
  const deviceToken = 'd'.repeat(64)
  const user = { id: 7, is_2fa: 1 }
  const abilities = [{ action: 'view', subject: 'Auth' }]
  const localStorage = {
    getItem: key => values.get(key) ?? null,
    setItem: (key, value) => values.set(key, String(value)),
    removeItem: key => values.delete(key),
  }
  const context = vm.createContext({ localStorage })
  const plugin = {
    isAvailable: async options => {
      calls.push(['available', options])
      return { isAvailable: true, strongBiometryIsAvailable: strongBiometry }
    },
    isCredentialsSaved: async () => {
      calls.push(['saved'])
      return { isSaved: Boolean(savedCredentials) }
    },
    setCredentials: async credentials => {
      calls.push(['set', credentials])
      if (storageError)
        throw storageError
      savedCredentials = credentials
    },
    getSecureCredentials: async options => {
      calls.push(['verify-and-read', options])
      if (secureReadError)
        throw secureReadError
      return savedCredentials
    },
    deleteCredentials: async options => {
      calls.push(['delete-local', options])
      savedCredentials = undefined
    },
  }
  const axios = {
    post: async (url, body, config) => {
      calls.push(['post', url, body, config])
      if (networkError)
        throw networkError
      if (url === 'auth/device-token')
        return { status: 201, data: { data: { token: deviceToken } } }
      return {
        status: httpStatus,
        data: {
          success: true,
          message,
          data: {
            accessToken: 'jwt',
            user_data: user,
            userAbilities: abilities,
            ...(message !== 'login_success' ? { token: '2fa-secret' } : {}),
            ...(message === '2fa-generate' ? { qr: 'qr-url' } : {}),
          },
        },
      }
    },
    delete: async url => {
      calls.push(['delete-remote', url])
      if (networkError)
        throw networkError
    },
  }
  const stubs = {
    '@capacitor/core': { Capacitor: { isNativePlatform: () => native } },
    '@capacitor/preferences': {
      Preferences: {
        get: async ({ key }) => {
          calls.push(['preference-get', key])
          return { value: preferences.get(key) ?? null }
        },
        set: async ({ key, value }) => {
          calls.push(['preference-set', key, value])
          preferences.set(key, value)
        },
        remove: async ({ key }) => {
          calls.push(['preference-remove', key])
          preferences.delete(key)
        },
      },
    },
    '@capgo/capacitor-native-biometric': {
      NativeBiometric: plugin,
      AccessControl: { BIOMETRY_CURRENT_SET: 1 },
      BiometricAuthError: { NO_PROTECTED_CREDENTIALS_FOUND: 21 },
    },
    '@axios': { default: axios },
    '@/plugins/casl/useAppAbility': {
      useAppAbility: () => ({ update: value => calls.push(['ability', value]) }),
    },
    'vue-router': {
      useRoute: () => ({ query }),
      useRouter: () => ({ replace: async target => calls.push(['navigate', target]) }),
    },
  }
  const cache = new Map()
  async function load(name) {
    if (cache.has(name))
      return cache.get(name)
    let module
    if (stubs[name]) {
      const exports = stubs[name]
      module = new vm.SyntheticModule(Object.keys(exports), function () {
        for (const [key, value] of Object.entries(exports))
          this.setExport(key, value)
      }, { context })
    } else {
      const filename = path.join(__dirname, '..', 'src', 'composables', `${name.split('/').pop()}.js`)
      module = new vm.SourceTextModule(await readFile(filename, 'utf8'), { context, identifier: filename })
    }
    cache.set(name, module)
    await module.link(load)
    return module
  }
  const module = await load('@/composables/useBiometricLogin')
  await module.evaluate()
  const biometric = module.namespace.useBiometricLogin()
  const session = cache.get('@/composables/useLoginSession').namespace.useLoginSession()

  return {
    biometric, session, calls, values, preferences, deviceToken,
    authenticate() {
      localStorage.setItem('user_data', JSON.stringify(user))
      localStorage.setItem('accessToken', 'password-login-jwt')
    },
    async enroll() {
      this.authenticate()
      await biometric.enable()
      values.clear()
      calls.length = 0
    },
    setResponse(nextMessage, status = 200) {
      message = nextMessage
      httpStatus = status
    },
    setSecureReadError(error) { secureReadError = error },
    setStorageError(error) { storageError = error },
    setNetworkError(error) { networkError = error },
    setStrongBiometry(value) { strongBiometry = value },
  }
}

test('all five methods are no-ops on web', async () => {
  const h = await createHarness({ native: false })
  assert.equal(await h.biometric.isAvailable(), false)
  assert.equal(await h.biometric.isEnabled(), false)
  await h.biometric.enable()
  await h.biometric.login()
  await h.biometric.disable()
  assert.equal(h.calls.length, 0)
  assert.equal(h.values.size, 0)
  assert.equal(h.preferences.size, 0)
})

test('enrollment stores a protected device token, never the account password', async () => {
  const h = await createHarness()
  h.authenticate()
  await h.biometric.enable()
  const credentials = h.calls.find(([method]) => method === 'set')[1]
  assert.equal(credentials.server, 'bilflogg.se')
  assert.equal(credentials.username, '7')
  assert.equal(credentials.password, h.deviceToken)
  assert.equal(credentials.accessControl, 1)
  assert.equal(h.preferences.get('biometric_login_user'), '7')
  assert.equal([...h.preferences.values()].includes(h.deviceToken), false)
  assert.equal([...h.values.values()].includes(h.deviceToken), false)
  assert.equal(await h.biometric.isEnabled(), true)
  h.values.set('user_data', JSON.stringify({ id: 8 }))
  assert.equal(await h.biometric.isEnabled(), false)
})

test('weak-only biometrics are not offered for hardware-protected credentials', async () => {
  const h = await createHarness()
  h.authenticate()
  h.setStrongBiometry(false)
  assert.equal(await h.biometric.isAvailable(), false)
  await assert.rejects(h.biometric.enable(), /inte tillgänglig/)
  assert.equal(h.calls.some(([method]) => method === 'post'), false)
})

test('failed secure storage does not leave enrollment enabled', async () => {
  const h = await createHarness()
  h.authenticate()
  h.setStorageError(new Error('storage unavailable'))
  await assert.rejects(h.biometric.enable(), /storage unavailable/)
  assert.equal(await h.biometric.isEnabled(), false)
  assert.equal(h.calls.some(([method]) => method === 'delete-local'), true)
})

for (const message of ['login_success', '2fa', '2fa-generate']) {
  test(`device login shares session handling for ${message}`, async () => {
    const h = await createHarness()
    await h.enroll()
    h.setResponse(message)
    await h.biometric.login()
    const verification = h.calls.findIndex(([method]) => method === 'verify-and-read')
    const request = h.calls.findIndex(([method]) => method === 'post')
    assert.ok(verification < request)
    const [, url, body, config] = h.calls[request]
    assert.equal(url, 'auth/device-login')
    assert.equal(body.token, h.deviceToken)
    assert.equal(config.skipAuthHeader, true)
    assert.equal(config.skipAuthRedirect, true)
    assert.equal(config.validateStatus(401), true)
    assert.equal(config.validateStatus(403), false)
    assert.equal(config.validateStatus(429), false)
    assert.equal(h.values.get('accessToken'), 'jwt')
    assert.equal(JSON.parse(h.values.get('is_2fa')).status, message !== 'login_success')
    assert.equal(JSON.parse(h.values.get('two_factor')).generate_qr, message === '2fa-generate')
    assert.equal(h.values.get('token'), message === 'login_success' ? undefined : '2fa-secret')
    assert.equal(h.values.get('qr'), message === '2fa-generate' ? 'qr-url' : undefined)
    assert.equal(h.calls.find(([method]) => method === 'navigate')[1], message === 'login_success' ? '/info' : '/')
    assert.equal(h.calls.some(([method]) => method === 'ability'), true)
  })
}

test('HTTP 401 deletes local credentials and asks for password login without navigating', async () => {
  const h = await createHarness()
  await h.enroll()
  h.setResponse('invalid_credentials', 401)
  await assert.rejects(h.biometric.login(), error => error.requiresPassword && /Logga in med ditt lösenord/.test(error.message))
  assert.equal(await h.biometric.isEnabled(), false)
  assert.equal(h.calls.some(([method]) => method === 'delete-local'), true)
  assert.equal(h.calls.some(([method]) => method === 'navigate'), false)
  assert.equal(h.values.has('accessToken'), false)
})

test('invalidated protected credentials require password login and clear the flag', async () => {
  const h = await createHarness()
  await h.enroll()
  h.setSecureReadError({ code: '21' })
  await assert.rejects(h.biometric.login(), error => error.requiresPassword)
  assert.equal(await h.biometric.isEnabled(), false)
  assert.equal(h.calls.some(([method]) => method === 'post'), false)
})

test('biometric cancellation does not call the backend or delete enrollment', async () => {
  const h = await createHarness()
  await h.enroll()
  h.setSecureReadError({ code: '16' })
  await assert.rejects(h.biometric.login())
  assert.equal(h.calls.some(([method]) => method === 'post'), false)
  assert.equal(h.calls.some(([method]) => method === 'delete-local'), false)
  assert.equal(await h.biometric.isEnabled(), true)
})

test('network errors preserve enrollment and do not navigate', async () => {
  const h = await createHarness()
  await h.enroll()
  h.setNetworkError(new Error('offline'))
  await assert.rejects(h.biometric.login(), /offline/)
  assert.equal(await h.biometric.isEnabled(), true)
  assert.equal(h.calls.some(([method]) => method === 'navigate'), false)
})

test('disable revokes on the backend before deleting local credentials and flag', async () => {
  const h = await createHarness()
  await h.enroll()
  await h.biometric.disable()
  assert.equal(h.calls[0][0], 'delete-remote')
  assert.equal(h.calls[0][1], 'auth/device-token')
  assert.equal(await h.biometric.isEnabled(), false)
  assert.equal(h.calls.some(([method]) => method === 'delete-local'), true)
})

test('failed remote revocation retains local enrollment for retry', async () => {
  const h = await createHarness()
  await h.enroll()
  h.setNetworkError(new Error('offline'))
  await assert.rejects(h.biometric.disable(), /offline/)
  assert.equal(h.calls.some(([method]) => method === 'delete-local'), false)
  assert.equal(await h.biometric.isEnabled(), true)
})

test('password login remembers only email, clears legacy password and awaits enrollment before redirect', async () => {
  const h = await createHarness({ query: { to: '/dashboard/panel' } })
  h.values.set('password', 'legacy-password')
  const response = {
    message: 'login_success',
    data: { accessToken: 'jwt', user_data: { id: 7, is_2fa: 1 }, userAbilities: [] },
  }
  await h.session.completeLogin(response, {
    rememberMe: true,
    email: 'user@example.test',
    beforeNavigate: async () => {
      assert.equal(h.values.get('accessToken'), 'jwt')
      assert.equal(h.calls.some(([method]) => method === 'navigate'), false)
      h.calls.push(['offer'])
    },
  })
  assert.equal(h.values.has('password'), false)
  assert.equal(h.values.get('email'), 'user@example.test')
  assert.equal(h.values.get('remember_me'), 'true')
  assert.equal(JSON.parse(h.values.get('is_2fa')).status, false)
  assert.equal(h.calls.at(-1)[1], '/dashboard/panel')
})

test('pending 2FA never offers enrollment before verification; completion does', async () => {
  const h = await createHarness()
  await h.session.completeLogin({
    message: '2fa',
    data: { accessToken: 'jwt', user_data: { id: 7 }, userAbilities: [], token: 'secret' },
  }, {
    beforeNavigate: async () => assert.fail('offered before 2FA'),
  })
  let offered = false
  await h.session.completeTwoFactorLogin(async () => {
    assert.equal(h.values.has('is_2fa'), false)
    assert.equal(h.values.has('token'), false)
    offered = true
  })
  assert.equal(offered, true)
  assert.equal(h.calls.at(-1)[1], '/info')
})
