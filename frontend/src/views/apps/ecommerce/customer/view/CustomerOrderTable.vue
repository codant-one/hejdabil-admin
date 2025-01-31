<script setup>

import { useOrdersStores } from '@/stores/useOrders'
import { formatNumber } from '@/@core/utils/formatters'
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import router from '@/router'

const route = useRoute()
const ordersStores = useOrdersStores()

const orders = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalOrders = ref(0)

const paginationData = computed(() => {
  const firstIndex = orders.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = orders.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Mostrando ${ firstIndex } hasta ${ lastIndex } de ${ totalOrders.value } registros`
})

watchEffect(fetchData)

async function fetchData() {

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    clientId: Number(route.params.id)
  }

  await ordersStores.fetchOrders(data)

  orders.value = ordersStores.getOrders
  totalPages.value = ordersStores.last_page
  totalOrders.value = ordersStores.ordersTotalCount

}

const seeOrder = orderData => {
  router.push({ name : 'dashboard-admin-orders-id', params: { id: orderData.id } })
}

const resolveStatusShipping = shipping_state_id => {
  if (shipping_state_id === 1)
    return { color: 'error' }
  if (shipping_state_id === 2)
    return { color: 'warning' }
  if (shipping_state_id === 3)
    return { color: 'info' }
  if (shipping_state_id === 4)
    return { color: 'success' }
}

const resolveStatusPayment = payment_state_id => {
  if (payment_state_id === 1)
    return { color: 'error' }
  if (payment_state_id === 2)
    return { color: 'default' }
  if (payment_state_id === 3)
    return { color: 'warning' }
  if (payment_state_id === 4)
    return { color: 'info' }
}

</script>

<template>

  <VCard title="Pedidos realizados">
    <VCardText class="d-flex flex-wrap py-4 gap-4">
      <div
        class="me-3"
        style="width: 80px;">
              
        <VSelect
          v-model="rowPerPage"
          density="compact"
          variant="outlined"
          :items="[10, 20, 30, 50]"/>
      </div>

      <VSpacer />

      <div class="d-flex align-center flex-wrap gap-4">
        <!-- 👉 Search  -->
        <div style="width: 10rem;">
          <VTextField
            v-model="searchQuery"
            placeholder="Buscar"
            density="compact"
            clearable
          />
        </div>
      </div>
    </VCardText>

    <VDivider />

    <VTable class="text-no-wrap">
      <!-- 👉 table head -->
      <thead>
        <tr>
          <th scope="col"> REFERENCIA </th>
          <th scope="col"> FECHA </th>
          <th scope="col"> ESTADO DEL ENVÍO</th>
          <th scope="col"> ESTADO DEL PAGO</th>
          <th scope="col"> VENTA </th>
          <th scope="col" v-if="$can('ver', 'pedidos') || $can('eliminar', 'pedidos')">
            ACCIONES
          </th>
        </tr>
      </thead>
      <!-- 👉 table body -->
      <tbody>
        <tr 
          v-for="order in orders"
          :key="order.id"
          style="height: 3.75rem;">
          <td>
            <span 
              class="font-weight-medium cursor-pointer" 
              :class="order.wholesale === 0 ? 'text-success': 'text-primary'" 
              @click="seeOrder(order)">
              {{ order.reference_code }}
            </span>
          </td>
          <td> {{ format(parseISO(order.date), 'MMMM d, yyyy', { locale: es }).replace(/(^|\s)\S/g, (char) => char.toUpperCase()) }}</td>
          <td> 
            <VChip
              label
              :color="resolveStatusShipping(order.shipping.id)?.color"
            >
              {{ order.shipping.name }}
            </VChip>
          </td>
          <td> 
            <VChip
              label
              variant="outlined"
              :color="resolveStatusPayment(order.payment.id)?.color"
            >
              {{ order.payment.name }}
            </VChip>
          </td>
          <td> 
            <span class="text-body-1 font-weight-medium text-uppercase" v-if="order.payment.id === 4">
              TOTAL: KR {{ formatNumber(order.total) }} 
            </span>
            <div class="d-flex align-center gap-x-2" v-if="order.payment.id === 4">
              <div class="d-flex flex-column">
                <span class="text-sm text-disabled">
                  VENTA: KR {{ formatNumber(order.sub_total) }} 
                </span>
                <span class="text-sm text-disabled">
                  ENVÍO: KR {{ formatNumber(order.shipping_total) }} 
                </span>
              </div>
              </div>
          </td>
          <!-- 👉 Acciones -->
          <td class="text-center" style="width: 5rem;" v-if="$can('ver', 'pedidos') || $can('eliminar', 'pedidos')">      
            <VBtn
              v-if="$can('ver', 'pedidos')"
              icon
              variant="text"
              color="default"
              size="x-small"
              @click="seeOrder(order)">
              <VTooltip
                open-on-focus
                location="top"
                activator="parent">
                Ver
              </VTooltip>
              <VIcon
                size="28"
                icon="tabler-eye"
                class="me-1"
              />
            </VBtn>
            <VBtn
              v-if="$can('eliminar','pedidos')"
              icon
              size="x-small"
              color="default"
              variant="text"
              @click="showDeleteDialog(client)">
              <VTooltip
                open-on-focus
                location="top"
                activator="parent">
                Eliminar
              </VTooltip>   
              <VIcon
                size="22"
                icon="tabler-trash" />
            </VBtn>
          </td>
        </tr>
      </tbody>
      <!-- 👉 table footer  -->
      <tfoot v-show="!orders.length">
        <tr>
          <td
            colspan="6"
            class="text-center">
            Data not available
          </td>
        </tr>
      </tfoot>
    </VTable>
        
    <VDivider />

    <VCardText class="d-flex align-center flex-wrap justify-space-between gap-4 py-3 px-5">
      <span class="text-sm text-disabled">
        {{ paginationData }}
      </span>

      <VPagination
        v-model="currentPage"
        size="small"
        :total-visible="5"
        :length="totalPages"/>
          
    </VCardText>
  </VCard>
</template>

<style scoped>
  .text-success:hover {
    color: #FF0090 !important
  }
</style>