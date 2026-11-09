const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')
const Vue = require('vue')
const { renderToString } = require('@vue/server-renderer')
const { parse, compileScript, compileStyle } = require('@vue/compiler-sfc')

const filename = path.join(__dirname, '../src/pages/dashboard/admin/help-center/video/[slug].vue')
const { descriptor, errors } = parse(readFileSync(filename, 'utf8'), { filename })
const script = descriptor.scriptSetup.content.replace(/^import .+$/gm, '')
const thumbnailSource = readFileSync(path.join(__dirname, '../src/@core/utils/helpVideo.js'), 'utf8')
const getHelpVideoThumbnailUrl = vm.runInNewContext(`${thumbnailSource.replace('export const ', 'const ')}\n;getHelpVideoThumbnailUrl`)
const previewFilename = path.join(__dirname, '../src/components/common/HelpVideoPreview.vue')
const previewDescriptor = parse(readFileSync(previewFilename, 'utf8'), { filename: previewFilename }).descriptor

function createHarness(fetchVideo) {
  const route = Vue.reactive({ params: { slug: 'first-tutorial' } })

  return vm.runInNewContext(`${script}
    ;({ route, helpVideo, relatedHelpVideos, isRequestOngoing, hasLoaded,
        errorMessage, advisor, snackbarLocation, mdAndDown, sectionEl,
        fetchData, getHelpVideoThumbnailUrl })`, {
    ref: Vue.ref,
    computed: Vue.computed,
    watch: Vue.watch,
    useRoute: () => route,
    useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
    useHelpCenterStores: () => ({ fetchVideo }),
    getHelpVideoThumbnailUrl,
    onMounted: () => {},
    onBeforeUnmount: () => {},
  })
}

function payload(provider = 'youtube', videoUrl = 'https://youtu.be/dQw4w9WgXcQ') {
  return {
    helpVideo: {
      id: 1, slug: 'first-tutorial', title: 'First tutorial',
      description: 'A step-by-step guide.\nSecond line.',
      provider, video_url: videoUrl,
      category: { id: 1, name: 'Overview', label: 'summary' },
    },
    relatedHelpVideos: [
      { id: 2, slug: 'second-tutorial', title: 'Second tutorial', thumbnail_url: 'https://example.test/thumbnail.jpg' },
    ],
  }
}

async function render(state) {
  const app = Vue.createSSRApp({
    setup: () => state,
    render: Vue.compile(descriptor.template.content),
  })
  const passthrough = { render() { return Vue.h('div', null, this.$slots.default?.()) } }

  for (const component of ['VCard', 'VCardText', 'VIcon', 'VImg', 'VSnackbar', 'VAlert', 'LoadingOverlay'])
    app.component(component, passthrough)

  const link = {
    props: ['to'],
    render() {
      const target = this.to.params?.slug
        ? `/dashboard/admin/help-center/video/${this.to.params.slug}`
        : this.to.params?.label
          ? `/dashboard/admin/help-center/category/${this.to.params.label}`
          : '/dashboard/admin/help-center'

      return Vue.h('a', { href: target }, this.$slots.default?.())
    },
  }
  app.component('RouterLink', link)
  app.component('VBtn', link)
  app.component('HelpVideoPreview', {
    props: ['provider', 'url', 'title', 'thumbnail'],
    setup(props) {
      return vm.runInNewContext(`${previewDescriptor.scriptSetup.content}
        ;({ embedUrl, props })`, {
        defineProps: () => props,
        computed: Vue.computed,
        URL,
      })
    },
    render: Vue.compile(previewDescriptor.template.content),
  })

  return renderToString(app)
}

test('video detail compiles with a single-column mobile layout and no pagination', () => {
  assert.deepEqual(errors, [])
  assert.ok(compileScript(descriptor, { id: 'help-video-detail' }).content)
  assert.deepEqual(compileStyle({
    source: descriptor.styles[0].content,
    id: 'data-v-help-video-detail',
    scoped: true,
  }).errors, [])
  assert.match(descriptor.styles[0].content, /@media \(max-width: 1023px\)[\s\S]*grid-template-columns: minmax\(0, 1fr\)/)
  assert.doesNotMatch(descriptor.template.content, /<VPagination\b/)
  assert.match(descriptor.customBlocks.find(block => block.type === 'route').content, /subject: help-center/)
})

test('video detail renders the player, description, breadcrumbs, back link and related links', async () => {
  let requestedSlug
  const state = createHarness(async slug => {
    requestedSlug = slug
    return payload()
  })
  await state.fetchData()
  const html = await render(state)

  assert.equal(requestedSlug, 'first-tutorial')
  assert.match(html, /<iframe/)
  assert.match(html, /https:\/\/www.youtube.com\/embed\/dQw4w9WgXcQ/)
  assert.match(html, /A step-by-step guide/)
  assert.match(html, /Fler videor i Overview/)
  assert.match(html, /href="\/dashboard\/admin\/help-center\/category\/summary"/)
  assert.match(html, /href="\/dashboard\/admin\/help-center\/video\/second-tutorial"/)
  assert.match(html, /Tillbaka/)
  assert.equal((html.match(/<iframe/g) || []).length, 1)
})

test('detail reuses the existing Vimeo and MP4 playback support', async () => {
  for (const [provider, url, expected] of [
    ['vimeo', 'https://vimeo.com/123456', /https:\/\/player.vimeo.com\/video\/123456/],
    ['mp4', 'https://example.test/tutorial.mp4', /<video[^>]*src="https:\/\/example.test\/tutorial.mp4"/],
  ]) {
    const state = createHarness(async () => payload(provider, url))
    await state.fetchData()
    assert.match(await render(state), expected)
  }
})

test('changing a related-video slug fetches and displays the new detail', async () => {
  const requests = []
  const state = createHarness(async slug => {
    requests.push(slug)
    const response = payload()
    response.helpVideo.slug = slug
    return response
  })
  await state.fetchData()
  state.route.params.slug = 'second-tutorial'
  await Vue.nextTick()
  await new Promise(resolve => setImmediate(resolve))

  assert.equal(requests.at(-1), 'second-tutorial')
  assert.equal(state.errorMessage.value, '')
  assert.equal(state.helpVideo.value.slug, 'second-tutorial')
})

test('missing videos show a persistent error and clear the previous player', async () => {
  let fail = false
  const state = createHarness(async () => {
    if (fail) throw { response: { data: { message: 'Videon hittades inte.' } } }
    return payload()
  })
  await state.fetchData()
  fail = true
  await state.fetchData()

  assert.equal(state.helpVideo.value, null)
  assert.equal(state.hasLoaded.value, false)
  assert.equal(state.isRequestOngoing.value, false)
  const html = await render(state)
  assert.match(html, /Videon hittades inte/)
  assert.doesNotMatch(html, /<iframe|<video/)
})

test('an older request cannot replace the latest video detail', async () => {
  let resolveFirst
  const firstResponse = new Promise(resolve => { resolveFirst = resolve })
  let calls = 0
  const latest = payload()
  latest.helpVideo.title = 'Latest tutorial'
  const state = createHarness(() => ++calls === 1 ? firstResponse : Promise.resolve(latest))
  const firstRequest = state.fetchData()
  await state.fetchData()
  resolveFirst(payload())
  await firstRequest

  assert.equal(state.helpVideo.value.title, 'Latest tutorial')
})

test('a video without related videos shows the explicit related empty state', async () => {
  const response = payload()
  response.relatedHelpVideos = []
  const state = createHarness(async () => response)
  await state.fetchData()

  assert.match(await render(state), /Inga fler videor i den här kategorin/)
})
