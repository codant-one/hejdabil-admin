<script setup>

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
        barHeight: '100%',
        columnWidth: '30%',
        startingShape: 'rounded',
        endingShape: 'rounded',
        borderRadius: 4,
        colors: {
          backgroundBarColors: [
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
            'rgba(var(--v-track-bg))',
          ],
          backgroundBarRadius: 4,
        },
      },
    },
    colors: ['rgba(var(--v-theme-primary),1)'],
    grid: {
      show: false,
      padding: {
        top: -30,
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
              columnWidth: '30%',
              borderRadius: 4,
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
              columnWidth: '15%',
              borderRadius: 4,
            },
            colors: { backgroundBarRadius: 9 },
          },
        },
      },
      {
        breakpoint: 479,
        options: {
          plotOptions: {
            bar: { borderRadius: 4 },
            colors: { backgroundBarRadius: 9 },
          },
          grid: {
            padding: {
              right: -15,
              left: -15,
            },
          },
        },
      },
      {
        breakpoint: 400,
        options: { plotOptions: { bar: { borderRadius: 3 } } },
      },
    ],
  }
})

import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()
const currentTheme = vuetifyTheme.current.value.colors

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
  colors: [currentTheme.success],
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 0.9,
      opacityFrom: 0.5,
      opacityTo: 0.07,
      stops: [
        0,
        80,
        100,
      ],
    },
  },
  dataLabels: { enabled: false },
  stroke: {
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
   <div class="d-flex gap-4 justify-between" :class="windowWidth < 1024 ? '' : 'flex-column'">

      <VCard title="" class="card-dashboard">
         <VCardItem class="pb-3">
            <VCardTitle>Total vinst</VCardTitle>
            <VCardSubtitle>Last Week</VCardSubtitle>
         </VCardItem>

         <VCardText>
            <VueApexCharts
            :options="chartOptions"
            :series="series"
            :height="70"
            />

            <div class="d-flex align-center justify-space-between gap-x-2 mt-3">
            <h4 class="text-h4 text-center">
               124k
            </h4>
            <div class="text-sm text-success">
               +12.6%
            </div>
            </div>
         </VCardText>
      </VCard>

      <VCard title="" class="card-dashboard">
      <VCardItem class="pb-3">
      <VCardTitle>
        Sales
      </VCardTitle>
      <VCardSubtitle>
        Last Year
      </VCardSubtitle>
    </VCardItem>

    <VueApexCharts
      :options="chartOptions2"
      :series="series2"
      :height="76"
    />

    <VCardText class="pt-1">
      <div class="d-flex align-center justify-space-between gap-x-2">
        <h4 class="text-h4 text-center">
          175k
        </h4>
        <span class="text-sm text-error">
          -16.2%
        </span>
      </div>
    </VCardText>
   </VCard>
   </div>
</template>