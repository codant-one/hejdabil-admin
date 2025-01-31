<script setup>
// import { VDataTable } from 'vuetify/labs/VDataTable'
import { confirmedValidator, passwordValidator, requiredValidator } from '@/@core/utils/validators'
import { useUsersStores } from '@/stores/useUsers'

const props = defineProps({
  user_id: {
    type: Number,
    required: true
  }
})
const usersStores = useUsersStores()

const refForm  = ref()
const password = ref()
const passwordConfirmation = ref()
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const smsVerificationNumber = ref('+1(968) 819-2547')
const isTwoFactorDialogOpen = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const emit = defineEmits(['alert'])

const recentDeviceHeader = [
  {
    title: 'BROWSER',
    key: 'browser',
  },
  {
    title: 'DEVICE',
    key: 'device',
  },
  {
    title: 'LOCATION',
    key: 'location',
  },
  {
    title: 'RECENT ACTIVITY',
    key: 'activity',
  },
]

const recentDevices = [
  {
    browser: 'Chrome on Windows',
    logo: 'tabler-brand-windows',
    color: 'info',
    device: 'HP Specter 360',
    location: 'Switzerland',
    activity: '10, July 2021 20:07',
  },
  {
    browser: 'Chrome on iPhone',
    logo: 'tabler-device-mobile',
    color: 'error',
    device: 'iPhone 12x',
    location: 'Australia',
    activity: '13, July 2021 10:10',
  },
  {
    browser: 'Chrome on Android',
    logo: 'tabler-brand-android',
    color: 'success',
    device: 'OnePlus 9 Pro',
    location: 'Dubai',
    activity: '4, July 2021 15:15',
  },
  {
    browser: 'Chrome on macOS',
    logo: 'tabler-brand-apple',
    color: 'secondary',
    device: 'Apple iMac',
    location: 'India',
    activity: '20, July 2021 21:01',
  },
  {
    browser: 'Chrome on Windows',
    logo: 'tabler-brand-windows',
    color: 'info',
    device: 'HP Specter 360',
    location: 'Switzerland',
    activity: '10, July 2021 20:07',
  },
  {
    browser: 'Chrome on Android',
    logo: 'tabler-brand-android',
    color: 'success',
    device: 'OnePlus 9 Pro',
    location: 'Dubai',
    activity: '4, July 2021 15:15',
  },
]

const onSubmit = () => {
  refForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      let data = {
        password: password.value
      }

      usersStores.updatePasswordUser(data, props.user_id)
        .then(response => {
          window.scrollTo(0, 0)

          advisor.value.show = true
          advisor.value.type = 'success'
          advisor.value.message = 'Password changed'
                    
          emit('alert', advisor)

          nextTick(() => {
            refForm.value?.reset()
            refForm.value?.resetValidation()
            password.value = null
            passwordConfirmation.value = null
          })

          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
            emit('alert', advisor)
          }, 5000)

      })
    }
  })
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 Change password -->
      <VCard title="Change password">
        <VCardText>
          <VAlert
            variant="tonal"
            color="warning"
            class="mb-4 px-4 py-3"
          >
            <VAlertTitle class="mb-3">
              Make sure these requirements are met
            </VAlertTitle>
            <span>Minimum 8 characters, uppercase, lowercase and numbers</span>
          </VAlert>

          <VForm
            ref="refForm"
            @submit.prevent="onSubmit">
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="password"
                   label="New password"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  :rules="[requiredValidator, passwordValidator]"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                  />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="passwordConfirmation"
                  label="Confirm password"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  :rules="[requiredValidator, confirmedValidator(passwordConfirmation, password)]"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                  />
              </VCol>

              <VCol cols="12">
                <VBtn type="submit">
                  Change password
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" v-if="false">
      <!-- 👉 Two step verification -->
      <VCard>
        <VCardItem>
          <VCardTitle class="mb-2">
            Two-steps verification
          </VCardTitle>
          <VCardSubtitle>
            <span class="text-base text-medium-emphasis">Keep your account secure with authentication step.</span>
          </VCardSubtitle>
        </VCardItem>

        <VCardText>
          <div>
            <span class="text-base text-high-emphasis font-weight-medium mb-1">
              SMS
            </span>
            <VTextField
              variant="underlined"
              :model-value="smsVerificationNumber"
            >
              <template #append-inner>
                <VBtn
                  icon
                  variant="text"
                  color="default">
                  <VIcon
                    icon="tabler-edit"
                    @click="isTwoFactorDialogOpen = true"
                  />
                </VBtn>
                <VBtn
                  icon
                  variant="text"
                  color="default">
                  <VIcon icon="tabler-trash" />
                </VBtn>
              </template>
            </VTextField>
          </div>

          <p class="mb-0 mt-4">
            Two-factor authentication adds an additional layer of security to your account by requiring more than just a password to log in. <a
              href="javascript:void(0)"
              class="text-decoration-none"
            >Learn more</a>.
          </p>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" v-if="false">
      <!-- 👉 Recent devices -->
      <VCard title="Recent devices">
        <VDivider />
        <!-- <VDataTable
          :items="recentDevices"
          :headers="recentDeviceHeader"
          hide-default-footer
          class="text-no-wrap"
        >
          <template #item.browser="{ item }">
            <div class="d-flex align-center">
              <VIcon
                :icon="item.logo"
                :color="item.color"
                size="18"
                class="me-2"
              />
              {{ item.browser }}
            </div>
          </template>
          <template #bottom />
        </VDataTable> -->
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Enable One Time Password Dialog -->
  <!-- <TwoFactorAuthDialog
    v-model:isDialogVisible="isTwoFactorDialogOpen"
    :sms-code="smsVerificationNumber"
  /> -->
</template>
