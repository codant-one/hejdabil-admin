<script setup>

// import CustomerOrderTable from './CustomerOrderTable.vue'
// import CustomerProductTable from './CustomerProductTable.vue'
import { formatNumber } from '@/@core/utils/formatters'

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

const balance = ref(null)

watch(() =>  
  props.isSupplier, (addreses_) => {
    fetchData()
  });

watchEffect(fetchData)

async function fetchData() {
  if(props.isSupplier)
    balance.value = null //calcular mas adelante
}

</script>

<template>
  <Suspense>
    <VRow>
      <VCol
        v-if="props.isSupplier"
        cols="12"
        md="6"
      >
        <VCard>
          <VCardText class="d-flex gap-y-2 flex-column">
            <VAvatar
              variant="tonal"
              color="primary"
              icon="tabler-currency-dollar"
              rounded
            />
            <h4 class="text-h4">
              xxxxx
            </h4>
            <div>
              <span class="text-primary text-h4 me-2">{{ formatNumber(balance) ?? '0,00' }} kr</span>
              <span class="text-body-1">xxxx</span>
              <p class="mb-0 text-base text-disabled">
                xxxxxx
              </p>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        v-if="props.isSupplier"
        cols="12"
        md="6"
      >
        <VCard>
          <VCardText class="d-flex gap-y-2 flex-column">
            <VAvatar
              variant="tonal"
              color="success"
              icon="tabler-currency-dollar"
              rounded
            />
            <h4 class="text-h4">
              xxxx
            </h4>
            <div>
              <VChip
                color="success"
                class="mb-2"
                label
              >
                xxxx
              </VChip>
              <p class="mb-0 text-base text-disabled">
                {{ formatNumber(props.customerData.sales) ?? '0,00' }} kr
              </p>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        v-if="!props.isSupplier"
        cols="12"
        md="6"
      >
        <VCard>
          <VCardText class="d-flex gap-y-2 flex-column">
            <VAvatar
              variant="tonal"
              color="warning"
              icon="tabler-star"
              rounded
            />
            <h4 class="text-h4">
              xxx
            </h4>
            <div>
              xxxx
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        v-if="!props.isSupplier"
        cols="12"
        md="6"
      >
        <VCard>
          <VCardText class="d-flex gap-y-1 flex-column">
            <VAvatar
              variant="tonal"
              color="info"
              icon="tabler-discount-2"
              rounded
            />
            <h4 class="text-h4 mb-2">
              xx
            </h4>
            <div>
             xxx
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="12">
        <!-- <CustomerOrderTable v-if="!props.isSupplier"/> -->
      </VCol>

      <VCol cols="12" md="12">
        <!-- <CustomerProductTable 
          v-if="props.isSupplier"
          :id="props.customerData.user.id"  
        /> -->
      </VCol>
    </VRow>
  </Suspense>
</template>
