<script setup>

import { ref, watchEffect, inject } from 'vue'
import { useRouter } from 'vue-router'
import { requiredValidator } from '@/@core/utils/validators'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useAuthStores } from '@/stores/useAuth'
import { useAgreementsStores } from '@/stores/useAgreements'
import { useCarInfoStores } from '@/stores/useCarInfo'

const router = useRouter()
const emitter = inject("emitter")

const authStores = useAuthStores()
const carInfoStores = useCarInfoStores()
const agreementsStores = useAgreementsStores()
const ability = useAppAbility()

const isRequestOngoing = ref(false)
const refForm = ref()

const userData = ref(null)
const role = ref(null)
const currencies = ref([])
const currency_id = ref(1)

const brands = ref([])
const models = ref([])
const brand_id = ref(null)
const model_id = ref(null)
const model = ref(null)
const modelsByBrand = ref([])
const offer_id = ref(null)
const reg_num = ref(null)
const mileage = ref(null)
const price = ref(null)
const comment = ref(null)
const terms_other_conditions = ref(null)

watchEffect(async () => {
    isRequestOngoing.value = true

    await agreementsStores.info()

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value.roles[0].name

    const { user_data, userAbilities } = await authStores.me(userData.value)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

    ability.update(userAbilities)

    localStorage.setItem('user_data', JSON.stringify(user_data))

    if(role.value === 'Supplier') {
      offer_id.value = user_data.supplier.user.offers.length + 1
    } else if(role.value === 'User') {
      offer_id.value = user_data.supplier.boss.user.offers.length + 1
    } else {
      offer_id.value = agreementsStores.offer_id + 1
    }

    brands.value = agreementsStores.brands
    models.value = agreementsStores.models 
    currencies.value = agreementsStores.currencies

    isRequestOngoing.value = false
   
})

const selectBrand = brand => {
    if (brand) {
        let _brand = brands.value.find(item => item.id === brand)
    
        model_id.value = ''
        modelsByBrand.value = models.value.filter(item => item.brand_id === _brand.id)
    }
}

const onClearBrand = () => {
    modelsByBrand.value = []
}

const getModels = computed(() => {
    const models = modelsByBrand.value.map((model) => ({
        title: model.name,
        value: model.id
    }))

    if (modelsByBrand.value.length > 0) {
        models.push({ title: 'En annan..', value: 0 })
    }

    return models
})

const selectModel = selected => {

  model.value = selected !== 0 ? null : model.value

}

const searchVehicule = async () => {

  isRequestOngoing.value = true

  const carRes = await carInfoStores.getLicensePlate(reg_num.value)

  isRequestOngoing.value = false
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      let formData = new FormData()

      //vehicle
      formData.append('reg_num', reg_num.value)
      formData.append('brand_id', brand_id.value)
      formData.append('model_id', model_id.value)
      formData.append('model', model.value)
      formData.append('offerId', offer_id.value)
      formData.append('mileage', mileage.value)
      formData.append('comment', comment.value)
      formData.append('price', price.value)
      formData.append('terms_other_conditions', terms_other_conditions.value)

      //agreement
      formData.append('agreement_type_id', 4)
      formData.append('currency_id', currency_id.value)
      formData.append('price', price.value)
      formData.append('residual_debt', 0)
      formData.append('guaranty', 0)
      formData.append('insurance_company', 0)

      isRequestOngoing.value = true

      agreementsStores.addAgreement(formData)
          .then((res) => {
              if (res.data.success) {
                  
                  let data = {
                      message: 'Erbjudande framgångsrikt skapat',
                      error: false
                  }

                  router.push({ name : 'dashboard-admin-agreements'})
                  emitter.emit('toast', data)
              }
              isRequestOngoing.value = false
          })
          .catch((err) => {
              
              let data = {
                  message: err.message,
                  error: true
              }

              router.push({ name : 'dashboard-admin-agreements'})
              emitter.emit('toast', data)

              isRequestOngoing.value = false
          })
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
    <VForm ref="refForm" @submit.prevent="onSubmit">
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
              <VBtn
                    type="submit"
                    color="primary"
                    class="w-100 w-md-auto"
                    :loading="isRequestOngoing"
                  >
                   Skapa
                  </VBtn>
            </div>
          </div>
        </VCol>
        <VCol cols="12">
            <VCard flat class="px-2 px-md-12">
              <VCardText class="px-2 pt-0 pt-md-5">            
                <h6 class="text-md-h4 text-h5 font-weight-medium my-5">
                  Detaljer om erbjudandet
                </h6>
                <VRow>
                  <VCol cols="12" md="6" class="d-none d-md-block"></VCol>
                  <VCol cols="10" md="3">
                      <VTextField
                          v-model="reg_num"
                          label="Regnr"
                          :rules="[requiredValidator]"
                      />
                  </VCol>
                  <VCol cols="2" md="1" class="px-0">
                    <VBtn
                        icon="tabler-search"
                        variant="tonal"
                        color="primary"
                        size="x-small"
                        @click="searchVehicule"
                    />
                  </VCol>
                  <VCol cols="12" md="2">
                    <VTextField
                      v-model="offer_id"
                      label="Offertnummer"
                      readonly
                      disabled
                      prefix="#"
                      density="compact"
                    />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VAutocomplete
                        v-model="brand_id"
                        label="Märke"
                        :items="brands"
                        :item-title="item => item.name"
                        :item-value="item => item.id"
                        autocomplete="off"
                        clearable
                        clear-icon="tabler-x"
                        :rules="[requiredValidator]"
                        @update:modelValue="selectBrand"
                        @click:clear="onClearBrand"
                        :menu-props="{ maxHeight: '300px' }"/>
                </VCol>
                <VCol cols="12" :md="model_id !== 0 ? 6 : 3">
                  <VAutocomplete
                      v-model="model_id"
                      label="Modell"
                      :items="getModels"
                      autocomplete="off"
                      clearable
                      clear-icon="tabler-x"
                      :rules="[requiredValidator]"
                      @update:modelValue="selectModel"
                      :menu-props="{ maxHeight: '300px' }"/> 
                </VCol>
                <VCol cols="12" md="3" v-if="model_id === 0">
                  <VTextField
                      v-model="model"
                      label="Modellens namn"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="mileage"
                    label="Mätarställning"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="price"
                    :label="'Belopp ' + (currencies.find(item => item.id === currency_id)?.code || '')"
                    type="number"
                    min="0"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <VCol cols="12" md="12">
                  <VTextarea
                    v-model="comment"
                    label="Anmärkning"
                    rows="3"
                  />
                </VCol>
                <VCol cols="12" md="12">
                  <VTextarea
                    v-model="terms_other_conditions"
                    label="Villkor och bestämmelser"
                    rows="5"
                  />
                </VCol>
                </VRow>
              </VCardText>
            </VCard>          
        </VCol>
      </VRow>
    </VForm>
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
    subject: agreements
</route>