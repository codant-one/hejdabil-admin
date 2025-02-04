<script setup>

import TabSecurity from '@/views/dashboard/profile/TabSecurity.vue'
import TabDealer from '@/views/dashboard/profile/TabDealer.vue'
import UserProfile from '@/views/dashboard/profile/UserProfile.vue'

const avatar = ref('')
const avatarOld = ref('')
const userData = ref(null)
const role = ref(null)
const userTab = ref(null)
const isRequestOngoing = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const tabs = [
  {
    icon: 'tabler-lock',
    title: 'Security',
  },
  {
    icon: 'tabler-lock',
    title: 'Company',
  },
]

watchEffect(fetchData)

async function fetchData() { 

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')

  avatarOld.value = userData.value.avatar
  avatar.value = userData.value.avatar
  role.value = userData.value.roles[0].name
}

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
}

const resizeImage = function(file, maxWidth, maxHeight, quality) {
  return new Promise((resolve, reject) => {
    const img = new Image()

    img.src = URL.createObjectURL(file)

    img.onload = () => {
      const canvas = document.createElement('canvas')
      const ctx = canvas.getContext('2d')

      let width = img.width
      let height = img.height

      if (maxWidth && width > maxWidth) {
        height *= maxWidth / width
        width = maxWidth
      }

      if (maxHeight && height > maxHeight) {
        width *= maxHeight / height
        height = maxHeight
      }

      canvas.width = width
      canvas.height = height

      ctx.drawImage(img, 0, 0, width, height)

      canvas.toBlob(blob => {
        resolve(blob)
      }, file.type, quality)
    }
    img.onerror = error => {
      reject(error)
    }
  })
}

const blobToBase64 = blob => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()

    reader.readAsDataURL(blob)
    reader.onload = () => {
      resolve(reader.result.split(',')[1])
    }
    reader.onerror = error => {
      reject(error)
    }
  })
}

const onImageSelected = event => {
  const file = event.target.files[0]

  if (!file) return
    avatarOld.value = file

  URL.createObjectURL(file)

  resizeImage(file, 400, 400, 0.9)
    .then(async blob => {
      avatarOld.value = blob
      let r = await blobToBase64(blob)
      avatar.value = 'data:image/jpeg;base64,' + r
    })
}
</script>

<template>
  <section>
    <VDialog
      v-model="isRequestOngoing"
      width="300"
      persistent>
          
      <VCard
        color="primary"
        width="300">
            
        <VCardText class="pt-3">
          Loading
          <VProgressLinear
            indeterminate
            color="white"
            class="mb-0"/>
        </VCardText>
      </VCard>
    </VDialog>
    <VAlert
      v-if="advisor.show"
      :type="advisor.type"
      class="mb-6">
        {{ advisor.message }}
    </VAlert>


    <VRow>
      <VCol
        cols="12"
        md="5"
        lg="4"
      >
        <UserProfile
          :user="userData"
          :avatarOld="avatarOld"
          :avatar="avatar"
          @onImageSelected="onImageSelected" />
      </VCol>
      <VCol
        cols="12"
        md="7"
        lg="8"
      >
        <VTabs
          v-model="userTab"
          class="v-tabs-pill"
          v-if="role === 'Supplier'"
        >
          <VTab
            v-for="tab in tabs"
            :key="tab.icon"
          >
            <VIcon
              :size="18"
              :icon="tab.icon"
              class="me-1"
            />
            <span>{{ tab.title }}</span>
          </VTab>
        </VTabs>

        <VWindow
          v-model="userTab"
          class="mt-3 disable-tab-transition"
          :touch="false"
        >
          <VWindowItem>
            <TabSecurity 
            @alert="showAlert"/>
          </VWindowItem>
          <VWindowItem v-if="role === 'Supplier'">
            <TabDealer />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>
  </section>
</template>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
