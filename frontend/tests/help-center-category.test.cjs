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
const thumbnailSource = readFileSync(path.join(__dirname, '../src/@core/utils/helpVideo.js'), 'utf8')
const getHelpVideoThumbnailUrl = vm.runInNewContext(
  `${thumbnailSource.replace(/^import .+$/gm, '').replace(/export const /g, 'const ')}\n;getHelpVideoThumbnailUrl`,
  { themeConfig: { settings: { urlStorage: 'https://example.test/storage/' } } },
)

function createHarness(fetchCategory) {
  const route = Vue.reactive({ params: { label: 'summary' } })
  const state = vm.runInNewContext(`${script}
    ;({ route, helpCategory, helpCategories, helpVideos, searchQuery,
        isRequestOngoing, hasLoaded,
        advisor, snackbarLocation, mdAndDown, sectionEl, sortOptions,
        fetchData, getHelpVideoThumbnailUrl, getHelpCategoryIconUrl, failedIconUrls, onHelpCategoryIconError })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    watch: Vue.watch,
    inject: () => undefined,
    useRoute: () => route,
    useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
    useHelpCenterStores: () => ({ fetchCategory }),
    themeConfig: { settings: { urlStorage: 'https://example.test/storage/' } },
    console: { warn: () => {} },
    getHelpVideoThumbnailUrl,
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
        { id: 1, slug: 'first-tutorial', title: 'First tutorial', thumbnail_url: 'https://example.test/thumbnail.jpg' },
        { id: 2, slug: 'youtube-tutorial', title: 'YouTube tutorial', provider: 'youtube', embed_url: 'https://www.youtube.com/embed/dQw4w9WgXcQ' },
        { id: 3, slug: 'no-thumbnail', title: 'No thumbnail', provider: 'mp4' },
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
    'VSnackbar', 'LoadingOverlay', 'VTextField', 'VMenu', 'VBtn', 'VList', 'VListItem', 'VListItemTitle',
  ]) app.component(component, passthrough)

  app.component('RouterLink', {
    props: ['to'],
    render() {
      const target = this.to.params?.slug
        ? `/dashboard/admin/help-center/video/${this.to.params.slug}`
        : '/dashboard/admin/help-center'

      return Vue.h('a', { href: target }, this.$slots.default?.())
    },
  })
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
  assert.equal((html.match(/href="\/dashboard\/admin\/help-center\/video\//g) || []).length, 3)
  assert.match(html, /href="\/dashboard\/admin\/help-center\/video\/first-tutorial"/)
  assert.match(html, /First tutorial/)
  assert.match(html, /https:\/\/example.test\/thumbnail.jpg/)
  assert.match(html, /https:\/\/i.ytimg.com\/vi\/dQw4w9WgXcQ\/hqdefault.jpg/)
  assert.match(html, /Ingen miniatyrbild/)
  assert.doesNotMatch(html, /<(?:video|iframe)\b/i)
})

test('category icons use image requests and fall back when an image fails', async () => {
  const data = payload()
  data.categories = [
    { id: 1, name: 'Overview', label: 'summary', icon: 'help-categories/summary.svg' },
    { id: 2, name: 'No icon', label: 'empty', icon: null },
  ]
  const state = createHarness(async () => data)
  await state.fetchData()
  const html = await render(state)

  assert.match(html, /<img[^>]*src="https:\/\/example\.test\/storage\/help-categories\/summary\.svg"/)
  assert.doesNotMatch(html, /mask-image/)
  assert.equal((html.match(/icon="tabler-help"/g) || []).length, 1)

  state.onHelpCategoryIconError(data.categories[0])
  const failedHtml = await render(state)
  assert.doesNotMatch(failedHtml, /<img[^>]*help-categories\/summary\.svg/)
  assert.equal((failedHtml.match(/icon="tabler-help"/g) || []).length, 2)

  data.categories[0].icon = 'help-categories/replacement.svg'
  assert.match(await render(state), /<img[^>]*help-categories\/replacement\.svg/)
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
