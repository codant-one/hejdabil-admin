<script setup>
import { useDisplay } from 'vuetify'
import { themeConfig } from '@themeConfig'
import { useHelpCenterStores } from '@/stores/useHelpCenter'
import { getHelpVideoThumbnailUrl } from '@/@core/utils/helpVideo'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'

const helpCenterStores = useHelpCenterStores()

const route = useRoute()
const { mdAndDown } = useDisplay()
const emitter = inject('emitter')

const sectionEl = ref(null)
const helpCategory = ref(null)
const helpCategories = ref([])
const helpVideos = ref([])
const searchQuery = ref('')
const isRequestOngoing = ref(false)
const hasLoaded = ref(false)
const advisor = ref({ show: false, type: '', message: '' })
const snackbarLocation = computed(() => mdAndDown.value ? '' : 'top end')

const sortOptions = [
  { title: 'Utvalda', value: 'featured' },
  { title: 'Mest populära', value: 'popular' },
  { title: 'Senaste', value: 'latest' },
]

let requestId = 0

const notify = (type, message) => { advisor.value = { show: true, type, message } }

const getHelpCategoryIconUrl = helpCategory => {
  const icon = typeof helpCategory.icon === 'string' ? helpCategory.icon.trim() : ''

  return icon && icon !== 'null' && icon !== 'undefined'
    ? `${themeConfig.settings.urlStorage}${icon}`
    : null
}

const fetchData = async () => {
  const id = ++requestId

  isRequestOngoing.value = true
  hasLoaded.value = false
  try {
    const helpCategoryData = await helpCenterStores.fetchCategory(route.params.label, {
      search: searchQuery.value,
    })

    if (id !== requestId) return
    helpCategory.value = helpCategoryData.helpCategory
    helpCategories.value = helpCategoryData.categories
    helpVideos.value = helpCategoryData.helpVideos
    hasLoaded.value = true
  } catch (error) {
    if (id === requestId) {
      helpCategory.value = null
      helpVideos.value = []
      notify('error', error.response?.data?.message || error.message || 'Kunde inte hämta videor.')
    }
  } finally {
    if (id === requestId) isRequestOngoing.value = false
  }
}

watch([searchQuery, () => route.params.label], fetchData)

const cleanFilters = () => {
  if (searchQuery.value) {
    searchQuery.value = ''
  } else fetchData()
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
  emitter?.on('cleanFilters', cleanFilters)
})
onBeforeUnmount(() => {
  requestId++
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
  emitter?.off('cleanFilters', cleanFilters)
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
      <VCardTitle
        class="d-flex flex-column gap-2"
        :class="mdAndDown ? 'pa-6' : 'pa-4'"
      >
        <div class="font-blauer">
          <h2>Hjälpcenter</h2>
        </div>
        <p class="help-center-description mb-0">
          Guider och videor för att komma igång med Bilflogg
        </p>
      </VCardTitle>

      <VDivider :class="mdAndDown ? 'm-0' : 'mt-2 mx-4'" />
      
      <VCardText
        class="d-flex align-center gap-1"
        :class="$vuetify.display.mdAndDown ? 'p-6 pb-0' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField
            v-model="searchQuery"
            placeholder="Sök"
            clearable
          />
        </div>
      </VCardText>
      <VCardText :class="mdAndDown ? 'pa-6' : 'pa-4'">
        <nav
          class="help-category-links"
          aria-label="Hjälpkategorier"
        >
          <RouterLink
            :to="{ name: 'dashboard-admin-help-center' }"
            class="help-category-link"
          >
            <VIcon
              icon="custom-table"
              class="help-category-font-icon"
              size="24"
            />
            Alla
          </RouterLink>
          <RouterLink
            v-for="helpCategoryItem in helpCategories"
            :key="helpCategoryItem.id"
            :to="{ name: 'dashboard-admin-help-center-category-label', params: { label: helpCategoryItem.label } }"
            class="help-category-link"
            :class="{ 'help-category-link-active': helpCategoryItem.label === route.params.label }"
            :aria-current="helpCategoryItem.label === route.params.label ? 'page' : undefined"
          >
            <span
              v-if="getHelpCategoryIconUrl(helpCategoryItem)"
              :style="{ maskImage: `url('${getHelpCategoryIconUrl(helpCategoryItem)}')`, WebkitMaskImage: `url('${getHelpCategoryIconUrl(helpCategoryItem)}')` }"
              class="help-category-icon"
              aria-hidden="true"
            />
            <VIcon
              v-else
              icon="tabler-help"
              class="help-category-font-icon"
              size="24"
            />
            {{ helpCategoryItem.name }}
          </RouterLink>
        </nav>
        <div
          v-if="hasLoaded && helpVideos.length"
          class="help-video-grid"
        >
          <RouterLink
            v-for="helpVideo in helpVideos"
            :key="helpVideo.id"
            :to="{ name: 'dashboard-admin-help-center-video-slug', params: { slug: helpVideo.slug } }"
            class="help-video-card"
          >
            <VImg
              v-if="getHelpVideoThumbnailUrl(helpVideo)"
              :src="getHelpVideoThumbnailUrl(helpVideo)"
              :alt="helpVideo.title"
              :aspect-ratio="16 / 9"
              cover
              class="help-video-thumbnail"
            >
              <template #error>
                <div class="help-video-placeholder">
                  <VIcon
                    icon="tabler-photo"
                    size="32"
                  />
                  <span>Ingen miniatyrbild</span>
                </div>
              </template>
            </VImg>
            <div
              v-else
              class="help-video-thumbnail help-video-placeholder"
            >
              <VIcon
                icon="tabler-photo"
                size="32"
              />
              <span>Ingen miniatyrbild</span>
            </div>
            <div class="help-video-content">
              <p class="help-video-category">
                {{ helpCategory.name }}
              </p>
              <h3 class="help-video-title">
                {{ helpVideo.title }}
              </h3>
            </div>
          </RouterLink>
        </div>
        <div
          v-else-if="!isRequestOngoing && hasLoaded"
          class="empty-state"
        >
          <VIcon
            :size="mdAndDown ? 80 : 120"
            icon="tabler-video"
          />
          <div class="empty-state-content">
            <div class="empty-state-title">
              Inga videor hittades.
            </div>
            <div class="empty-state-text">
              Prova en annan sökning.
            </div>
          </div>
        </div>
      </VCardText>
    </VCard>
  </section>
</template>

<style scoped>
.help-center-description {
  color: #878787;
  font-size: 14px;
  font-weight: 400;
  white-space: normal;
}

.help-video-filters {
  display: grid;
  grid-template-columns: minmax(0, 400px) minmax(180px, 240px);
  gap: 16px;
  margin-bottom: 24px;
}

.help-category-links {
  display: flex;
  overflow-x: auto;
  gap: 8px;
  padding-bottom: 8px;
  margin-bottom: 16px;
}

.help-category-link {
  display: flex;
  flex: none;
  align-items: center;
  gap: 8px;
  height: 48px;
  padding: 10px 16px;
  border: 1px solid #6E9383;
  border-radius: 64px;
  font-weight: 500;
  font-size: 14px;
  line-height: 16px;
  letter-spacing: 0;
  color: #6E9383;
  text-decoration: none;
  white-space: nowrap;
}

.help-category-link-active,
.help-category-link:hover,
.help-category-link:focus-visible {
  background: #1C2925;
  color: #fff;
}

.help-category-icon {
  flex: none;
  width: 24px;
  height: 24px;
  background-color: currentColor;
  mask-size: contain;
  mask-position: center;
  mask-repeat: no-repeat;
  -webkit-mask-size: contain;
  -webkit-mask-position: center;
  -webkit-mask-repeat: no-repeat;
}

.help-category-link-active .help-category-icon,
.help-category-link:hover .help-category-icon,
.help-category-link:focus-visible .help-category-icon {
  background-color: #57F287;
}

.help-category-font-icon {
  color: #6E9383;
}

.help-category-link-active .help-category-font-icon,
.help-category-link:hover .help-category-font-icon,
.help-category-link:focus-visible .help-category-font-icon {
  color: #57F287;
}

.help-video-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.help-video-card {
  display: block;
  text-decoration: none;
  overflow: hidden;
  border: 1px solid #E7E7E7;
  border-radius: 16px;
  background: #F5F8F6;
}

/*.help-video-card:hover,
.help-video-card:focus-visible {
  border-color: #6E9383;
}*/

.help-video-thumbnail {
  width: 100%;
  aspect-ratio: 16 / 9;
  background: #eef0ef;
}

.help-video-placeholder {
  display: flex;
  width: 100%;
  height: 100%;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 8px;
  color: #878787;
}

.help-video-content {
  padding: 16px;
}

.help-video-category {
  margin-bottom: 4px;
  font-weight: 400;
  font-size: 11px;
  line-height: 100%;
  letter-spacing: 0.44px;
  color: #878787;
}

.help-video-title {
  color: #1C2925;
  font-weight: 700;
  font-size: 14px;
  line-height: 19.6px;
  letter-spacing: 0%;
  overflow-wrap: anywhere;
}

@media (max-width: 1023px) {
  .help-category-links {
    scrollbar-width: none;
  }

  .help-category-links::-webkit-scrollbar {
    display: none;
  }
}

@media (max-width: 1023px) {
  .help-video-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 16px;
  }
}

@media (max-width: 599px) {
  .help-video-filters {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>

<route lang="yaml">
meta:
  action: view
  subject: help-center
</route>
