<script setup>

import { themeConfig } from '@themeConfig'
import { useDisplay } from 'vuetify'
import { useActivitiesStore } from '@/stores/useActivities'
import { formatNumber } from '@/@core/utils/formatters'
import { getActivityVisibleFields } from './activityVisibleFields'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'
import DefaultLayoutWithoutVerticalNav from '@/layouts/components/DefaultLayoutWithoutVerticalNav.vue'
import MobileBottomBar from '@/layouts/components/MobileBottomBar.vue'
import navItems from '@/navigation/vertical'
import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";
import { ref } from 'vue'

const activitiesStore = useActivitiesStore()
const emitter = inject('emitter')

const { width: windowWidth } = useWindowSize()
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const sectionEl = ref(null);

const activities = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalActivities = ref(0)
const isRequestOngoing = ref(true)
const expandedActivityId = ref(null)

const filtreraMobile = ref(false);
const isFilterDialogVisible = ref(false)

const userData = ref(null)
const user_id = ref(null)
const role = ref(null)
const suppliers = ref([])
const supplier_id = ref(null)
const users = ref([])
const userId = ref(null)
const modules = ref([])
const module_id = ref(null)
const mode = ref('Lista')

const modeOptions = [
  {
    title: 'Lista',
    value: 'Lista',
    icon: 'custom-list',
  },
  {
    title: 'Tabell',
    value: 'Tabell',
    icon: 'custom-table',
  },
]

const advisor = ref({
  type: '',
  message: '',
  show: false,
})

const activityModuleLabels = {
  agreements: 'Avtal',
  billings: 'Fakturor',
  clients: 'Kunder',
  documents: 'Dokument',
  notes: 'Mina Värderingar',
  comment_notes: 'Mina Värderingar',
  payouts: 'Swish',
  vehicles: 'Fordonslager',
}

const activityModuleSingularLabels = {
  agreements: 'Avtal',
  billings: 'Faktura',
  clients: 'Kund',
  documents: 'Dokument',
  notes: 'Anteckning',
  comment_notes: 'Kommentar',
  payouts: 'Utbetalning',
  vehicles: 'Fordon',
}

const activityActionLabels = {
  cancelled: 'annullerad',
  create: 'skapad',
  delete: 'borttaget',
  delivered: 'levererad',
  paid: 'markerad som betald',
  reminder: 'påminnelse skickad',
  credit: 'kreditfaktura skapad',
  resend: 'skickad igen',
  reviewed: 'granskad',
  send: 'skickad',
  sell: 'såld',
  cancel: 'avbruten',
  signed: 'signerad',
  unpaid: 'markerad som obetald',
  update: 'uppdaterad',
  revoke: 'återkallad',
}

const activityDetailTitles = {
  created: 'Skapad information',
  deleted: 'Borttagen information',
  sent: 'Skickad till',
  updated: 'Ändring',
}

const activityFieldLabels = {
  account: 'Konto',
  address: 'Adress',
  agreement_id: 'Avtals-ID',
  amount: 'Belopp',
  invoice_id: 'Faktura nr',
  invoice_date: 'Fakturadatum',
  due_date: 'Förfallodatum',
  tax: 'Moms',
  detail: 'Detaljer',
  subtotal: 'Netto',
  total: 'Summa att betala',
  discount: 'Preliminär skattereduktion (Rabatt)',
  amount_discount: 'Rabatt',
  amount_tax: 'Belopp skatt',
  comments: 'Anteckningar',
  country_id: 'Land',
  description: 'Beskrivning',
  email: 'E-post',
  fullname: 'Namn',
  installment_amount: 'Belopp',
  model_id: 'Modell',
  brand_id: 'Märke',
  year: 'Årsmodell',
  mileage: 'Miltal',
  car_body_id: 'Kaross',
  purchase_date: 'Inköpsdatum',
  control_inspection: 'Kontrollbesiktning gäller tom',
  fuel_id: 'Drivmedel',
  gearbox_id: 'Växellåda',
  engine: 'Motor',
  number_keys: 'Antal nycklar',
  service_book: 'Servicebok finns?',
  summer_tire: 'Sommardäck finns?',
  winter_tire: 'Vinterdäck finns?',
  dist_belt: 'Kamrem bytt?',
  purchase_price: 'Inköpspris',
  iva_purchase_id: 'VMB / Moms',
  iva_sale_id: 'VMB / Moms',
  sale_price: 'Försäljningspris',
  sale_date: 'Försäljningsdag',
  chassis: 'Chassinummer',
  sale_comments: 'Comments',
  iva_sale_amount: 'Varav moms',
  iva_sale_exclusive: 'Prix ex moms',
  registration_fee: 'Registreringsavgift',
  total_sale: 'Total försäljning',
  name: 'Namn',
  notes: 'Anteckning',
  num_iva: 'Momsnummer',
  organization_number: 'Org.nr',
  payment_method: 'Betalsätt',
  phone: 'Telefon',
  postal_code: 'Postnummer',
  reference: 'Referens',
  reg_num: 'Reg nr',
  state_id: 'Status',
  address: 'Adress',
  street: 'Stad',
  title: 'Titel',
  order_id: 'ID',
  signature_status: 'Signera status',
  payee_alias: 'Mobilnummer',
  payee_ssn: 'Personnummer',
  message: 'Meddelande',
  error_message: 'Felmeddelande',
  error_code: 'Felkod',
  payout_state_id: 'Status',
  comment: 'Kommentar',
  phone: 'Mobilnummer',
  landline: 'Telefon',
  comments_note: 'Kommentarer',
  user_id: 'Användare',
  comments_date: 'Datum',
  agreement_type_id: 'Avtalstyp',
  residual_debt: 'Restskulden löses av',
  price: 'Totalpris',
  client_id: 'Kund',
  terms_other_conditions: 'Övriga upplysningar',
  terms_other_information: 'Övriga villkor',
  client_type_id: 'Kundtyp',
  payment_terms: 'Betalningsvillkor',
  payment_description: 'Betalningsbeskrivning',
  iva_purchase_amount: 'Varav moms',
  iva_purchase_exclusive: 'Prix ex moms',
  iva_id: 'Moms / VMB',
  fair_value: 'Inbytespris',
  middle_price: 'Mellanpris',
  payment_received: 'Summa kontant / handpenning',
  payment_method_forcash: 'Betalsätt för kontant / handpenning',
  installment_amount: 'Avbetalningsbelopp (kreditbelopp/leasing)',
}

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = activities.value.length 
    ? (currentPage.value - 1) * rowPerPage.value + 1 
    : 0
  const 
  lastIndex = activities.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalActivities.value} resultat`;
 //return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalActivities.value } register`
})

const displayActivities = computed(() => {
  if (!Array.isArray(activities.value))
    return []

  return activities.value
    .map(activity => {
    const { changes, detailMode } = getActivityChangeSummary(activity)

    if (detailMode === 'updated' && !changes.length)
      return null

    return {
      ...activity,
      changes,
      createdAtLabel: formatActivityDateTime(activity.created_at),
      descriptionText: getActivityDescription(activity),
      descriptionTitle: getActivityTitle(activity),
      descriptionSubtitle: getActivitySecondaryDescription(activity),
      detailTitle: getActivityDetailTitle(detailMode, changes.length),
      eventLabel: getActivityEventLabel(activity),
      moduleLabel: getActivityModuleLabel(activity.entity_type),
      userInitials: getActivityUserInitials(activity),
      userName: getActivityUserName(activity),
    }
    })
    .filter(Boolean)
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    user_id.value = userData.value ? userData.value.id : null

    if(cleanFilters === true) {
        searchQuery.value = ''
        rowPerPage.value = 10
        currentPage.value = 1;
        supplier_id.value = null;
        user_id.value = null;
        userId.value = null;
    }

    let data = {
        search: searchQuery.value,
        orderByField: 'id',
        orderBy: 'desc',
        limit: rowPerPage.value,
        page: currentPage.value,
        supplier_id: supplier_id.value,
        user_id: userId.value,
    }

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value?.roles?.[0]?.name ?? null

    await activitiesStore.fetchActivities(data)

    activities.value = Array.isArray(activitiesStore.getActivities) ? activitiesStore.getActivities : []
    totalPages.value = activitiesStore.last_page
    totalActivities.value = activitiesStore.activitiesTotalCount
    expandedActivityId.value = activities.value.some(activity => activity.id === expandedActivityId.value)
        ? expandedActivityId.value
        : activities.value[0]?.id ?? null
    
    isRequestOngoing.value = false

    if(role.value === 'SuperAdmin' || role.value === 'Administrator') {
        suppliers.value = activitiesStore.getSuppliers
    }

    users.value = activitiesStore.getUsers
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

function toggleActivity(activityId) {
  expandedActivityId.value = expandedActivityId.value === activityId ? null : activityId
}

function isActivityExpanded(activityId) {
  return expandedActivityId.value === activityId
}

function parseActivityMetadata(activity) {
  if (!activity?.metadata)
    return {}

  if (typeof activity.metadata === 'object')
    return activity.metadata

  try {
    return JSON.parse(activity.metadata)
  } catch (error) {
    return {}
  }
}

function formatActivityFieldLabel(field) {
  if (!field)
    return 'Fält'

  if (typeof field === 'object' && typeof field.label === 'string' && field.label.trim())
    return field.label.trim()

  const { displayKey, sourceKey } = resolveConfiguredActivityField(field)

  return activityFieldLabels[displayKey]
    ?? activityFieldLabels[sourceKey]
    ?? String(displayKey)
      .replace(/_name$/g, '')
      .replace(/_/g, ' ')
      .replace(/\b\w/g, letter => letter.toUpperCase())
}

function normalizeActivityValue(value) {
  if (typeof value === 'string') {
    const trimmedValue = value.trim()
    const normalizedString = trimmedValue.toLowerCase()

    if (!trimmedValue || normalizedString === 'null' || normalizedString === 'undefined')
      return null

    return trimmedValue
  }

  return value
}

function applyActivityFieldFormatting(value, fieldEntry) {
  const normalizedValue = normalizeActivityValue(value)

  if (normalizedValue === null || normalizedValue === undefined || normalizedValue === '')
    return 'Tomt'

  let formattedValue = String(normalizedValue)

  if (fieldEntry?.prefix && !formattedValue.startsWith(fieldEntry.prefix))
    formattedValue = `${fieldEntry.prefix}${formattedValue}`

  if (fieldEntry?.suffix && !formattedValue.endsWith(fieldEntry.suffix))
    formattedValue = `${formattedValue}${fieldEntry.suffix}`

  return formattedValue
}

function formatActivityValue(value, fieldEntry = null) {
  const normalizedValue = normalizeActivityValue(value)

  if (normalizedValue === null || normalizedValue === undefined || normalizedValue === '')
    return 'Tomt'

  if (typeof fieldEntry?.formatter === 'function') {
    return applyActivityFieldFormatting(fieldEntry.formatter(normalizedValue), fieldEntry)
  }

  if (typeof normalizedValue === 'boolean')
    return applyActivityFieldFormatting(normalizedValue ? 'Ja' : 'Nej', fieldEntry)

  if (Array.isArray(normalizedValue)) {
    const formattedValues = normalizedValue
      .filter(hasRenderableActivityValue)
      .map(item => formatActivityValue(item))

    return applyActivityFieldFormatting(formattedValues.length ? formattedValues.join(', ') : 'Tomt', fieldEntry)
  }

  if (typeof normalizedValue === 'object') {
    const flattenedValues = Object.values(normalizedValue)
      .filter(hasRenderableActivityValue)
      .map(item => formatActivityValue(item))

    return applyActivityFieldFormatting(flattenedValues.length ? flattenedValues.join(', ') : 'Tomt', fieldEntry)
  }

  return applyActivityFieldFormatting(normalizedValue, fieldEntry)
}

function isBillingDetailRenderType(change) {
  return change?.renderType === 'billing-detail'
}

function getBillingDetailRows(rawDetail) {
  if (!rawDetail)
    return []

  try {
    const parsed = typeof rawDetail === 'string' ? JSON.parse(rawDetail) : rawDetail

    if (!Array.isArray(parsed))
      return []

    return parsed
      .map(row => {
        if (!Array.isArray(row))
          return null

        const noteCell = row.find(column => column?.note)

        if (noteCell?.note) {
          return {
            note: noteCell.note,
          }
        }

        const values = {}

        row.forEach(column => {
          if (column?.id)
            values[column.id] = column.value
        })

        return {
          values,
        }
      })
      .filter(Boolean)
  } catch {
    return []
  }
}

function hasRenderableActivityValue(value) {
  const normalizedValue = normalizeActivityValue(value)

  if (normalizedValue === null || normalizedValue === undefined)
    return false

  if (typeof normalizedValue === 'string')
    return normalizedValue !== ''

  if (Array.isArray(normalizedValue))
    return normalizedValue.some(hasRenderableActivityValue)

  if (typeof normalizedValue === 'object')
    return Object.values(normalizedValue).some(hasRenderableActivityValue)

  return true
}

function normalizeActivityValueMap(values) {
  if (!values || Array.isArray(values) || typeof values !== 'object')
    return {}

  return values
}

function collectActivityRecipientValues(...valueGroups) {
  return [...new Set(
    valueGroups
      .flatMap(value => Array.isArray(value) ? value : [value])
      .map(value => normalizeActivityValue(value))
      .filter(value => value !== null && value !== undefined && value !== '')
      .map(value => String(value)),
  )]
}

function resolveActivityRecipients(metadata) {
  return {
    emails: collectActivityRecipientValues(metadata?.emails, metadata?.email, metadata?.recipient),
    phones: collectActivityRecipientValues(metadata?.phones, metadata?.phone, metadata?.recipient_phone),
  }
}

function buildSentActivityChanges(metadata) {
  const { emails, phones } = resolveActivityRecipients(metadata)

  return [
    ...(emails.length
      ? [{
        key: 'emails',
        label: 'E-post',
        renderType: null,
        rawValue: emails,
        type: 'created',
        value: emails.join(', '),
      }]
      : []),
    ...(phones.length
      ? [{
        key: 'phones',
        label: 'Telefon',
        renderType: null,
        rawValue: phones,
        type: 'created',
        value: phones.join(', '),
      }]
      : []),
  ]
}

function getActivityRecipientSummary(activity) {
  const metadata = parseActivityMetadata(activity)
  const { emails, phones } = resolveActivityRecipients(metadata)
  const parts = []

  if (emails.length)
    parts.push(`E-post: ${emails.join(', ')}`)

  if (phones.length)
    parts.push(`Telefon: ${phones.join(', ')}`)

  return parts.length ? `Skickad till ${parts.join(' | ')}` : null
}

function resolveConfiguredActivityField(field) {
  if (field && typeof field === 'object') {
    const displayKey = String(field.displayKey ?? field.key ?? field.sourceKey ?? '')
    const sourceKeys = Array.isArray(field.sourceKeys) && field.sourceKeys.length
      ? field.sourceKeys.map(source => String(source))
      : [String(field.sourceKey ?? (displayKey.endsWith('_name') ? displayKey.replace(/_name$/g, '_id') : displayKey))]
    const sourceKey = sourceKeys[0]

    return {
      ...field,
      displayKey,
      sourceKey,
      sourceKeys,
    }
  }

  const normalizedField = String(field ?? '')

  if (normalizedField.endsWith('_name')) {
    return {
      displayKey: normalizedField,
      sourceKey: normalizedField.replace(/_name$/g, '_id'),
      sourceKeys: [normalizedField.replace(/_name$/g, '_id')],
    }
  }

  return {
    displayKey: normalizedField,
    sourceKey: normalizedField,
    sourceKeys: [normalizedField],
  }
}

function getActivityFieldSourceKeys(fieldEntry) {
  return Array.isArray(fieldEntry?.sourceKeys) && fieldEntry.sourceKeys.length
    ? fieldEntry.sourceKeys
    : [fieldEntry?.sourceKey].filter(Boolean)
}

function getActivityFieldRawValue(values, fieldEntry) {
  const sourceKeys = getActivityFieldSourceKeys(fieldEntry)

  if (sourceKeys.length <= 1)
    return values[fieldEntry.sourceKey]

  return sourceKeys.reduce((accumulator, sourceKey) => {
    accumulator[sourceKey] = values[sourceKey] ?? null

    return accumulator
  }, {})
}

function hasRenderableActivityFieldValue(values, fieldEntry) {
  return getActivityFieldSourceKeys(fieldEntry).some(sourceKey => hasRenderableActivityValue(values[sourceKey]))
}

function hasActivityFieldKey(values, fieldEntry) {
  return getActivityFieldSourceKeys(fieldEntry).some(sourceKey => Object.prototype.hasOwnProperty.call(values, sourceKey))
}

function shouldRenderActivityField(fieldEntry, activity, oldValues = {}, newValues = {}) {
  if (typeof fieldEntry?.visibleWhen !== 'function')
    return true

  return fieldEntry.visibleWhen({
    activity,
    oldValues,
    newValues,
    values: {
      ...oldValues,
      ...newValues,
    },
  }) !== false
}

function getActivityVisibleFieldEntries(activity, availableKeys, oldValues = {}, newValues = {}) {
  const visibleFields = getActivityVisibleFields(activity?.entity_type)

  if (!Array.isArray(visibleFields)) {
    return availableKeys.map(key => resolveConfiguredActivityField(key))
  }

  return visibleFields
    .map(field => resolveConfiguredActivityField(field))
    .filter(fieldEntry => getActivityFieldSourceKeys(fieldEntry).some(sourceKey => availableKeys.includes(sourceKey)))
    .filter(fieldEntry => shouldRenderActivityField(fieldEntry, activity, oldValues, newValues))
}

function getActivityDetailMode(activity, oldValues, newValues) {
  const normalizedAction = String(activity?.action_type ?? '').toLowerCase()
  const hasOldValues = Object.keys(oldValues).length > 0
  const hasNewValues = Object.keys(newValues).length > 0

  if (normalizedAction.includes('create'))
    return 'created'

  if (normalizedAction.includes('delete'))
    return 'deleted'

  if (hasOldValues && hasNewValues)
    return 'updated'

  if (hasNewValues)
    return 'created'

  if (hasOldValues)
    return 'deleted'

  return 'default'
}

function buildCreatedActivityChanges(activity, newValues) {
  return getActivityVisibleFieldEntries(activity, Object.keys(newValues), {}, newValues)
    .filter(fieldEntry => hasRenderableActivityFieldValue(newValues, fieldEntry))
    .map(fieldEntry => {
      const rawValue = getActivityFieldRawValue(newValues, fieldEntry)

      return ({
      key: fieldEntry.displayKey,
      label: formatActivityFieldLabel(fieldEntry),
      renderType: fieldEntry.renderType ?? null,
      rawValue,
      type: 'created',
      value: formatActivityValue(rawValue, fieldEntry),
    })
    })
}

function buildDeletedActivityChanges(activity, oldValues) {
  return getActivityVisibleFieldEntries(activity, Object.keys(oldValues), oldValues, {})
    .filter(fieldEntry => hasRenderableActivityFieldValue(oldValues, fieldEntry))
    .map(fieldEntry => {
      const rawValue = getActivityFieldRawValue(oldValues, fieldEntry)

      return ({
      key: fieldEntry.displayKey,
      label: formatActivityFieldLabel(fieldEntry),
      renderType: fieldEntry.renderType ?? null,
      rawValue,
      type: 'deleted',
      value: formatActivityValue(rawValue, fieldEntry),
    })
    })
}

function buildUpdatedActivityChanges(activity, oldValues, newValues) {
  const keys = [...new Set([...Object.keys(oldValues), ...Object.keys(newValues)])]

  return getActivityVisibleFieldEntries(activity, keys, oldValues, newValues)
    .map(fieldEntry => {
      const hasOldValue = hasActivityFieldKey(oldValues, fieldEntry)
      const hasNewValue = hasActivityFieldKey(newValues, fieldEntry)
      const rawOldValue = hasOldValue ? getActivityFieldRawValue(oldValues, fieldEntry) : null
      const rawNewValue = hasNewValue ? getActivityFieldRawValue(newValues, fieldEntry) : null
      const hasRenderableOldValue = hasRenderableActivityValue(rawOldValue)
      const hasRenderableNewValue = hasRenderableActivityValue(rawNewValue)

      if (!hasRenderableOldValue && !hasRenderableNewValue)
        return null

      const oldValue = hasOldValue ? formatActivityValue(rawOldValue, fieldEntry) : null
      const newValue = hasNewValue ? formatActivityValue(rawNewValue, fieldEntry) : null

      if (oldValue === newValue)
        return null

      return {
        key: fieldEntry.displayKey,
        label: formatActivityFieldLabel(fieldEntry),
        renderType: fieldEntry.renderType ?? null,
        oldRawValue: hasOldValue ? oldValues[fieldEntry.sourceKey] : null,
        newRawValue: hasNewValue ? newValues[fieldEntry.sourceKey] : null,
        type: 'updated',
        newValue: hasRenderableNewValue ? newValue : null,
        oldValue: hasRenderableOldValue ? oldValue : null,
      }
    })
    .filter(Boolean)
}

function getActivityChangeSummary(activity) {
  const metadata = parseActivityMetadata(activity)
  const oldValues = normalizeActivityValueMap(metadata.old_values)
  const newValues = normalizeActivityValueMap(metadata.new_values)
  const detailMode = getActivityDetailMode(activity, oldValues, newValues)

  if (detailMode === 'created') {
    return {
      detailMode,
      changes: buildCreatedActivityChanges(activity, newValues),
    }
  }

  if (detailMode === 'deleted') {
    return {
      detailMode,
      changes: buildDeletedActivityChanges(activity, oldValues),
    }
  }

  if (detailMode === 'updated') {
    return {
      detailMode,
      changes: buildUpdatedActivityChanges(activity, oldValues, newValues),
    }
  }

  const sentChanges = buildSentActivityChanges(metadata)

  if (sentChanges.length) {
    return {
      detailMode: 'sent',
      changes: sentChanges,
    }
  }

  return {
    detailMode,
    changes: [],
  }
}

function getActivityDetailTitle(detailMode, changesLength) {
  if (!changesLength)
    return 'Beskrivning'

  return activityDetailTitles[detailMode] ?? 'Ändring'
}

function getActivityModuleLabel(entityType) {
  return activityModuleLabels[entityType] ?? formatActivityFieldLabel(entityType)
}

function getActivityModuleSingularLabel(entityType) {
  return activityModuleSingularLabels[entityType] ?? formatActivityFieldLabel(entityType)
}

function resolveActivityActionKey(actionType) {
  const normalizedAction = String(actionType ?? '').toLowerCase()
  const orderedKeys = ['credit', 'reminder', 'resend', 'cancelled', 'unpaid', 'paid', 'delete', 'update', 'create', 'signed', 'delivered', 'reviewed', 'send', 'sell', 'cancel', 'revoke']

  return orderedKeys.find(key => normalizedAction.includes(key)) ?? null
}

function getActivityEventLabel(activity) {
  const actionKey = resolveActivityActionKey(activity?.action_type)
  const actionLabel = actionKey
    ? activityActionLabels[actionKey]
    : activity?.description || 'aktivitet'

  return `${getActivityModuleSingularLabel(activity?.entity_type)} ${actionLabel}`
}

function getActivityDescription(activity) {
  return activity?.title || activity?.description || '-'
}

function getActivityTitle(activity) {
  return activity?.title || activity?.description || '-'
}

function getActivitySecondaryDescription(activity) {
  const recipientSummary = getActivityRecipientSummary(activity)

  if (recipientSummary)
    return recipientSummary

  const title = normalizeActivityValue(activity?.title)
  const description = normalizeActivityValue(activity?.description)

  if (!description || description === title)
    return null

  return description
}

function getActivityUserName(activity) {
  return [activity?.user?.name, activity?.user?.last_name].filter(Boolean).join(' ') || 'Okänd användare'
}

function getActivityUserInitials(activity) {
  const initialsSource = getActivityUserName(activity)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(part => part[0])
    .join('')

  return initialsSource || 'NA'
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + "...";
  }
  return text;
};

function formatActivityDateTime(dateString) {
  const date = new Date(dateString)

  if (Number.isNaN(date.getTime()))
    return '-'

  const now = new Date()
  const yesterday = new Date(now)
  yesterday.setDate(now.getDate() - 1)

  const rawDateLabel = date.toDateString() === now.toDateString()
    ? 'Idag'
    : date.toDateString() === yesterday.toDateString()
      ? 'Igår'
      : date.toLocaleDateString('sv-SE', {
        day: '2-digit',
        month: 'short',
        ...(date.getFullYear() !== now.getFullYear() ? { year: 'numeric' } : {}),
      })

  const dateLabel = rawDateLabel.replace('.', '')
  const timeLabel = date.toLocaleTimeString('sv-SE', {
    hour: '2-digit',
    minute: '2-digit',
  })

  return `${dateLabel}, ${timeLabel}`
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});

</script>

<template>
  <section class="page-section" ref="sectionEl">
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

    <VCard 
      class="page-activities card-fill pa-6 d-flex flex-column" 
      :class="windowWidth < 1024 ? '' : ''"
    >
      
      <DefaultLayoutWithoutVerticalNav />

      <div class="d-flex w-auto margin-activities">
        <VBtn
          class="btn-light"
          :to="{ name: 'dashboard-panel' }"
        >
          <VIcon icon="custom-return" size="24" />
          <span v-if="windowWidth < 1024">Gå ut</span>
          <span v-else>Tillbaka</span>
        </VBtn>
      </div>

      <div>
        <div class="d-flex align-center w-auto margin-activities pb-4 my-4 border-bottom-settings">
          <span class="title-activities">Aktivitetshistorik</span>
        </div>

        <VCardText class="d-flex align-center justify-space-between gap-2 margin-activities p-0">
            <!-- 👉 Search  -->
            <div class="search">
                <VTextField v-model="searchQuery" placeholder="Sök" clearable />
            </div>

            <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

            <div
                v-if="!$vuetify.display.mdAndDown"
                class="d-flex align-center empty-select"
                >
                <VSelect
                    v-model="mode"
                    class="custom-select-hover activity-mode-select"
                    :items="modeOptions"
                    item-title="title"
                    item-value="value"
                >
                    <template #selection="{ item }">
                        <div class="activity-mode-option">
                            <VIcon :icon="item.raw.icon" size="24" />
                            <span>{{ item.raw.title }}</span>
                        </div>
                    </template>

                    <template #item="{ props, item }">
                        <VListItem v-bind="props">
                            <template #prepend>
                            <VIcon :icon="item.raw.icon" size="24" class="activity-mode-item-icon" />
                            </template>
                        </VListItem>
                    </template>
                </VSelect>
            </div>

            <VBtn
                class="btn-transparent px-3"
                v-if="role !== 'Supplier' && role !== 'User'"
                @click="isFilterDialogVisible = true"
                :class="windowWidth > 1023 ? 'd-none' : 'd-flex'"
                >
                <VIcon icon="custom-profile" size="24" />
            </VBtn>

            <VBtn
                class="btn-transparent px-3"
                :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"
                @click="isFilterDialogVisible = true"
                >
                <VIcon icon="custom-filter" size="24" />
                <span :class="windowWidth < 1024 ? 'd-none' : 'd-flex'">Filtrera efter</span>
            </VBtn>

            <VBtn 
              class="btn-white-2 px-3"
              :class="windowWidth >= 1024 ? 'd-none' : 'd-flex'"
              @click="filtreraMobile = true"
            >
              <VIcon icon="custom-filter" size="24" />
            </VBtn>

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
      </div>

      <div
        v-if="!$vuetify.display.mdAndDown"
        v-show="displayActivities.length"
      >
        <VTable class="activities-table margin-activities mt-4">
          <colgroup>
            <col style="width: 190px">
            <col style="width: 190px">
            <col>
            <col style="width: 230px">
            <col style="width: 170px">
            <col style="width: 72px">
          </colgroup>

          <thead>
            <tr>
              <th scope="col" class="text-center">Modul</th>
              <th scope="col" class="text-center">Händelse</th>
              <th scope="col" class="text-center">Beskrivning</th>
              <th scope="col" class="text-center">Användare</th>
              <th scope="col" class="text-center">Datum & Tid</th>
              <th scope="col" class="text-center"></th>
            </tr>
          </thead>

          <tbody class="activities-table-body">
            <template v-for="activity in displayActivities" :key="activity.id">
              <tr
                class="activity-summary-row"
                :class="{ 'is-expanded': isActivityExpanded(activity.id) }"
                @click="toggleActivity(activity.id)"
              >
                <td class="text-center">
                  <div class="activity-module-pill">
                    <VIcon :icon="activity.icon || 'custom-circle-help'" size="16" />
                    <span>{{ activity.moduleLabel }}</span>
                  </div>
                </td>

                <td class="text-center w-20">
                  <span class="activity-event-text">
                    {{ activity.eventLabel }}
                  </span>
                </td>

                <td class="text-center">
                  <div class="activity-description-text">
                    <span class="activity-description-title">{{ activity.descriptionTitle }}</span>
                    <span v-if="activity.descriptionSubtitle" class="activity-description-subtitle">{{ activity.descriptionSubtitle }}</span>
                  </div>
                </td>

                <td style="width: 1%; white-space: nowrap">
                    <div class="d-flex align-center gap-x-1">
                        <VAvatar
                            variant="outlined"
                            size="38"
                        >
                            <VImg
                                v-if="activity.user.avatar"
                                style="border-radius: 50%"
                                :src="themeConfig.settings.urlStorage + activity.user.avatar"
                            />
                            <PresetAvatarImage
                                v-else
                                :avatar-id="activity.user?.user_detail?.avatar_id"
                            />
                        </VAvatar>
                        <div class="d-flex flex-column">
                            <span class="font-weight-medium">
                                {{ activity.user.name }} {{ activity.user.last_name ?? "" }}
                            </span>
                            <span class="text-sm text-disabled">
                                <VTooltip 
                                    v-if="activity.user.email && activity.user.email.length > 20"
                                    location="bottom">
                                    <template #activator="{ props }">
                                        <span v-bind="props" class="cursor-pointer">
                                        {{ truncateText(activity.user.email, 20) }}
                                        </span>
                                    </template>
                                    <span>{{ activity.user.email }}</span>
                                    </VTooltip>
                                    <span class="text-sm text-disabled"v-else>{{ activity.user.email }}</span>
                            </span>
                        </div>
                    </div>
                </td> 

                <td class="text-center">
                  <span class="activity-date-text">{{ activity.createdAtLabel }}</span>
                </td>

                <td class="text-center">
                  <VBtn
                    icon
                    variant="text"
                    class="activity-toggle-btn"
                    @click.stop="toggleActivity(activity.id)"
                  >
                    <VIcon
                      icon="custom-chevron-down"
                      size="18"
                      class="activity-toggle-icon"
                      :class="{ 'is-open': isActivityExpanded(activity.id) }"
                    />
                  </VBtn>
                </td>
              </tr>

              <tr
                v-if="isActivityExpanded(activity.id)"
                class="activity-detail-row"
              >
                <td colspan="6">
                  <div class="activity-detail-panel">
                    <div class="activity-detail-title">{{ activity.detailTitle }}</div>

                    <div v-if="activity.changes.length" class="activity-detail-list">
                      <div
                        v-for="change in activity.changes"
                        :key="`${activity.id}-${change.key}`"
                        class="activity-detail-item"
                        :class="{ 'activity-detail-item--rich': isBillingDetailRenderType(change) }"
                      >
                        <div class="activity-detail-label">{{ change.label }}:</div>

                        <div class="activity-detail-values" :class="{ 'is-rich': isBillingDetailRenderType(change) }">
                          <template v-if="change.type === 'created'">
                            <template v-if="isBillingDetailRenderType(change)">
                              <div class="activity-detail-created activity-detail-rich">
                                <div class="billing-detail-tooltip">
                                  <table
                                    v-if="getBillingDetailRows(change.rawValue).length"
                                    class="billing-detail-table"
                                  >
                                    <tbody>
                                      <tr
                                        v-for="(row, rowIndex) in getBillingDetailRows(change.rawValue)"
                                        :key="rowIndex"
                                      >
                                        <td v-if="row.note" colspan="5">{{ row.note }}</td>
                                        <template v-else>
                                          <td class="w-40">{{ row.values[1] ?? '-' }}</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[2] ?? '0.00') }}</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[3] ?? '0.00') }} kr</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[4] ?? '0.00') }} kr</td>
                                          <td v-if="row.values[5] > 0" class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[5] ?? '0.00') }} %</td>
                                        </template>
                                      </tr>
                                    </tbody>
                                  </table>
                                  <span v-else>Ingen detaljinformation</span>
                                </div>
                              </div>
                            </template>
                            <span v-else class="activity-detail-created">{{ change.value }}</span>
                          </template>

                          <template v-else-if="change.type === 'deleted'">
                            <template v-if="isBillingDetailRenderType(change)">
                              <div class="activity-detail-deleted activity-detail-rich">
                                <div class="billing-detail-tooltip">
                                  <table
                                    v-if="getBillingDetailRows(change.rawValue).length"
                                    class="billing-detail-table"
                                  >
                                    <tbody>
                                      <tr
                                        v-for="(row, rowIndex) in getBillingDetailRows(change.rawValue)"
                                        :key="rowIndex"
                                      >
                                        <td v-if="row.note" colspan="5">{{ row.note }}</td>
                                        <template v-else>
                                          <td class="w-40">{{ row.values[1] ?? '-' }}</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[2] ?? '0.00') }}</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[3] ?? '0.00') }} kr</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[4] ?? '0.00') }} kr</td>
                                          <td v-if="row.values[5] > 0" class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[5] ?? '0.00') }} %</td>
                                        </template>
                                      </tr>
                                    </tbody>
                                  </table>
                                  <span v-else>Ingen detaljinformation</span>
                                </div>
                              </div>
                            </template>
                            <span v-else class="activity-detail-deleted">{{ change.value }}</span>
                          </template>

                          <template v-else>
                            <template v-if="isBillingDetailRenderType(change)">
                              <div v-if="change.oldValue !== null" class="activity-detail-old activity-detail-rich">
                                <div class="billing-detail-tooltip">
                                  <table
                                    v-if="getBillingDetailRows(change.oldRawValue).length"
                                    class="billing-detail-table"
                                  >
                                    <tbody>
                                      <tr
                                        v-for="(row, rowIndex) in getBillingDetailRows(change.oldRawValue)"
                                        :key="`old-${rowIndex}`"
                                      >
                                        <td v-if="row.note" colspan="5">{{ row.note }}</td>
                                        <template v-else>
                                          <td class="w-40">{{ row.values[1] ?? '-' }}</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[2] ?? '0.00') }}</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[3] ?? '0.00') }} kr</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[4] ?? '0.00') }} kr</td>
                                          <td v-if="row.values[5] > 0" class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[5] ?? '0.00') }} %</td>
                                        </template>
                                      </tr>
                                    </tbody>
                                  </table>
                                  <span v-else>Ingen detaljinformation</span>
                                </div>
                              </div>
                              <VIcon
                                v-if="change.oldValue !== null && change.newValue !== null"
                                icon="custom-arrow-right"
                                size="16"
                                class="activity-detail-arrow activity-detail-arrow-rich"
                              />
                              <div v-if="change.newValue !== null" class="activity-detail-new activity-detail-rich">
                                <div class="billing-detail-tooltip">
                                  <table
                                    v-if="getBillingDetailRows(change.newRawValue).length"
                                    class="billing-detail-table"
                                  >
                                    <tbody>
                                      <tr
                                        v-for="(row, rowIndex) in getBillingDetailRows(change.newRawValue)"
                                        :key="`new-${rowIndex}`"
                                      >
                                        <td v-if="row.note" colspan="5">{{ row.note }}</td>
                                        <template v-else>
                                          <td class="w-40">{{ row.values[1] ?? '-' }}</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[2] ?? '0.00') }}</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[3] ?? '0.00') }} kr</td>
                                          <td class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[4] ?? '0.00') }} kr</td>
                                          <td v-if="row.values[5] > 0" class="text-right" :class="row.values[5] > 0 ? 'w-15' : 'w-20'">{{ formatNumber(row.values[5] ?? '0.00') }} %</td>
                                        </template>
                                      </tr>
                                    </tbody>
                                  </table>
                                  <span v-else>Ingen detaljinformation</span>
                                </div>
                              </div>
                              <span v-else class="activity-detail-muted">Borttagen</span>
                            </template>
                            <template v-else>
                              <span v-if="change.oldValue !== null" class="activity-detail-old">{{ change.oldValue }}</span>
                              <VIcon
                                v-if="change.oldValue !== null && change.newValue !== null"
                                icon="custom-arrow-right"
                                size="16"
                                class="activity-detail-arrow"
                              />
                              <span v-if="change.newValue !== null" class="activity-detail-new">{{ change.newValue }}</span>
                              <span v-else class="activity-detail-muted">Borttagen</span>
                            </template>
                          </template>
                        </div>
                      </div>
                    </div>

                    <div v-else class="activity-detail-empty">
                      {{ activity.descriptionText }}
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </VTable>

        <VCardText
          v-if="displayActivities.length"
          :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
          class="align-center flex-wrap gap-4 mt-6 p-0 margin-activities"
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
      </div>

      <div
        v-if="!isRequestOngoing && !displayActivities.length"
        class="activity-empty-state margin-activities"
      >
        Inga aktiviteter hittades.
      </div>
    </VCard>
  </section>

    <!-- Filter Dialog -->
  <VDialog
    v-model="isFilterDialogVisible"
    persistent
    class="action-dialog"
  >
    <VBtn
      icon
      class="btn-white close-btn"
      @click="isFilterDialogVisible = false"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <VCard flat class="card-form">
      <VCardText class="dialog-title-box">
        <VIcon size="32" icon="custom-filter" class="action-icon" />
        <div class="dialog-title">
          Filtrera
        </div>
      </VCardText>
      
      <VCardText class="pt-0">
        <VRow class="pt-3">
          <VCol 
            cols="12" md="12" 
            v-if="role === 'SuperAdmin' || role === 'Administrator'"
            class="pb-0">
            <AppAutocomplete
              prepend-icon="custom-profile"
              v-model="supplier_id"
              placeholder="Leverantörer"
              :items="suppliers"
              :item-title="item => item.full_name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              class="selector-user selector-truncate"
            />
          </VCol>
          <VCol cols="12" md="12" class="pb-0">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Användare" />
             <AppAutocomplete
              v-model="userId"
              :items="users"
              :item-title="item => item.user_name"
              :item-value="item => item.user_id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              :menu-props="{ maxHeight: '300px' }"
            />
          </VCol>
          <VCol cols="12" md="12" class="pb-0">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Modul" />
            <AppAutocomplete
              v-model="module_id"
              :items="modules"
              :item-title="item => item.name"
              :item-value="item => item.id"
              autocomplete="off"
              clearable
              clear-icon="tabler-x"
              :menu-props="{ maxHeight: '300px' }"/>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0">
        <VBtn
          class="btn-light"
          @click="fetchData(true); isFilterDialogVisible = false">
            Rensa filter
        </VBtn>
        <VBtn class="btn-gradient" @click="isFilterDialogVisible = false">
            Visa resultat
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <MobileBottomBar :nav-items="navItems" />
</template>

<style lang="scss">

    .navigation-bar {
        background: linear-gradient(90deg, #eafff1 0%, #eafff8 50%, #ecffff 100%) !important;
        inset-block-start: 0rem;
        padding: 24px 24px;
        position: fixed;
        left: 0;
        right: 0;
        top: 0;
        z-index: 9999;

        @media (max-width: 1023px) {
        padding: 16px 24px;
        }
    }

    .margin-activities {
        margin: 0 96px;

        @media (max-width: 1023px) {
            margin: 0;
        }
    }

    .page-activities .selector-user {
      .v-input__control {
        background: transparent !important;
        padding-top: 0 !important;

        .v-field,
        .v-field__overlay {
          background: transparent !important;
        }
      }
    }

    .title-activities {
        color: #1C2925;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: 0;
        line-height: 100%;
    }

    .activity-mode-option {
      align-items: center;
      color: #454545;
      display: inline-flex;
      gap: 8px;
      line-height: 16px;
    }

    .activity-mode-option .v-icon,
    .activity-mode-item-icon {
      color: #454545 !important;
    }

    .activities-table {
        background-color: #FFFFFF !important;
        border-radius: 16px !important;
        overflow: hidden;
    }

    .activities-table .v-table__wrapper thead th:first-child {
        border-top-left-radius: 16px !important;
        border-bottom-left-radius: 0 !important;
    }

    .activities-table .v-table__wrapper thead th:last-child {
        border-top-right-radius: 16px !important;
        border-bottom-right-radius: 0 !important;
    }

    .activities-table .v-table__wrapper {
        overflow: hidden;
    }

    .activities-table table {
        border-collapse: collapse;
        width: 100%;
    }

    .activities-table thead tr {
        background-color: #F5F8F6;
    }

    .activity-summary-row {
        cursor: pointer;
    }

    .activity-summary-row td {
        background-color: #FFFFFF;
        border-bottom: 1px solid #E7E7E7 !important;
        line-height: 16px;
        letter-spacing: 0;
        color: #1C2925 !important;
        padding: 22px 24px !important;
        transition: background-color 0.2s ease;
        vertical-align: middle;
    }

    .activity-summary-row:hover td {
        background-color: #FCFDFC;
    }

    .activity-summary-row.is-expanded td {
        border-bottom-color: transparent !important;
    }

    .activity-module-pill {
        align-items: center;
        background-color: #E7E7E7;
        border-radius: 56px;
        color: #797979;
        display: inline-flex;
        gap: 4px;
        min-height: 32px;
        padding: 8px;
        white-space: nowrap;
    }

    .activity-module-pill .v-icon {
        color: #878787 !important;
    }

    .activity-module-pill span {
        font-size: 14px;
        line-height: 16px;
        letter-spacing: 0;
        color: #878787 !important;
    }

    .activity-event-text {
        font-weight: 600;
        line-height: 16px;
        letter-spacing: 0;
        text-align: center;
        color: #454545;
    }

    .activity-description-text {
        display: flex;
        flex-direction: column;
        gap: 4px;
        line-height: 16px;
        letter-spacing: 0;
        color: #454545;
    }

    .activity-description-title {
      color: #454545;
    }

    .activity-description-subtitle {
      color: #878787;
      font-size: 12px;
      line-height: 16px;
    }

    .activity-user {
        align-items: center;
        display: flex;
        gap: 12px;
        min-width: 0;
    }

    .activity-user-avatar {
        background-color: #F1F3F2 !important;
        border: 1px solid #E5EBE8;
        color: #7E8985 !important;
        flex-shrink: 0;
    }

    .activity-user-initials {
        font-size: 13px;
        font-weight: 700;
    }

    .activity-user-copy {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .activity-user-name {
        color: #2C3A35;
        font-weight: 600;
    }

    .activity-user-caption {
        color: #9CA4A0;
        font-size: 12px;
    }

    .activity-date-text {
        font-weight: 400;
        line-height: 16px;
        letter-spacing: 0;
        text-align: center;
        color: #454545;
        white-space: nowrap;
    }

    .activity-toggle-btn {
        color: #006D5C !important;
    }

    .activity-toggle-icon {
        transition: transform 0.2s ease;
    }

    .activity-toggle-icon.is-open {
        transform: rotate(180deg);
    }

    .activity-detail-row td {
        background-color: #F6F6F6 !important;
        border-bottom: 1px solid #EEF2F0 !important;
        padding: 0 24px 24px !important;
    }

    .activity-detail-panel {
        background-color: #F6F6F6;
        color: #4F5955;
        padding: 16px 48px;
    }

    .activity-detail-title {
        color: #BFBFBF;
        font-weight: 400;
        font-size: 12px;
        line-height: 16px;
        letter-spacing: 0;
        margin-bottom: 4px;
    }

    .activity-detail-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .activity-detail-item {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .activity-detail-item--rich {
      align-items: flex-start;
      flex-direction: column;
    }

    .activity-detail-label {
        color: #878787;
        font-weight: 400;
        font-size: 12px;
        line-height: 16px;
        letter-spacing: 0;
    }

    .activity-detail-values {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        line-height: 1.5;
    }

    .activity-detail-values.is-rich {
      align-items: stretch;
      flex-direction: column;
      gap: 4px;
      width: 100%;
    }

    .activity-detail-old {
        font-weight: 400;
        font-size: 12px;
        line-height: 16px;
        letter-spacing: 0;
        color: #FF4D4F;
    }

    .activity-detail-deleted {
        font-weight: 400;
        font-size: 12px;
        line-height: 16px;
        letter-spacing: 0;
        color: #FF4D4F;
    }

    .activity-detail-new {
        color: #006D5C;
        font-weight: 400;
        font-size: 12px;
        line-height: 16px;
        letter-spacing: 0;
    }

    .activity-detail-created {
        font-weight: 400;
        font-size: 12px;
        line-height: 16px;
        letter-spacing: 0;
        color: #006D5C;
    }

    .activity-detail-arrow {
        color: #454545 !important;
    }

    .activity-detail-arrow-rich {
      margin: 2px 0 2px 12px;
    }

    .activity-detail-rich {
      display: block;
      max-width: 100%;
      width: 100%;
    }

    .billing-detail-tooltip {
      background-color: transparent;
      border: 0;
      border-radius: 0;
      color: #454545;
      max-width: 100%;
      overflow: hidden;
      padding: 0;
    }

    .billing-detail-table {
      border-collapse: collapse;
      color: #454545;
      font-size: 12px;
      letter-spacing: 0;
      line-height: 16px;
      table-layout: auto;
      width: 100%;
    }

    .activities-table .activity-detail-row .billing-detail-table td {
      height: auto !important;
      border-bottom: 1px solid #E7E7E7 !important;
      color: #454545 !important;
      font-size: 12px !important;
      font-weight: 400 !important;
      padding: 8px 16px 8px 0 !important;
      text-align: left;
      vertical-align: top;
    }

    .activities-table .activity-detail-row .billing-detail-table td:first-child {
      color: #878787 !important;
      min-width: 132px;
    }

    .activities-table .activity-detail-row .billing-detail-table td:last-child {
      padding-right: 0 !important;
    }

    .activities-table .activity-detail-row .billing-detail-table tr:first-child td {
      padding-top: 0;
    }

    .activities-table .activity-detail-row .billing-detail-table tr:last-child td {
      border-bottom: 0;
      padding-bottom: 0;
    }

    .activities-table .activity-detail-row .billing-detail-table td[colspan='5'] {
      color: #878787;
      padding-right: 0;
    }

    .activities-table .activity-detail-row .activity-detail-created .billing-detail-table td,
    .activities-table .activity-detail-row .activity-detail-new .billing-detail-table td {
      border-bottom-color: rgba(0, 140, 145, 0.18) !important;
      color: #006D5C !important;
    }

    .activities-table .activity-detail-row .activity-detail-deleted .billing-detail-table td,
    .activities-table .activity-detail-row .activity-detail-old .billing-detail-table td {
      border-bottom-color: rgba(240, 98, 98, 0.18) !important;
      color: #FF4D4F !important;
    }

    .activities-table .activity-detail-row .activity-detail-created .billing-detail-table td:first-child,
    .activities-table .activity-detail-row .activity-detail-new .billing-detail-table td:first-child {
      color: #006D5C !important;
    }

    .activities-table .activity-detail-row .activity-detail-deleted .billing-detail-table td:first-child,
    .activities-table .activity-detail-row .activity-detail-old .billing-detail-table td:first-child {
      color: #FF4D4F !important;
    }

    .activities-table .activity-detail-row .activity-detail-created .billing-detail-table td[colspan='5'],
    .activities-table .activity-detail-row .activity-detail-new .billing-detail-table td[colspan='5'] {
      color: #006D5C !important;
    }

    .activities-table .activity-detail-row .activity-detail-deleted .billing-detail-table td[colspan='5'],
    .activities-table .activity-detail-row .activity-detail-old .billing-detail-table td[colspan='5'] {
      color: #FF4D4F !important;
    }

    .activity-detail-muted,
    .activity-detail-empty {
        color: #8A948F;
        font-size: 12px;
    }

    .activity-empty-state {
        color: #5F6D67;
        font-size: 14px;
        padding: 40px 0 24px;
    }

    .page-activities {
        margin-top: 80px;
        background: linear-gradient(90deg, #EAFFF1 0%, #EAFFF8 50%, #ECFFFF 100%);

        @media (max-width: 1023px) {
        padding-bottom: 120px !important;
        }
    }

    @media (max-width: 1400px) {
        .activities-table thead th,
        .activity-summary-row td,
        .activity-detail-row td {
            padding-left: 18px !important;
            padding-right: 18px !important;
        }

        .activity-description-text {
            max-width: 320px;
    }
}
</style>

<route lang="yaml">
  meta:
      layout: blank
      action: view
      subject: dashboard
</route>
