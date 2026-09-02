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
    bgColor: {
        type: String,
        default: '',
    },
    borderColor: {
        type: String,
        default: '',
    },
    iconColor: {
        type: String,
        default: '',
    },
})

const variantMap = {
    success: {
        bg: '#FFFFFF',
        border: '#00EEB0',
        iconColor: '#00EEB0',
    },
    info: {
        bg: '#FFFFFF',
        border: '#1890FF',
        iconColor: '#1890FF',
    },
    warning: {
        bg: '#FFFFFF',
        border: '#FAAD14',
        iconColor: '#FAAD14',
    },
    error: {
        bg: '#FFFFFF',
        border: '#FF4D4F',
        iconColor: '#FF4D4F',
    },
}

const currentVariant = computed(() => variantMap[props.variant] ?? variantMap.info)

const resolvedStyles = computed(() => ({
    '--ia-bg': props.bgColor || currentVariant.value.bg,
    '--ia-border': props.borderColor || currentVariant.value.border,
    '--ia-icon': props.iconColor || currentVariant.value.iconColor,
}))

</script>

<template>
    <VAlert
        class="inline-alert"
        variant="flat"
        :icon="false"
        :style="resolvedStyles"
        role="status"
        aria-live="polite"
    >
        <div class="d-flex gap-4 justify-content-center align-center">
            <VIcon v-if="props.icon" :icon="props.icon" size="24" class="inline-alert__icon" />

            <div class="inline-banner__content">
                <div v-if="title || $slots.title" class="inline-banner__title">
                    <slot name="title">{{ title }}</slot>
                </div>

                <div v-if="$slots.default" class="inline-banner__text">
                    <slot />
                </div>

                <div v-else-if="text" class="inline-banner__text" v-html="text" />
            </div>
        </div>
    </VAlert>
</template>

<style scoped lang="scss">
    .inline-alert {
        width: 100%;
        min-height: 54px;
        border-radius: 8px !important;
        background: var(--ia-bg) !important;
        box-shadow: 0px 4px 16px 0px rgba(0, 0, 0, 0.15) !important;
        border: none !important;
        border-left: 3px solid var(--ia-border) !important;
        padding: 16px !important;
    }

    :deep(.inline-alert .v-alert__content) {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 0 !important;
        color: #878787 !important;
        font-size: 16px;
        line-height: 20px;
    }

    :deep(.inline-alert .v-alert__prepend) {
        display: none !important;
    }

    .inline-alert__icon {
        color: var(--ia-icon);
        flex: 0 0 24px;
        margin-top: 1px;
    }

    .inline-banner__content {
        width: 100%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .inline-banner__title {
        margin: 0;
        color: #5D5D5D;
        font-weight: 600;
        font-size: 16px;
        line-height: 16px;
        letter-spacing: 0;
    }

    .inline-banner__text {
        margin: 0;
        color: #878787;
        font-weight: 400;
        font-size: 14px;
        line-height: 16px;
        letter-spacing: 0;
    }
</style>