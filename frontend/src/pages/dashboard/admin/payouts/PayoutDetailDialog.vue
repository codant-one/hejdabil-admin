<script setup>
import { usePayoutsStores } from '@/stores/usePayouts'
import { avatarText } from '@core/utils/formatters'
import { themeConfig } from '@themeConfig'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  payoutId: {
    type: [Number, String],
    default: null,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'payoutUpdated'])

const payoutsStores = usePayoutsStores()
const payout = ref(null)
const isLoading = ref(false)
const isSaving = ref(false)

const statusColors = {
  'CREATED': 'info',
  'DEBITED': 'warning', 
  'PAID': 'success',
  'ERROR': 'error',
  'CANCELLED': 'secondary'
}

const dialogVisible = computed({
  get: () => props.isDialogVisible,
  set: (value) => emit('update:isDialogVisible', value),
})

const fetchPayout = async () => {
  if (!props.payoutId) return
  
  try {
    isLoading.value = true
    const data = await payoutsStores.showPayout(props.payoutId)
    payout.value = data
  } catch (error) {
    console.error('Error fetching payout:', error)
  } finally {
    isLoading.value = false
  }
}

watch(() => props.isDialogVisible, (newVal) => {
  if (newVal && props.payoutId) {
    fetchPayout()
  }
})

const closeDialog = () => {
  dialogVisible.value = false
  payout.value = null
}
</script>

<template>
  <VDialog
    v-model="dialogVisible"
    max-width="1200px"
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>Payout Detaljer #{{ payoutId }}</span>
        <VBtn
          icon
          size="small"
          variant="text"
          @click="closeDialog"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText v-if="isLoading" class="text-center py-10">
        <VProgressCircular indeterminate color="primary" />
      </VCardText>

      <VCardText v-else-if="payout" class="pa-5" style="max-height: 70vh;">
        <VRow>
          <!-- Información General -->
          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Allmän Information
              </VCardTitle>
              <VDivider />
             <VCardText class="pa-3">
                <VList density="compact">
                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payout ID
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.id }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Swish ID
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.swish_id || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Reference
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.reference || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Belopp
                    </VListItemTitle>
                    <VListItemSubtitle class="text-h6 text-success">
                      {{ payout.amount }} SEK
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Valuta
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.currency || 'SEK' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Status
                    </VListItemTitle>
                    <VListItemSubtitle>
                      {{ payout.state.name }}
                    </VListItemSubtitle>
                  </VListItem>

                   <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Meddelande
                    </VListItemTitle>
                    <VListItemSubtitle>
                      {{ payout.message || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Información de Swish -->
          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Swish Detaljer
              </VCardTitle>
              <VDivider />
              <VCardText class="pa-3">
                <VList density="compact">
                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payer Alias
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.payer_alias || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payee Alias
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.payee_alias || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payee SSN
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.payee_ssn || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payout Type
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.payout_type || 'PAYOUT' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payout UUID
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2 text-truncate">
                      {{ payout.payout_instruction_uuid || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Instruction Date
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.instruction_date || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>
                  <VListItem class="d-none">
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Location URL
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2 text-truncate">
                      <a :href="payout.location_url" target="_blank" class="text-primary">
                        {{ payout.location_url }}
                      </a>
                    </VListItemSubtitle>
                  </VListItem>
                    <VListItem v-if="payout.signing_certificate_serial_number">
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Certificate Serial Number
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.signing_certificate_serial_number }}
                    </VListItemSubtitle>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardActions>
        <VSpacer />
        <VBtn
          color="secondary"
          variant="outlined"
          class="mt-3"
          @click="closeDialog"
        >
          Stäng
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
  pre {
    background-color: rgba(var(--v-theme-on-surface), 0.05);
    padding: 0.75rem;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 0.75rem;
    line-height: 1.4;
  }
</style>
