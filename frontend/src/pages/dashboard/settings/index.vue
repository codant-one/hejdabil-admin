<script setup>

import settingsNavItems from "@/navigation/settings";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import { canWithPlan } from "@layouts/plugins/casl";

const { width: windowWidth } = useWindowSize()
const isRequestOngoing = ref(true);
const shouldRenderContent = ref(false);
const router = useRouter()
const sectionEl = ref(null)

const settingsNavItemsWithAccess = computed(() => {
  return settingsNavItems.map(item => ({
    ...item,
    hasAccess: canWithPlan(item.action, item.subject),
  }))
})

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
            v-for="item in settingsNavItemsWithAccess"
            :key="item.to"
            class="settings-nav-item"
          >
            <RouterLink
              v-if="item.hasAccess"
              :to="{ name: item.to }"
              class="settings-nav-link"
            >
              <VIcon
                :icon="item.icon?.icon"
                size="24"
                class="settings-nav-icon"
                color="#1C2925"
              />
              <span 
                class="settings-nav-title"
              >
                {{ item.title }}
              </span>
            </RouterLink>

            <div
              v-else
              class="settings-nav-link settings-nav-link-disabled"
            >
              <VIcon
                :icon="item.icon?.icon"
                size="24"
                class="settings-nav-icon"
                color="#BDD2C8"
              />
              <span class="settings-nav-title settings-nav-title-disabled">
                {{ item.title }}
              </span>
            </div>
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
    flex-shrink: 0;
  }

  .settings-nav-link-disabled {
    cursor: not-allowed;
    opacity: 0.7;
  }

  .settings-nav-link-disabled:hover {
    background: #FFFFFF;
  }

  .settings-nav-title {
    font-weight: 500;
    font-size: 16px;
    line-height: 16px;
    letter-spacing: 0;
    color: #1C2925;
  }

  .settings-nav-title-disabled {
    color: #BDD2C8;
  }

</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
