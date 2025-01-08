<script setup>

const currentActiveTab = ref('Nuevas')

const orders = [
  {
    tabName: 'Nuevas',
    timeline1: [
      {
        icon: 'tabler-circle-check',
        type: 'REMITENTE',
        name: 'Myrtle Ullrich',
        address: '101 Boulder, California(CA), 95959',
      },
      {
        icon: 'tabler-map-pin',
        type: 'RECEPTOR',
        name: 'Barry Schowalter',
        address: '939 Orange, California(CA),92118',
      },
    ],
    timeline2: [
      {
        icon: 'tabler-circle-check',
        type: 'REMITENTE',
        name: 'Veronica Herman',
        address: '162 Windsor, California(CA), 95492',
      },
      {
        icon: 'tabler-map-pin',
        type: 'RECEPTOR',
        name: 'Helen Jacobs',
        address: '487 Sunset, California(CA), 94043',
      },
    ],
  },
  {
    tabName: 'Preparando',
    timeline1: [
      {
        icon: 'tabler-circle-check',
        type: 'REMITENTE',
        name: 'Barry Schowalter',
        address: '939 Orange, California(CA),92118',
      },
      {
        icon: 'tabler-map-pin',
        type: 'RECEPTOR',
        name: 'Myrtle Ullrich',
        address: '101 Boulder, California(CA), 95959',
      },
    ],
    timeline2: [
      {
        icon: 'tabler-circle-check',
        type: 'REMITENTE',
        name: 'Veronica Herman',
        address: '162 Windsor, California(CA), 95492',
      },
      {
        icon: 'tabler-map-pin',
        type: 'RECEPTOR',
        name: 'Helen Jacobs',
        address: '487 Sunset, California(CA), 94043',
      },
    ],
  },
  {
    tabName: 'Envío',
    timeline1: [
      {
        icon: 'tabler-circle-check',
        type: 'REMITENTE',
        name: 'Veronica Herman',
        address: '101 Boulder, California(CA), 95959',
      },
      {
        icon: 'tabler-map-pin',
        type: 'RECEPTOR',
        name: 'Barry Schowalter',
        address: '939 Orange, California(CA),92118',
      },
    ],
    timeline2: [
      {
        icon: 'tabler-circle-check',
        type: 'REMITENTE',
        name: 'Myrtle Ullrich',
        address: '162 Windsor, California(CA), 95492',
      },
      {
        icon: 'tabler-map-pin',
        type: 'RECEPTOR',
        name: 'Helen Jacobs',
        address: '487 Sunset, California(CA), 94043',
      },
    ],
  },
]
</script>

<template>
  <VCard>
    <VCardItem class="pb-0">
      <VCardTitle>Ventas por pedidos</VCardTitle>
      <VCardSubtitle>62 entregas en curso</VCardSubtitle>
    </VCardItem>

    <VTabs
      v-model="currentActiveTab"
      grow
    >
      <VTab
        v-for="order in orders"
        :key="order.tabName"
      >
        {{ order.tabName }}
      </VTab>
    </VTabs>

    <VCardText>
      <VWindow
        v-model="currentActiveTab"
        class="disable-tab-transition"
      >
        <VWindowItem
          v-for="order in orders"
        >
          <VTimeline
            side="end"
            align="start"
            truncate-line="both"
            density="compact"
            class="v-timeline-density-compact v-timeline-icon-only"
          >
            <VTimelineItem
              v-for="item in order.timeline1"
              :key="item.icon"
              fill-dot
              size="small"
            >
              <template #icon>
                <VIcon
                  size="20"
                  :icon="item.icon"
                  :color="item.type === 'REMITENTE' ? 'success' : 'primary'"
                />
              </template>

              <p :class="`text-sm font-weight-medium text-${item.type === 'REMITENTE' ? 'success' : 'primary'} mb-0`">
                {{ item.type }}
              </p>
              <p class="app-timeline-title mb-0">
                {{ item.name }}
              </p>
              <p class="text-disabled mb-0">
                {{ item.address }}
              </p>
            </VTimelineItem>
          </VTimeline>

          <VDivider
            class="my-5"
            style="border-style: dashed;"
          />

          <VTimeline
            side="end"
            align="start"
            truncate-line="both"
            density="compact"
            class="v-timeline-density-compact v-timeline-icon-only"
          >
            <VTimelineItem
              v-for="item in order.timeline2"
              :key="item.icon"
              fill-dot
              size="small"
            >
              <template #icon>
                <VIcon
                  size="20"
                  :icon="item.icon"
                  :color="item.type === 'REMITENTE' ? 'success' : 'primary'"
                />
              </template>
              <p :class="`text-sm font-weight-medium text-${item.type === 'REMITENTE' ? 'success' : 'primary'} mb-0`">
                {{ item.type }}
              </p>

              <p class="app-timeline-title mb-0">
                {{ item.name }}
              </p>
              <p class="text-disabled mb-0">
                {{ item.address }}
              </p>
            </VTimelineItem>
          </VTimeline>
        </VWindowItem>
      </VWindow>
    </VCardText>
  </VCard>
</template>
