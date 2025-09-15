<script setup>
import { avatarText } from "@core/utils/formatters";

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

const emit = defineEmits(["click:readAllNotifications"]);
</script>

<template>
  <VBtn icon variant="text" class="btn-custom-notifications">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="20"
      height="24"
      viewBox="0 0 20 24"
      fill="none"
    >
      <path
        d="M10 0C8.94903 0 8.09524 0.860625 8.09524 1.92C8.09524 2.97937 8.94903 3.84 10 3.84C11.051 3.84 11.9048 2.97937 11.9048 1.92C11.9048 0.860625 11.051 0 10 0ZM7.32143 2.925C5.14509 3.86437 3.80952 5.97937 3.80952 8.64C3.80952 13.92 1.99963 15.2456 0.922619 16.035C0.444569 16.3837 0 16.7081 0 17.28C0 19.2994 2.99107 20.16 10 20.16C17.0089 20.16 20 19.2994 20 17.28C20 16.7081 19.5554 16.3837 19.0774 16.035C18.0004 15.2456 16.1905 13.92 16.1905 8.64C16.1905 5.97187 14.8568 3.8625 12.6786 2.925C12.2712 4.01625 11.2221 4.8 10 4.8C8.7779 4.8 7.72879 4.01437 7.32143 2.925ZM7.14286 21.06C7.14286 21.0787 7.14286 21.1012 7.14286 21.12C7.14286 22.7081 8.42448 24 10 24C11.5755 24 12.8571 22.7081 12.8571 21.12C12.8571 21.1012 12.8571 21.0787 12.8571 21.06C11.9606 21.0975 11.0082 21.12 10 21.12C8.99182 21.12 8.03943 21.0975 7.14286 21.06Z"
        fill="#1C2925"
      />
    </svg>
    <span>+ {{ props.notifications.length }}</span>
    <VMenu
      activator="parent"
      width="380px"
      :location="props.location"
      offset="14px"
    >
      <VList class="py-0">
        <!-- 👉 Header -->
        <VListItem
          title="Notifications"
          class="notification-section"
          height="48px"
        >
          <template #append>
            <VChip
              v-if="props.notifications.length"
              color="primary"
              size="small"
            >
              {{ props.notifications.length }} New
            </VChip>
          </template>
        </VListItem>

        <VDivider />

        <!-- 👉 Notifications list -->
        <template
          v-for="notification in props.notifications"
          :key="notification.title"
        >
          <VListItem
            :title="notification.title"
            :subtitle="notification.subtitle"
            link
            lines="one"
            min-height="66px"
          >
            <!-- Slot: Prepend -->
            <!-- Handles Avatar: Image, Icon, Text -->
            <template #prepend>
              <VListItemAction start>
                <VAvatar
                  :color="notification.color || 'primary'"
                  :image="notification.img || undefined"
                  :icon="notification.icon || undefined"
                  size="40"
                  variant="tonal"
                >
                  <span v-if="notification.text">{{
                    avatarText(notification.text)
                  }}</span>
                </VAvatar>
              </VListItemAction>
            </template>
            <!-- Slot: Append -->
            <template #append>
              <small class="whitespace-no-wrap text-medium-emphasis">{{
                notification.time
              }}</small>
            </template>
          </VListItem>
          <VDivider />
        </template>

        <!-- 👉 Footer -->
        <VListItem class="notification-section">
          <VBtn block @click="$emit('click:readAllNotifications')">
            READ ALL NOTIFICATIONS
          </VBtn>
        </VListItem>
      </VList>
    </VMenu>
  </VBtn>
</template>

<style lang="scss">
.notification-section {
  padding: 14px !important;
}
</style>
