
<script setup>

   import { formatNumber } from "@/@core/utils/formatters";

   const activeTab = ref(0);
   const { width: windowWidth } = useWindowSize();

    const tabData = {
     0: [
       { icon: 'custom-autofordon', iconColor: '#6E9383', iconBg: '#D8FFE4', value: '20', label: 'Antal' },
       { icon: 'custom-pris-information', iconColor: '#4BBFBF', iconBg: '#C0FEFF', value: formatNumber('20000'), suffix: 'KR', label: 'Värde' },
       { icon: 'custom-calendar', iconColor: '#878787', iconBg: '#F6F6F6', value: '46%', suffix: '', label: 'Månadsförändring' },
     ],
     1: [
       { icon: 'custom-autofordon', iconColor: '#6E9383', iconBg: '#D8FFE4', value: '12', label: 'Antal' },
       { icon: 'custom-pris-information', iconColor: '#4BBFBF', iconBg: '#C0FEFF', value: formatNumber('14500'), suffix: 'KR', label: 'Värde' },
       { icon: 'custom-calendar', iconColor: '#878787', iconBg: '#F6F6F6', value: '23%', suffix: '', label: 'Månadsförändring' },
     ],
     2: [
       { icon: 'custom-autofordon', iconColor: '#6E9383', iconBg: '#D8FFE4', value: '8', label: 'Antal' },
       { icon: 'custom-pris-information', iconColor: '#4BBFBF', iconBg: '#C0FEFF', value: formatNumber('98000'), suffix: 'KR', label: 'Värde' },
       { icon: 'custom-calendar', iconColor: '#878787', iconBg: '#F6F6F6', value: '31%', suffix: '', label: 'Månadsförändring' },
     ],
   };
</script>

<template>
   <VCard title="" class="card-dashboard">
      <VCardTitle class="title-box">
         <div class="title-text">Nyckeltal</div>

         <div class="d-flex gap-2" :class="windowWidth < 1024 ? 'flex-column w-100' : ''">
            <VBtn
               class="btn-light w-auto h-40"
               block
            >
               <VIcon icon="custom-export" size="24" />               
               Exportera
            </VBtn>

            <VBtn
              class="btn-white-2 h-40"
              block
            >
               <VIcon icon="custom-filter" size="24" color="#6E9383"/>
               <span class="text-gunmetal-3">Filtrera efter datum</span>
            </VBtn>
         </div>
      </VCardTitle>

      <VCardText class="pt-0 h-0 px-4 px-md-6">
         <VTabs
            v-model="activeTab"
            grow          
            :show-arrows="false"
            class="vehicles-dashboard-tabs"
        >
            <VTab :value="0" >
               <VIcon size="24" icon="custom-autofordon" />
               Bilar i lager
            </VTab>
            <VTab :value="1">
               <VIcon size="24" icon="custom-car-open" />
               Inköpta bilar
            </VTab>
            <VTab :value="2">
               <VIcon size="24" icon="custom-car-close" />
               Sålda bilar
            </VTab>
        </VTabs>
      </VCardText>

      <VCardText class="pt-0 flex-grow-1 d-flex flex-column">
        <VWindow v-model="activeTab" class="flex-grow-1">
         <VWindowItem v-for="tab in 3" :key="tab" :value="tab - 1" class="px-md-0 h-100">
            <div class="indicator-grid">
               <div v-for="(card, i) in tabData[tab - 1]" :key="i" class="indicator-card">
                  <VAvatar
                     :color="card.iconBg"
                     :icon="card.icon"
                     variant="flat"
                     size="56"
                     rounded="lg"
                     class="indicator-icon"
                     :style="{ '--icon-color': card.iconColor }"
                  />
                  <div class="indicator-info">
                     <div class="indicator-value">
                        {{ card.value }}<span v-if="card.suffix" class="indicator-suffix">{{ card.suffix }}</span>
                     </div>
                     <div class="indicator-label">{{ card.label }}</div>
                  </div>
               </div>
            </div>
         </VWindowItem>
      </VWindow>
      </VCardText>
   </VCard>
</template>

<style lang="scss">

   .v-tabs.vehicles-dashboard-tabs {
      .v-btn {
         min-width: 50px !important;
         .v-btn__content {
            font-size: 14px !important;
            color: #454545;
         }
      }
   }

   @media (max-width: 776px) {
      .v-tabs.v-tabs--horizontal:not(.v-tabs-pill) .v-btn {
         padding-right: 6px;
         padding-left: 6px;
      }
      .v-tabs.v-tabs--horizontal:not(.v-tabs-pill) .v-btn__content {
         gap: 4px !important;
      }
      .v-tabs.vehicles-dashboard-tabs {
         .v-icon {
            width: 20px!important;
            height: 20px!important;
         }
         .v-btn {
            .v-btn__content {
               white-space: break-spaces;
               font-size: 10px !important;
            }
         }
      }
   }

   .v-window {
      .v-window__container {
         height: 100%;
      }
   }

   .indicator-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      height: 100%;

      @media (max-width: 1023px) {
         grid-template-columns: 1fr;
         gap: 8px;
         margin-top: 40px;
         height: 217px;
      }
   }

   .indicator-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      border: 1px solid #F0F0F0;
      border-radius: 8px;
      gap: 8px;
      height: 100%;

      @media (max-width: 1023px) {
         flex-direction: row;
         justify-content: flex-start;
         padding: 8px;
         gap: 16px;
         height: 67px;
      }
   }

   .indicator-content-mobile {
      display: flex;
      flex-direction: column;
   }

   .indicator-info {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;

      @media (max-width: 1023px) {
         align-items: flex-start;
      }
   }

   .indicator-icon .v-icon {
      color: var(--icon-color) !important;
   }

   .indicator-value {
      font-weight: 600;
      font-size: 24px;
      line-height: 100%;
      letter-spacing: 0px;
      text-align: center;
      vertical-align: middle;
      color: #454545;
   }

   .indicator-suffix {
      font-weight: 400;
      font-size: 16px;
      line-height: 100%;
      letter-spacing: 0px;
      text-align: center;
      vertical-align: middle;
      color: #454554;
   }

   .indicator-label {
      font-weight: 400;
      font-size: 10px;
      line-height: 16px;
      letter-spacing: 0px;
      text-align: center;
      vertical-align: middle;
      color: #454545;
   }

</style>