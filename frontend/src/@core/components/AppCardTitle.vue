<script setup>
import AppHeaderBg from '@images/pages/app-search-header-bg.png'

const props = defineProps({
  title: {
    type: String,
    required: false,
  },
  subtitle: {
    type: String,
    required: false,
  },
  customClass: {
    type: String,
    required: false,
  }
})

defineOptions({ inheritAttrs: false })
</script>

<template>
    <!-- 👉 Banner  -->
    <VCard
        flat
        class="text-center card-header"
        :class="props.customClass"
        :style="`background: url(${AppHeaderBg});`"
    >
        <VCardText>

            <VCardTitle class="text-h5 font-weight-semibold">
                {{ props.title }}
            </VCardTitle>

            <p class="mb-0 mt-3">
                {{ props.subtitle }}
            </p>
        </VCardText>
    </VCard>
</template>

<style lang="scss">
.card-header {
  padding: 4rem !important;
  background-size: cover !important;
}


@media (max-width: 37.5rem) {
  .card-header {
    padding: 1.5rem !important;
  }
}
</style>
