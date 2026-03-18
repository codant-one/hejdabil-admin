<script setup>
   const activeTab = ref(0);
</script>

<template>
  <VCard title="" class="card-dashboard">
    <VCardTitle class="title-box">
      <div class="title-text">Senaste aktivitet</div>

      <VTabs
        v-model="activeTab"
        :show-arrows="false"
        class="dashboard-tabs-1"
      >
        <VTab :value="0">
          <span>Meddelanden</span>
        </VTab>
        <VTab :value="1">
          <span>Aktiviteter</span>
        </VTab>
      </VTabs>
    </VCardTitle>

    <VCardText class="pt-5">
      <VWindow v-model="activeTab">
         <VWindowItem :value="0" class="px-md-0">
            Notificaciones
         </VWindowItem>
         <VWindowItem :value="1" class="px-md-0">
            Actividades
         </VWindowItem>
      </VWindow>
    </VCardText>
  </VCard>
</template>
