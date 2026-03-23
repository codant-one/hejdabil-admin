<script setup>

import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import Activities from "@/components/dashboard/Activities.vue";
import Indicators from "@/components/dashboard/KeyIndicators.vue";
import Statisticians from "@/components/dashboard/Statisticians.vue";
import Profit from "@/components/dashboard/Profit.vue";
import Information from "@/components/dashboard/Information.vue";
import Measures from "@/components/dashboard/Measures.vue";
import VehicleInfo from "@/components/dashboard/VehicleInfo.vue";
import Team from "@/components/dashboard/Team.vue";
import { themeConfig } from '@themeConfig'

const userDataJ = ref('')
const name = ref('')
const role = ref('')

const isRequestOngoing = ref(false)
const sectionEl = ref(null)
const environment = ref(null)

watchEffect(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  const userData = localStorage.getItem('user_data') || 'null'
    
  userDataJ.value = JSON.parse(userData)
  name.value = userDataJ.value?.name + " " + userDataJ.value?.last_name

  role.value = userDataJ.value.roles[0].name
  environment.value = themeConfig.settings.enviroment

  isRequestOngoing.value = false
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value
  if (!el) return

  const rect = el.getBoundingClientRect()
  const remaining = Math.max(0, window.innerHeight - rect.top - 25)
  el.style.minHeight = `${remaining}px`
}

onMounted(() => {
  resizeSectionToRemainingViewport()
  window.addEventListener('resize', resizeSectionToRemainingViewport)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
})

</script>

<template>
  <section class="page-section" ref="sectionEl">
    <LoadingOverlay :is-loading="isRequestOngoing" />

    <!-- 👉 Administrador -->
    <VCard title="" class="card-fill" v-if="(role !== 'Supplier' && role !== 'User') || environment === 'production'">
      <VRow class="py-6 px-md-6 px-2">
        <VCol
          cols="12"
          md="8"

        >
          <div class="pe-3">
            <h3 class="text-h3 text-high-emphasis mb-1">
              Välkommen tillbaka, <span class="font-weight-medium"> {{ name }} 👋🏻 </span>
            </h3>

            <div
              class="mb-7 text-wrap"
              style="max-inline-size: 800px;"
            >
              I den här panelen hittar du relevant information om bilförsäljning.
            </div>

          </div>
        </VCol>
      </VRow>
    </VCard>

    <!-- 👉 Supplier / User -->
    <VCard title="" class="card-transparent" v-else>
      <div class="dashboard-grid">
        <div class="dashboard-grid__item dashboard-grid__item--md-6 h-card">
          <Activities />
        </div>
        <div class="dashboard-grid__item dashboard-grid__item--md-6">
          <Indicators />
        </div>

        <div class="dashboard-grid__item dashboard-grid__item--md-10">
          <Statisticians />
        </div>
        <div class="dashboard-grid__item dashboard-grid__item--md-2">
          <Profit />
        </div>

        <div class="dashboard-grid__item dashboard-grid__item--md-6 h-card">
          <Information />
        </div>
        <div class="dashboard-grid__item dashboard-grid__item--md-6 h-card">
          <Measures />
        </div>

        <div class="dashboard-grid__item dashboard-grid__item--md-12">
          <VehicleInfo />
        </div>

        <div class="dashboard-grid__item dashboard-grid__item--md-12">
          <Team />
        </div>
      </div>
    </VCard>
  </section>
</template>

<style lang="scss" scoped>
  .dashboard-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(0, 1fr);
  }

  .dashboard-grid__item {
    min-width: 0;
    min-height: 0;
  }

  @media (min-width: 960px) {
    .dashboard-grid {
      grid-template-columns: repeat(12, minmax(0, 1fr));
    }

    .dashboard-grid__item--md-2 {
      grid-column: span 2;
    }

    .dashboard-grid__item--md-6 {
      grid-column: span 6;
    }

    .dashboard-grid__item--md-10 {
      grid-column: span 10;
    }

    .dashboard-grid__item--md-12 {
      grid-column: span 12;
    }
  }

  .card-list {
    --v-card-list-gap: 30px;
  }
</style>

<style lang="scss">
  .h-card {
    display: flex;
    height: 392px !important;
    min-height: 0;
    overflow: hidden;
  }

  .h-card > * {
    flex: 1 1 auto;
    height: 100%;
    min-height: 0;
  }

  .h-card > .card-dashboard {
    overflow-y: auto !important;
    overflow-x: hidden;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
  }

  .h-card > .card-dashboard::-webkit-scrollbar {
    display: none;
  }

  .h-card > .card-dashboard > .v-card-title,
  .h-card > .card-dashboard > .v-card-text,
  .h-card > .card-dashboard > .v-card-item,
  .h-card > .card-dashboard > .v-window {
    flex-shrink: 0;
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
