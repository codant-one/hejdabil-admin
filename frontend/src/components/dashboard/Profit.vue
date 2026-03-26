<script setup>
import { formatNumber } from '@/@core/utils/formatters'

const props = defineProps({
  profit: {
    type: Object,
    default: () => ({}),
  },
})

const { width: windowWidth } = useWindowSize();

const profitData = computed(() => props.profit?.profit ?? props.profit ?? {})

const formatAmount = value => formatNumber(value ?? 0)

const formatVariation = value => {
  const numericValue = Number(value ?? 0)
  const roundedValue = Math.round(numericValue)
  const prefix = roundedValue > 0 ? '+' : ''

  return `${prefix}${roundedValue}% denna månad`
}

</script>

<template>
  <div class="profit-cards">
    <VCard title="" class="card-dashboard profit-card bg-aqua-1">
      <VCardText class="px-4 pt-4 pb-0">
          <VAvatar
            rounded="lg"
            size="48"
            style="background-color: #FFFFFF !important;"
          >
            <VIcon
              icon="custom-profit"
              size="24"
              color="#04585D"
            />
          </VAvatar>          
      </VCardText>

      <VCardTitle 
          class="title-box px-4 py-0 border-none"
          :class="windowWidth < 1024 ? 'flex-row' : ''"
      >
        <div class="title-text d-flex flex-column gap-2">
            Total vinst
            <span>Sedan start</span>
        </div>
      </VCardTitle>

      <VCardText class="profit-card__content px-4 py-0">
        <div class="d-flex align-center">
          <span class="text-number-grafic text-aqua-5">
            {{ formatAmount(profitData?.totalProfit) }}
          </span>
          <span class="currency-number-grafic text-aqua-5">
            SEK
          </span>
        </div>
      </VCardText>
      <VCardText class="px-4 pt-0 pb-4">
        <VChip
          label
          size="small"
          class="chip-profit"
        >
            {{ formatVariation(profitData?.totalProfitMonthlyVariation) }}
        </VChip>
      </VCardText>
    </VCard>

    <VCard title="" class="card-dashboard profit-card bg-green-1">
      <VCardText class="px-4 pt-4 pb-0">
        <VAvatar
          rounded="lg"
          size="48"
          style="background-color: #FFFFFF !important;"
        >
          <VIcon
            icon="custom-car-open"
            size="24"
            color="#0C5B27"
          />
        </VAvatar>          
      </VCardText>

      <VCardTitle 
        class="title-box px-4 py-0 border-none"
        :class="windowWidth < 1024 ? 'flex-row' : ''"
      >
        <div class="title-text d-flex flex-column gap-2">
            Total försäljning
            <span>Sedan start</span>
        </div>
      </VCardTitle>

      <VCardText class="profit-card__content px-4 py-0">
        <div class="d-flex align-center">
          <span class="text-number-grafic text-green-5">
            {{ formatAmount(profitData?.totalSale) }}
          </span>
          <span class="currency-number-grafic text-green-5">
            SEK
          </span>
        </div>
      </VCardText>
      <VCardText class="px-4 pt-0 pb-4">
        <VChip
          label
          size="small"
          class="chip-profit"
        >
            {{ formatVariation(profitData?.totalSaleMonthlyVariation) }}
        </VChip>
      </VCardText>
   </VCard>
   </div>
</template>

<style lang="scss">
  .chip-profit.v-chip {
    border-radius: 4px;
    opacity: 1;
    padding-top: 4px !important;
    padding-right: 8px !important;
    padding-bottom: 4px !important;
    padding-left: 8px !important;
    gap: 8px;
    background-color: #FFFFFF !important;

    font-weight: 400;
    font-size: 10px;
    line-height: 100%;
    letter-spacing: 0px;
    vertical-align: middle;
    color: #5D5D5D;
  }

  .chip-profit .v-chip__underlay {
    background-color: transparent !important;
  }

  .v-chip.v-chip--density-default {
    height: 24px !important;
  }

  .profit-cards {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: 1fr;
  }

  .profit-card {
    min-width: 0;
    gap: 16px;
  }

  .profit-card__content {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: space-between;
  }

  .profit-card__content--compact {
    padding-top: 4px;
  }

  @media (min-width: 1024px) {
    .profit-cards {
      height: 100%;
      grid-template-columns: minmax(0, 1fr);
      grid-template-rows: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 767px) {
    .profit-card {
      height: 210px !important;
    }
  }

  .text-number-grafic {
    font-weight: 600;
    font-size: 20px;
    line-height: 100%;
    letter-spacing: 0px;
    vertical-align: middle;
  }

  .currency-number-grafic {
    font-weight: 300;
    font-size: 20px;
    line-height: 100%;
    letter-spacing: 0px;
    vertical-align: middle;
  }
</style>