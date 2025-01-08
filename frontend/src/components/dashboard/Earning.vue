<script setup>

import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'

const vuetifyTheme = useTheme()

const series = [{
  data: [
    40,
    95,
    60,
    45,
    90,
    50,
    75,
  ],
}]

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables
  const labelColor = `rgba(${ hexToRgb(currentTheme['on-background']) },${ variableTheme['disabled-opacity'] })`
  const labelPrimaryColor = `rgba(${ hexToRgb(currentTheme.primary) },0.1)`
  
  return {
    chart: {
      type: 'bar',
      toolbar: { show: false },
    },
    tooltip: { enabled: false },
    plotOptions: {
      bar: {
        barHeight: '60%',
        columnWidth: '60%',
        startingShape: 'rounded',
        endingShape: 'rounded',
        borderRadius: 4,
        distributed: true,
      },
    },
    grid: {
      show: false,
      padding: {
        top: -20,
        bottom: 0,
        left: -10,
        right: -10,
      },
    },
    colors: [
      labelPrimaryColor,
      labelPrimaryColor,
      labelPrimaryColor,
      labelPrimaryColor,
      `rgba(${ hexToRgb(currentTheme.primary) }, 1)`,
      labelPrimaryColor,
      labelPrimaryColor,
    ],
    dataLabels: { enabled: false },
    legend: { show: false },
    xaxis: {
      categories: [
        'Lu',
        'Ma',
        'Mi',
        'Ju',
        'Vi',
        'Sa',
        'Do',
      ],
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        style: {
          colors: labelColor,
          fontSize: '13px',
        },
      },
    },
    yaxis: { labels: { show: false } },
  }
})

const earningReports = [
  {
    avatarIcon: 'tabler-chart-pie-2',
    avatarColor: 'primary',
    title: 'Beneficio neto',
    subtitle: 'COP 12.40',
    earnings: '$1,619',
    percentage: '18.6%',
  },
  {
    avatarIcon: 'tabler-currency-dollar',
    avatarColor: 'success',
    title: 'Ingresos totales',
    subtitle: 'Ventas, Afiliación',
    earnings: '$3,571',
    percentage: '39.6%',
  },
  {
    avatarIcon: 'tabler-credit-card',
    avatarColor: 'secondary',
    title: 'Gastos totales',
    subtitle: 'ADVT, Marketing',
    earnings: '$430',
    percentage: '52.8%',
  },
]
</script>

<template>
  <VCard
    title="Informes de ganancias"
    subtitle="Resumen de ganancias semanales"
  >

    <VCardText class="pb-0">
      <VList class="card-list mb-3">
        <VListItem
          v-for="report in earningReports"
          :key="report.title"
        >
          <template #prepend>
            <VAvatar
              rounded
              size="34"
              variant="tonal"
              :color="report.avatarColor"
            >
              <VIcon :icon="report.avatarIcon" />
            </VAvatar>
          </template>

          <VListItemTitle class="font-weight-medium">
            {{ report.title }}
          </VListItemTitle>
          <VListItemSubtitle class="text-disabled">
            {{ report.subtitle }}
          </VListItemSubtitle>

          <template #append>
            <div class="d-flex align-center text-body-2">
              <span class="me-3">{{ report.earnings }}</span>
              <VIcon
                color="success"
                icon="tabler-chevron-up"
                size="18"
                class="me-1"
              />
              <span class="text-disabled">{{ report.percentage }}</span>
            </div>
          </template>
        </VListItem>
      </VList>

      <VueApexCharts
        :options="chartOptions"
        :series="series"
        :height="220"
      />
    </VCardText>
  </VCard>
</template>
