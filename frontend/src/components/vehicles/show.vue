<script setup>

import { FreeMode, Navigation, Thumbs, Scrollbar } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Carousel, Slide } from 'vue3-carousel'
import { formatNumber, formatNumberInteger } from '@/@core/utils/formatters'
import car from '@images/car.png'

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/free-mode';
import 'swiper/css/thumbs';
import 'swiper/css/scrollbar';
import '@/styles/vendor/vue3-carousel.css'

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

const { width: windowWidth } = useWindowSize();

const vehicleImages = ref([])
const currentSlide = ref(0)
const modules = ref([FreeMode, Navigation, Thumbs, Scrollbar])
const thumbsSwiper = ref(null);

const title = ref(null)
const reg_num = ref(null)
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
const engine = ref(null)
const purchase_price = ref(null)
const iva_purchase = ref(null)
const iva_sale = ref(null)
const iva_sale_amount = ref(null)
const iva_sale_exclusive = ref(null)
const discount = ref(null)
const registration_fee = ref(null)
const total_sale = ref(null)
const currency_purchase = ref(null)
const currency_sale = ref(null)
const state = ref(null)
const state_id = ref(null)
const sale_price = ref(null)
const purchase_date = ref(null)
const sale_date = ref(null)
const number_keys = ref(null)
const service_book = ref(0)
const summer_tire = ref(0)
const winter_tire = ref(0)
const last_service = ref(null)
const last_service_date = ref(null)
const dist_belt = ref(0)
const last_dist_belt = ref(null)
const last_dist_belt_date = ref(null)
const comments = ref(null)
const sale_comments = ref(null)

const optionsRadio = ['Ja', 'Nej', 'Vet ej']

const tasks = ref([])

const organization_number_purchase = ref('')
const address_purchase = ref('')
const postal_code_purchase = ref('')
const phone_purchase = ref('')
const fullname_purchase= ref('')
const email_purchase = ref('')

const organization_number_sale = ref('')
const address_sale = ref('')
const postal_code_sale = ref('')
const phone_sale = ref('')
const fullname_sale= ref('')
const email_sale = ref('')

const tab = ref('0')

const cardItems = ref([])

watchEffect(async () => {
    if (props.isDrawerOpen) {
        if (!(Object.entries(props.vehicle).length === 0) && props.vehicle.constructor === Object) {
            title.value = props.vehicle.car_name ?? (props.vehicle.model?.brand.name + ' ' + props.vehicle.model?.name + (props.vehicle.year ? ', ' + props.vehicle.year : '')) ?? 'Detaljer'
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
            engine.value = props.vehicle.engine
            purchase_price.value = props.vehicle.purchase_price
            iva_purchase.value = props.vehicle.iva_purchase?.name
            iva_sale.value = props.vehicle.iva_sale?.name
            iva_sale_amount.value = props.vehicle.iva_sale_amount
            iva_sale_exclusive.value = props.vehicle.iva_sale_exclusive
            discount.value = props.vehicle.discount
            total_sale.value = props.vehicle.total_sale
            registration_fee.value = props.vehicle.registration_fee
            currency_purchase.value = props.vehicle.currency_purchase?.code
            currency_sale.value = props.vehicle.currency_sale?.code
            state.value = props.vehicle.state.name
            state_id.value = props.vehicle.state_id
            sale_price.value = props.vehicle.sale_price
            purchase_date.value = props.vehicle.purchase_date
            sale_date.value = props.vehicle.sale_date
            number_keys.value = props.vehicle.number_keys
            service_book.value = props.vehicle.service_book
            summer_tire.value = props.vehicle.summer_tire
            winter_tire.value = props.vehicle.winter_tire
            last_service.value = props.vehicle.last_service
            last_service_date.value = props.vehicle.last_service_date
            dist_belt.value = props.vehicle.dist_belt
            last_dist_belt.value = props.vehicle.last_dist_belt
            last_dist_belt_date.value = props.vehicle.last_dist_belt_date

            sale_comments.value = props.vehicle.sale_comments
            organization_number_sale.value = props.vehicle.client_sale?.organization_number
            address_sale.value = props.vehicle.client_sale?.address
            postal_code_sale.value = props.vehicle.client_sale?.street + ' ' + props.vehicle.client_sale?.postal_code
            phone_sale.value = props.vehicle.client_sale?.phone
            fullname_sale.value = props.vehicle.client_sale?.fullname
            email_sale.value = props.vehicle.client_sale?.email

            comments.value = props.vehicle.comments
            organization_number_purchase.value = props.vehicle.client_purchase?.organization_number
            address_purchase.value = props.vehicle.client_purchase?.address
            postal_code_purchase.value = props.vehicle.client_purchase ? (props.vehicle.client_purchase?.street + ' ' + props.vehicle.client_purchase?.postal_code) : null
            phone_purchase.value = props.vehicle.client_purchase?.phone
            fullname_purchase.value = props.vehicle.client_purchase?.fullname
            email_purchase.value = props.vehicle.client_purchase?.email

            tasks.value = props.vehicle.tasks || []

            vehicleImages.value = [
                { url: car },
                { url: car },
                { url: car }
            ]

            cardItems.value = [
                {
                    title: "Märke",
                    value: brand.value,
                    bgCustomColor: "bg-netto",
                },
                {
                    title: "Modell",
                    value: model.value,
                    bgCustomColor: "bg-moms",
                },
                {
                    title: "Årsmodell",
                    value: year.value,
                    bgCustomColor: "bg-summa",
                },
                {
                    title: "Färg",
                    value: color.value,
                    bgCustomColor: "bg-summa",
                }
            ]
              
        }
    }
})

const resolveStatus = () => {
    if (state_id.value === 10)
        return { class: 'pending' }
    if (state_id.value === 11)
        return { class: 'info' } 
    if (state_id.value === 12)
        return { class: 'success' }  
    if (state_id.value === 13)
        return { class: 'success' }
}

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
        width="1200"
        persistent=""
        class="action-dialog">

        <VCard min-height="500">
            <VCardText class="dialog-title-box d-flex flex-row justify-between align-center">
                <div class="dialog-title">
                    Detaljer
                </div>
                <VBtn
                    icon
                    small
                    class="btn-white"
                    @click="closeVehicleDetailDialog"
                >
                    <VIcon size="24" icon="custom-cancel" />
                </VBtn>
            </VCardText>
            <VDivider />
            <VCardText class="px-2 px-md-4">
                <div 
                    class="d-flex gap-4"
                    :class="[
                        windowWidth < 1024 ? 'flex-column' : 'flex-row'
                    ]"
                >
                    <div class="d-flex flex-column gap-2" :class="[windowWidth < 1024 ? 'w-100' : 'w-40']">
                        <span class="px-1 py-0 title-vehicle"> {{ title }} </span>
                        <span class="mb-3 px-1 py-0 subtitle-vehicle"> 
                            Reg nr: {{ reg_num }}
                        </span>

                        <VRow no-gutters>
                            <VCol md="2" cols="12">
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
                            <VCol md="10" cols="12"  class="d-none d-md-block">
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
                        </VRow>
                    </div>
                    <div :class="[windowWidth < 1024 ? 'w-100' : 'w-58 bg-neutral-1']">
                         <VCardText :class="[windowWidth < 1024 ? 'px-2 py-1' : 'pt-4 pb-2']">
                            <div class="d-flex gap-4 flex-wrap vehicle-items-container">
                                <div
                                    v-for="item in cardItems"
                                    class="vehicle-item"
                                    :class="item.bgCustomColor"
                                >
                                    <div class="vehicle-item-title">{{ item.title }}</div>
                                    <div class="vehicle-item-text">{{ item.value }}</div>
                                </div>
                            </div>                            
                        </VCardText>
                        <VCardText class="px-0 px-md-4 py-2">
                            <VTabs
                                v-model="tab"
                                grow
                                :show-arrows="false"
                                class="vehicles-tabs"
                            >
                                <VTab value="0"><span>Fordon</span></VTab>
                                <VTab value="1"><span>Information om bilen</span></VTab>
                                <VTab value="2"><span>Prisinformation</span></VTab>
                                <VTab value="3"><span>Säljaren</span></VTab>
                                <VTab value="4"><span>Åtgärder / Kostnader</span></VTab>
                                <VTab value="5" v-if="state_id === 12"><span>Försäljningsuppgifter</span></VTab>
                                <VTab value="6" v-if="state_id === 12"><span>Köpare</span></VTab>
                            </VTabs>
                            <VWindow v-model="tab">
                                <VWindowItem value="0">
                                    <VContainer fluid class="px-3">
                                        <div class="d-flex gap-6">
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2" v-if="mileage">
                                                    <span class="title-detail"> Miltal </span>
                                                    <span class="subtitle-detail">{{ formatNumberInteger(mileage ?? '0,00') }} Mil</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="generation">
                                                    <span class="title-detail"> Generation </span>
                                                    <span class="subtitle-detail">{{ generation }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="car_body">
                                                    <span class="title-detail"> Kaross </span>
                                                    <span class="subtitle-detail">{{ car_body }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="purchase_date">
                                                    <span class="title-detail"> Inköpsdatum </span>
                                                    <span class="subtitle-detail">{{ purchase_date }}</span>
                                                </div>
                                            </div>
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2" v-if="control_inspection">
                                                    <span class="title-detail"> Kontrollbesiktning gäller tom </span>
                                                    <span class="subtitle-detail">{{ control_inspection }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="fuel">
                                                    <span class="title-detail"> Drivmedel </span>
                                                    <span class="subtitle-detail">{{ fuel }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="gearbox">
                                                    <span class="title-detail"> Växellåda </span>
                                                    <span class="subtitle-detail">{{ gearbox }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="engine">
                                                    <span class="title-detail"> Motor </span>
                                                    <span class="subtitle-detail">{{ engine }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="1">
                                    <VContainer fluid class="px-3">
                                        <div class="d-flex gap-6">
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2" v-if="number_keys">
                                                    <span class="title-detail"> Antal nycklar </span>
                                                    <span class="subtitle-detail">{{ number_keys }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Servicebok finns? </span>
                                                    <span class="subtitle-detail">
                                                        <VRadioGroup v-model="service_book" inline readonly class="radio-form">
                                                            <VRadio
                                                                v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
                                                        </VRadioGroup>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Sommardäck finns? </span>
                                                    <span class="subtitle-detail">
                                                        <VRadioGroup v-model="summer_tire" inline readonly class="radio-form">
                                                        <VRadio
                                                                v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
                                                        </VRadioGroup>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Vinterdäck finns? </span>
                                                    <span class="subtitle-detail">
                                                        <VRadioGroup v-model="winter_tire" inline readonly class="radio-form">
                                                            <VRadio
                                                                v-for="(radio, index) in optionsRadio.slice(0, 2)"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
                                                        </VRadioGroup>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Kamrem bytt? </span>
                                                    <span class="subtitle-detail">
                                                        <VRadioGroup v-model="dist_belt" inline readonly class="radio-form">
                                                            <VRadio
                                                                v-for="(radio, index) in optionsRadio"
                                                                :key="index"
                                                                :label="radio"
                                                                :value="index"
                                                            />
                                                        </VRadioGroup>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="last_service">
                                                    <span class="title-detail"> Senaste service: Mil/datum</span>
                                                    <span class="subtitle-detail">{{ last_service ?? 0 }} Mil / {{ last_service_date ?? '0000-00-00' }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="last_dist_belt">
                                                    <span class="title-detail"> Kamrem bytt vid Mil/datum </span>
                                                    <span class="subtitle-detail">{{ last_dist_belt ?? 0 }} Mil / {{ last_dist_belt_date ?? '0000-00-00' }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="comments">
                                                    <span class="title-detail"> Anteckningar </span>
                                                    <span class="subtitle-detail">{{ comments }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="2">
                                    <VContainer fluid class="px-3">
                                         <div class="d-flex gap-6">
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2" v-if="vehicle.purchase_price">
                                                    <span class="title-detail"> Inköpspris </span>
                                                    <span class="subtitle-detail">{{ formatNumber(vehicle.purchase_price ?? 0) }} {{ currency_purchase }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="iva_purchase">
                                                    <span class="title-detail"> VMB / Moms</span>
                                                    <span class="subtitle-detail">{{ iva_purchase }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Status </span>
                                                    <span class="subtitle-detail">
                                                        <div
                                                            class="status-chip"
                                                            :class="`status-chip-${resolveStatus()?.class}`"
                                                        >
                                                            {{ vehicle.state.name }}
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-1-1 d-flex flex-column gap-4" v-if="vehicle.total_sale">
                                                <div class="d-flex flex-column gap-2" v-if="vehicle.total_sale">
                                                    <span class="title-detail"> Försäljningspris</span>
                                                    <span class="subtitle-detail">{{ formatNumber(vehicle.total_sale ?? 0) }} {{ currency_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="vehicle.total_sale && vehicle.purchase_price">
                                                    <span class="title-detail"> Vinst</span>
                                                    <span class="subtitle-detail">{{ formatNumber(vehicle.total_sale - vehicle.purchase_price - (tasks ?? []).filter(t => t.is_cost == 1).reduce((sum, item) => sum + parseFloat(item.cost), 0)) }} {{ currency_sale }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="3">
                                    <VContainer fluid class="px-3">
                                        <div class="d-flex gap-6">
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2" v-if="organization_number_purchase">
                                                    <span class="title-detail"> Org/personnummer </span>
                                                    <span class="subtitle-detail">{{ organization_number_purchase }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="fullname_purchase">
                                                    <span class="title-detail"> Namn</span>
                                                    <span class="subtitle-detail">{{ fullname_purchase }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="address_purchase">
                                                    <span class="title-detail"> Adress </span>
                                                    <span class="subtitle-detail">{{ address_purchase }}</span>
                                                </div>
                                            </div>
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2" v-if="postal_code_purchase">
                                                    <span class="title-detail"> Postnr. ort</span>
                                                    <span class="subtitle-detail">{{postal_code_purchase }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="phone_purchase">
                                                    <span class="title-detail"> Telefon</span>
                                                    <span class="subtitle-detail">{{ phone_purchase }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="email_purchase">
                                                    <span class="title-detail"> E-post</span>
                                                    <span class="subtitle-detail">{{ email_purchase }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="4">
                                    <VContainer fluid class="px-3">
                                        <div class="d-flex gap-6">                                            
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail font-weight-bold"> Totala åtgärder </span>
                                                    <span class="subtitle-detail font-weight-bold">
                                                        {{ formatNumber((tasks ?? []).filter(t => t.is_cost == 0).reduce((sum, item) => sum + parseFloat(item.cost), 0)) }} {{ currency_purchase }}
                                                    </span>
                                                    <VDivider />
                                                </div>
                                                <div 
                                                    v-for="(task, index) in tasks.filter(t => t.is_cost == 0)"
                                                    :key="task.id"
                                                    class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> {{ task.measure }} </span>
                                                    <span class="subtitle-detail" v-if="task.description">{{ task.description }}</span>
                                                    <span class="subtitle-detail">{{ formatNumber(task.cost ?? 0) }} {{ currency_purchase }}</span>
                                                </div>
                                            </div>
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail font-weight-bold"> Totala kostnader </span>
                                                    <span class="subtitle-detail font-weight-bold">
                                                        {{ formatNumber((tasks ?? []).filter(t => t.is_cost == 1).reduce((sum, item) => sum + parseFloat(item.cost), 0)) }} {{ currency_purchase }}
                                                    </span>
                                                    <VDivider />
                                                </div>
                                                <div 
                                                    v-for="(task, index) in tasks.filter(t => t.is_cost == 1)"
                                                    :key="task.id"
                                                    class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> {{ task.measure }} </span>
                                                    <span class="subtitle-detail" v-if="task.description">{{ task.description }}</span>
                                                    <span class="subtitle-detail">{{ formatNumber(task.cost ?? 0) }} {{ currency_purchase }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="5" v-if="state_id === 12">
                                    <VContainer fluid class="px-3">
                                        <div class="d-flex gap-6">
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Försäljningspris </span>
                                                    <span class="subtitle-detail">{{ formatNumber(vehicle.sale_price ?? 0) }} {{ currency_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> VMB / Moms</span>
                                                    <span class="subtitle-detail">{{ iva_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Varav moms </span>
                                                    <span class="subtitle-detail">{{ formatNumber(vehicle.iva_sale_amount ?? 0) }} {{ currency_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Prix ex moms </span>
                                                    <span class="subtitle-detail">{{ formatNumber(vehicle.iva_sale_exclusive ?? 0) }} {{ currency_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Rabatt </span>
                                                    <span class="subtitle-detail">{{ formatNumber(vehicle.discount ?? 0) }} {{ currency_sale }}</span>
                                                </div>                                    
                                            </div>
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Registreringsavgift</span>
                                                    <span class="subtitle-detail">{{ formatNumber(vehicle.registration_fee ?? 0) }} {{ currency_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Totalpris</span>
                                                    <span class="subtitle-detail">{{ formatNumber(vehicle.total_sale ?? 0) }} {{ currency_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Försäljningsdag</span>
                                                    <span class="subtitle-detail">{{ sale_date }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2" v-if="sale_comments">
                                                    <span class="title-detail"> Comments</span>
                                                    <span class="subtitle-detail">{{ sale_comments }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </VContainer>
                                </VWindowItem>
                                <VWindowItem value="6" v-if="state_id === 12">
                                    <VContainer fluid class="px-3">
                                        <div class="d-flex gap-6">
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Org/personnummer </span>
                                                    <span class="subtitle-detail">{{ organization_number_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Namn</span>
                                                    <span class="subtitle-detail">{{ fullname_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Adress</span>
                                                    <span class="subtitle-detail">{{ address_sale }}</span>
                                                </div>                                 
                                            </div>
                                            <div class="flex-1-1 d-flex flex-column gap-4">
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Postnr. ort</span>
                                                    <span class="subtitle-detail">{{ postal_code_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> Telefon</span>
                                                    <span class="subtitle-detail">{{ phone_sale }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="title-detail"> E-post</span>
                                                    <span class="subtitle-detail">{{ email_sale }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </VContainer>
                                </VWindowItem>
                            </VWindow>
                        </VCardText>   
                    </div>
                </div>
            </VCardText>
        </VCard>
    </VDialog>
</template>

<style lang="scss" scoped>
    .flex-1-1 {
        flex: 1 1 0;
    }

    .title-detail {
        font-weight: 400;
        font-size: 12px;
        line-height: 100%;
        letter-spacing: 0;
        color: #878787;
    }

    .subtitle-detail {
        font-weight: 400;
        font-size: 12px;
        line-height: 100%;
        color: #454545;
    }

    .title-vehicle {
        font-weight: 700;
        font-size: 32px;
        line-height: 100%;
        color: #454545;

        @media (max-width: 1023px) {
            font-size: 24px
        }
    }

    .subtitle-vehicle {
        font-weight: 400;
        font-size: 16px;
        line-height: 100%;
        color: #454545;
    }

    .vehicle-items-container {
        @media (max-width: 1023px) {
            .vehicle-item {
                flex: 1 1 calc(50% - 8px);
                max-width: calc(50% - 8px);
            }
        }
    }

    .vehicle-item {
        flex: 1 1;
        border-radius: 8px;
        padding: 16px;

        img {
            margin-bottom: 10px;    
        }

        .vehicle-item-title {
            margin-bottom: 5px;
            font-family: "Blauer Nue";
            font-weight: 400;
            font-size: 14px;
            line-height: 16px;
            color: #0C5B27;
;
        }

        .vehicle-item-text {
            font-family: "Blauer Nue";
            font-weight: 700;
            font-size: 14px;
            line-height: 16px;
            color: #0C5B27;
        }
    }

    .v-tabs.vehicles-tabs {
        :deep(.v-btn) {
            background-color: #F6F6F6 !important;
            min-width: 50px !important;
            .v-btn__content {
                font-size: 14px !important;
                color: #454545;
            }
        }
        :deep(.v-slide-group__prev),
        :deep(.v-slide-group__next) {
            display: none !important;
        }
    }

    @media (max-width: 776px) {
        .v-tabs.vehicles-tabs {
            :deep(.v-icon) {
                display: none !important;
            }
            :deep(.v-btn) {
                 background-color: #FFFFFF !important;
                .v-btn__content {
                    white-space: break-spaces;
                }
            }
        }
    }
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

    .radio-form {
        display: flex;
        align-items: center;
        height: 24px;

        :deep(.v-selection-control-group) {
            gap: 8px;
        }

        :deep(.v-selection-control) {
            min-height: auto;
        }

        :deep(.v-selection-control--dirty) {
            .v-selection-control__input > .v-icon {
                color: #00E1E2 !important;
            }
        }

        :deep(.v-label) {
            color: #5D5D5D;
            font-size: 12px;
            opacity: 1;
        }
    }

</style>
<style lang="scss" scoped>
    :deep(.radio-form .v-input--density-comfortable), :deep(.v-radio) {
        --v-input-control-height: 0 !important;
    }

    :deep(.radio-form) {
        @media (max-width: 1023px) {
            .v-selection-control-group  {
                .v-radio:not(:last-child) {
                    margin-inline-end: 0.1rem !important;
                } 
            }
        }
    }

    :deep(.radio-form .v-selection-control__wrapper) {
        height: 20px !important;
    }

    :deep(.radio-form .v-icon--size-default) {
        font-size: calc(var(--v-icon-size-multiplier) * 1em) !important;
    }

    :deep(.radio-form .v-selection-control--dirty) {
        .v-selection-control__input > .v-icon {
            color: #00E1E2 !important;
        }
    }

    :deep(.radio-form .v-label) {
        color: #5D5D5D;
        font-size: 12px;
    }
</style>
