<script setup>
import { avatarText } from "@/@core/utils/formatters";
import { initialAbility } from "@/plugins/casl/ability";
import { useAppAbility } from "@/plugins/casl/useAppAbility";
import { useAuthStores } from "@/stores/useAuth";

const authStores = useAuthStores();
const router = useRouter();
const ability = useAppAbility();
const userData_ = ref(null)
const role = ref(null)

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {
  userData_.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData_.value.roles[0].name
}

const userData = field =>{
  let values = JSON.parse(localStorage.getItem('user_data') || 'null')

  if (values && field === "avatar") {
    return values.avatar;
  }
  if (values && field === "name") {
    return values.name;
  }

  if (values && field === "last_name") {
    return values.last_name;
  }

  if (values && field === "email") {
    return values.email;
  }

  return false;
};

const truncateText = (text, length = 28) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
};

const logout = async () => {
  await nextTick(() => {
    router.replace("/login");
  });

  authStores.logout().then((response) => {
    // Remove "user_data" from localStorage
    localStorage.removeItem("user_data");

    // Remove "accessToken" from localStorage
    localStorage.removeItem("accessToken");

    // Remove "userAbilities" from localStorage
    localStorage.removeItem("userAbilities");

    // Reset ability to initial ability
    ability.update(initialAbility);
    router.push("/login");
  });
};
</script>

<template>
  <VBtn
    class="btn-icon-profile"
  >
    <VAvatar
      class="cursor-pointer"
      :color="userData('avatar') ? 'default' : 'primary'"
      variant="tonal"
    >
      <VImg
        v-if="userData('avatar')"
        style="border-radius: 50%"
        :src="userData('avatar')"
      />
      <span v-else class="font-weight-semibold">
        {{ avatarText(userData("name")) }}
      </span>
    </VAvatar>
    <VIcon icon="custom-chevron-down" size="16" />

    <VMenu activator="parent" width="230" location="bottom end" offset="14px">
      <VCard class="profile-menu-card">
        <VList class="profile-menu-list" density="compact">
          <!-- 👉 User Avatar & Name -->
          <VListItem class="align-center p-0">
            <template #prepend>
              <VListItemAction start class="pt-0">
              <VBadge
                dot
                location="bottom right"
                offset-x="3"
                offset-y="3"
                color="success"
              >
                <VAvatar
                  :color="userData('avatar') ? 'default' : 'primary'" 
                  variant="tonal"
                  size="40"
                >
                  <VImg
                    v-if="userData('avatar')"
                    style="border-radius: 50%;"
                    :src="userData('avatar')"
                  />
                  <span
                    v-else
                    class="font-weight-semibold"
                  >
                    {{ avatarText(userData('name')) }}
                  </span>
                </VAvatar>
              </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-semibold d-flex flex-column">
              <span>{{ userData('name') }} {{ userData('last_name') }}</span>
              <span class="text-xs">
                {{ truncateText(userData('email')) }}</span>             
            </VListItemTitle>
            <VListItemSubtitle />
          </VListItem>

          <VDivider class="my-2" />

          <!--  👉 Roles -->
          <VListItem :to="{ name: 'dashboard-admin-roles' }" v-if="$can('view', 'roles')">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="mdi-account-lock-open"
                size="22"
              />
            </template>

            <VListItemTitle>Roller</VListItemTitle>
          </VListItem>

          <!--  👉 Users -->
          <VListItem :to="{ name: 'dashboard-admin-users' }" v-if="$can('view', 'users') && role !== 'Supplier'">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="mdi-account"
                size="22"
              />
            </template>

            <VListItemTitle>Användare</VListItemTitle>
          </VListItem>

          <!--<VListItem :to="{ name: 'dashboard-admin-suppliers-users' }" v-if="$can('view', 'users') && role === 'Supplier'">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="custom-users"
                size="22"
              />
            </template>

            <VListItemTitle>Mitt team</VListItemTitle>
          </VListItem>-->

          <!--  👉 Company -->
          <VListItem :to="{ name: 'dashboard-company' }" v-if="role === 'SuperAdmin' || role === 'Administrator'">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-building-store"
                size="22"
              />
            </template>

            <VListItemTitle>Företag</VListItemTitle>
          </VListItem>

          <!--  👉 Profile -->
          <VListItem :to="{ name: 'dashboard-profile' }">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="custom-profile"
                size="22"
              />
            </template>

            <VListItemTitle>Profil</VListItemTitle>
          </VListItem>

          <!-- 👉 Logout -->
          <VListItem
            link
            @click="logout"
          >
            <template #prepend>
              <VIcon
                class="me-2"
                icon="custom-logout"
                size="22"
              />
            </template>

            <VListItemTitle>Logga ut</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VMenu>
  </VBtn>
</template>

<style lang="scss">
.profile-menu-card {
  box-shadow: 0px 0px 40px 0px rgba(0, 0, 0, 0.15) !important;
}

.profile-menu-list {
  padding: 0.5rem;

  .v-list-item {
    margin: 4px;
    padding: 8px !important;
    min-height: 44px !important;
    border-radius: 64px !important;
    transition: background-color 0.2s ease;

    &:first-child {
      margin: 0 0 8px 0 !important;
      border-radius: 0 !important;
      padding: 0 4px !important;

      &:hover {
        background-color: transparent !important;
      }
    }

    &:hover:not(:first-child) {
      background-color: #bdd2c8 !important;

      .v-list-item-title {
        color: #416054 !important;
      }

      .v-icon {
        color: #416054 !important;
      }
    }

    &.v-list-item--active {
      background-color: #1c2925 !important;

      .v-list-item-title {
        color: #fff !important;
      }

      .v-icon {
        color: #57f287 !important;
      }
    }

    .v-icon {
      color: #6e9383 !important;
      font-size: 22px !important;
    }

    .v-list-item-title {
      font-size: 14px !important;
      font-weight: 400 !important;
      color: #6E9383 !important;
    }
  }
}
</style>
