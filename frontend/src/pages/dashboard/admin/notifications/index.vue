<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useDisplay } from "vuetify";
import { useNotificationsBroadcastStores } from '@/stores/useNotificationsBroadcast'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const TITLE_MAX = 65
const SUBTITLE_MAX = 178
const PLACEHOLDER = '{{user}}'
// Same as BroadcastNotificationRequest::INTERNAL_ROUTE_REGEX in the backend
const INTERNAL_ROUTE_REGEX = /^\/(?![/\\])[^\s\\]*$/
const PLACEHOLDER_REGEX = /\{\{\s*user\s*\}\}/gi

const COMMON_ROUTES = [
  { title: 'Panel', value: '/dashboard/panel' },
  { title: 'Påminnelser', value: '/dashboard/panel#reminders' },
  { title: 'Meddelanden', value: '/dashboard/notifications' },
  { title: 'Lager', value: '/dashboard/admin/stock' },
  { title: 'Sålda', value: '/dashboard/admin/sold' },
  { title: 'Kunder', value: '/dashboard/admin/clients' },
  { title: 'Fakturor', value: '/dashboard/admin/billings' },
  { title: 'Avtal', value: '/dashboard/admin/agreements' },
  { title: 'Dokument', value: '/dashboard/admin/documents' },
  { title: 'Anteckningar', value: '/dashboard/admin/notes' },
  { title: 'Hjälpcenter', value: '/dashboard/admin/help-center' },
  { title: 'Profil', value: '/dashboard/profile' },
  { title: 'Inställningar', value: '/dashboard/settings' },
]

// Chip color by states.label; the text comes from states.name (StateSeeder)
const STATE_CHIP_CLASS = {
  queued: 'pending',
  sending: 'info',
  sent: 'success',
  failed: 'error',
}

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const broadcastStores = useNotificationsBroadcastStores()

// Permissions (PermissionSeeder): create/delete notifications
const ability = useAppAbility()
const canCreate = computed(() => ability.can('create', 'notifications'))
const canDelete = computed(() => ability.can('delete', 'notifications'))

// ---------------------------------------------------------------- Form
const emptyForm = () => ({
  title: '',
  subtitle: '',
  route: null,
  audience: 'all',
  roles: [],
  user_ids: [],
})

const form = ref(emptyForm())
const formRef = ref(null)
const titleRef = ref(null)
const subtitleRef = ref(null)
const serverErrors = ref({})
const isSending = ref(false)
const isConfirmDialogVisible = ref(false)

const advisor = ref({ type: '', message: '', show: false })

const showAdvisor = (type, message) => {
  advisor.value = { type, message, show: true }
  setTimeout(() => { advisor.value = { type: '', message: '', show: false } }, 3000)
}

// Name + last name, same as NotificationTemplateRenderer::resolveName (backend)
const fullName = user => `${String(user?.name ?? '').trim()} ${String(user?.last_name ?? '').trim()}`.replace(/\s+/g, ' ').trim()

const adminName = (() => {
  try {
    return fullName(JSON.parse(localStorage.getItem('user_data') || 'null'))
  } catch {
    return ''
  }
})()

// Same validation rules as the backend
const titleRules = [
  v => !!String(v ?? '').trim() || 'Titeln är obligatorisk.',
  v => String(v ?? '').length <= TITLE_MAX || `Titeln får inte vara längre än ${TITLE_MAX} tecken.`,
]
const subtitleRules = [
  v => !!String(v ?? '').trim() || 'Underrubriken är obligatorisk.',
  v => String(v ?? '').length <= SUBTITLE_MAX || `Underrubriken får inte vara längre än ${SUBTITLE_MAX} tecken.`,
]
const routeRules = [
  v => !v || String(v).length <= 255 || 'Länken får inte vara längre än 255 tecken.',
  v => !v || INTERNAL_ROUTE_REGEX.test(String(v)) || 'Länken måste vara en intern sökväg som börjar med "/" (externa adresser är inte tillåtna).',
]
const rolesRules = [
  v => form.value.audience !== 'roles' || (Array.isArray(v) && v.length > 0) || 'Välj minst en roll.',
]
const usersRules = [
  v => form.value.audience !== 'users' || (Array.isArray(v) && v.length > 0) || 'Välj minst en användare.',
]

const fieldErrors = field => serverErrors.value[field] ?? []

const clearServerError = field => {
  if (serverErrors.value[field]) {
    const { [field]: _removed, ...rest } = serverErrors.value
    serverErrors.value = rest
  }
}

watch(() => form.value.title, () => clearServerError('title'))
watch(() => form.value.subtitle, () => clearServerError('subtitle'))
watch(() => form.value.route, () => clearServerError('route'))
watch(() => [form.value.audience, form.value.roles, form.value.user_ids], () => {
  clearServerError('audience')
  clearServerError('roles')
  clearServerError('user_ids')
})

// Backend errors (400 from the project's FormRequests or standard 422) → shown under each field
const extractServerErrors = err => {
  const raw = err?.errors ?? (err?.message && typeof err.message === 'object' ? err.message : null)
  if (!raw)
    return null

  const mapped = {}
  Object.entries(raw).forEach(([key, messages]) => {
    const field = key.split('.')[0] // roles.0 → roles, user_ids.0 → user_ids
    mapped[field] = [...(mapped[field] ?? []), ...(Array.isArray(messages) ? messages : [messages])]
  })

  return mapped
}

// ---------------------------------------------------------------- {{user}}
const lastFocusedField = ref('title')
const caret = ref({ title: null, subtitle: null })

const inputElement = field => {
  const component = field === 'title' ? titleRef.value : subtitleRef.value
  return component?.$el?.querySelector('input, textarea') ?? null
}

const rememberCaret = field => {
  lastFocusedField.value = field
  const el = inputElement(field)
  if (el)
    caret.value[field] = { start: el.selectionStart, end: el.selectionEnd }
}

const insertPlaceholder = async () => {
  const field = lastFocusedField.value
  const current = String(form.value[field] ?? '')
  const saved = caret.value[field]
  const start = saved?.start ?? current.length
  const end = saved?.end ?? start

  const before = current.slice(0, start)
  const after = current.slice(end)
  const needsSpaceBefore = before.length > 0 && !/\s$/.test(before)
  const insertion = `${needsSpaceBefore ? ' ' : ''}${PLACEHOLDER}`

  form.value[field] = `${before}${insertion}${after}`

  const position = before.length + insertion.length
  caret.value[field] = { start: position, end: position }

  await nextTick()
  const el = inputElement(field)
  if (el) {
    el.focus()
    el.setSelectionRange(position, position)
  }
}

// Same algorithm as NotificationTemplateRenderer (backend)
const renderTemplate = (template, name) => {
  const text = String(template ?? '')
  const cleanName = String(name ?? '').trim()

  if (cleanName)
    return text.replace(PLACEHOLDER_REGEX, () => cleanName)

  const placeholders = '[ \\t]*(?:\\{\\{\\s*user\\s*\\}\\}[ \\t]*)+'

  return text
    .replace(new RegExp(`${placeholders}(?=[,.!?;:])`, 'gi'), '')
    .replace(new RegExp(placeholders, 'gi'), ' ')
    .trim()
}

const previewTitle = computed(() => renderTemplate(form.value.title, adminName) || 'Titel')
const previewSubtitle = computed(() => renderTemplate(form.value.subtitle, adminName) || 'Undertitel')

// ---------------------------------------------------------------- Recipients
const roleItems = computed(() =>
  (broadcastStores.getRoles || []).map(role => ({
    title: `${role.name} (${role.users_count})`,
    value: role.name,
  })),
)

// "Specifika användare": debounced server-side search; selected users are sent
// as "include" so their chips stay visible when the search changes.
const userOptions = ref([])
const isSearchingUsers = ref(false)
let userSearchTimer = null
let userSearchRequestId = 0

const userItems = computed(() =>
  userOptions.value.map(user => ({
    title: fullName(user) || user.email,
    value: user.id,
    email: user.email,
    roles: user.roles || [],
  })),
)

const searchUsers = (search = '') => {
  clearTimeout(userSearchTimer)
  isSearchingUsers.value = true

  userSearchTimer = setTimeout(async () => {
    const requestId = ++userSearchRequestId
    try {
      const users = await broadcastStores.searchUsers({
        search: search || undefined,
        include: form.value.user_ids,
      })
      if (requestId === userSearchRequestId)
        userOptions.value = users
    } catch {
      // keep the current options
    } finally {
      if (requestId === userSearchRequestId)
        isSearchingUsers.value = false
    }
  }, 300)
}

watch(() => form.value.audience, audience => {
  if (audience === 'users' && !userOptions.value.length)
    searchUsers()
})

const recipientsCount = ref(0)
const isCounting = ref(false)
let countTimer = null
let countRequestId = 0

const refreshRecipientsCount = () => {
  clearTimeout(countTimer)

  if ((form.value.audience === 'roles' && !form.value.roles.length)
    || (form.value.audience === 'users' && !form.value.user_ids.length)) {
    recipientsCount.value = 0
    isCounting.value = false
    return
  }

  isCounting.value = true
  countTimer = setTimeout(async () => {
    const requestId = ++countRequestId
    try {
      const count = await broadcastStores.previewCount({
        audience: form.value.audience,
        roles: form.value.audience === 'roles' ? form.value.roles : undefined,
        user_ids: form.value.audience === 'users' ? form.value.user_ids : undefined,
      })
      if (requestId === countRequestId)
        recipientsCount.value = Number(count) || 0
    } catch {
      if (requestId === countRequestId)
        recipientsCount.value = 0
    } finally {
      if (requestId === countRequestId)
        isCounting.value = false
    }
  }, 400)
}

watch(() => [form.value.audience, [...form.value.roles], [...form.value.user_ids]], refreshRecipientsCount, { deep: true })

const recipientsLabel = computed(() => `Skickas till ${recipientsCount.value} användare`)

// ---------------------------------------------------------------- Send
const openConfirm = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid)
    return

  if (!recipientsCount.value) {
    showAdvisor('error', 'Det finns inga mottagare för det valda urvalet.')
    return
  }

  isConfirmDialogVisible.value = true
}

const resetForm = async () => {
  form.value = emptyForm()
  serverErrors.value = {}
  caret.value = { title: null, subtitle: null }
  lastFocusedField.value = 'title'
  await nextTick()
  formRef.value?.resetValidation()
}

const sendBroadcast = async () => {
  isConfirmDialogVisible.value = false
  isSending.value = true

  const payload = {
    title: form.value.title.trim(),
    subtitle: form.value.subtitle.trim(),
    route: form.value.route ? String(form.value.route).trim() : null,
    audience: form.value.audience,
  }
  if (form.value.audience === 'roles')
    payload.roles = form.value.roles
  if (form.value.audience === 'users')
    payload.user_ids = form.value.user_ids

  try {
    const res = await broadcastStores.sendBroadcast(payload)
    if (res?.data?.success) {
      showAdvisor('success', 'Notisen har köats för utskick!')
      await resetForm()
      currentPage.value = 1
      fetchHistory()
    }
  } catch (err) {
    const errors = extractServerErrors(err)
    if (errors) {
      serverErrors.value = errors
      showAdvisor('error', 'Kontrollera de markerade fälten.')
    } else {
      showAdvisor('error', (typeof err?.message === 'string' && err.message) || 'Notisen kunde inte skickas. Försök igen.')
    }
  } finally {
    isSending.value = false
  }
}

// ---------------------------------------------------------------- History
const broadcasts = ref([])
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalBroadcasts = ref(0)
const isHistoryLoading = ref(false)
const hasLoaded = ref(false)

// silent: background sync (polling) without toggling the loading state
const fetchHistory = async ({ silent = false } = {}) => {
  if (!silent)
    isHistoryLoading.value = true

  await broadcastStores.fetchBroadcasts({
    limit: rowPerPage.value,
    page: currentPage.value,
  })

  broadcasts.value = broadcastStores.getBroadcasts || []
  totalPages.value = broadcastStores.last_page || 1
  totalBroadcasts.value = broadcastStores.broadcastsTotalCount || 0
  isHistoryLoading.value = false
  hasLoaded.value = true
}

// Smart polling (same approach as agreements/index.vue): only runs while a broadcast
// on the current page is still queued or sending, and pauses while the tab is hidden.
const POLLING_INTERVAL_MS = 5000
const IN_PROGRESS_STATES = ['queued', 'sending']

const hasBroadcastsInProgress = computed(() =>
  broadcasts.value.some(broadcast => IN_PROGRESS_STATES.includes(broadcast.state?.label)),
)

let pollingInterval = null
let isPollingRequest = false

const startPolling = () => {
  if (pollingInterval || document.hidden)
    return

  pollingInterval = setInterval(async () => {
    if (isPollingRequest)
      return

    isPollingRequest = true
    try {
      await fetchHistory({ silent: true })
    } finally {
      isPollingRequest = false
    }
  }, POLLING_INTERVAL_MS)
}

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

watch(hasBroadcastsInProgress, inProgress => {
  if (inProgress)
    startPolling()
  else
    stopPolling()
})

const onVisibilityChange = () => {
  if (document.hidden) {
    stopPolling()
  } else if (hasBroadcastsInProgress.value) {
    fetchHistory({ silent: true }) // catch up immediately after returning to the tab
    startPolling()
  }
}

watch(currentPage, () => fetchHistory())
watch(rowPerPage, () => {
  if (currentPage.value !== 1)
    currentPage.value = 1 // triggers fetchHistory through the currentPage watcher
  else
    fetchHistory()
})

const paginationData = computed(() => `${totalBroadcasts.value} resultat`)

// ---------------------------------------------------------------- Delete
const isConfirmDeleteDialogVisible = ref(false)
const isMobileActionDialogVisible = ref(false)
const isDeleting = ref(false)
const selectedBroadcast = ref({})

// In-progress broadcasts cannot be deleted (the backend answers 409)
const canDeleteBroadcast = broadcast =>
  canDelete.value && !IN_PROGRESS_STATES.includes(broadcast?.state?.label)

const showDeleteDialog = broadcast => {
  selectedBroadcast.value = { ...broadcast }
  isMobileActionDialogVisible.value = false
  isConfirmDeleteDialogVisible.value = true
}

const removeBroadcast = async () => {
  isConfirmDeleteDialogVisible.value = false
  isDeleting.value = true

  try {
    const res = await broadcastStores.deleteBroadcast(selectedBroadcast.value.id)
    if (res?.data?.success) {
      showAdvisor('success', 'Notisen har tagits bort!')

      // Step back a page if the deleted row was the last one on it
      if (broadcasts.value.length === 1 && currentPage.value > 1)
        currentPage.value -= 1 // triggers fetchHistory through the currentPage watcher
      else
        await fetchHistory()
    }
  } catch (err) {
    showAdvisor('error', (typeof err?.message === 'string' && err.message) || 'Notisen kunde inte tas bort. Försök igen.')
    await fetchHistory({ silent: true })
  } finally {
    isDeleting.value = false
    selectedBroadcast.value = {}
  }
}

const formatDate = value => {
  if (!value)
    return '-'

  return new Date(value).toLocaleString('sv-SE', {
    year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit',
  })
}

const resolveStatus = state => ({
  label: state?.name ?? '-',
  class: STATE_CHIP_CLASS[state?.label] ?? 'disabled',
})

const resolveAudience = broadcast => {
  if (broadcast.audience === 'roles')
    return `Roller: ${(broadcast.roles || []).join(', ')}`
  if (broadcast.audience === 'users') {
    const count = (broadcast.user_ids || []).length
    return `${count} ${count === 1 ? 'specifik användare' : 'specifika användare'}`
  }

  return 'Alla användare'
}

const confirmAudienceText = computed(() => {
  if (form.value.audience === 'roles')
    return `användare med rollerna ${form.value.roles.join(', ')}`
  if (form.value.audience === 'users') {
    const names = userItems.value
      .filter(item => form.value.user_ids.includes(item.value))
      .map(item => item.title)

    return names.length <= 5
      ? names.join(', ')
      : `${names.slice(0, 5).join(', ')} och ${names.length - 5} till`
  }

  return 'alla användare'
})

const resolveCreator = broadcast => {
  const creator = broadcast.user
  if (!creator)
    return '-'

  return `${creator.name ?? ''} ${creator.last_name ?? ''}`.trim() || creator.email || '-'
}

// ---------------------------------------------------------------- Init
const sectionEl = ref(null);

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  // Form data (roles, recipient count) is only needed when the user can send
  if (canCreate.value) {
    broadcastStores.fetchRoles()
    refreshRecipientsCount()
  }
  fetchHistory()

  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
  document.addEventListener("visibilitychange", onVisibilityChange);
});

onBeforeUnmount(() => {
  clearTimeout(countTimer)
  stopPolling()
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
  document.removeEventListener("visibilitychange", onVisibilityChange);
});
</script>

<template>
  <section class="page-section notifications-broadcast-page" ref="sectionEl">
    <LoadingOverlay :is-loading="isSending || isDeleting" />

    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
    >
      {{ advisor.message }}
    </VSnackbar>

    <!-- 👉 Send notification -->
    <VCard v-if="canCreate" class="mb-6">
      <VCardTitle
        class="d-flex align-center font-blauer"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'"
      >
        <h2>Skicka notis</h2>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'">
        <VRow>
          <VCol cols="12" lg="7">
            <VForm ref="formRef" class="d-flex flex-column gap-4" @submit.prevent="openConfirm">
              <div>
                <VLabel for="broadcast-title" class="mb-1 text-body-2 text-high-emphasis" text="Titel" />
                <VTextField
                  id="broadcast-title"
                  ref="titleRef"
                  v-model="form.title"
                  :counter="TITLE_MAX"
                  :rules="titleRules"
                  :error-messages="fieldErrors('title')"
                  @focus="rememberCaret('title')"
                  @click="rememberCaret('title')"
                  @keyup="rememberCaret('title')"
                  @select="rememberCaret('title')"
                  @blur="rememberCaret('title')"
                />
              </div>

              <div>
                <VLabel for="broadcast-subtitle" class="mb-1 text-body-2 text-high-emphasis" text="Undertitel" />
                <VTextarea
                  id="broadcast-subtitle"
                  ref="subtitleRef"
                  v-model="form.subtitle"
                  rows="2"
                  auto-grow
                  :counter="SUBTITLE_MAX"
                  :rules="subtitleRules"
                  :error-messages="fieldErrors('subtitle')"
                  @focus="rememberCaret('subtitle')"
                  @click="rememberCaret('subtitle')"
                  @keyup="rememberCaret('subtitle')"
                  @select="rememberCaret('subtitle')"
                  @blur="rememberCaret('subtitle')"
                />
              </div>

              <div class="d-flex align-center flex-wrap gap-2">
                <VChip
                  color="primary"
                  variant="tonal"
                  prepend-icon="tabler-user-plus"
                  @mousedown.prevent
                  @click="insertPlaceholder"
                >
                  Infoga {{ PLACEHOLDER }}
                </VChip>
                <span class="text-body-2 text-medium-emphasis">
                  Använd {{ PLACEHOLDER }} var som helst för att infoga användarens namn.
                  <span class="d-inline-block">(Infogas i {{ lastFocusedField === 'title' ? 'titeln' : 'undertiteln' }})</span>
                </span>
              </div>

              <div>
                <VLabel for="broadcast-route" class="mb-1 text-body-2 text-high-emphasis" text="Sida (valfritt)" />
                <VCombobox
                  id="broadcast-route"
                  v-model="form.route"
                  :items="COMMON_ROUTES"
                  item-title="title"
                  item-value="value"
                  :return-object="false"
                  placeholder="/dashboard/panel"
                  persistent-placeholder
                  clearable
                  hint="Välj en sida eller skriv en intern sökväg som börjar med /"
                  persistent-hint
                  :rules="routeRules"
                  :error-messages="fieldErrors('route')"
                >
                  <template #item="{ props: itemProps, item }">
                    <VListItem v-bind="itemProps" :subtitle="item.raw.value" />
                  </template>
                </VCombobox>
              </div>

              <div>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mottagare" />
                <VRadioGroup
                  v-model="form.audience"
                  :inline="!$vuetify.display.xs"
                  hide-details="auto"
                  :error-messages="fieldErrors('audience')"
                >
                  <VRadio value="all" :label="`Alla användare (${broadcastStores.allCount})`" />
                  <VRadio value="roles" label="En eller flera roller" />
                  <VRadio value="users" label="Specifika användare" />
                </VRadioGroup>
              </div>

              <VAutocomplete
                v-if="form.audience === 'users'"
                v-model="form.user_ids"
                :items="userItems"
                :loading="isSearchingUsers"
                no-filter
                placeholder="Sök på namn, efternamn eller e-post"
                aria-label="Användare"
                no-data-text="Inga användare hittades"
                multiple
                chips
                closable-chips
                clear-on-select
                persistent-placeholder
                :rules="usersRules"
                :error-messages="fieldErrors('user_ids')"
                @update:search="searchUsers"
              >
                <template #item="{ props: itemProps, item }">
                  <VListItem
                    v-bind="itemProps"
                    :subtitle="[item.raw.email, item.raw.roles.join(', ')].filter(Boolean).join(' · ')"
                  />
                </template>
              </VAutocomplete>

              <VSelect
                v-if="form.audience === 'roles'"
                v-model="form.roles"
                :items="roleItems"
                placeholder="Välj roller"
                aria-label="Roller"
                multiple
                chips
                closable-chips
                persistent-placeholder
                :rules="rolesRules"
                :error-messages="fieldErrors('roles')"
              />

              <div class="d-flex align-center gap-2 text-body-1">
                <VProgressCircular v-if="isCounting" indeterminate size="18" width="2" />
                <VIcon v-else icon="tabler-users" size="20" />
                <strong>{{ recipientsLabel }}</strong>
              </div>

              <div class="d-flex justify-end">
                <VBtn
                  type="submit"
                  class="btn-gradient"
                  :block="$vuetify.display.xs"
                  :loading="isSending"
                  :disabled="isSending || isCounting"
                >
                  <VIcon icon="tabler-send" size="20" />
                  Skicka
                </VBtn>
              </div>
            </VForm>
          </VCol>

          <!-- 👉 Preview -->
          <VCol cols="12" lg="5">
            <VLabel class="mb-2 text-body-2 text-high-emphasis" text="Förhandsvisning" />
            <div class="broadcast-preview">
              <VAvatar
                color="#F5F8F6"
                icon="custom-alarm"
                variant="flat"
                size="40"
                rounded="lg"
                class="notification-avatar"
              />
              <div class="broadcast-preview-content">
                <div class="d-flex justify-space-between gap-2">
                  <span class="broadcast-preview-title">{{ previewTitle }}</span>
                  <small class="text-medium-emphasis text-no-wrap">nu</small>
                </div>
                <div class="broadcast-preview-subtitle">{{ previewSubtitle }}</div>
                <div v-if="form.route" class="broadcast-preview-route text-medium-emphasis">
                  <VIcon icon="tabler-link" size="14" /> {{ form.route }}
                </div>
              </div>
            </div>
            <div class="text-caption text-medium-emphasis mt-2">
              {{ PLACEHOLDER }} visas här med ditt namn. Varje mottagare ser sitt eget namn.
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 History -->
    <VCard class="card-fill">
      <VCardTitle
        class="d-flex align-center gap-4"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'"
      >
        <div class="align-center font-blauer">
          <h2>Historik <span v-if="hasLoaded">({{ totalBroadcasts }})</span></h2>
        </div>

        <VSpacer />

        <div
          v-if="!$vuetify.display.mdAndDown"
          class="d-flex align-center visa-select"
        >
          <span class="text-no-wrap pe-4">Visa</span>
          <VSelect
            v-model="rowPerPage"
            class="custom-select-hover"
            :items="[10, 20, 30, 50]"
          />
        </div>

        <div
          v-if="hasBroadcastsInProgress"
          class="d-flex align-center gap-2 text-body-2 text-medium-emphasis text-no-wrap"
        >
          <VProgressCircular indeterminate size="16" width="2" />
          <span class="d-none d-sm-inline">Synkroniserar…</span>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VTable
        v-if="windowWidth >= 1024"
        v-show="broadcasts.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <thead>
          <tr>
            <th scope="col"> Datum </th>
            <th scope="col"> Titel </th>
            <th scope="col"> Mottagare </th>
            <th scope="col" class="text-center"> Antal </th>
            <th scope="col" class="text-center"> Status </th>
            <th scope="col"> Skapad av </th>
            <th v-if="canDelete" scope="col" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="broadcast in broadcasts"
            :key="broadcast.id"
            style="height: 3rem;"
          >
            <td> {{ formatDate(broadcast.created_at) }} </td>
            <td class="text-wrap">
              <div class="font-weight-medium">{{ broadcast.title }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ broadcast.subtitle }}</div>
            </td>
            <td class="text-wrap"> {{ resolveAudience(broadcast) }} </td>
            <td class="text-center"> {{ broadcast.recipients_count }} </td>
            <td>
              <div class="d-flex justify-center">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(broadcast.state).class}`"
                >
                  {{ resolveStatus(broadcast.state).label }}
                </div>
              </div>
            </td>
            <td> {{ resolveCreator(broadcast) }} </td>
            <!-- 👉 Actions -->
            <td v-if="canDelete" class="text-center" style="width: 3rem;">
              <VMenu v-if="canDeleteBroadcast(broadcast)">
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem @click="showDeleteDialog(broadcast)">
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

      <VExpansionPanels
        v-if="broadcasts.length && windowWidth < 1024"
        class="expansion-panels pb-6 px-6"
      >
        <VExpansionPanel v-for="broadcast in broadcasts" :key="broadcast.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <div class="order-title-box">
              <span class="title-panel">{{ broadcast.title }}</span>
              <span class="text-caption text-medium-emphasis">{{ formatDate(broadcast.created_at) }}</span>
            </div>
          </VExpansionPanelTitle>

          <VExpansionPanelText>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Status</div>
              <div class="expansion-panel-item-value">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(broadcast.state).class}`"
                >
                  {{ resolveStatus(broadcast.state).label }}
                </div>
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Undertitel</div>
              <div class="expansion-panel-item-value">{{ broadcast.subtitle }}</div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Mottagare</div>
              <div class="expansion-panel-item-value">{{ resolveAudience(broadcast) }}</div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Antal</div>
              <div class="expansion-panel-item-value">{{ broadcast.recipients_count }}</div>
            </div>
            <div>
              <div class="expansion-panel-item-label">Skapad av</div>
              <div class="expansion-panel-item-value">{{ resolveCreator(broadcast) }}</div>
            </div>

            <div v-if="canDeleteBroadcast(broadcast)" class="d-flex justify-end flex-wrap gap-4 mt-6">
              <VBtn
                class="btn-light"
                icon
                @click="selectedBroadcast = broadcast; isMobileActionDialogVisible = true"
              >
                <VIcon icon="custom-dots-vertical" size="24" />
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <div
        v-if="!isHistoryLoading && hasLoaded && !broadcasts.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-bell"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga notiser skickade än</div>
          <div class="empty-state-text">
            Här kommer alla notiser du skickar att listas.
          </div>
        </div>
      </div>

      <VCardText
        v-if="broadcasts.length"
        :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
        class="align-center flex-wrap gap-4 pt-0 px-6"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <VPagination
          v-model="currentPage"
          size="small"
          :total-visible="4"
          :length="totalPages"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>
    </VCard>

    <!-- 👉 Confirm send -->
    <VDialog
      v-model="isConfirmDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-warning-outlined" class="action-icon" />
          <div class="dialog-title">
            Skicka till {{ recipientsCount }} användare?
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Notisen <strong>{{ previewTitle }}</strong> skickas till
          {{ confirmAudienceText }}.
          Utskicket kan inte ångras.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" :loading="isSending" @click="sendBroadcast">
            Skicka
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmDeleteDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filled-waste" class="action-icon" />
          <div class="dialog-title">
            Ta bort notis
          </div>
        </VCardText>

        <VCardText class="dialog-text">
          Är du säker att du vill ta bort notisen <strong>{{ selectedBroadcast.title }}</strong>?
          Den tas också bort från alla {{ selectedBroadcast.recipients_count }} mottagares notiser.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmDeleteDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" :loading="isDeleting" @click="removeBroadcast">
            Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile actions -->
    <VDialog
      v-model="isMobileActionDialogVisible"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem
            v-if="canDeleteBroadcast(selectedBroadcast)"
            @click="showDeleteDialog(selectedBroadcast)"
          >
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

<style lang="scss" scoped>
.notifications-broadcast-page {
  .broadcast-preview {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 12px 16px;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    box-shadow: 0 2px 8px rgba(0, 0, 0, 6%);
  }

  .broadcast-preview-content {
    flex: 1;
    min-width: 0;
  }

  .broadcast-preview-title {
    font-weight: 600;
    overflow-wrap: anywhere;
  }

  .broadcast-preview-subtitle {
    margin-top: 2px;
    font-size: 0.875rem;
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    overflow-wrap: anywhere;
  }

  .broadcast-preview-route {
    margin-top: 6px;
    font-size: 0.75rem;
    overflow-wrap: anywhere;
  }

  .order-title-box {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }
}
</style>

<route lang="yaml">
  meta:
    action: view
    subject: notifications
</route>
