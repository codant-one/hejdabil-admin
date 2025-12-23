<script setup>
defineOptions({
  name: 'AppAutocomplete',
  inheritAttrs: false,
})

const menuOpen = ref(false)
const autocompleteRef = ref(null)
const isClickFromToggle = ref(false)

// Toggle menu when clicking on the menu icon
const handleMenuIconClick = () => {
  isClickFromToggle.value = true
  menuOpen.value = !menuOpen.value
}

// Handle focus - only open if not triggered by toggle click
const handleFocus = () => {
  if (!isClickFromToggle.value) {
    menuOpen.value = true
  }
  isClickFromToggle.value = false
}

// Get the menu-icon from attrs or use default
const menuIcon = computed(() => {
  const attrs = useAttrs()
  return attrs['menu-icon'] || attrs.menuIcon || 'tabler-chevron-down'
})

// const { class: _class, label, variant: _, ...restAttrs } = useAttrs()
const elementId = computed(() => {
  const attrs = useAttrs()
  const _elementIdToken = attrs.id || attrs.label
  
  return _elementIdToken ? `app-autocomplete-${ _elementIdToken }-${ Math.random().toString(36).slice(2, 7) }` : undefined
})

const label = computed(() => useAttrs().label)
</script>

<template>
  <div
    class="app-autocomplete flex-grow-1"
    :class="$attrs.class"
  >
    <VLabel
      v-if="label"
      :for="elementId"
      class="mb-1 text-body-2 text-high-emphasis"
      :text="label"
    />
    <VAutocomplete
      ref="autocompleteRef"
      v-model:menu="menuOpen"
      v-bind="{
        ...$attrs,
        class: null,
        label: undefined,
        id: elementId,
        menuIcon: '',
        menuProps: {
          contentClass: [
            'app-inner-list',
            'app-autocomplete__content',
            'v-autocomplete__content',
          ],
        },
      }"
      @focus="handleFocus"
    >
      <template #append-inner>
        <VIcon 
          :icon="menuIcon" 
          class="cursor-pointer transition-icon"
          :class="{ 'rotate-180': menuOpen }"
          @click.stop.prevent="handleMenuIconClick"
        />
      </template>
      <template
        v-for="(_, name) in $slots"
        #[name]="slotProps"
      >
        <slot
          :name="name"
          v-bind="slotProps || {}"
        />
      </template>
    </VAutocomplete>
  </div>
</template>

<style lang="scss">
  .app-autocomplete {
    .transition-icon {
      transition: transform 0.2s ease-in-out;
    }

    .rotate-180 {
      transform: rotate(180deg);
    }

    .v-field--focused {
      box-shadow: none !important;
    }
  }
</style>
