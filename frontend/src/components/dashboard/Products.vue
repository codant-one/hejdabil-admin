<script setup>
import amazonEchoDot from '@images/eCommerce/amazon-echo-dot.png'
import appleWatch from '@images/eCommerce/apple-watch.png'
import headphone from '@images/eCommerce/headphone.png'
import iphone from '@images/eCommerce/iphone.png'
import nike from '@images/eCommerce/nike.png'
import sonyDualsense from '@images/eCommerce/sony-dualsense.png'

const popularProducts = [
  {
    avatarImg: iphone,
    title: 'Apple iPhone 13',
    subtitle: 'SKU: FXZ-4567',
    stats: '$999.29',
  },
  {
    avatarImg: nike,
    title: 'Nike Air Jordan',
    subtitle: 'SKU: FXZ-3456',
    stats: '$72.40',
  },
  {
    avatarImg: headphone,
    title: 'Beats Studio 2',
    subtitle: 'SKU: FXZ-9485',
    stats: '$99.00',
  },
  {
    avatarImg: appleWatch,
    title: 'Apple Watch Series 7',
    subtitle: 'SKU: FXZ-2345',
    stats: '$249.99',
  },
  {
    avatarImg: amazonEchoDot,
    title: 'Amazon Echo Dot',
    subtitle: 'SKU: FXZ-8959',
    stats: '$79.40',
  },
  {
    avatarImg: sonyDualsense,
    title: 'Play Station Console',
    subtitle: 'SKU: FXZ-7892',
    stats: '$129.48',
  },
]

const moreList = [
  {
    title: 'Refresh',
    value: 'refresh',
  },
  {
    title: 'Download',
    value: 'Download',
  },
  {
    title: 'View All',
    value: 'View All',
  },
]
</script>

<template>
  <VCard
    title="Productos Populares"
    subtitle="Total de 10,4 mil clientes"
  >
    <VCardText>
      <VList class="card-list">
        <VListItem
          v-for="product in popularProducts"
          :key="product.title"
        >
          <template #prepend>
            <VAvatar
              size="46"
              rounded
              :image="product.avatarImg"
            />
          </template>

          <VListItemTitle class="font-weight-medium">
            {{ product.title }}
          </VListItemTitle>
          <VListItemSubtitle class="text-disabled">
            {{ product.subtitle }}
          </VListItemSubtitle>

          <template #append>
            <div class="d-flex align-center">
              <span class="font-weight-medium text-medium-emphasis me-2">{{ product.stats }}</span>
            </div>
          </template>
        </VListItem>
      </VList>
    </VCardText>
  </VCard>
</template>
