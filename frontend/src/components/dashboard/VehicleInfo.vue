<script setup>
   import { formatNumber } from "@/@core/utils/formatters";

   const brandLogoModules = import.meta.glob('../../assets/images/brands/*.svg', {
      eager: true,
      import: 'default',
   });

   const getBrandLogo = fileName => brandLogoModules[`../../assets/images/brands/${fileName}`] ?? '';

   const activeTab = ref(0);
   const { width: windowWidth } = useWindowSize();
   const dragScroll = reactive({
      isDragging: false,
      startX: 0,
      scrollLeft: 0,
      activeElement: null,
   });

   const inventoryVehicles = [
      {
         id: 1,
         brandLogo: getBrandLogo('ford.svg'),
         title: 'Ford Kuga Hybrid',
         year: 2022,
         regNumber: 'XTR230',
         weeksInStock: 2,
         purchasePrice: 25150,
         costs: 25150,
         purchaseDate: '2025-08-21',
      },
      {
         id: 2,
         brandLogo: getBrandLogo('volvo.svg'),
         title: 'Volvo XC60',
         year: 2021,
         regNumber: 'JLK552',
         weeksInStock: 3,
         purchasePrice: 31900,
         costs: 32750,
         purchaseDate: '2025-08-18',
      },
      {
         id: 3,
         brandLogo: getBrandLogo('bmw.svg'),
         title: 'BMW X3 xDrive30e',
         year: 2023,
         regNumber: 'NMO441',
         weeksInStock: 1,
         purchasePrice: 41200,
         costs: 41890,
         purchaseDate: '2025-08-24',
      },
      {
         id: 4,
         brandLogo: getBrandLogo('audi.svg'),
         title: 'Audi Q5 TFSI e',
         year: 2022,
         regNumber: 'PLA902',
         weeksInStock: 4,
         purchasePrice: 38950,
         costs: 39550,
         purchaseDate: '2025-08-12',
      },
      {
         id: 5,
         brandLogo: getBrandLogo('toyota.svg'),
         title: 'Toyota RAV4 Hybrid',
         year: 2021,
         regNumber: 'KSD118',
         weeksInStock: 2,
         purchasePrice: 28400,
         costs: 29120,
         purchaseDate: '2025-08-20',
      },
      {
         id: 6,
         brandLogo: getBrandLogo('mercedes-benz.svg'),
         title: 'Mercedes GLC 300e',
         year: 2023,
         regNumber: 'RTA671',
         weeksInStock: 5,
         purchasePrice: 43800,
         costs: 44650,
         purchaseDate: '2025-08-09',
      },
   ];

   const soldVehicles = inventoryVehicles.map((vehicle, index) => ({
      ...vehicle,
      id: `sold-${vehicle.id}`,
      weeksInStock: index + 1,
      salePrice: vehicle.purchasePrice + 5200 + index * 750,
      costs: vehicle.costs + 900,
      profitAmount: vehicle.purchasePrice + 5200 + index * 750 - (vehicle.costs + 900),
      profitMargin: 23 + index,
      saleDate: vehicle.purchaseDate,
   }));

   const onDragStart = event => {
      if (event.button !== 0)
         return;

      dragScroll.isDragging = true;
      dragScroll.startX = event.clientX;
      dragScroll.scrollLeft = event.currentTarget.scrollLeft;
      dragScroll.activeElement = event.currentTarget;

      document.body.style.userSelect = 'none';
      document.body.style.cursor = 'grabbing';
      event.currentTarget.style.scrollBehavior = 'auto';

      window.addEventListener('mousemove', onDragMove);
      window.addEventListener('mouseup', onDragEnd);

      event.preventDefault();
   };

   const onDragMove = event => {
      if (!dragScroll.isDragging || !dragScroll.activeElement)
         return;

      event.preventDefault();

      const deltaX = event.clientX - dragScroll.startX;
      dragScroll.activeElement.scrollLeft = dragScroll.scrollLeft - deltaX;
   };

   const onDragEnd = () => {
      if (!dragScroll.isDragging)
         return;

      window.removeEventListener('mousemove', onDragMove);
      window.removeEventListener('mouseup', onDragEnd);
      document.body.style.userSelect = '';
      document.body.style.cursor = '';
      if (dragScroll.activeElement)
         dragScroll.activeElement.style.scrollBehavior = '';

      dragScroll.isDragging = false;
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
            <VBtn class="btn-white-2 h-40 w-auto" block>
               Sortera
               <VIcon icon="custom-arrow-down" size="16" /> 
            </VBtn>

            <VBtn class="btn-light px-3 h-40" block>
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

                     <div class="vehicle-dashboard-card__weeks">{{ vehicle.weeksInStock }} veckor</div>
                  </div>

                  <div class="vehicle-dashboard-card__body">
                     <div class="vehicle-dashboard-card__title">
                        {{ vehicle.title }} <span>({{ vehicle.year }})</span>
                     </div>
                     <div class="vehicle-dashboard-card__reg">{{ vehicle.regNumber }}</div>
                  </div>

                  <div class="vehicle-dashboard-card__divider" />

                  <div class="vehicle-dashboard-card__meta">
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

                     <div class="vehicle-dashboard-card__weeks">{{ vehicle.weeksInStock }} veckor</div>
                  </div>

                  <div class="vehicle-dashboard-card__body">
                     <div class="vehicle-dashboard-card__title">
                        {{ vehicle.title }} <span>({{ vehicle.year }})</span>
                     </div>
                     <div class="vehicle-dashboard-card__reg">{{ vehicle.regNumber }}</div>
                  </div>

                  <div class="vehicle-dashboard-card__divider" />

                  <div class="vehicle-dashboard-card__meta">
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
      user-select: none;
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