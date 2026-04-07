<script setup>

const props = defineProps({
    variant: {
        type: String,
        default: 'info',
        validator: 
            value => [
                'success', 
                'info', 
                'warning', 
                'error'
            ].includes(value),
    },
    title: {
        type: String,
        default: '',
    },
    text: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: '',
    },
})

const variantMap = {
   success: {
      bg: '#D8FFE4',
      border: '#BDD2C8',
      iconColor: '#6E9383',
   },
   info: {
      bg: '#E3F2FD',
      border: '#6E9383',
      iconColor: '#1890FF',
   },
   warning: {
      bg: '#FFF8E1',
      border: '#6E9383',
      iconColor: '#FAAD14',
   },
   error: {
      bg: '#FFEBEE',
      border: '#6E9383',
      iconColor: '#FF4D4F',
   },
}

const currentVariant = computed(() => variantMap[props.variant] ?? variantMap.info)

</script>

<template>
    <div
        class="inline-banner"
        :style="{
            '--ib-bg': currentVariant.bg,
            '--ib-border': currentVariant.border
        }"
        role="status"
        aria-live="polite"
    >
       <VIcon :icon="props.icon" size="24" :color="currentVariant.iconColor"/>

        <div class="inline-banner__content">
            <div v-if="title || $slots.title" class="inline-banner__title">
                <slot name="title">{{ title }}</slot>
            </div>

            <div v-if="text || $slots.default" class="inline-banner__text">
                <slot>{{ text }}</slot>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
    .inline-banner {
        display: flex;
        align-items: center;
        gap: 16px;
        width: 100%;
        padding: 8px 16px;
        border-radius: 8px;
        background: var(--ib-bg);
        border: 1px solid var(--ib-border);
        height: 54px;
    }

    .inline-banner__content {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .inline-banner__title {
        margin: 0;
        color: #5D5D5D;
        font-weight: 600;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 0px;
        vertical-align: middle;
    }

    .inline-banner__text {
        margin: 0;
        color: #5D5D5D;
        font-weight: 400;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 0px;
        vertical-align: middle;
    }
</style>