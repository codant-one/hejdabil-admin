<script setup>

import Congratulations from "@/components/dashboard/Congratulations.vue";

const userDataJ = ref('')
const name = ref('')

const isRequestOngoing = ref(false)
const sectionEl = ref(null)

watchEffect(fetchData)

async function fetchData() {

  isRequestOngoing.value = true

  const userData = localStorage.getItem('user_data')
    
  userDataJ.value = JSON.parse(userData)
  name.value = userDataJ.value?.name + " " + userDataJ.value?.last_name

  isRequestOngoing.value = false
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value
  if (!el) return

  const rect = el.getBoundingClientRect()
  const remaining = Math.max(0, window.innerHeight - rect.top - 25)
  el.style.minHeight = `${remaining}px`
}

onMounted(() => {
  resizeSectionToRemainingViewport()
  window.addEventListener('resize', resizeSectionToRemainingViewport)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
})

</script>

<template>
  <section class="page-section" ref="sectionEl">
    <VDialog
      v-model="isRequestOngoing"
      width="auto"
      persistent>
      <VProgressCircular
        indeterminate
        color="primary"
        class="mb-0"/>
    </VDialog>

    <VCard title="" class="card-fill">
      <VRow class="py-6 px-md-6 px-2">
        <VCol
          cols="12"
          md="8"

        >
          <div class="pe-3">
            <h3 class="text-h3 text-high-emphasis mb-1">
              Välkommen tillbaka, <span class="font-weight-medium"> {{ name }} 👋🏻 </span>
            </h3>

            <div
              class="mb-7 text-wrap"
              style="max-inline-size: 800px;"
            >
              I den här panelen hittar du relevant information om bilförsäljning.
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
    </VCard>
  </section>
</template>

<style lang="scss" scoped>
  .card-list {
    --v-card-list-gap: 30px;
  }
  
  .page-section {
    display: flex;
    flex-direction: column;
  }

  .card-fill {
    flex: 1 1 auto;
  }

  @media (max-width: 991px) {
    .card-fill {
      border-radius: 0 !important;
    }
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
