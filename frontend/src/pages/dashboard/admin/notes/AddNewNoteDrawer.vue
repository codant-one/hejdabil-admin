<script setup>

import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg"

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
  'edited'
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
const isConfirmLeaveVisible = ref(false)

const initialData = ref(null)
const currentData = computed(() => ({
  reg_num: reg_num.value,
  note: note.value,
  name: name.value,
  phone: phone.value,
  email: email.value,
  comment: comment.value,
}))

const isDirty = computed(() => {
  if (!initialData.value) return false
  try {
    return JSON.stringify(currentData.value) !== JSON.stringify(initialData.value)
  } catch (e) {
    return true
  }
})

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

    // snapshot initial state after fields are populated
    nextTick(() => {
      initialData.value = { ...currentData.value }
      emit('edited', false)
    })

  }
})

// 👉 drawer close
const reallyCloseAndReset = () => {
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
    initialData.value = null
    emit('edited', false)
  })
}

const closeNavigationDrawer = () => {
  if (isDirty.value) {
    isConfirmLeaveVisible.value = true
    return
  }
  reallyCloseAndReset()
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

      setTimeout(() => {
        // After successful submit, close without confirmation
        reallyCloseAndReset()
      }, 100)
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  if (val === false) {
    if (isDirty.value) {
      // keep drawer open and show confirm dialog
      emit('update:isDrawerOpen', true)
      isConfirmLeaveVisible.value = true
      return
    }
    reallyCloseAndReset()
    return
  }
  emit('update:isDrawerOpen', val)
}

watch(currentData, () => {
  if (!initialData.value) return
  emit('edited', isDirty.value)
}, { deep: true })

</script>

<template>
  <VNavigationDrawer
    temporary
    :width="550"
    location="end"
    class="scrollable-content right-drawer rounded-left-4"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="title-modal font-blauer">
        {{ getTitle }}
      </h6>

      <VSpacer />

      <!-- 👉 Close btn -->
      <VBtn
        icon
        class="btn-white"
        @click="closeNavigationDrawer"
      >
        <VIcon size="32" icon="custom-cancel" />
      </VBtn>
    </div>
    
    <VDivider class="mt-4"/>

    <PerfectScrollbar :options="{ wheelPropagation: false }" class="scrollbar-no-border">
      <VCard flat class="card-form">
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
                      label="Reg nr*"
                      :rules="[requiredValidator]"
                      @input="reg_num = reg_num.toUpperCase()"
                  />
              </VCol>
              <VCol cols="12" md="12">
                  <VTextField
                      v-model="note"
                      type="number"
                      min="0"
                      label="Egen värdering*"
                      suffix="KR"
                      :rules="[requiredValidator]"
                  />
              </VCol>
              <VCol cols="12" md="12">
                  <VTextField
                      v-model="name"
                      label="Kundnamn"
                  />
              </VCol>
              <VCol cols="12" md="12">
                  <VTextField
                      v-model="phone"
                      :rules="[phoneValidator]"
                      label="Tel nr"
                  />
              </VCol>
              <VCol cols="12" md="12">
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
                  type="reset"
                  class="btn-light me-3"
                  @click="closeNavigationDrawer"
                >
                  Avbryt
                </VBtn>
                <VBtn
                  type="submit"
                  class="btn-gradient"
                >
                  {{ isEdit ? 'Uppdatering': 'Lägg till' }}
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>

  <!-- Confirm leave without saving -->
  <VDialog
    v-model="isConfirmLeaveVisible"
    persistent
    class="action-dialog"
  >
    <VBtn
      icon
      class="btn-white close-btn"
      @click="isConfirmLeaveVisible = false"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>
    <VCard>
      <VCardText class="dialog-title-box">
        <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
        <div class="dialog-title">Du har osparade ändringar</div>
      </VCardText>
      <VCardText class="dialog-text">
        Om du lämnar den här sidan nu kommer den information du har angett inte att sparas.
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn class="btn-light" @click="isConfirmLeaveVisible = false">Lämna sidan</VBtn>
        <VBtn class="btn-gradient" @click="() => { isConfirmLeaveVisible = false; reallyCloseAndReset(); }">Stanna kvar</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
  .btn-close-client {
    height: 32px !important;
  }
  .card-form {
    .v-input {
      .v-input__control {
        .v-field {
          background-color: #f6f6f6 !important;
          min-height: 48px !important;

          .v-text-field__suffix {
            padding: 12px 16px !important;
          }

          .v-field__input {
            min-height: 48px !important;
            padding: 12px 16px !important;

            input {
                min-height: 48px !important;
            }
          }

          .v-field-label {
            top: 12px !important;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }
      }
    }

    .v-select .v-field,
    .v-autocomplete .v-field {
      .v-select__selection,
      .v-autocomplete__selection {
          align-items: center;
      }

      .v-field__input > input {
          top: 0px;
          left: 0px;
      }
    }
  }
  .border-img {
    border: 1.8px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 6px;
  }
  .border-img .v-img__img--contain {
    padding: 10px;
  }

  :deep(.right-drawer.v-navigation-drawer) {
    border-color: transparent !important;
    border-width: 0 !important;
    border-style: none !important;
    box-shadow: none !important;
  }

  :deep(.right-drawer.v-navigation-drawer .v-navigation-drawer__content) {
    border: none !important;
  }
</style>

<style>
  .right-drawer.v-navigation-drawer {
    border: none !important;
    border-color: transparent !important;
    border-width: 0 !important;
    border-style: none !important;
    box-shadow: none !important;
  }

  .right-drawer.v-navigation-drawer .v-navigation-drawer__content {
    border: none !important;
  }
</style>
