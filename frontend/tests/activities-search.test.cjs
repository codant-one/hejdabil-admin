const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { test } = require('node:test')
const vm = require('node:vm')
const Vue = require('vue')
const { parse, compileScript } = require('@vue/compiler-sfc')

const filename = path.resolve(__dirname, '..', 'src', 'pages', 'dashboard', 'activities', 'index.vue')
const { descriptor, errors } = parse(readFileSync(filename, 'utf8'), { filename })
const script = descriptor.scriptSetup.content.replace(/^import .+$/gm, '')

test('activity search starts on page one regardless of the previous page', async () => {
  assert.deepEqual(errors, [])
  assert.ok(compileScript(descriptor, { id: 'activities-search' }).content)

  const rows = Array.from({ length: 40 }, (_, i) => ({
    id: i + 1,
    title: i < 10 || i === 34 ? 'Matching activity' : 'Other activity',
  }))
  const requests = []
  const store = {
    async fetchActivities({ search, page, limit }) {
      requests.push({ search, page })
      const matches = rows.filter(row => !search || row.title.includes(search))
      this.getActivities = matches.slice((page - 1) * limit, page * limit)
      this.last_page = Math.ceil(matches.length / limit)
      this.activitiesTotalCount = matches.length
    },
  }
  const scope = Vue.effectScope()

  try {
    const state = scope.run(() => vm.runInNewContext(`${script}
      ;({ activities, currentPage, searchQuery, totalActivities })`, {
      ...Vue,
      inject: () => ({ on() {} }),
      useActivitiesStore: () => store,
      useWindowSize: () => ({ width: Vue.ref(1200) }),
      useDisplay: () => ({ mdAndDown: Vue.ref(false) }),
      canWithPlan: () => true,
      localStorage: { getItem: () => null },
      onMounted: () => {},
      onBeforeUnmount: () => {},
    }))

    for (const page of [2, 4]) {
      state.currentPage.value = page
      await Vue.nextTick()
      assert.deepEqual(requests.at(-1), { search: '', page })

      const previousRequestCount = requests.length
      state.searchQuery.value = 'Matching'
      await Vue.nextTick()
      assert.deepEqual(requests.slice(previousRequestCount), [{ search: 'Matching', page: 1 }])
      assert.equal(state.currentPage.value, 1)
      assert.equal(state.totalActivities.value, 11)
      assert.equal(state.activities.value[0].id, 1)

      state.currentPage.value = 2
      await Vue.nextTick()
      assert.deepEqual(requests.at(-1), { search: 'Matching', page: 2 })
      assert.equal(state.activities.value[0].id, 35)

      state.searchQuery.value = null
      await Vue.nextTick()
      assert.deepEqual(requests.at(-1), { search: null, page: 1 })
      assert.equal(state.totalActivities.value, 40)
      state.searchQuery.value = ''
      await Vue.nextTick()
    }
  } finally {
    scope.stop()
  }
})
