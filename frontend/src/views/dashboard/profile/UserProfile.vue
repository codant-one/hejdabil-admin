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
const roles = ref('')

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
    <VRow>
      <LoadingOverlay :is-loading="isRequestOngoing" />

      <VCol cols="12">
        <VCard>
          <VCardText class="pt-6 px-0">
            <div class="bg-alert">
              <VRow class="px-md-3 ">
                <!-- 👉 Details -->
                <VCol 
                  cols="12" 
                  sm="12" 
                  md="2" 
                  :class="windowWidth < 1024 ? '' : 'px-0'"
                  class="d-flex align-center justify-center"
                >
                  <VAvatar
                    rounded
                    :size="100"
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
                  <h6 class="text-h6 mt-4" style="display: none;">
                    {{ name.toUpperCase() }} {{ last_name.toUpperCase() }}
                  </h6>

                  <!-- 👉 Role chip -->
                  <VChip
                    v-for="rol in roles"
                    :key="rol"
                    label
                    size="small"
                    class="text-capitalize mt-4 mr-1"
                  >
                    {{ rol.name }}
                  </VChip>
                </VCol>
              
                <!-- 👉 Details -->
                <VCol 
                  cols="12" 
                  sm="12" 
                  md="8" 
                  :class="windowWidth < 1024 ? '' : 'px-4'"
                >
                  <VRow>
                    <VCol 
                      cols="6" 
                      sm="6" 
                      md="3" 
                      :class="windowWidth < 1024 ? '' : 'px-0'"
                    >
                      <span class="text-body-2">
                        <VIcon
                          class="me-1"
                          icon="mdi-account"
                          size="17"
                        />
                        Namn:
                      </span>
                      <h6 class="text-base font-weight-semibold">
                        {{ name }}
                      </h6>
                    </VCol>
                    <VCol 
                      cols="6" 
                      sm="6" 
                      md="3" 
                      :class="windowWidth < 1024 ? '' : 'px-0'"
                    >
                      <span class="text-body-2">
                        <VIcon
                          class="me-1"
                          icon="mdi-account"
                          size="17"
                        />
                        Efternamn:
                      </span>
                      <h6 class="text-base font-weight-semibold">
                        {{ last_name }}
                      </h6>
                    </VCol>
                    <VCol 
                      cols="6" 
                      sm="6" 
                      md="6" 
                      :class="windowWidth < 1024 ? '' : 'px-0'"
                    >
                      <span class="text-body-2">
                        <VIcon
                          class="me-1"
                          icon="mdi-email"
                          size="17"
                        />
                        E-post:
                      </span>
                      <h6 class="text-base font-weight-semibold">
                        {{ email }}
                      </h6>
                    </VCol>
                    <VCol 
                      cols="6" 
                      sm="6" 
                      md="3" 
                      :class="windowWidth < 1024 ? '' : 'px-0'"
                    >
                      <span class="text-body-2">
                        <VIcon
                          class="me-1"
                          icon="mdi-phone"
                          size="17"
                        />
                        Telefon:
                      </span>
                      <h6 class="text-base font-weight-semibold">
                        {{ phone }}
                      </h6>
                    </VCol>
                    <VCol 
                      cols="12" 
                      sm="12" 
                      md="8" 
                      :class="windowWidth < 1024 ? '' : 'px-0'"
                    >
                      <span class="text-body-2">
                        <VIcon
                          class="me-1"
                          icon="mdi-map-marker"
                          size="17"
                        />
                        Adress:
                      </span>
                      <h6 class="text-base font-weight-semibold">
                        {{ address }}
                      </h6>
                    </VCol>
                    
                  </VRow>
                </VCol>

                <!-- 👉 Edit and Suspend button -->
                <VCol 
                  cols="12" 
                  sm="12" 
                  md="2" 
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
                </VCol>
              </VRow>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- DIALOG Edit personal information -->
      <VDialog
        v-model="isUserEditDialog"
        max-width="800"
        :width="windowWidth < 1024 ? '' : '800'"
        class="action-dialog"
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
          <VCardText class="dialog-title-box mt-2">
            <VIcon size="32" icon="custom-pdf-2" />
            <div class="dialog-title">Redigera personlig information</div>
          </VCardText>

          <VCol 
            v-if="alert.show" 
            cols="12" 
          >
            <VAlert
              v-if="alert.show"
              :type="alert.type"
            >
              {{ alert.message }}
            </VAlert>
          </VCol>
          <VForm
            ref="refVForm"
            class="card-form"
            @submit.prevent="onSubmit"
          >
            <VCardText class="p-0">
              <VRow>
                <VCol
                  cols="12"
                  sm="12"
                  md="5"
                >
                  <div 
                      class="bg-alert ms-4"
                      :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                      :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                  >
                    <span class="d-block d-md-flex text-center justify-center">
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
                        <VIcon size="32" icon="custom-camera" />
                        <VFileInput                          
                          accept="image/png, image/jpeg, image/bmp"
                          placeholder="Avatar"
                          prepend-icon=""
                          @change="$emit('onImageSelected', $event)"
                          @click:clear="resetAvatar"
                        />
                      </div>
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Tillåtna format JPG, GIF, PNG." />
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
                </VCol>

                <VCol
                  cols="12"
                  sm="12"
                  md="7"
                >
                  <div 
                      class="d-flex flex-wrap me-4 mb-4"
                      :class="windowWidth < 1024 ? 'ms-4 flex-column' : 'flex-row'"
                      :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                  >
                      <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Namn*" />
                        <VTextField
                          v-model="name"
                          :rules="[requiredValidator]"
                        />
                      </div>
                      <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Efternamn*" />
                        <VTextField
                          v-model="last_name"
                          :rules="[requiredValidator]"
                        />
                      </div>
                      <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
                        <VTextField
                          v-model="email"
                          type="email"
                          :rules="[requiredValidator, emailValidator]"
                          disabled
                        />
                      </div>
                      <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon*" />
                        <VTextField
                          v-model="phone"
                          placeholder="+(XX) XXXXXXXXX"
                          :rules="[requiredValidator, phoneValidator]"
                        />
                      </div>
                      <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />
                        <VTextarea
                          v-model="address"
                          rows="3"
                          :rules="[requiredValidator]"
                        />
                      </div>

                      <!-- 👉 Form Actions -->
                      <div 
                        class="d-flex justify-end gap-3 flex-wrap dialog-actions"
                        :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'"
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
                          Spara ändringar
                        </VBtn>
                      </div>
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VForm>
        </VCard>      
      </VDialog> 
    </VRow>
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
    padding-top: 16px;
    padding-right: 24px;
    padding-bottom: 16px;
    padding-left: 24px;
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
