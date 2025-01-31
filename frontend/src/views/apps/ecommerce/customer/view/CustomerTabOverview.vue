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
              <span class="text-primary text-h4 me-2">KR {{ formatNumber(balance) ?? '0.00' }}</span>
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
                KR {{ formatNumber(props.customerData.sales) ?? '0.00' }}
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
              Lista de deseos
            </h4>
            <div>
              <p class="mb-1">
                <span class="text-warning text-h5 me-2">{{ props.customerData.user.favorites.length }}</span>
                <span class="text-body-1">artículos en la lista de deseos</span>
              </p>
              <p class="mb-0 text-disabled">
                Artículos agregados por el cliente.
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
          <VCardText class="d-flex gap-y-1 flex-column">
            <VAvatar
              variant="tonal"
              color="info"
              icon="tabler-discount-2"
              rounded
            />
            <h4 class="text-h4 mb-2">
              Cupones
            </h4>
            <div>
              <p class="mb-1">
                <span class="text-info text-h5 me-2">0</span>
                <span class="text-body-1">Cupones que obtuvieron</span>
              </p>
              <p class="mb-0 text-disabled">
                Para usar cupón en sus próximas compras
              </p>
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
