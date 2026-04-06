<script setup>

import { useDisplay } from "vuetify";
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { themeConfig } from "@themeConfig";
import TabMyTeam from '@/pages/dashboard/admin/suppliers/users/index.vue'
import TabReports from '@/pages/dashboard/admin/suppliers/users/reports.vue'
import UserProfile from '@/views/dashboard/profile/UserProfile.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import Toaster from "@/components/common/Toaster.vue";

const sectionEl = ref(null);
const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const ability = useAppAbility()
const route = useRoute();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const avatar = ref('')
const avatarOld = ref('')
const haveAvatar = ref(false)
const avatar_id = ref(null)
const userData = ref(null)
const role = ref(null)
const userTab = ref(null)
const isRequestOngoing = ref(false)
const isFormEdited = ref(false);
let nextRoute = null;

const tabMyTeamRef = ref(null)
const tabReportsRef = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const tabs = [
  {
    icon: 'custom-user-search',
    title: 'Användaruppgifter',
  },
  {
    icon: 'custom-profit',
    title: 'Rapporter',
  },
]

const hasMyTeamAccess = computed(() => {
  if (role.value === 'Supplier') return true

  return role.value === 'User' && ability.can('view', 'my-team')
})

const hasReportsAccess = computed(() => {
  if (role.value === 'Supplier') return true

  return role.value === 'User' && ability.can('view', 'team-reports')
})

const visibleTabs = computed(() => {
  return tabs.filter(tab => {
    if (tab.title === 'Användaruppgifter') return hasMyTeamAccess.value
    if (tab.title === 'Rapporter') return hasReportsAccess.value

    return true
  })
})

const setTabFromRoute = (tabQuery, hash) => {
  const reportTabIndex = visibleTabs.value.findIndex(tab => tab.title === 'Rapporter')

  if ((hash === '#tab-report') && reportTabIndex !== -1) {
    userTab.value = reportTabIndex
    return
  }

  if (userTab.value !== null && userTab.value >= visibleTabs.value.length) {
    userTab.value = 0
    return
  }

  if (userTab.value === null) {
    userTab.value = 0
  }
}

watch(() => [route.query.tab, route.hash], ([tab, hash]) => {
  setTabFromRoute(tab, hash)
}, { immediate: true })

watch(role, () => {
  setTabFromRoute(route.query.tab, route.hash)
})

watch(userTab, () => {
  tabMyTeamRef.value?.resetFilters()
  tabReportsRef.value?.resetFilters()
})

onBeforeRouteLeave((to, from, next) => {
  if (isFormEdited.value) {
    nextRoute = next;
  } else {
    next();
  }
});

watchEffect(fetchData)

async function fetchData() { 

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')

  role.value = userData.value.roles[0].name

  if(role.value === 'Supplier') {
    avatarOld.value = userData.value.avatar
    avatar.value = userData.value.avatar
    haveAvatar.value = userData.value.avatar === null ? false : true
    avatar_id.value = userData.value.user_detail.avatar_id
  } else {
    avatarOld.value = themeConfig.settings.urlStorage + userData.value.supplier.boss.user.avatar
    avatar.value = themeConfig.settings.urlStorage + userData.value.supplier.boss.user.avatar
    haveAvatar.value = userData.value.supplier.boss.user.avatar === null ? false : true
    avatar_id.value = userData.value.supplier.boss.user.user_detail.avatar_id
    userData.value = userData.value.supplier.boss.user
  }
}

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
}

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
    <section class="page-section agreements-page" ref="sectionEl">
    <LoadingOverlay :is-loading="isRequestOngoing" />
    
    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
    >
        {{ advisor.message }}
    </VSnackbar>

    <Toaster />

    <VCard
      flat 
      class="card-fill pa-6"
      :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row'
      ]"
    >

      <UserProfile
        :user="userData"
        :avatarOld="avatarOld"
        :avatar="avatar"
        :haveAvatar="haveAvatar"
        :avatarId="avatar_id"
        :show-button="false"
      />
  
      <div>
        <VTabs 
          v-model="userTab" 
          grow            
          :show-arrows="false"
          class="profile-tabs mt-4" 
        >
          <VTab v-for="tab in visibleTabs" :key="tab.title">
              <VIcon size="24" :icon="'' + tab.icon" />
              {{ tab.title }}
          </VTab>
        </VTabs>

        <VWindow
          v-model="userTab"
          :touch="false"
        >
          <VWindowItem v-if="hasMyTeamAccess">
            <TabMyTeam ref="tabMyTeamRef" @alert="showAlert"/>
          </VWindowItem>
          <VWindowItem v-if="hasReportsAccess">
            <TabReports ref="tabReportsRef" @alert="showAlert"/>
          </VWindowItem>
        </VWindow>
      </div>
        
    </VCard>
  </section>
</template>

<style lang="scss">
  .v-tabs.profile-tabs {
    .v-btn {
      min-width: 50px !important;
      .v-btn__content {
        font-size: 14px !important;
        color: #454545;
      }
    }
  }

  @media (max-width: 776px) {
    .v-tabs.profile-tabs {
      .v-btn {
        .v-btn__content {
            white-space: break-spaces;
        }
      }
    }
  }
</style>

<route lang="yaml">
  meta:
    permissionsAny:
      - action: view
        subject: users
      - action: view
        subject: my-team
</route>
