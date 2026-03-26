<script setup>

import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()
const currentTheme = vuetifyTheme.current.value.colors
const { width: windowWidth } = useWindowSize();

const series = [{
  name: '2020',
  data: [
    60,
    50,
    20,
    45,
    50,
    30,
    70,
  ],
}]

const chartOptions = computed(() => {
  return {
    chart: {
      height: 90,
      parentHeightOffset: 0,
      type: 'bar',
      toolbar: { show: false },
    },
    tooltip: { enabled: false },
    plotOptions: {
      bar: {
        barHeight: '92%',
        columnWidth: '38%',
        borderRadius: 4,
        borderRadiusApplication: 'around',
        borderRadiusWhenStacked: 'all',
        colors: {
          backgroundBarColors: [
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
            '#C0FEFF',
          ],
          backgroundBarRadius: 4,
        },
      },
    },
    colors: ['#008C91'],
    grid: {
      show: false,
      padding: {
        top: -24,
        left: -16,
        bottom: 0,
        right: -6,
      },
    },
    dataLabels: { enabled: false },
    legend: { show: false },
    xaxis: {
      categories: [
        'M',
        'T',
        'W',
        'T',
        'F',
        'S',
        'S',
      ],
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: { show: false },
    },
    yaxis: { labels: { show: false } },
    responsive: [
      {
        breakpoint: 1441,
        options: {
          plotOptions: {
            bar: {
              columnWidth: '40%',
              borderRadius: 4,
              borderRadiusApplication: 'around',
            },
          },
        },
      },
      {
        breakpoint: 1368,
        options: { plotOptions: { bar: { columnWidth: '48%' } } },
      },
      {
        breakpoint: 1264,
        options: {
          plotOptions: {
            bar: {
              borderRadius: 6,
              borderRadiusApplication: 'around',
              columnWidth: '30%',
              colors: { backgroundBarRadius: 6 },
            },
          },
        },
      },
      {
        breakpoint: 960,
        options: {
          plotOptions: {
            bar: {
              columnWidth: '15%',
              borderRadius: 4,
              borderRadiusApplication: 'around',
            },
          },
        },
      },
      {
        breakpoint: 883,
        options: { plotOptions: { bar: { columnWidth: '20%' } } },
      },
      {
        breakpoint: 768,
        options: { plotOptions: { bar: { columnWidth: '25%' } } },
      },
      {
        breakpoint: 600,
        options: {
          plotOptions: {
            bar: {
              columnWidth: '22%',
              borderRadius: 4,
              borderRadiusApplication: 'around',
            },
            colors: { backgroundBarRadius: 9 },
          },
        },
      },
      {
        breakpoint: 479,
        options: {
          plotOptions: {
            bar: { borderRadius: 4, borderRadiusApplication: 'around', columnWidth: '36%' },
            colors: { backgroundBarRadius: 9 },
          },
          grid: {
            padding: {
              right: 0,
              left: -5,
              bottom: 12,
            },
          },
        },
      },
      {
        breakpoint: 400,
        options: {
          plotOptions: {
            bar: {
              borderRadius: 3,
              borderRadiusApplication: 'around',
              columnWidth: '42%',
            },
          },
          grid: {
            padding: {
              top: -18,
              left: -2,
              right: 0,
              bottom: 14,
            },
          },
        },
      },
    ],
  }
})

const series2 = [{
  name: 'Subscribers',
  data: [
    200,
    55,
    400,
    250,
  ],
}]

const chartOptions2 = {
  chart: {
    type: 'area',
    parentHeightOffset: 0,
    toolbar: { show: false },
    sparkline: { enabled: true },
  },
  markers: {
    colors: 'transparent',
    strokeColors: 'transparent',
  },
  grid: { show: false },
  colors: ['#009875'],
  fill: {
    type: 'gradient',
    colors: ['#C6FFEB'],
    gradient: {
      shadeIntensity: 0,
      type: 'vertical',
      gradientToColors: ['#FFFFFF'],
      opacityFrom: 1,
      opacityTo: 1,
      stops: [
        0,
        66,
      ],
    },
  },
  dataLabels: { enabled: false },
  stroke: {
    colors: ['#009875'],
    width: 2,
    curve: 'smooth',
  },
  xaxis: {
    show: true,
    lines: { show: false },
    labels: { show: false },
    stroke: { width: 0 },
    axisBorder: { show: false },
  },
  yaxis: {
    stroke: { width: 0 },
    show: false,
  },
  tooltip: { enabled: false },
}
</script>

<template>
  <div class="profit-cards">
    <VCard title="" class="card-dashboard profit-card">
      <VCardTitle 
          class="title-box pt-6 pb-3 border-none"
          :class="windowWidth < 1024 ? 'flex-row' : ''"
      >
        <div class="title-text d-flex flex-column gap-2">
            Total vinst
            <span>Senaste månaden</span>
        </div>
      </VCardTitle>

      <VCardText class="profit-card__content">
        <div class="profit-bar-chart">
          <VueApexCharts
            :options="chartOptions"
            :series="series"
            :height="windowWidth <= 400 ? 82 : 100"
          />
        </div>

        <div class="d-flex align-center">
          <span class="text-number-grafic">
            237
          </span>
          <span class="currency-number-grafic">
            SEK
          </span>
        </div>
      </VCardText>
    </VCard>

    <VCard title="" class="card-dashboard profit-card">
      <VCardTitle 
          class="title-box py-6 border-none"
          :class="windowWidth < 1024 ? 'flex-row' : ''"
      >
        <div class="title-text d-flex flex-column gap-2">
            Total försäljning
            <span>Senaste månaden</span>
        </div>
      </VCardTitle>

      <VCardText class="profit-card__content profit-card__content--compact px-0">
        <VueApexCharts
          :options="chartOptions2"
          :series="series2"
          :height="76"
        />

        <div class="d-flex align-center px-6">
          <span class="text-number-grafic">
            237
          </span>
          <span class="currency-number-grafic">
            SEK
          </span>
        </div>
      </VCardText>
   </VCard>
   </div>
</template>

<style lang="scss">
  .profit-cards {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: 1fr;
  }

  .profit-card {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-width: 0;
  }

  .profit-card__content {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: space-between;
  }

  .profit-card__content--compact {
    padding-top: 4px;
  }

  .profit-bar-chart {
    padding-bottom: 4px;
  }

  .profit-bar-chart .apexcharts-canvas,
  .profit-bar-chart .apexcharts-svg,
  .profit-bar-chart .apexcharts-inner {
    overflow: visible !important;
  }

  .profit-bar-chart .apexcharts-bar-area {
    clip-path: inset(0 round 4px) !important;
  }

  @media (min-width: 1024px) {
    .profit-cards {
      height: 100%;
      grid-template-columns: minmax(0, 1fr);
      grid-template-rows: repeat(2, minmax(0, 1fr));
    }
  }

  .text-number-grafic {
    font-weight: 600;
    font-size: 20px;
    line-height: 100%;
    letter-spacing: 0px;
    vertical-align: middle;
    color: #454545;
  }

  .currency-number-grafic {
    font-weight: 300;
    font-size: 20px;
    line-height: 100%;
    letter-spacing: 0px;
    vertical-align: middle;
    color: #454545;
  }
</style>