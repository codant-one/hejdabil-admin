<!-- src/pages/sign/[token].vue -->
<template>
    <div class="signing-container">
      <VCard class="pa-6 pa-md-8 mx-auto" max-width="800">
        <!-- Logo de la aplicación (opcional, pero recomendado) -->
        <VImg :src="themeConfig.app.logo" max-height="40" class="mx-auto mb-6" />
  
        <!-- Estado inicial: Formulario de firma -->
        <div v-if="!finalState">
          <VCardTitle class="text-h5 text-center">Signera avtal</VCardTitle>
          <VCardSubtitle class="text-center mb-6">
            Rita din signatur i rutan nedan och tryck sedan på "Acceptera och signera".
          </VCardSubtitle>
          
          <div class="signature-pad-wrapper">
            <canvas ref="signatureCanvas"></canvas>
          </div>
  
          <div class="d-flex justify-center gap-4 mt-6">
            <VBtn @click="clearSignature" variant="tonal" color="secondary">Rensa</VBtn>
            <VBtn @click="submitSignature" :loading="isSubmitting" :disabled="isSubmitting" color="primary">
              Acceptera och signera
            </VBtn>
          </div>
        </div>
  
        <!-- Estado final: Mensaje de éxito o error -->
        <div v-if="finalState" class="text-center">
          <VIcon 
            :icon="finalState.icon" 
            :color="finalState.type" 
            size="64" 
            class="mb-4" 
          />
          <VCardTitle class="text-h5">{{ finalState.title }}</VCardTitle>
          <VCardText>{{ finalState.message }}</VCardText>
          <VBtn 
            v-if="finalState.downloadUrl" 
            :href="finalState.downloadUrl" 
            target="_blank" 
            color="success" 
            class="mt-4"
            prepend-icon="mdi-cloud-download-outline"
          >
            Ladda ner signerat avtal
          </VBtn>
        </div>
      </VCard>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
  import SignaturePad from 'signature_pad'
  // Asegúrate de que tu instancia de axios esté bien importada. 
  // Si usas una configuración central, podría ser algo como '@axios'.
  import axios from '@/plugins/axios' 
  import { useTheme } from 'vuetify'
  import { themeConfig } from '@themeConfig'
  
  // defineProps recibe automáticamente los parámetros de la ruta gracias a la estructura de archivos.
  const props = defineProps({
    token: {
      type: String,
      required: true,
    },
  })
  
  // === Refs para el estado del componente ===
  const signaturePad = ref(null)
  const signatureCanvas = ref(null)
  const isSubmitting = ref(false)
  const finalState = ref(null) // Para mostrar el mensaje final (éxito/error)
  
  // === Lógica para el tema (oscuro/claro) ===
  const { global } = useTheme()
  
  // === Funciones del componente ===
  const initializeSignaturePad = () => {
    const canvas = signatureCanvas.value
    if (canvas) {
      signaturePad.value = new SignaturePad(canvas, {
        // Adapta los colores al tema actual de Vuetify
        backgroundColor: global.name.value === 'dark' ? 'rgb(30, 30, 47)' : 'rgb(255, 255, 255)',
        penColor: global.name.value === 'dark' ? 'rgb(220, 220, 240)' : 'rgb(0, 0, 0)',
      })
      resizeCanvas()
    }
  }
  
  const resizeCanvas = () => {
    const canvas = signatureCanvas.value
    if (canvas && signaturePad.value) {
      const ratio = Math.max(window.devicePixelRatio || 1, 1)
      canvas.width = canvas.offsetWidth * ratio
      canvas.height = canvas.offsetHeight * ratio
      canvas.getContext('2d').scale(ratio, ratio)
      signaturePad.value.clear() // Limpia la firma al redimensionar
    }
  }
  
  const clearSignature = () => {
    if (signaturePad.value) {
      signaturePad.value.clear()
    }
  }
  
  const submitSignature = async () => {
    if (!signaturePad.value || signaturePad.value.isEmpty()) {
      alert('Vänligen ange din signatur i rutan.')
      return
    }
  
    isSubmitting.value = true
    try {
      const signatureData = signaturePad.value.toDataURL('image/png')
      
      // Llamada a la API que definimos en api.php
      const response = await axios.post(`/signatures/submit/${props.token}`, {
        signature: signatureData,
      })
  
      // Actualiza el estado para mostrar el mensaje de éxito
      finalState.value = {
        type: 'success',
        icon: 'mdi-check-circle-outline',
        title: 'Signering slutförd!',
        message: response.data.message,
        downloadUrl: response.data.download_url,
      }
    } catch (error) {
      // Actualiza el estado para mostrar el mensaje de error
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
        title: 'Ett fel uppstod',
        message: error.response?.data?.message || 'Länken för signering är ogiltig, har löpt ut eller har redan använts.',
      }
      console.error("Error al enviar la firma:", error.response)
    } finally {
      isSubmitting.value = false
    }
  }
  
  // === Hooks del ciclo de vida ===
  onMounted(() => {
    // Espera a que el DOM esté completamente renderizado para inicializar el canvas
    nextTick(() => {
      initializeSignaturePad()
      window.addEventListener('resize', resizeCanvas)
    })
  })
  
  onBeforeUnmount(() => {
    // Limpia el evento al destruir el componente para evitar fugas de memoria
    window.removeEventListener('resize', resizeCanvas)
  })
  </script>
  
  <style scoped>
  .signing-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    /* Utiliza los colores del tema de Vuetify para el fondo */
    background-color: rgb(var(--v-theme-surface));
  }
  
  .signature-pad-wrapper {
    border: 2px dashed rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 6px;
    height: 250px; /* Altura del área de firma */
  }
  
  canvas {
    width: 100%;
    height: 100%;
    cursor: crosshair;
  }
  </style>
  
  <!-- ¡ESTA PARTE ES CRUCIAL! -->
  <route lang="yaml">
  meta:
    layout: blank
    action: read
    subject: public
  </route>