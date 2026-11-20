<script setup>
import { getHelpVideoThumbnailUrl } from '@/@core/utils/helpVideo'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { useDisplay } from 'vuetify'
import { useHelpVideosStores } from '@/stores/useHelpVideos'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'
import AddNewHelpVideoDrawer from './AddNewHelpVideoDrawer.vue'
import AddNewHelpVideoMobile from './AddNewHelpVideoMobile.vue'
import HelpVideoPreview from '@/components/common/HelpVideoPreview.vue'
import html2pdf from 'html2pdf.js'

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
const isAddNewHelpVideoDrawerVisible = ref(false)
const isAddNewHelpVideoMobileVisible = ref(false)
const addNewHelpVideoMobileRef = ref(null)
const helpVideoFormError = ref('')
const selected = ref({})
const confirmation = ref(null)
const isConfirmActiveDialogVisible = ref(false)
const selectedHelpVideo = ref({})
const previewVideo = ref(null)
const hasLoaded = ref(false)
const isFilterDialogVisible = ref(false)
const filtreraMobile = ref(false)
const exporteraMobile = ref(false)
const isMobileActionDialogVisible = ref(false)
const advisor = ref({ show: false, type: '', message: '' })
const snackbarLocation = computed(() => mdAndDown.value ? '' : 'top end')
let requestId = 0
const COMPANY_STORAGE_KEY = 'clients_company_snapshot'

const readCachedCompany = () => {
  try {
    const cached = localStorage.getItem(COMPANY_STORAGE_KEY)
    if (!cached) return {}

    const parsed = JSON.parse(cached)

    return parsed && typeof parsed === 'object' ? parsed : {}
  } catch {
    return {}
  }
}

const company = ref(readCachedCompany())

const notify = (type, message) => { advisor.value = { show: true, type, message } }

const thumbnailFor = getHelpVideoThumbnailUrl

const downloadPDF = async () => {
  exporteraMobile.value = false
  loading.value = true

  const pdfFontFamily = "'Gelion Regular', 'DM Sans', sans-serif"

  const escapeHtml = value => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')

  let pdfContainer = null

  try {
    const data = await store.fetchHelpVideos({
      limit: -1,
      orderByField: 'id',
      orderBy: 'desc',
    })

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }

    const rows = data.helpVideos.data.map(video => ({
      id: video.id,
      title: video.title,
      category: video.category?.name || '',
      status: video.state?.name || '',
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Videor',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      <tr style="height: 46px;">
        <td style="width: 10%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.id)}</td>
        <td style="width: 35%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: left; vertical-align: middle;">${escapeHtml(item.title)}</td>
        <td style="width: 35%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: left; vertical-align: middle;">${escapeHtml(item.category)}</td>
        <td style="width: 20%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: left; vertical-align: middle;">${escapeHtml(item.status)}</td>
      </tr>
    `).join('')

    pdfContainer = document.createElement('div')
    pdfContainer.innerHTML = `
      <div style="font-family: ${pdfFontFamily} !important; color: #454545; background-color: #FFFFFF; letter-spacing: 0; width: 100%;">
        <table style="width: 100%; border-spacing: 0; border-collapse: separate; font-size: 12px; font-weight: 400;">
          <tbody>
            <tr>
              <td>
                ${headerMarkup}

                <table style="width: 100%; table-layout: fixed; border-spacing: 0; border-collapse: separate; margin-top: 10px; font-family: ${pdfFontFamily} !important; font-size: 12px;">
                  <thead>
                    <tr style="height: 46px;">
                      <td style="text-align: center; width: 10%; padding: 0 8px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Id</td>
                      <td style="text-align: left; width: 35%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Titel</td>
                      <td style="text-align: left; width: 35%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Kategori</td>
                      <td style="text-align: left; width: 20%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Status</td>
                    </tr>
                  </thead>
                  <tbody>
                    ${rowsMarkup}
                  </tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    `

    document.body.appendChild(pdfContainer)

    await html2pdf()
      .set({
        margin: [12, 10, 12, 10],
        filename: 'videor.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#FFFFFF' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] },
      })
      .from(pdfContainer)
      .save()
  } finally {
    if (pdfContainer?.parentNode)
      pdfContainer.parentNode.removeChild(pdfContainer)

    loading.value = false
  }
}

const downloadCSV = async () => {
  exporteraMobile.value = false
  loading.value = true

  try {
    const data = await store.fetchHelpVideos({
      limit: -1,
      orderByField: 'id',
      orderBy: 'desc',
    })

    const dataArray = data.helpVideos.data.map(video => ({
      ID: video.id,
      TITEL: video.title,
      KATEGORI: video.category?.name || '',
      STATUS: video.state?.name || '',
    }))

    excelParser()
      .exportDataFromJSON(dataArray, 'videor', 'csv')
  } finally {
    loading.value = false
  }
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
  helpVideoFormError.value = ''
  isMobileActionDialogVisible.value = false
  if (mdAndDown.value) isAddNewHelpVideoMobileVisible.value = true
  else isAddNewHelpVideoDrawerVisible.value = true
}

const handleMobileDialogUpdate = isOpen => {
  if (!isOpen) addNewHelpVideoMobileRef.value?.closeNavigationDrawer()
}

const showActivateDialog = helpVideoData => {
  selectedHelpVideo.value = { ...helpVideoData }
  isMobileActionDialogVisible.value = false
  isConfirmActiveDialogVisible.value = true
}

const showConfirmation = video => {
  confirmation.value = { ...video }
  isMobileActionDialogVisible.value = false
}

const submit = async (payload, method) => {
  saving.value = true
  helpVideoFormError.value = ''
  try {
    if (method === 'update') {
      payload.data.append('_method', 'PUT')
      await store.updateHelpVideo(payload)
    } else await store.addHelpVideo(payload.data)
    isAddNewHelpVideoDrawerVisible.value = false
    isAddNewHelpVideoMobileVisible.value = false
    notify('success', 'Videon har sparats.')
    await fetchData()
  } catch (error) {
    helpVideoFormError.value = error.response?.data?.message || error.message || 'Kunde inte spara.'
  } finally {
    saving.value = false
  }
}

const confirmAction = async () => {
  saving.value = true
  try {
    await store.deleteHelpVideo(confirmation.value.id)
    confirmation.value = null
    notify('success', 'Videon har tagits bort.')
    await fetchData()
  } catch (error) {
    notify('error', error.response?.data?.message || error.message || 'Åtgärden misslyckades.')
  } finally {
    saving.value = false
  }
}

const updateState = async () => {
  if (saving.value) return

  saving.value = true
  try {
    const res = await store.updateState(selectedHelpVideo.value.id)
    if (!res.data.success) {
      notify('error', res.data.message || 'Kunde inte uppdatera videon.')

      return
    }

    isConfirmActiveDialogVisible.value = false
    notify('success', 'Videon uppdaterad!')
    await fetchData()
  } catch (error) {
    notify('error', error.response?.data?.message || error.message || 'Kunde inte uppdatera videon.')
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
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
        <div class="align-center font-blauer">
          <h2>Videor <span v-if="hasLoaded">({{ total }})</span></h2>
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'" />
        
        <div class="d-flex gap-4">
          <VMenu v-if="windowWidth >= 1024">
            <template #activator="{ props }">
              <VBtn
                id="payout-export-button"
                class="btn-light w-auto"
                block
                v-bind="props"
              >
                <VIcon icon="custom-export" size="24" />
                Exportera
              </VBtn>
            </template>

            <VList>
              <VListItem @click="downloadPDF">
                <VListItemTitle>Exportera PDF</VListItemTitle>
              </VListItem>
              <VListItem @click="downloadCSV">
                <VListItemTitle>Exportera Excel</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>

          <VBtn
            v-if="windowWidth < 1024"
            id="payout-export-button"
            class="btn-light w-auto"
            block
            @click="exporteraMobile = true"
          >
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

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
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.mdAndDown ? 'p-6' : 'pa-4 gap-2'"
      >

        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="search" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-2'">
          <AppAutocomplete
              prepend-icon="custom-help-center-category"
              v-model="categoryId"
              placeholder="Kategorier"
              :items="activeCategories"
              :item-title="(item) => item.name"
              :item-value="(item) => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              class="selector-user selector-truncate"
            />
        </div>

        <VBtn
          v-if="windowWidth < 1024"
          class="btn-white-2 px-3"
          aria-label="Kategorier"
          @click="isFilterDialogVisible = true"
        >
          <VIcon
            icon="custom-help-center-category"
            size="24"
          />
        </VBtn>

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
        
        <div
          v-if="!$vuetify.display.mdAndDown"
          class="d-flex align-center visa-select"
        >
          <span class="text-no-wrap pr-4">Visa</span>
          <VSelect
            v-model="rowPerPage"
            class="custom-select-hover"
            :items="[10, 20, 30, 50]"
          />
        </div>

      </VCardText>
      
      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="videos.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col">#ID</th>
            <th scope="col">Video</th>
            <th scope="col">Titel</th>
            <th scope="col" class="text-center">Kategori</th>
            <th scope="col" class="text-center">Status</th>
            <th scope="col"></th>
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
                class="video-thumbnail my-2"
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
            <td class="text-wrap w-100">
              <div class="d-flex flex-column">
                <span>{{ video.title }}</span>
                <span class="text-neutral-3">{{ video.description }}</span>
              </div>
            </td>
            <td class="text-center">
              {{ video.category.name }}
            </td>
            <td class="text-center">
              <div
                class="status-chip"
                :class="video.state_id === 2 ? 'status-chip-success' : 'status-chip-error'"
              >
                {{ video.state.name }}
              </div>
            </td>
            <td>
              <VMenu v-if="$can('edit', 'help-videos') || $can('delete', 'help-videos')">
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                    v-if="$can('view', 'help-videos')"
                    @click="previewVideo = video"
                  >
                    <template #prepend>
                      <VIcon icon="custom-eye-search" size="24" />
                    </template>
                    <VListItemTitle>Förhandsvisa</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'help-videos') && video.state_id === 1"
                    @click="showActivateDialog(video)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-check-mark" size="24" />
                    </template>
                    <VListItemTitle>Aktivera</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'help-videos') && video.state_id === 2"
                    @click="showActivateDialog(video)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-cancel-2" size="24" />
                    </template>
                    <VListItemTitle>Inaktivera</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('edit', 'help-videos')"
                    @click="edit(video)"
                  >
                  <template #prepend>
                      <VIcon icon="custom-pencil" size="24" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('delete', 'help-videos')"
                    @click="showConfirmation(video)"
                  >
                    <template #prepend>
                      <VIcon icon="custom-waste" size="24" />
                    </template>
                    <VListItemTitle>Ta bort</VListItemTitle>
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
        <VIcon 
          :size="$vuetify.display.mdAndDown ? 80 : 120" 
          icon="custom-f-video" 
        />
        <div class="empty-state-content">
          <div class="empty-state-title"> Inga videor skapade än</div>
          <div class="empty-state-text">
            Här kommer alla dina skapade videor att listas.
          </div>
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
              <div class="expansion-panel-item-label">Beskrivning </div>
              <div class="expansion-panel-item-value">{{ video.description }}</div>
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
            <div class="d-flex gap-4">
              <VBtn class="btn-light flex-1" @click="previewVideo = video">
                <VIcon icon="custom-eye-search" size="24" />
                Förhandsvisa
              </VBtn>
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
      v-model:is-drawer-open="isAddNewHelpVideoDrawerVisible"
      :help-video="selected"
      :categories="categories"
      :busy="saving"
      :error-message="helpVideoFormError"
      @help-video-data="submit"
    />
    <VDialog
      :model-value="isAddNewHelpVideoMobileVisible"
      :persistent="saving"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
      @update:model-value="handleMobileDialogUpdate"
    >
      <VCard>
        <AddNewHelpVideoMobile
          ref="addNewHelpVideoMobileRef"
          v-model:is-drawer-open="isAddNewHelpVideoMobileVisible"
          :help-video="selected"
          :categories="categories"
          :busy="saving"
          :error-message="helpVideoFormError"
          @help-video-data="submit"
        />
      </VCard>
    </VDialog>
    <VDialog
      :model-value="!!previewVideo"
      :max-width="$vuetify.display.mdAndDown ? undefined : '500'"
      class="action-dialog"
      aria-labelledby="help-video-preview-title"
      @update:model-value="!$event && (previewVideo = null)"
    >
      <VCard v-if="previewVideo">
        <VCardText class="dialog-title-box d-flex flex-row align-center">
          <VIcon icon="custom-eye-search" size="32" class="action-icon flex-shrink-0" />
          <div id="help-video-preview-title" class="dialog-title preview-video-title text-wrap">
            {{ previewVideo.title }}
          </div>
          <VSpacer />
          <VBtn
            icon
            class="btn-white flex-shrink-0"
            aria-label="Stäng"
            @click="previewVideo = null"
          >
            <VIcon icon="custom-close" size="16" />
          </VBtn>
        </VCardText>
        <VDivider />
        <VCardText class="preview-video-body pa-4">
          <HelpVideoPreview
            :key="previewVideo.id"
            :provider="previewVideo.provider"
            :url="previewVideo.video_url"
            :title="previewVideo.title"
            :thumbnail="thumbnailFor(previewVideo) || ''"
            show-poster
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
            icon="custom-filled-waste"
            class="action-icon"
          />
          <div class="dialog-title">Radera video?</div>
        </VCardText>
        <VCardText class="dialog-text">
          Du är på väg att permanent radera <strong>{{ confirmation.title }}</strong>.
          Denna åtgärd kan inte ångras.
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
            Ja, radera video
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isConfirmActiveDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        :disabled="saving"
        @click="isConfirmActiveDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-warning-outlined" class="action-icon" />
          <div class="dialog-title">
            {{ selectedHelpVideo.state_id === 1 ? 'Aktivera video' : 'Inaktivera video' }}
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker att du vill {{ selectedHelpVideo.state_id === 1 ? 'aktivera' : 'inaktivera' }} video
          <strong>{{ selectedHelpVideo.title }}</strong>?
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            :disabled="saving"
            @click="isConfirmActiveDialogVisible = false"
          >
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" :loading="saving" @click="updateState">
            Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isFilterDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        aria-label="Stäng"
        @click="isFilterDialogVisible = false"
      >
        <VIcon
          size="16"
          icon="custom-close"
        />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon
            size="32"
            icon="custom-filter"
            class="action-icon"
          />
          <div class="dialog-title">Filtrera efter</div>
        </VCardText>

        <VCardText class="pt-0">
          <AppAutocomplete
            v-model="categoryId"
            prepend-icon="custom-help-center-category"
            placeholder="Kategorier"
            :items="activeCategories"
            :item-title="(item) => item.name"
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user"
            :menu-props="{ maxHeight: '400px' }"
          />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-10">
          <VBtn
            class="btn-light"
            @click="isFilterDialogVisible = false"
          >
            Avbryt
          </VBtn>
          <VBtn
            class="btn-gradient"
            @click="isFilterDialogVisible = false"
          >
            Stäng
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
      v-model="exporteraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem @click="downloadPDF">
            <VListItemTitle>Exportera PDF</VListItemTitle>
          </VListItem>
          <VListItem @click="downloadCSV">
            <VListItemTitle>Exportera Excel</VListItemTitle>
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
          <VListItem
            v-if="$can('edit', 'help-videos') && selected.state_id === 1"
            @click="showActivateDialog(selected)"
          >
            <template #prepend>
              <VIcon icon="custom-check-mark" size="24" />
            </template>
            <VListItemTitle>Aktivera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'help-videos') && selected.state_id === 2"
            @click="showActivateDialog(selected)"
          >
            <template #prepend>
              <VIcon icon="custom-cancel-2" size="24" />
            </template>
            <VListItemTitle>Inaktivera</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('edit', 'help-videos')" @click="edit(selected)">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('delete', 'help-videos')" @click="showConfirmation(selected)">
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

.preview-video-title {
  min-width: 0;
  overflow-wrap: anywhere;
}

.preview-video-body :deep(.help-video-preview) {
  overflow: hidden;
  border-radius: 24px;
}
</style>

<route lang="yaml">
meta:
  action: view
  subject: help-videos
</route>