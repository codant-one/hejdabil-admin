<script setup>

import { useMouse } from '@vueuse/core'
import { useTheme } from 'vuetify'
import { useGenerateImageVariant } from '@/@core/composable/useGenerateImageVariant'
import joinArrow from '@images/frontPages/icons/Join-community-arrow.png'
import heroDashboardImgDark from '@images/frontPages/hero-background.png'
import heroDashboardImgLight from '@images/frontPages/hero-background.png'
import heroElementsImgDark from '@images/frontPages/hero-logo.png'
import heroElementsImgLight from '@images/frontPages/hero-logo.png'

const theme = useTheme()
const heroElementsImg = useGenerateImageVariant(heroElementsImgLight, heroElementsImgDark)
const heroDashboardImg = useGenerateImageVariant(heroDashboardImgLight, heroDashboardImgDark)
const { x, y } = useMouse({ touch: false })

const emit = defineEmits([
  'register'
])

const translateMouse = computed(() => {
  if (typeof window !== 'undefined') {
    const rotateX = ref((window.innerHeight - 2 * y.value) / 100)
    
    return { transform: `perspective(1200px) rotateX(${ rotateX.value < -40 ? -20 : rotateX.value }deg) rotateY(${ (window.innerWidth - 2 * x.value) / 100 }deg) scale3d(1,1,1)` }
  }
})

</script>

<template>
  <div
    id="home"
    :style="{ background: 'rgb(var(--v-theme-surface))' }"
  >
    <div id="landingHero">
      <div
        class="landing-hero"
        :class="theme.current.value.dark ? 'landing-hero-dark-bg' : 'landing-hero-light-bg'"
      >
        <VContainer class="py-0">
          <div class="hero-text-box text-center px-6">
            <h1 class="text-h4 text-sm-h1 text-primary hero-title  font-weight-bold text-wrap mb-4">
              Registro Voluntariado por el Futuro
            </h1>
            <h5 class="mb-6 text-h5">
              Haz parte del futuro
            </h5>
            <div class="position-relative">
                <h6 class="position-absolute hero-btn-item d-md-flex d-none text-h6">
                    Transformaciones
                    <VImg
                        :src="joinArrow"
                        class="flip-in-rtl"
                        width="60"
                        height="42"
                    />
                </h6>
                <VBtn height="48" @click="$emit('register')">
                  REGISTRARSE
                </VBtn>
            </div>
          </div>
        </VContainer>
      </div>
    </div>

    <VContainer>
      <div class="position-relative">
        <div class="blank-section" />
        <div class="hero-animation-img position-absolute">
            <div
              class="hero-dashboard-img position-relative"
              :style="translateMouse"
            >
              <img
                :src="heroDashboardImg"
                alt="Hero Dashboard"
                class="animation-img"
              >
              <img
                :src="heroElementsImg"
                alt="hero elements"
                class="hero-elements-img animation-img position-absolute"
                style="transform: translateZ(1rem);"
              >
            </div>
        </div>
      </div>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>

    .landing-hero {
        border-radius: 0 0 50px 50px;
        padding-block-end: 15rem;
        padding-block-start: 3rem;
    }

    .hero-animation-img {
        position: absolute;
        inline-size: 90%;
        inset-inline-start: 50%;
        margin-inline: auto;
        transform: translateX(-50%);
        margin-top: -26rem;
    }

    section {
        display: block;
    }

    .blank-section {
        background-color: rgba(var(--v-theme-surface));
        min-block-size: 10rem;
    }

    @media (min-width: 1280px) and (max-width: 1440px)
    {

        .landing-hero {
            padding-block-end: 15rem;
        }

        .hero-animation-img {
            inset-block-start: 25rem;
        }
    }

    @media (min-width: 900px) and (max-width: 1279px)
    {
        .landing-hero{
            padding-block-end: 14rem;
        }

        .hero-animation-img{
            inset-block-start: 13rem;
            inline-size: 100%;
        }
    }

    @media (min-width: 768px) and (max-width: 899px)
    {

        .landing-hero{
            padding-block-end: 12rem;
        }

        .hero-animation-img {
            inset-block-start: 15rem;
            inline-size: 100%;
        }
    }

    @media (min-width: 600px) and (max-width: 767px)
    {

        .landing-hero {
            padding-block-end: 8rem;
        }

        .hero-animation-img {
            inset-block-start: 20rem;
            inline-size: 100%;
        }
    }

    @media (min-width: 425px) and (max-width: 600px)
    {

        .landing-hero {
            padding-block-end: 8rem;
        }

        .hero-animation-img{
            inset-block-start: 20rem;
            inline-size: 100%;
        }
    }

    @media (min-width: 300px) and (max-width: 424px)
    {

        .landing-hero {
            padding-block-end: 6rem;
        }

        .hero-animation-img {
            margin-top: -16rem;
            inline-size: 100%;
        }

        .hero-dashboard-img {
            width: 100% !important;
        }
    }

    .landing-hero::before{
        position: absolute;
        background-repeat: no-repeat;
        inset-block: 0;
        opacity: 0.5;
    }

    .landing-hero-dark-bg {
        background-color: #1e2130;
        background-image: url("@images/frontPages/backgrounds/hero-bg-dark.png");
        background-position: center;
        background-repeat: no-repeat;
    }

    .landing-hero-light-bg{
        background-image: linear-gradient(138.18deg, #eae8fd 0%, #f8efdc 94.44%);
    }

    @media (min-width: 600px)
    {
        .hero-text-box {
            inline-size: 35rem;
            margin-block-end: 1rem;
            margin-inline: auto;
        }
    }

    @media (max-width: 991px)
    {
        .hero-elements-img {
            margin-top: 15rem;
        }
    }

    .hero-title {
        animation: shine 2s ease-in-out infinite alternate;
        background: linear-gradient(to right, #08714d 0%, #1440ad 47.92%, #e63022 100%);
        //  stylelint-disable-next-line property-no-vendor-prefix
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: rgba(0,0,0,0%);
    }

    @keyframes shine {
        0% {
            background-position: 0% 50%
        }

        80% {
            background-position: 50% 90%
        }

        100% {
            background-position: 91% 100%
        }
    }

    .hero-dashboard-img {
        margin-block: 0;
        margin-inline: auto;
        transform-style: preserve-3d;
        transition: all 0.35s;
        width: 75%;
        
        img {
            inline-size: 100%;
        }
    }

    .hero-elements-img {
        position: absolute;
        inset-block: 0;
        inset-inline-start: 0;
    }

    .feature-cards {
        margin-block-start: 6.25rem;
    }

    .hero-btn-item{
        inset-block-start: 80%;
        inset-inline-start: 0;
    }
</style>
