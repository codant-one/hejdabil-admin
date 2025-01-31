<script setup>

import { useProductsStores } from '@/stores/useProducts'
import { themeConfig } from '@themeConfig'
import show from "@/components/products/show.vue";
import router from '@/router'

const props = defineProps({
  id: {
    type: Number,
    required: true
  }
})

const emitter = inject("emitter")
const productsStores = useProductsStores()

const myProductsList = ref([])
const products = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalProducts = ref(0)

const isProductDetailDialog = ref(false)
const selectedProduct = ref({})
const isConfirmApproveDialogVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const state_id = ref(3)

const paginationData = computed(() => {
  const firstIndex = products.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = products.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Mostrando ${ firstIndex } hasta ${ lastIndex } de ${ totalProducts.value } registros`
})

watchEffect(fetchData)

async function fetchData() {

    let data = {
        search: searchQuery.value,
        orderByField: 'id',
        orderBy: 'desc',
        limit: rowPerPage.value,
        page: currentPage.value,
        supplierId: props.id
    }

    await productsStores.fetchProducts(data)

    myProductsList.value = productsStores.getProducts

    myProductsList.value.forEach(element =>
        products.value.push({
        id: element.id,
        favourite: element.favourite,
        discarded: element.discarded,
        user: element.user,
        state: element.state,
        in_stock: element.in_stock,
        stock: element.stock,
        archived: element.archived,            
        title: element.name,
        image: element.image,
        price: element.price_for_sale,
        originalLink: themeConfig.settings.urlDomain + 'products/' + element.slug,
        categories: element.colors[0]?.categories.map(item => item.category.name),// Utiliza map para extraer los nombres de las categorías
        rating: element.rating,//agregar mas adelante informacion
        comments: 0,//agregar mas adelante informacion
        sales: element.sales,//agregar mas adelante informacion
        selling_price: 0,//agregar mas adelante informacion,
        likes: element.likes
        })
    );

    totalPages.value = productsStores.last_page
    totalProducts.value = productsStores.productsTotalCount

}

const resolveStatus = statusMsg => {
    if (statusMsg === 3)
        return {
            text: 'Publicado',
            color: 'success',
        }
    if (statusMsg === 5)
        return {
            text: 'Eliminado',
            color: 'warning',
        }
    if (statusMsg === 4)
        return {
            text: 'Pendiente',
            color: 'error',
        }
    if (statusMsg === 6)
        return {
            text: 'Rechazado',
            color: 'warning'
        }
}

const showProduct = async (id) => {
  isProductDetailDialog.value = true
  selectedProduct.value = myProductsList.value.filter((element) => element.id === id )[0]
}

const showStateDialog = (productData, id) => {
    isConfirmApproveDialogVisible.value = true
    state_id.value = id
    selectedProduct.value = { ...productData }
}

const showDeleteDialog = productData => {
  isConfirmDeleteDialogVisible.value = true
  selectedProduct.value = { ...productData }
}

const stateProduct = async state_id => {
    isConfirmApproveDialogVisible.value = false

    let data = { state_id: state_id }
    let res = await productsStores.updateState(data, selectedProduct.value.id)
    selectedProduct.value = {}

    let info = {
        message: res.data.success ? 'Producto actualizado!' : res.data.message,
        error: false
    }

    emitter.emit('toast', info)

    await fetchData()

    return true

}

const removeProduct = async () => {
    isConfirmDeleteDialogVisible.value = false

    let res = await productsStores.deleteProduct({ ids: [selectedProduct.value.id] })
    selectedProduct.value = {}

    let info = {
        message: res.data.success ? 'Producto eliminado!' : res.data.message,
        error: false
    }

    emitter.emit('toast', info)

    await fetchData()

    return true
}

const editProduct = id => {
    router.push({ name : 'dashboard-products-products-edit-id', params: { id: id } })
}

</script>

<template>
    <section>
        <VCard title="Productos publicados">
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
                    <th class="px-2"> PRODUCTO </th>
                    <th class="px-2"> STOCK </th>
                    <th class="px-2"> SKU </th>
                    <th class="px-2"> PRECIO </th>
                    <th class="px-2"> QTY </th>
                    <th class="px-2"> STATUS </th>
                    <th class="px-2 text-end" v-if="
                        $can('aprobar', 'productos') || 
                        $can('rechazar', 'productos') || 
                        $can('editar', 'productos') || 
                        $can('eliminar', 'productos')">
                        ACCIONES
                    </th>
                </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
                <tr 
                    v-for="product in myProductsList"
                    :key="product.id"
                    style="height: 3.75rem;">
                    <td class="text-wrap px-1"> 
                        <div class="d-flex align-center gap-x-2">
                            <VAvatar
                                v-if="product.image"
                                size="38"
                                variant="outlined"
                                rounded
                                :image="themeConfig.settings.urlStorage + product.image"
                            />
                            <div class="d-flex flex-column">
                                <span class="text-body-1 font-weight-medium text-uppercase">{{ product.name }}</span>
                                <span class="text-sm text-disabled">Tienda: {{ product.user.user_detail.store_name ?? (product.user.supplier?.company_name ?? (product.user.name + ' ' + (product.user.last_name ?? ''))) }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-2">   
                        <VSwitch 
                            :model-value="product.in_stock === 1 ? true : false"
                            readonly
                        /> 
                    </td>
                    <td class="px-2"> {{ product.colors[0]?.sku ?? '--' }} </td>
                    <td class="px-2"> {{ product.price_for_sale }} </td>
                    <td class="px-2"> {{ product.stock }} </td>
                    <td class="px-2"> 
                        <VChip
                            v-bind="resolveStatus(product.state_id)"
                            density="default"
                            label
                        />
                    </td>
                    <!-- 👉 Acciones -->
                    <td class="text-end px-2"
                        v-if="$can('aprobar', 'productos') ||
                        $can('rechazar', 'productos') || 
                        $can('editar', 'productos') || 
                        $can('eliminar', 'productos')">    
                        <VBtn
                            v-if="$can('ver', 'productos')"
                            @click="showProduct(product.id)"
                            icon
                            variant="text"
                            color="default"
                            size="x-small">
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
                            v-if="$can('aprobar', 'productos') && product.state_id === 4"
                            icon
                            size="x-small"
                            color="default"
                            variant="text"
                            @click="showStateDialog(product, 3)">
                            <VTooltip
                                open-on-focus
                                location="top"
                                activator="parent">
                                Aprobar
                            </VTooltip>      
                            <VIcon
                                size="22"
                                icon="mdi-cart-check" />
                        </VBtn>
                        <VBtn
                            v-if="$can('rechazar', 'productos') && product.state_id === 4"
                            icon
                            size="x-small"
                            color="default"
                            variant="text"
                            @click="showStateDialog(product, 6)">
                            <VTooltip
                                open-on-focus
                                location="top"
                                activator="parent">
                                Rechazar
                            </VTooltip>      
                            <VIcon
                                size="22"
                                icon="mdi-cart-off" />
                        </VBtn>
                        <VBtn
                            v-if="$can('editar', 'productos') && product.state_id !== 4"
                            icon
                            size="x-small"
                            color="default"
                            variant="text"
                            :disabled="product.state_id === 5"
                            @click="editProduct(product.id)">
                            <VTooltip
                                open-on-focus
                                location="top"
                                activator="parent">
                                Editar
                            </VTooltip>
                            <VIcon
                                size="22"
                                icon="tabler-edit" />
                        </VBtn>
                        <VBtn
                            v-if="$can('eliminar','productos')"
                            icon
                            size="x-small"
                            color="default"
                            variant="text"
                            :disabled="product.state_id === 5"
                            @click="showDeleteDialog(product)">
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
            <tfoot v-show="!products.length">
                <tr>
                <td
                    colspan="7"
                    class="text-center">
                    Datos no disponibles
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

        <show 
            v-model:isDrawerOpen="isProductDetailDialog"
            :product="selectedProduct"/>
  
        <VDialog
            v-model="isConfirmApproveDialogVisible"
            persistent
            class="v-dialog-sm" >
            <!-- Dialog close btn -->
                
            <DialogCloseBtn @click="isConfirmApproveDialogVisible = !isConfirmApproveDialogVisible" />

            <!-- Dialog Content -->
            <VCard :title=" (state_id === 3 ? 'Aprobar ': 'Rechazar ') + 'Producto'">
                <VDivider class="mt-4"/>
                <VCardText>
                Está seguro de {{ state_id === 3 ? 'aprobar': 'rechazar' }}  el producto <strong>{{ selectedProduct.name }}</strong>?.
                </VCardText>

                <VCardText class="d-flex justify-end gap-3 flex-wrap">
                <VBtn
                    color="secondary"
                    variant="tonal"
                    @click="isConfirmApproveDialogVisible = false">
                    Cancelar
                </VBtn>
                <VBtn @click="stateProduct(state_id)">
                    Aceptar
                </VBtn>
                </VCardText>
            </VCard>
        </VDialog>

        <!-- 👉 Confirm Delete -->
        <VDialog
            v-model="isConfirmDeleteDialogVisible"
            persistent
            class="v-dialog-sm" >
            <!-- Dialog close btn -->
                
            <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

            <!-- Dialog Content -->
            <VCard title="Eliminar Producto">
                <VDivider class="mt-4"/>
                <VCardText>
                Está seguro de eliminar el producto de <strong>{{ selectedProduct.name }}</strong>?.
                </VCardText>

                <VCardText class="d-flex justify-end gap-3 flex-wrap">
                <VBtn
                    color="secondary"
                    variant="tonal"
                    @click="isConfirmDeleteDialogVisible = false">
                    Cancelar
                </VBtn>
                <VBtn @click="removeProduct">
                    Aceptar
                </VBtn>
                </VCardText>
            </VCard>
        </VDialog>
    </section>
</template>
