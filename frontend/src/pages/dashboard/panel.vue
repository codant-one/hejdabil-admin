<script setup>

import Congratulations from "@/components/dashboard/Congratulations.vue";

const userDataJ = ref('')
const name = ref('')

const isRequestOngoing = ref(false)

watchEffect(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  const userData = localStorage.getItem('user_data')
    
  userDataJ.value = JSON.parse(userData)
  name.value = userDataJ.value.name + " " + userDataJ.value.last_name

  isRequestOngoing.value = false
}

</script>

<template>
  <section>
    <VDialog
      v-model="isRequestOngoing"
      width="300"
      persistent>
          
      <VCard
        color="primary"
        width="300">
            
        <VCardText class="pt-3">
          Loading

          <VProgressLinear
            indeterminate
            color="white"
            class="mb-0"/>
        </VCardText>
      </VCard>
    </VDialog>

    <VRow class="py-6 px-md-6 px-2">
      <VCol
        cols="12"
        md="8"

      >
        <div class="pe-3">
          <h3 class="text-h3 text-high-emphasis mb-1">
            Welcome back, <span class="font-weight-medium"> {{ name }} 👋🏻 </span>
          </h3>

          <div
            class="mb-7 text-wrap"
            style="max-inline-size: 800px;"
          >
            In this panel you will find relevant information about car sales.
          </div>

        </div>
      </VCol>
      <!-- <VCol
        cols="12"
        md="5"
        lg="4"
      >
        <Congratulations/>
      </VCol> -->
    </VRow>

  </section>
</template>

<style lang="scss" scoped>
  .card-list {
    --v-card-list-gap: 30px;
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
