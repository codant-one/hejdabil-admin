<script setup>

import avatar1 from "@/assets/images/avatars/1.svg";
import avatar2 from "@/assets/images/avatars/2.svg";
import avatar3 from "@/assets/images/avatars/3.svg";
import avatar4 from "@/assets/images/avatars/4.svg";
import avatar5 from "@/assets/images/avatars/5.svg";
import avatar6 from "@/assets/images/avatars/6.svg";

const props = defineProps({
  avatarId: {
    type: [String, Number, null],
    default: null,
    required: true
  },
  alt: {
    type: String,
    default: "Avatar",
    required: false
  },
  radius: {
    type: [String, Number],
    default: 50,
    required: false
  },
});

const avatarById = {
  1: avatar1,
  2: avatar2,
  3: avatar3,
  4: avatar4,
  5: avatar5,
  6: avatar6,
};

const src = computed(() => {
  const resolvedId = Number(props.avatarId);
  return avatarById[resolvedId] || avatar5;
});
</script>

<template>
  <VImg :style="{ borderRadius: props.radius + '%' }" :src="src" :alt="props.alt" />
</template>
