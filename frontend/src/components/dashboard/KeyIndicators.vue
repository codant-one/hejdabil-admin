
<script setup>

import { excelParser } from '@/plugins/csv/excelParser'
import { formatNumber } from '@/@core/utils/formatters'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import { themeConfig } from '@themeConfig'
import ExportDateMenu from '@/components/common/ExportDateMenu.vue'
import html2pdf from 'html2pdf.js'
import Dashboard from '@/api/dashboard'

const emit = defineEmits(['filter', 'loading'])

const props = defineProps({
   indicators: {
      type: Object,
      default: () => ({}),
   },
   company: {
      type: Object,
      default: () => ({}),
   },
})

const activeTab = ref(0)
const { width: windowWidth } = useWindowSize()
const exporteraMobile = ref(false)
const filterMenuVisible = ref(false)
const filterDateRange = ref(null)
const selectedExportType = ref(null)
const isExportMenuVisible = ref(false)
const isExportingFile = ref(false)
const exportDateRange = ref(null)
const lastExportSelectionKey = ref(null)
const lastFilterSelectionKey = ref(null)

watch(isExportMenuVisible, isVisible => {
   if (isVisible)
      lastExportSelectionKey.value = null
})

watch(filterMenuVisible, isVisible => {
   if (isVisible)
      lastFilterSelectionKey.value = null
})

const indicatorsData = computed(() => props.indicators?.indicators ?? props.indicators ?? {})

const formatCurrencyValue = value => formatNumber(value ?? 0)

const getAbbreviatedPriceValue = field => indicatorsData.value?.[`${field}Abbreviated`] ?? formatCurrencyValue(indicatorsData.value?.[field] ?? 0)

const formatVariationValue = value => `${Math.round(Number(value ?? 0))}%`

const normalizeRangeValue = value => {
   if (!value)
      return null

   if (Array.isArray(value)) {
      const start = value[0] ?? null
      const end = value[1] ?? value[0] ?? null

      return start && end ? [start, end] : null
   }

   if (typeof value === 'string') {
      const chunks = value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i).map(item => item.trim()).filter(Boolean)
      if (chunks.length >= 2)
         return [chunks[0], chunks[1]]

      return null
   }

   return null
}

const onFilterDateUpdate = value => {
   const range = normalizeRangeValue(value)
   if (!range)
      return

   const selectionKey = `${range[0]}__${range[1]}`
   if (selectionKey === lastFilterSelectionKey.value)
      return

   lastFilterSelectionKey.value = selectionKey

   filterMenuVisible.value = false
   emit('loading', true)
   emit('filter', {
      date_from: range[0],
      date_to: range[1],
   })
}

const clearFilter = () => {
   filterDateRange.value = null
   lastFilterSelectionKey.value = null
   filterMenuVisible.value = false

   emit('loading', true)
   emit('filter', {})
}

const buildRangeSelectionKey = value => {
   if (!value)
      return null

   const normalize = item => {
      if (!item)
         return ''

      if (item instanceof Date && !Number.isNaN(item.getTime())) {
         const year = item.getFullYear()
         const month = `${item.getMonth() + 1}`.padStart(2, '0')
         const day = `${item.getDate()}`.padStart(2, '0')

         return `${year}-${month}-${day}`
      }

      if (typeof item === 'string')
         return item.trim()

      return String(item)
   }

   if (Array.isArray(value)) {
      const first = normalize(value[0])
      const second = normalize(value[1] ?? value[0])

      return `${first}__${second}`
   }

   if (typeof value === 'string') {
      const chunks = value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i).map(item => item.trim()).filter(Boolean)
      if (chunks.length >= 2)
         return `${chunks[0]}__${chunks[1]}`

      const single = normalize(value)
      return `${single}__${single}`
   }

   const single = normalize(value)
   return `${single}__${single}`
}

const isCompleteRangeSelection = value => {
   if (!value)
      return false

   if (Array.isArray(value))
      return value.length >= 2 && !!value[0] && !!value[1]

   if (typeof value === 'string') {
      const chunks = value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i)
      return chunks.length >= 2 && !!chunks[0]?.trim() && !!chunks[1]?.trim()
   }

   return false
}

const toYmd = value => {
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

const getExportDateRangePayload = () => {
   if (!exportDateRange.value)
      return {}

   if (Array.isArray(exportDateRange.value)) {
      const from = toYmd(exportDateRange.value[0])
      const to = toYmd(exportDateRange.value[1] ?? exportDateRange.value[0])
      if (from && to)
         return { date_from: from, date_to: to }
   }

   if (typeof exportDateRange.value === 'string') {
      const splitByRange = exportDateRange.value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i)
      if (splitByRange.length >= 2) {
         const from = toYmd(splitByRange[0])
         const to = toYmd(splitByRange[1])
         if (from && to)
            return { date_from: from, date_to: to }
      }

      const single = toYmd(exportDateRange.value)
      if (single)
         return { date_from: single, date_to: single }
   }

   if (exportDateRange.value instanceof Date) {
      const single = toYmd(exportDateRange.value)
      if (single)
         return { date_from: single, date_to: single }
   }

   return {}
}

const getExportIndicators = async () => {
   const params = getExportDateRangePayload()

   if (!params.date_from || !params.date_to)
      return indicatorsData.value

   const response = await Dashboard.indicators(params)
   return response?.data?.data ?? {}
}

const buildIndicatorRows = data => ([
   {
      category: 'Bilar i lager',
      count: data?.vehiclesInStock ?? 0,
      value: `${formatCurrencyValue(data?.stockVehiclesPurchasePrice)} kr`,
      variation: formatVariationValue(data?.stockVehiclesMonthlyVariation),
   },
   {
      category: 'Inköpta bilar',
      count: data?.purchasedVehiclesCount ?? 0,
      value: `${formatCurrencyValue(data?.purchasedVehiclesPrice)} kr`,
      variation: formatVariationValue(data?.purchasedVehiclesMonthlyVariation),
   },
   {
      category: 'Sålda bilar',
      count: data?.soldVehiclesCount ?? 0,
      value: `${formatCurrencyValue(data?.soldVehiclesPrice)} kr`,
      variation: formatVariationValue(data?.soldVehiclesMonthlyVariation),
   },
])

const openExportDateMenu = type => {
   exporteraMobile.value = false
   selectedExportType.value = type
   exportDateRange.value = null
   lastExportSelectionKey.value = null
   nextTick(() => {
      isExportMenuVisible.value = true
   })
}

const downloadCSV = async () => {
   exporteraMobile.value = false
   isExportMenuVisible.value = false
   isExportingFile.value = true
   emit('loading', true)

   try {
      const exportData = await getExportIndicators()
      const rows = buildIndicatorRows(exportData).map(item => ({
         Kategori: item.category,
         Antal: item.count,
         Värde: item.value,
         'Månadsförändring': item.variation,
      }))

      excelParser().exportDataFromJSON(rows, 'key-indicators', 'csv')
   } finally {
      isExportingFile.value = false
      emit('loading', false)
   }
}

const downloadPDF = async () => {
   exporteraMobile.value = false
   isExportMenuVisible.value = false
   isExportingFile.value = true
   emit('loading', true)
   const pdfFontFamily = "'Gelion Regular', 'DM Sans', sans-serif"

   const escapeHtml = value => String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;')

   let pdfContainer = null

   try {
      const exportData = await getExportIndicators()
      const rows = buildIndicatorRows(exportData)

      if (document.fonts?.load) {
         await Promise.all([
            document.fonts.load(`400 12px ${pdfFontFamily}`),
            document.fonts.load(`600 32px ${pdfFontFamily}`),
         ])
      }

      const { headerMarkup } = await buildPdfTopHeader({
         company: props.company,
         title: 'Nyckeltal',
         themeConfig,
         escapeHtml,
         showCompanyDetailsWhenLogo: true,
      })

      const rowsMarkup = rows.map(item => `
        <tr style="height: 44px;">
          <td style="width: 28%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.category)}</td>
          <td style="width: 18%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.count)}</td>
          <td style="width: 27%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.value)}</td>
          <td style="width: 27%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.variation)}</td>
        </tr>
      `).join('')

      pdfContainer = document.createElement('div')
      pdfContainer.innerHTML = `
        <div style="font-family: ${pdfFontFamily} !important; color: #454545; background-color: #FFFFFF; letter-spacing: 0; width: 100%;">
          <table style="width: 100%; border-spacing: 0; border-collapse: separate; font-size: 11px; font-weight: 400;">
            <tbody>
              <tr>
                <td>
                  ${headerMarkup}

                  <table style="width: 100%; table-layout: fixed; border-spacing: 0; border-collapse: separate; margin-top: 10px; font-family: ${pdfFontFamily} !important; font-size: 11px;">
                    <thead>
                      <tr style="height: 44px;">
                        <td style="text-align: center; width: 28%; padding: 0 8px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Kategori</td>
                        <td style="text-align: center; width: 18%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Antal</td>
                        <td style="text-align: center; width: 27%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Värde</td>
                        <td style="text-align: center; width: 27%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Månadsförändring</td>
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
            filename: 'key-indicators.pdf',
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

      isExportingFile.value = false
      emit('loading', false)
   }
}

const exportPDFAndCloseMenu = async () => {
   if (isExportingFile.value || !selectedExportType.value)
      return

   isExportingFile.value = true
   try {
      if (selectedExportType.value === 'excel')
         await downloadCSV()
      else
         await downloadPDF()

      isExportMenuVisible.value = false
   } finally {
      selectedExportType.value = null
      isExportingFile.value = false
   }
}

const onDatePickerUpdate = value => {
   if (!selectedExportType.value || !isCompleteRangeSelection(value))
      return

   const selectionKey = buildRangeSelectionKey(value)
   if (!selectionKey || selectionKey === lastExportSelectionKey.value)
      return

   lastExportSelectionKey.value = selectionKey
   isExportMenuVisible.value = false

   if (!isExportingFile.value)
      exportPDFAndCloseMenu()
}

const tabData = computed(() => ({
   0: [
      { icon: 'custom-autofordon', iconColor: '#6E9383', iconBg: '#D8FFE4', value: indicatorsData.value?.vehiclesInStock ?? 0, label: 'Antal' },
      { icon: 'custom-pris-information', iconColor: '#4BBFBF', iconBg: '#C0FEFF', value: getAbbreviatedPriceValue('stockVehiclesPurchasePrice'), suffix: 'kr', label: 'Värde' },
      { icon: 'custom-calendar', iconColor: '#878787', iconBg: '#F6F6F6', value: formatVariationValue(indicatorsData.value?.stockVehiclesMonthlyVariation), suffix: '', label: 'Månadsförändring' },
   ],
   1: [
      { icon: 'custom-autofordon', iconColor: '#6E9383', iconBg: '#D8FFE4', value: indicatorsData.value?.purchasedVehiclesCount ?? 0, label: 'Antal' },
      { icon: 'custom-pris-information', iconColor: '#4BBFBF', iconBg: '#C0FEFF', value: getAbbreviatedPriceValue('purchasedVehiclesPrice'), suffix: 'kr', label: 'Värde' },
      { icon: 'custom-calendar', iconColor: '#878787', iconBg: '#F6F6F6', value: formatVariationValue(indicatorsData.value?.purchasedVehiclesMonthlyVariation), suffix: '', label: 'Månadsförändring' },
   ],
   2: [
      { icon: 'custom-autofordon', iconColor: '#6E9383', iconBg: '#D8FFE4', value: indicatorsData.value?.soldVehiclesCount ?? 0, label: 'Antal' },
      { icon: 'custom-pris-information', iconColor: '#4BBFBF', iconBg: '#C0FEFF', value: getAbbreviatedPriceValue('soldVehiclesPrice'), suffix: 'kr', label: 'Värde' },
      { icon: 'custom-calendar', iconColor: '#878787', iconBg: '#F6F6F6', value: formatVariationValue(indicatorsData.value?.soldVehiclesMonthlyVariation), suffix: '', label: 'Månadsförändring' },
   ],
}))
</script>

<template>
   <VCard title="" class="card-dashboard">
      <VCardTitle class="title-box">
         <div class="title-text">Nyckeltal</div>

         <div class="d-flex gap-2" :class="windowWidth < 1024 ? 'flex-column w-100' : ''">
            <VMenu v-if="windowWidth >= 1024">
               <template #activator="{ props }">
                  <VBtn
                     id="indicator-export-button"
                     class="btn-light w-auto h-40"
                     v-bind="props"
                  >
                     <VIcon icon="custom-export" size="24" />
                     Exportera
                  </VBtn>
               </template>

               <VList>
                  <VListItem @click="openExportDateMenu('pdf')">
                     <VListItemTitle>Exportera PDF</VListItemTitle>
                  </VListItem>
                  <VListItem @click="openExportDateMenu('excel')">
                     <VListItemTitle>Exportera Excel</VListItemTitle>
                  </VListItem>
               </VList>
            </VMenu>

            <VBtn
               v-else
               id="indicator-export-button"
               class="btn-light w-auto h-40"
               block
               @click="exporteraMobile = true"
            >
               <VIcon icon="custom-export" size="24" />
               Exportera
            </VBtn>

            <VBtn
               class="btn-light w-auto h-40"
               block
               @click="clearFilter"
            >
               <VIcon icon="custom-clean" size="24" />
               Rengör filter
            </VBtn>

            <VBtn
              id="indicator-filter-button"
              class="btn-white-2 h-40"
              @click="filterMenuVisible = true"
              block
            >
               <VIcon icon="custom-filter" size="24" color="#6E9383"/>
               <span class="text-gunmetal-3">Filtrera efter datum</span>
            </VBtn>

            <ExportDateMenu
               v-model="filterDateRange"
               v-model:menuVisible="filterMenuVisible"
               :show-activator="false"
               :is-mobile="windowWidth < 1024"
               :reset-on-open="false"
               activator="#indicator-filter-button"
               button-text="Filtrera"
               button-icon="custom-filter"
               picker-label="Filtrera efter datum"
               picker-placeholder="Välj datum"
               @update:modelValue="onFilterDateUpdate"
            />
         </div>
      </VCardTitle>

      <VCardText class="pt-0 h-0 px-4 px-md-6">
         <VTabs
            v-model="activeTab"
            grow          
            :show-arrows="false"
            class="vehicles-dashboard-tabs"
        >
            <VTab :value="0" >
               <VIcon size="24" icon="custom-autofordon" />
               Bilar i lager
            </VTab>
            <VTab :value="1">
               <VIcon size="24" icon="custom-car-open" />
               Inköpta bilar
            </VTab>
            <VTab :value="2">
               <VIcon size="24" icon="custom-car-close" />
               Sålda bilar
            </VTab>
        </VTabs>
      </VCardText>

      <VCardText class="pt-0 flex-grow-1 d-flex flex-column">
        <VWindow v-model="activeTab" class="flex-grow-1">
         <VWindowItem v-for="tab in 3" :key="tab" :value="tab - 1" class="px-md-0 h-100">
            <div class="indicator-grid">
               <div v-for="(card, i) in tabData[tab - 1]" :key="i" class="indicator-card">
                  <VAvatar
                     :color="card.iconBg"
                     :icon="card.icon"
                     variant="flat"
                     size="56"
                     rounded="lg"
                     class="indicator-icon"
                     :style="{ '--icon-color': card.iconColor }"
                  />
                  <div class="indicator-info">
                     <div class="indicator-value">
                        {{ card.value }}<span v-if="card.suffix" class="indicator-suffix">{{ card.suffix }}</span>
                     </div>
                     <div class="indicator-label">{{ card.label }}</div>
                  </div>
               </div>
            </div>
         </VWindowItem>
      </VWindow>
      </VCardText>
   </VCard>

   <VDialog
      v-model="exporteraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
   >
      <VCard>
         <VList>
            <VListItem @click="openExportDateMenu('pdf')">
               <VListItemTitle>Exportera PDF</VListItemTitle>
            </VListItem>

            <VListItem @click="openExportDateMenu('excel')">
               <VListItemTitle>Exportera Excel</VListItemTitle>
            </VListItem>
         </VList>
      </VCard>
   </VDialog>

   <ExportDateMenu
      v-model="exportDateRange"
      v-model:menuVisible="isExportMenuVisible"
      :show-activator="false"
      :is-mobile="windowWidth < 1024"
      :reset-on-open="true"
      activator="#indicator-export-button"
      button-text="Exportera"
      button-icon="custom-export"
      picker-label="Välj datumintervall för export"
      picker-placeholder="Välj datum"
      @update:modelValue="onDatePickerUpdate"
   />
</template>

<style lang="scss">

   .v-tabs.vehicles-dashboard-tabs {
      .v-btn {
         min-width: 50px !important;
         .v-btn__content {
            font-size: 14px !important;
            color: #454545;
         }
      }
   }

   @media (max-width: 776px) {
      .v-tabs.v-tabs--horizontal:not(.v-tabs-pill) .v-btn {
         padding-right: 6px;
         padding-left: 6px;
      }
      .v-tabs.v-tabs--horizontal:not(.v-tabs-pill) .v-btn__content {
         gap: 4px !important;
      }
      .v-tabs.vehicles-dashboard-tabs {
         .v-icon {
            width: 20px!important;
            height: 20px!important;
         }
         .v-btn {
            .v-btn__content {
               white-space: break-spaces;
               font-size: 10px !important;
            }
         }
      }
   }

   .v-window {
      .v-window__container {
         height: 100%;
      }
   }

   .indicator-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      height: 100%;

      @media (max-width: 1023px) {
         grid-template-columns: 1fr;
         gap: 8px;
         margin-top: 40px;
         height: 217px;
      }
   }

   .indicator-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      border: 1px solid #F0F0F0;
      border-radius: 8px;
      gap: 8px;
      height: 100%;

      @media (max-width: 1023px) {
         flex-direction: row;
         justify-content: flex-start;
         padding: 8px;
         gap: 16px;
         height: 67px;
      }
   }

   .indicator-content-mobile {
      display: flex;
      flex-direction: column;
   }

   .indicator-info {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;

      @media (max-width: 1023px) {
         align-items: flex-start;
      }
   }

   .indicator-icon .v-icon {
      color: var(--icon-color) !important;
   }

   .indicator-value {
      font-weight: 600;
      font-size: 24px;
      line-height: 100%;
      letter-spacing: 0px;
      text-align: center;
      vertical-align: middle;
      color: #454545;
   }

   .indicator-suffix {
      margin-left: 4px;
      font-weight: 400;
      font-size: 16px;
      line-height: 100%;
      letter-spacing: 0px;
      text-align: center;
      vertical-align: middle;
      color: #454554;
   }

   .indicator-label {
      font-weight: 400;
      font-size: 10px;
      line-height: 16px;
      letter-spacing: 0px;
      text-align: center;
      vertical-align: middle;
      color: #454545;
   }

</style>