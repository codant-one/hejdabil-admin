<script setup>

import { formatDateTimeShortMonth } from '@/@core/utils/formatters'
import AppDateTimePicker from '@/@core/components/AppDateTimePicker.vue'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'
import SmsMessages from '@/api/smsMessages'
import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";

const { width: windowWidth } = useWindowSize()

const sectionEl = ref(null)
const isRequestOngoing = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const smsMessages = ref([])
const responseScope = ref('all')
const role = ref('')

const summary = ref({
  total_count: 0,
  accepted_count: 0,
  failed_count: 0,
  with_supplier_count: 0,
  without_supplier_count: 0,
})

const advisor = ref({
  message: '',
  show: false,
  type: '',
})

const filterStatus = ref('all')
const filterSupplierScope = ref('all')
const filterDateRange = ref(resolveCurrentDateRange())

const statusOptions = [
  { title: 'Alla', value: 'all' },
  { title: 'Skickade', value: 'accepted' },
  { title: 'Misslyckade', value: 'failed' },
]

const dateFilterConfig = {
  altInput: true,
  altFormat: 'd M Y',
  dateFormat: 'Y-m-d',
  inline: false,
  mode: 'range',
  locale: {
    rangeSeparator: ' - ',
  },
  rangePresets: false,
}

const isAdminRole = computed(() => role.value === 'SuperAdmin' || role.value === 'Administrator')
const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')

// 👉 Computing pagination data
const paginationData = computed(() => {
  return `${totalItems.value} resultat`;
  // return `Visar ${firstIndex} till ${lastIndex} av ${totalBillings.value} fakturor`;
});

const summaryCards = computed(() => [
  {
    title: 'Totalt',
    value: summary.value.total_count,
    color: '#111827',
    bg: '#FFFFFF',
    border: '#E7E7E7',
    show: false
  },
  {
    title: 'Skickade',
    value: summary.value.accepted_count,
    color: '#006D5C',
    bg: '#FFFFFF',
    border: '#E7E7E7',
    show: false
  },
  {
    title: 'Misslyckade',
    value: summary.value.failed_count,
    color: '#9B191B',
    bg: '#FEF2F2',
    border: '#FECACA',
    show: true
  },
])

function toYmd(value) {
  if (!value)
    return null

  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    const year = value.getFullYear()
    const month = `${value.getMonth() + 1}`.padStart(2, '0')
    const day = `${value.getDate()}`.padStart(2, '0')

    return `${year}-${month}-${day}`
  }

  if (typeof value === 'string') {
    const normalized = value.trim()
    const ymdMatch = normalized.match(/^\d{4}-\d{2}-\d{2}/)

    if (ymdMatch)
      return ymdMatch[0]

    const parsed = new Date(normalized)
    if (!Number.isNaN(parsed.getTime())) {
      const year = parsed.getFullYear()
      const month = `${parsed.getMonth() + 1}`.padStart(2, '0')
      const day = `${parsed.getDate()}`.padStart(2, '0')

      return `${year}-${month}-${day}`
    }
  }

  return null
}

function resolveCurrentDateRange() {
  const date = new Date()
  const startOfMonth = new Date(date.getFullYear(), date.getMonth(), 1)

  return [toYmd(startOfMonth), toYmd(date)]
}

function getDateRangePayload() {
  if (!filterDateRange.value)
    return {}

  if (Array.isArray(filterDateRange.value)) {
    const from = toYmd(filterDateRange.value[0])
    const to = toYmd(filterDateRange.value[1] ?? filterDateRange.value[0])

    if (from && to)
      return { date_from: from, date_to: to }
  }

  if (typeof filterDateRange.value === 'string') {
    const splitByRange = filterDateRange.value.split(/\s+-\s+|\s+to\s+|\s+till\s+|\s+a\s+/i)

    if (splitByRange.length >= 2) {
      const from = toYmd(splitByRange[0])
      const to = toYmd(splitByRange[1])

      if (from && to)
        return { date_from: from, date_to: to }
    }

    const single = toYmd(filterDateRange.value)

    if (single)
      return { date_from: single, date_to: single }
  }

  if (filterDateRange.value instanceof Date) {
    const single = toYmd(filterDateRange.value)

    if (single)
      return { date_from: single, date_to: single }
  }

  return {}
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value
  if (!el)
    return

  const rect = el.getBoundingClientRect()
  const remaining = Math.max(0, window.innerHeight - rect.top - 25)
  el.style.minHeight = `${remaining}px`
}

function resolveQueryParams() {
  const params = {
    page: currentPage.value,
    limit: 10,
    ...getDateRangePayload(),
  }

  if (filterStatus.value !== 'all')
    params.status = filterStatus.value

  if (isAdminRole.value && filterSupplierScope.value !== 'all')
    params.supplier_scope = filterSupplierScope.value

  return params
}

async function fetchSmsMessages() {
  isRequestOngoing.value = true

  try {
    const response = await SmsMessages.get(resolveQueryParams())
    const payload = response?.data?.data ?? {}
    const paginatedMessages = payload.smsMessages ?? {}

    smsMessages.value = paginatedMessages.data ?? []
    totalPages.value = paginatedMessages.last_page ?? 1
    totalItems.value = payload.smsMessagesTotalCount ?? 0
    summary.value = {
      ...summary.value,
      ...(payload.summary ?? {}),
    }
    responseScope.value = payload.meta?.scope ?? 'all'
  } catch (error) {
    smsMessages.value = []
    totalPages.value = 1
    totalItems.value = 0
    summary.value = {
      total_count: 0,
      accepted_count: 0,
      failed_count: 0,
      with_supplier_count: 0,
      without_supplier_count: 0,
    }
    responseScope.value = 'all'
    advisor.value = {
      type: 'error',
      message: error?.message ?? 'No fue posible cargar los registros de SMS.',
      show: true,
    }
  } finally {
    isRequestOngoing.value = false
  }
}

function applyFilters() {
  currentPage.value = 1
  fetchSmsMessages()
}

function resetFilters() {
  filterStatus.value = 'all'
  filterSupplierScope.value = 'all'
  filterDateRange.value = resolveCurrentDateRange()
  applyFilters()
}

const resolveStatus = item => {
  if (Number(item?.billable_count) > 0)
    return { class: 'success' }
  if (Number(item?.billable_count) === 0)
    return { class: 'error' }
}

function resolveSourceLabel(item) {
  const sourceType = String(item?.source_type || '').trim()
  const sourceId = item?.source_id ? `#${item.source_id}` : ''

  const labels = {
    billing: 'Fakturor',
    agreement: 'Avtal',
    document: 'Dokument',
  }

  return `${labels[sourceType] || 'Otro'} ${sourceId}`.trim()
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + "...";
  }
  return text;
};

onMounted(() => {
  const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData?.roles?.[0]?.name ?? ''

  resizeSectionToRemainingViewport()
  window.addEventListener('resize', resizeSectionToRemainingViewport)
  fetchSmsMessages()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
})
</script>

<template>
  <section class="page-section bg-white" ref="sectionEl">
    <LoadingOverlay :is-loading="isRequestOngoing" />

    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
    >
      {{ advisor.message }}
    </VSnackbar>

    <VCard class="card-fill">
      <VCardText class="pb-0" v-if="windowWidth < 1024">
        <div class="d-flex flex-column gap-4 flex-1">
          <VBtn
            class="btn-light"
            style="width: 120px;"
            :to="{ name: 'dashboard-settings' }"
          >
            <VIcon icon="custom-return" size="24" />
            Tillbaka
          </VBtn>

          <span class="title-settings pb-4 border-bottom-settings">
            SMS-översikt
          </span>
        </div>
      </VCardText>

      <VCardText class="pb-0">
        <div :class="windowWidth < 1024 ? 'pb-4' : 'pb-6'">
          <div class="settings-layout__sidebar">
            <div class="d-flex flex-column gap-4">
              <span class="subtitle-settings">SMS-översikt</span>
              <span class="text-settings">
                Visa dina skickade SMS — inklusive status, mottagare, ursprung och vem i teamet som skickade.
              </span>
            </div>
          </div>
          <div class="settings-layout__content"> </div>
        </div>
      </VCardText>

      <VCardText class="pt-0 pb-6 card-form d-flex flex-column gap-8 border-bottom-settings">
        <VRow>
          <VCol cols="12" md="6">
            <VLabel
              class="mb-1 text-body-2 text-high-emphasis"
              text="Datumintervall"
            />
            <AppDateTimePicker
              :model-value="filterDateRange"
              @update:modelValue="filterDateRange = $event"
              :config="dateFilterConfig"
            />
          </VCol>
          <VCol cols="12" md="6">
            <AppAutocomplete
              v-model="filterStatus"
              label="Status"
              :items="statusOptions"
              :item-title="(item) => item.title"
              :item-value="(item) => item.value"
              autocomplete="off"
            />
          </VCol>
        </VRow>
        <div class="d-flex gap-4 text-end">
          <VSpacer :class="windowWidth < 1024 ? 'd-none' : ''"/>
          <VBtn 
            class="btn-light w-auto"
            :block="windowWidth < 1024"
            @click="resetFilters"
          >
            Återställ
          </VBtn>
          <VBtn 
            class="btn-gradient" 
            :block="windowWidth < 1024"
            @click="applyFilters"
          >
            Tillämpa filter
          </VBtn>
        </div>

        <div class="d-flex flex-column gap-6 card-form">
          <div class="sms-summary-grid">
            <div
              v-for="card in summaryCards"
              :key="card.title"
              class="sms-summary-card"
              :style="{
                backgroundColor: card.bg,
                border: `1px solid ${card.border}`,
              }"
            >
              <span class="sms-summary-card__label">{{ card.title }}</span>
              <span class="sms-summary-card__value" :style="{ color: card.color }">{{ card.value }}</span>
              <span class="sms-summary-card__text">Filtrerat period</span>
              <span class="sms-summary-card__info" v-if="card.show">
                <VIcon icon="custom-alert" size="13" />
                Granska misslyckade
              </span>
            </div>
          </div>
        </div>
      </VCardText>

      <VCardText class="pb-0">
        <VTable
          v-if="!$vuetify.display.mdAndDown"
          v-show="smsMessages.length"
          class="px-0 pb-6 text-no-wrap"
        >
          <thead>
            <tr>
              <th scope="col">Datum & tid</th>
              <th scope="col" class="text-center">Status</th>
              <th scope="col" class="text-center">Mottagare</th>
              <th scope="col" class="text-center">Källa</th>
              <th scope="col">Skapad av</th>
            </tr>
          </thead>

          <tbody v-show="smsMessages.length">
            <tr v-for="item in smsMessages" :key="item.id">
              <td>
                {{ formatDateTimeShortMonth(item.sent_at || item.failed_at || item.created_at) }}
              </td>

              <td class="text-center text-wrap d-flex justify-center align-center">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(item)?.class}`"
                >
                  {{ item?.billable_count > 0 ? 'Skickat' : 'Misslyckat' }}
                </div>
              </td>

              <td class="text-center">
                <span>{{ item.to_number }}</span>
              </td>

              <td class="text-center">
                <span>{{ resolveSourceLabel(item) }}</span>
              </td>

              <td style="width: 1%; white-space: nowrap">
                <div class="d-flex align-center gap-x-1">
                  <VAvatar
                    variant="outlined"
                    size="38"
                  >
                    <VImg
                      v-if="item.supplier?.user?.avatar"
                      style="border-radius: 50%"
                      :src="themeConfig.settings.urlStorage + item.supplier.user.avatar"
                    />
                    <PresetAvatarImage
                      v-else
                      :avatar-id="item.supplier?.user?.user_detail?.avatar_id"
                    />
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <span class="font-weight-medium">
                      {{ item.supplier?.user?.name || 'Sin usuario' }} {{ item.supplier?.user?.last_name ?? "" }}
                    </span>
                    <span class="text-sm text-disabled">
                      <VTooltip 
                        v-if="item.supplier?.user?.email && item.supplier.user.email.length > 20"
                        location="bottom">
                        <template #activator="{ props }">
                          <span v-bind="props" class="cursor-pointer">
                            {{ truncateText(item.supplier.user.email, 20) }}
                          </span>
                        </template>
                        <span>{{ item.supplier.user.email }}</span>
                      </VTooltip>
                      <span class="text-sm text-disabled"v-else>{{ item.supplier?.user?.email || '---' }}</span>
                    </span>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </VTable>

        <div
          v-if="!isRequestOngoing && !smsMessages.length"
          class="empty-state"
          :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
        >
          <VIcon
            :size="$vuetify.display.mdAndDown ? 80 : 120"
            icon="custom-f-sms"
          />
          <div class="empty-state-content">
            <div class="empty-state-title">Inga SMS har skickats ännu</div>
            <div class="empty-state-text">
              SMS skickas automatiskt när du sänder fakturor, avtal och kontrakt till dina kunder via Billogg. Dina poster visas här.
            </div>
          </div>
        </div>

        <div
          class="d-flex flex-column gap-2 pb-6" 
          v-if="smsMessages.length && $vuetify.display.mdAndDown">
          <div v-for="item in smsMessages" :key="item.id">
            <div class="card-mobile-sms d-flex justify-between">
              <div class="d-flex flex-column gap-4">
                <span class="card-mobile-sms__date">{{ formatDateTimeShortMonth(item.sent_at || item.failed_at || item.created_at) }}</span>
                <span class="card-mobile-sms__number">{{ item.to_number }}</span>
                <span class="card-mobile-sms__description">{{ resolveSourceLabel(item) }}</span>
              </div>
              <div class="d-flex flex-column justify-between align-end">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(item)?.class}`"
                >
                  {{ item?.billable_count > 0 ? 'Skickat' : 'Misslyckat' }}
                </div>
                <span class="card-mobile-sms__user">{{ item.supplier?.user?.name || 'Sin usuario' }} {{ item.supplier?.user?.last_name ?? "" }}</span>
              </div>
            </div>
          </div>
        </div>

      </VCardText>

      <VCardText
        v-if="smsMessages.length"
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
          @update:model-value="fetchSmsMessages"
        />
      </VCardText>
    </VCard>
  </section>
</template>

<style lang="scss">

  .card-mobile-sms {
    gap: 24px;
    opacity: 1;
    border-radius: 16px;
    padding: 16px;
    background: #F6F6F6;
  }

  .card-mobile-sms__date {
    color: #878787;
    font-weight: 400;
    font-size: 14px;
    line-height: 16px;
    letter-spacing: 0;
  }

  .card-mobile-sms__number {
    color: #454545;
    font-weight: 400;
    font-size: 16px;
    line-height: 16px;
    letter-spacing: 0;
  }

  .card-mobile-sms__description {
    color: #878787;
    font-weight: 400;
    font-size: 14px;
    line-height: 16px;
    letter-spacing: 0;
  }

  .card-mobile-sms__user {
    color: #878787;
    font-weight: 400;
    font-size: 14px;
    line-height: 16px;
    letter-spacing: 0;
  }

  .h-40 {
    height: 40px !important;
  }

  .v-btn.h-40 {
    height: 40px !important;
    min-height: 40px !important;
  }

  @media (min-width: 1024px) {
    .card-form.client-desktop {
      .v-input:not(.v-textarea) {
        .v-input__control {
          .v-field {
            background-color: #f6f6f6 !important;
            min-height: 40px !important;
            /*height: 40px !important;*/

            .v-text-field__suffix {
              padding: 8px 16px !important;
            }

            .v-field__input {
              min-height: 40px !important;
              /*height: 40px !important;*/
              padding: 8px 16px !important;

              input {
                min-height: 40px !important;
                height: 40px !important;
              }
            }

            .v-field-label {
              top: 12px !important;
            }

            .v-field__append-inner {
              align-items: center;
              padding-top: 0px;
            }

            .v-text-field__prefix {
              height: 40px;
            }
          }
        }
      }

      .v-input.always-show-prefix {
        .v-input__control {
          .v-field {
            .v-field__input {
              padding: 8px 0 !important;
            }
          }
        }
      }

      .v-select .v-field {
        .v-select__selection {
            align-items: center;
            color: #454545;
        }

        .v-field__input > input {
          top: 0px;
          left: 18px;

        }

        .v-field__input input::placeholder,
        input.v-field__input::placeholder,
        .v-field__input textarea::placeholder,
        textarea.v-field__input::placeholder {
            color: #454545 !important;
            opacity: 1 !important;
          }
      }

      .selector-country {
        .v-input__prepend {
          margin-inline-end: 6px !important;
        }
      }

    }
  }

  .sms-filter-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
  }

  .sms-filter-grid__range {
    grid-column: span 2;
  }

  .sms-summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
  }

  .sms-summary-card {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 16px;
    border-radius: 12px;
  }

  .sms-summary-card__label {
    color: #5D5D5D;
    font-weight: 600;
    font-size: 14px;
    line-height: 16.5px;
    letter-spacing: 0.77px;
    vertical-align: middle;
    text-transform: capitalize;
  }

  .sms-summary-card__value {
    font-weight: 700;
    font-size: 34px;
    line-height: 34px;
    letter-spacing: -1.5px;
    vertical-align: middle;
  }

  .sms-summary-card__text {
    color: #878787;
    font-weight: 400;
    font-size: 11.5px;
    line-height: 17.25px;
    letter-spacing: 0%;
    vertical-align: middle;
  }

  .sms-summary-card__info {
    color: #9B191B;
    font-weight: 600;
    font-size: 11.5px;
    line-height: 17.25px;
    letter-spacing: 0%;
    vertical-align: middle;
  }

  .sms-log-table__muted,
  .sms-scope-hint {
    color: #737373;
    font-size: 12px;
    line-height: 18px;
  }

  @media (max-width: 1279px) {
    .sms-summary-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 1023px) {
    .sms-filter-grid,
    .sms-summary-grid {
      grid-template-columns: 1fr;
    }

    .sms-filter-grid__range {
      grid-column: span 1;
    }
  }
</style>

<route lang="yaml">
meta:
  action: view
  subject: dashboard
</route>