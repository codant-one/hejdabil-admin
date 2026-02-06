<script setup>

import { initialAbility } from '@/plugins/casl/ability'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useAuthStores } from '@/stores/useAuth'

import logo from "@images/logos/billogg-logo.svg";

const router = useRouter()
const ability = useAppAbility()
const authStores = useAuthStores()

const { width: windowWidth } = useWindowSize()

const back = function(){
  const abilities = localStorage.getItem('userAbilities')
  const permissions = Array.from(JSON.parse(abilities))
  const band = ref(false)

  permissions.forEach(function(abili) {
    if(abili.subject === 'dashboard'){
        band.value = true
    }
  })

  if(band.value) {
    router.replace('/info')
  } else {

    authStores.logout()
      .then(response => {
        // Remove "user_data" from localStorage
        localStorage.removeItem('user_data')

        // Remove "accessToken" from localStorage
        localStorage.removeItem('accessToken')
        
        // Remove "userAbilities" from localStorage
        localStorage.removeItem('userAbilities')

        // Reset ability to initial ability
        ability.update(initialAbility)
        router.push('/login')

      })
  }
}
</script>

<template>
  <VCard class="bg-gradient pa-4 d-flex flex-column" style="min-height: 100vh;">
    <div class="d-flex align-center flex-0" :class="windowWidth < 1024 ? 'justify-center' : ''">
      <img :src="logo" width="121" height="40" alt="Billogg" />
    </div>

    <div 
      class="empty-state my-auto"
      :class="$vuetify.display.smAndDown ? 'px-6 py-0' : 'pa-4'">
      <VIcon
        :size="$vuetify.display.smAndDown ? 80 : 120"
        icon="custom-f-cancel"
      />
      <div class="empty-state-content">
        <div class="empty-state-title">Du saknar behörighet</div>
        <div class="empty-state-text">
          Du har inte rättigheter att se den här sidan. 
          Kontakta din administratör om du behöver tillgång till denna funktion.
        </div>
      </div>
      <VBtn
        class="btn-ghost"
        @click="back"
      >
        Gå tillbaka till översikten
        <VIcon icon="custom-arrow-right" size="24" />
      </VBtn>
    </div>
  </VCard>
</template>

<route lang="yaml">
meta:
  layout: blank
  action: view
  subject: Auth
</route>
