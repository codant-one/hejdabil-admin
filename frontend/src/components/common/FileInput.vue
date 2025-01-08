<script setup>

import { useDropZone, useFileDialog, useObjectUrl } from '@vueuse/core'

const props = defineProps({
    images: {
        type: Object,
        required: false
    }
})

const emit = defineEmits([
    'files'
])

const { open, onChange } = useFileDialog({ accept: 'image/*' })
const dropZoneRef = ref()
const fileData = ref([])

function onDrop(DroppedFiles) {
  DroppedFiles?.forEach(file => {
    if (file.type.slice(0, 6) !== 'image/') {
      alert('Sólo se permiten archivos de imagen')
      
      return
    }
    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
    })
  })
}

onChange(selectedFiles => {
  if (!selectedFiles)
    return
  for (const file of selectedFiles) {
    onImageSelected(file)
  }

  emit('files', fileData.value)
})

watchEffect(() => {

    if (typeof props.images !== 'undefined') {
        fileData.value = props.images
        emit('files', fileData.value)
    }
})


const onImageSelected = (file) => {

  URL.createObjectURL(file)

  resizeImage(file, 400, 400, 0.9)
    .then(async blob => {
        fileData.value.push({
            file,
            url: useObjectUrl(file).value ?? '',
            blob: blob
        })
    })
}

const resizeImage = function(file, maxWidth, maxHeight, quality) {
  return new Promise((resolve, reject) => {
    const img = new Image()

    img.src = URL.createObjectURL(file)
    img.onload = () => {
      const canvas = document.createElement('canvas')
      const ctx = canvas.getContext('2d')

      let width = img.width
      let height = img.height

      if (maxWidth && width > maxWidth) {
        height *= maxWidth / width
        width = maxWidth
      }

      if (maxHeight && height > maxHeight) {
        width *= maxHeight / height
        height = maxHeight
      }

      canvas.width = width
      canvas.height = height

      ctx.drawImage(img, 0, 0, width, height)

      canvas.toBlob(blob => {
        resolve(blob)
      }, file.type, quality)
    }
    img.onerror = error => {
      reject(error)
    }
  })
}

useDropZone(dropZoneRef, onDrop)


</script>

<template>
    <section>
        <VCard class="mb-6">
            <VCardItem>
                <template #title> Galería</template>
            </VCardItem>

            <VCardText>
                <div class="flex">
                    <div class="w-full h-auto relative">
                        <div
                            ref="dropZoneRef"
                            class="cursor-pointer"
                            @click="() => open()"
                        >
                            <div
                                v-if="fileData?.length === 0"
                                class="d-flex flex-column justify-center align-center gap-y-3 px-6 py-10 border-dashed drop-zone"
                            >
                                <VBtn
                                    variant="tonal"
                                    class="rounded-sm"
                                >
                                    <VIcon icon="tabler-upload" />
                                </VBtn>
                                <div class="text-base text-high-emphasis font-weight-medium">
                                    Arrastra y suelta tu imagen aquí.
                                </div>
                            </div>

                            <div
                              v-else
                              class="d-flex justify-center align-center gap-3 pa-8 border-dashed drop-zone flex-wrap"
                            >
                                <VRow class="match-height w-100">
                                    <template
                                        v-for="(item, index) in fileData"
                                        :key="index"
                                    >
                                        <VCol
                                            cols="12"
                                            sm="4"
                                        >
                                            <VCard
                                                :ripple="false"
                                                border
                                            >
                                                <VCardText
                                                    class="d-flex flex-column"
                                                    @click.stop
                                                >
                                                    <VImg
                                                        :src="item.url"
                                                        width="200px"
                                                        height="150px"
                                                        class="w-100 mx-auto"
                                                    />
                                                    <div class="mt-2">
                                                        <span class="clamp-text text-wrap me-3">
                                                            {{ item.file.name }}
                                                        </span>
                                                        <span>
                                                            {{ item.file.size / 1000 }} KB
                                                        </span>
                                                    </div>
                                                </VCardText>
                                                <VSpacer />
                                                <VCardActions>
                                                    <VBtn
                                                        variant="outlined"
                                                        block
                                                        @click.stop="fileData.splice(index, 1)"
                                                    >
                                                        Eliminar Archivo
                                                    </VBtn>
                                                </VCardActions>
                                            </VCard>
                                        </VCol>
                                    </template>
                                </VRow>
                            </div>
                        </div>
                    </div>
                </div>
            </VCardText>
        </VCard>
    </section>
</template>

<style lang="scss" scoped>
    .drop-zone {
        border: 2px dashed rgba(var(--v-theme-on-surface), 0.12);
        border-radius: 6px;
    }
</style>

