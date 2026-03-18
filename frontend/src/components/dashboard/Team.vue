
<script setup>
   const { width: windowWidth } = useWindowSize();
</script>

<template>
   <VCard title="" class="card-dashboard">
      <VCardTitle 
         class="title-box"
         :class="windowWidth < 1024 ? 'flex-row' : ''"
      >
         <div class="title-text">Teamstatistik</div>

         <VBtn
               class="btn-white-2 px-3 h-40"
            >
               <VIcon icon="custom-filter" size="24" />
               <span class="d-none d-md-block">Filtrera efter datum</span>
            </VBtn>
      </VCardTitle>
      <VCardText class="pt-5">
         Aqui va la informacion de los miembros del equipo
      </VCardText>
   </VCard>
</template>