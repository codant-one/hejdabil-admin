<script setup>
import { onMounted, ref } from 'vue'
import router from '@/router'
import { useAlertStore } from '@/stores/useAlerts'
import InlineAlert from '@/components/common/InlineAlert.vue'

const alertsStore = useAlertStore()
const alerts = ref([])

const loadAlerts = async () => {
  const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
  await alertsStore.fetchAlerts({ user_id: userData?.id ?? null })
  alerts.value = Array.isArray(alertsStore.getAlerts) ? alertsStore.getAlerts : []
}

const goToBilling = route => {
  if (typeof route !== 'string' || !route.trim())
    return

  router.push(route).catch(() => {})
}

onMounted(async () => {
  await loadAlerts()
})

defineExpose({
  refresh: loadAlerts,
})
</script>

<template>
  <div v-if="alerts.length" class="alerts-list">
    <div
      v-for="(value, index) in alerts"
      :key="value.id ?? index"
      class="alerts-list__item"
      @click="goToBilling(value.route)"
    >
      <InlineAlert
        :variant="value.color"
        :title="value.title"
        :text="value.subtitle"
        :icon="value.icon"
      />
    </div>
  </div>
</template>

<style scoped lang="scss">
  .alerts-list__item {
    cursor: pointer;
    padding: 16px 16px 0 16px;
  }

  @media (max-width: 960px) {
    .alerts-list__item {
      padding: 24px 24px 0 24px;
    }
  }
</style>
