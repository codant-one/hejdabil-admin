<script setup>
import { nextTick, unref, useAttrs, watch } from 'vue'
import { useDisplay } from 'vuetify'
import { scrollElementIntoScrollableParent } from '@/@core/composable/useMobilePaginationScroll'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: undefined,
  },
  targetRef: {
    type: Object,
    default: null,
  },
  mobileOnly: {
    type: Boolean,
    default: true,
  },
  offset: {
    type: Number,
    default: 16,
  },
  behavior: {
    type: String,
    default: 'smooth',
  },
})

const emit = defineEmits(['update:modelValue'])

const attrs = useAttrs()
const { mdAndDown } = useDisplay()

const shouldScroll = () => !props.mobileOnly || mdAndDown.value

const scrollToTarget = async () => {
  if (!shouldScroll())
    return

  const element = unref(props.targetRef)
  if (!element)
    return

  await nextTick()
  scrollElementIntoScrollableParent({
    element,
    offset: props.offset,
    behavior: props.behavior,
  })
}

watch(() => props.modelValue, async (newValue, oldValue) => {
  if (newValue === oldValue)
    return

  await scrollToTarget()
})

const onUpdateModelValue = value => {
  emit('update:modelValue', value)
}
</script>

<template>
  <VTabs
    v-bind="attrs"
    :model-value="modelValue"
    @update:modelValue="onUpdateModelValue"
  >
    <slot />
  </VTabs>
</template>