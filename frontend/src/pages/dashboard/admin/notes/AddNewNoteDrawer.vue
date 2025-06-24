<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { urlValidator, emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  note: {
    type: Object,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'noteData',
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(0)
const reg_num = ref('')
const note = ref('')
const name = ref('')
const phone = ref('')
const email = ref('')
const comment = ref('')
const isEdit = ref(false)

const getTitle = computed(() => {
  return isEdit.value ? 'Uppdatera värdering': 'Ny värdering'
})

watchEffect(async() => {
  if (props.isDrawerOpen) {

    if (!(Object.entries(props.note).length === 0) && props.note.constructor === Object) {

      isEdit.value = true
      id.value = props.note.id
      reg_num.value = props.note.reg_num
      note.value = props.note.note
      name.value = props.note.name
      phone.value = props.note.phone
      email.value = props.note.email
      comment.value = props.note.comment
     
    }
  }
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()

    reg_num.value = null
    note.value = null
    name.value = null
    phone.value = null
    email.value = null
    comment.value = null
    
    isEdit.value = false 
    id.value = 0
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

      formData.append('reg_num', reg_num.value)
      formData.append('note', note.value)
      formData.append('name', name.value)
      formData.append('phone', phone.value)
      formData.append('email', email.value)
      formData.append('comment', comment.value)

      emit('noteData', { data: formData, id: id.value }, isEdit.value ? 'update' : 'create')

      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

</script>

<template>
  <VNavigationDrawer
    temporary
    :width="550"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ getTitle }}
      </h6>

      <VSpacer />

      <!-- 👉 Close btn -->
      <VBtn
        variant="tonal"
        color="default"
        icon
        size="32"
        class="rounded btn-close-note"
        @click="closeNavigationDrawer"
      >
        <VIcon
          size="18"
          icon="tabler-x"
        />
      </VBTn>
    </div>
    
    <VDivider class="mt-4"/>

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat class="card-note">
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
          <VRow>
            <VCol cols="12" md="12">
                <VTextField
                    v-model="reg_num"
                    label="Reg nr"
                    :rules="[requiredValidator]"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="note"
                    label="Egen värdering"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="name"
                    label="Kundnamn"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="phone"
                    :rules="[phoneValidator]"
                    label="Tel nr"
                />
            </VCol>
            <VCol cols="12" md="6">
                <VTextField
                    v-model="email"
                    :rules="[emailValidator]"
                    label="E-post"
                />
            </VCol>
            <VCol cols="12" md="12">
                <VTextarea
                    v-model="comment"
                    rows="3"
                    label="Kommentar"
                />
            </VCol>
              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  {{ isEdit ? 'Uppdatering': 'Lägg till' }}
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="secondary"
                  @click="closeNavigationDrawer"
                >
                  Avbryt
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style scoped>
  .btn-close-note {
    height: 32px !important;
  }
  .card-note {
    border-radius: 0 !important;
  }
  .border-img {
      border: 1.8px solid rgba(var(--v-border-color), var(--v-border-opacity));
      border-radius: 6px;
  }
  .border-img .v-img__img--contain {
      padding: 10px;
  }
</style>
