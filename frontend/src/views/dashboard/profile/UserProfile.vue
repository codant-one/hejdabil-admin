<script setup>

import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { useProfileStores } from '@/stores/useProfile'
import avatar1 from '@/assets/images/avatars/1.svg'
import avatar2 from '@/assets/images/avatars/2.svg'
import avatar3 from '@/assets/images/avatars/3.svg'
import avatar4 from '@/assets/images/avatars/4.svg'
import avatar5 from '@/assets/images/avatars/5.svg'
import avatar6 from '@/assets/images/avatars/6.svg'
import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  avatarOld: {
    type: [String, Object, null],
    required: true
  },
  avatar: {
    type: [String, null],
    required: true
  },
  haveAvatar: {
    type: Boolean,
    required: true
  },
  avatarId: {
    type: [String, Number, null],
    required: true
  }
})

const emit = defineEmits([
  'onImageSelected',
  'loading',
  'alert'
])

const { width: windowWidth } = useWindowSize();

const profileStores = useProfileStores()

const refVForm = ref()
const refAlert = ref()
const refAvatarFileInput = ref()
const isUserEditDialog = ref(false)

const userData = ref(props.user)
const user_id = ref('')
const email = ref('')
const name = ref('')
const last_name = ref('')
const phone = ref('')
const address = ref('')
const avatarCommitted = ref(props.avatar)
const avatarPreview = ref(props.avatar)

const avatarOld = ref(props.avatarOld)

const isConfirmEditAvatarVisible = ref(false)
const selectedDialogAvatar = ref(props.avatarId)

const avatarOptions = [
  { id: 1, src: avatar1 },
  { id: 2, src: avatar2 },
  { id: 3, src: avatar3 },
  { id: 4, src: avatar4 },
  { id: 5, src: avatar5 },
  { id: 6, src: avatar6 },
]

const alert = ref({
    message: '',
    show: false,  
    type: ''
})

watch(() =>  
  props.avatar, (avatar_) => {
    if (!isUserEditDialog.value)
      avatarCommitted.value = avatar_

    avatarPreview.value = avatar_
  });

watch(() => 
  props.avatarOld, (avatarOld_) => {
    avatarOld.value = avatarOld_
  });

watch(() => alert.value.show, (show) => {
  if (show) {
    nextTick(() => {
      refAlert.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
  }
})

watchEffect(fetchData)

async function fetchData() { 

  user_id.value = userData.value.id
  email.value = userData.value.email
  name.value = userData.value.name
  last_name.value = userData.value.last_name ?? ''
  phone.value = userData.value.user_detail?.personal_phone
  address.value = userData.value.user_detail?.personal_address
}

const resetAvatar = () => {
  avatarPreview.value = null
}

const presetAvatarToFile = async selectedPresetAvatar => {
  const response = await fetch(selectedPresetAvatar.src)
  const blob = await response.blob()
  const extension = blob.type?.split('/')[1] || 'png'

  return new File([blob], `avatar-${selectedPresetAvatar.id}.${extension}`, {
    type: blob.type || 'application/octet-stream',
  })
}

const onSubmit = () => {
  refVForm.value?.validate().then(async ({ valid: isValid }) => {
    if (isValid) {

      let formData = new FormData()
      const selectedPresetAvatar = avatarOptions.find(option => option.src === avatarPreview.value)
      
      formData.append('user_id', user_id.value)
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
        formData.append('avatar_id', props.avatarId)
      }

      formData.append('logo',  userData.value.user_detail?.logo)
      formData.append('company', userData.value.user_detail?.company)
      formData.append('organization_number', userData.value.user_detail?.organization_number)
      formData.append('address', userData.value.user_detail?.address)
      formData.append('street', userData.value.user_detail?.street)
      formData.append('postal_code', userData.value.user_detail?.postal_code)
      formData.append('phone', userData.value.user_detail?.phone)
      formData.append('link', userData.value.user_detail?.link)
      formData.append('bank', userData.value.user_detail?.bank)
      formData.append('iban', userData.value.user_detail?.iban)
      formData.append('account_number', userData.value.user_detail?.account_number)       
      formData.append('iban_number', userData.value.user_detail?.iban_number)
      formData.append('bic', userData.value.user_detail?.bic)
      formData.append('plus_spin', userData.value.user_detail?.plus_spin)
      formData.append('swish', userData.value.user_detail?.swish)
      formData.append('vat', userData.value.user_detail?.vat)

      emit("loading", true);
      isUserEditDialog.value = false

      profileStores.updateData(formData)
        .then(response => {    

          alert.value.type = 'success'
          alert.value.message = 'Uppgifterna har sparats. Sidan laddas om automatiskt för att visa ändringarna.'
          alert.value.show = true
          emit("alert", alert);

          localStorage.setItem('user_data', JSON.stringify(response.user_data))

          // Commit avatar preview to profile card only after successful save.
          avatarCommitted.value = avatarPreview.value
          emit("loading", false);

          setTimeout(() => {
            location.reload()
          }, 3000)

        }).catch(error => {
          
          alert.value.type = 'error'
          alert.value.show = true
          alert.value.message = 'Ett serverfel uppstod. Försök igen.'

          emit("alert", alert);
          emit("loading", false);

          setTimeout(() => {
            alert.value.show = false,
            alert.value.message = ''
            emit("alert", alert);
          }, 5000) 
        })
      }
            
    })
}

const deleteAvatar = ()=>{
    avatarOld.value = null
    resetAvatar()
}

const showUserEditDialog = u =>{
  avatarPreview.value = avatarCommitted.value
  avatarOld.value = props.avatarOld
  isUserEditDialog.value = true
}

const showConfirmEditAvatarDialog = () => {
  const selectedFromCurrent = avatarOptions.find(option => option.src === avatarPreview.value)
  selectedDialogAvatar.value = selectedFromCurrent?.id ?? props.avatarId
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
  isConfirmEditAvatarVisible.value = false
}

const onAvatarSelected = event => {
  emit('onImageSelected', event)

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
    const fileInput = refAvatarFileInput.value?.$el?.querySelector('input[type="file"]')
    fileInput?.click()
  })
}

const closeUserEditDialog = ()=>{
  isUserEditDialog.value = false
  avatarPreview.value = avatarCommitted.value
  avatarOld.value = props.avatarOld
}
</script>

<template>
  <section>
    <VCardText class="p-0">
      <div class="bg-alert">
        <div 
          class="d-flex"
          :class="windowWidth < 1024 ? 'flex-column gap-4' : 'justify-between gap-7'">
          <!-- 👉 Details -->
          <div 
            :class="windowWidth < 1024 ? 'justify-center' : 'px-0'"
            class="d-flex align-center"
          >
            <VAvatar
              rounded
              :size="144"
              :color="avatarCommitted ? 'default' : 'primary'"
              variant="tonal"
            >
              <VImg
                v-if="avatarCommitted"
                :src="avatarCommitted"
              />
              <PresetAvatarImage
                v-else
                :radius="0"
                :avatar-id="avatarId"
              />
            </VAvatar>
          </div>
        
          <!-- 👉 Details -->
          <div
            class="d-flex align-center w-100"
            :class="windowWidth < 1024 ? 'flex-column py-2' : 'px-4'"
          >
            <div class="profile-info-grid">
              <div class="profile-info-item profile-info-col-3">
                <span class="text-body-profile">
                  <VIcon
                    class="me-1"
                      icon="custom-user-profile"
                      size="16"
                  />
                  Namn
                </span>
                <span class="span-body-profile">
                  {{ name }}
                </span>
              </div>
              <div class="profile-info-item profile-info-col-3">
                <span class="text-body-profile">
                  <VIcon
                    class="me-1"
                    icon="custom-user-profile"
                    size="16"
                  />
                  Efternamn
                </span>
                <span class="span-body-profile">
                  {{ last_name }}
                </span>
              </div>
              <div class="profile-info-item profile-info-col-6">
                <span class="text-body-profile">
                  <VIcon
                    class="me-1"
                    icon="custom-email-profile"
                    size="16"
                  />
                  E-post
                </span>
                <span class="span-body-profile">
                  {{ email }}
                </span>
              </div>
              <div class="profile-info-item profile-info-col-3">
                <span class="text-body-profile">
                  <VIcon
                    class="me-1"
                    icon="custom-phone-profile"
                    size="16"
                  />
                  Telefon
                </span>
                <span class="span-body-profile">
                  {{ phone }}
                </span>
              </div>
              <div class="profile-info-item profile-info-col-8">
                <span class="text-body-profile">
                  <VIcon
                    class="me-1"
                    icon="custom-location-profile"
                    size="16"
                  />
                  Adress
                </span>
                <span class="span-body-profile">
                  {{ address }}
                </span>
              </div>                
            </div>
          </div>

          <!-- 👉 Edit and Suspend button -->
          <div
            :class="windowWidth < 1024 ? 'w-100' : 'px-0'"
          >
            <div class="d-flex gap-4"
              :class="windowWidth < 1024 ? 'w-100' : 'align-center'"
            >
              <VBtn 
                class="btn-light w-auto" 
                block
                @click="showUserEditDialog()"
              >
                <VIcon icon="custom-pencil" size="24" />
                Redigera
              </VBtn>
          </div>
          </div>
        </div>
      </div>
    </VCardText>
      
    <!-- DIALOG Edit personal information desktop -->
    <VDialog
      v-if="windowWidth >= 1024"
      v-model="isUserEditDialog"
      :width="windowWidth < 1024 ? '' : '1022'"
      class="action-dialog"
      scrollable
      persistent
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="closeUserEditDialog"
        >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>    
        <VCardTitle class="dialog-title-box mt-2">
          <VIcon size="32" icon="custom-user-outlined" />
          <div class="dialog-title" style="white-space: pre-line">Redigera personlig information</div>
        </VCardTitle>        
        <VCardText class="p-0">
          <VForm
            ref="refVForm"
            class="card-form"
            @submit.prevent="onSubmit"
          >
            <div class="dialog-form-grid">
              <div class="dialog-form-col-5">
                <div 
                    class="bg-alert ms-4"
                    :class="windowWidth < 1024 ? 'flex-column me-4' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                  <span class="d-block d-md-flex text-center justify-center">
                    <VAvatar
                      rounded
                      :size="120"
                      class="mb-2"
                      :color="avatarPreview ? 'default' : 'primary'"
                      variant="tonal"
                    >
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
                  </span>
                  <!-- 👉 Upload Photo -->
                  <div class="d-flex justify-center gap-2 my-2 my-md-0">
                    <div class="d-none flex-wrap gap-2">
                      <VIcon size="48" icon="custom-camera" />
                      <VFileInput                          
                        ref="refAvatarFileInput"
                        accept="image/png, image/jpeg, image/bmp"
                        placeholder="Avatar"
                        prepend-icon=""
                        @change="onAvatarSelected"
                        @click:clear="resetAvatar"
                      />
                    </div>
                    <VBtn 
                      class="btn-light w-auto d-none" 
                      block
                      @click="deleteAvatar"
                    >
                      <VIcon icon="custom-waste" size="24" />
                      Ta bort avatar
                    </VBtn>     
                    <VBtn 
                      class="btn-light w-auto" 
                      @click="showConfirmEditAvatarDialog"
                    >
                      <VIcon icon="custom-pencil" size="24" />
                      Redigera
                    </VBtn>        
                  </div>
                </div>
              </div>

              <div class="dialog-form-col-7">
                <div 
                    class="d-flex flex-wrap me-4"
                    :class="windowWidth < 1024 ? 'ms-4 flex-column' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                      <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Namn*" />
                      <VTextField
                        v-model="name"
                        :rules="[requiredValidator]"
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                      <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Efternamn*" />
                      <VTextField
                        v-model="last_name"
                        :rules="[requiredValidator]"
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                      <VLabel class="mb-1 text-body-profile text-high-emphasis" text="E-post*" />
                      <VTextField
                        v-model="email"
                        type="email"
                        :rules="[requiredValidator, emailValidator]"
                        disabled
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                      <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Telefon*" />
                      <VTextField
                        v-model="phone"
                        placeholder="+(XX) XXXXXXXXX"
                        :rules="[requiredValidator, phoneValidator]"
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
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
              class="d-flex justify-end gap-3 flex-wrap dialog-actions"
              :class="windowWidth < 1024 ? 'px-4' : 'my-4 me-4'"
            >
              <VBtn
                class="btn-light"
                :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                @click="closeUserEditDialog"
                >
                <VIcon icon="custom-return" size="24" />
                Avbryt
              </VBtn>
              <VBtn 
                type="submit" 
                class="btn-gradient"
                :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
              >
                <VIcon icon="custom-save"  size="24" />
                Spara
              </VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog> 

    <!-- DIALOG Edit personal information mobile -->
    <VDialog
      v-else
      v-model="isUserEditDialog"
      fullscreen
      persistent
      :scrim="false"
      transition="dialog-bottom-transition"
      class="action-dialog dialog-fullscreen"
      content-class="clients-pending-mobile-fullscreen">

      <VBtn
          icon
          class="btn-white close-btn"
          @click="closeUserEditDialog"
      >
          <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>    
        <VCardTitle class="dialog-title-box mt-2">
          <VIcon size="32" icon="custom-user-outlined" />
          <div class="dialog-title" style="white-space: pre-line; font-size: 22px!important;">Redigera personlig information</div>
        </VCardTitle>        
        <VCardText class="p-0" style="overflow-y: auto; overflow-x: hidden;">
          <VForm
            ref="refVForm"
            class="card-form"
            @submit.prevent="onSubmit"
          >
            <div class="dialog-form-grid">
              <div class="dialog-form-col-5">
                <div 
                    class="bg-alert ms-4"
                    :class="windowWidth < 1024 ? 'flex-column me-4' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                  <span class="d-block d-md-flex text-center justify-center">
                    <VAvatar
                      rounded
                      :size="120"
                      class="mb-2"
                      :color="avatarPreview ? 'default' : 'primary'"
                      variant="tonal"
                    >
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
                  </span>
                  <!-- 👉 Upload Photo -->
                  <div class="d-flex justify-center gap-2 my-2 my-md-0">
                    <div class="d-none flex-wrap gap-2">
                      <VIcon size="48" icon="custom-camera" />
                      <VFileInput                          
                        ref="refAvatarFileInput"
                        accept="image/png, image/jpeg, image/bmp"
                        placeholder="Avatar"
                        prepend-icon=""
                        @change="onAvatarSelected"
                        @click:clear="resetAvatar"
                      />
                    </div>
                    <VBtn 
                      class="btn-light w-auto d-none" 
                      block
                      @click="deleteAvatar"
                    >
                      <VIcon icon="custom-waste" size="24" />
                      Ta bort avatar
                    </VBtn>     
                    <VBtn 
                      class="btn-light w-auto" 
                      @click="showConfirmEditAvatarDialog"
                    >
                      <VIcon icon="custom-pencil" size="24" />
                      Redigera
                    </VBtn>        
                  </div>
                </div>
              </div>

              <div class="dialog-form-col-7">
                <div 
                    class="d-flex flex-wrap me-4"
                    :class="windowWidth < 1024 ? 'ms-4 flex-column' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                      <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Namn*" />
                      <VTextField
                        v-model="name"
                        :rules="[requiredValidator]"
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                      <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Efternamn*" />
                      <VTextField
                        v-model="last_name"
                        :rules="[requiredValidator]"
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                      <VLabel class="mb-1 text-body-profile text-high-emphasis" text="E-post*" />
                      <VTextField
                        v-model="email"
                        type="email"
                        :rules="[requiredValidator, emailValidator]"
                        disabled
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                      <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Telefon*" />
                      <VTextField
                        v-model="phone"
                        placeholder="+(XX) XXXXXXXXX"
                        :rules="[requiredValidator, phoneValidator]"
                      />
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
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
              class="d-flex justify-end gap-3 flex-wrap dialog-actions"
              :class="windowWidth < 1024 ? 'px-4' : 'my-4 me-4'"
            >
              <VBtn
                class="btn-light"
                :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                @click="closeUserEditDialog"
                >
                <VIcon icon="custom-return" size="24" />
                Avbryt
              </VBtn>
              <VBtn 
                type="submit" 
                class="btn-gradient"
                :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
              >
                <VIcon icon="custom-save"  size="24" />
                Spara
              </VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm edit avatar -->
    <VDialog
      v-model="isConfirmEditAvatarVisible"
      :z-index="3000"
      persistent
      class="action-dialog" >

      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmEditAvatarVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-name" class="action-icon" />
          <div class="dialog-title">
            Välj en avatar
          </div>
        </VCardText>
        <VCardText class="dialog-text" style="overflow-y: auto; overflow-x: hidden;">>
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
            @click="triggerAvatarUpload">
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
  .v-list-item-title {
    white-space: normal;
  }

  .bg-alert {
    background: linear-gradient(90deg, #EAFFF1 0%, #EAFFF8 50%, #ECFFFF 100%);
    border-radius: 16px;
    gap: 16px;
    opacity: 1;
    padding: 16px;
  }

  .text-body-profile {
    font-weight: 400;
    font-size: 14px;
    line-height: 24px;
    letter-spacing: 0;
    color: #878787 !important;
  }

  .span-body-profile {
    font-weight: 700;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: 0;
    color: #454545 !important;

    @media (max-width: 1024px) {
      font-size: 14px;
    }
  }

  .profile-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    width: 100%;

    @media (min-width: 1024px) {
      grid-template-columns: repeat(12, 1fr);
    }
  }

  .profile-info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;

    @media (max-width: 1023px) {
      grid-column: span 1;
    }

    &.profile-info-col-3 {
      @media (min-width: 1024px) {
        grid-column: span 3;
      }
    }

    &.profile-info-col-6 {
      grid-column: span 2;

      @media (min-width: 1024px) {
        grid-column: span 6;
      }
    }

    &.profile-info-col-8 {
      grid-column: span 2;

      @media (min-width: 1024px) {
        grid-column: span 8;
      }
    }
  }

  .dialog-form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;

    @media (min-width: 1024px) {
      grid-template-columns: repeat(12, 1fr);
    }
  }

  .dialog-form-col-5 {
    @media (max-width: 1023px) {
      grid-column: span 1;
    }

    @media (min-width: 1024px) {
      grid-column: span 5;
    }
  }

  .dialog-form-col-7 {
    @media (max-width: 1023px) {
      grid-column: span 1;
    }

    @media (min-width: 1024px) {
      grid-column: span 7;
    }
  }

  .card-info {
        background-color: #F6F6F6;
        border-radius: 16px;
    }

    .title-tabs {
        font-weight: 700;
        font-size: 24px;
        line-height: 100%;
        color: #454545;

        @media (max-width: 1023px) {
            font-size: 16px
        }
    }

    .list-kopare {
        font-size: 16px;
        line-height: 100%;
        font-weight: 700;

        span {
            font-weight: 400;
            font-size: 16px;
        }
    }
    
    .title-kopare {
        font-weight: 700;
        font-size: 24px;
        line-height: 100%;
        color: #878787;

        @media (max-width: 1023px) {
            font-size: 16px
        }
    }

    .title-page {
        font-weight: 700;
        font-size: 32px;
        line-height: 100%;
        color: #1C2925;

        @media (max-width: 1023px) {
            font-size: 24px
        }
    }

    .subtitle-page {
        font-weight: 400;
        font-size: 24px;
        line-height: 100%;
        color: #878787;
    }

    .v-btn--disabled {
        opacity: 1 !important;
    }

    .border-bottom-secondary {
        border-bottom: 1px solid #d9d9d9;
        padding-bottom: 10px;
    }

    .justify-content-end {
        justify-content: end !important;
    }

    .info-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;

        .info-item {
            flex: 0 0 calc(100% / 7 - 14px);
            min-width: 0;

            span  {
                font-weight: 400;
                font-size: 16px;
                line-height: 24px;
                color: #454545;
            }

            .value-field {
                background-color: #F6F6F6;
                border-radius: 8px;
                border: 1px solid #E7E7E7;
                padding: 16px;
                height: 48px !important;
                align-items: center;
                display: flex;
                font-weight: 400;
                font-size: 12px;
                line-height: 24px;
                color: #5D5D5D;
            }

            @media (max-width: 1023px) {
                flex: 0 0 calc(50% - 8px);
            }
        }
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
