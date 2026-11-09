<script setup>

import { useDisplay } from 'vuetify'
import { useHelpCenterStores } from '@/stores/useHelpCenter'
import { getHelpVideoThumbnailUrl } from '@/@core/utils/helpVideo'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'
import HelpVideoPreview from '@/components/common/HelpVideoPreview.vue'

const helpCenterStores = useHelpCenterStores()

const route = useRoute()
const { mdAndDown } = useDisplay()

const sectionEl = ref(null)
const helpVideo = ref(null)
const relatedHelpVideos = ref([])
const isRequestOngoing = ref(false)
const hasLoaded = ref(false)
const errorMessage = ref('')
const advisor = ref({ show: false, type: '', message: '' })
const snackbarLocation = computed(() => mdAndDown.value ? '' : 'top end')

let requestId = 0

const notify = (type, message) => { advisor.value = { show: true, type, message } }

const fetchData = async () => {
  const id = ++requestId

  isRequestOngoing.value = true
  hasLoaded.value = false
  errorMessage.value = ''
  helpVideo.value = null
  relatedHelpVideos.value = []
  try {
    const helpVideoData = await helpCenterStores.fetchVideo(route.params.slug)

    if (id !== requestId) return
    helpVideo.value = helpVideoData.helpVideo
    relatedHelpVideos.value = helpVideoData.relatedHelpVideos
    hasLoaded.value = true
  } catch (error) {
    if (id === requestId) {
      errorMessage.value = error.response?.data?.message || error.message || 'Kunde inte hämta videon.'
      notify('error', errorMessage.value)
    }
  } finally {
    if (id === requestId) isRequestOngoing.value = false
  }
}

watch(() => route.params.slug, fetchData)

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value
  if (!el) return
  el.style.minHeight = `${Math.max(0, window.innerHeight - el.getBoundingClientRect().top - 25)}px`
}

onMounted(() => {
  fetchData()
  resizeSectionToRemainingViewport()
  window.addEventListener('resize', resizeSectionToRemainingViewport)
})
onBeforeUnmount(() => {
  requestId++
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
})
</script>

<template>
  <section
    ref="sectionEl"
    class="page-section"
  >
    <LoadingOverlay :is-loading="isRequestOngoing" />

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
      <VCardText :class="mdAndDown ? 'pa-6' : 'pa-4'">
         <div class="d-flex w-auto pb-6">
        <VBtn
            :to="{ name: 'dashboard-admin-help-center-category-label', params: { label: helpVideo.category.label } }"
            class="btn-light w-auto align-self-start"
            :class="mdAndDown ? 'd-flex' : 'd-none'"
          >
            <VIcon
              icon="custom-return"
              size="24"
            />
            Tillbaka
          </VBtn>
        </div>

        <template v-if="hasLoaded && helpVideo">
          <nav
            class="help-video-breadcrumbs"
            aria-label="Brödsmulor"
          >
            <RouterLink :to="{ name: 'dashboard-admin-help-center' }">
              Hjälpcenter
            </RouterLink>
            <VIcon
              icon="custom-chevron-right"
              size="12"
              aria-hidden="true"
            />
            <RouterLink
              :to="{ name: 'dashboard-admin-help-center-category-label', params: { label: helpVideo.category.label } }"
            >
              {{ helpVideo.category.name }}
            </RouterLink>
            <VIcon
              icon="custom-chevron-right"
              size="12"
              aria-hidden="true"
            />
            <span
              class="help-video-current"
              aria-current="page"
            >{{ helpVideo.title }}</span>
          </nav>

          <div class="help-video-detail-grid">
            <div class="help-video-main">
              <HelpVideoPreview
                :key="helpVideo.id"
                :provider="helpVideo.provider"
                :url="helpVideo.video_url"
                :title="helpVideo.title"
                :thumbnail="getHelpVideoThumbnailUrl(helpVideo) || ''"
                class="help-video-player"
              />
              <div class="d-flex flex-column gap-1">
                <h1 class="help-video-heading">
                  {{ helpVideo.title }}
                </h1>
                <p
                  v-if="helpVideo.description"
                  class="help-video-description"
                >
                  {{ helpVideo.description }}
                </p>
              </div>
              <VBtn
                :to="{ name: 'dashboard-admin-help-center-category-label', params: { label: helpVideo.category.label } }"
                class="btn-light w-auto align-self-start"
                :class="mdAndDown ? 'd-none' : 'd-flex'"
              >
                <VIcon
                  icon="custom-return"
                  size="24"
                />
                Tillbaka
              </VBtn>
            </div>

            <aside
              class="help-video-related"
              aria-labelledby="related-help-videos-title"
            >
              <h2
                id="related-help-videos-title"
                class="help-video-related-heading"
              >
                Fler videor i {{ helpVideo.category.name }}
              </h2>
              <RouterLink
                v-for="relatedHelpVideo in relatedHelpVideos"
                :key="relatedHelpVideo.id"
                :to="{ name: 'dashboard-admin-help-center-video-slug', params: { slug: relatedHelpVideo.slug } }"
                class="help-video-related-link"
              >
                <VImg
                  v-if="getHelpVideoThumbnailUrl(relatedHelpVideo)"
                  :src="getHelpVideoThumbnailUrl(relatedHelpVideo)"
                  alt=""
                  :aspect-ratio="16 / 9"
                  cover
                  class="help-video-related-thumbnail"
                >
                  <template #error>
                    <div class="help-video-image-placeholder">
                      <VIcon
                        icon="tabler-photo"
                        size="24"
                      />
                    </div>
                  </template>
                </VImg>
                <div
                  v-else
                  class="help-video-related-thumbnail help-video-image-placeholder"
                >
                  <VIcon
                    icon="tabler-photo"
                    size="24"
                  />
                </div>
                <div class="help-video-related-content">
                  <p class="help-video-related-category">
                    {{ helpVideo.category.name }}
                  </p>
                  <h3 class="help-video-related-title">
                    {{ relatedHelpVideo.title }}
                  </h3>
                </div>
              </RouterLink>
              <p
                v-if="!relatedHelpVideos.length"
                class="help-video-related-empty"
              >
                Inga fler videor i den här kategorin.
              </p>
            </aside>
          </div>
        </template>

        <div
          v-else-if="!isRequestOngoing && errorMessage"
          class="help-video-error"
        >
          <VAlert
            type="error"
            variant="tonal"
          >
            {{ errorMessage }}
          </VAlert>
          <VBtn
            :to="{ name: 'dashboard-admin-help-center' }"
            class="btn-light"
          >
            <VIcon
              icon="custom-return"
              size="24"
            />
            Tillbaka till hjälpcenter
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </section>
</template>

<style scoped>

.help-video-breadcrumbs {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 24px;
  font-weight: 400;
  font-size: 12px;
  line-height: 100%;
  letter-spacing: 0;
  color: #6E9383;
}

.help-video-breadcrumbs a {
  color: #6E9383;
  text-decoration: none;
}

.help-video-breadcrumbs a:hover,
.help-video-breadcrumbs a:focus-visible {
  text-decoration: underline;
}

.help-video-current {
  color: #1C2925;
  font-weight: 700;
  font-style: Bold;
  font-size: 12px;
  line-height: 100%;
  letter-spacing: 0;
  overflow-wrap: anywhere;
}

.help-video-detail-grid {
  display: grid;
  grid-template-columns: minmax(0, 2.5fr) minmax(240px, 1fr);
  align-items: start;
  gap: 32px;
}

.help-video-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.help-video-player {
  overflow: hidden;
  border-radius: 16px;
  background: #F5F8F6;
}

.help-video-player :deep(.help-video-preview) {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 9;
}

.help-video-heading {
  font-weight: 800;
  font-size: 22px;
  line-height: 100%;
  letter-spacing: 0;
  color: #1C2925;
  overflow-wrap: anywhere;
}

.help-video-description {
  margin: 0;
  font-weight: 400;
  font-size: 14.5px;
  line-height: 23.2px;
  letter-spacing: 0;
  color: #878787;
  white-space: pre-line;
  overflow-wrap: anywhere;
}

.help-video-related-heading {
  padding-bottom: 16px;
  margin: 0;
  font-weight: 700;
  font-size: 12px;
  line-height: 100%;
  letter-spacing: 0px;
  color: #1C2925;
  overflow-wrap: anywhere;
}

.help-video-related-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 8px;
  border-top: 1px solid #E7E7E7;
  color: #1C2925;
  text-decoration: none;
}

.help-video-related-link:hover,
.help-video-related-link:focus-visible {
  background: #F5F8F6;
}

.help-video-related-thumbnail {
  flex: none;
  width: 150px;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  border-radius: 8px;
  background: #F5F8F6;
}

.help-video-image-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #878787;
}

.help-video-related-content {
  min-width: 0;
}

.help-video-related-category {
  margin: 0 0 4px;
  font-weight: 400;
  font-size: 10px;
  line-height: 100%;
  letter-spacing: 0.44px;
  color: #878787;
}

.help-video-related-title {
  margin: 0;
  font-weight: 700;
  font-size: 14px;
  line-height: 19.6px;
  letter-spacing: 0;
  color: #1C2925;
  overflow-wrap: anywhere;
}

.help-video-related-empty {
  padding-top: 16px;
  border-top: 1px solid #E7E7E7;
  color: #878787;
  font-size: 14px;
}

@media (max-width: 1023px) {
  .help-video-detail-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 32px;
  }

  .help-video-breadcrumbs {
    margin-bottom: 24px;
  }

  .help-video-heading {
    font-size: 20px;
  }
}
</style>

<route lang="yaml">
meta:
  action: view
  subject: help-center
</route>
