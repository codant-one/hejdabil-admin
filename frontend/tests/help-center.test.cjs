const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')
const Vue = require('vue')
const { renderToString } = require('@vue/server-renderer')
const { parse, compileScript, compileStyle } = require('@vue/compiler-sfc')

const filename = path.join(__dirname, '../src/pages/dashboard/admin/help-center/index.vue')
const { descriptor, errors } = parse(readFileSync(filename, 'utf8'), { filename })
const script = descriptor.scriptSetup.content.replace(/^import .+$/gm, '')

function createHarness(fetchCategories) {
  return vm.runInNewContext(`${script}
    ;({ helpCategories, isRequestOngoing, hasLoaded, advisor, snackbarLocation, mdAndDown,
        sectionEl, fetchData, getHelpCategoryIconUrl, windowWidth })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    inject: () => undefined,
    useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
    useWindowSize: () => ({ width: Vue.ref(1200) }),
    useHelpCenterStores: () => ({ fetchCategories }),
    themeConfig: { settings: { urlStorage: 'https://example.test/storage/' } },
    onMounted: () => {},
    onBeforeUnmount: () => {},
  })
}

async function render(state) {
  const app = Vue.createSSRApp({
    setup: () => state,
    render: Vue.compile(descriptor.template.content),
  })
  const passthrough = { render() { return Vue.h('div', null, this.$slots.default?.()) } }

  for (const component of ['VCard', 'VCardTitle', 'VCardText', 'VDivider', 'VIcon', 'VImg', 'VSnackbar', 'LoadingOverlay'])
    app.component(component, passthrough)

  app.component('RouterLink', {
    props: ['to'],
    render() {
      return Vue.h('a', { href: `/dashboard/admin/help-center/category/${this.to.params.label}` }, this.$slots.default?.())
    },
  })
  app.config.globalProperties.$vuetify = { display: { mdAndDown: state.mdAndDown.value } }

  return renderToString(app)
}

test('help center script, template and scoped styles compile', () => {
  assert.deepEqual(errors, [])
  assert.ok(compileScript(descriptor, { id: 'help-center' }).content)
  assert.equal(typeof Vue.compile(descriptor.template.content), 'function')
  assert.deepEqual(compileStyle({
    source: descriptor.styles[0].content,
    id: 'data-v-help-center',
    scoped: true,
  }).errors, [])
  assert.match(descriptor.customBlocks.find(block => block.type === 'route').content, /subject: help-center/)
})

test('categories render names, stored icons and exact video counts including zero', async () => {
  const state = createHarness(async () => [
    { id: 1, name: 'Overview', label: 'summary', icon: 'help-categories/overview.svg', videos_count: 3 },
    { id: 2, name: 'Customers', label: 'clients', icon: '', videos_count: 1 },
    { id: 3, name: 'Empty', label: 'empty', icon: null, videos_count: 0 },
  ])
  await state.fetchData()
  const html = await render(state)

  assert.equal((html.match(/<a /g) || []).length, 3)
  assert.match(html, /href="\/dashboard\/admin\/help-center\/category\/summary"/)
  assert.match(html, /Overview/)
  assert.match(html, /3 videor/)
  assert.match(html, /1 video\s*</)
  assert.match(html, /0 videor/)
  assert.match(html, /https:\/\/example.test\/storage\/help-categories\/overview.svg/)
  assert.equal(state.isRequestOngoing.value, false)
  assert.equal(state.hasLoaded.value, true)
})

test('successful empty responses show the category empty state', async () => {
  const state = createHarness(async () => [])
  assert.doesNotMatch(await render(state), /Inga kategorier hittades/)
  await state.fetchData()
  assert.match(await render(state), /Inga kategorier hittades/)
})

test('failed requests display an error without a successful empty state', async () => {
  const state = createHarness(async () => {
    throw new Error('Request failed')
  })
  await state.fetchData()

  assert.equal(state.advisor.value.type, 'error')
  assert.equal(state.advisor.value.message, 'Request failed')
  assert.equal(state.advisor.value.show, true)
  assert.equal(state.isRequestOngoing.value, false)
  assert.equal(state.hasLoaded.value, false)
})

test('an older response does not overwrite the latest category list', async () => {
  let resolveFirst
  const firstResponse = new Promise(resolve => { resolveFirst = resolve })
  let calls = 0
  const latest = [{ id: 2, name: 'Latest', videos_count: 2 }]
  const state = createHarness(() => ++calls === 1 ? firstResponse : Promise.resolve(latest))
  const firstRequest = state.fetchData()
  await state.fetchData()
  resolveFirst([{ id: 1, name: 'Old', videos_count: 0 }])
  await firstRequest

  assert.equal(state.helpCategories.value[0].name, 'Latest')
})
