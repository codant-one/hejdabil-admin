<script setup>

import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'

  const { width: windowWidth } = useWindowSize();

  const vuetifyTheme = useTheme()
  const currentTab = ref(0)
  const refVueApexChart = ref()

  const chartConfigs = computed(() => {
    const currentTheme = vuetifyTheme.current.value.colors
    const variableTheme = vuetifyTheme.current.value.variables
    const labelPrimaryColor = `rgba(${ hexToRgb(currentTheme.primary) },${ variableTheme['dragged-opacity'] })`
    const legendColor = `rgba(${ hexToRgb(currentTheme['on-background']) },${ variableTheme['high-emphasis-opacity'] })`
    const borderColor = `rgba(${ hexToRgb(String(variableTheme['border-color'])) },${ variableTheme['border-opacity'] })`
    const labelColor = `rgba(${ hexToRgb(currentTheme['on-surface']) },${ variableTheme['disabled-opacity'] })`
    const primaryColor = `rgba(${ hexToRgb(currentTheme.primary) }, 1)`

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec']

    const makeColors = (activeIndex, activeColor, inactiveColor) =>
      months.map((_, i) => (i === activeIndex ? activeColor : inactiveColor))

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
        padding: { top: 0, bottom: 0, left: -10, right: -10 },
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
          style: { colors: labelColor, fontSize: '13px', fontFamily: 'Public Sans' },
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
              bar: { columnWidth: '50%', borderRadius: 4, borderRadiusApplication: 'end', distributed: true, dataLabels: { position: 'top' } },
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
        chartOptions: createChartOptions(makeColors(2, '#6E9383', '#BDD2C8')),
        series: [{ data: [28, 10, 45, 38, 15, 30, 35, 30, 8, 20, 25, 18] }],
        border: 'border-selected-inventary',
        bgColor: '#F5F8F6',
      },
      {
        title: 'Försäljning',
        icon: 'custom-car-open',
        chartOptions: createChartOptions(makeColors(6, '#79FCA2', '#D8FFE4')),
        series: [{ data: [35, 25, 15, 40, 42, 25, 48, 8, 30, 22, 38, 15] }],
        border: 'border-selected-sales',
        bgColor: '#D8FFE4',
      },
      {
        title: 'Extra kostnader',
        icon: 'custom-costs',
        chartOptions: createChartOptions(makeColors(4, '#4DFFD1', '#C6FFEB')),
        series: [{ data: [10, 22, 27, 33, 42, 32, 27, 22, 8, 15, 20, 12] }],
        border: 'border-selected-costs',
        bgColor: '#C6FFEB',
      },
      {
        title: 'Vinst',
        icon: 'custom-profit',
        chartOptions: createChartOptions(makeColors(11, '#3AF8FF', '#C0FEFF')),
        series: [{ data: [5, 9, 12, 18, 20, 25, 30, 36, 48, 40, 33, 28] }],
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
        :class="windowWidth < 1024 ? 'flex-row' : ''"
    >
        <div class="title-text d-flex flex-column gap-2">
          Viktiga statistikuppgifter
          <span>Inköpta bilar</span>
        </div>

        <div class="d-flex gap-2">
          <VBtn
              :class="windowWidth < 1024 ? 'btn-ghost' : 'btn-light'"
              class="w-auto h-40"
          >
              <VIcon icon="custom-export" size="24" />               
              <span class="d-none d-md-block">Exportera</span>
          </VBtn>

          <VBtn
              class="btn-white-2 px-3 h-40"
          >
              <VIcon icon="custom-filter" size="24" />
              <span class="d-none d-md-block">Filtrera efter datum</span>
          </VBtn>
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
        :key="currentTab"
        :options="chartConfigs[Number(currentTab)].chartOptions"
        :series="chartConfigs[Number(currentTab)].series"
        height="280"
      />
    </VCardText>
  </VCard>
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
      font-size: 10px;
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