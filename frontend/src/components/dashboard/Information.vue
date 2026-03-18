
<script setup>
   const { width: windowWidth } = useWindowSize();
</script>

<template>
   <VCard title="" class="card-dashboard">
      <VCardTitle 
         class="title-box"
         :class="windowWidth < 1024 ? 'flex-row' : ''"
      >
         <div class="title-text">Mina uppgifter</div>

         <VBtn
            class="btn-white-2 px-3 h-40"
         >
            <VIcon icon="custom-eye" size="24" />
            Visa avslutade
         </VBtn>

      </VCardTitle>
      <VCardText class="pt-5">
         aqui van a ir las notas
      </VCardText>
   </VCard>
</template>