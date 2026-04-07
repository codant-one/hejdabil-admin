<script setup>

import { useDisplay } from "vuetify";
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

const { width: windowWidth } = useWindowSize();

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const statisticians = ref(null)
const indicators = ref({})
const profit = ref({})
const measures = ref({})
const team = ref({})
const vehicles = ref({})
const reminders = ref({})
const activities = ref({})
const notifications = ref({})
const indicatorFilters = ref({})
const statisticiansFilters = ref({})
const teamFilters = ref({})
const vehicleFilters = ref({
  sort_by: 'latest_added',
})
const userDataJ = ref('')
const name = ref('')
const role = ref('')

const advisor = ref({
  type: '',
  message: '',
  show: false
})

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
    await loadReminders()
    await loadMeasures()
    await loadVehicles(vehicleFilters.value)
    await loadTeam()
    await loadActivities()
    await loadNotifications()
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

async function loadMeasures() {
  await dashboardStore.fetchMeasures()
  measures.value = dashboardStore.getMeasures
}

async function loadReminders() {
  await dashboardStore.fetchReminders()
  reminders.value = dashboardStore.getReminders
}

async function handleRemindersRefresh() {
  isRequestOngoing.value = true

  try {
    await loadReminders()
  } finally {
    isRequestOngoing.value = false
  }
}

function handleAdvisor(data) {
  advisor.value.type = data.type
  advisor.value.message = data.message
  advisor.value.show = true

  setTimeout(() => {
    advisor.value.show = false
    advisor.value.type = ''
    advisor.value.message = ''
  }, 3000)
}

async function loadVehicles(params = {}) {
  await dashboardStore.fetchVehicles(params)
  vehicles.value = dashboardStore.getVehicles
}

async function handleVehiclesFilter(filters) {
  vehicleFilters.value = {
    ...vehicleFilters.value,
    ...(filters?.sort_by ? { sort_by: filters.sort_by } : {}),
  }

  isRequestOngoing.value = true

  try {
    await loadVehicles(vehicleFilters.value)
  } finally {
    isRequestOngoing.value = false
  }
}

async function loadTeam(params = {}) {
  await dashboardStore.fetchTeam(params)
  team.value = dashboardStore.getTeam
}

async function loadActivities() {
  await dashboardStore.fetchActivities()
  activities.value = dashboardStore.getActivities
}

async function loadNotifications() {
  await dashboardStore.fetchNotifications()
  notifications.value = dashboardStore.getNotifications
}

async function handleTeamFilter(filters) {
  teamFilters.value = {
    ...(filters?.date_from ? { date_from: filters.date_from } : {}),
    ...(filters?.date_to ? { date_to: filters.date_to } : {}),
  }

  isRequestOngoing.value = true

  try {
    await loadTeam(teamFilters.value)
  } finally {
    isRequestOngoing.value = false
  }
}

async function handleMeasuresRefresh() {
  isRequestOngoing.value = true

  try {
    await loadMeasures()
  } finally {
    isRequestOngoing.value = false
  }
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

    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
    >
      {{ advisor.message }}
    </VSnackbar>

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
    <template  v-else>
      <span class="title-dashboard" v-if="windowWidth < 1024">Översikt</span>
    
      <VCard title="" class="card-transparent">
        
        <div class="dashboard-grid">
          <div class="dashboard-grid__item dashboard-grid__item--md-6 h-card">
            <Activities 
              :activities="activities?.activities" 
              :notifications="notifications?.notifications"
            />
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

          <div class="dashboard-grid__item dashboard-grid__item--md-6 h-card" style="height: 480px !important;">
            <Information
              :reminders="reminders?.reminders"
              @refresh="handleRemindersRefresh"
              @advisor="handleAdvisor"
            />
          </div>
          <div class="dashboard-grid__item dashboard-grid__item--md-6 h-card" style="height: 480px !important;">
            <Measures
              :measures="measures"
              @loading="handleStatisticiansLoading"
              @refresh="handleMeasuresRefresh"
            />
          </div>

          <div class="dashboard-grid__item dashboard-grid__item--md-12">
            <VehicleInfo
              :stock-vehicles="vehicles?.stockVehicles"
              :sold-vehicles="vehicles?.soldVehicles"
              @loading="handleStatisticiansLoading"
              @filter="handleVehiclesFilter"
            />
          </div>

          <div class="dashboard-grid__item dashboard-grid__item--md-12" v-if="$can('view','team-reports')">
            <Team
              :team-members="team?.teamMembers"
              :team-totals="team?.teamTotals"
              @loading="handleStatisticiansLoading"
              @filter="handleTeamFilter"
            />
          </div>
        </div>
      </VCard>
    </template>
  </section>
</template>

<style lang="scss" scoped>
  .title-dashboard {
    display: flex;
    gap: 24px;
    padding: 24px;
    border-bottom: 1px solid #E7E7E7;
    margin-bottom: 24px;

    font-family: "Blauer Nue";
    font-weight: 700;
    font-size: 24px;
    line-height: 100%;
    letter-spacing: 0;
    color: #1C2925
  }

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
