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
              Avtal
            </span>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Avtalsutseende</span>
                <span class="text-settings">
                  Välj en design för avtal och prisförslag. Förhandsgranska hur dokumentet visas för kunden.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              aaa
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Standardvillkor</span>
                <span class="text-settings">
                  Ställ in förvalda villkor för dina avtal. Fylls i automatiskt och kan justeras vid behov.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              aaa
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Påminnelser för signering</span>
                <span class="text-settings">
                  Skicka påminnelser till kunder som ännu inte har signerat sina avtal.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              aaa
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Leveransmetod för signering</span>
                <span class="text-settings">
                  Välj hur avtal skickas till kunder för digital signering.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              aaa
            </div>
          </div>
        </VCardText>
      </VCard>
    </section>
</template>

<style lang="scss">
  
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
