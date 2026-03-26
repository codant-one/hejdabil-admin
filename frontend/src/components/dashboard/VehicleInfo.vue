<script setup>
   import { formatNumber } from "@/@core/utils/formatters";
   import { themeConfig } from '@themeConfig'

   const emit = defineEmits(['filter', 'loading'])

   const props = defineProps({
      stockVehicles: {
         type: Array,
         default: () => [],
      },
      soldVehicles: {
         type: Array,
         default: () => [],
      },
   })

   const activeTab = ref(0);
   const { width: windowWidth } = useWindowSize();
   const sortMobile = ref(false);
   const selectedSort = ref('latest_added')

   const sortOptions = [
      {
         title: 'Äldst i lager',
         value: 'oldest_in_stock',
      },
      {
         title: 'Senast inlagda',
         value: 'latest_added',
      },
      {
         title: 'Högst pris',
         value: 'highest_price',
      },
   ]

   const dragScroll = reactive({
      isDragging: false,
      isPointerDown: false,
      startX: 0,
      scrollLeft: 0,
      activeElement: null,
   });

   const DRAG_START_THRESHOLD = 8

   const formatRelativeTime = dateString => {
      if (!dateString)
         return ''

      const date = new Date(dateString)
      if (Number.isNaN(date.getTime()))
         return ''

      const now = new Date()
      const diffInSeconds = Math.floor((now - date) / 1000)
      const diffInMinutes = Math.floor(diffInSeconds / 60)
      const diffInHours = Math.floor(diffInSeconds / 3600)
      const diffInDays = Math.floor(diffInSeconds / 86400)

      if (diffInSeconds < 0)
         return date.toLocaleDateString('sv-SE')

      if (diffInSeconds < 60)
         return 'Nyss'

      if (diffInMinutes < 60)
         return `${diffInMinutes} ${diffInMinutes === 1 ? 'minut' : 'minuter'}`

      if (diffInHours < 24)
         return `${diffInHours} ${diffInHours === 1 ? 'timme' : 'timmar'}`

      if (diffInDays < 7)
         return `${diffInDays} ${diffInDays === 1 ? 'dag' : 'dagar'}`

      if (diffInDays < 30) {
         const diffInWeeks = Math.floor(diffInDays / 7)
         return `${diffInWeeks} ${diffInWeeks === 1 ? 'vecka' : 'veckor'}`
      }

      const totalMonths = (now.getFullYear() - date.getFullYear()) * 12 + (now.getMonth() - date.getMonth())
      const adjustedMonths = now.getDate() < date.getDate() ? totalMonths - 1 : totalMonths

      if (adjustedMonths < 12) {
         const safeMonths = Math.max(1, adjustedMonths)
         return `${safeMonths} ${safeMonths === 1 ? 'månad' : 'månader'}`
      }

      const diffInYears = Math.floor(adjustedMonths / 12)
      if (diffInYears >= 1)
         return `${diffInYears} ${diffInYears === 1 ? 'år' : 'år'}`

      return date.toLocaleDateString('sv-SE')
   }

   const buildBrandLogo = vehicle => {
      const logo = vehicle?.model?.brand?.logo
      return logo ? `${themeConfig.settings.urlStorage}${logo}` : ''
   }

   const onSelectSort = sortValue => {
      if (!sortValue || sortValue === selectedSort.value) {
         sortMobile.value = false
         return
      }

      selectedSort.value = sortValue
      sortMobile.value = false
      emit('loading', true)
      emit('filter', {
         sort_by: sortValue,
      })
   }

   const vehiclesRoute = computed(() => activeTab.value === 0
      ? { name: 'dashboard-admin-stock' }
      : { name: 'dashboard-admin-sold' })

   const inventoryVehicles = computed(() => (props.stockVehicles ?? []).map(vehicle => ({
      id: vehicle?.id,
      brandLogo: buildBrandLogo(vehicle),
      title: vehicle?.title ?? '',
      year: vehicle?.year ?? '',
      regNumber: vehicle?.reg_num ?? '',
      weeksInStock: formatRelativeTime(vehicle?.created_at),
      purchasePrice: vehicle?.purchase_price ?? 0,
      costs: vehicle?.total_costs ?? 0,
      purchaseDate: vehicle?.purchase_date ?? '',
      raw: vehicle,
   })))

   const soldVehicles = computed(() => (props.soldVehicles ?? []).map(vehicle => ({
      id: vehicle?.id,
      brandLogo: buildBrandLogo(vehicle),
      title: vehicle?.title ?? '',
      year: vehicle?.year ?? '',
      regNumber: vehicle?.reg_num ?? '',
      weeksInStock: formatRelativeTime(vehicle?.created_at),
      purchasePrice: vehicle?.purchase_price ?? 0,
      salePrice: vehicle?.sale_price ?? 0,
      costs: vehicle?.total_costs ?? 0,
      profitAmount: vehicle?.profit_amount ?? 0,
      profitMargin: vehicle?.profit_margin ?? 0,
      purchaseDate: vehicle?.purchase_date ?? '',
      saleDate: vehicle?.sale_date ?? '',
      raw: vehicle,
   })))

   const isSelectionTarget = target => target instanceof Element
      && !!target.closest('[data-allow-selection="true"]')

   const onDragStart = event => {
      if (event.button !== 0)
         return;

      if (isSelectionTarget(event.target))
         return;

      dragScroll.isPointerDown = true;
      dragScroll.isDragging = false;
      dragScroll.startX = event.clientX;
      dragScroll.scrollLeft = event.currentTarget.scrollLeft;
      dragScroll.activeElement = event.currentTarget;

      window.addEventListener('mousemove', onDragMove);
      window.addEventListener('mouseup', onDragEnd);
   };

   const onDragMove = event => {
      if (!dragScroll.isPointerDown || !dragScroll.activeElement)
         return;

      const deltaX = event.clientX - dragScroll.startX;

      if (!dragScroll.isDragging) {
         if (Math.abs(deltaX) < DRAG_START_THRESHOLD)
            return;

         dragScroll.isDragging = true;
         document.body.style.userSelect = 'none';
         document.body.style.cursor = 'grabbing';
         dragScroll.activeElement.style.scrollBehavior = 'auto';
      }

      event.preventDefault();
      dragScroll.activeElement.scrollLeft = dragScroll.scrollLeft - deltaX;
   };

   const onDragEnd = () => {
      if (!dragScroll.isPointerDown)
         return;

      window.removeEventListener('mousemove', onDragMove);
      window.removeEventListener('mouseup', onDragEnd);
      document.body.style.userSelect = '';
      document.body.style.cursor = '';
      if (dragScroll.activeElement)
         dragScroll.activeElement.style.scrollBehavior = '';

      dragScroll.isDragging = false;
      dragScroll.isPointerDown = false;
      dragScroll.activeElement = null;
   };

   onBeforeUnmount(() => {
      window.removeEventListener('mousemove', onDragMove);
      window.removeEventListener('mouseup', onDragEnd);
      document.body.style.userSelect = '';
      document.body.style.cursor = '';
      if (dragScroll.activeElement)
         dragScroll.activeElement.style.scrollBehavior = '';
   });
</script>

<template>
   <VCard title="" class="card-dashboard">
      <VCardTitle class="title-box">
         <div class="title-text">Fordonsöversikt</div> 
         
         <div class="d-flex gap-4" :class="windowWidth < 1024 ? 'w-100' : ''">
            <VMenu 
               v-if="windowWidth >= 1024">
               <template #activator="{ props }">
                  <VBtn
                     class="btn-white-2 h-40 w-auto"
                     v-bind="props"
                  >
                     Sortera
                     <VIcon icon="custom-arrow-down" size="16" /> 
                  </VBtn>
               </template>

               <VList>
                  <VListItem
                     v-for="option in sortOptions"
                     :key="option.value"
                     @click="onSelectSort(option.value)"
                  >
                     <VListItemTitle>{{ option.title }}</VListItemTitle>
                  </VListItem>
               </VList>
            </VMenu>

            <VBtn
               v-else
               class="btn-white-2 h-40 w-auto"
               block
               @click="sortMobile = true"
            >
               Sortera
               <VIcon icon="custom-arrow-down" size="16" /> 
            </VBtn>

            <VBtn class="btn-light px-3 h-40" block :to="vehiclesRoute">
               Visa alla fordon
            </VBtn>
         </div>
      </VCardTitle>
      <VCardText class="pt-0">
         <VTabs
            v-model="activeTab"
            grow          
            :show-arrows="false"
            class="vehicles-tabs"
        >
            <VTab :value="0" >
               <VIcon size="24" icon="custom-autofordon" />
               Bilar i lager
            </VTab>
            <VTab :value="1">
               <VIcon size="24" icon="custom-car-close" />
               Sålda bilar
            </VTab>
        </VTabs>
      </VCardText>

      <VCardText class="pt-0">
         <VWindow v-model="activeTab" :touch="false">
            <VWindowItem :value="0" class="px-md-0">
               <div
                  class="vehicle-scroll-row"
                  :class="{ 'vehicle-scroll-row--dragging': dragScroll.isDragging }"
                  @mousedown="onDragStart"
                  @dragstart.prevent
               >
                  <article
                     v-for="vehicle in inventoryVehicles"
                     :key="vehicle.id"
                     class="vehicle-dashboard-card"
                     draggable="false"
                  >
                     <div class="vehicle-dashboard-card__header">
                        <div class="vehicle-dashboard-card__brand">
                           <VImg
                              :src="vehicle.brandLogo"
                              :alt="vehicle.title"
                              contain
                              width="64"
                              height="64"
                              draggable="false"
                           />
                        </div>

                        <div class="vehicle-dashboard-card__weeks">{{ vehicle.weeksInStock }}</div>
                     </div>

                     <div class="vehicle-dashboard-card__body">
                        <div data-allow-selection="true">
                        <div class="vehicle-dashboard-card__title">
                           {{ vehicle.title }} <span>({{ vehicle.year }})</span>
                        </div>
                        <div class="vehicle-dashboard-card__reg">{{ vehicle.regNumber }}</div>
                        </div>
                     </div>

                     <div class="vehicle-dashboard-card__divider" />

                     <div class="vehicle-dashboard-card__meta" data-allow-selection="true">
                        <div class="vehicle-dashboard-card__row">
                           <span>Inköpspris</span>
                           <strong>{{ formatNumber(vehicle.purchasePrice) }} kr</strong>
                        </div>
                        <div class="vehicle-dashboard-card__row">
                           <span>Kostnader</span>
                           <strong>{{ formatNumber(vehicle.costs) }} kr</strong>
                        </div>
                        <div class="vehicle-dashboard-card__row">
                           <span>Inköpsdatum</span>
                           <strong>{{ vehicle.purchaseDate }}</strong>
                        </div>
                     </div>
                  </article>
               </div>
            </VWindowItem>
            <VWindowItem :value="1" class="px-md-0">
               <div
                  class="vehicle-scroll-row"
                  :class="{ 'vehicle-scroll-row--dragging': dragScroll.isDragging }"
                  @mousedown="onDragStart"
                  @dragstart.prevent
               >
                  <article
                     v-for="vehicle in soldVehicles"
                     :key="vehicle.id"
                     class="vehicle-dashboard-card"
                     draggable="false"
                  >
                     <div class="vehicle-dashboard-card__header">
                        <div class="vehicle-dashboard-card__brand">
                           <VImg
                              :src="vehicle.brandLogo"
                              :alt="vehicle.title"
                              contain
                              width="64"
                              height="64"
                              draggable="false"
                           />
                        </div>

                        <div class="vehicle-dashboard-card__weeks">{{ vehicle.weeksInStock }}</div>
                     </div>

                     <div class="vehicle-dashboard-card__body">
                        <div data-allow-selection="true">
                        <div class="vehicle-dashboard-card__title">
                           {{ vehicle.title }} <span>({{ vehicle.year }})</span>
                        </div>
                        <div class="vehicle-dashboard-card__reg">{{ vehicle.regNumber }}</div>
                        </div>
                     </div>

                     <div class="vehicle-dashboard-card__divider" />

                     <div class="vehicle-dashboard-card__meta" data-allow-selection="true">
                        <div class="vehicle-dashboard-card__row">
                           <span>Inköpspris</span>
                           <strong>{{ formatNumber(vehicle.purchasePrice) }} kr</strong>
                        </div>
                        <div class="vehicle-dashboard-card__row">
                           <span>Försäljningspris</span>
                           <strong>{{ formatNumber(vehicle.salePrice) }} kr</strong>
                        </div>
                        <div class="vehicle-dashboard-card__row">
                           <span>Kostnader</span>
                           <strong>{{ formatNumber(vehicle.costs) }} kr</strong>
                        </div>
                        <div class="vehicle-dashboard-card__row">
                           <span>Vinst</span>
                           <strong>{{ formatNumber(vehicle.profitAmount) }} kr - {{ vehicle.profitMargin }}%</strong>
                        </div>
                        <div class="vehicle-dashboard-card__row">
                           <span>Inköpsdatum</span>
                           <strong>{{ vehicle.purchaseDate }}</strong>
                        </div>
                        <div class="vehicle-dashboard-card__row">
                           <span>Försäljningsdatum</span>
                           <strong>{{ vehicle.saleDate }}</strong>
                        </div>
                     </div>
                  </article>
               </div>
            </VWindowItem>
         </VWindow>
      </VCardText>
   </VCard>

   <!-- 👉 Sort Mobile Dialog -->
   <VDialog
      v-model="sortMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
   >
      <VCard>
         <VList>
            <VListItem
               v-for="option in sortOptions"
               :key="option.value"
               @click="onSelectSort(option.value)"
            >
               <VListItemTitle>{{ option.title }}</VListItemTitle>
            </VListItem>
         </VList>
      </VCard>
   </VDialog>
</template>

<style lang="scss">

   .v-tabs.vehicles-tabs {
      .v-btn {
         min-width: 50px !important;
         .v-btn__content {
            font-size: 14px !important;
            color: #454545;
         }
      }
   }

   .vehicle-scroll-row {
      display: flex;
      gap: 16px;
      overflow-x: auto;
      overflow-y: hidden;
      padding: 0;
      scrollbar-width: none;
      -ms-overflow-style: none;
      cursor: grab;
      scroll-behavior: auto;
      -webkit-overflow-scrolling: touch;
      touch-action: pan-x pinch-zoom;
      overscroll-behavior-x: contain;

      &::-webkit-scrollbar {
         display: none;
         width: 0;
         height: 0;
      }
   }

   .vehicle-scroll-row--dragging {
      cursor: grabbing;
      user-select: none;
   }

   .vehicle-dashboard-card {
      flex: 0 0 281px;
      background: #ECFFFF;
      border-radius: 16px;
      padding: 16px;
      gap: 16px;
      display: flex;
      flex-direction: column;
   }

   .vehicle-dashboard-card__header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
   }

   .vehicle-dashboard-card__brand {
      width: 64px;
      height: 64px;
      border-radius: 8px;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
   }

   .vehicle-dashboard-card__weeks {
      font-weight: 600;
      font-size: 14px;
      line-height: 100%;
      letter-spacing: 0;
      color: #008C91;
      text-align: right;
   }

   .vehicle-dashboard-card__body {
      display: flex;
      flex-direction: column;
      gap: 4px;
   }

   .vehicle-dashboard-card__title {
      color: #454545;
      font-weight: 700;
      font-size: 16px;
      line-height: 100%;
      letter-spacing: 0;

      @media (max-width: 776px) {
         font-size: 14px;
      }
   }

   .vehicle-dashboard-card__title span {
      font-weight: 400;
      font-size: 16px;
      line-height: 100%;
      letter-spacing: 0;

      @media (max-width: 776px) {
         font-size: 14px;
      }

   }

   .vehicle-dashboard-card__reg {
      color: #878787;
      font-weight: 400;
      font-size: 16px;
      line-height: 100%;
      letter-spacing: 0;

      @media (max-width: 776px) {
         font-size: 14px;
      }
   }

   .vehicle-dashboard-card__divider {
      height: 1px;
      background: #E7E7E7;
      margin: 8px 0;
   }

   .vehicle-dashboard-card__meta {
      display: flex;
      flex-direction: column;
      gap: 8px;
   }

   .vehicle-dashboard-card__row {
      display: flex;
      align-items: center;
      gap: 16px;
      color: #878787;
      font-weight: 400;
      font-size: 14px;
      line-height: 100%;
      letter-spacing: 0;

      @media (max-width: 776px) {
         font-size: 12px;
      }
   }

   .vehicle-dashboard-card__row strong {
      color: #454545;
      font-weight: 600;
      font-size: 14px;
      line-height: 100%;
      letter-spacing: 0;

      @media (max-width: 776px) {
         font-size: 12px;
      }
   }

   @media (max-width: 776px) {
      .v-tabs.vehicles-tabs {
         .v-icon {
            width: 20px!important;
            height: 20px!important;
         }
         .v-btn {
            .v-btn__content {
               white-space: break-spaces;
            }
         }
      }

      .vehicle-dashboard-card {
         flex: 0 0 240px;
      }    
   }

</style>