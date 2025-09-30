<!-- src/pages/sign/[token].vue -->
<script setup>
import { ref, onMounted, nextTick } from 'vue'
import VuePdfEmbed from 'vue-pdf-embed'
import SignaturePad from 'signature_pad'
import axios from '@/plugins/axios'
import { useTheme } from 'vuetify'
import { themeConfig } from '@themeConfig'

const props = defineProps({
  token: {
    type: String,
    required: true,
  },
})

// --- Refs de Estado (Ahora más simples) ---
const isLoading = ref(true)
const pdfSource = ref(null)
const signaturePlacement = ref({ x: 0, y: 0, visible: false }) // Coordenadas fijas del admin
const isSignatureModalVisible = ref(false)
const signaturePad = ref(null)
const finalState = ref(null)
const isSubmitting = ref(false)

// --- Refs de Elementos del DOM ---
const signatureCanvas = ref(null)

// --- Lógica del Tema (para el lienzo de firma) ---
const { global } = useTheme()

// --- Funciones del Componente (Refactorizadas) ---

// Carga tanto el PDF como los detalles de la firma
const loadSignatureData = async () => {
  isLoading.value = true
  try {
    // Usamos Promise.all para hacer ambas peticiones en paralelo
    const [detailsResponse, pdfResponse] = await Promise.all([
      axios.get(`/signatures/${props.token}/details`),
      axios.get(`/signatures/${props.token}/get-unsigned-pdf`, { responseType: 'blob' })
    ]);

    // Guardamos las coordenadas que el admin eligió
    signaturePlacement.value = {
      x: detailsResponse.data.placement_x,
      y: detailsResponse.data.placement_y,
      visible: true,
    }

    // Creamos la URL del PDF y la guardamos
    pdfSource.value = URL.createObjectURL(pdfResponse.data);

  } catch (error) {
    console.error("Kunde inte ladda signeringsdata:", error);
    finalState.value = {
      type: 'error',
      icon: 'mdi-alert-circle-outline',
      title: 'Fel',
      message: 'Kunde inte ladda dokumentet. Länken kan vara ogiltig eller ha löpt ut.',
    }
  } finally {
    isLoading.value = false;
  }
}

// Abre el diálogo para que el cliente dibuje su firma
const openSignatureModal = () => {
  isSignatureModalVisible.value = true
  nextTick(() => {
    const canvas = signatureCanvas.value;
    if (canvas) {
      signaturePad.value = new SignaturePad(canvas, {
        backgroundColor: 'rgba(0, 0, 0, 0)',
        penColor: global.name.value === 'dark' ? 'rgb(220, 220, 240)' : 'rgb(0, 0, 0)',
      });
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      canvas.width = canvas.offsetWidth * ratio;
      canvas.height = canvas.offsetHeight * ratio;
      canvas.getContext("2d").scale(ratio, ratio);
      signaturePad.value.clear();
    }
  });
}

// Cierra el diálogo y procesa la firma
const closeSignatureModal = (accepted) => {
  isSignatureModalVisible.value = false;
  if (accepted && signaturePad.value && !signaturePad.value.isEmpty()) {
    submitFinalSignature(signaturePad.value.toDataURL('image/png'));
  }
}

// Envía la firma al backend (AHORA NO ENVÍA COORDENADAS)
const submitFinalSignature = async (signatureImage) => {
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  try {
    const payload = {
      signature: signatureImage, // Solo la imagen de la firma
    };
    const response = await axios.post(`/signatures/submit/${props.token}`, payload);

    finalState.value = {
      type: 'success',
      icon: 'mdi-check-circle-outline',
      title: 'Signering slutförd!',
      message: response.data.message,
      downloadUrl: response.data.download_url,
    };
  } catch (error) {
    finalState.value = {
      type: 'error',
      icon: 'mdi-alert-circle-outline',
      title: 'Ett fel uppstod',
      message: error.response?.data?.message || 'Kunde inte slutföra signeringen.',
    };
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(loadSignatureData);
</script>

<template>
  <div class="signing-container">
    <!-- Estado de carga y final -->
    <VCard v-if="isLoading || finalState" class="pa-8 text-center" max-width="500">
      <VProgressCircular v-if="isLoading" indeterminate color="primary" size="64" />
      <div v-if="finalState">
        <VIcon :icon="finalState.icon" :color="finalState.type" size="64" class="mb-4" />
        <VCardTitle class="text-h5">{{ finalState.title }}</VCardTitle>
        <VCardText>{{ finalState.message }}</VCardText>
        <VBtn v-if="finalState.downloadUrl" :href="finalState.downloadUrl" target="_blank" color="success" class="mt-4" prepend-icon="mdi-cloud-download-outline">
          Ladda ner signerat avtal
        </VBtn>
      </div>
    </VCard>

    <!-- Visor de PDF cuando la carga ha finalizado -->
    <VCard v-if="!isLoading && !finalState" class="signing-card">
      <VToolbar density="compact" color="surface">
        <VToolbarTitle class="text-subtitle-1">
          Vänligen signera dokumentet
        </VToolbarTitle>
      </VToolbar>
      <VDivider />
      
      <div class="pdf-container">

      <!-- ESTE ES EL NUEVO DIV WRAPPER -->
      <!-- Se encarga del posicionamiento. Su altura se ajustará al contenido del PDF -->
      <div style="position: relative;">

          <vue-pdf-embed :source="pdfSource" />

          <!-- Ahora, este placeholder se posiciona relativo al nuevo wrapper, que tiene la altura total del PDF -->
          <div 
            v-if="signaturePlacement.visible"
            class="signature-placeholder"
            :style="{left: signaturePlacement.x + '%', 
                    top: signaturePlacement.y + '%'}"
            @click.stop="openSignatureModal"
          >
            <VIcon icon="mdi-draw" />
            <span>Signera här</span>
          </div>

      </div>
      </div>
    </VCard>

    <!-- Diálogo para firmar (el lienzo) -->
    <VDialog v-model="isSignatureModalVisible" persistent max-width="500">
      <VCard>
        <VCardTitle>Rita din signatur</VCardTitle>
        <VCardText>
          <div class="signature-pad-wrapper">
            <canvas ref="signatureCanvas"></canvas>
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" @click="closeSignatureModal(false)">Avbryt</VBtn>
          <VBtn color="primary" @click="closeSignatureModal(true)" :disabled="isSubmitting" :loading="isSubmitting">Acceptera</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.signing-container {
  display: flex;
  justify-content: center;
  align-items: center; /* Centrado vertical y horizontal */
  padding: 2rem;
  min-height: 100vh;
  background-color: rgb(var(--v-theme-surface-variant));
}
.signing-card {
  width: 100%;
  max-width: 900px;
  height: 90vh;
  display: flex;
  flex-direction: column;
}
.pdf-container {
  position: relative;
  flex-grow: 1;
  overflow: auto; /* Permite scroll en el PDF */
}
.signature-placeholder {
  position: absolute;
  border: 2px dashed rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.1);
  border-radius: 8px;
  padding: 8px 12px;
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
  z-index: 10;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.signature-pad-wrapper {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 4px;
}
canvas {
  width: 100%;
  height: 200px;
  display: block;
}
</style>

<route lang="yaml">
  meta:
    layout: blank
    redirectIfLoggedIn: false
    public: true    # opcional, claro indicador de que es pública
</route>