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
const signaturePlacement = ref({ x: null, y: null, isStatic: false, visible: false, alignment: 'left' }) // Coordenadas fijas del admin
const isSignatureModalVisible = ref(false)
const signaturePad = ref(null)
const finalState = ref(null)
const isSubmitting = ref(false)
const isAlreadySigned = ref(false)
const signedInfo = ref(null)
// --- Refs de Elementos del DOM ---
const signatureCanvas = ref(null)
const isRequestOngoing = ref(false)
// --- Lógica del Tema (para el lienzo de firma) ---
const { global } = useTheme()

// --- Funciones del Componente (Refactorizadas) ---

const checkTokenStatus = async () => {
  try {
    const response = await axios.get(`/signatures/${props.token}/status`)
    
    if (response.data.status === 'signed') {
      // El documento ya está firmado
      isAlreadySigned.value = true
      signedInfo.value = {
        signedAt: response.data.signed_date_formatted,
        agreementId: response.data.agreement_id,
        message: response.data.message
      }
      return true
    } else if (response.data.status === 'expired') {
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
        title: 'Länk utgången',
        message: 'Denna signeringslänk har löpt ut.',
      }
      return false
    }
    
    return response.data.status === 'sent'
  } catch (error) {
    console.error("Kunde inte verifiera token status:", error)
    if (error.response?.status === 404) {
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
        title: 'Ogiltig länk',
        message: 'Signeringslänken är ogiltig.',
      }
    }
    return false
  }
}

// Cargar el PDF según el estado
const loadPdf = async () => {
  try {
    let pdfResponse
    
    if (isAlreadySigned.value) {
      // Cargar el PDF firmado
      pdfResponse = await axios.get(`/signatures/${props.token}/get-signed-pdf`, { 
        responseType: 'blob' 
      })
    } else {
      // Cargar el PDF sin firmar
      pdfResponse = await axios.get(`/signatures/${props.token}/get-unsigned-pdf`, { 
        responseType: 'blob' 
      })
    }
    
    pdfSource.value = URL.createObjectURL(pdfResponse.data)
  } catch (error) {
    console.error("Kunde inte ladda PDF:", error)
    throw error
  }
}

// Cargar los detalles de posicionamiento de la firma (solo si no está firmado)
const loadSignatureDetails = async () => {
  try {
    const response = await axios.get(`/signatures/${props.token}/details`)
    
    const { placement_x, placement_y, signature_alignment } = response.data

    if (placement_x !== null && placement_y !== null) {
      signaturePlacement.value = {
        x: placement_x,
        y: placement_y,
        isStatic: false,
        visible: true,
        alignment: signature_alignment || 'left',
      }
    } else {
      signaturePlacement.value = {
        x: null, 
        y: null,
        isStatic: true,
        visible: true,
        alignment: signature_alignment || 'left',
      }
    }
  } catch (error) {
    console.error("Kunde inte ladda signeringsdetaljer:", error)
    throw error
  }
}

// Carga tanto el PDF como los detalles de la firma
const loadSignatureData = async () => {
  isLoading.value = true
  
  try {
    // Primero verificar el estado del token
    const canProceed = await checkTokenStatus()
    
    if (!canProceed && !isAlreadySigned.value) {
      isLoading.value = false
      return
    }
    
    // Cargar el PDF
    await loadPdf()
    
    // Solo cargar detalles de firma si no está firmado
    if (!isAlreadySigned.value) {
      await loadSignatureDetails()
    }
    
  } catch (error) {
    console.error("Ett fel uppstod:", error)
    if (!finalState.value) {
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
        title: 'Fel',
        message: 'Kunde inte ladda dokumentet.',
      }
    }
  } finally {
    isLoading.value = false
  }
}

// Abrir el diálogo de firma
const openSignatureModal = () => {
  if (isAlreadySigned.value) return // No permitir firmar si ya está firmado
  
  isSignatureModalVisible.value = true
  nextTick(() => {
    const canvas = signatureCanvas.value
    if (canvas) {
      signaturePad.value = new SignaturePad(canvas, {
        backgroundColor: 'rgba(0, 0, 0, 0)',
        penColor: global.name.value === 'dark' ? 'rgb(220, 220, 240)' : 'rgb(0, 0, 0)',
      })
      const ratio = Math.max(window.devicePixelRatio || 1, 1)
      canvas.width = canvas.offsetWidth * ratio
      canvas.height = canvas.offsetHeight * ratio
      canvas.getContext("2d").scale(ratio, ratio)
      signaturePad.value.clear()
    }
  })
}

// Cerrar el diálogo de firma
const closeSignatureModal = (accepted) => {
  isSignatureModalVisible.value = false
  if (accepted && signaturePad.value && !signaturePad.value.isEmpty()) {
    submitFinalSignature(signaturePad.value.toDataURL('image/png'))
  }
}

// Enviar la firma al backend
const submitFinalSignature = async (signatureImage) => {
  isRequestOngoing.value = true
  if (isSubmitting.value) return
  isSubmitting.value = true
  
  try {
    const payload = {
      signature: signatureImage,
    }
    const response = await axios.post(`/signatures/submit/${props.token}`, payload)

    finalState.value = {
      type: 'success',
      icon: 'mdi-check-circle-outline',
      title: 'Signering slutförd!',
      message: response.data.message,
      downloadUrl: response.data.download_url,
    }
  } catch (error) {
    finalState.value = {
      type: 'error',
      icon: 'mdi-alert-circle-outline',
      title: 'Ett fel uppstod',
      message: error.response?.data?.message || 'Kunde inte slutföra signeringen.',
    }
  } finally {
    isSubmitting.value = false
    isRequestOngoing.value = false
  }
}

// Descargar el PDF firmado
const downloadSignedPdf = () => {
  if (pdfSource.value) {
    const link = document.createElement('a')
    link.href = pdfSource.value
    link.download = `avtal-${signedInfo.value?.agreementId}-signerat.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
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

    <!-- Visor de PDF cuando ya está firmado -->
    <VCard v-if="!isLoading && !finalState && isAlreadySigned" class="signing-card">
      <!-- Banner informativo de documento firmado -->
      <VAlert
        type="success"
        variant="tonal"
        class="ma-4 mb-0 alert-no-shrink"
        prominent
      >
        <template #prepend>
          <VIcon icon="mdi-check-circle" size="32" />
        </template>
        <VAlertTitle class="text-h6">Dokumentet är redan signerat</VAlertTitle>
        <div>
          <p class="mb-2">Avtal #{{ signedInfo?.agreementId }} signerades {{ signedInfo?.signedAt }}</p>
          <VBtn
            color="success"
            variant="outlined"
            size="small"
            prepend-icon="mdi-download"
            class="mb-2"
            @click="downloadSignedPdf"
          >
            Ladda ner signerat dokument
          </VBtn>
        </div>
      </VAlert>
      
      <VDivider class="mt-4" />
      
      <!-- Visor del PDF firmado (solo lectura) -->
      <div class="pdf-container read-only">
        <div style="position: relative;">
          <vue-pdf-embed :source="pdfSource" />
        </div>
      </div>
    </VCard>

    <!-- Visor de PDF para firma (cuando no está firmado) -->
    <VCard v-if="!isLoading && !finalState && !isAlreadySigned" class="signing-card">
      <VToolbar density="compact" color="surface">
        <VToolbarTitle class="text-subtitle-1">
          Vänligen signera dokumentet
        </VToolbarTitle>
      </VToolbar>
      <VDivider />
      
      <div class="pdf-container">
        <div style="position: relative;">
          <vue-pdf-embed :source="pdfSource" />
          
          <!-- Placeholder de firma clickeable -->
          <div 
            v-if="signaturePlacement.visible"
            class="signature-placeholder"
            :class="{ 
              'static-signature-position': signaturePlacement.isStatic,
              'align-left': signaturePlacement.isStatic && signaturePlacement.alignment === 'left',
              'align-right': signaturePlacement.isStatic && signaturePlacement.alignment === 'right'
            }"
            :style="!signaturePlacement.isStatic ? {
              left: signaturePlacement.x + '%', 
              top: signaturePlacement.y + '%'
            } : {}"
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
          <VBtn 
            color="primary" 
            @click="closeSignatureModal(true)" 
            :disabled="isSubmitting" 
            :loading="isSubmitting"
          >
            Acceptera
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
        v-model="isRequestOngoing"
        width="auto"
        persistent>
        <VProgressCircular
          indeterminate
          color="primary"
          class="mb-0"/>
    </VDialog>
  </div>
</template>


<style scoped>
.signing-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
  min-height: 100vh;
  background-color: rgb(var(--v-theme-surface-variant));
}

.signing-card {
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.alert-no-shrink {
  flex-shrink: 0;
}

.pdf-container {
  position: relative;
  flex-grow: 1;
  overflow: auto;
  padding: 1rem;
}

.pdf-container.read-only {
  background-color: rgb(var(--v-theme-surface));
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
  transition: all 0.2s;
}

.signature-placeholder:hover {
  background-color: rgba(var(--v-theme-primary), 0.2);
  transform: scale(1.05);
}

.signature-placeholder:not(.static-signature-position) {
  transform: translate(-50%, -50%);
}

.signature-placeholder:not(.static-signature-position):hover {
  transform: translate(-50%, -50%) scale(1.05);
}

/* Posicionamiento para la firma estática */
.static-signature-position.align-left {
  bottom: 11%;
  left: 25%;
  top: auto;
  transform: translate(-50%, -50%);
}

.static-signature-position.align-right {
  bottom: 15%;
  left: 75%;
  top: auto;
  transform: translate(-50%, -50%);
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

/* Responsive */
@media (max-width: 768px) {
  .signing-container {
    padding: 0.5rem;
  }

  .signing-card {
    max-height: 95vh;
    max-width: 100%;
  }

  .signature-placeholder {
    padding: 4px 8px;
    font-size: 0.875rem;
    gap: 4px;
    border-radius: 4px;
  }
  
  .signature-placeholder .v-icon {
    font-size: 1rem;
  }

  .static-signature-position.align-left {
    bottom: 7%;
  }

  .static-signature-position.align-right {
    bottom: 12%;
  }
}
</style>

<route lang="yaml">
  meta:
    layout: blank
    redirectIfLoggedIn: false
    public: true    # opcional, claro indicador de que es pública
</route>