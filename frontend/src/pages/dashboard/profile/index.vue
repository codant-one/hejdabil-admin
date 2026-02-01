<script setup>

import { useDisplay } from "vuetify";
import TabSecurity from '@/views/dashboard/profile/TabSecurity.vue'
import TabDealer from '@/views/dashboard/profile/TabDealer.vue'
import UserProfile from '@/views/dashboard/profile/UserProfile.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";

const sectionEl = ref(null);
const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

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
    icon: 'custom-agreement',
    title: 'Säkerhet',
  },
  {
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

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});
</script>

<template>
    <section class="page-section agreements-page" ref="sectionEl">
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

    <VCard
      flat 
      class="card-fill pa-6"
      :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row'
      ]"
    >

      <UserProfile
        :user="userData"
        :avatarOld="avatarOld"
        :avatar="avatar"
        @onImageSelected="onImageSelected" 
      />
  
      <div v-if="role !== 'SuperAdmin' && role !== 'Administrator'">
        <VTabs 
          v-model="userTab" 
          grow            
          :show-arrows="false"
          class="profile-tabs mt-4" 
        >
          <VTab v-for="tab in tabs" :key="tab.title">
              <VIcon size="24" :icon="'' + tab.icon" />
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
          
    </VCard>

    <VDialog
      v-model="dialog"
      persistent 
      class="action-dialog">
      <!-- Dialog close btn -->
        
      <VBtn
        icon
        class="btn-white close-btn"
        @click="cancelLeave"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
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
            @click="cancelLeave">
              Avbryt
          </VBtn>
          <VBtn 
            class="btn-gradient"
            @click="confirmLeave">
              Ja, fortsätt
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
