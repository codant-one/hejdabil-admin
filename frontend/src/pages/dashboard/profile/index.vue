<script setup>

import { useDisplay } from "vuetify";
import TabSecurity from '@/views/dashboard/profile/TabSecurity.vue'
import TabDealer from '@/views/dashboard/profile/TabDealer.vue'
import UserProfile from '@/views/dashboard/profile/UserProfile.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();

const avatar = ref('')
const avatarOld = ref('')
const userData = ref(null)
const role = ref(null)
const userTab = ref(null)
const isRequestOngoing = ref(false)
const isFormEdited = ref(false);
const dialog = ref(false);
let nextRoute = null;

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const tabs = [
  {
    // icon: 'tabler-lock',
    icon: 'custom-agreement',
    title: 'Säkerhet',
  },
  {
    // icon: 'tabler-building-store',
    icon: 'custom-clients',
    title: 'Företag',
  },
]

onBeforeRouteLeave((to, from, next) => {
  if (isFormEdited.value) {
    dialog.value = true;
    nextRoute = next;
  } else {
    next();
  }
});

watchEffect(fetchData)

async function fetchData() { 

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')

  avatarOld.value = userData.value.avatar
  avatar.value = userData.value.avatar
  role.value = userData.value.roles[0].name
}

const showWindow = function(data) {
  isFormEdited.value = data
}

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
}

const confirmLeave = () => {
  dialog.value = false;
  nextRoute();
};

const cancelLeave = () => {
  dialog.value = false;
  nextRoute(false);
};

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
    <LoadingOverlay :is-loading="isRequestOngoing" />
    
    <VAlert
      v-if="advisor.show"
      :type="advisor.type"
      class="mb-6">
        {{ advisor.message }}
    </VAlert>

    <VCard
      flat 
      class="card-fill"
      :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
      ]"
    >
      <VCardText class="p-0">
        <VRow>
          <VCol
            cols="12"
            md="12"
            lg="12"
          >
            <UserProfile
              :user="userData"
              :avatarOld="avatarOld"
              :avatar="avatar"
              @onImageSelected="onImageSelected" />
          </VCol>

          <VCol
            cols="12"
            md="12"
            lg="12"
          >
            <div v-if="role !== 'SuperAdmin' && role !== 'Administrator'">
              <VTabs 
                    v-model="userTab" 
                    :grow="windowWidth < 1024 ? true : false"             
                    :show-arrows="false"
                    class="profile-tabs" 
              >
                <VTab v-for="tab in tabs" >
                    <VIcon size="18" :icon="'' + tab.icon" />
                    {{ tab.title }}
                </VTab>
              </VTabs>

              <VWindow
                v-model="userTab"
                :touch="false"
              >
                <VWindowItem>
                  <TabSecurity @alert="showAlert"/>
                </VWindowItem>
                <VWindowItem>
                  <TabDealer 
                    @alert="showAlert"
                    @window="showWindow"/>
                </VWindowItem>
              </VWindow>
            </div>
            <TabSecurity @alert="showAlert" v-else/>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>


     <!-- 👉 Confirm Delete -->
     <VDialog
      v-model="dialog"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="cancelLeave" />

      <!-- Dialog Content -->
      <VCard title="Avsluta utan att spara">
        <VDivider class="mt-4"/>
        <VCardText>
          <strong>Du har osparade ändringar.</strong> Är du säker på att du vill lämna sidan?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="cancelLeave">
              Avbryt
          </VBtn>
          <VBtn @click="confirmLeave">
              Ja, fortsätt
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss">
  .v-tabs.profile-tabs {
    .v-btn {
      min-width: 50px !important;
      .v-btn__content {
        font-size: 14px !important;
        color: #454545;
      }
    }
  }

  @media (max-width: 776px) {
    .v-tabs.profile-tabs {
      .v-icon {
        display: none !important;
      }
      .v-btn {
        .v-btn__content {
            white-space: break-spaces;
        }
      }
    }
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
