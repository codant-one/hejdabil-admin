<script setup>

import { FreeMode, Navigation, Thumbs, Scrollbar } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Carousel, Slide } from 'vue3-carousel'
import { formatNumber } from '@/@core/utils/formatters'
import car from '@images/car.png'
import car2 from '@images/car2.png'

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
  vehicle: {
    type: Object,
    required: true
  }

})

const emit = defineEmits([
  'update:isDrawerOpen',
  'close'
])

const vehicleImages = ref([])
const currentSlide = ref(0)
const modules = ref([FreeMode, Navigation, Thumbs, Scrollbar])
const thumbsSwiper = ref(null);

const title = computed(() => {
  const v = props.vehicle;
  if (!v || !v.model || !v.model.brand) return '';
  return v.model.brand.name + ' ' + v.model.name + (v.year === null ? '' : ', ' + v.year);
});
const reg_num = ref('')
const brand = ref(null)
const model = ref(null)
const mileage = ref(null)
const generation = ref(null)
const car_body = ref(null)
const year = ref(null)
const control_inspection = ref(null)
const color = ref(null)
const fuel = ref(null)
const gearbox = ref(null)
const purchase_price = ref(null)
const iva_purchase = ref(null)
const iva_sale = ref(null)
const state = ref(null)
const state_id = ref(null)
const sale_price = ref(null)
const min_sale_price = ref(null)
const purchase_date = ref(null)
const sale_date = ref(null)
const number_keys = ref(null)
const service_book = ref(0)
const summer_tire = ref(0)
const winter_tire = ref(0)
const last_service = ref(null)
const dist_belt = ref(0)
const last_dist_belt = ref(null)
const comments = ref(null)
const sale_comments = ref(null)
const organization_number = ref('')
const address = ref('')
const postal_code = ref('')
const phone = ref('')
const fullname = ref('')
const email = ref('')

const tab = ref('0')

watchEffect(async () => {
    if (props.isDrawerOpen) {
        if (!(Object.entries(props.vehicle).length === 0) && props.vehicle.constructor === Object) {
            reg_num.value = props.vehicle.reg_num

            brand.value = props.vehicle.model?.brand.name
            model.value = props.vehicle.model?.name
            year.value = props.vehicle.year
            color.value = props.vehicle.color

            mileage.value = props.vehicle.mileage
            generation.value = props.vehicle.generation
            car_body.value = props.vehicle.carbody?.name
            control_inspection.value = props.vehicle.control_inspection
            fuel.value = props.vehicle.fuel?.name
            gearbox.value = props.vehicle.gearbox?.name
            purchase_price.value = props.vehicle.purchase_price
            iva_purchase.value = props.vehicle.iva_purchase?.name
            iva_sale.value = props.vehicle.iva_sale?.name
            state.value = props.vehicle.state.name
            state_id.value = props.vehicle.state_id
            sale_price.value = props.vehicle.sale_price
            min_sale_price.value = props.vehicle.min_sale_price
            purchase_date.value = props.vehicle.purchase_date
            sale_date.value = props.vehicle.sale_date
            number_keys.value = props.vehicle.number_keys
            service_book.value = props.vehicle.service_book
            summer_tire.value = props.vehicle.summer_tire
            winter_tire.value = props.vehicle.winter_tire
            last_service.value = props.vehicle.last_service
            dist_belt.value = props.vehicle.dist_belt
            last_dist_belt.value = props.vehicle.last_dist_belt
            comments.value = props.vehicle.comments

            sale_comments.value = props.vehicle.sale_comments
            organization_number.value = props.vehicle.client?.organization_number
            address.value = props.vehicle.client?.address
            postal_code.value = props.vehicle.client?.postal_code
            phone.value = props.vehicle.client?.phone
            fullname.value = props.vehicle.client?.fullname
            email.value = props.vehicle.client?.email

            vehicleImages.value = [
                { url: car },
                { url: car2 }
            ]
              
        }
    }
})

const mediaSlides = computed(() => {
    const imgs = vehicleImages.value.map(i => ({
      type: 'image',
      url: i.url,
      thumb: i.url
    }));

    return [...imgs];
});

const closeVehicleDetailDialog = function() {
    thumbsSwiper.value = null
    vehicleImages.value = []
    
    emit('update:isDrawerOpen', false)
}

const slideTo = (val) => {
    currentSlide.value = val
}

const setThumbsSwiper = (swiper) => {
    thumbsSwiper.value = swiper;
}

</script>

<template>
    <!-- DIALOGO DE VER -->
    <VDialog
        :model-value="props.isDrawerOpen"
        max-width="1200"
        persistent>
        <DialogCloseBtn @click="closeVehicleDetailDialog" />

        <VCard title="Fordonsuppgifter" min-height="500">
            <VCardText class="px-2 px-md-4">
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
                                class="mySwiper pt-0"
                            >
                                <swiper-slide v-for="(slide, index) in mediaSlides" :key="index">
                                    <img
                                        :src="slide.url" 
                                        :alt="'image-'+index"
                                        class="thumb-media"
                                    />
                                </swiper-slide>
                            </swiper>
                        </div>
                        <div class="d-block d-md-none">
                            <Carousel
                                id="thumbnails"
                                :items-to-show="1"
                                :wrap-around="true"
                                v-model="currentSlide"
                                ref="carousel"
                            >
                                <Slide v-for="(slide, index) in mediaSlides" :key="index">
                                    <div class="carousel__item border-img" @click="slideTo(index)">
                                        <img 
                                            v-if="slide.type === 'image'"
                                            :src="slide.url" 
                                            :alt="'image-'+index"
                                            class="thumb-media-fill"
                                        />
                                        <iframe
                                            v-else
                                            :src="buildEmbedUrl(slide.url)"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen
                                        />
                                    </div>
                                </Slide>
                            </Carousel>
                        </div>
                    </VCol>
                    <VCol md="4" cols="12"  class="d-none d-md-block">
                        <swiper
                            :scrollbar="{
                                hide: true,
                            }"
                            :spaceBetween="10"
                            :thumbs="{ swiper: thumbsSwiper }"
                            :modules="modules"
                            class="mySwiper2 border-img"
                        >
                            <swiper-slide v-for="(slide, index) in mediaSlides" :key="index">
                                <img 
                                    :src="slide.url" 
                                    :alt="'slide-'+index"
                                    class="thumb-media-fill"
                                />
                            </swiper-slide>
                        </swiper>
                    </VCol>
                    <VCol md="7" cols="12">
                        <VCardTitle class="text-h6 title-truncate py-0 text-uppercase"> {{ title }} </VCardTitle>
                        <VCardSubtitle class="subtitle-truncate mb-3"> 
                            <span><strong class="me-2">REGNR:</strong> {{ reg_num }}</span>
                        </VCardSubtitle>
                        <VDivider />
                        <VCardText class="py-4">
                            <div>Märke: 
                                <span  class="font-weight-semibold">{{ brand }}</span>
                            </div>
                            <div>Modell: 
                                <span class="font-weight-semibold">{{ model }}</span>
                            </div>
                            <div>Årsmodell: 
                                <span class="font-weight-semibold">{{ year }}</span>
                            </div>
                            <div>Färg: 
                                <span class="font-weight-semibold">{{ color }}</span>
                            </div>
                            
                        </VCardText>
                        <VDivider />
                        <VCardText class="px-0 px-md-4 py-2">
                            <VTabs
                                v-model="tab"
                                color="deep-purple-accent-4"
                                align-tabs="center"
                            >
                                <VTab value="0">Fordon</VTab>
                                <VTab value="1">Information om bilen</VTab>
                                <VTab value="2">Prisinformation</VTab>
                                <VTab value="3" v-if="state_id === 12">Försäljningsuppgifter</VTab>
                                <VTab value="4" v-if="state_id === 12">Köpare</VTab>
                            </VTabs>
                            <VWindow v-model="tab">
                                <VWindowItem value="0">
                                    <VContainer fluid>
                                        <VRow>
                                            <VCol cols="12">
                                                <div>
                                                    <span class="font-weight-semibold"> Miltal: </span>
                                                    <span>{{ mileage }} Mil</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Generation: </span>
                                                    <span>{{ generation }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Kaross: </span>
                                                    <span>{{ car_body }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Inköpsdatum: </span>
                                                    <span>{{ purchase_date }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Kontrollbesiktning gäller tom: </span>
                                                    <span>{{ control_inspection }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Drivmedel: </span>
                                                    <span>{{ fuel }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Växellåda: </span>
                                                    <span>{{ gearbox }}</span>
                                                </div>
                                            </VCol>
                                        </VRow>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="1">
                                    <VContainer fluid>
                                        <VRow>
                                            <VCol cols="12">
                                                <div> 
                                                    <span class="font-weight-semibold"> Antal nycklar: </span>
                                                    <span>{{ number_keys }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Servicebok finns? </span>
                                                    <span>{{ service_book === 0 ? 'Ja' : 'Nej' }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Sommardäck finns? </span>
                                                    <span>{{ summer_tire === 0 ? 'Ja' : 'Nej' }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Vinterdäck finns? </span>
                                                    <span>{{ winter_tire  === 0 ? 'Ja' : 'Nej'}}</span>                                                    
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Senaste service: Mil/datum: </span>
                                                    <span>{{ last_service }}</span>                                                    
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Kamrem bytt? </span>
                                                    <span>{{ dist_belt  === 0 ? 'Ja' : (dist_belt  === 1 ? 'Nej' : 'Vet ej') }}</span>                                                    
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Kamrem bytt vid Mil/datum: </span>
                                                    <span>{{ last_dist_belt }}</span>                                                    
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Anteckningar: </span>
                                                    <span>{{ comments }}</span>                                                    
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
                                                    <span class="font-weight-semibold"> Inköpspris: </span>
                                                    <span>{{ formatNumber(vehicle.purchase_price ?? 0) }} kr</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> VMB / Moms: </span>
                                                    <span>{{ iva_purchase }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Status: </span>
                                                    <span>{{ state }}</span>
                                                </div>
                                            </VCol>
                                        </VRow>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="3" v-if="state_id === 12">
                                    <VContainer fluid>
                                        <VRow>
                                            <VCol cols="12">
                                                <div>
                                                    <span class="font-weight-semibold"> Försäljningspris: </span>
                                                    <span>{{ formatNumber(vehicle.purchase_price ?? 0) }} kr</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> VMB / Moms: </span>
                                                    <span>{{ iva_sale }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Försäljningsdag: </span>
                                                    <span>{{ sale_date }}</span>
                                                </div>
                                            </VCol>
                                        </VRow>
                                    </VContainer>
                                </VWindowItem>
                                 <VWindowItem value="4" v-if="state_id === 12">
                                    <VContainer fluid>
                                        <VRow>
                                            <VCol cols="12">                                             
                                                <div>
                                                    <span class="font-weight-semibold"> Org/personummer: </span>
                                                    <span>{{ organization_number }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Namn: </span>
                                                    <span>{{ fullname }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Adress: </span>
                                                    <span>{{ address }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Postnr. ort: </span>
                                                    <span>{{ postal_code }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> Telefon: </span>
                                                    <span>{{ phone }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-weight-semibold"> E-post: </span>
                                                    <span>{{ email }}</span>
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

    .thumb-media {
        width: 60px !important;
        height: 60px !important;
        object-fit: cover !important;
        border-radius: 8px !important;
    }

    .thumb-media-fill {
        width: 100% !important;
        object-fit: cover !important;
        border-radius: 8px !important;
    }

    .carousel__item  {
        width: 100%;
        margin: 0 2px;
    }

    .carousel__item iframe {
        height: 250px !important;
        width: 100% !important;
        border-radius: 16px !important;
    }
    
    .carousel__item img {
        height: 250px !important;
        border-radius: 16px !important;
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

    .border-img {
        border-radius: 16px !important;
        border: 1px solid #D9D9D9;
        background-color: white;
        text-align: center;
        align-items: center;
        display: flex;
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
        width: 60px !important;
        height: 60px !important;
    }

    .mySwiper .swiper-slide-thumb-active {
        opacity: 1;
    }

    .swiper-slide img, .swiper-slide iframe {
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
