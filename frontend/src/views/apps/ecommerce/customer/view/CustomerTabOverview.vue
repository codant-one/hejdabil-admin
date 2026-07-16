<script setup>

import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";
import ExportDateMenu from '@/components/common/ExportDateMenu.vue'
import Suppliers from '@/api/suppliers'
import { themeConfig } from '@themeConfig'

const props = defineProps({
  customerData: {
    type: Object,
    required: true,
  },
  isSupplier: {
    type: Boolean,
    required: true
  }
})

const { width: windowWidth } = useWindowSize();

const filterMenuVisible = ref(false)
const filterDateRange = ref(null)
const lastFilterSelectionKey = ref(null)
const date_from = ref(null)
const date_to = ref(null)

const balance = ref(null)
const teamMembers = ref([])
const rowPerPage = ref(10)
const currentPage = ref(1)

const items = ref([
  {
    icon: 'custom-lager',
    title: 'Fordon i lager',
    subtitle: 'Aktiva fordon',
    stats: '12',
  },
  {
    icon: 'custom-sold',
    title: 'Sålda fordon',
    subtitle: 'Denna månad',
    stats: '3',
  },
  {
    icon: 'custom-clients',
    title: 'Antal kunder',
    subtitle: 'Registrerade',
    stats: '15',
  },
    // {
    //   icon: 'custom-facture',
    //   title: 'Fakturerat',
    //   subtitle: 'Denna månad',
    //   stats: '45 200 kr',
    // },
  {
    icon: 'custom-sms',
    title: 'SMS skickade',
    subtitle: 'Denna månad',
    stats: '124',
    stats2: '500'
  }
])

const itemsTwo = ref([
  {
    icon: 'custom-clients',
    title: 'Kunder',
    stats: '15'
  },
  {
    icon: 'custom-facture',
    title: 'Fakturor',
    stats: '34'
  },
  {
    icon: 'custom-car',
    title: 'Fordonslager',
    stats: '15'
  },
  {
    icon: 'custom-cash',
    title: 'Värderingar',
    stats: '7'
  },
  {
    icon: 'custom-contract',
    title: 'Avtal',
    stats: '12'
  },
  {
    icon: 'custom-signature',
    title: 'Signera dok.',
    stats: '8'
  },
  {
    icon: 'custom-sms',
    title: 'SMS',
    stats: '124'
  },
  {
    icon: 'custom-swish',
    title: 'Swish',
    stats: '0'
  }
])

const supplierId = computed(() => {
  return props.customerData?.supplier?.id
    ?? props.customerData?.supplier_id
    ?? props.customerData?.id
    ?? null
})


const totalPages = computed(() => {
  if (!teamMembers.value.length)
    return 1

  return Math.max(Math.ceil(teamMembers.value.length / rowPerPage.value), 1)
})

const paginatedTeamMembers = computed(() => {
  const start = (currentPage.value - 1) * rowPerPage.value
  const end = start + rowPerPage.value

  return teamMembers.value.slice(start, end)
})

const paginationData = computed(() => `${teamMembers.value.length} resultat`)

watch(totalPages, value => {
  if (currentPage.value > value)
    currentPage.value = value
})

watch(
  () => [props.isSupplier, supplierId.value],
  () => {
    fetchData()
  },
  { immediate: true },
)

async function fetchTeamData() {
  if (!supplierId.value) {
    teamMembers.value = []
    currentPage.value = 1

    return
  }

  const now = new Date()

  const response = await Suppliers.getCustomerOverviewTeam({
    supplier_id: supplierId.value,
    date_from: date_from.value ? date_from.value :  new Date(now.getFullYear(), now.getMonth(), '01').toISOString().split('T')[0],
    date_to: date_to.value ? date_to.value : new Date(now.getFullYear(), now.getMonth(), now.getDate()).toISOString().split('T')[0],
    limit: -1,
  })

  const payload = response?.data?.data
  const teamTotals = payload?.teamTotals

  items.value[0].stats = teamTotals?.vehicles_stock ?? 0
  items.value[1].stats = teamTotals?.vehicles_sold ?? 0
  items.value[2].stats = teamTotals?.clients ?? 0
  items.value[3].stats = teamTotals?.sms ?? 0



  itemsTwo.value[0].stats = teamTotals?.clients ?? 0
  itemsTwo.value[1].stats = teamTotals?.invoices ?? 0
  itemsTwo.value[2].stats = (teamTotals?.vehicles_stock ?? 0) + (teamTotals?.vehicles_sold ?? 0)
  itemsTwo.value[3].stats = teamTotals?.notes ?? 0
  itemsTwo.value[4].stats = teamTotals?.agreements ?? 0
  itemsTwo.value[5].stats = teamTotals?.documents ?? 0
  itemsTwo.value[6].stats = teamTotals?.sms ?? 0
  itemsTwo.value[7].stats = teamTotals?.payouts ?? 0
  
  teamMembers.value = Array.isArray(payload?.teamMembers) ? payload.teamMembers : []
  currentPage.value = 1
}

async function fetchData() {
  if (props.isSupplier)
    balance.value = null //calcular mas adelante

  await fetchTeamData()
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
      return text.substring(0, length) + "...";
  }
  return text;
};

const resolvePosition = position => {
  if (position === 1)
    return { name: 'Admin' }
  if (position === 2)
    return { name: 'Inköpare' }
  if (position === 3)
    return { name: 'Säljare' }
  if (position === 4)
    return { name: 'Revisor' }
}

const formatLastActive = value => {
  if (!value)
    return '----'

  const date = new Date(value)
  if (Number.isNaN(date.getTime()))
    return '----'

  const now = new Date()

  const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const startOfDate = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const diffDays = Math.floor((startOfToday - startOfDate) / 86400000)

  if (diffDays <= 0)
    return 'Idag'
  if (diffDays === 1)
    return '1 dag sedan'

  return `${diffDays} dagar sedan`
}

watch(filterMenuVisible, isVisible => {
  if (isVisible)
      lastFilterSelectionKey.value = null
})

const normalizeRangeValue = value => {
  if (!value)
      return null

  if (Array.isArray(value)) {
      const start = value[0] ?? null
      const end = value[1] ?? value[0] ?? null

      return start && end ? [start, end] : null
  }

  if (typeof value === 'string') {
      const chunks = value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i).map(item => item.trim()).filter(Boolean)
      if (chunks.length >= 2)
        return [chunks[0], chunks[1]]

      return null
  }

  return null
}

const onFilterDateUpdate = value => {
  const range = normalizeRangeValue(value)
  if (!range)
      return

  const selectionKey = `${range[0]}__${range[1]}`
  if (selectionKey === lastFilterSelectionKey.value)
      return

  lastFilterSelectionKey.value = selectionKey
  filterMenuVisible.value = false
  date_from.value = range[0]
  date_to.value = range[1]

  fetchData();

  // emit('loading', true)
  // emit('filter', {
  //     date_from: range[0],
  //     date_to: range[1],
  // })
}

const clearFilter = () => {
  filterDateRange.value = null
  lastFilterSelectionKey.value = null
  filterMenuVisible.value = false

  emit('loading', true)
  emit('filter', {})
}


</script>

<template>
  <div class="overview pt-6">
    <div class="dashboard-grid gap-4 justify-between pb-3">
      <VCard 
        v-for="(item, index) in items"
        :key="item.title"
        class="card-overview__main w-100">
        <VCardText class="p-0">
          <VAvatar
            rounded="lg"
            size="36"
            style="background-color: #57F28724 !important;"
          >
            <VIcon
              :icon="item.icon"
              size="24"
              color="#6E9383" 
            />
          </VAvatar> 
        </VCardText>
        <VCardTitle class="title-main p-0">
          {{ item.title }} 
        </VCardTitle>
        <VCardText class="stats-main p-0">
          {{ item.stats }}
          <span v-if="item.stats2" class="stats2-main"> / {{ item.stats2 }}</span>
          <VProgressLinear 
            v-if="item.stats2" 
            :model-value="(parseInt(item.stats) * 100) / parseInt(item.stats2)" 
            height="3"
            rounded="pill"   
            color="#57F287"      
            bg-color="#5A7065" 
          />
        </VCardText>
        <VCardText class="subtitle-main p-0">
          {{ item.subtitle }}
        </VCardText>
      </VCard>
    </div>

    <div 
      class="d-flex align-center"
      :class="windowWidth < 1024 ? 'flex-column py-4' : 'justify-between py-3'"
    >
      <span 
        class="item-title"
        :class="windowWidth < 1024 ? 'w-100 pb-3' : ''"
      >
        Aktivitet per modul
      </span>
      <VBtn 
        id="team-filter-button"
        class="btn-transparent px-3"
        :class="windowWidth < 1024 ? 'w-100' : ''"
        @click="filterMenuVisible = true"
      >
        <VIcon icon="custom-calendar-2" size="24" />
        <span class="d-flex" >Datum</span>
      </VBtn>
    </div>

    <ExportDateMenu
        v-model="filterDateRange"
        v-model:menuVisible="filterMenuVisible"
        :show-activator="false"
        :is-mobile="windowWidth < 1024"
        :reset-on-open="false"
        activator="#team-filter-button"
        button-text="Filtrera"
        button-icon="custom-filter"
        picker-label="Filtrera efter datum"
        picker-placeholder="Välj datum"
        @update:modelValue="onFilterDateUpdate"
      />

    <div class="dashboard-secondary justify-between">
      <VCard 
        v-for="(item, index) in itemsTwo"
        :key="item.title"
        class="card-overview__secondary w-100">
        <VCardText class="p-0 d-flex gap-4 align-center">
          <VIcon
              :icon="item.icon"
              size="24"
              color="#6E9383" 
            />
          <div class="d-flex flex-column">
            <span
              class="stats-secondary"
              :class="{ 'stats-secondary--zero': Number(item.stats) === 0 }"
            >{{ item.stats }}</span>
            <span class="title-secondary">{{ item.title }}</span>
          </div>
        </VCardText>
      </VCard>
    </div>

    <div class="d-flex justify-between align-center py-6">
      <span class="item-title">Teamöversikt</span>
      
    </div>

    <VCard v-if="teamMembers" id="rol-list" >
        <VTable
          v-if="!$vuetify.display.mdAndDown"
          v-show="teamMembers.length > 0"
          class="pb-6 text-no-wrap">
          <thead>
            <tr>
              <th scope="col">Skapad Av</th>
              <th scope="col" class="text-center">Roll</th>
              <th scope="col" class="text-center">Fakturor</th>
              <th scope="col" class="text-center">Swish</th>
              <th scope="col" class="text-center">Avtal</th>
              <th scope="col" class="text-center">Senast aktiv</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="member in paginatedTeamMembers" :key="member?.id ?? member?.supplier_id">
              <td style="width: 1%; white-space: nowrap">
                  <div class="d-flex align-center gap-x-1">
                    <VAvatar
                        variant="outlined"
                        size="38"
                    >
                        <VImg
                          v-if="member.avatar"
                          style="border-radius: 50%"
                          :src="themeConfig.settings.urlStorage + member.avatar"
                        />
                        <PresetAvatarImage
                          v-else
                          :avatar-id="member.user_detail?.avatar_id"
                        />
                    </VAvatar>
                    <div class="d-flex flex-column">
                        <div class="d-flex gap-1 align-center font-weight-medium text-aqua">
                          <span class="font-weight-medium">
                              {{ member?.name ?? '' }} {{ member?.last_name ?? "" }}
                          </span>
                          <span class="text-neutral-25" v-if="member.is_boss">
                              (Leverantör)
                          </span>
                        </div>
                        <span class="text-sm text-disabled">
                        <VTooltip 
                          v-if="member?.email && member.email.length > 20"
                          location="bottom">
                          <template #activator="{ props }">
                              <span v-bind="props" class="cursor-pointer">
                              {{ truncateText(member?.email, 20) }}
                              </span>
                          </template>
                          <span>{{ member?.email }}</span>
                        </VTooltip>
                        <span class="text-sm text-disabled"v-else>{{ member?.email ?? '' }}</span>
                        </span>
                    </div>
                  </div>
              </td>
              <td class="d-flex align-center justify-center text-center">
                <div class="status-chip status-chip-disabled" v-if="member.position">
                  {{ resolvePosition(member.position).name }}
                </div>
                <span v-else class="text-neutral-3" >
                  ----  
                </span>
              </td>
              <td class="text-center">
                  <span>{{ member.invoices }}</span>
              </td>
              <td class="text-center">
                  <span>{{ member.swish }}</span>
              </td>
              <td class="text-center">
                  <span>{{ member.agreements }}</span>
              </td>
              <td class="text-center">
                  <span>{{ formatLastActive(member.created_at) }}</span>
              </td>
            </tr>
          </tbody>
        </VTable>

        <VExpansionPanels
          class="expansion-panels pb-6 px-0"
          v-if="paginatedTeamMembers.length && windowWidth < 1024"
        >
          <VExpansionPanel v-for="member in paginatedTeamMembers" :key="member.id" readonly>
            <VExpansionPanelTitle
                collapse-icon="custom-chevron-right"
                expand-icon="custom-chevron-down"
            >
                <div class="d-flex align-center justify-space-between w-100">
                    <div class="d-flex align-center">
                        <span class="order-id">
                            <VAvatar
                            variant="outlined"
                            size="38"
                            >
                                <VImg
                                    v-if="member.avatar"
                                    style="border-radius: 50%"
                                    :src="themeConfig.settings.urlStorage + member.avatar"
                                />
                                <PresetAvatarImage
                                    v-else
                                    :avatar-id="member.user_detail?.avatar_id"
                                />
                            </VAvatar>
                        </span>

                        <div class="order-title-box">
                            <span class="title-panel">
                                {{ member.name ?? '' }} {{ member.last_name ?? "" }}
                                <span class="text-neutral-25 font-12" v-if="member.is_boss">
                                  (Leverantör)
                                </span>
                            </span>
                            <div class="title-organization">
                                {{ truncateText(member.email, 20) }}
                            </div>
                        </div>
                    </div>
                </div>
            </VExpansionPanelTitle>
            <VExpansionPanelText>
                <div class="mb-6">
                    <div class="expansion-panel-item-label">Roll:</div>
                    <div class="expansion-panel-item-value">
                      <div class="status-chip-mobile status-chip-disabled" v-if="member.position">
                        {{ resolvePosition(member.position).name }}
                      </div>
                      <span v-else class="text-neutral-3" >
                        ----  
                      </span>
                    </div>
                </div>
                <div class="mb-6">
                    <div class="expansion-panel-item-label">Fakturor:</div>
                    <div class="expansion-panel-item-value">
                        {{ member.invoices ?? "" }}
                    </div>
                </div>
                <div class="mb-6">
                    <div class="expansion-panel-item-label">Swish:</div>
                    <div class="expansion-panel-item-value">
                        {{ member.swish ?? "" }}
                    </div>
                </div>
                <div class="mb-6">
                    <div class="expansion-panel-item-label">Avtal:</div>
                    <div class="expansion-panel-item-value">
                        {{ member.agreements ?? "" }}
                    </div>
                </div>
                <div>
                    <div class="expansion-panel-item-label">Senast aktiv:</div>
                    <div class="expansion-panel-item-value">
                        <span>{{ formatLastActive(member.created_at) }}</span>
                    </div>
                </div>
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>

        <div
          v-if="!paginatedTeamMembers.length"
          class="empty-state"
          :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
        >
          <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-account"
          />
          <div class="empty-state-content w-100 pa-4">
              <div class="empty-state-title">Inget team ännu</div>
              <div class="empty-state-text">
                  Bjud in dina medarbetare för att börja följa försäljning och prestation.
              </div>
          </div>
        </div>

        <VCardText
            v-if="teamMembers.length"
            :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
            class="align-center flex-wrap gap-4 p-0"
        >
          <span class="text-pagination-results">
            {{ paginationData }}
          </span>

          <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

          <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="4"
              :length="totalPages"
              next-icon="custom-chevron-right"
              prev-icon="custom-chevron-left"
          />
        </VCardText>
    </VCard>
  </div>
</template>

<style lang="scss">

  .stats-secondary {
    font-weight: 700;
    font-size: 18px;
    line-height: 18px;
    letter-spacing: 0%;
    vertical-align: middle;
    color: #1C2925;
  }

  .stats-secondary--zero {
    color: #BDD2C8;
  }
  
  .title-secondary {
    font-weight: 500;
    font-size: 11px;
    line-height: 16.5px;
    letter-spacing: 0%;
    vertical-align: middle;
    color: #6E9383;
  }

  .dashboard-secondary {
    display: grid;
    background-color: #F6F6F6; 
    border: 1px solid #F6F6F6;
    border-radius: 8px !important;
    gap: 1px; 
    overflow: hidden; 
    grid-template-columns: repeat(4, 2fr);
    @media (max-width: 1023px) {
      grid-template-columns: repeat(2, 4fr);
    }
  }

  .card-overview__secondary {
    background-color: #FFFFFF;
    height: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    border: none !important;
    border-radius: 0 !important; 
    padding: 14px;
  }

  .item-title {
    font-weight: 600;
    font-size: 14px;
    line-height: 16px;
    letter-spacing: 0px;
    vertical-align: middle;
    text-transform: capitalize;
    color: #454545;
  }

  .team-overview-empty {
    padding: 24px;
    text-align: center;
    color: #6E9383;
    font-weight: 500;
  }

  #rol-list .v-pagination__list,
  #rol-list .v-pagination__list li {
    list-style: none !important;
    margin: 0;
    padding: 0;
  }

  #rol-list .v-pagination__list li::marker {
    content: '';
  }

  .subtitle-main {
    font-weight: 400;
    font-size: 11px;
    line-height: 16.5px;
    letter-spacing: 0%;
    vertical-align: middle;
    color: #5A7065;
  }

  .stats2-main {
    font-weight: 500;
    font-size: 13px;
    line-height: 14.3px;
    letter-spacing: 0%;
    vertical-align: middle;
    color: #5A7065;
  }

  .stats-main {
    font-weight: 700;
    font-size: 22px;
    line-height: 24.2px;
    letter-spacing: 0%;
    vertical-align: middle;
    color: #1C2925;
  }

  .title-main {
    font-weight: 700;
    font-size: 10px;
    line-height: 15px;
    letter-spacing: 0.5px;
    vertical-align: middle;
    text-transform: uppercase;
    color: #5A7065;
  }

  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    @media (max-width: 1023px) {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  .card-full {
    grid-column: span 1;
    @media (max-width: 1023px) {
      grid-column: span 2;
    }
  }

  .card-overview__main {
    border-radius: 8px !important;
    padding: 16px;
    display: flex;
    flex-direction: column;
    border: 1px solid #D4E6DF;
    background: #F6FDFB;
    height: 170px;
    @media (max-width: 1023px) { 
      height: 157px;
    }
  }

  .overview.v-card--variant-elevated {
      box-shadow: none !important;
  }
</style>
