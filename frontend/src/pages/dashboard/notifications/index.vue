<script setup>

import { useDisplay } from "vuetify";
import { useMobilePaginationScroll } from '@/@core/composable/useMobilePaginationScroll'
import { useRouter } from 'vue-router'
import { useNotificationsStore } from '@/stores/useNotifications'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import NavBarNotifications from "@/layouts/components/NavBarNotifications.vue";
import MobileBottomBar from "@/layouts/components/MobileBottomBar.vue";
import navItems from "@/navigation/vertical";
import UserProfile from "@/layouts/components/UserProfile.vue";
import logo from "@images/logos/billogg-logo.svg";

const notificationsStore = useNotificationsStore()
const router = useRouter()
const emitter = inject("emitter")

const { width: windowWidth } = useWindowSize()
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const sectionEl = ref(null);

const notifications = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalNotifications = ref(0)
const isRequestOngoing = ref(true)
const user_id = ref(null)

const userData = ref(null)
const advisor = ref({
  type: '',
  message: '',
  show: false
})

useMobilePaginationScroll({
  targetRef: sectionEl,
  currentPage,
  isRequestOngoing,
  enabled: mdAndDown,
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = notifications.value.length 
    ? (currentPage.value - 1) * rowPerPage.value + 1 
    : 0
  const 
  lastIndex = notifications.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalNotifications.value} resultat`;
 //return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalNotifications.value } register`
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  user_id.value = userData.value ? userData.value.id : null

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1;
    user_id.value = null;
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    user_id: user_id.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await notificationsStore.fetchNotifications(data)

  notifications.value = notificationsStore.getNotifications
  totalPages.value = notificationsStore.last_page
  totalNotifications.value = notificationsStore.notificationsTotalCount

  isRequestOngoing.value = false
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const redirectTo = (path) => {
  router.push({
    name: path,
  });
};

const onReadAll = async () => {
  isRequestOngoing.value = true
  
  await notificationsStore.markAllRead()

  isRequestOngoing.value = false
}

const onNotificationClick = async (notification) => {
  // Marcar como leída si tiene ID
  if (notification.id && !notification.read) {
    await notificationsStore.markAsRead(notification.id)
  }
  
  // Navegar a la ruta si existe
  if (notification.route) {
    router.push(notification.route)
  }
}

const onDeleteNotification = async (notificationId) => {
  if (!notificationId) return

  isRequestOngoing.value = true

  try {
    
    let res = await notificationsStore.deleteNotification(notificationId)

    if (notifications.value.length === 1 && currentPage.value > 1) {
      currentPage.value -= 1
    }

    advisor.value = {
      type: res.data.success ? 'success' : 'error',
      message: res.data.success ? 'Anmälan raderad!' : res.data.message,
      show: true
    }

    await fetchData()

    setTimeout(() => {
      advisor.value = {
        type: '',
        message: '',
        show: false
      }
    }, 3000)

  } finally {
    isRequestOngoing.value = false
  }
}

const onDeleteAll = async () => {
  if (!user_id.value) return

  isRequestOngoing.value = true

  try {
    const res = await notificationsStore.clearAllNotificationsByUser(user_id.value)

    currentPage.value = 1
    await fetchData()

    advisor.value = {
      type: res.data.success ? 'success' : 'error',
      message: res.data.success ? 'Alla meddelanden raderade!' : res.data.message,
      show: true,
    }

    setTimeout(() => {
      advisor.value = {
        type: '',
        message: '',
        show: false,
      }
    }, 3000)
  } finally {
    isRequestOngoing.value = false
  }
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInSeconds = Math.floor((now - date) / 1000)
  
  if (diffInSeconds < 60) return 'Nyss'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} min sedan`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} tim sedan`
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} dag sedan`
  
  return date.toLocaleDateString('sv-SE')
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
  <section class="page-section" ref="sectionEl">
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

    <VCard 
      class="page-notifications card-fill pa-6 d-flex flex-column" 
      :class="windowWidth < 1024 ? '' : ''"
    >
      <!--Menu horizontal-->
      <div class="d-flex justify-between align-center mb-6 flex-wrap gap-y-4 navigation-notification-bar"> 
        <div class="d-flex align-center flex-0 cursor-pointer" 
            :class="windowWidth < 1024 ? 'justify-center' : ''"
            @click="redirectTo('dashboard-panel')"
        >
          <img :src="logo" width="121" height="40" alt="Billogg" />
        </div>

        <div class="d-flex gap-x-3 buttons-center" v-if="windowWidth >= 1024">
          <VBtn
            class="btn-blue px-6"
            @click="redirectTo('dashboard-admin-agreements-purchase')"
          >
            Köp
            <VIcon icon="custom-car-close" size="24" />
          </VBtn>
          <VBtn
            class="btn-green px-6"
            @click="redirectTo('dashboard-admin-agreements-sales')"
          >
            Sälj
            <VIcon icon="custom-car-open" size="24" />
          </VBtn>
        </div>

        <div class="d-flex align-center gap-x-2">
          <NavBarNotifications />
          <VBtn
            variant="flat"
            :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"
            class="btn-white-3"
            height="48"
            width="48"
          >
            <VIcon icon="custom-settings" size="24" />
          </VBtn>
          <UserProfile />
        </div>
      </div>
    
      <!--Buttons-->
      <div class="d-flex w-auto margin-notifications">
        <VBtn                
          class="btn-light" 
          :to="{ name: 'dashboard-panel' }"
          >
          <VIcon icon="custom-return" size="24" />
          <span v-if="windowWidth < 1024">Gå ut</span>
          <span v-else>Tillbaka</span>                    
        </VBtn>
      </div>

      <!--List desktop-->
      <div  
        v-if="!$vuetify.display.mdAndDown"
        v-show="notifications.length"
        class="pb-6">

        <div class="d-flex align-center w-auto margin-notifications my-6" v-if="notifications.length">
          <span class="title-notifications">Alla meddelanden</span>

          <VSpacer />

          <div class="d-flex gap-4">
            <VBtn 
              class="btn-light" 
              block
              @click="onReadAll"
            >
              <VIcon icon="custom-eye" size="24" />
              Markera alla som läst
            </VBtn>
            <VBtn 
              class="btn-light" 
              block
              @click="onDeleteAll"
            >
              <VIcon icon="custom-waste" size="24" />
              Rensa allt
            </VBtn>
          </div>
        </div>

        <VCardText 
          v-for="notification in notifications"
          :key="notification.id"
          class="bg-white mb-2 card-notification d-flex gap-2 align-center justify-between cursor-pointer"
          @click="onNotificationClick(notification)"
        >
          
          <VBadge
            v-if="notification.read === 0"
            location="top start"
            dot
          >
            <VAvatar
              color="#F5F8F6"
              :image="notification.img || undefined"
              :icon="notification.icon || undefined"
              variant="flat"
              size="40"
              rounded="lg"
              class="notification-avatar"
            />
          </VBadge>

          <VAvatar
            v-else
            color="#F5F8F6"
            :image="notification.img || undefined"
            :icon="notification.icon || undefined"
            variant="flat"
            size="40"
            rounded="lg"
            class="notification-avatar"
          />
          
          <div class="d-flex flex-column gap-1">          
            <span class="notification-title">{{ notification.title }}</span>
            <span class="notification-text">{{ notification.text }}</span>
          </div>
          <VSpacer />
          <div class="me-6">
            <span class="notification-time">{{ formatTime(notification.created_at) }}</span>
          </div>
          <VBtn
            icon
            class="btn-white close-btn"
            @click.stop="onDeleteNotification(notification.id)"
          >
            <VIcon size="16" icon="custom-close" />
          </VBtn>

        </VCardText>
      </div>

      <!--List empty-->
      <div
        v-if="!isRequestOngoing && !notifications.length"
        class="empty-state mt-10"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-notifications"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga nya notifikationer</div>
          <div class="empty-state-text">
            Här samlas alla dina viktiga uppdateringar. 
            Vi meddelar dig när en faktura betalas, ett avtal signeras eller när något annat viktigt händer. 
            Just nu är allt lugnt!
          </div>
        </div>
      </div>

      <!--List mobile-->
      <div v-if="notifications.length && $vuetify.display.mdAndDown">

        <div class="d-flex flex-column gap-2 w-100 margin-notifications mb-6" v-if="notifications.length">
          <span class="title-notifications my-6">Alla meddelanden</span>

          <VBtn 
            class="btn-light" 
            block
            @click="onReadAll"
            >
            <VIcon icon="custom-eye" size="24" />
            Markera alla som läst
          </VBtn>
          <VBtn 
            class="btn-light" 
            block
            @click="onDeleteAll"
            >
            <VIcon icon="custom-waste" size="24" />
            Rensa allt
          </VBtn>
  
        </div>

        <VCardText 
          v-for="notification in notifications"
          :key="notification.id"
          class="bg-white mb-2 card-notification align-center d-flex gap-1 cursor-pointer"
          @click="onNotificationClick(notification)"
        >
          <div class="d-flex gap-2">
            <VBadge
              v-if="notification.read === 0"
              location="top start"
              dot
            >
              <VAvatar
                color="#F5F8F6"
                :image="notification.img || undefined"
                :icon="notification.icon || undefined"
                class="notification-avatar-mobile notification-avatar"
                variant="flat"
                size="x-small"
                rounded="sm"
              />
            </VBadge>

            <VAvatar
              v-else
              color="#F5F8F6"
              :image="notification.img || undefined"
              :icon="notification.icon || undefined"
              class="notification-avatar-mobile notification-avatar"
              variant="flat"
              size="x-small"
              rounded="sm"
            />

            <div class="d-flex flex-column gap-1">          
              <span class="notification-title">{{ notification.title }}</span>
              <span class="notification-text">{{ notification.text }}</span>
              <span class="notification-time">{{ formatTime(notification.created_at) }}</span>
          </div>
          </div>

          <VBtn
            icon
            class="close-btn px-1"
            @click.stop="onDeleteNotification(notification.id)"
          >
            <VIcon size="10" icon="custom-close" />
          </VBtn>

        </VCardText>
      </div>
      
      <!--pagination-->
      <VCardText
        v-if="notifications.length"
        :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
        class="align-center flex-wrap gap-4 pt-0 px-0 margin-notifications pagination-bottom"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer class="d-none d-md-block"/>
        
        <VPagination
          v-model="currentPage"
          size="small"
          :total-visible="4"
          :length="totalPages"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>
    </VCard>
  </section>

  <MobileBottomBar :nav-items="navItems" />
</template>

<style lang="scss">

  .navigation-notification-bar {
    background: linear-gradient(90deg, #eafff1 0%, #eafff8 50%, #ecffff 100%) !important;
    inset-block-start: 0rem;
    padding: 24px 24px;
    position: fixed;
    left: 0;
    right: 0;
    top: 0;
    z-index: 9999;
  }

  .card-notification {
    border-radius: 16px;
    height: 94px !important;
    min-height: 94px !important;
    max-height: 94px !important;
    margin: 0 96px;
    padding: 24px !important;

    @media (max-width: 1023px) {
      margin: 0;
      padding: 18px 12px !important;
      position: relative;
    }

  }

  @media (max-width: 1023px) {
    .card-notification .close-btn {
      position: absolute;
      top: -6px;
      right: 6px;
      z-index: 1;
      width: 24px !important;
      background: transparent !important;
    }

    .notification-avatar-mobile > svg,
    .notification-avatar-mobile .v-icon svg {
      width: 16px;
      height: 16px;
    }
  }

  .pagination-bottom {
    margin-top: auto !important;
    height: 48px;
    min-height: 48px;
    max-height: 48px;
    @media (max-width: 1023px) {
      margin-top: 8px !important;
    }
  }

  .margin-notifications {
    margin: 0 96px;

    @media (max-width: 1023px) {
      margin: 0;
    }
  }

  .title-notifications {
    font-weight: 700;
    font-size: 32px;
    line-height: 100%;
    letter-spacing: 0;
    color: #1C2925;
  }

  .notification-title {
    font-weight: 600;
    font-size: 16px;
    line-height: 100%;
    letter-spacing: 0px;
    color: #1C2925;

    @media (max-width: 1023px) {
      font-size: 14px;
    }
  }

  .notification-text {
    font-weight: 400;
    font-size: 16px;
    line-height: 100%;
    letter-spacing: 0px;
    color: #454545;

    @media (max-width: 1023px) {
      font-size: 14px;
    }
  }

  .notification-time {
    font-weight: 400;
    font-size: 16px;
    line-height: 100%;
    letter-spacing: 0px;
    color: #878787;

    @media (max-width: 1023px) {
      font-size: 14px;
    }
  }

  .page-notifications {
    margin-top: 80px;
    background: linear-gradient(90deg, #EAFFF1 0%, #EAFFF8 50%, #ECFFFF 100%);

    @media (max-width: 1023px) {
      padding-bottom: 120px !important;
    }
  }

  .notification-avatar .v-icon {
    color: #6E9383 !important;
  }
</style>

<route lang="yaml">
  meta:
      layout: blank
      action: view
      subject: dashboard
</route>
