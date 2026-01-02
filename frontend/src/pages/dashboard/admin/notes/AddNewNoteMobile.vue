<script setup>

import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'

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

      // After successful submit, close without confirmation
      reallyCloseAndReset()
    }
  })
}

watch(currentData, () => {
  if (!initialData.value) return
  emit('edited', isDirty.value)
}, { deep: true })

</script>

<template>
    <!-- 👉 Form -->
    <VForm
        class="card-form"
        ref="refForm"
        v-model="isFormValid"
        @submit.prevent="onSubmit"
    >
        <VList>
            <VListItem>
                <VTextField
                    v-model="reg_num"
                    label="Reg nr*"
                    :rules="[requiredValidator]"
                />
            </VListItem>
            <VListItem>
                <VTextField
                    v-model="note"
                    type="number"
                    min="0"
                    label="Egen värdering*"
                    :rules="[requiredValidator]"
                />
            </VListItem>
            <VListItem>
                <VTextField
                    v-model="name"
                    label="Kundnamn"
                />
            </VListItem>
            <VListItem>
                <VTextField
                    v-model="phone"
                    :rules="[phoneValidator]"
                    label="Tel nr"
                />
            </VListItem>
            <VListItem>
                <VTextField
                    v-model="email"
                    :rules="[emailValidator]"
                    label="E-post"
                />
            </VListItem>
            <VListItem>
                <VTextarea
                    v-model="comment"
                    rows="3"
                    label="Kommentar"
                />
            </VListItem>
        </VList>
        <div class="pb-6 px-6 d-flex gap-4 form-actions">
            <!-- 👉 Submit and Cancel -->
            <VBtn
                class="btn-light" 
                type="reset"
                @click="closeNavigationDrawer"
            >
                Avbryt
            </VBtn>
            <VBtn
                class="btn-gradient"
                type="submit"
            >
                {{ isEdit ? "Uppdatering" : "Lägg till" }}
            </VBtn>
        </div>       
    </VForm>

    <!-- Confirm leave without saving (mobile) -->
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

<style scoped>
.border-img {
  border: 1.8px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
}
.border-img .v-img__img--contain {
  padding: 10px;
}

.v-btn {
  width: 100%;
}

.form-actions {
  .v-btn {
    flex: 1;
  }
}
</style>

<style lang="scss">
.card-form {
  .v-list {
    padding: 28px 24px 40px !important;

    .v-list-item {
      margin-bottom: 0px;
      padding: 0px !important;
      gap: 0px !important;

      .v-input--density-compact {
        --v-input-control-height: 48px !important;
      }

      .v-select .v-field {
        .v-select__selection {
          align-items: center;
        }

        .v-field__input > input {
          top: 0px;
          left: 0px;
        }

        .v-field__append-inner {
          align-items: center;
          padding-top: 0px;
        }
      }

      .v-text-field {
        .v-input__control {
          padding-top: 16px;
          input {
            min-height: 48px;
            padding: 12px 16px;
          }
        }
      }
    }
  }
  & .v-input {
    & .v-input__control {
      .v-field {
        background-color: #f6f6f6;
        .v-field-label {
          @media (max-width: 991px) {
            top: 12px !important;
          }
        }
      }
    }
  }
}

.v-dialog .v-overlay__content {
  max-height: calc(100dvh - 48px) !important;
}
</style>
