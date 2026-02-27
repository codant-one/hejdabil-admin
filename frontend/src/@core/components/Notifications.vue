<script setup>
import { avatarText } from "@core/utils/formatters";
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
                color="primary"
                size="small"
              >
                {{ props.notifications.filter(n => !n.read).length }} Nytt
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
            :key="notification.id || notification.title"
          >
            <VListItem
              :title="notification.title"
              :subtitle="notification.subtitle"
              link
              lines="one"
              min-height="66px"
              :class="{ 'notification-read': notification.read }"
              @click="handleNotificationClick(notification)"
            >
              <!-- Slot: Prepend -->
              <template #prepend>
                <VListItemAction start>
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
                </VListItemAction>
              </template>
              <!-- Slot: Append -->
              <template #append>
                <div class="d-flex flex-column align-end">
                  <small class="whitespace-no-wrap text-medium-emphasis">{{
                    notification.time
                  }}</small>
                  <VIcon 
                    v-if="!notification.read"
                    icon="tabler-circle-filled" 
                    size="8"
                    color="primary"
                    class="mt-1"
                  />
                </div>
              </template>
            </VListItem>
            <VDivider />
          </template>
        </div>

        <!-- 👉 Footer -->
        <VListItem class="notification-section" @click="$emit('click:readAllNotifications')">
         <VBtn block class="px-0">
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
</style>
