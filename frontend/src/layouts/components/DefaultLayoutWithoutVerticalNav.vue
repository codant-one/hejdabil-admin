<script setup>

import NavBarNotifications from '@/layouts/components/NavBarNotifications.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'
import router from '@/router'
import logo from '@images/logos/billogg-logo.svg'

const { width: windowWidth } = useWindowSize()
const vm = getCurrentInstance()

const canShowSwishaButton = computed(() => {
  const hasPermission = vm?.proxy?.$can ? vm.proxy.$can('create', 'payouts') : true

  if (!hasPermission)
    return false

  const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
  if (!userData)
    return false

  const userRole = userData.roles?.[0]?.name

  if (userRole !== 'Supplier' && userRole !== 'User')
    return false

  if (userRole === 'Supplier')
    return userData.supplier?.is_payout === 1

  return true
})

const redirectTo = path => {
  router.push({
    name: path,
  })
}

const redirectToPayoutsAndOpenDialog = () => {
  router.push({
    name: 'dashboard-admin-payouts',
    query: { open_payout: 'true' },
  })
}
</script>

<template>
    <div class="d-flex justify-between align-center mb-6 flex-wrap gap-y-4 navigation-bar">
        <div
            class="d-flex align-center flex-0 cursor-pointer"
            :class="windowWidth < 1024 ? 'justify-center' : ''"
            @click="redirectTo('dashboard-panel')"
        >
            <img
                :src="logo"
                :width="windowWidth < 1024 ? 95 : 121"
                alt="Billogg"
            >
        </div>

        <div class="d-flex align-center" :class="windowWidth < 1024 ? 'gap-1' : 'gap-2'">
            <VBtn
                v-if="$can('create', 'agreements')"
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
                v-if="$can('create', 'agreements')"
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
                :class="windowWidth < 1024 ? 'px-3' : 'px-4'"
                :to="{ name: 'dashboard-activities' }"
            >
                <VIcon
                icon="custom-log-outlined"
                size="24"
                />
                <span :class="windowWidth < 1024 ? 'd-none' : ''">Din logg</span>
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
    </div>
</template>
