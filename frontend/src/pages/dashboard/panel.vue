<script setup>

import { themeConfig } from '@themeConfig'
import { useDashboardStores } from '@/stores/useDashboard'
import { useAuthStores } from '@/stores/useAuth';
import { useConfigsStores } from '@/stores/useConfigs';
import { useAppAbility } from '@/plugins/casl/useAppAbility';
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import Activities from "@/components/dashboard/Activities.vue";
import Indicators from "@/components/dashboard/KeyIndicators.vue";
import Statisticians from "@/components/dashboard/Statisticians.vue";
import Profit from "@/components/dashboard/Profit.vue";
import Information from "@/components/dashboard/Information.vue";
import Measures from "@/components/dashboard/Measures.vue";
import VehicleInfo from "@/components/dashboard/VehicleInfo.vue";
import Team from "@/components/dashboard/Team.vue";

const dashboardStore = useDashboardStores()
const authStores = useAuthStores();
const configsStores = useConfigsStores();
const ability = useAppAbility()

const statisticians = ref(null)
const indicators = ref({})
const profit = ref({})
const indicatorFilters = ref({})
const statisticiansFilters = ref({})
const userDataJ = ref('')
const name = ref('')
const role = ref('')

const isRequestOngoing = ref(false)
const sectionEl = ref(null)
const environment = ref(null)

const COMPANY_STORAGE_KEY = 'clients_company_snapshot';

const readCachedCompany = () => {
  try {
    const cached = localStorage.getItem(COMPANY_STORAGE_KEY);
    if (!cached) return {};

    const parsed = JSON.parse(cached);
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch {
    return {};
  }
};

const company = ref(readCachedCompany())

const setCompany = (value) => {
  const normalized = value && typeof value === 'object' ? { ...value } : {};
  company.value = normalized;
  localStorage.setItem(COMPANY_STORAGE_KEY, JSON.stringify(normalized));
};

onMounted(async () => {
  try {
  
    const userData = localStorage.getItem('user_data') || 'null'
    
    userDataJ.value = JSON.parse(userData)
    name.value = userDataJ.value?.name + " " + userDataJ.value?.last_name

    role.value = userDataJ.value.roles[0].name

    if (!role.value) return;

    const { user_data, userAbilities } = await authStores.me(userDataJ.value)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

    ability.update(userAbilities)

    localStorage.setItem('user_data', JSON.stringify(user_data))

    if (role.value === 'Supplier') {
      setCompany({
        ...(user_data?.user_detail ?? {}),
        email: user_data?.email ?? '',
        name: user_data?.name ?? '',
        last_name: user_data?.last_name ?? '',
      });
    } else if (role.value === 'User') {
      setCompany({
        ...(user_data?.supplier?.boss?.user?.user_detail ?? {}),
        email: user_data?.supplier?.boss?.user?.email ?? '',
        name: user_data?.supplier?.boss?.user?.name ?? '',
        last_name: user_data?.supplier?.boss?.user?.last_name ?? '',
      });
    } else {
      await configsStores.getFeature('company')
      await configsStores.getFeature('logo')

      const companyConfig = configsStores.getFeaturedConfig('company') ?? {};
      const logoConfig = configsStores.getFeaturedConfig('logo') ?? {};

      setCompany({
        ...companyConfig,
        logo: logoConfig.logo ?? companyConfig.logo ?? '',
      });
    }

  } catch (error) {
    console.error('Failed to load company data:', error);
  }
});

onMounted(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  const userData = localStorage.getItem('user_data') || 'null'
    
  userDataJ.value = JSON.parse(userData)
  name.value = userDataJ.value?.name + " " + userDataJ.value?.last_name

  role.value = userDataJ.value.roles[0].name
  environment.value = themeConfig.settings.enviroment

  if (role.value === 'Supplier' || role.value === 'User') {
    await loadProfit()
    await loadIndicators(indicatorFilters.value)
    await loadStatisticians(statisticiansFilters.value)
  }

  isRequestOngoing.value = false
}

async function loadStatisticians(params = {}) {
  await dashboardStore.fetchStatisticians(params)
  statisticians.value = dashboardStore.getStatisticians
}

async function loadIndicators(params = {}) {
  await dashboardStore.fetchIndicators(params)
  indicators.value = dashboardStore.getIndicators
}

async function loadProfit() {
  await dashboardStore.fetchProfit()
  profit.value = dashboardStore.getProfit
}

async function handleIndicatorsFilter(filters) {
  indicatorFilters.value = {
    ...(filters?.date_from ? { date_from: filters.date_from } : {}),
    ...(filters?.date_to ? { date_to: filters.date_to } : {}),
  }

  isRequestOngoing.value = true

  try {
    await loadIndicators(indicatorFilters.value)
  } finally {
    isRequestOngoing.value = false
  }
}

async function handleStatisticiansFilter(filters) {
  statisticiansFilters.value = {
    ...(filters?.date_from ? { date_from: filters.date_from } : {}),
    ...(filters?.date_to ? { date_to: filters.date_to } : {}),
  }

  isRequestOngoing.value = true

  try {
    await loadStatisticians(statisticiansFilters.value)
  } finally {
    isRequestOngoing.value = false
  }
}

function handleStatisticiansLoading(value) {
  isRequestOngoing.value = value
}

function handleIndicatorsLoading(value) {
  isRequestOngoing.value = value
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
          <Indicators
            :company="company"
            :indicators="indicators"
            @loading="handleIndicatorsLoading"
            @filter="handleIndicatorsFilter"
          />
        </div>

        <div class="dashboard-grid__item dashboard-grid__item--md-10">
          <Statisticians 
            :company="company"
            :statisticians="statisticians" 
            @loading="handleStatisticiansLoading"
            @filter="handleStatisticiansFilter" 
          />
        </div>
        <div class="dashboard-grid__item dashboard-grid__item--md-2">
          <Profit :profit="profit" />
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
