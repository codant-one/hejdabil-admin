<script setup>

import AppDateTimePicker from '@/@core/components/AppDateTimePicker.vue'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'
import SmsMessages from '@/api/smsMessages'
import { Spanish } from 'flatpickr/dist/l10n/es.js'

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
  { title: 'Todos', value: 'all' },
  { title: 'Validos', value: 'accepted' },
  { title: 'Fallidos', value: 'failed' },
]

const supplierScopeOptions = [
  { title: 'Todos', value: 'all' },
  { title: 'Con supplier asignado', value: 'with_supplier' },
  { title: 'Sin supplier asignado', value: 'without_supplier' },
]

const dateFilterConfig = {
  altInput: true,
  altFormat: 'd/m/Y',
  dateFormat: 'Y-m-d',
  inline: false,
  locale: {
    ...Spanish,
    rangeSeparator: ' a ',
    time_24hr: true,
  },
  mode: 'range',
  rangePresets: false,
}

const isAdminRole = computed(() => role.value === 'SuperAdmin' || role.value === 'Administrator')
const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')

const paginationData = computed(() => `${totalItems.value} resultados`)

const scopeDescription = computed(() => {
  if (responseScope.value === 'supplier_account')
    return 'Muestra los registros de SMS que pertenecen a tu cuenta supplier.'

  if (responseScope.value === 'with_supplier')
    return 'Muestra los registros de SMS vinculados a cuentas supplier.'

  if (responseScope.value === 'without_supplier')
    return 'Muestra los registros de SMS donde supplier_id es null.'

  return 'Muestra todos los registros de SMS segun los filtros seleccionados.'
})

const summaryCards = computed(() => [
  {
    title: 'Total',
    value: summary.value.total_count,
    tone: 'default',
  },
  {
    title: 'Validos',
    value: summary.value.accepted_count,
    tone: 'success',
  },
  {
    title: 'Fallidos',
    value: summary.value.failed_count,
    tone: 'error',
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
    const splitByRange = filterDateRange.value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i)

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

function resolveStatusColor(item) {
  return Number(item?.billable_count) > 0 ? 'success' : 'error'
}

function resolveStatusLabel(item) {
  return Number(item?.billable_count) > 0 ? 'Valido' : 'Fallido'
}

function resolveRawStatus(item) {
  return String(item?.status || '').trim() || 'desconocido'
}

function resolveSourceLabel(item) {
  const sourceType = String(item?.source_type || '').trim()
  const sourceId = item?.source_id ? `#${item.source_id}` : ''

  const labels = {
    billing: 'Factura',
    agreement: 'Contrato',
    document: 'Documento',
  }

  return `${labels[sourceType] || 'Otro'} ${sourceId}`.trim()
}

function resolveActionLabel(actionType) {
  const labels = {
    send_billing_sms: 'Envio de factura por SMS',
    send_agreement_sms: 'Envio de contrato por SMS',
    send_document_signature_sms: 'Firma de documento',
    resend_document_signature_sms: 'Reenvio de firma de documento',
    send_agreement_signature_sms: 'Firma de contrato',
    resend_agreement_signature_sms: 'Reenvio de firma de contrato',
  }

  return labels[actionType] || actionType || 'Sin especificar'
}

function resolveUserLabel(item) {
  const fullName = `${item?.user?.name || ''} ${item?.user?.last_name || ''}`.trim()

  if (fullName)
    return fullName

  return item?.user?.email || 'Usuario desconocido'
}

function formatDateTime(value) {
  if (!value)
    return '---'

  return new Intl.DateTimeFormat('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

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
            Volver
          </VBtn>

          <span class="title-settings pb-4 border-bottom-settings">
            Registros de SMS
          </span>
        </div>
      </VCardText>

      <VCardText class="pb-0">
        <div class="settings-layout border-bottom-settings pb-6">
          <div class="settings-layout__sidebar">
            <div class="d-flex flex-column gap-4">
              <span class="subtitle-settings">Monitoreo de SMS</span>
              <span class="text-settings">
                Muestra los SMS validos y fallidos enviados desde esta cuenta, con su estado, destinatario, origen y usuario responsable.
              </span>
            </div>
          </div>

          <div class="settings-layout__content">
            <div class="d-flex flex-column gap-6 card-form">
              <div class="sms-filter-grid">
                <div class="sms-filter-grid__range">
                  <AppDateTimePicker
                    :model-value="filterDateRange"
                    @update:modelValue="filterDateRange = $event"
                    label="Rango de fechas"
                    placeholder="Selecciona un rango de fechas"
                    :config="dateFilterConfig"
                  />
                </div>

                <div>
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Estado" />
                  <VSelect
                    v-model="filterStatus"
                    :items="statusOptions"
                    item-title="title"
                    item-value="value"
                    density="compact"
                  />
                </div>

                <div v-if="isAdminRole">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Asignacion de supplier" />
                  <VSelect
                    v-model="filterSupplierScope"
                    :items="supplierScopeOptions"
                    item-title="title"
                    item-value="value"
                    density="compact"
                  />
                </div>
              </div>

              <div class="d-flex flex-wrap gap-3 align-center">
                <VBtn class="btn-gradient" @click="applyFilters">
                  Aplicar filtros
                </VBtn>
                <VBtn class="btn-light" @click="resetFilters">
                  Restablecer
                </VBtn>
                <span class="text-settings sms-scope-hint">
                  {{ scopeDescription }}
                </span>
              </div>

              <div class="sms-summary-grid">
                <div
                  v-for="card in summaryCards"
                  :key="card.title"
                  class="sms-summary-card"
                >
                  <span class="sms-summary-card__label">{{ card.title }}</span>
                  <span class="sms-summary-card__value">{{ card.value }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </VCardText>

      <VCardText class="pb-0">
        <div class="settings-layout pb-4">
          <div class="settings-layout__sidebar">
            <div class="d-flex flex-column gap-4">
              <span class="subtitle-settings">Ultimos registros</span>
              <span class="text-settings">
                Cada fila muestra quien envio el SMS, a que numero se envio y desde que flujo del sistema se genero.
              </span>
            </div>
          </div>

          <div class="settings-layout__content">
            <VDivider />

            <VTable class="text-no-wrap sms-log-table">
              <thead class="text-uppercase">
                <tr>
                  <th scope="col">Fecha</th>
                  <th scope="col">Estado</th>
                  <th scope="col">Destinatario</th>
                  <th scope="col">Origen</th>
                  <th scope="col">Enviado por</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="item in smsMessages"
                  :key="item.id"
                  style="height: 3.5rem;"
                >
                  <td>
                    {{ formatDateTime(item.sent_at || item.failed_at || item.created_at) }}
                  </td>

                  <td>
                    <div class="d-flex flex-column gap-1">
                      <VChip
                        label
                        size="small"
                        :color="resolveStatusColor(item)"
                      >
                        {{ resolveStatusLabel(item) }}
                      </VChip>
                      <span class="sms-log-table__muted">Twilio: {{ resolveRawStatus(item) }}</span>
                    </div>
                  </td>

                  <td>
                    <div class="d-flex flex-column gap-1">
                      <span>{{ item.to_number }}</span>
                      <span class="sms-log-table__muted" v-if="item.provider_message_sid">
                        SID: {{ item.provider_message_sid }}
                      </span>
                    </div>
                  </td>

                  <td>
                    <div class="d-flex flex-column gap-1">
                      <span>{{ resolveSourceLabel(item) }}</span>
                      <span class="sms-log-table__muted">{{ resolveActionLabel(item.action_type) }}</span>
                    </div>
                  </td>

                  <td>
                    <div class="d-flex flex-column gap-1">
                      <span>{{ resolveUserLabel(item) }}</span>
                      <span class="sms-log-table__muted">{{ item.user?.email || '---' }}</span>
                    </div>
                  </td>
                </tr>
              </tbody>

              <tfoot v-show="!smsMessages.length">
                <tr>
                  <td colspan="5" class="text-center text-body-1">
                    No hay registros de SMS para los filtros seleccionados.
                  </td>
                </tr>
              </tfoot>
            </VTable>

            <VDivider />

            <VCardText class="d-block d-md-flex text-center align-center flex-wrap gap-4 py-3 px-0">
              <span class="text-sm text-disabled">
                {{ paginationData }}
              </span>

              <VSpacer class="d-none d-md-block" />

              <VPagination
                v-model="currentPage"
                size="small"
                :total-visible="4"
                :length="totalPages"
                @update:model-value="fetchSmsMessages"
              />
            </VCardText>
          </div>
        </div>
      </VCardText>
    </VCard>
  </section>
</template>

<style lang="scss">
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
  border: 1px solid #E7E7E7;
  border-radius: 10px;
  background: #FAFAFA;
}

.sms-summary-card__label {
  color: #737373;
  font-size: 13px;
  line-height: 18px;
}

.sms-summary-card__value {
  color: #141414;
  font-size: 28px;
  font-weight: 600;
  line-height: 32px;
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