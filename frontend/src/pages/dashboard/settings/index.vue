<script setup>

import settingsNavItems from "@/navigation/settings";
import { can } from "@layouts/plugins/casl";

const { width: windowWidth } = useWindowSize()
const router = useRouter()
const sectionEl = ref(null)

const visibleSettingsNavItems = computed(() => settingsNavItems.filter(item => can(item.action, item.subject)))

watch(windowWidth, width => {
  if (width >= 1024) {
    router.replace('/dashboard/settings/profile')
  }
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
    <VCard class="card-fill">
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
