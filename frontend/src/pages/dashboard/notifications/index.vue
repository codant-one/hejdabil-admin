<script setup>

import { useDisplay } from "vuetify";
import { useRouter } from 'vue-router'
import { useNotificationsStore } from '@/stores/useNotifications'
import { avatarText } from "@core/utils/formatters";
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import NavBarNotifications from "@/layouts/components/NavBarNotifications.vue";
import UserProfile from "@/layouts/components/UserProfile.vue";
import logo from "@images/logos/billogg-logo.svg";

const notificationsStore = useNotificationsStore()
const router = useRouter()
const emitter = inject("emitter")

const { width: windowWidth } = useWindowSize()
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

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

</script>

<template>
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

  <VCard class="page-notifications pa-6 d-flex flex-column" style="min-height: 100vh;">

    <!--Menu horizontal-->
    <div class="d-flex justify-between align-center mb-6 flex-wrap gap-y-4"> 
      <div class="d-flex align-center flex-0 cursor-pointer" 
          :class="windowWidth < 1024 ? 'justify-center' : ''"
          @click="redirectTo('dashboard-panel')"
      >
        <img :src="logo" width="121" height="40" alt="Billogg" />
      </div>

      <div class="d-flex gap-x-3 buttons-center">
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
   
    <template  
      v-if="!$vuetify.display.mdAndDown"
      v-show="notifications.length">

      <div class="d-flex align-center w-100 w-md-auto margin-notifications my-6" v-if="notifications.length">
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
            :color="notification.color || 'primary'"
            :image="notification.img || undefined"
            :icon="notification.icon || undefined"
            variant="tonal"
            size="40"
          >
            <span v-if="notification.text">{{
              avatarText(notification.text)
            }}</span>
          </VAvatar>
        </VBadge>

        <VAvatar
          v-else
          :color="notification.color || 'primary'"
          :image="notification.img || undefined"
          :icon="notification.icon || undefined"
          variant="tonal"
          size="40"
        >
          <span v-if="notification.text">{{
            avatarText(notification.text)
          }}</span>
        </VAvatar>
        
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
    </template>
    
    <VCardText
      v-if="notifications.length"
      :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
      class="align-center flex-wrap gap-4 mb-6 px-0 margin-notifications pagination-bottom"
    >
      <span class="text-pagination-results">
        {{ paginationData }}
      </span>

      <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
      
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
</template>

<style>
  .card-notification {
    border-radius: 16px;
    height: 94px !important;
    min-height: 94px !important;
    max-height: 94px !important;
    margin: 0 96px;
    padding: 24px !important;
  }

  .pagination-bottom {
    margin-top: auto !important;
    height: 48px;
    min-height: 48px;
    max-height: 48px;
  }

  .margin-notifications {
    margin: 0 96px;
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
  }

  .notification-text {
    font-weight: 400;
    font-size: 16px;
    line-height: 100%;
    letter-spacing: 0px;
    color: #454545;
  }

  .notification-time {
    font-weight: 400;
    font-size: 16px;
    line-height: 100%;
    letter-spacing: 0px;
    color: #878787;
  }

  .page-notifications {
    background: linear-gradient(90deg, #EAFFF1 0%, #EAFFF8 50%, #ECFFFF 100%);
  }
</style>

<route lang="yaml">
  meta:
      layout: blank
      action: view
      subject: dashboard
</route>
