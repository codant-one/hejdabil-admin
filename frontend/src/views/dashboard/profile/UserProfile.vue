<script setup>

import { avatarText } from '@/@core/utils/formatters'
import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { useProfileStores } from '@/stores/useProfile'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

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
  }
})

const emit = defineEmits([
  'onImageSelected',
])

const { width: windowWidth } = useWindowSize();

const profileStores = useProfileStores()

const refVForm = ref()
const refAlert = ref()
const isUserEditDialog = ref(false)
const isRequestOngoing = ref(false)

const userData = ref(props.user)
const user_id = ref('')
const email = ref('')
const name = ref('')
const last_name = ref('')
const phone = ref('')
const address = ref('')
const avatar = ref(props.avatar)

const avatarOld = ref(props.avatarOld)

const alert = ref({
    message: '',
    show: false,  
    type: ''
})

watch(() =>  
  props.avatar, (avatar_) => {
    avatar.value = avatar_
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
  avatar.value = null
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {

      let formData = new FormData()
      
      formData.append('user_id', user_id.value)
      formData.append('email', email.value)
      formData.append('name', name.value)
      formData.append('last_name', last_name.value)
      formData.append('personal_phone', phone.value)
      formData.append('personal_address', address.value)
      formData.append('image', avatarOld.value)

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

      isRequestOngoing.value = true

      profileStores.updateData(formData)
        .then(response => {    

          window.scrollTo(0, 0)
                    
          alert.value.type = 'success'
          alert.value.message = 'Uppgifterna har sparats. Sidan laddas om automatiskt för att visa ändringarna.'
          alert.value.show = true
                    
          localStorage.setItem('user_data', JSON.stringify(response.user_data))
                    
          fetchData()

          setTimeout(() => {
            alert.value.show = false,
            alert.value.message = ''
            location.reload()
          }, 5000)

          isRequestOngoing.value = false

        }).catch(error => {
          alert.value.type = 'error'
          alert.value.show = true
          alert.value.message = 'Ett serverfel uppstod. Försök igen.'
                    
          setTimeout(() => {
            alert.value.show = false,
            alert.value.message = ''
          }, 5000) 

          isRequestOngoing.value = false
        })
      }
            
    })
}

const deleteAvatar = ()=>{
    avatarOld.value = null
    resetAvatar()
}

const showUserEditDialog = u =>{
  isUserEditDialog.value = true
}

const closeUserEditDialog = ()=>{
  isUserEditDialog.value = false
  fetchData()
}
</script>

<template>
  <section>
    <LoadingOverlay :is-loading="isRequestOngoing" />

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
              :color="avatar ? 'default' : 'primary'"
              variant="tonal"
            >
              <VImg
                v-if="avatar"
                style="border-radius: 16px;"
                :src="avatar"
              />
              <span
                v-else
                class="text-5xl font-weight-semibold"
              >
                {{ avatarText(name) }}
              </span>
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
      
    <!-- DIALOG Edit personal information -->
    <VDialog
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
          <VCol 
            v-if="alert.show" 
            cols="12"
            class="px-4 py-0 mb-4"
          >
            <VAlert
              ref="refAlert"
              v-if="alert.show"
              :color="alert.type"
              class="alert-no-shrink custom-alert mt-4"
              style="flex: none;"
            >
              <VAlertTitle>{{ alert.message }}</VAlertTitle>
            </VAlert>

          </VCol>

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
                  <span class="d-block d-md-flex text-center justify-start">
                    <VAvatar
                      rounded
                      :size="120"
                      class="me-md-6 mb-2"
                      :color="avatar ? 'default' : 'primary'"
                      variant="tonal"
                    >
                      <VImg
                        v-if="avatar"
                        style="border-radius: 6px;"
                        :src="avatar"
                      />
                      <span
                        v-else
                        class="text-5xl font-weight-semibold"
                      >
                        {{ avatarText(name) }}
                      </span>
                    </VAvatar>
                  </span>
                  <!-- 👉 Upload Photo -->
                  <div class="d-flex flex-column justify-center gap-2 my-2 my-md-0">
                    <div class="d-flex flex-wrap gap-2">
                      <VIcon size="48" icon="custom-camera" />
                      <VFileInput                          
                        accept="image/png, image/jpeg, image/bmp"
                        placeholder="Avatar"
                        prepend-icon=""
                        @change="$emit('onImageSelected', $event)"
                        @click:clear="resetAvatar"
                      />
                    </div>
                    <VLabel class="mb-1 text-body-profile text-high-emphasis" text="Tillåtna format JPG, GIF, PNG." />
                    <VBtn 
                      class="btn-light w-auto" 
                      block
                      @click="deleteAvatar"
                    >
                      <VIcon icon="custom-waste" size="24" />
                      Ta bort avatar
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
</style>
