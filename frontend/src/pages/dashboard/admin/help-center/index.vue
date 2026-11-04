<script setup>
import { useHelpStores } from '@/stores/useHelp'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'
import AddNewHelpVideoDrawer from './AddNewHelpVideoDrawer.vue'
import HelpVideoPreview from '@/components/common/HelpVideoPreview.vue'

const store = useHelpStores()
const videos = ref([])
const modules = ref([])
const states = ref([])
const search = ref('')
const stateId = ref(null)
const page = ref(1)
const totalPages = ref(1)
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const drawer = ref(false)
const selected = ref({})
const confirmation = ref(null)
const previewVideo = ref(null)
const advisor = ref({ show: false, type: '', message: '' })
let requestId = 0

const notify = (type, message) => { advisor.value = { show: true, type, message } }

const thumbnailFor = video => {
  if (video.thumbnail_url) return video.thumbnail_url

  const youtubeId = video.provider === 'youtube'
    ? video.embed_url?.match(/^https:\/\/www\.youtube\.com\/embed\/([a-zA-Z0-9_-]{11})$/)?.[1]
    : null

  return youtubeId ? `https://i.ytimg.com/vi/${youtubeId}/hqdefault.jpg` : undefined
}

const fetchData = async () => {
  const id = ++requestId

  loading.value = true
  try {
    const data = await store.fetchVideos({
      search: search.value, state_id: stateId.value,
      sort: 'newest', page: page.value, limit: 10,
    })

    if (id !== requestId) return
    videos.value = data.videos.data
    modules.value = data.modules
    states.value = data.states
    totalPages.value = data.videos.last_page
    total.value = data.videos.total
    if (page.value > totalPages.value) page.value = totalPages.value
  } catch (error) {
    if (id === requestId) notify('error', error.message || 'Kunde inte hämta videor.')
  } finally {
    if (id === requestId) loading.value = false
  }
}

watch(stateId, () => {
  if (page.value !== 1) page.value = 1
  else fetchData()
})
watch(page, fetchData)
onMounted(fetchData)
onBeforeUnmount(() => { requestId++ })

const searchVideos = () => {
  if (page.value !== 1) page.value = 1
  else fetchData()
}

const edit = video => {
  selected.value = { ...video }
  drawer.value = true
}

const submit = async (payload, method) => {
  saving.value = true
  try {
    if (method === 'update') {
      payload.data.append('_method', 'PUT')
      await store.updateVideo(payload)
    } else await store.addVideo(payload.data)
    drawer.value = false
    notify('success', 'Videon har sparats.')
    await fetchData()
  } catch (error) {
    notify('error', error.message || 'Kunde inte spara.')
  } finally {
    saving.value = false
  }
}

const confirmAction = async () => {
  const { action, video } = confirmation.value

  saving.value = true
  try {
    if (action === 'delete') await store.deleteVideo(video.id)
    else await store.updateState(video.id)
    confirmation.value = null
    notify('success', action === 'delete' ? 'Videon har tagits bort.' : 'Videon har sparats.')
    await fetchData()
  } catch (error) {
    notify('error', error.message || 'Åtgärden misslyckades.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section>
    <LoadingOverlay :is-loading="loading" />
    <VSnackbar
      v-model="advisor.show"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
      :timeout="3000"
    >
      {{ advisor.message }}
    </VSnackbar>
    <VCard class="card-fill">
      <VCardTitle class="d-flex align-center flex-wrap gap-4 pa-6 font-blauer">
        <h2>Hjälpcenter</h2>
        <VSpacer />
        <VBtn
          v-if="$can('create', 'help')"
          class="btn-gradient"
          @click="edit({})"
        >
          <VIcon
            icon="custom-plus"
            size="24"
          />Ny video
        </VBtn>
      </VCardTitle>
      <VDivider />
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            md="8"
          >
            <VTextField
              v-model="search"
              placeholder="Sök"
              clearable
              @keyup.enter="searchVideos"
              @click:clear="search = ''; searchVideos()"
            >
              <template #append-inner>
                <VBtn
                  variant="text"
                  @click="searchVideos"
                >
                  Sök
                </VBtn>
              </template>
            </VTextField>
          </VCol>
          <VCol
            cols="12"
            md="4"
          >
            <VSelect
              v-model="stateId"
              :items="states"
              item-title="name"
              item-value="id"
              label="Status"
              clearable
            />
          </VCol>
        </VRow>
      </VCardText>
      <VTable class="px-4 pb-6">
        <thead>
          <tr>
            <th>ID</th><th>Video</th><th>Titel</th><th>Modul</th><th>Status</th><th>Åtgärder</th>
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
            <td>{{ video.title }}</td><td>{{ video.module.name }}</td>
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
              <VMenu v-if="$can('edit', 'help') || $can('delete', 'help')">
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
                    v-if="$can('edit', 'help')"
                    @click="edit(video)"
                  >
                    Redigera
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'help')"
                    @click="confirmation = { action: 'state', video }"
                  >
                    {{ video.state_id === 2 ? 'Inaktivera' : 'Aktivera' }}
                  </VListItem>
                  <VListItem
                    v-if="$can('delete', 'help')"
                    @click="confirmation = { action: 'delete', video }"
                  >
                    Ta bort
                  </VListItem>
                </VList>
              </VMenu>
            </td>
          </tr>
          <tr v-if="!videos.length">
            <td colspan="6">
              Inga videor hittades.
            </td>
          </tr>
        </tbody>
      </VTable>
      <VCardText class="d-flex align-center flex-wrap gap-4">
        <span>{{ total }} resultat</span><VSpacer />
        <VPagination
          v-model="page"
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
      :modules="modules"
      :states="states"
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
      <VCard v-if="confirmation">
        <VCardTitle>{{ confirmation.action === 'delete' ? 'Ta bort video' : 'Ändra status' }}</VCardTitle>
        <VCardText>Bekräfta åtgärden för <strong>{{ confirmation.video.title }}</strong>?</VCardText>
        <VCardActions class="justify-end gap-3">
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
            Acceptera
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<style scoped>
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
  subject: help
</route>