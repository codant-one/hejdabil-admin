<script setup>

import { themeConfig } from '@themeConfig'
import { FreeMode, Navigation, Thumbs, Scrollbar } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Carousel, Slide } from 'vue3-carousel'

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/free-mode';
import 'swiper/css/thumbs';
import 'swiper/css/scrollbar';
import 'vue3-carousel/dist/carousel.css'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  product: {
    type: Object,
    required: true
  }

})

const emit = defineEmits([
  'update:isDrawerOpen',
  'close'
])

const productImages = ref([])
const currentSlide = ref(0)
const modules = ref([FreeMode, Navigation, Thumbs, Scrollbar])
const thumbsSwiper = ref(null);

const title = ref('')
const description = ref(null)
const sku = ref('')
const selling_price = ref(null)
const sales =  ref(null)
const comments = ref(null)
const favourite = ref('')
const rating = ref(null)
const price = ref('')
const price_for_sale = ref('')
const wholesale_price = ref('')
const wholesale = ref(false)
const stock = ref('')
const width = ref('')
const height = ref('')
const deep = ref('')
const weigth = ref('')
const material = ref('')
const categories = ref('')
const store = ref('')
const color = ref('')
const imageAux = ref('')

const radioContent = ref([])
const selectedColor = ref(null)
const tab = ref('1')

watchEffect(() => {
    if (props.isDrawerOpen) {
        if (!(Object.entries(props.product).length === 0) && props.product.constructor === Object) {
            imageAux.value = [{ image : props.product.image }]

            categories.value = props.product.colors[0]?.categories.map(item => item.category.name)
            productImages.value = (props.product.colors[0]?.images.length === 0) ? imageAux.value : props.product.colors[0]?.images
            color.value = props.product.colors[0]?.color.name
            selectedColor.value = props.product.colors[0]?.color.id.toString()

            props.product.colors.forEach(element => { 
                var aux = {
                    value: element.color.id.toString(),
                    title: element.color.name,
                    image:  (element.images.length === 0) ? props.product.image : element.images[0].image
                }

                radioContent.value.push(aux)
            });

            store.value = props.product.user.user_detail.store_name ?? (props.product.user.supplier?.company_name ?? (props.product.user.name + ' ' + (props.product.user.last_name ?? '')))
            title.value = props.product.name
            description.value = props.product.single_description
            sku.value = props.product.colors[0]?.sku ?? null
            selling_price.value = props.product.selling_price ?? 0
            sales.value = props.product.sales ?? 0
            comments.value = props.product.comments ?? 0
            favourite.value = props.product.favourite ?? 0
            rating.value = props.product.rating ?? 2.5
            price.value = props.product.price
            price_for_sale.value = props.product.price_for_sale
            wholesale_price.value = props.product.wholesale_price
            wholesale.value = props.product.wholesale
            stock.value = props.product.stock
            width.value = props.product.detail.width
            weigth.value = props.product.detail.weigth
            height.value = props.product.detail.height
            deep.value = props.product.detail.deep
            material.value = props.product.detail.material
        }
    }
})

const closeProductDetailDialog = function() {
    thumbsSwiper.value = null
    productImages.value = []
    radioContent.value = []
    
    emit('update:isDrawerOpen', false)
}

const slideTo = (val) => {
    currentSlide.value = val
}

const setThumbsSwiper = (swiper) => {
    thumbsSwiper.value = swiper;
}

const chanceRadio = (value) => {

    if (Number.isInteger(Number(value.id))) {        
        var seleted = props.product.colors.filter(item => item.color_id === Number(value.id))[0]
        
        categories.value = seleted.categories.map(item => item.category.name)
        productImages.value = (seleted?.images.length === 0) ? imageAux.value : seleted?.images
        color.value = seleted?.color.name
        selectedColor.value = seleted?.color.id.toString()
        sku.value = seleted?.sku ?? null
    }
}
</script>

<template>
    <!-- DIALOGO DE VER -->
    <VDialog
        :model-value="props.isDrawerOpen"
        max-width="1000"
        persistent>
        <DialogCloseBtn @click="closeProductDetailDialog" />

        <VCard title="Detalle producto">
            <VCardText>
                <VRow>
                    <VCol md="1" cols="12">
                        <div class="d-none d-md-block">
                            <swiper
                                :direction="'vertical'"
                                :pagination="{ clickable: true}"
                                :spaceBetween="5"
                                :slidesPerView="6"
                                :freeMode="true"
                                :watchSlidesProgress="true"
                                @swiper="setThumbsSwiper"
                                class="mySwiper"
                            >
                                <swiper-slide v-for="(picture, index) in productImages" :key="index">
                                    <img :src="themeConfig.settings.urlStorage + picture.image" />
                                </swiper-slide>
                            </swiper>
                        </div>
                        <div class="d-block d-md-none">
                            <Carousel id="gallery" :items-to-show="1" :wrap-around="false" v-model="currentSlide">
                                <Slide v-for="(picture, index) in productImages" :key="index">
                                    <div class="carousel__item">
                                        <img :src="themeConfig.settings.urlStorage + picture.image" />
                                    </div>
                                </Slide>
                            </Carousel>

                            <Carousel
                                id="thumbnails"
                                :items-to-show="(productImages.length > 4 ) ? 4 : productImages.length"
                                :wrap-around="true"
                                v-model="currentSlide"
                                ref="carousel"
                            >
                                <Slide v-for="(picture, index) in productImages" :key="index">
                                    <div class="carousel__item" @click="slideTo(index)">
                                        <img :src="themeConfig.settings.urlStorage + picture.image" />
                                    </div>
                                </Slide>
                            </Carousel>
                        </div>
                    </VCol>
                    <VCol md="3" cols="12">
                        <div class="d-none d-md-block">
                            <swiper
                                :scrollbar="{
                                    hide: true,
                                }"
                                :spaceBetween="10"
                                :thumbs="{ swiper: thumbsSwiper }"
                                :modules="modules"
                                class="mySwiper2"
                            >
                                <swiper-slide v-for="(picture, index) in productImages" :key="index">
                                    <img :src="themeConfig.settings.urlStorage + picture.image" />
                                </swiper-slide>
                            </swiper>
                        </div>
                    </VCol>
                    <VCol md="8" cols="12">
                        <VCardTitle class="text-h6 title-truncate py-0 text-uppercase"> {{ title }} </VCardTitle>
                        <VCardSubtitle v-if="store" class="subtitle-truncate mb-3"> 
                            <span><strong class="me-2">Tienda:</strong> {{ store }}</span>
                        </VCardSubtitle>
                        <VDivider />
                        <VCardText class="py-4">
                            <div>SKU: 
                                <span  class="font-weight-semibold">{{ sku }}</span>
                            </div>
                            <div>Color: 
                                <span class="font-weight-semibold text-uppercase">{{ color }}</span>
                            </div>
                            <div> 
                                <CustomRadiosWithIcon
                                    v-model:selected-radio="selectedColor"
                                    :radio-content="radioContent"
                                    :grid-column="{ sm: '3', cols: '12' }"
                                    class="custom-input-setting"
                                    @change="chanceRadio"
                                >
                                    <template #default="{ item }">
                                        <div class="text-center">
                                            <span class="font-weight-semibold text-uppercase">
                                                {{ item.title }}
                                            </span>
                                            <div class="d-flex align-center justify-center">
                                                <img 
                                                    width="100"
                                                    :src="themeConfig.settings.urlStorage + item.image" />
                                            </div>
                                        </div>
                                    </template>
                                </CustomRadiosWithIcon>
                            </div>
                        </VCardText>
                        <VDivider />
                        <VCardText class="px-4 py-2">
                            <VTabs
                                v-model="tab"
                                color="deep-purple-accent-4"
                                align-tabs="center"
                            >
                                <VTab value="0">Descripción</VTab>
                                <VTab value="1">Especificaciones</VTab>
                                <VTab value="2">Detalles</VTab>
                            </VTabs>
                            <VWindow v-model="tab">
                                <VWindowItem value="0">
                                    <VContainer fluid>
                                        <VRow>
                                            <VCol cols="12">
                                                <span class="text-xs description" v-html="description"></span>
                                            </VCol>
                                        </VRow>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="1">
                                    <VContainer fluid>
                                        <VRow>
                                            <VCol cols="12">
                                                <div>
                                                    <span class="font-weight-semibold"> Costo: </span>
                                                    <span>{{ (parseFloat(price)).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2, style: "currency", currency: 'KR' }) }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Precio al detal: </span>
                                                    <span>{{ (parseFloat(price_for_sale)).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2, style: "currency", currency: 'KR' }) }}</span>
                                                </div>
                                                <div v-if="wholesale">
                                                    <span class="font-weight-semibold"> Precio al mayor: </span>
                                                    <span>{{ (parseFloat(wholesale_price)).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2, style: "currency", currency: 'KR' }) }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Stock: </span>
                                                    <span>{{ stock }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Alto: </span>
                                                    <span>{{ height }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Ancho: </span>
                                                    <span>{{ width }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Peso: </span>
                                                    <span>{{ weigth }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Profundo: </span>
                                                    <span>{{ deep }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Material: </span>
                                                    <span>{{ material }}</span>
                                                </div>
                                            </VCol>
                                        </VRow>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="2">
                                    <VContainer fluid>
                                        <VRow>
                                            <VCol cols="12">
                                                <div> 
                                                    <span class="font-weight-semibold"> Envios: </span>
                                                    <span v-if="selling_price >= 0">{{ selling_price }}</span>
                                                    <span v-else>
                                                        <VIcon
                                                            size="20"
                                                            icon="tabler-alarm-filled"
                                                            class="me-1"
                                                        />
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Ventas: </span>
                                                    <span v-if="sales >= 0">{{ sales }}</span>
                                                    <span v-else>
                                                        <VIcon
                                                            size="20"
                                                            icon="tabler-alarm-filled"
                                                            class="me-1"
                                                        />
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Comentarios: </span>
                                                    <span v-if="comments >= 0">{{ comments }}</span>
                                                    <span v-else>
                                                        <VIcon
                                                            size="20"
                                                            icon="tabler-alarm-filled"
                                                            class="me-1"
                                                        />
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Favoritos: </span>
                                                    <span v-if="favourite >= 0">{{ favourite }}</span>
                                                    <span v-else>
                                                        <VIcon
                                                            size="20"
                                                            icon="tabler-alarm-filled"
                                                            class="me-1"
                                                        />
                                                    </span>
                                                </div>  
                                                <div class="d-flex">
                                                    <span class="font-weight-semibold"> Valoración: </span>
                                                    <VRating
                                                        class="ms-1"
                                                        v-model="rating"
                                                        half-increments
                                                        readonly
                                                        density="compact"
                                                        size="small"
                                                        color="error"
                                                        active-color="error"
                                                    />
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Categorías: </span>
                                                    <span v-for="category in categories">
                                                        <VChip
                                                            class="me-4"
                                                            label
                                                            size="x-small"
                                                            color="secondary"
                                                            >
                                                            {{ category }}
                                                        </VChip>
                                                    </span>
                                                </div> 
                                            </VCol>
                                        </VRow>
                                    </VContainer>
                                </VWindowItem>
                            </VWindow>
                        </VCardText>              
                    </VCol>
                </VRow>
            </VCardText>
        </VCard>
    </VDialog>
</template>

<style lang="scss" scoped>

    .custom-input-setting {

        :deep(.custom-input) {
            padding: 10px !important;
        }
    }

    .carousel__item img {
        width: 60%;
    }

    .swiper-vertical > .swiper-pagination-bullets .swiper-pagination-bullet, .swiper-pagination-vertical.swiper-pagination-bullets .swiper-pagination-bullet {
        display: none !important;
    }
    .swiper {
        width: 100%;
        height: 100%;
    }

    .swiper-slide {
        text-align: center;
        font-size: 18px;
        background: #fff;

        /* Center slide text vertically */
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .swiper {
        width: 100%;
        height: 350px;
        margin-left: auto;
        margin-right: auto;
    }

    .swiper-slide {
        background-size: cover;
        background-position: center;
    }

    .mySwiper2 {
        height: 350px;
        width: 100%;
    }

    .mySwiper {
        box-sizing: border-box;
        padding: 10px 5px;
    }

    .mySwiper .swiper-slide {
        opacity: 0.4;
        border-style: solid;
        border-width: 1px;
        border-radius: 8px;
    }

    .mySwiper .swiper-slide-thumb-active {
        opacity: 1;
    }

    .swiper-slide img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 8px;
    }

    .description {
        line-height: 5px;

        :deep(ul) {
            list-style: disc;
            padding-inline-start: 1.5em;
        }
    }

</style>
