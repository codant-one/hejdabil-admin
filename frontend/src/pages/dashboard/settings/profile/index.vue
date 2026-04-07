<script setup>

import { useProfileStores } from '@/stores/useProfile'
import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import avatar1 from '@/assets/images/avatars/1.svg'
import avatar2 from '@/assets/images/avatars/2.svg'
import avatar3 from '@/assets/images/avatars/3.svg'
import avatar4 from '@/assets/images/avatars/4.svg'
import avatar5 from '@/assets/images/avatars/5.svg'
import avatar6 from '@/assets/images/avatars/6.svg'
import PresetAvatarImage from '@/components/common/PresetAvatarImage.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from '@/assets/images/icons/alerts/modal-warning-icon.svg'

const { width: windowWidth } = useWindowSize()
const sectionEl = ref(null)
const profileStores = useProfileStores()

const refVForm = ref()
const refAvatarFileInput = ref()

const userData = ref(null)
const userId = ref('')
const email = ref('')
const name = ref('')
const last_name = ref('')
const phone = ref('')
const address = ref('')

const avatarId = ref(null)
const avatarOld = ref(null)
const avatarPreview = ref(null)

const isConfirmEditAvatarVisible = ref(false)
const selectedDialogAvatar = ref(null)
const dialog = ref(false)
const isFormEdited = ref(false)
const isHydratingProfile = ref(false)
const initialProfileSnapshot = ref('')

let nextRoute = null

const isRequestOngoing = ref(false);
const advisor = ref({
  type: '',
  message: '',
  show: false,
})

const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')

const avatarOptions = [
  { id: 1, src: avatar1 },
  { id: 2, src: avatar2 },
  { id: 3, src: avatar3 },
  { id: 4, src: avatar4 },
  { id: 5, src: avatar5 },
  { id: 6, src: avatar6 },
]

const getAvatarSnapshotValue = value => {
  if (!value)
    return null

  if (typeof value === 'string')
    return value

  return JSON.stringify({
    name: value.name ?? null,
    size: value.size ?? null,
    type: value.type ?? null,
    lastModified: value.lastModified ?? null,
  })
}

const getProfileSnapshot = () => JSON.stringify({
  email: email.value,
  name: name.value,
  last_name: last_name.value,
  phone: phone.value,
  address: address.value,
  avatarId: avatarId.value,
  avatarPreview: avatarPreview.value ?? null,
  avatarOld: getAvatarSnapshotValue(avatarOld.value),
})

const syncInitialProfileSnapshot = () => {
  initialProfileSnapshot.value = getProfileSnapshot()
  isFormEdited.value = false
}

function loadUserData() {
  isRequestOngoing.value = true
  isHydratingProfile.value = true
  const storedUser = JSON.parse(localStorage.getItem('user_data') || 'null')
  userData.value = storedUser

  userId.value = storedUser?.id ?? ''
  email.value = storedUser?.email ?? ''
  name.value = storedUser?.name ?? ''
  last_name.value = storedUser?.last_name ?? ''
  phone.value = storedUser?.user_detail?.personal_phone ?? ''
  address.value = storedUser?.user_detail?.personal_address ?? ''

  avatarId.value = storedUser?.user_detail?.avatar_id ?? null
  avatarOld.value = storedUser?.avatar ?? null
  avatarPreview.value = storedUser?.avatar ?? null
  syncInitialProfileSnapshot()
  isHydratingProfile.value = false
  isRequestOngoing.value = false 
}

onBeforeRouteLeave((to, from, next) => {
  if (isFormEdited.value) {
    dialog.value = true
    nextRoute = next

    return
  }

  next()
})

watch([
  email,
  name,
  last_name,
  phone,
  address,
  avatarId,
  avatarOld,
  avatarPreview,
], () => {
  if (isHydratingProfile.value)
    return

  isFormEdited.value = getProfileSnapshot() !== initialProfileSnapshot.value
})

const resetAvatar = () => {
  avatarPreview.value = null
}

const deleteAvatar = () => {
  avatarOld.value = null
  resetAvatar()
}

const presetAvatarToFile = async selectedPresetAvatar => {
  const response = await fetch(selectedPresetAvatar.src)
  const blob = await response.blob()
  const extension = blob.type?.split('/')[1] || 'png'

  return new File([blob], `avatar-${selectedPresetAvatar.id}.${extension}`, {
    type: blob.type || 'application/octet-stream',
  })
}

const showConfirmEditAvatarDialog = () => {
  const selectedFromCurrent = avatarOptions.find(option => option.src === avatarPreview.value)

  selectedDialogAvatar.value = selectedFromCurrent?.id ?? avatarId.value
  isConfirmEditAvatarVisible.value = true
}

const selectDialogAvatar = id => {
  selectedDialogAvatar.value = id
}

const applySelectedAvatar = () => {
  const selected = avatarOptions.find(option => option.id === selectedDialogAvatar.value)

  if (!selected)
    return

  avatarPreview.value = selected.src
  avatarOld.value = selected.src
  avatarId.value = selected.id
  isConfirmEditAvatarVisible.value = false
}

const onAvatarSelected = event => {
  if (event?.target?.files?.length) {
    const selectedFile = event.target.files[0]

    avatarOld.value = selectedFile
    avatarPreview.value = URL.createObjectURL(selectedFile)
    selectedDialogAvatar.value = null
    isConfirmEditAvatarVisible.value = false
  }
}

const triggerAvatarUpload = () => {
  nextTick(() => {
    refAvatarFileInput.value?.click()
  })
}

const confirmLeave = () => {
  dialog.value = false
  nextRoute?.()
  nextRoute = null
}

const cancelLeave = () => {
  dialog.value = false
  nextRoute?.(false)
  nextRoute = null
}

const onSubmit = () => {
  refVForm.value?.validate().then(async ({ valid: isValid }) => {
    if (!isValid)
      return

    const formData = new FormData()
    const selectedPresetAvatar = avatarOptions.find(option => option.src === avatarPreview.value)

    formData.append('user_id', userId.value)
    formData.append('email', email.value)
    formData.append('name', name.value)
    formData.append('last_name', last_name.value)
    formData.append('personal_phone', phone.value)
    formData.append('personal_address', address.value)

    if (selectedPresetAvatar) {
      const selectedPresetAvatarFile = await presetAvatarToFile(selectedPresetAvatar)

      formData.append('image', selectedPresetAvatarFile)
      formData.append('avatar_id', selectedPresetAvatar.id)
    } else {
      formData.append('image', avatarOld.value)
      formData.append('avatar_id', avatarId.value ?? '')
    }

    formData.append('logo', userData.value?.user_detail?.logo ?? '')
    formData.append('company', userData.value?.user_detail?.company ?? '')
    formData.append('organization_number', userData.value?.user_detail?.organization_number ?? '')
    formData.append('address', userData.value?.user_detail?.address ?? '')
    formData.append('street', userData.value?.user_detail?.street ?? '')
    formData.append('postal_code', userData.value?.user_detail?.postal_code ?? '')
    formData.append('phone', userData.value?.user_detail?.phone ?? '')
    formData.append('link', userData.value?.user_detail?.link ?? '')
    formData.append('bank', userData.value?.user_detail?.bank ?? '')
    formData.append('iban', userData.value?.user_detail?.iban ?? '')
    formData.append('account_number', userData.value?.user_detail?.account_number ?? '')
    formData.append('iban_number', userData.value?.user_detail?.iban_number ?? '')
    formData.append('bic', userData.value?.user_detail?.bic ?? '')
    formData.append('plus_spin', userData.value?.user_detail?.plus_spin ?? '')
    formData.append('swish', userData.value?.user_detail?.swish ?? '')
    formData.append('vat', userData.value?.user_detail?.vat ?? '')

    isRequestOngoing.value = true

    profileStores.updateData(formData)
      .then(response => {
        if (!response) {
          throw new Error('Profile update failed')
        }

        advisor.value.type = 'success'
        advisor.value.message = 'Uppgifterna har sparats.'
        advisor.value.show = true

        localStorage.setItem('user_data', JSON.stringify(response.user_data))
        isRequestOngoing.value = false
        syncInitialProfileSnapshot()
      })
      .catch(() => {
        advisor.value.type = 'error'
        advisor.value.show = true
        advisor.value.message = 'Ett serverfel uppstod. Försök igen.'
        isRequestOngoing.value = false

        setTimeout(() => {
          advisor.value.show = false
          advisor.value.message = ''
        }, 5000)
      })
  })
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  loadUserData();
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});
</script>

<template>
    <section class="page-section bg-white" ref="sectionEl">
      <LoadingOverlay :is-loading="isRequestOngoing" />
      <VSnackbar
        v-model="advisor.show"
        transition="scroll-y-reverse-transition"
        :location="snackbarLocation"
        :color="advisor.type"
        class="snackbar-alert snackbar-dashboard"
      >
        {{ advisor.message }}
      </VSnackbar>

      <VCard class="card-fill">
        <VCardText class="pb-0" v-if="windowWidth < 1024">
          <div class="d-flex flex-column gap-4 flex-1">
            <VBtn
              class="btn-light"
              style="width: 120px;"
              :to="{ name: 'dashboard-settings' }"
            >
              <VIcon icon="custom-return" size="24" />
              Tillbaka
            </VBtn>

            <span class="title-settings pb-4 border-bottom-settings">
              Min profil
            </span>
          </div>
        </VCardText>
        <VCardText>
          <div class="settings-layout">
            <div class="settings-layout__sidebar">
              <div class="d-flex flex-column gap-4">
                <span class="subtitle-settings">Personliga uppgifter</span>
                <span class="text-settings">
                  Hantera din profil och dina kontaktuppgifter. 
                  Håll din information uppdaterad för att säkerställa att ditt konto alltid är korrekt.
                </span>
              </div>
            </div>
            <div class="settings-layout__content">
              <VForm
                ref="refVForm"
                class="card-form"
                @submit.prevent="onSubmit"
              >
                <div class="d-flex flex-column gap-6">
                  <div class="w-100">
                    <div 
                        class="d-flex flex-column"
                        :class="windowWidth >= 1024 ? 'align-start justify-start' : 'align-center justify-center'"
                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                    >
                      <span class="avatar-text">
                        Avatar
                      </span> 

                      <VBtn
                        class="upload-avatar-btn"
                        @click="showConfirmEditAvatarDialog"
                        style="padding: 0; min-width: auto"
                      >
                        <VAvatar class="avatar-box" size="128">
                          <VImg
                            v-if="avatarPreview"
                            style="border-radius: 6px;"
                            :src="avatarPreview"
                          />
                          <PresetAvatarImage
                            v-else
                            :radius="0"
                            :avatar-id="avatarId"
                          />
                        </VAvatar>
                      </VBtn>

                      <input
                        ref="refAvatarFileInput"
                        type="file"
                        data-avatar-upload="true"
                        accept="image/png, image/jpeg, image/bmp"
                        @change="onAvatarSelected"
                        style="display: none"
                      >

                      <div class="d-flex align-center gap-2 my-2 my-md-0 flex-wrap justify-center">
                        <VBtn 
                          class="btn-light w-auto d-none" 
                          block
                          @click="deleteAvatar"
                        >
                          <VIcon icon="custom-waste" size="24" />
                          Ta bort avatar
                        </VBtn>  
                        <span class="name-profile">
                          {{ name }} {{ last_name }}
                        </span>     
                      </div>
                    </div>
                  </div>

                  <div class="d-flex flex-column gap-4">
                    <div 
                        class="d-flex flex-wrap"
                        :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                    >
                        <div :style=" windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                          <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Namn*" />
                          <VTextField
                            v-model="name"
                            :rules="[requiredValidator]"
                          />
                        </div>
                        <div :style=" windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                          <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Efternamn*" />
                          <VTextField
                            v-model="last_name"
                            :rules="[requiredValidator]"
                          />
                        </div>
                        <div :style=" windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                          <VLabel class="mb-1 text-body-profile text-high-emphasis" text="E-post*" />
                          <VTextField
                            v-model="email"
                            type="email"
                            :rules="[requiredValidator, emailValidator]"
                            disabled
                          />
                        </div>
                        <div :style=" windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                          <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Telefon*" />
                          <VTextField
                            v-model="phone"
                            placeholder="+(XX) XXXXXXXXX"
                            :rules="[requiredValidator, phoneValidator]"
                          />
                        </div>
                        <div :style=" windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                          <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Adress*" />
                          <VTextField
                            v-model="address"
                            :rules="[requiredValidator]"
                          />
                        </div>
                    </div>
                  </div>
                </div>

                <!-- 👉 Form Actions -->
                <div 
                  class="d-flex justify-start gap-3 flex-wrap dialog-actions4"
                  :class="windowWidth < 1024 ? 'mt-4' : 'mt-8'"
                >
                
                  <VBtn 
                    type="submit" 
                    class="btn-gradient"
                    :class="windowWidth < 1024 ? 'w-100' : 'w-25'"
                  >
                    Spara
                  </VBtn>
                </div>
              </VForm>
            </div>
          </div>
        </VCardText>
      </VCard>

      <VDialog
        v-model="dialog"
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
            <img :src="modalWarningIcon" alt="Warning" class="action-icon">
            <div class="dialog-title">
              Avsluta utan att spara
            </div>
          </VCardText>
          <VCardText class="dialog-text">
            <strong>Du har osparade ändringar.</strong> Är du säker på att du vill lämna sidan?
          </VCardText>
          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn
              class="btn-light"
              @click="cancelLeave"
            >
              Avbryt
            </VBtn>
            <VBtn
              class="btn-gradient"
              @click="confirmLeave"
            >
              Ja, fortsätt
            </VBtn>
          </VCardText>
        </VCard>
      </VDialog>

      <VDialog
        v-model="isConfirmEditAvatarVisible"
        :z-index="3000"
        persistent
        class="action-dialog"
      >
        <VBtn
          icon
          class="btn-white close-btn"
          @click="isConfirmEditAvatarVisible = false"
        >
          <VIcon size="16" icon="custom-close" />
        </VBtn>

        <VCard>
          <VCardText class="dialog-title-box">
            <VIcon size="32" icon="custom-name" class="action-icon" />
            <div class="dialog-title">
              Välj en avatar
            </div>
          </VCardText>
          <VCardText class="dialog-text" style="overflow-y: auto; overflow-x: hidden;">
            <div class="avatar-picker-grid">
              <button
                v-for="item in avatarOptions"
                :key="item.id"
                type="button"
                class="avatar-picker-item"
                :class="selectedDialogAvatar === item.id ? 'is-selected' : ''"
                @click="selectDialogAvatar(item.id)"
              >
                <img
                  :src="item.src"
                  :alt="`Avatar ${item.id}`"
                  class="avatar-picker-image"
                >
                <span
                  v-if="selectedDialogAvatar === item.id"
                  class="avatar-picker-check"
                >
                  <VIcon v-if="selectedDialogAvatar === item.id" size="72" icon="custom-check-avatar" class="action-icon" />
                </span>
              </button>
            </div>
          </VCardText>

          <VCardText class="dialog-text my-6">
            <VDivider />
          </VCardText>

          <VCardText class="dialog-text">
            <VBtn
              class="btn-light w-100"
              block
              @click="triggerAvatarUpload"
            >
              <VIcon size="24" icon="custom-upload" />
              Ladda upp bild
            </VBtn>
          </VCardText>

          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn class="btn-gradient" :disabled="!selectedDialogAvatar" @click="applySelectedAvatar">
              Spara
            </VBtn>
          </VCardText>
        </VCard>
      </VDialog>
    </section>
</template>

<style lang="scss">
  .avatar-text {
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;
    color: #454545;
  }

  .name-profile {
    font-weight: 700;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;
    color: #878787;
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
            @media (max-width: 991px) {
              top: 12px !important;
            }
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0;
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
        top: 0;
        left: 0;
      }
    }
  }

  .avatar-picker-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(88px, 1fr));
    gap: 16px;
    justify-items: center;

    @media (max-width: 600px) {
      grid-template-columns: repeat(2, minmax(88px, 1fr));
      gap: 32px;
    }
  }

  .avatar-picker-item {
    position: relative;
    display: grid;
    place-items: center;
    inline-size: 138px;
    block-size: 138px;
    border-radius: 50%;
    border: 0;
    cursor: pointer;
    transition: background-color 0.2s ease;
  }

  .avatar-picker-image {
    inline-size: 138px;
    block-size: 138px;
    border-radius: 50%;
    object-fit: cover;
  }

  .avatar-picker-check {
    position: absolute;
    display: grid;
    place-items: center;
    inline-size: 138px;
    block-size: 138px;
    border-radius: 50%;
    border: 0;
    cursor: pointer;
    transition: background-color 0.2s ease;
    background: linear-gradient(90deg, rgba(216, 255, 228, 0.9) 0%, rgba(198, 255, 235, 0.9) 50%, rgba(192, 254, 255, 0.9) 100%);
  }

</style>

<route lang="yaml">
  meta:
    navActiveLink: dashboard-settings
    action: view
    subject: dashboard
</route>
