
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
         <div class="title-text">Nyckeltal</div>

         <div class="d-flex gap-2">
            <VBtn
               :class="windowWidth < 1024 ? 'btn-ghost' : 'btn-light'"
               class="w-auto h-40"
            >
               <VIcon icon="custom-export" size="24" />               
               <span class="d-none d-md-block">Exportera</span>
            </VBtn>

            <VBtn
               class="btn-white-2 px-3 h-40"
            >
               <VIcon icon="custom-filter" size="24" />
               <span class="d-none d-md-block">Filtrera efter datum</span>
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
               <VIcon size="24" icon="custom-car-open" />
               Inköpta bilar
            </VTab>
            <VTab :value="2">
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
            carros comprados
         </VWindowItem>
         <VWindowItem :value="2" class="px-md-0">
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