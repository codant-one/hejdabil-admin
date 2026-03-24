
<script setup>
   import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";
   import avatarImg from "@/assets/images/avatar-example.jpg";

   const { width: windowWidth } = useWindowSize();

   const teamTotals = [
      {
         value: 20,
         label: 'Fakturor',
         icon: 'custom-facture',
         iconClass: 'team-stat-card__icon--mint',
      },
      {
         value: 20,
         label: 'Swish',
         icon: 'custom-swish',
         iconClass: 'team-stat-card__icon--cyan',
      },
      {
         value: 20,
         label: 'Avtal',
         icon: 'custom-contract',
         iconClass: 'team-stat-card__icon--neutral',
      },
   ];

   const teamMembers = [
      {
         id: 1,
         name: 'Förnamn och efternamn',
         email: 'Lorem Ipsum',
         invoices: 3,
         swish: 22,
         agreements: 30,
         avatar: avatarImg,
      },
      {
         id: 2,
         name: 'Förnamn och efternamn',
         email: 'Lorem Ipsum',
         invoices: 6,
         swish: 4,
         agreements: 3,
         avatar: avatarImg,
      },
      {
         id: 3,
         name: 'Förnamn och efternamn',
         email: 'Lorem Ipsum',
         invoices: 2,
         swish: 8,
         agreements: 6,
         avatar: avatarImg,
      },
      {
         id: 4,
         name: 'Förnamn och efternamn',
         email: 'Lorem Ipsum',
         invoices: 1,
         swish: 12,
         agreements: 8,
         avatar: avatarImg,
      },
      {
         id: 5,
         name: 'Förnamn och efternamn',
         email: 'Lorem Ipsum',
         invoices: 0,
         swish: 3,
         agreements: 1,
         avatar: avatarImg,
      },
   ];

   const truncateText = (text, length = 15) => {
      if (text && text.length > length) {
         return text.substring(0, length) + "...";
      }
      return text;
   };

   // 👉 Computing pagination data
   const paginationData = computed(() => {
      const totalMembers = teamMembers.length;

      if (!totalMembers)
         return 'Visar 0 av 0 teammedlemmar';

      return `Visar ${totalMembers} av ${totalMembers} teammedlemmar`;
   });
</script>

<template>
   <VCard title="" class="card-dashboard border-team">
      <VCardTitle 
         class="title-box"
         :class="windowWidth < 1024 ? 'flex-row align-center' : ''"
      >
         <div class="title-text">Teamstatistik</div>

         <VBtn class="btn-white-2 px-3 h-40">
            <VIcon icon="custom-filter" size="24" />
            <span class="d-none d-md-block">Filtrera efter datum</span>
         </VBtn>
      </VCardTitle>
      <VCardText class="px-0 py-0">
         <div class="team-stats-panel">
            <div class="team-stats-heading">Företagets total</div>

            <div class="team-stats-grid">
               <article
                  v-for="item in teamTotals"
                  :key="item.label"
                  class="team-stat-card"
               >
                  <div :class="['team-stat-card__icon', item.iconClass]">
                     <VIcon :icon="item.icon" size="16" />
                  </div>

                  <div class="team-stat-card__content">
                     <div class="team-stat-card__value">{{ item.value }}</div>
                     <div class="team-stat-card__label">{{ item.label }}</div>
                  </div>
               </article>
            </div>
         </div>
      </VCardText>
      <VCardTitle 
         class="title-box border-none"
         :class="windowWidth < 1024 ? 'flex-row align-center' : ''"
      >
         <div class="title-text">Individuell prestation</div>

         <VBtn class="btn-ghost px-3 h-40">
            <VIcon icon="custom-export" size="24" />
            <span class="d-none d-md-block">Exportera</span>
         </VBtn>
      </VCardTitle>
      <VCardText class="pb-0">
         <VTable
            v-if="!$vuetify.display.mdAndDown"
            v-show="teamMembers.length"
            class="pb-6 text-no-wrap">
            <thead>
               <tr>
                  <th scope="col">Skapad Av</th>
                  <th scope="col" class="text-center">Fakturor</th>
                  <th scope="col" class="text-center">Swish</th>
                  <th scope="col" class="text-center">Avtal</th>
               </tr>
            </thead>

            <tbody>
               <tr v-for="member in teamMembers" :key="member.id">
                  <td style="width: 1%; white-space: nowrap">
                     <div class="d-flex align-center gap-x-1">
                        <VAvatar
                           variant="outlined"
                           size="38"
                        >
                           <VImg
                              v-if="member.avatar"
                              style="border-radius: 50%"
                              :src="member.avatar"
                           />
                           <PresetAvatarImage
                           v-else
                           :avatar-id="member.user_detail?.avatar_id"
                           />
                        </VAvatar>
                        <div class="d-flex flex-column">
                           <span class="font-weight-medium">
                           {{ member.name }} {{ member.last_name ?? "" }}
                           </span>
                           <span class="text-sm text-disabled">
                           <VTooltip 
                              v-if="member.email && member.email.length > 20"
                              location="bottom">
                              <template #activator="{ props }">
                                 <span v-bind="props" class="cursor-pointer">
                                 {{ truncateText(member.email, 20) }}
                                 </span>
                              </template>
                              <span>{{ member.email }}</span>
                           </VTooltip>
                           <span class="text-sm text-disabled"v-else>{{ member.email }}</span>
                           </span>
                        </div>
                     </div>
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
               </tr>
            </tbody>
         </VTable>
      </VCardText>

      <VExpansionPanels
        class="expansion-panels pb-4 px-4"
        v-if="teamMembers.length && $vuetify.display.mdAndDown"
      >
        <VExpansionPanel v-for="member in teamMembers" :key="member.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >
            <span class="order-id">
               <VAvatar
                  variant="outlined"
                  size="38"
               >
                  <VImg
                     v-if="member.avatar"
                     style="border-radius: 50%"
                     :src="member.avatar"
                  />
                  <PresetAvatarImage
                  v-else
                  :avatar-id="member.user_detail?.avatar_id"
                  />
               </VAvatar>
            </span>
            <div class="order-title-box">
              <span class="title-panel">{{ member.name }} {{ member.last_name ?? "" }}</span>
              <div class="title-organization">
                {{ truncateText(member.email, 20) }}
              </div>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
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
            <div>
              <div class="expansion-panel-item-label">Avtal:</div>
              <div class="expansion-panel-item-value">
                {{ member.agreements ?? "" }}
              </div>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="teamMembers.length"
        :class="windowWidth < 1024 ? 'flex-column px-4' : 'px-6'"
        class="d-flex align-center gap-4 pt-0"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <VBtn class="btn-light px-3 h-40" :class="windowWidth < 1024 ? 'w-100' : ''">
           Visa alla rapporter
         </VBtn>
      </VCardText>

   </VCard>
</template>

<style lang="scss" scoped>

   .border-team {
      border: 1px solid #E7E7E7 !important;
   }

   .team-stats-panel {
      padding: 24px;
      background: #D8FFE4;
   }

   .team-stats-heading {
      margin-bottom: 16px;
      color: #454545;
      font-weight: 400;
      font-size: 16px;
      line-height: 100%;
      letter-spacing: 0px;
      vertical-align: middle;
   }

   .team-stats-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
   }

   .team-stat-card {
      display: flex;
      align-items: center;
      gap: 8px;
      min-height: 72px;
      padding: 16px;
      border-radius: 8px;
      background-color: #ffffff;
   }

   .team-stat-card__icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 8px;
      color: #1C2925;
      flex: 0 0 auto;
   }

   .team-stat-card__icon--mint {
      background-color: #D8FFE4;
   }

   .team-stat-card__icon--cyan {
      background-color: #C0FEFF;
   }

   .team-stat-card__icon--neutral {
      background-color: #F6F6F6;
   }

   .team-stat-card__content {
      min-width: 0;
   }

   .team-stat-card__value {
      color: #454545;
      font-weight: 600;
      font-size: 16px;
      line-height: 16px;
      letter-spacing: 0px;
      vertical-align: middle;
   }

   .team-stat-card__label {
      color: #454545;
      font-weight: 400;
      font-size: 10px;
      line-height: 16px;
      letter-spacing: 0px;
      vertical-align: middle;
   }

   @media (max-width: 1023px) {
      .team-stats-grid {
         grid-template-columns: 1fr;
      }
   }

   @media (max-width: 767px) {

      .title-panel {
         color: #6E9383 !important;
      }

      .team-stats-panel {
         padding: 16px;
      }

      .team-stat-card {
         min-height: 72px;
         padding: 14px 16px;
      }
   }
</style>