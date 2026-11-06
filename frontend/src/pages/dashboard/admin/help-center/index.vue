<script setup>
import { useDisplay } from 'vuetify'
import { themeConfig } from '@themeConfig'
import { useHelpCenterStores } from '@/stores/useHelpCenter'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'

const helpCenterStores = useHelpCenterStores()
const { mdAndDown } = useDisplay()
const { width: windowWidth } = useWindowSize()

const emitter = inject('emitter')
const sectionEl = ref(null)
const helpCategories = ref([])
const isRequestOngoing = ref(false)
const hasLoaded = ref(false)
const advisor = ref({ show: false, type: '', message: '' })
const snackbarLocation = computed(() => mdAndDown.value ? '' : 'top end')

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
  try {
    const helpCategoriesData = await helpCenterStores.fetchCategories()

    if (id !== requestId) return
    helpCategories.value = helpCategoriesData
    hasLoaded.value = true
  } catch (error) {
    if (id === requestId) notify('error', error.response?.data?.message || error.message || 'Kunde inte hämta kategorier.')
  } finally {
    if (id === requestId) isRequestOngoing.value = false
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
        class="d-flex flex-column gap-2 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
        <div class="align-center font-blauer">
          <h2>Hjälpcenter</h2>
        </div>
        <p class="help-center-description mb-0">
          Guider och videor för att komma igång med Bilflogg
        </p>
      </VCardTitle>
      
      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />
      
      <VCardText :class="mdAndDown ? 'pa-6' : 'pa-4'">
        <h2
          class="help-center-section-title"
          :class="mdAndDown ? 'mb-6' : 'mb-4'"
        >
          Kategorier
        </h2>
        <div
          v-if="helpCategories.length"
          class="category-grid"
        >
          <RouterLink
            v-for="helpCategory in helpCategories"
            :key="helpCategory.id"
            :to="{ name: 'dashboard-admin-help-center-category-label', params: { label: helpCategory.label } }"
            class="category-card"
          >
            <div class="category-icon">
              <VImg
                v-if="getHelpCategoryIconUrl(helpCategory)"
                :src="getHelpCategoryIconUrl(helpCategory)"
                class="category-icon-image"
                alt=""
                width="24"
                height="24"
              />
              <VIcon
                v-else
                icon="tabler-help"
                size="24"
              />
            </div>
            <h3 class="category-name">
              {{ helpCategory.name }}
            </h3>
            <p class="category-count">
              {{ helpCategory.videos_count }} {{ helpCategory.videos_count === 1 ? 'video' : 'videor' }}
            </p>
          </RouterLink>
        </div>
        <div
          v-else-if="!isRequestOngoing && hasLoaded"
          class="empty-state"
        >
          <VIcon
            :size="mdAndDown ? 80 : 120"
            icon="tabler-help"
          />
          <div class="empty-state-content">
            <div class="empty-state-title">
              Inga kategorier hittades.
            </div>
            <div class="empty-state-text">
              Här kommer alla hjälpkategorier att listas.
            </div>
          </div>
        </div>
      </VCardText>
    </VCard>
  </section>
</template>

<style scoped>
.help-center-description {
  font-weight: 400;
  font-size: 14px;
  line-height: 100%;
  letter-spacing: 0;
  color: #878787;
}

.help-center-section-title {
  font-weight: 600;
  font-size: 14px;
  line-height: 21px;
  letter-spacing: 0;
  color: #454545;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.category-card {
  display: block;
  text-decoration: none;
  position: relative;
  overflow: hidden;
  padding: 22px;
  border: 1px solid #E7E7E7;
  border-radius: 16px;
  background: linear-gradient(90deg, #F5F8F6 30%, rgba(245, 248, 246, 0.9) 60%, rgba(245, 248, 246, 0) 100%);
}

.category-card:focus-visible {
  outline: 2px solid #20332f;
  outline-offset: 3px;
}

.category-card::before {
  position: absolute;
  background: url('@/assets/images/checker.png') right center / auto 100% no-repeat;
  content: '';
  inset: 0;
  mask-image: linear-gradient(to right, transparent 35%, #000);
  pointer-events: none;
}

.category-card > * {
  position: relative;
}

.category-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  margin-bottom: 16px;
  border-radius: 12px;
  background: #1C2925;
  color: #FFFFFF;
}

.category-name {
  margin-bottom: 4px;
  color: #20332f;
  font-size: 16px;
  font-weight: 600;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.category-icon-image {
  flex: none;
  filter: brightness(0) invert(1);
}

.category-count {
  margin: 0;
  color: #8c9391;
  font-size: 14px;
  line-height: 1.5;
}

@media (max-width: 959px) {
  .category-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 599px) {
  .category-grid {
    gap: 16px;
  }

  .category-card {
    padding: 20px;
  }
}
</style>

<route lang="yaml">
meta:
  action: view
  subject: help-center
</route>
