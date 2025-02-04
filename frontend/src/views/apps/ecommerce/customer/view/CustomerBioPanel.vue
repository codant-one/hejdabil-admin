<script setup>

import { themeConfig } from '@themeConfig'
import { avatarText, formatNumber } from '@/@core/utils/formatters'
const props = defineProps({
  customerData: {
    type: Object,
    required: true,
  },
  isSupplier: {
    type: Boolean,
    required: true
  }
})

const route = useRoute()

const valueCount = ref(null)
const valueText = ref(null)
const icon = ref('tabler-shopping-cart')
const sales = ref(null)

watchEffect(fetchData)

async function fetchData() {

  if (route.name.includes('clients')) {
    valueCount.value = props.customerData.orders_count ?? 0
    valueText.value = 'Pedidos'
    icon.value = 'tabler-shopping-cart'
  } else {
    valueCount.value = props.customerData.product_count ?? 0
    valueText.value = 'Clients'
    icon.value = 'tabler-user'
    sales.value = null //CALCULAR MAS ADELANTE
  }

}
</script>

<template>
  <VRow>
    <!-- SECTION Customer Details -->
    <VCol cols="12">
      <VCard v-if="props.customerData">
        <VCardText class="text-center pt-15">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            :size="100"
            :color="!props.customerData.customer ? 'primary' : undefined"
            :variant="!props.customerData.user.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="props.customerData.user.avatar"
              :src="themeConfig.settings.urlStorage + props.customerData.user.avatar"
            />
            <span
              v-else
              class="text-5xl font-weight-medium"
            >
              {{ avatarText(props.customerData.user.name) }}
            </span>
          </VAvatar>

          <!-- 👉 Customer fullName -->
          <h4 class="text-h4 mt-4">
            {{ props.customerData.user.name }}  {{ props.customerData.user.last_name ?? '' }}
          </h4>
          <span class="text-sm"> {{ props.isSupplier ? 'Supplier' : 'Client' }} ID #{{ props.customerData.id }}</span>

          <div class="d-flex justify-center gap-x-5 mt-6">
            <div class="d-flex align-center">
              <VAvatar
                variant="tonal"
                color="primary"
                rounded
                class="me-3"
              >
                <VIcon :icon="icon" />
              </VAvatar>
              <div class="d-flex flex-column align-start">
                <span class="text-body-1 font-weight-medium"> {{ valueCount }} </span>
                <span class="text-body-2">{{ valueText }}</span>
              </div>
            </div>
            <div class="d-flex align-center">
              <VAvatar
                variant="tonal"
                color="primary"
                rounded
                class="me-3"
              >
                <VIcon icon="tabler-currency-dollar" />
              </VAvatar>
              <div class="d-flex flex-column align-start">
                <span class="text-body-1 font-weight-medium">KR {{ formatNumber(sales) ?? '0.00' }}</span>
                <span class="text-body-2">Total Sales</span>
              </div>
            </div>
          </div>
        </VCardText>

        <!-- 👉 Customer Details -->
        <VCardText>
          <VDivider class="my-4" />
          <div class="text-disabled text-uppercase text-sm">
            Details
          </div>

          <VList class="card-list mt-2">
            <VListItem>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  Name:
                  <span class="text-body-2">
                    {{ props.customerData.user.name }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  Lastname:
                  <span class="text-body-2">
                    {{ props.customerData.user.last_name ?? '' }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  E-mail:
                  <span class="text-body-2">
                    {{ props.customerData.user.email }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                    Phone:
                  <span class="text-body-2">
                    {{ props.customerData.user.user_detail.phone }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  Address:
                  <span class="text-body-2">
                    {{ props.customerData.address }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  Street:
                  <span class="text-body-2">
                    {{ props.customerData.street }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  Postal code:
                  <span class="text-body-2">
                    {{ props.customerData.postal_code }}
                  </span>
                </h6>
              </VListItemTitle>
              <VListItemTitle>
                <h6 class="text-base font-weight-semibold">
                  Organization number:
                  <span class="text-body-2">
                    {{ props.customerData.organization_number }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss" scoped>
  .card-list {
    --v-card-list-gap: 0.75rem;
  }

  .current-plan{
    background: linear-gradient(45deg, rgb(var(--v-theme-primary)) 0%, #9E95F5 100%);
    color: #fff;
  }
</style>