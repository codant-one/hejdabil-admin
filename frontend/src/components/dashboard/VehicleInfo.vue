<script setup>
   const activeTab = ref(0);
   const { width: windowWidth } = useWindowSize();
</script>

<template>
   <VCard title="" class="card-dashboard">
      <VCardTitle 
         class="title-box"
         :class="windowWidth < 1024 ? 'flex-row' : ''"
      >
         <div class="title-text">Fordonsöversikt</div> 
         
         <div class="d-flex gap-2">
            <VBtn
               class="btn-ghost w-auto h-40"
            >              
               <span class="d-none d-md-block">Sortera</span>
            </VBtn>

            <VBtn
               class="btn-light px-3 h-40"
            >
               <span class="d-none d-md-block">Visa alla fordon</span>
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
        <VWindow v-model="activeTab">
         <VWindowItem :value="0" class="px-md-0">
            carros en inventario
         </VWindowItem>
         <VWindowItem :value="1" class="px-md-0">
            carros vendidos
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
   }

</style>