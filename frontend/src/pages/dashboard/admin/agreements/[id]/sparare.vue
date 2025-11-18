<script setup>
import { useRoute } from 'vue-router'
import { useAgreementsStores } from '@/stores/useAgreements'
import { avatarText } from '@/@core/utils/formatters'
import { themeConfig } from '@themeConfig'
import pdfIcon from '@images/icon-pdf-documento.png'
import axios from '@/plugins/axios'
import VuePdfEmbed from 'vue-pdf-embed'

const route = useRoute()
const agreementsStores = useAgreementsStores()

const isLoading = ref(true)
const error = ref('')
const agreementData = ref(null)
const isPreviewDialogVisible = ref(false)
const isLoadingPreview = ref(false)
const previewPdfSource = ref(null)
const previewError = ref('')

const events = computed(() => {
  if (!agreementData.value) return []

  const items = []

  // Created event
  items.push({
    key: 'created',
    title: 'Avtal skapat',
    meta: new Date(agreementData.value.created_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
    text: agreementData.value.agreement_type?.name || `Avtal #${agreementData.value.agreement_id}`,
    color: 'primary',
  })

  // Prefer tokens[] like documents, fallback to single token
  const tokensArr = Array.isArray(agreementData.value.tokens) ? agreementData.value.tokens : []
  const latestToken = tokensArr.length
    ? [...tokensArr].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
    : (agreementData.value.token || null)

  if (latestToken) {
    items.push({
      key: 'sent',
      title: 'Signeringsförfrågan skickad',
      meta: latestToken.created_at ? new Date(latestToken.created_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : '—',
      text: latestToken.recipient_email ? `Skickad till ${latestToken.recipient_email}` : 'Begäran skapad',
      color: 'success',
    })

    if (latestToken.viewed_at) {
      items.push({
        key: 'viewed',
        title: 'Avtal visat av kunden',
        meta: new Date(latestToken.viewed_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
        text: 'Kunden har öppnat signeringslänken.',
        color: 'primary',
      })
    }

    if (latestToken.signature_status === 'signed' && latestToken.signed_at) {
      items.push({
        key: 'signed',
        title: 'Avtal signerat',
        meta: new Date(latestToken.signed_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
        text: 'Signeringen är slutförd.',
        color: 'info',
      })
    } else if (latestToken.signature_status === 'sent') {
      items.push({
        key: 'pending',
        title: 'Väntar på signering',
        meta: 'Aktiv begäran',
        text: 'Mottagaren har inte signerat ännu.',
        color: 'warning',
      })
    }
  }

  return items
})

const openPreview = async () => {
  if (!agreementData.value) return
  isPreviewDialogVisible.value = true
  isLoadingPreview.value = true
  previewError.value = ''
  try {
    const response = await axios.get(`/agreements/${agreementData.value.id}/get-admin-preview-pdf`, { responseType: 'blob' })
    previewPdfSource.value = URL.createObjectURL(response.data)
  } catch (e) {
    previewError.value = 'Kunde inte ladda PDF-förhandsvisning.'
  } finally {
    isLoadingPreview.value = false
  }
}

watch(isPreviewDialogVisible, val => {
  if (!val && previewPdfSource.value) {
    URL.revokeObjectURL(previewPdfSource.value)
    previewPdfSource.value = null
  }
})

onUnmounted(() => {
  if (previewPdfSource.value) URL.revokeObjectURL(previewPdfSource.value)
})

onMounted(async () => {
  try {
    isLoading.value = true
    const id = route.params.id
    const agreement = await agreementsStores.showAgreement(Number(id))
    agreementData.value = agreement
    // Fallback: if detail lacks token(s), try to hydrate from list cache
    if (agreementData.value && !agreementData.value.token && !Array.isArray(agreementData.value.tokens)) {
      const fromList = (agreementsStores.agreements || []).find(a => a.id == id)
      if (fromList && fromList.token) {
        agreementData.value.token = fromList.token
      }
    }
  } catch (e) {
    error.value = 'Kunde inte hämta avtalets information.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section>
    <VRow>
      <VCol cols="12">
        <VCard :title="agreementData ? `Spårare: Avtal ${agreementData.agreement_type?.name || '-'}` : 'Spårare'">
          <VCardText>
            <VAlert v-if="error" type="error" class="mb-4">{{ error }}</VAlert>

            <div v-if="isLoading" class="d-flex justify-center my-8">
              <VProgressCircular indeterminate color="primary" />
            </div>

            <template v-else>
              <div class="d-flex align-center justify-space-between flex-wrap mb-6">
                <div class="d-flex align-center gap-3">
                  <VAvatar size="36" :variant="agreementData?.user?.avatar ? 'outlined' : 'tonal'">
                    <VImg v-if="agreementData?.user?.avatar" :src="themeConfig.settings.urlStorage + agreementData?.user?.avatar" style="border-radius: 50%;" />
                    <span v-else>{{ avatarText(agreementData?.user?.name || 'U') }}</span>
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <span class="font-weight-medium">{{ agreementData?.user?.name }} {{ agreementData?.user?.last_name ?? '' }}</span>
                    <span class="text-sm text-disabled">{{ agreementData?.user?.email }}</span>
                  </div>
                </div>
                <div class="d-inline-flex align-center timeline-chip pointer" @click="openPreview" title="Förhandsvisa PDF">
                  <img :src="pdfIcon" height="20" class="me-2" alt="pdf">
                  <span class="app-timeline-text font-weight-medium">{{ agreementData?.file?.split('/').pop() }}</span>
                </div>
              </div>

              <VTimeline side="end" align="start" line-inset="8" truncate-line="start" density="compact">
                <VTimelineItem v-for="item in events" :key="item.key" size="x-small" :dot-color="item.color">
                  <div class="d-flex justify-space-between align-center gap-2 flex-wrap mb-2">
                    <span class="app-timeline-title">{{ item.title }}</span>
                    <span class="app-timeline-meta">{{ item.meta }}</span>
                  </div>
                  <div class="app-timeline-text mt-1">{{ item.text }}</div>
                  <div v-if="item.key==='created' && agreementData?.file" class="d-inline-flex align-center timeline-chip mt-2 pointer" @click="openPreview" title="Förhandsvisa PDF">
                    <img :src="pdfIcon" height="20" class="me-2" alt="pdf">
                    <span class="app-timeline-text font-weight-medium">{{ agreementData?.file?.split('/').pop() }}</span>
                  </div>
                  <div v-if="item.key==='signed' && ((Array.isArray(agreementData?.tokens) && agreementData.tokens[0]?.signed_pdf_path) || agreementData?.token?.signed_pdf_path)" class="d-inline-flex align-center timeline-chip mt-2 pointer" @click="openPreview" title="Visa signerad PDF">
                    <img :src="pdfIcon" height="20" class="me-2" alt="signed-pdf">
                    <span class="app-timeline-text font-weight-medium">Signerad PDF</span>
                  </div>
                </VTimelineItem>
              </VTimeline>
            </template>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- PDF Preview Dialog -->
    <VDialog v-model="isPreviewDialogVisible" max-width="900">
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          <span>Förhandsvisa avtal</span>
          <VBtn icon variant="text" @click="isPreviewDialogVisible = false">
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText class="d-flex justify-center" style="min-height:400px;">
          <VProgressCircular v-if="isLoadingPreview" indeterminate color="primary" />
          <div v-else class="w-100">
            <VAlert v-if="previewError" type="error" class="mb-4">{{ previewError }}</VAlert>
            <vue-pdf-embed v-if="previewPdfSource && !previewError" :source="previewPdfSource" style="width:100%;" />
            <VAlert v-else-if="!previewError" type="warning">Ingen PDF tillgänglig.</VAlert>
          </div>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
  
</template>

<route lang="yaml">
  meta:
    action: view
    subject: agreements
</route>

<style scoped>
.timeline-chip {
  border-radius: 6px;
  padding: 2px 8px;
  background: rgba(var(--v-theme-on-surface), 0.04);
}
.pointer { cursor: pointer; }
</style>
