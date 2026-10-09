<script setup>

import { canWithPlan } from "@/@layouts/plugins/casl";
import { useThemeConfig } from "@core/composable/useThemeConfig";
import { useSuppliersStores } from "@/stores/useSuppliers";
import { useAuthStores } from "@/stores/useAuth";
import MobileBottomBar from "@/layouts/components/MobileBottomBar.vue";
import navItems from "@/navigation/vertical";
import settingsNavItems from "@/navigation/settings";
import router from "@/router";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

// Components
import UserProfile from "@/layouts/components/UserProfile.vue";

// @layouts plugin
import { VerticalNavLayout } from "@layouts";
import { VNodeRenderer } from "@layouts/components/VNodeRenderer";
import { themeConfig } from "@themeConfig";
import NavBarNotifications from "@/layouts/components/NavBarNotifications.vue";

const suppliersStores = useSuppliersStores()
const authStores = useAuthStores()

const { appRouteTransition, isLessThanOverlayNavBreakpoint } = useThemeConfig();
const { width: windowWidth } = useWindowSize();
const route = useRoute();
const emitter = inject('emitter');
const vm = getCurrentInstance();
const readUserDataFromStorage = () => JSON.parse(localStorage.getItem('user_data') || 'null')
const userData = ref(readUserDataFromStorage())
const role = computed(() => userData.value?.roles?.[0]?.name ?? '')
const isSupplier = computed(() => role.value === 'Supplier')
const supplierData = computed(() => isSupplier.value ? userData.value?.supplier ?? null : null)
const isCancelDeletionRequestOngoing = ref(false)
const isCancelDeletionSuccessDialogVisible = ref(false)
const isCancelDeletionErrorDialogVisible = ref(false)
const cancelDeletionErrorText = ref('Ett serverfel uppstod. Försök igen.')

const syncUserData = event => {
  if (event?.detail)
    userData.value = event.detail
  else
    userData.value = readUserDataFromStorage()
}

onMounted(() => {
  window.addEventListener('user-data-updated', syncUserData)
  window.addEventListener('storage', syncUserData)
})

onBeforeUnmount(() => {
  window.removeEventListener('user-data-updated', syncUserData)
  window.removeEventListener('storage', syncUserData)
})

const isSettingsRoute = computed(() => route.path.startsWith("/dashboard/settings"));
const settingsButtonStyle = computed(() => (
  isSettingsRoute.value
    ? "box-shadow: 0px 0px 40px 0px rgba(0, 0, 0, 0.15) !important;"
    : undefined
));
const canShowSwishaButton = computed(() => {
  const hasPermission = vm?.proxy?.$can ? vm.proxy.$can('create', 'payouts') : true;

  if (!hasPermission)
    return false;

  if (!userData.value)
    return false;

  const userRole = userData.value.roles?.[0]?.name;

  if (userRole !== 'Supplier' && userRole !== 'User')
    return false;

  if (userRole === 'Supplier')
    return userData.value.supplier?.is_payout === 1;

  return true;
});

const redirectTo = (path) => {
  router.push({
    name: path,
  });
};

const logButtonStyle = computed(() => (
  isSettingsRoute.value
    ? 'box-shadow: 0px 0px 40px 0px rgba(0, 0, 0, 0.15) !important;'
    : undefined
));

const redirectToPayoutsAndOpenDialog = () => {
  if (route.name === 'dashboard-admin-payouts') {
    emitter.emit('open-payout-dialog');

    return;
  }

  router.push({
    name: 'dashboard-admin-payouts',
    query: { open_payout: 'true' },
  });
};

const syncUserDataAfterCancelDeletion = async response => {
  const responseUserData = response?.data?.data?.user_data ?? response?.data?.user_data ?? null

  if (responseUserData) {
    localStorage.setItem('user_data', JSON.stringify(responseUserData))
    userData.value = responseUserData

    return
  }

  const currentUserData = readUserDataFromStorage()

  if (!currentUserData?.hash)
    return

  const { user_data } = await authStores.me(currentUserData)

  if (!user_data)
    return

  localStorage.setItem('user_data', JSON.stringify(user_data))
  userData.value = user_data
}

const cancelDeletionRequest = async () => {
  const supplierId = supplierData.value?.id

  if (!supplierId)
    return

  isCancelDeletionRequestOngoing.value = true
  cancelDeletionErrorText.value = 'Ett serverfel uppstod. Försök igen.'

  try {
    const response = await suppliersStores.cancelDeletion(supplierId)

    await syncUserDataAfterCancelDeletion(response)
    isCancelDeletionSuccessDialogVisible.value = true
  } catch (error) {
    const errorMessage = error?.response?.data?.message
      ?? Object.values(error?.response?.data?.errors ?? {}).flat().join(' ')
      ?? error?.message

    if (errorMessage)
      cancelDeletionErrorText.value = errorMessage

    isCancelDeletionErrorDialogVisible.value = true
  } finally {
    isCancelDeletionRequestOngoing.value = false
  }
}

const showDeletionAlert = computed(() => Boolean(supplierData.value?.deletion_scheduled_at))
const deletionDaysLeft = computed(() => {
  const deletionScheduledAt = supplierData.value?.deletion_scheduled_at;

  if (!deletionScheduledAt)
    return null;

  const scheduledDate = new Date(deletionScheduledAt);

  if (Number.isNaN(scheduledDate.getTime()))
    return null;

  const msLeft = scheduledDate.getTime() - Date.now();

  if (msLeft <= 0)
    return 0;

  return Math.ceil(msLeft / (1000 * 60 * 60 * 24));
});
</script>

<template>
  <VerticalNavLayout
    :nav-items="isSettingsRoute ? settingsNavItems : navItems"
    :vertical-nav-attrs="{ showDeletionAlert }"
    :class="{ 'has-delete-account-alert': showDeletionAlert }"
  >
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <RouterLink to="/" :class="windowWidth < 1024 ? 'd-flex' : 'd-none'" class="align-center md-ms-3 header-logo">
          <VNodeRenderer :nodes="themeConfig.app.logoFull" />
        </RouterLink>

        <VSpacer />

        <div class="d-flex align-center" :class="windowWidth < 1024 ? 'gap-1' : 'gap-2'">
          <VBtn
            v-if="canWithPlan('create', 'agreements')"
            class="btn-blue px-6"
            :class="windowWidth < 1024 ? 'd-none' : ''"
            @click="redirectTo('dashboard-admin-agreements-purchase')"
          >
            Köp
            <VIcon icon="custom-car-close" size="24" />
          </VBtn>
          <VBtn
            v-if="canWithPlan('create', 'agreements')"
            class="btn-green px-6"
            :class="windowWidth < 1024 ? 'd-none' : ''"
            @click="redirectTo('dashboard-admin-agreements-sales')"
          >
            Sälj
            <VIcon icon="custom-car-open" size="24" />
          </VBtn>
            
          <VBtn
            v-if="canShowSwishaButton"
            class="btn-gradient-2 px-4"
            :class="windowWidth < 1024 ? 'd-none' : ''"
            @click="redirectToPayoutsAndOpenDialog"
          >
            <VIcon icon="custom-swish-outlined" size="24" />
            Swisha
          </VBtn>

          <VBtn
            class="btn-white-2"
            :class="windowWidth < 1024 ? 'd-none' : 'px-4'"
            :to="{ name: 'dashboard-activities' }"
            :style="logButtonStyle"
          >
            <VIcon icon="custom-log-outlined" size="24" />
            Din logg
          </VBtn>

          <NavBarNotifications />

          <VBtn
            variant="flat"
            :class="[
              windowWidth < 1024 ? 'd-none' : 'd-flex',
              { 'shadow-button-settings': isSettingsRoute },
            ]"
            :style="settingsButtonStyle"
            class="btn-white-3"
            height="48"
            width="48"
            :to="{ name: 'dashboard-settings' }"
          >
            <VIcon icon="custom-settings" size="24" />
          </VBtn>
          <UserProfile :can-show-swisha-button="canShowSwishaButton" />
        </div>
      </div>

      <div 
        v-if="showDeletionAlert"
        class="nav-delete-account d-flex mt-3"
        :class="windowWidth < 1024 ? 'flex-column gap-2': 'flex-row gap-6'"
      >
        <div 
          class="d-flex align-center gap-6"
          :class="windowWidth < 1024 ? '': 'nav-delete-account-days'">
          <VIcon icon="custom-warning-danger" size="32" />
          <div class="d-flex gap-2">
            <span class="nav-delete-account-number">{{ deletionDaysLeft ?? '-' }}</span>
            <span class="nav-delete-account-text">Dagar kvar</span>
          </div>
        </div>

        <div class="d-flex flex-column">
          <span class="nav-delete-account-text" :class="windowWidth < 1024 ? 'text-center': ''">
            Ditt konto raderas permanent
          </span>
          <span class="nav-delete-account-text-simple" :class="windowWidth < 1024 ? 'text-center': ''">
            Ladda ner din information under tiden som återstår — gå till respektive sektion för att exportera dina uppgifter. 
            Du kan fortsätta använda Bilflogg som vanligt under uppsägningstiden.
          </span>
        </div>

        <VBtn
          class="btn-light px-4"
          :class="windowWidth < 1024 ? 'w-100': ''"
          @click="cancelDeletionRequest"
        >
          Avbryt raderingen
        </VBtn>
      </div>

      <VDialog
        v-model="isCancelDeletionSuccessDialogVisible"
        persistent
        class="action-dialog dialog-big-icon"
      >
        <VCard>
          <VCardText class="dialog-title-box big-icon justify-center pb-0">
            <VIcon size="72" icon="custom-f-crown" />
          </VCardText>
          <VCardText class="dialog-title-box justify-center">
            <div class="dialog-title">
              Vad kul att du stannar!
            </div>
          </VCardText>
          <VCardText class="dialog-text text-center">
            Din begäran om kontoradering har avbrutits. Ditt konto och abonnemang fortsätter som vanligt och du kan fortsätta använda Bilflogg precis som tidigare.
          </VCardText>
          <VCardText class="d-flex justify-center dialog-actions">
            <VBtn class="btn-gradient" @click="isCancelDeletionSuccessDialogVisible = false">
              Fortsätt till Bilflogg
            </VBtn>
          </VCardText>
        </VCard>
      </VDialog>

      <VDialog
        v-model="isCancelDeletionErrorDialogVisible"
        persistent
        class="action-dialog dialog-big-icon"
      >
        <VCard>
          <VCardText class="dialog-title-box big-icon justify-center pb-0">
            <VIcon size="72" icon="custom-f-cancel" />
          </VCardText>
          <VCardText class="dialog-title-box justify-center">
            <div class="dialog-title">
              Ett fel inträffade
            </div>
          </VCardText>
          <VCardText class="dialog-text text-center">
            {{ cancelDeletionErrorText }}
          </VCardText>
          <VCardText class="d-flex justify-center dialog-actions">
            <VBtn class="btn-light" @click="isCancelDeletionErrorDialogVisible = false">
              Stäng
            </VBtn>
          </VCardText>
        </VCard>
      </VDialog>
    </template>

    <!-- 👉 Pages -->
    <RouterView v-slot="{ Component }">
      <Transition :name="appRouteTransition" mode="out-in">
        <Component :is="Component" />
      </Transition>
    </RouterView>

    <!-- 👉 Mobile Bottom Bar -->
    <MobileBottomBar :nav-items="navItems" />

    <LoadingOverlay :is-loading="isCancelDeletionRequestOngoing" />
  </VerticalNavLayout>  
</template>

<style>

  body .v-btn.shadow-button-settings,
  body .v-btn.shadow-button-settings:hover,
  body .v-btn.shadow-button-settings:focus,
  body .v-btn.shadow-button-settings:active {
    box-shadow: 0px 0px 40px 0px rgba(0, 0, 0, 0.15) !important;
  }
  
  .sticky-container {
    position: sticky;
    top: 2.5%;      
    z-index: 9999;
  }

  .buttons-center {
    position: absolute;
    left: 50%;                 
    transform: translateX(-50%);
  }

  .nav-delete-account {
      align-items: center;
      padding: 16px 24px;
      width: calc(100% + 48px);
      margin-inline: -24px;
      border-left: 3px solid #CC3E3F;
      border-image-source: linear-gradient(0deg, #CC3E3F, #CC3E3F);
      border-image-slice: 1;
      background: #FFF1F1;
  }

  .nav-delete-account-days {
    border-right: 1px solid #CC3E3F;
    border-image-source: linear-gradient(0deg, #CC3E3F, #CC3E3F);
    border-image-slice: 1;
    background: #FFF1F1;
    width: 226px;
    height: 50px;
  }

  .nav-delete-account-number {
    font-weight: 600;
    font-size: 40px;
    line-height: 16px;
    letter-spacing: 0;
    color: #9B191B;
  }

  .nav-delete-account-text {
    font-weight: 600;
    font-size: 16px;
    line-height: 16px;
    letter-spacing: 0;
    color: #9B191B;
  }

  .nav-delete-account-text-simple {
    font-weight: 400;
    font-size: 14px;
    line-height: 16px;
    letter-spacing: 0;
    color: #9B191B;
  }

</style>
<style lang="scss" scoped>
  :deep(
    .layout-wrapper.layout-nav-type-vertical
    .layout-navbar
     .navbar-content-container
  ) {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;

    -webkit-backdrop-filter: none !important;
    backdrop-filter: none !important;
  }

  :deep(.layout-navbar) {
    background: transparent !important;
    box-shadow: none !important;
  }

  :deep(.btn-custom) {
    border-radius: 48px !important;
    padding-inline: 16px !important;
    text-transform: none;

    .v-btn__content {
      font-family: "DM Sans", sans-serif;
      font-weight: 500;
      font-size: 16px;
      line-height: 16px;
      letter-spacing: 0;
    }
  }

  :deep(.btn-custom-settings) {
    width: 48px !important;
    height: 48px !important;
    min-width: auto !important;
    border-radius: 50% !important;
    background-color: #fff !important;
    padding: 0 !important;
  }

  .navbar-actions-group {
    background-color: #ffffff;
    border-radius: 64px;
    padding-inline: 16px 8px;
    padding-block: 4px;
    gap: 8px;
  }
</style>
