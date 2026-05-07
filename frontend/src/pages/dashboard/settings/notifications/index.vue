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
              Meddelanden
            </span>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout border-bottom-settings pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Allmänt</span>
                <span class="text-settings">
                  Hantera grundläggande notiser för ditt konto.
                </span>
              </div>
            </div>
            <div class="settings-layout__content d-flex flex-column gap-6">
              <div class="d-flex gap-4 align-start">
                <VSwitch
                  class="reminders-switch"
                  hide-details
                  inset
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">Ljud för aviseringar</span>
                      <span class="reminders-description">
                       Spela upp ett ljud vid nya händelser i systemet.
                      </span>
                    </div>
                  </template>
                </VSwitch>             
              </div>

              <div class="d-flex gap-4 align-start">
                <VSwitch
                  class="reminders-switch"
                  hide-details
                  inset
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">E-postnotiser</span>
                      <span class="reminders-description">
                       Få uppdateringar skickade till din e-post.
                      </span>
                    </div>
                  </template>
                </VSwitch>             
              </div>

              <div class="d-flex gap-4 align-start">
                <VSwitch
                  class="reminders-switch"
                  hide-details
                  inset
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">Uppgifter och påminnelser</span>
                      <span class="reminders-description">
                       Få påminnelser om aktiviteter som behöver göras.
                      </span>
                    </div>
                  </template>
                </VSwitch>             
              </div>
            </div>
          </div>
        </VCardText>

        <VCardText class="pb-0">
          <div class="settings-layout pb-4">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Avtal & dokument</span>
                <span class="text-settings">
                  Hantera notiser för avtal & dokument. 
                </span>
              </div>
            </div>
            <div class="settings-layout__content d-flex flex-column gap-6">
              <div class="d-flex gap-4 align-start">
                <VSwitch
                  class="reminders-switch"
                  hide-details
                  inset
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">Dokument signerade</span>
                      <span class="reminders-description">
                        Få notis när ett dokument har signerats.
                      </span>
                    </div>
                  </template>
                </VSwitch>             
              </div>

              <div class="d-flex gap-4 align-start">
                <VSwitch
                  class="reminders-switch"
                  hide-details
                  inset
                >
                  <template v-slot:label>
                    <div class="d-flex flex-column">
                      <span class="reminders-title">Avtal signerade</span>
                      <span class="reminders-description">
                        Få notis när ett avtal har signerats.
                      </span>
                    </div>
                  </template>
                </VSwitch>             
              </div>

              <!-- 👉 Form Actions -->
              <div 
                class="d-flex justify-start gap-3 flex-wrap dialog-actions"
                :class="windowWidth < 1024 ? 'pb-4' : ''"
              >
              
                <VBtn 
                  type="submit" 
                  class="btn-gradient"
                  :class="windowWidth < 1024 ? 'w-100' : 'w-25'"
                >
                  Spara
                </VBtn>
              </div>
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
