<script setup>

import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import { emailValidator, minLengthDigitsValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { PHONE_INPUT_DEFAULTS, formatPhonePayload, normalizePhoneInput } from '@/@core/utils/phone'

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

const notePhonePrefix = `+${PHONE_INPUT_DEFAULTS.defaultPhoneCode}`
const notePhoneDigits = PHONE_INPUT_DEFAULTS.defaultPhoneDigits
const notePhoneRules = [minLengthDigitsValidator(notePhoneDigits), phoneValidator]

const normalizeNotePhoneForInput = value => normalizePhoneInput(value, [], null, PHONE_INPUT_DEFAULTS)
const normalizeLandlineForInput = value => String(value ?? '').replace(/\D/g, '')
const formatNotePhoneForPayload = value => formatPhonePayload(value, [], null, PHONE_INPUT_DEFAULTS)

const isFormValid = ref(false)
const refForm = ref()

const id = ref(0)
const reg_num = ref('')
const note = ref('')
const name = ref('')
const phone = ref('')
const landline = ref('')
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
  landline: landline.value,
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
      phone.value = normalizeNotePhoneForInput(props.note.phone)
      landline.value = normalizeLandlineForInput(props.note.landline)
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
    landline.value = null
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

const confirmLeave = () => {
  isConfirmLeaveVisible.value = false
  reallyCloseAndReset()
}

const cancelLeave = () => {
  isConfirmLeaveVisible.value = false
}

const handlePhoneInput = () => {
  phone.value = normalizeNotePhoneForInput(phone.value)
}

const handleLandlineInput = () => {
  landline.value = normalizeLandlineForInput(landline.value)
}

const handlePhoneKeydown = event => {
  const allowedKeys = [
    'Backspace',
    'Delete',
    'Tab',
    'Enter',
    'Escape',
    'ArrowLeft',
    'ArrowRight',
    'ArrowUp',
    'ArrowDown',
    'Home',
    'End',
  ]

  if (allowedKeys.includes(event.key))
    return

  if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase()))
    return

  if (/^\d$/.test(event.key))
    return

  event.preventDefault()
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let formData = new FormData()

      formData.append('reg_num', reg_num.value)
      formData.append('note', note.value)
      formData.append('name', name.value)
      formData.append('phone', formatNotePhoneForPayload(phone.value))
      formData.append('landline', normalizeLandlineForInput(landline.value))
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
        class="card-form note-mobile"
        ref="refForm"
        v-model="isFormValid"
        @submit.prevent="onSubmit"
    >
        <VList>
            <VListItem>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Reg nr*" />
                <VTextField
                    v-model="reg_num"
                    :rules="[requiredValidator]"
                    @input="reg_num = reg_num.toUpperCase()"
                />
            </VListItem>
            <VListItem>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Egen värdering*" />
                <VTextField
                    v-model="note"
                    type="number"
                    min="0"
                    :rules="[requiredValidator]"
                />
            </VListItem>
            <VListItem>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kundnamn" />
                <VTextField
                    v-model="name"
                />
            </VListItem>
            <VListItem>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mobilnummer" />
                <VTextField
                  v-model="phone"
                  class="always-show-prefix"
                  :rules="notePhoneRules"
                  :min-length="notePhoneDigits"
                  :maxlength="notePhoneDigits"
                  :prefix="notePhonePrefix"
                  inputmode="numeric"
                  type="tel"
                  @input="handlePhoneInput"
                  @keydown="handlePhoneKeydown"
                />
            </VListItem>
            <VListItem>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon" />
                 <VTextField
                  v-model="landline"
                  :rules="[phoneValidator]"
                  type="tel"
                  inputmode="numeric"
                  @input="handleLandlineInput"
                  @keydown="handlePhoneKeydown"
                />
            </VListItem>
            <VListItem>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post" />
                <VTextField
                    v-model="email"
                    :rules="[emailValidator]"
                />
            </VListItem>
            <VListItem>
                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kommentar" />
                <VTextarea
                    v-model="comment"
                    rows="3"
                />
            </VListItem>
        </VList>
        <div class="pt-3 pb-6 px-6 d-flex gap-4 form-actions">
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
        @click="cancelLeave"
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
          <VBtn class="btn-light" @click="confirmLeave">Lämna sidan</VBtn>
          <VBtn class="btn-gradient" @click="cancelLeave">Stanna kvar</VBtn>
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
.always-show-prefix .v-text-field__prefix {
  opacity: 1 !important;
}

@media (max-width: 1023px) {
  .card-form.note-mobile {
    .v-list {
      padding: 28px 24px 4px !important;

      .v-list-item {
        margin-bottom: 0px;
        padding: 4px 0 !important;
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

        .selector-country {
          .v-input__prepend {
            margin-inline-end: 6px !important;
          }
        }

        .v-autocomplete .v-field {
          .v-autocomplete__selection-text {
            align-items: center;
            display: flex;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }

        .v-text-field {
          .v-input__control {
            padding-top: 0;

            .v-text-field__prefix {
              height: 48px;
              color: #454545;
            }

            input {
              min-height: 48px !important;
              height: 48px !important;
              padding: 12px 16px;
            }
          }
        }
      }
    }

    & .v-input.always-show-prefix {
      .v-input__control {
        .v-field {
          .v-field__input {
            padding: 8px 0 !important;
          }
        }
      }
    }

    & .v-input:not(.v-textarea) {
      & .v-input__control {
        .v-field {
          background-color: #f6f6f6;
          min-height: 48px !important;
          height: 48px !important;
          .v-field-label {
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
