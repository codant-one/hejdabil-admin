<script setup>
import { avatarText } from '@core/utils/formatters'

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
    default: 'bottom end',
  },
})

const emit = defineEmits(['click:readAllNotifications'])
</script>

<template>
  <VBadge
    :model-value="!!props.badgeProps"
    v-bind="props.badgeProps"
  >
    <VBtn
      icon
      variant="text"
      class="btn-custom-notifications"
    >
      <VIcon icon="tabler-bell" size="24" />
      <span>+{{ props.notifications.length }}</span>  

      <VMenu
        activator="parent"
        width="380px"
        :location="props.location"
        offset="14px"
      >
        <VList class="py-0">
          <!-- 👉 Header -->
          <VListItem
            title="Aviseringar"
            class="notification-section"
            height="48px"
          >
            <template #append>
              <VChip
                v-if="props.notifications.length"
                color="primary"
                size="small"
              >
                {{ props.notifications.length }} Ny
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
              :subtitle="notification.text"
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
                    <span v-if="notification.text">{{ avatarText(notification.text) }}</span>
                  </VAvatar>
                </VListItemAction>
              </template>
              <!-- Slot: Append -->
              <template #append>
                <small class="whitespace-no-wrap text-medium-emphasis">{{ notification.time }}</small>
              </template>
            </VListItem>
            <VDivider />
          </template>

          <!-- 👉 Footer -->
          <VListItem class="notification-section">
            <VBtn
              block
              @click="$emit('click:readAllNotifications')"
            >
              Läs alla aviseringar
            </VBtn>
          </VListItem>
        </VList>
      </VMenu>
    </VBtn>
  </VBadge>
</template>

<style lang="scss">
.notification-section {
  padding: 14px !important;
}
</style>
