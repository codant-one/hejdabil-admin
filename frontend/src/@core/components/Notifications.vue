<script setup>

import { ref } from 'vue';

const props = defineProps({
  notifications: {
    type: Array,
    required: true,
  },
  badgeProps: {
    type: null,
    required: false,
    default: undefined,
  },
  location: {
    type: null,
    required: false,
    default: "bottom end",
  },
});

const emit = defineEmits(["click:readAllNotifications", "click:notification"]);

const isMenuOpen = ref(false);

const handleNotificationClick = (notification) => {
  emit('click:notification', notification);
};

const closeMenu = () => {
  isMenuOpen.value = false;
};
</script>

<template>
  <VBtn icon variant="text" class="btn-custom-notifications">
    <VIcon icon="custom-bell" size="24" />
    <span>+{{ props.notifications.filter(n => !n.read).length }}</span>
    <VMenu
      v-model="isMenuOpen"
      activator="parent"
      width="380px"
      :location="props.location"
      offset="14px"
    >
      <VList class="py-0">
        <!-- 👉 Header -->
        <VListItem
          title="Meddelanden"
          class="notification-section notification-header"
          height="48px"
        >
          <template #append>
            <div class="d-flex align-center gap-2">
              <VChip
                v-if="props.notifications.filter(n => !n.read).length"
                color="#D8FFE4"
                size="small"
                variant="elevated"
              >
                <span class="nytt-class">
                  {{ props.notifications.filter(n => !n.read).length }} Nytt
                </span>
              </VChip>
              <VBtn
                icon
                size="small"
                variant="text"
                @click.stop="closeMenu"
              >
                <VIcon icon="tabler-x" size="20" />
              </VBtn>
            </div>
          </template>
        </VListItem>

        <VDivider />

        <!-- 👉 Notifications list -->
        <div style="max-height: 400px; overflow-y: auto;">
          <template
            v-for="notification in props.notifications"
            v-if="props.notifications.length > 0"
            :key="notification.id || notification.title"
          >
            <VListItem
              link
              lines="one"
              min-height="66px"
              :class="{ 'notification-read': notification.read }"
              @click="handleNotificationClick(notification)"
            >
            <VListItemTitle>
              <div class="d-flex justify-between">
                {{ notification.title }}
                <small class="whitespace-no-wrap text-medium-emphasiso notification-time">
                  {{ notification.time }} 
                </small>
              </div>
            </VListItemTitle>
            <VListItemSubtitle>
              {{ notification.subtitle }}
            </VListItemSubtitle>
              <!-- Slot: Prepend -->
              <template #prepend>
                <VListItemAction start>
                   <VBadge
                    v-if="!notification.read"
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
                </VListItemAction>
              </template>

              <!-- Slot: Append -->
              <template #append>
                <div class="d-none flex-column align-end mb-auto">
                  <small class="whitespace-no-wrap text-medium-emphasiso">
                    {{ notification.time }} 
                  </small>
                  <VIcon 
                    v-if="!notification.read"
                    icon="tabler-circle-filled" 
                    size="8"
                    color="primary d-none"
                    class="mt-1"
                  />
                </div>
              </template>
            </VListItem>
            <VDivider />
          </template>

          <template v-else>
            <div class="empty-state pa-6 mb-0">
              <VIcon
                size="64"
                icon="custom-f-checkmark"
              />
              <div class="empty-state-content w-100">
                <div class="empty-state-title font-16">Du är helt uppdaterad</div>
                <div class="empty-state-text">
                  Här var det tomt! Vi meddelar dig när något nytt händer.
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- 👉 Footer -->
        <VListItem class="notification-section notification-all" @click="$emit('click:readAllNotifications')">
         <VBtn class="btn-ghost px-0">
            Läs alla meddelanden
          </VBtn>
        </VListItem>
      </VList>
    </VMenu>
  </VBtn>
</template>

<style lang="scss" scoped>
.notification-section {
  padding: 14px !important;
}

.notification-all {
  width: 100%;
  justify-content: center !important;
}

.notification-all :deep(.v-list-item__content) {
  display: flex;
  justify-content: center;
}

.notification-header {
  position: sticky;
  top: 0;
  background: rgb(var(--v-theme-surface));
  z-index: 1;
}

.notification-read {
  opacity: 0.6;
  background-color: rgba(var(--v-theme-on-surface), 0.02);
}

.notification-avatar .v-icon {
  color: #6E9383 !important;
}

.notification-time {
  font-weight: 400;
  font-size: 12px;
  line-height: 16px;
  letter-spacing: 0px;
  vertical-align: middle;
  color: #878787;
}

.nytt-class {
  font-weight: 400;
  font-size: 14px;
  line-height: 16px;
  letter-spacing: 0;
  color: #0D6E2D;
}

.v-chip--variant-elevated {
  box-shadow: none !important;
}
</style>
