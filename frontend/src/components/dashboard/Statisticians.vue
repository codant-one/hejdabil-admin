<script setup>

import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import ExportDateMenu from '@/components/common/ExportDateMenu.vue'

  const emit = defineEmits(['filter'])

  const props = defineProps({
    statisticians: {
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

  const statisticiansData = computed(() => props.statisticians?.statisticians ?? props.statisticians ?? {})

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

    filterMenuVisible.value = false

    emit('filter', {
      date_from: range[0],
      date_to: range[1],
    })
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

    const makeColors = (seriesData, activeColor, inactiveColor) => {
      const maxValue = Math.max(...seriesData, 0)
      const highlightedIndex = Math.max(seriesData.findIndex(item => item === maxValue), 0)

      return months.map((_, i) => (i === highlightedIndex ? activeColor : inactiveColor))
    }

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
        formatter(val) {
          return `${ val }kr`
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
            return `${ val / 1 }kr`
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
        chartOptions: createChartOptions(makeColors(purchaseSeries.value, '#6E9383', '#BDD2C8')),
        series: [{ data: purchaseSeries.value }],
        border: 'border-selected-inventary',
        bgColor: '#F5F8F6',
      },
      {
        title: 'Försäljning',
        icon: 'custom-car-open',
        chartOptions: createChartOptions(makeColors(saleSeries.value, '#79FCA2', '#D8FFE4')),
        series: [{ data: saleSeries.value }],
        border: 'border-selected-sales',
        bgColor: '#D8FFE4',
      },
      {
        title: 'Extra kostnader',
        icon: 'custom-costs',
        chartOptions: createChartOptions(makeColors(costSeries.value, '#4DFFD1', '#C6FFEB')),
        series: [{ data: costSeries.value }],
        border: 'border-selected-costs',
        bgColor: '#C6FFEB',
      },
      {
        title: 'Vinst',
        icon: 'custom-profit',
        chartOptions: createChartOptions(makeColors(profitSeries.value, '#3AF8FF', '#C0FEFF')),
        series: [{ data: profitSeries.value }],
        border: 'border-selected-profit',
        bgColor: '#C0FEFF',
      },
    ]
  })

</script>

<template>
  <VCard title="" class="card-dashboard">
    <VCardTitle 
        class="title-box"
        :class="windowWidth < 1024 ? 'flex-row align-center' : ''"
    >
        <div class="title-text d-flex flex-column gap-2">
          Viktiga statistikuppgifter
          <span>Inköpta bilar</span>
        </div>

        <div class="d-flex gap-2">
          <VMenu 
            v-if="windowWidth >= 1024">
            <template #activator="{ props }">
              <VBtn
                id="payout-export-button"
                class="btn-light h-40 w-auto"
                v-bind="props"
              >
                <VIcon icon="custom-export" size="24" />
                Exportera
              </VBtn>
            </template>

            <VList>
              <VListItem>
                <VListItemTitle>Exportera PDF</VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>Exportera Excel</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>

          <VBtn
            v-else
            id="payout-export-button"
            class="btn-ghost px-2 h-24 w-auto"
            v-bind="props"
            block
            @click="exporteraMobile = true"
          >
            <VIcon icon="custom-export" size="24" color="#454545"/>
          </VBtn>

          <VBtn
              class="h-40"
              id="statistics-filter-button"
              :class="windowWidth < 1024 ? 'btn-ghost px-0 h-24' : 'btn-white-2 px-3 h-40'"
              @click="filterMenuVisible = true"
          >
              <VIcon icon="custom-filter" size="24" color="#454545"/>
              <span class="d-none d-md-block">Filtrera efter datum</span>
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
        <VListItem>
          <VListItemTitle>Exportera PDF</VListItemTitle>
        </VListItem>

        <VListItem>
          <VListItemTitle>Exportera Excel</VListItemTitle>
        </VListItem>
      </VList>
    </VCard>
  </VDialog>
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