const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')
const Vue = require('vue')
const { renderToString } = require('@vue/server-renderer')
const { parse, compileScript, compileStyle } = require('@vue/compiler-sfc')

const filename = path.join(__dirname, '../src/pages/dashboard/admin/help-center/category/[label].vue')
const { descriptor, errors } = parse(readFileSync(filename, 'utf8'), { filename })
const script = descriptor.scriptSetup.content.replace(/^import .+$/gm, '')

function createHarness(fetchCategory) {
  const route = Vue.reactive({ params: { label: 'summary' } })
  const state = vm.runInNewContext(`${script}
    ;({ route, helpCategory, helpCategories, helpVideos, searchQuery,
        isRequestOngoing, hasLoaded,
        advisor, snackbarLocation, mdAndDown, sectionEl, sortOptions,
        fetchData, getHelpVideoThumbnailUrl, getHelpCategoryIconUrl })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    watch: Vue.watch,
    inject: () => undefined,
    useRoute: () => route,
    useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
    useHelpCenterStores: () => ({ fetchCategory }),
    themeConfig: { settings: { urlStorage: 'https://example.test/storage/' } },
    onMounted: () => {},
    onBeforeUnmount: () => {},
  })

  return state
}

function payload() {
  return {
    helpCategory: { id: 1, name: 'Overview', label: 'summary' },
    categories: [{ id: 1, name: 'Overview', label: 'summary' }],
    helpVideos: [
        { id: 1, title: 'First tutorial', thumbnail_url: 'https://example.test/thumbnail.jpg' },
        { id: 2, title: 'YouTube tutorial', provider: 'youtube', embed_url: 'https://www.youtube.com/embed/dQw4w9WgXcQ' },
        { id: 3, title: 'No thumbnail', provider: 'mp4' },
    ],
    helpVideosTotalCount: 3,
  }
}

async function render(state) {
  const app = Vue.createSSRApp({
    setup: () => state,
    render: Vue.compile(descriptor.template.content),
  })
  const passthrough = { render() { return Vue.h('div', null, this.$slots.default?.()) } }

  for (const component of [
    'VCard', 'VCardTitle', 'VCardText', 'VDivider', 'VIcon', 'VImg',
    'VSnackbar', 'LoadingOverlay', 'VTextField', 'VMenu', 'VBtn', 'VList', 'VListItem', 'VListItemTitle', 'RouterLink',
  ]) app.component(component, passthrough)

  app.config.globalProperties.$vuetify = { display: { mdAndDown: false } }

  return renderToString(app)
}

test('category view compiles and never embeds a video player', () => {
  assert.deepEqual(errors, [])
  assert.ok(compileScript(descriptor, { id: 'help-category' }).content)
  assert.deepEqual(compileStyle({
    source: descriptor.styles[0].content,
    id: 'data-v-help-category',
    scoped: true,
  }).errors, [])
  assert.doesNotMatch(descriptor.template.content, /<(?:video|iframe|HelpVideoPreview)\b/i)
  assert.doesNotMatch(descriptor.template.content, /<VPagination\b/)
  assert.match(descriptor.customBlocks.find(block => block.type === 'route').content, /subject: help-center/)
})

test('category view requests its label and renders only image previews', async () => {
  let requestedLabel
  const state = createHarness(async label => {
    requestedLabel = label
    return payload()
  })
  await state.fetchData()
  const html = await render(state)

  assert.equal(requestedLabel, 'summary')
  assert.equal((html.match(/<article/g) || []).length, 3)
  assert.match(html, /First tutorial/)
  assert.match(html, /https:\/\/example.test\/thumbnail.jpg/)
  assert.match(html, /https:\/\/i.ytimg.com\/vi\/dQw4w9WgXcQ\/hqdefault.jpg/)
  assert.match(html, /Ingen miniatyrbild/)
  assert.doesNotMatch(html, /<(?:video|iframe)\b/i)
})

test('category navigation requests the new label without pagination', async () => {
  const requests = []
  const state = createHarness(async (label, params) => {
    requests.push({ label, ...params })
    return payload()
  })
  state.route.params.label = 'clients'
  await Vue.nextTick()
  await Vue.nextTick()

  assert.equal(requests.at(-1).label, 'clients')
  assert.equal('page' in requests.at(-1), false)
  assert.equal('limit' in requests.at(-1), false)

  state.searchQuery.value = 'Tutorial'
  await Vue.nextTick()

  assert.equal(requests.at(-1).search, 'Tutorial')
  assert.equal('sort' in requests.at(-1), false)
})

test('category failures display the API error instead of an empty success state', async () => {
  const state = createHarness(async () => {
    throw { response: { data: { message: 'Kategorin hittades inte.' } } }
  })
  await state.fetchData()

  assert.equal(state.advisor.value.message, 'Kategorin hittades inte.')
  assert.equal(state.advisor.value.type, 'error')
  assert.equal(state.isRequestOngoing.value, false)
  assert.equal(state.hasLoaded.value, false)
})
