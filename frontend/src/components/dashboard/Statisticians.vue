<script setup>

import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { excelParser } from '@/plugins/csv/excelParser'
import { formatNumber } from '@/@core/utils/formatters'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import { themeConfig } from '@themeConfig'
import ExportDateMenu from '@/components/common/ExportDateMenu.vue'
import html2pdf from 'html2pdf.js'
import Dashboard from '@/api/dashboard'

  const emit = defineEmits(['filter', 'loading'])

  const props = defineProps({
    statisticians: {
      type: Object,
      default: () => ({}),
    },
    company: {
      type: Object,
      default: () => ({}),
    },
  })

  const defaultMonthlySeries = Array.from({ length: 12 }, () => ({
    month: null,
    month_label: null,
    total_purchase_price: 0,
    total_sale_price: 0,
    total_cost: 0,
    total_profit: 0,
  }))

  const defaultMonthCategories = ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec']

  const exporteraMobile = ref(false);
  const filterMenuVisible = ref(false)
  const filterDateRange = ref(null)

  // 👉 Export management refs
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

  const statisticiansData = computed(() => props.statisticians?.statisticians ?? props.statisticians ?? {})

  const formatSwedishAbbreviatedCurrency = value => {
    const numericValue = Number(value ?? 0)
    const absoluteValue = Math.abs(numericValue)

    let divisor = 1
    let suffix = ''

    if (absoluteValue >= 1000000000) {
      divisor = 1000000000
      suffix = 'Md'
    } else if (absoluteValue >= 1000000) {
      divisor = 1000000
      suffix = 'M'
    } else if (absoluteValue >= 1000) {
      divisor = 1000
      suffix = 'k'
    }

    const scaledValue = numericValue / divisor
    const roundedValue = Math.round(scaledValue * 10) / 10
    const hasFractionalComponent = Math.abs(roundedValue - Math.round(roundedValue)) > 0.00001
      || (Math.abs(scaledValue - roundedValue) > 0.00001 && Math.abs(roundedValue) < 10)

    return new Intl.NumberFormat('sv-SE', {
      minimumFractionDigits: hasFractionalComponent ? 1 : 0,
      maximumFractionDigits: hasFractionalComponent ? 1 : 0,
    }).format(roundedValue) + suffix
  }

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

  // 👉 Export functions
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

  const getExportMonthlyStats = async () => {
    const params = getExportDateRangePayload()

    if (!params.date_from || !params.date_to)
      return monthlyStats.value

    const response = await Dashboard.statisticians(params)
    const exportData = response?.data?.data?.priceByMonth

    return Array.isArray(exportData) && exportData.length ? exportData : []
  }

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
      const filteredData = await getExportMonthlyStats()

      const data = filteredData.map(item => ({
        Månad: item.month_label ?? item.month ?? '',
        Inköp: `${formatNumber(item.total_purchase_price ?? 0)} kr`,
        Försäljning: `${formatNumber(item.total_sale_price ?? 0)} kr`,
        'Extra kostnader': `${formatNumber(item.total_cost ?? 0)} kr`,
        Vinst: `${formatNumber(item.total_profit ?? 0)} kr`,
      }))

      excelParser().exportDataFromJSON(data, 'statisticians', 'csv')
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
      const rowsData = await getExportMonthlyStats()

      if (document.fonts?.load) {
        await Promise.all([
          document.fonts.load(`400 12px ${pdfFontFamily}`),
          document.fonts.load(`600 32px ${pdfFontFamily}`),
        ])
      }

      const rows = rowsData.map(item => ({
        month: item.month_label ?? item.month ?? '',
        purchase: formatNumber(item.total_purchase_price ?? 0) + ' kr',
        sale: formatNumber(item.total_sale_price ?? 0) + ' kr',
        cost: formatNumber(item.total_cost ?? 0) + ' kr',
        profit: formatNumber(item.total_profit ?? 0) + ' kr',
      }))

      const { headerMarkup } = await buildPdfTopHeader({
        company: props.company,
        title: 'Viktiga statistikuppgifter',
        themeConfig,
        escapeHtml,
        showCompanyDetailsWhenLogo: true,
      })

      const rowsMarkup = rows.map(item => `
        <tr style="height: 44px;">
          <td style="width: 20%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.month)}</td>
          <td style="width: 20%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.purchase)}</td>
          <td style="width: 20%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.sale)}</td>
          <td style="width: 20%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.cost)}</td>
          <td style="width: 20%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.profit)}</td>
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
                        <td style="text-align: center; width: 20%; padding: 0 8px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Månad</td>
                        <td style="text-align: center; width: 20%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Inköp</td>
                        <td style="text-align: center; width: 20%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Försäljning</td>
                        <td style="text-align: center; width: 20%; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Extra kostnader</td>
                        <td style="text-align: center; width: 20%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Vinst</td>
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
          filename: 'statisticians.pdf',
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
    if (isExportingFile.value)
      return

    if (!selectedExportType.value)
      return

    isExportingFile.value = true
    try {
      if (selectedExportType.value === 'excel') {
        await downloadCSV()
      } else {
        await downloadPDF()
      }

      isExportMenuVisible.value = false
    } finally {
      selectedExportType.value = null
      isExportingFile.value = false
    }
  }

  const onDatePickerUpdate = value => {
    if (!selectedExportType.value)
      return

    if (!isCompleteRangeSelection(value))
      return

    const selectionKey = buildRangeSelectionKey(value)
    if (!selectionKey || selectionKey === lastExportSelectionKey.value)
      return

    lastExportSelectionKey.value = selectionKey
    isExportMenuVisible.value = false

    if (!isExportingFile.value)
      exportPDFAndCloseMenu()
  }

  const monthlyStats = computed(() => {
    const priceByMonth = statisticiansData.value?.priceByMonth

    if (!Array.isArray(priceByMonth) || priceByMonth.length === 0)
      return defaultMonthlySeries

    return priceByMonth
  })

  const monthCategories = computed(() => {
    const categories = monthlyStats.value.map((item, index) => {
      if (item?.month_label)
        return item.month_label

      if (item?.month)
        return item.month

      return defaultMonthCategories[index] ?? `M${index + 1}`
    })

    return categories.length ? categories : defaultMonthCategories
  })

  const purchaseSeries = computed(() => monthlyStats.value.map(item => Number(item.total_purchase_price ?? 0)))
  const saleSeries = computed(() => monthlyStats.value.map(item => Number(item.total_sale_price ?? 0)))
  const costSeries = computed(() => monthlyStats.value.map(item => Number(item.total_cost ?? 0)))
  const profitSeries = computed(() => monthlyStats.value.map(item => Number(item.total_profit ?? 0)))
  const currentChartAmountField = computed(() => {
    return [
      'total_purchase_price',
      'total_sale_price',
      'total_cost',
      'total_profit',
    ][Number(currentTab.value)] ?? 'total_purchase_price'
  })

  const getCurrentMonthAbbreviatedValue = dataPointIndex => {
    const item = monthlyStats.value[dataPointIndex] ?? {}
    const field = currentChartAmountField.value
    const abbreviatedField = `${field}_abbreviated`

    return item?.[abbreviatedField] ?? formatSwedishAbbreviatedCurrency(item?.[field] ?? 0)
  }

  const currentMonthKey = computed(() => {
    const currentDate = new Date()
    const year = currentDate.getFullYear()
    const month = `${currentDate.getMonth() + 1}`.padStart(2, '0')

    return `${year}-${month}`
  })

  const chartRenderKey = computed(() => {
    return [
      currentTab.value,
      monthCategories.value.join('|'),
      purchaseSeries.value.length,
      saleSeries.value.length,
      costSeries.value.length,
      profitSeries.value.length,
    ].join('::')
  })

  const { width: windowWidth } = useWindowSize();

  const vuetifyTheme = useTheme()
  const currentTab = ref(0)
  const refVueApexChart = ref()

  const chartConfigs = computed(() => {
    const currentTheme = vuetifyTheme.current.value.colors
    const variableTheme = vuetifyTheme.current.value.variables
    const legendColor = `rgba(${ hexToRgb(currentTheme['on-background']) },${ variableTheme['high-emphasis-opacity'] })`
    const borderColor = `rgba(${ hexToRgb(String(variableTheme['border-color'])) },${ variableTheme['border-opacity'] })`
    const labelColor = `rgba(${ hexToRgb(currentTheme['on-surface']) },${ variableTheme['disabled-opacity'] })`
    const months = monthCategories.value
    const isMobile = windowWidth.value < 590
    const xAxisOffsetY = isMobile && months.length > 9 ? 16 : 0
    const xAxisFontSize = isMobile ? '9px' : '13px'

    const makeColors = (activeColor, inactiveColor) => monthlyStats.value.map(item => {
      const normalizedMonth = typeof item?.month === 'string' ? item.month.slice(0, 7) : null

      return normalizedMonth === currentMonthKey.value ? activeColor : inactiveColor
    })

    const createChartOptions = (colors) => ({
      chart: {
        parentHeightOffset: 0,
        type: 'bar',
        toolbar: { show: false },
      },
      plotOptions: {
        bar: {
          columnWidth: '50%',
          borderRadiusApplication: 'end',
          borderRadius: 4,
          distributed: true,
          dataLabels: { position: 'top' },
        },
      },
      grid: {
        show: false,
        padding: { top: 0, bottom: 0, left: 0, right: 0 },
      },
      colors,
      dataLabels: {
        enabled: true,
        formatter(_val, opts) {
          return `${getCurrentMonthAbbreviatedValue(opts?.dataPointIndex)} kr`
        },
        offsetY: -25,
        style: {
          fontSize: '15px',
          colors: [legendColor],
          fontWeight: '600',
          fontFamily: 'Public Sans',
        },
      },
      legend: { show: false },
      tooltip: { enabled: false },
      xaxis: {
        categories: months,
        axisBorder: { show: true, color: borderColor },
        axisTicks: { show: false },
        labels: {
          hideOverlappingLabels: false,
          offsetY: xAxisOffsetY,
          style: { colors: labelColor, fontSize: xAxisFontSize, fontFamily: 'Public Sans' },
        },
      },
      yaxis: {
        labels: {
          offsetX: -15,
          formatter(val) {
            return `${formatSwedishAbbreviatedCurrency(val)} kr`
          },
          style: { fontSize: '13px', colors: labelColor, fontFamily: 'Public Sans' },
          min: 0,
          max: 60000,
          tickAmount: 6,
        },
      },
      responsive: [
        {
          breakpoint: 1441,
          options: {
            plotOptions: {
              bar: { columnWidth: '41%', borderRadius: 4, borderRadiusApplication: 'end', distributed: true, dataLabels: { position: 'top' } },
            },
          },
        },
        {
          breakpoint: 590,
          options: {
            plotOptions: {
              bar: { columnWidth: '60%', borderRadius: 4, borderRadiusApplication: 'end', distributed: true, dataLabels: { position: 'top' } },
            },
            yaxis: { labels: { show: false } },
            dataLabels: {
              style: { fontSize: '12px', fontWeight: '400' },
            },
          },
        },
      ],
    })

    return [
      {
        title: 'Inköp',
        icon: 'custom-car-close',
        chartOptions: createChartOptions(makeColors('#6E9383', '#BDD2C8')),
        series: [{ data: purchaseSeries.value }],
        border: 'border-selected-inventary',
        bgColor: '#F5F8F6',
      },
      {
        title: 'Försäljning',
        icon: 'custom-car-open',
        chartOptions: createChartOptions(makeColors('#79FCA2', '#D8FFE4')),
        series: [{ data: saleSeries.value }],
        border: 'border-selected-sales',
        bgColor: '#D8FFE4',
      },
      {
        title: 'Extra kostnader',
        icon: 'custom-costs',
        chartOptions: createChartOptions(makeColors('#4DFFD1', '#C6FFEB')),
        series: [{ data: costSeries.value }],
        border: 'border-selected-costs',
        bgColor: '#C6FFEB',
      },
      {
        title: 'Vinst',
        icon: 'custom-profit',
        chartOptions: createChartOptions(makeColors('#3AF8FF', '#C0FEFF')),
        series: [{ data: profitSeries.value }],
        border: 'border-selected-profit',
        bgColor: '#C0FEFF',
      },
    ]
  })

</script>

<template>
  <VCard title="" class="card-dashboard">
    <VCardTitle class="title-box">
        <div class="title-text d-flex flex-column gap-2">
          Viktiga statistikuppgifter
          <span>Inköpta bilar</span>
        </div>

        <div class="d-flex gap-2" :class="windowWidth < 1024 ? 'flex-column w-100' : ''">
          <VMenu 
            v-if="windowWidth >= 1024">
            <template #activator="{ props }">
              <VBtn
                id="payout-export-button"
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
            id="payout-export-button"
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
            id="statistics-filter-button"
            class="btn-white-2 h-40"
            @click="filterMenuVisible = true"
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
            activator="#statistics-filter-button"
            button-text="Filtrera"
            button-icon="custom-filter"
            picker-label="Filtrera efter datum"
            picker-placeholder="Välj datum"
            @update:modelValue="onFilterDateUpdate"
          />
        </div>
    </VCardTitle>

    <VCardText class="pt-5 pb-3 px-4 px-md-6">
      <div class="stats-container mb-0">
        <div
          v-for="(report, index) in chartConfigs"
          :key="report.title"
          :class="currentTab === index ? report.border : ''"
          class="d-flex flex-column justify-center align-center cursor-pointer border-default stats-item"
          @click="currentTab = index"
        >
          <VAvatar
            rounded="lg"
            size="32"
            :style="{ backgroundColor: currentTab === index ? report.bgColor : '#F6F6F6' }"
          >
            <VIcon
              size="16"
              :icon="report.icon"
              color="#878787"
            />
          </VAvatar>
          <span class="text-statistics">
            {{ report.title }}
          </span>
        </div>
      </div>

      <VueApexCharts
        ref="refVueApexChart"
        :key="chartRenderKey"
        :options="chartConfigs[Number(currentTab)].chartOptions"
        :series="chartConfigs[Number(currentTab)].series"
        :height="windowWidth < 1024 ? 210 : 280"
      />
    </VCardText>
  </VCard>

  <!-- 👉 Export Mobile Dialog -->
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

  <!-- 👉 Export Date Menu -->
  <ExportDateMenu
    v-model="exportDateRange"
    v-model:menuVisible="isExportMenuVisible"
    :show-activator="false"
    :is-mobile="windowWidth < 1024"
    :reset-on-open="true"
    activator="#payout-export-button"
    button-text="Exportera"
    button-icon="custom-export"
    picker-label="Välj datumintervall för export"
    picker-placeholder="Välj datum"
    @update:modelValue="onDatePickerUpdate"
  />
</template>

<style lang="scss">

  .border-selected {
    width: 104px;
    height: 72px;
    gap: 8px;
    opacity: 1;
    border-radius: 8px;
    padding: 8px;
  }

  .border-selected-inventary {
    border: 1px solid #6E9383 !important;
  }

  .border-selected-sales {
    border: 1px solid #79FCA2 !important;
  }

  .border-selected-costs {
    border: 1px solid #4DFFD1 !important;
  }

  .border-selected-profit {
    border: 1px solid #3AF8FF !important; 
  }

  .border-default {
    width: 104px;
    height: 72px;
    gap: 8px;
    opacity: 1;
    border-radius: 8px;
    padding: 0;
    border: 1px solid #E7E7E7;
  }

  .text-statistics {
    font-weight: 400;
    font-size: 12px;
    line-height: 16px;
    letter-spacing: 0px;
    text-align: center;
    vertical-align: middle;
    color: #5d5d5d;
  }

  .stats-container {
    display: flex;
    gap: 16px;
  }

  @media (max-width: 767px) {
    .stats-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
    }

    .stats-item {
      width: 100% !important;
    }
  }

  //----------- graficas

  // Forzar borderRadius en barras (ApexCharts v5 lo desactiva en Safari/WebKit)
  .apexcharts-bar-area {
    clip-path: inset(0 0 0 0 round 4px 4px 0 0);
  }

  .apexcharts-text tspan {
    font-family: "DM Sans", Arial, sans-serif !important;
    font-weight: 400;
    font-size: 14px;
    line-height: 100%;
    letter-spacing: 0px;
    text-align: right;
    vertical-align: middle;
    color: #878787;

    @media (max-width: 767px) {
      font-size: 8px;
    }
  }

  .apexcharts-datalabel {
    font-family: "DM Sans", Arial, sans-serif !important;
    font-weight: 400;
    font-size: 14px;
    line-height: 100%;
    letter-spacing: 0px;
    text-align: center;
    vertical-align: middle;
    color: #454545;

    @media (max-width: 767px) {
      font-size: 10px;
    }
  }
</style>