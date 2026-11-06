<script setup>

import { useDisplay } from 'vuetify'
import { useHelpVideosStores } from '@/stores/useHelpVideos'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'
import AddNewHelpVideoDrawer from './AddNewHelpVideoDrawer.vue'
import HelpVideoPreview from '@/components/common/HelpVideoPreview.vue'

const store = useHelpVideosStores()
const { mdAndDown } = useDisplay()
const { width: windowWidth } = useWindowSize()
const emitter = inject('emitter')
const sectionEl = ref(null)
const videos = ref([])
const categories = ref([])
const activeCategories = computed(() => categories.value.filter(category => category.state_id === 2))
const states = ref([])
const search = ref('')
const stateId = ref(null)
const categoryId = ref(null)
const rowPerPage = ref(10)
const page = ref(1)
const totalPages = ref(1)
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const drawer = ref(false)
const selected = ref({})
const confirmation = ref(null)
const previewVideo = ref(null)
const hasLoaded = ref(false)
const filtreraMobile = ref(false)
const isMobileActionDialogVisible = ref(false)
const advisor = ref({ show: false, type: '', message: '' })
const snackbarLocation = computed(() => mdAndDown.value ? '' : 'top end')
let requestId = 0

const notify = (type, message) => { advisor.value = { show: true, type, message } }

const thumbnailFor = video => {
  if (video.thumbnail_url) return video.thumbnail_url

  const youtubeId = video.provider === 'youtube'
    ? video.embed_url?.match(/^https:\/\/www\.youtube\.com\/embed\/([a-zA-Z0-9_-]{11})$/)?.[1]
    : null

  return youtubeId ? `https://i.ytimg.com/vi/${youtubeId}/hqdefault.jpg` : undefined
}

const fetchData = async (cleanFilters = false) => {
  if (cleanFilters === true) {
    search.value = ''
    stateId.value = null
    categoryId.value = null
    rowPerPage.value = 10
    page.value = 1
  }
  const id = ++requestId

  loading.value = true
  try {
    const data = await store.fetchHelpVideos({
      search: search.value, state_id: stateId.value,
      help_category_id: categoryId.value,
      orderByField: 'id', orderBy: 'desc',
      page: page.value, limit: rowPerPage.value,
    })

    if (id !== requestId) return
    videos.value = data.helpVideos.data
    categories.value = data.categories
    states.value = data.states
    totalPages.value = data.helpVideos.last_page
    total.value = data.helpVideosTotalCount
    hasLoaded.value = true
    if (page.value > totalPages.value) page.value = totalPages.value
  } catch (error) {
    if (id === requestId) notify('error', error.response?.data?.message || error.message || 'Kunde inte hämta videor.')
  } finally {
    if (id === requestId) loading.value = false
  }
}

watch([search, stateId, categoryId, rowPerPage], () => {
  if (page.value !== 1) page.value = 1
  else fetchData()
})
watch(page, () => fetchData())

const updateStateId = id => {
  stateId.value = stateId.value === id ? null : id
  filtreraMobile.value = false
}

const edit = (video = {}) => {
  selected.value = { ...video }
  isMobileActionDialogVisible.value = false
  drawer.value = true
}

const showConfirmation = (action, video) => {
  confirmation.value = { action, video: { ...video } }
  isMobileActionDialogVisible.value = false
}

const submit = async (payload, method) => {
  saving.value = true
  try {
    if (method === 'update') {
      payload.data.append('_method', 'PUT')
      await store.updateHelpVideo(payload)
    } else await store.addHelpVideo(payload.data)
    drawer.value = false
    notify('success', 'Videon har sparats.')
    await fetchData()
  } catch (error) {
    notify('error', error.response?.data?.message || error.message || 'Kunde inte spara.')
  } finally {
    saving.value = false
  }
}

const confirmAction = async () => {
  const { action, video } = confirmation.value

  saving.value = true
  try {
    if (action === 'delete') await store.deleteHelpVideo(video.id)
    else await store.updateState(video.id)
    confirmation.value = null
    notify('success', action === 'delete' ? 'Videon har tagits bort.' : 'Videon har sparats.')
    await fetchData()
  } catch (error) {
    notify('error', error.response?.data?.message || error.message || 'Åtgärden misslyckades.')
  } finally {
    saving.value = false
  }
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value
  if (!el) return
  el.style.minHeight = `${Math.max(0, window.innerHeight - el.getBoundingClientRect().top - 25)}px`
}

onMounted(() => {
  fetchData()
  resizeSectionToRemainingViewport()
  window.addEventListener('resize', resizeSectionToRemainingViewport)
  emitter?.on('cleanFilters', fetchData)
})
onBeforeUnmount(() => {
  requestId++
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
  emitter?.off('cleanFilters', fetchData)
})
</script>

<template>
  <section ref="sectionEl" class="page-section">
    <LoadingOverlay :is-loading="loading" />
    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
      :timeout="3000"
    >
      {{ advisor.message }}
    </VSnackbar>
    <VCard class="card-fill">
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4',
        ]"
      >
        <div class="align-center font-blauer">
          <h2>Videor <span v-if="hasLoaded">({{ total }})</span></h2>
        </div>
        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'" />
        <div class="d-flex gap-4">
          <VBtn
            v-if="$can('create', 'help-videos')"
            class="btn-gradient"
            block
            @click="edit()"
          >
            <VIcon icon="custom-plus" size="24" />
            Lägg till video
          </VBtn>
        </div>
      </VCardTitle>
      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />
      <VCardText
        class="d-flex align-center justify-space-between gap-1 flex-wrap"
        :class="$vuetify.display.mdAndDown ? 'p-6' : 'pa-4 gap-2'"
      >
        <div class="search">
          <VTextField v-model="search" placeholder="Sök" clearable />
        </div>
        <VSelect
          v-model="categoryId"
          :items="activeCategories"
          item-title="name"
          item-value="id"
          label="Kategori"
          clearable
          class="category-filter custom-select-hover"
        />
        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
        <VBtn
          v-if="$vuetify.display.mdAndDown"
          class="btn-white-2 px-3"
          @click="filtreraMobile = true"
        >
          <VIcon icon="custom-filter" size="24" />
          <span class="d-none d-md-block">Filtrera efter</span>
        </VBtn>
        <VMenu v-else>
          <template #activator="{ props }">
            <VBtn class="btn-white-2 px-2" v-bind="props">
              <VIcon icon="custom-filter" size="24" />
              <span class="d-none d-md-block">Filtrera efter</span>
            </VBtn>
          </template>
          <VList>
            <VListItem v-for="state in states" :key="state.id" @click="updateStateId(state.id)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="stateId === state.id"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                  />
                </VListItemAction>
              </template>
              <VListItemTitle>{{ state.name }}</VListItemTitle>
            </VListItem>
          </VList>
        </VMenu>
        <div v-if="!$vuetify.display.mdAndDown" class="d-flex align-center visa-select">
          <span class="text-no-wrap pr-4">Visa</span>
          <VSelect v-model="rowPerPage" class="custom-select-hover" :items="[10, 20, 30, 50]" />
        </div>
      </VCardText>
      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="videos.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <thead>
          <tr>
            <th>#ID</th><th>Video</th><th>Titel</th><th>Kategori</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="video in videos"
            :key="video.id"
          >
            <td>{{ video.id }}</td>
            <td class="py-2">
              <button
                type="button"
                class="video-thumbnail"
                :aria-label="`Förhandsvisa: ${video.title}`"
                @click="previewVideo = video"
              >
                <VImg
                  v-if="thumbnailFor(video)"
                  :src="thumbnailFor(video)"
                  alt=""
                  width="128"
                  height="72"
                  cover
                />
                <span class="video-thumbnail-play">
                  <VIcon
                    icon="tabler-player-play"
                    size="32"
                  />
                </span>
              </button>
            </td>
            <td class="text-wrap w-100">{{ video.title }}</td><td>{{ video.category.name }}</td>
            <td>
              <div
                class="status-chip"
                :class="video.state_id === 2 ? 'status-chip-success' : 'status-chip-error'"
              >
                {{ video.state.name }}
              </div>
            </td>
            <td>
              <VBtn
                variant="text"
                @click="previewVideo = video"
              >
                Förhandsvisa
              </VBtn>
              <VMenu v-if="$can('edit', 'help-videos') || $can('delete', 'help-videos')">
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    icon
                    class="btn-white"
                    aria-label="Åtgärder"
                  >
                    <VIcon icon="custom-dots-vertical" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                    v-if="$can('edit', 'help-videos')"
                    @click="edit(video)"
                  >
                    Redigera
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'help-videos')"
                    @click="showConfirmation('state', video)"
                  >
                    {{ video.state_id === 2 ? 'Inaktivera' : 'Aktivera' }}
                  </VListItem>
                  <VListItem
                    v-if="$can('delete', 'help-videos')"
                    @click="showConfirmation('delete', video)"
                  >
                    Ta bort
                  </VListItem>
                </VList>
              </VMenu>
            </td>
          </tr>
        </tbody>
      </VTable>

      <div
        v-if="!loading && hasLoaded && !videos.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon :size="$vuetify.display.mdAndDown ? 80 : 120" icon="tabler-video" />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga videor hittades.</div>
          <div class="empty-state-text">Här kommer alla dina skapade videor att listas.</div>
        </div>
        <VBtn v-if="$can('create', 'help-videos')" class="btn-ghost" @click="edit()">
          Lägg till video
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        v-if="videos.length && $vuetify.display.mdAndDown"
        class="expansion-panels pb-6 px-6"
      >
        <VExpansionPanel v-for="video in videos" :key="video.id">
          <VExpansionPanelTitle collapse-icon="custom-chevron-right" expand-icon="custom-chevron-down">
            <div class="d-flex flex-column gap-1">
              <span class="text-aqua">{{ video.title }}</span>
              <span class="text-neutral-3">{{ video.category.name }}</span>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6">
              <button
                type="button"
                class="video-thumbnail"
                :aria-label="`Förhandsvisa: ${video.title}`"
                @click="previewVideo = video"
              >
                <VImg
                  v-if="thumbnailFor(video)"
                  :src="thumbnailFor(video)"
                  alt=""
                  width="128"
                  height="72"
                  cover
                />
                <span class="video-thumbnail-play">
                  <VIcon icon="tabler-player-play" size="32" />
                </span>
              </button>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">#ID</div>
              <div class="expansion-panel-item-value">{{ video.id }}</div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Status</div>
              <div class="expansion-panel-item-value">
                <div
                  class="status-chip"
                  :class="video.state_id === 2 ? 'status-chip-success' : 'status-chip-error'"
                >
                  {{ video.state.name }}
                </div>
              </div>
            </div>
            <div class="d-flex justify-end flex-wrap gap-4">
              <VBtn class="btn-light" @click="previewVideo = video">Förhandsvisa</VBtn>
              <VBtn
                v-if="$can('edit', 'help-videos') || $can('delete', 'help-videos')"
                class="btn-light"
                icon
                aria-label="Åtgärder"
                @click="selected = video; isMobileActionDialogVisible = true"
              >
                <VIcon icon="custom-dots-vertical" size="24" />
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="videos.length"
        :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
        class="align-center flex-wrap gap-4 pt-0 px-6"
      >
        <span class="text-pagination-results">{{ total }} resultat</span>
        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
        <VPagination
          v-model="page"
          size="small"
          :length="totalPages"
          :total-visible="4"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>
    </VCard>
    <AddNewHelpVideoDrawer
      v-model:is-drawer-open="drawer"
      :video="selected"
      :modules="categories"
      :busy="saving"
      @video-data="submit"
    />
    <VDialog
      :model-value="!!previewVideo"
      max-width="960"
      @update:model-value="previewVideo = null"
    >
      <VCard v-if="previewVideo">
        <VCardTitle class="d-flex align-center text-wrap">
          {{ previewVideo.title }}
          <VSpacer />
          <VBtn
            icon
            class="btn-white"
            aria-label="Stäng"
            @click="previewVideo = null"
          >
            <VIcon icon="custom-close" />
          </VBtn>
        </VCardTitle>
        <VCardText>
          <HelpVideoPreview
            :provider="previewVideo.provider"
            :url="previewVideo.video_url"
            :title="previewVideo.title"
            :thumbnail="previewVideo.thumbnail_url || ''"
          />
        </VCardText>
      </VCard>
    </VDialog>
    <VDialog
      :model-value="!!confirmation"
      persistent
      class="action-dialog"
    >
      <VBtn icon class="btn-white close-btn" :disabled="saving" @click="confirmation = null">
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard v-if="confirmation">
        <VCardText class="dialog-title-box">
          <VIcon
            size="32"
            :icon="confirmation.action === 'delete' ? 'custom-filled-waste' : 'custom-warning-outlined'"
            class="action-icon"
          />
          <div class="dialog-title">
            {{ confirmation.action === 'delete' ? 'Radera video?' : confirmation.video.state_id === 1 ? 'Aktivera video' : 'Inaktivera video' }}
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          <template v-if="confirmation.action === 'delete'">
            Du är på väg att permanent radera <strong>{{ confirmation.video.title }}</strong>.
            Denna åtgärd kan inte ångras.
          </template>
          <template v-else>
            Är du säker att du vill {{ confirmation.video.state_id === 1 ? 'aktivera' : 'inaktivera' }}
            video <strong>{{ confirmation.video.title }}</strong>?
          </template>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            :disabled="saving"
            @click="confirmation = null"
          >
            Avbryt
          </VBtn>
          <VBtn
            class="btn-gradient"
            :loading="saving"
            @click="confirmAction"
          >
            {{ confirmation.action === 'delete' ? 'Ja, radera video' : 'Acceptera' }}
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="filtreraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem v-for="state in states" :key="state.id" @click="updateStateId(state.id)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="stateId === state.id"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
                />
              </VListItemAction>
            </template>
            <VListItemTitle>{{ state.name }}</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isMobileActionDialogVisible"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem v-if="$can('edit', 'help-videos')" @click="showConfirmation('state', selected)">
            <template #prepend>
              <VIcon :icon="selected.state_id === 1 ? 'custom-check-mark' : 'custom-cancel-2'" size="24" />
            </template>
            <VListItemTitle>{{ selected.state_id === 1 ? 'Aktivera' : 'Inaktivera' }}</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('edit', 'help-videos')" @click="edit(selected)">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('delete', 'help-videos')" @click="showConfirmation('delete', selected)">
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>

<style scoped>
.category-filter {
  min-width: 160px;
  max-width: 260px;
}

.video-thumbnail {
  position: relative;
  display: block;
  overflow: hidden;
  width: 128px;
  height: 72px;
  border-radius: 6px;
  background: #252525;
  cursor: pointer;
}

.video-thumbnail-play {
  position: absolute;
  display: grid;
  color: #fff;
  inset: 0;
  place-items: center;
  background: rgb(0 0 0 / 20%);
}
</style>

<route lang="yaml">
meta:
  action: view
  subject: help-videos
</route>