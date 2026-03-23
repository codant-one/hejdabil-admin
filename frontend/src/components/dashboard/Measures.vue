<script setup>
   const { width: windowWidth } = useWindowSize();

   const measureItems = ref(Array.from({ length: 10 }, (_, index) => ({
      id: index + 1,
      title: `Lexus RX 450h, ${2009 + index}`,
      description: 'Ring tillbaka Maria ang. BMW visning Ring tillbaka Maria ang. BMW visning Ring, tillbaka Maria ang. BMW visning Ring tillbaka Maria ang. BMW visning',
      amount: '1.000.000 SEK',
      startDate: '10/10/2026',
      endDate: '10/10/2026',
      completed: false,
   })));

   const truncateText = (text, length = 15) => {
      if (text && text.length > length) {
         return text.substring(0, length) + '...';
      }
      return text;
   };
</script>

<template>
   <VCard title="" class="card-dashboard  measures-scroll">
      <VCardTitle 
         class="title-box"
         :class="windowWidth < 1024 ? 'flex-row align-center' : ''"
      >
         <div class="title-text">Åtgärder</div>

         <VBtn
            class="btn-white-2 px-3 h-24"
         >
            <VIcon icon="custom-eye" size="24" />
            Visa avslutade
         </VBtn>
      </VCardTitle>

      <VCardText class="pt-6 measures-card-text" :class="windowWidth < 1024 ? 'px-4' : 'px-6'">
         <div class="measure-list d-flex flex-column">
            <div
               v-for="item in measureItems"
               :key="item.id"
               class="measure-item d-flex align-start"
            >
               <VCheckbox
                  v-model="item.completed"
                  hide-details
                  class="measure-item__checkbox ms-2"
               />

               <div class="measure-item__content">
                  <div class="measure-item__header d-flex align-center flex-wrap">
                     <VIcon icon="custom-autofordon" size="16" color="#6E9383" />
                     <span class="measure-item__title">{{ item.title }}</span>
                     <VIcon icon="custom-arrow-right" size="16" color="#6E9383" />
                  </div>

                  <div class="measure-item__description">
                     {{ truncateText(item.description, windowWidth < 1024 ? 70 : 130) }}
                  </div>

                  <div class="measure-item__meta d-flex align-center">
                     <div class="measure-item__meta-group d-flex align-center">
                        <VIcon icon="custom-pris-information" size="16" color="#878787" />
                        <span>{{ item.amount }}</span>
                     </div>

                     <div class="measure-item__meta-group d-flex align-center">
                        <VIcon icon="custom-calendar" size="16" color="#878787" />
                        <span>{{ item.startDate }} - {{ item.endDate }}</span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </VCardText>
   </VCard>
</template>

<style lang="scss">

   .card-dashboard {
      height: 100%;
      display: flex;
      flex-direction: column;
      overflow: hidden;
   }

   .measures-scroll {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      scrollbar-width: none;
      -ms-overflow-style: none;
   }

   .measures-scroll::-webkit-scrollbar {
      display: none;
   }

   .measures-card-text {
      border-bottom: 1px solid #F6F6F6;
   }

   .measure-list {
      gap: 24px;
   }

   .measure-item {
      gap: 12px;
   }

   .measure-item__checkbox {
      flex: 0 0 auto;
   }

   .measure-item__content {
      min-width: 0;
      display: flex;
      flex: 1 1 auto;
      flex-direction: column;
      gap: 4px;
   }

   .measure-item__header {
      gap: 4px;
   }

   .measure-item__title {
      font-weight: 400;
      font-size: 14px;
      line-height: 20px;
      letter-spacing: 0px;
      vertical-align: middle;
      color: #6E9383;

      @media (max-width: 767px) {
         font-size: 16px;
      }
   }

   .measure-item__description {
      color: #454545;
      font-weight: 400;
      font-size: 14px;
      line-height: 20px;
      letter-spacing: 0px;
      vertical-align: middle;
   }

   .measure-item__meta {
      color: #878787;
      font-weight: 400;
      font-size: 14px;
      line-height: 20px;
      letter-spacing: 0px;
      vertical-align: middle;
      gap: 16px;

      @media (max-width: 767px) {
         font-size: 12px;
         flex-direction: row;
         gap: 4px;
      }
   }

   .measure-item__meta-group {
      gap: 4px;
   }
</style>