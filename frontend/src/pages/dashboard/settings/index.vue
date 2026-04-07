<script setup>

import settingsNavItems from "@/navigation/settings";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import { can } from "@layouts/plugins/casl";

const { width: windowWidth } = useWindowSize()
const isRequestOngoing = ref(true);
const shouldRenderContent = ref(false);
const router = useRouter()
const sectionEl = ref(null)

const visibleSettingsNavItems = computed(() => settingsNavItems.filter(item => can(item.action, item.subject)))

watch(windowWidth, async width => {
  isRequestOngoing.value = true
  shouldRenderContent.value = false

  if (width >= 1024) {
    try {
      await router.replace('/dashboard/settings/profile')
      return
    } catch (error) {
      console.error(error)
    }
  }

  shouldRenderContent.value = true
  isRequestOngoing.value = false
}, { immediate: true })

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
    <VCard v-if="shouldRenderContent" class="card-fill">
      <VCardText class="pb-0">
        <div class="d-flex flex-column gap-4 flex-1">
          <VBtn
            class="btn-light"
            style="width: 120px;"
            :to="{ name: 'dashboard-panel' }"
          >
            <VIcon icon="custom-return" size="24" />
            Tillbaka
          </VBtn>

          <span class="title-settings">
            Inställningar
          </span>
        </div>
      </VCardText>
      <VCardText>
        <ul class="settings-nav-list">
          <li
            v-for="item in visibleSettingsNavItems"
            :key="item.to"
            class="settings-nav-item"
          >
            <RouterLink
              :to="{ name: item.to }"
              class="settings-nav-link"
            >
              <VIcon
                :icon="item.icon?.icon"
                size="24"
                class="settings-nav-icon"
              />
              <span class="settings-nav-title">
                {{ item.title }}
              </span>
            </RouterLink>
          </li>
        </ul>
      </VCardText>
    </VCard>
  </section>
</template>

<style lang="scss">

  .settings-nav-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 0;
    margin: 0;
    list-style: none;
  }

  .settings-nav-item {
    list-style: none;
  }

  .settings-nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 16px;
    border-radius: 8px;
    background: #FFFFFF;
    color: #454545;
    text-decoration: none;
    transition: background-color 0.2s ease;
  }

  .settings-nav-link:hover {
    background: #E7E7E7;
  }

  .settings-nav-icon {
    color: #454545 !important;
    flex-shrink: 0;
  }

  .settings-nav-title {
    font-weight: 500;
    font-size: 16px;
    line-height: 16px;
    letter-spacing: 0;
    color: #454545;
  }

</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
