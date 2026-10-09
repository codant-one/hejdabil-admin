<script setup>

import { canWithPlan } from "@/@layouts/plugins/casl";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import NavBarNotifications from '@/layouts/components/NavBarNotifications.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'
import router from '@/router'
import { useAuthStores } from '@/stores/useAuth'
import { useSuppliersStores } from '@/stores/useSuppliers'
import logo from '@images/logos/bilflogg-logo.svg'

const suppliersStores = useSuppliersStores()
const authStores = useAuthStores()
const { width: windowWidth } = useWindowSize()
const route = useRoute()
const emitter = inject('emitter')
const vm = getCurrentInstance()
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

const canShowSwishaButton = computed(() => {
  const hasPermission = vm?.proxy?.$can ? vm.proxy.$can('create', 'payouts') : true

  if (!hasPermission)
    return false

  if (!userData.value)
    return false

  const userRole = userData.value.roles?.[0]?.name

  if (userRole !== 'Supplier' && userRole !== 'User')
    return false

  if (userRole === 'Supplier')
    return userData.value.supplier?.is_payout === 1

  return true
})

const redirectTo = path => {
  router.push({
    name: path,
  })
}

const redirectToPayoutsAndOpenDialog = () => {
  if (route.name === 'dashboard-admin-payouts') {
    emitter.emit('open-payout-dialog')

    return
  }

  router.push({
    name: 'dashboard-admin-payouts',
    query: { open_payout: 'true' },
  })
}

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
  const deletionScheduledAt = supplierData.value?.deletion_scheduled_at

  if (!deletionScheduledAt)
    return null

  const scheduledDate = new Date(deletionScheduledAt)

  if (Number.isNaN(scheduledDate.getTime()))
    return null

  const msLeft = scheduledDate.getTime() - Date.now()

  if (msLeft <= 0)
    return 0

  return Math.ceil(msLeft / (1000 * 60 * 60 * 24))
})
</script>

<template>
  <div 
    class="d-flex justify-between align-center flex-wrap navigation-bar" 
    :class="showDeletionAlert ? 'gap-y-3 pb-0': 'mb-6 gap-y-4'">
    <div
      class="d-flex align-center flex-0 cursor-pointer"
      :class="windowWidth < 1024 ? 'justify-center' : ''"
      @click="redirectTo('dashboard-panel')"
    >
      <img
        :src="logo"
        :width="windowWidth < 1024 ? 95 : 121"
        alt="Bilflogg"
      >
    </div>

    <div class="d-flex align-center" :class="windowWidth < 1024 ? 'gap-1' : 'gap-2'">
      <VBtn
        v-if="canWithPlan('create', 'agreements')"
        class="btn-blue px-6"
        :class="windowWidth < 1024 ? 'd-none' : ''"
        @click="redirectTo('dashboard-admin-agreements-purchase')"
      >
        Köp
        <VIcon
          icon="custom-car-close"
          size="24"
        />
      </VBtn>
      <VBtn
        v-if="canWithPlan('create', 'agreements')"
        class="btn-green px-6"
        :class="windowWidth < 1024 ? 'd-none' : ''"
        @click="redirectTo('dashboard-admin-agreements-sales')"
      >
        Sälj
        <VIcon
          icon="custom-car-open"
          size="24"
        />
      </VBtn>

      <VBtn
        v-if="canShowSwishaButton"
        class="btn-gradient-2 px-4"
        :class="windowWidth < 1024 ? 'd-none' : ''"
        @click="redirectToPayoutsAndOpenDialog"
      >
        <VIcon
          icon="custom-swish-outlined"
          size="24"
        />
        Swisha
      </VBtn>

      <VBtn
        class="btn-white-2"
        :class="windowWidth < 1024 ? 'd-none' : 'px-4'"
        :to="{ name: 'dashboard-activities' }"
      >
        <VIcon icon="custom-log-outlined" size="24" />
        Din logg
      </VBtn>

      <NavBarNotifications />

      <VBtn
        variant="flat"
        :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"
        class="btn-white-3"
        height="48"
        width="48"
        :to="{ name: 'dashboard-settings' }"
      >
        <VIcon
          icon="custom-settings"
          size="24"
        />
      </VBtn>
      <UserProfile :can-show-swisha-button="canShowSwishaButton" />
    </div>

    <div
    v-if="showDeletionAlert"
    class="nav-delete-account d-flex"
    :class="windowWidth < 1024 ? 'flex-column gap-2': 'flex-row gap-6'"
  >
    <div
      class="d-flex align-center gap-6"
      :class="windowWidth < 1024 ? '' : 'nav-delete-account-days'"
    >
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
      :loading="isCancelDeletionRequestOngoing"
      :disabled="isCancelDeletionRequestOngoing"
      @click="cancelDeletionRequest"
    >
      Avbryt raderingen
    </VBtn>
  </div>
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

  <LoadingOverlay :is-loading="isCancelDeletionRequestOngoing" />
</template>

<style>
.nav-delete-account {
  align-items: center;
  padding: 16px 24px;
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
