<script setup>

  import { useRouter } from 'vue-router'
  import { themeConfig } from '@themeConfig'
  import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";
  
  const props = defineProps({
    activities: {
      type: Array,
      default: () => []
    },
    notifications: {
      type: Array,
      default: () => []
    }
  })

  const router = useRouter()
  const activeTab = ref(1);
  const { width: windowWidth } = useWindowSize()

  const formatTime = (date) => {
    const d = new Date(date);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 60) return `${diffMins} min sedan`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours} tim sedan`;
    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays} dagar sedan`;
  };

  const getUserName = (activity) => {
    if (activity.user) {
      return `${activity.user.name ?? ''} ${activity.user.last_name ?? ''}`.trim()
    }
    return ''
  }

  const onNotificationClick = (notification) => {
    if (notification.route) {
      router.push(notification.route)
    }
  };

  const onActivityClick = (activity) => {
    if (activity.route) {
      router.push(activity.route)
    }
  };
</script>

<template>
  <VCard title="" class="card-dashboard">
    <VCardTitle class="title-box">
      <div class="title-text">
        {{ activeTab === 0 ? 'Senaste notiser' : 'Senaste aktiviteter' }}
      </div>

      <VTabs
        v-model="activeTab"
        :show-arrows="false"
        class="dashboard-tabs-1"
      >
        <VTab :value="0">
          <span>Notiser</span>
        </VTab>
        <VTab :value="1">
          <span>Aktiviteter</span>
        </VTab>
      </VTabs>
    </VCardTitle>

    <VCardText class="p-0 flex-grow-1 d-flex min-h-0">
      <VWindow v-model="activeTab" class="activities-window flex-grow-1">
        <VWindowItem :value="0" class="px-md-0 activities-window-item">
          <div
            v-if="!props.notifications || props.notifications.length === 0"
            class="empty-state mb-0"
            :class="$vuetify.display.mdAndDown ? 'py-0' : 'pa-4'"
          >
            <VIcon
              size="80"
              icon="custom-f-notifications"
            />
            <div class="empty-state-content w-100 pa-4">
              <div class="empty-state-title">Inga nya meddelanden</div>
              <div class="empty-state-text">
                Här visas viktiga notiser, till exempel när ett avtal har signerats eller när en faktura har betalats.
              </div>
            </div>
          </div>

          <VCardText 
            v-for="notification in props.notifications"
            v-else
            :key="notification.id"
            class="bg-white py-2 mx-0 card-activity d-flex align-center cursor-pointer"
            :class="windowWidth < 1024 ? 'gap-3' : 'gap-6'"
            style="height: 80px !important; min-height: 80px !important; max-height: 80px !important;"
            @click="onNotificationClick(notification)"
          >
            <VBadge
              v-if="notification.read === 0"
              location="top start"
              dot
            >
              <VAvatar
                color="#F5F8F6"
                :icon="notification.icon || undefined"
                variant="flat"
                size="40"
                rounded="lg"
                class="activity-avatar"
              />
            </VBadge>

            <VAvatar
              v-else
              color="#F5F8F6"
              :icon="notification.icon || undefined"
              variant="flat"
              size="40"
              rounded="lg"
              class="activity-avatar"
            />
            
            <div class="d-flex flex-column gap-1 w-100">
              <span class="activity-title d-flex justify-between align-center gap-2">
                {{ notification.title }}
                <span class="activity-time">{{ formatTime(notification.created_at) }}</span>
              </span>
              <span class="activity-text">{{ notification.text }}</span>
            </div>
          </VCardText>
        </VWindowItem>
        <VWindowItem :value="1" class="px-md-0 activities-window-item">
          <div
            v-if="!props.activities || props.activities.length === 0"
            class="empty-state mb-0"
            :class="$vuetify.display.mdAndDown ? 'py-0' : 'pa-4'"
          >
            <VIcon
              size="80"
              icon="custom-f-hourglass"
            />
            <div class="empty-state-content w-100 pa-4">
              <div class="empty-state-title">Ingen aktivitet ännu</div>
              <div class="empty-state-text">
                Här visas allt som händer i systemet - till exempel när fordon läggs till, fakturor skapas eller affärer uppdateras.
              </div>
            </div>
          </div>

          <VCardText 
            v-for="activity in props.activities"
            v-else
            :key="activity.id"
            class="bg-white pt-4 mx-0 card-activity d-flex gap-6 align-start"
            :class="activity.route ? 'cursor-pointer' : ''"
            @click="onActivityClick(activity)"
          >
            <VAvatar
              color="#F5F8F6"
              :icon="activity.icon || undefined"
              variant="flat"
              size="40"
              rounded="lg"
              class="activity-avatar"
            />
            
            <div class="d-flex flex-column gap-1 w-100">
              <span class="activity-title d-flex justify-between align-center gap-2">
                {{ activity.title }}
                <span class="activity-time">{{ formatTime(activity.created_at) }}</span>
              </span>
              <span class="activity-text">{{ activity.description }}</span>
              <span class="activity-user d-flex align-center gap-1">
                <VAvatar
                  variant="outlined"
                  size="16"
                >
                  <VImg
                    v-if="activity.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + activity.user.avatar"
                  />
                  <PresetAvatarImage
                    v-else
                    :avatar-id="activity.user.user_detail?.avatar_id"
                  />
                </VAvatar>
                {{ getUserName(activity) }}
              </span>
            </div>            
          </VCardText>
         </VWindowItem>
      </VWindow>
    </VCardText>
  </VCard>
</template>

<style lang="scss">

  .card-dashboard {
    height: 100%;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
  }

  .activities-window {
    height: 100%;
    min-height: 0;

    .v-window__container,
    .v-window-item {
      height: 100%;
      min-height: 0;
    }
  }

  .activities-window-item {
    height: 100%;
  }

  .empty-state {
    height: 100%;
    min-height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 24px;
  }

  .empty-state-content {
    max-width: 420px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
  }

  .empty-state-title {
    font-weight: 600;
    font-size: 18px;
    line-height: 24px;
    color: #454545;
  }

  .empty-state-text {
    font-weight: 400;
    font-size: 14px;
    line-height: 22px;
    color: #878787;
  }

  .card-activity {
    height: 94px !important;
    min-height: 94px !important;
    max-height: 94px !important;
    margin: 0 96px;
    padding: 0 24px;
    border-bottom: 1px solid #F6F6F6;

    &:last-of-type {
      border-bottom: none;
    }

    @media (max-width: 1023px) {
      margin: 0;
      padding: 18px 12px !important;
      position: relative;
    }

  }

  @media (max-width: 1023px) {
    .notification-avatar-mobile > svg,
    .notification-avatar-mobile .v-icon svg {
      width: 16px;
      height: 16px;
    }

    .card-activity {
      height: 116px !important;
      min-height: 116px !important;
      max-height: 116px !important;
    }
  }

  .activity-title {
    font-weight: 600;
    font-size: 14px;
    line-height: 20px;
    letter-spacing: 0px;
    color: #454545;

    @media (max-width: 1023px) {
      font-size: 14px;
    }
  }

  .activity-time {
    font-weight: 400;
    font-size: 12px;
    line-height: 16px;
    letter-spacing: 0px;
    color: #878787;
  }

  .activity-text {
    font-weight: 400;
    font-size: 12px;
    line-height: 20px;
    letter-spacing: 0px;
    color: #878787;
  } 

  .activity-user {
    font-weight: 400;
    font-size: 10px;
    line-height: 16px;
    letter-spacing: 0;
    color: #5d5d5d;
  }

  .activity-avatar .v-icon {
    color: #6E9383 !important;
  }
</style>
