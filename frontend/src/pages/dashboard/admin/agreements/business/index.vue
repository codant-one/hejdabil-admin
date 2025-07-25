<script setup>
import { ref, watchEffect, inject } from 'vue'
import { useRouter } from 'vue-router'
import { requiredValidator } from '@/@core/utils/validators'
import { useAgreementsStores } from '@/stores/useAgreements'
const router = useRouter()
const emitter = inject("emitter")

const agreementsStores = useAgreementsStores()
const isRequestOngoing = ref(false)
const refForm = ref()
const models = ref([])

const offerData = ref({
  offer_id: null,
  model_id: null,
  reg_num: '',
  mileage: null,
  comment: '',
  price: null,
  terms_other_conditions: 'Bilen köpes i befintligt skick utan några garantier. Hejdåbil ordnar alla administrationer och transporten enligt överenskommelse.', 
});

watchEffect(async () => {
  isRequestOngoing.value = true
  try {
    await agreementsStores.info()
    models.value = agreementsStores.models 

    offerData.value.offer_id = `OFR-${Math.floor(1000 + Math.random() * 9000)}`;

  } catch (error) {
    console.error("Error al cargar los datos iniciales:", error)
    emitter.emit('toast', { message: 'No se pudieron cargar los datos para el formulario.', error: true })
  } finally {
    isRequestOngoing.value = false
  }
})


const submitOffer = () => {
  refForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      isRequestOngoing.value = true
      
      // --- SIMULACIÓN (borra esto cuando conectes tu store real) ---
      console.log('Datos de la oferta a enviar:', offerData.value);
      setTimeout(() => {
        isRequestOngoing.value = false;
        emitter.emit('toast', { message: 'Oferta guardada (simulación)', error: false });
        router.push({ name: 'dashboard-admin-agreements' }); // Redirige a donde necesites
      }, 1500);
      // --- FIN DE SIMULACIÓN ---
    }
  })
}
</script>

<template>
  <section>
    <!-- Dialog para mostrar el estado de carga -->
    <VDialog
      v-model="isRequestOngoing"
      width="auto"
      persistent
    >
      <VProgressCircular
        indeterminate
        color="primary"
        class="mb-0"
      />
    </VDialog>

    <VRow>
      <VCol cols="12" class="py-0">
        <div class="d-flex flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
          <div class="d-flex flex-column justify-center">
            <h6 class="text-md-h4 text-h6 font-weight-medium">
              Prisförslag
            </h6>
          </div>
          <VSpacer />
          <div class="d-flex flex-column flex-md-row gap-1 gap-md-4 w-100 w-md-auto">
            <VBtn
              variant="tonal"
              color="secondary"
              class="mb-2 w-100 w-md-auto"
              :to="{ name: 'dashboard-admin-agreements' }"
            >
              Tillbaka
            </VBtn>
          </div>
        </div>
      </VCol>


      <VCol cols="12">
        <VForm ref="refForm" @submit.prevent="submitOffer">
          <VCard flat class="px-2 px-md-12">
            <VCardText class="px-2 pt-0 pt-md-5">
              
              <h6 class="text-md-h4 text-h5 font-weight-medium my-5">
                Detaljer om erbjudandet
              </h6>

              <VRow class="px-md-5">
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="offerData.offer_id"
                    label="Erbjudandenummer"
                    readonly
                    disabled
                    prefix="#"
                    density="compact"
                  />
                </VCol>

                <VCol cols="12" md="6">
                  <VAutocomplete
                    v-model="offerData.model_id"
                    label="Modell"
                    :items="models"
                    item-title="name"
                    item-value="id"
                    :rules="[requiredValidator]"
                    autocomplete="off"
                    clearable
                    clear-icon="tabler-x"
                    placeholder="Seleccione un modelo"
                  />
                </VCol>

                <VCol cols="12" md="6">
                  <VTextField
                    v-model="offerData.reg_num"
                    label="Reg nr"
                    :rules="[requiredValidator]"
                    placeholder="exempel: ABC 123"
                  />
                </VCol>

                <VCol cols="12" md="6">
                  <VTextField
                    v-model.number="offerData.mileage"
                    label="Mätarställning"
                    type="number"
                    min="0"
                    :rules="[requiredValidator]"
                    suffix="km"
                    placeholder="exempel: 50000"
                  />
                </VCol>

                <VCol cols="12" md="6">
                  <VTextField
                    v-model.number="offerData.price"
                    label="BELOPP"
                    type="number"
                    min="0"
                    step="0.01"
                    :rules="[requiredValidator]"
                    prefix="€"
                    placeholder="exempel: 15000.00"
                  />
                </VCol>

                <VCol cols="12" md="12">
                  <VTextarea
                    v-model="offerData.comment"
                    label="Kommentarer"
                    rows="3"
                    placeholder="Ytterligare observationer eller egenskaper hos fordonet"
                  />
                </VCol>

                <VCol cols="12" md="12">
                  <VTextarea
                    v-model="offerData.terms_other_conditions"
                    label="Villkor och bestämmelser"
                    :rules="[requiredValidator]"
                    rows="5"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VRow class="mt-5">
            <VCol cols="12">
              <div class="text-center">
                <VBtn
                  type="submit"
                  color="primary"
                  class="w-100 w-md-auto"
                  :loading="isRequestOngoing"
                >
                    Lägg till
                </VBtn>
              </div>
            </VCol>
          </VRow>
        </VForm>
      </VCol>
    </VRow>
  </section>
</template>

<style scoped>
.justify-content-end {
    justify-content: end !important;
}
</style>

<route lang="yaml">
meta:
  action: create
  subject: offers 
</route>