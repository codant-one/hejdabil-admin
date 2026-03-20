<script setup>
  const activeTab = ref(0);
  const { width: windowWidth } = useWindowSize()

  const now = new Date();

  const notifications = ref([
    { id: 1, read: 0, color: 'primary', icon: 'custom-contract', title: 'Avtal signerat', text: 'Avtalet har signerats korrekt. Avtals ID: 1042', created_at: new Date(now.getTime() - 15 * 60000).toISOString() },
    { id: 2, read: 1, color: 'primary', icon: 'custom-signature', title: 'Dokument signerat', text: 'Dokumentet har signerats korrekt. Dokument ID: 2087', created_at: new Date(now.getTime() - 2 * 3600000).toISOString() },
    { id: 3, read: 0, color: 'primary', icon: 'custom-contract', title: 'Avtal signerat', text: 'Avtalet har signerats korrekt. Avtals ID: 1039', created_at: new Date(now.getTime() - 5 * 3600000).toISOString() },
    { id: 4, read: 1, color: 'primary', icon: 'custom-signature', title: 'Dokument signerat', text: 'Dokumentet har signerats korrekt. Dokument ID: 2081', created_at: new Date(now.getTime() - 10 * 3600000).toISOString() },
    { id: 5, read: 0, color: 'primary', icon: 'custom-contract', title: 'Avtal signerat', text: 'Avtalet har signerats korrekt. Avtals ID: 1035', created_at: new Date(now.getTime() - 24 * 3600000).toISOString() },
    { id: 6, read: 1, color: 'primary', icon: 'custom-signature', title: 'Dokument signerat', text: 'Dokumentet har signerats korrekt. Dokument ID: 2076', created_at: new Date(now.getTime() - 36 * 3600000).toISOString() },
    { id: 7, read: 0, color: 'primary', icon: 'custom-contract', title: 'Avtal signerat', text: 'Avtalet har signerats korrekt. Avtals ID: 1030', created_at: new Date(now.getTime() - 47 * 3600000).toISOString() },
  ]);

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

  const onNotificationClick = (notification) => {
    console.log('Clicked notification:', notification.id);
  };
</script>

<template>
  <VCard title="" class="card-dashboard activities-scroll">
    <VCardTitle class="title-box">
      <div class="title-text">Senaste aktivitet</div>

      <VTabs
        v-model="activeTab"
        :show-arrows="false"
        class="dashboard-tabs-1"
      >
        <VTab :value="0">
          <span>Meddelanden</span>
        </VTab>
        <VTab :value="1">
          <span>Aktiviteter</span>
        </VTab>
      </VTabs>
    </VCardTitle>

    <VCardText class="p-0">
      <VWindow v-model="activeTab">
        <VWindowItem :value="0" class="px-md-0">
          <!-- Desktop -->
          <VCardText 
            v-for="notification in notifications"
            :key="notification.id"
            class="bg-white mx-0 card-notification d-none d-md-flex gap-1 align-center justify-between cursor-pointer"
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
              />
            </VBadge>

            <VAvatar
              v-else
              :color="notification.color || 'primary'"
              :image="notification.img || undefined"
              :icon="notification.icon || undefined"
              variant="tonal"
              size="40"
            />
            
            <div class="d-flex flex-column gap-1">          
              <span class="notification-title">{{ notification.title }}</span>
              <span class="notification-text">{{ notification.text }}</span>
            </div>
            <VSpacer />
            <span class="notification-time">{{ formatTime(notification.created_at) }}</span>
          </VCardText>

          <!-- Mobile -->
          <VCardText 
            v-for="notification in notifications"
            :key="'m-' + notification.id"
            class="bg-white card-notification align-center d-flex d-md-none gap-1 cursor-pointer"
            @click="onNotificationClick(notification)"
          >
            <div class="d-flex gap-2">
              <VBadge
                v-if="notification.read === 0"
                location="top start"
                dot
              >
                <VAvatar
                  :color="notification.color || 'primary'"
                  :image="notification.img || undefined"
                  :icon="notification.icon || undefined"
                  class="notification-avatar-mobile"
                  variant="tonal"
                  size="x-small"
                />
              </VBadge>

              <VAvatar
                v-else
                class="notification-avatar-mobile"
                :color="notification.color || 'primary'"
                :image="notification.img || undefined"
                :icon="notification.icon || undefined"
                variant="tonal"
                size="x-small"
              />

              <div class="d-flex flex-column gap-1">          
                <span class="notification-title">{{ notification.title }}</span>
                <span class="notification-text">{{ notification.text }}</span>
                <span class="notification-time">{{ formatTime(notification.created_at) }}</span>
              </div>
            </div>
          </VCardText>
         </VWindowItem>
         <VWindowItem :value="1" class="px-md-0">
            Actividades
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
  overflow: hidden;
}

.activities-scroll {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.activities-scroll::-webkit-scrollbar {
  display: none;
}

.card-notification {
    height: 94px !important;
    min-height: 94px !important;
    max-height: 94px !important;
    margin: 0 96px;
    padding: 0 24px !important;
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
</style>
