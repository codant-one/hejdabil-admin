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
    <VCard title="" class="card-transparent pt-0" v-else>
      <VRow nclass="py-6 px-md-6 px-2">

        <VCol cols="12" md="6" class="h-card">
          <Activities />
        </VCol>
        <VCol cols="12" md="6">
          <Indicators />
        </VCol>

        <VCol cols="12" md="10">
          <Statisticians />
        </VCol>
        <VCol cols="12" md="2">
          <Profit />
        </VCol>

        <VCol cols="12" md="6">
          <Information />
        </VCol>
        <VCol cols="12" md="6" >
          <Measures />
        </VCol>

        <VCol cols="12" md="12" >
          <VehicleInfo />
        </VCol>

        <VCol cols="12" md="12">
          <Team />
        </VCol>
      </VRow>
    </VCard>
  </section>
</template>

<style lang="scss" scoped>
  .card-list {
    --v-card-list-gap: 30px;
  }
</style>

<style lang="scss">
  .h-card {
    height: 392px !important;
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
