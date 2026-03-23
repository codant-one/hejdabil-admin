
<script setup>
   const { width: windowWidth } = useWindowSize();

   const taskItems = ref(Array.from({ length: 10 }, (_, index) => ({
      id: index + 1,
      title: `Oljebyte för Volvo XC60 - Reg.nr: DEF${String(index + 1).padStart(3, '0')}`,
      startDate: '10/10/2026',
      endDate: '10/10/2026',
      completed: false,
   })));
</script>

<template>
   <VCard title="" class="card-dashboard information-scroll">
      <VCardTitle 
         class="title-box border-none pb-0"
         :class="windowWidth < 1024 ? 'flex-row align-center' : ''"
      >
         <div class="title-text">Mina uppgifter</div>

         <VBtn
            class="btn-white-2 px-3 h-24"
         >
            <VIcon icon="custom-eye" size="24" />
            Visa avslutade
         </VBtn>

      </VCardTitle>

      <VCardText class="pt-4 form-dashboard" :class="windowWidth < 1024 ? 'px-4' : 'px-6'">
         <div class="d-flex flex-column gap-2">
            <div>
               <VTextField
                  placeholder="Lägg till ny uppgift..."
                />
            </div>
            <div class="d-flex gap-2 w-100">
               <VTextField
                  placeholder="Startdatum"
               />
               <VTextField
                  placeholder="Slutdatum"
               />
               
               <VBtn
                  class="btn-gradient"
                  style="border-radius: 8px !important;"
               >
                  <VIcon icon="custom-add-circle" size="24" />
               </VBtn>

            </div>
         </div>
      </VCardText>

      <VCardText class="pt-4" :class="windowWidth < 1024 ? 'px-4' : 'px-6'">
         <div class="information-list d-flex flex-column">
            <div
               v-for="item in taskItems"
               :key="item.id"
               class="information-item d-flex align-start"
            >
               <VCheckbox
                  v-model="item.completed"
                  hide-details
                  class="information-item__checkbox ms-2"
               />

               <div class="information-item__content">
                  <div class="information-item__title">
                     {{ item.title }}
                  </div>

                  <div class="information-item__meta d-flex align-center">
                     <VIcon icon="custom-calendar" size="16" />
                     <span>{{ item.startDate }} - {{ item.endDate }}</span>
                  </div>
               </div>
            </div>
         </div>
      </VCardText>
   </VCard>
</template>

<style lang="scss">
   .form-dashboard {
      border-bottom: 1px solid #F6F6F6;
      & .v-input {
         .v-input__prepend {
            padding-top: 12px !important;
         }
         & .v-input__control {
         .v-field {
            background-color: #FFFFFF;
            min-height: 48px !important;

            .v-text-field__suffix {
               padding: 12px 16px !important;
            }

            .v-field__input {
               min-height: 48px !important;
               padding: 12px 16px !important;

               @media (max-width: 767px) { 
                  font-size: 13px !important;
               }
               input {
                  min-height: 48px !important;
               }
            }

            .v-field-label {
               top: 12px !important;
            }

            .v-field__append-inner {
               align-items: center;
               padding-top: 0px;
            }
         }
         }
      }
   }

   .card-dashboard {
      height: 100%;
      display: flex;
      flex-direction: column;
      overflow: hidden;
   }

   .information-scroll {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      scrollbar-width: none;
      -ms-overflow-style: none;
   }

   .information-scroll::-webkit-scrollbar {
      display: none;
   }

   .card-dashboard {
      .information-list {
         gap: 20px;
      }

      .information-item {
         gap: 12px;
      }

      .information-item__checkbox {
         flex: 0 0 auto;
      }

      .information-item__content {
         min-width: 0;
      }

      .information-item__title {
         color: #454545;
         font-weight: 400;
         font-size: 14px;
         line-height: 20px;
         letter-spacing: 0px;
         vertical-align: middle;

         @media (max-width: 767px) {
            font-size: 13px;
         }
      }

      .information-item__meta {
         color: #878787;
         font-weight: 400;
         font-size: 14px;
         line-height: 20px;
         letter-spacing: 0px;
         vertical-align: middle;
         gap: 4px;
         line-height: 22px;
      }
   }
</style>