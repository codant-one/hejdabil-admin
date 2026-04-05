<script setup>

import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const { width: windowWidth } = useWindowSize()
const sectionEl = ref(null)

const isRequestOngoing = ref(false);

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});
</script>

<template>
    <section class="page-section bg-white" ref="sectionEl">
      <LoadingOverlay :is-loading="isRequestOngoing" />
      <VCard class="card-fill">
        <VCardText class="pb-0" v-if="windowWidth < 1024">
          <div class="d-flex flex-column gap-4 flex-1">
            <VBtn
              class="btn-light"
              style="width: 120px;"
              :to="{ name: 'dashboard-settings' }"
            >
              <VIcon icon="custom-return" size="24" />
              Tillbaka
            </VBtn>

            <span class="title-settings pb-4 border-bottom-settings">
              Fakturor
            </span>
          </div>
        </VCardText>
      </VCard>
    </section>
</template>

<style lang="scss">

  .title-settings {
    font-weight: 700;
    font-size: 24px;
    line-height: 100%;
    letter-spacing: 0;
    color: #1C2925;
  }

  .border-bottom-settings {
    border-bottom: 1px solid #E7E7E7;
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
