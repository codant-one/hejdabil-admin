<!-- src/pages/sign/[token].vue -->
<script setup>
import { ref, onMounted, nextTick } from 'vue'
import VuePdfEmbed from 'vue-pdf-embed'
import SignaturePad from 'signature_pad'
import axios from '@/plugins/axios'

const props = defineProps({ token: { type: String, required: true } })

// Refs de estado
const isLoadingPdf = ref(true)
const pdfSource = ref(null)
const signatureBox = ref({ x: 0, y: 0, visible: false })
const signatureData = ref({ img: null, x: 0, y: 0, page: 1 })
const isSignatureModalVisible = ref(false)
const signaturePad = ref(null)
const finalState = ref(null)
const isSubmitting = ref(false)

// Refs de elementos del DOM
const signatureCanvas = ref(null)
const pdfContainer = ref(null)

// --- Lógica Principal ---
const fetchUnsignedPdf = async () => {
  try {
    const response = await axios.get(`/signatures/${props.token}/get-unsigned-pdf`, {
      responseType: 'blob',
    });
    pdfSource.value = URL.createObjectURL(response.data);
  } catch (error) {
    console.error("Kunde inte ladda PDF:", error);
    // ...
  } finally {
    isLoadingPdf.value = false;
  }
}

const handlePdfClick = (event) => {
  if (signatureData.value.img) return; // No hacer nada si ya se firmó
  const rect = pdfContainer.value.getBoundingClientRect();
  signatureBox.value = {
    x: event.clientX - rect.left - 50, // Centrar el recuadro
    y: event.clientY - rect.top - 25,
    visible: true,
  }
}

const openSignatureModal = () => {
  isSignatureModalVisible.value = true
  nextTick(() => {
    const canvas = signatureCanvas.value
    if (canvas) {
      signaturePad.value = new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)' });
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      canvas.width = canvas.offsetWidth * ratio;
      canvas.height = canvas.offsetHeight * ratio;
      canvas.getContext("2d").scale(ratio, ratio);
      signaturePad.value.clear();
    }
  });
}

const closeSignatureModal = (accepted) => {
  if (accepted && !signaturePad.value.isEmpty()) {
    signatureData.value = {
      img: signaturePad.value.toDataURL('image/png'),
      x: signatureBox.value.x,
      y: signatureBox.value.y,
      page: 1, // Asumimos página 1 por ahora
    }
    signatureBox.value.visible = false;
  }
  isSignatureModalVisible.value = false;
}

const submitFinalSignature = async () => {
  isSubmitting.value = true;
  try {
    const payload = {
      signature: signatureData.value.img,
      x: signatureData.value.x,
      y: signatureData.value.y,
      page: signatureData.value.page,
    };
    const response = await axios.post(`/signatures/submit/${props.token}`, payload);
    // ... tu lógica de estado final ...
  } catch (error) {
    // ... tu lógica de error ...
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(fetchUnsignedPdf)
</script>
<template>
  <div class="signing-container">
    <VCard v-if="!finalState" class="signing-card">
      <VCardTitle class="text-h6">
        Signera Dokument
        <VSpacer />
        <VBtn
          color="primary"
          :disabled="!signatureData.img"
          :loading="isSubmitting"
          @click="submitFinalSignature"
        >
          Slutför och skicka
        </VBtn>
      </VCardTitle>
      <VDivider />

      <VProgressLinear v-if="isLoadingPdf" indeterminate color="primary" />
      
      <div v-if="!isLoadingPdf" ref="pdfContainer" class="pdf-container" @click="handlePdfClick">
        <!-- Capa para el PDF renderizado -->
        <vue-pdf-embed :source="pdfSource" ref="pdfEmbed" />

        <!-- Recuadro movible para la firma -->
        <div 
          v-if="signatureBox.visible"
          class="signature-placeholder"
          :style="{ left: signatureBox.x + 'px', top: signatureBox.y + 'px' }"
          @click.stop="openSignatureModal"
        >
          <VIcon icon="mdi-draw" />
          <span>Signera här</span>
        </div>

        <!-- La firma ya colocada -->
        <img 
          v-if="signatureData.img"
          :src="signatureData.img"
          class="placed-signature"
          :style="{ left: signatureData.x + 'px', top: signatureData.y + 'px' }"
        />
      </div>
    </VCard>

    <!-- Estado final (éxito o error) -->
    <VCard v-if="finalState" class="pa-8 text-center" max-width="500">
        <!-- ... tu código de estado final existente ... -->
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
          <VBtn color="primary" @click="closeSignatureModal(true)">Acceptera</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
<style scoped>
.signing-container { display: flex; justify-content: center; align-items: flex-start; padding-top: 2rem; min-height: 100vh; background-color: #f0f2f5; }
.signing-card { width: 90%; max-width: 850px; }
.pdf-container { position: relative; cursor: pointer; }
.signature-placeholder {
  position: absolute;
  border: 2px dashed #007bff;
  background-color: rgba(0, 123, 255, 0.1);
  border-radius: 8px;
  padding: 8px 12px;
  color: #007bff;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  z-index: 10;
}
.placed-signature { position: absolute; width: 200px; z-index: 5; }
.signature-pad-wrapper { border: 1px solid #ccc; }
canvas { width: 100%; height: 200px; }
</style>

<route lang="yaml">
meta:
  layout: blank
</route>