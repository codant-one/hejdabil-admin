<script setup>

import { initialAbility } from '@/plugins/casl/ability'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import { useAuthStores } from '@/stores/useAuth'

import page401 from '@images/pages/401.png'
import miscMaskDark from '@images/pages/misc-mask-dark.png'
import miscMaskLight from '@images/pages/misc-mask-light.png'

const router = useRouter()
const ability = useAppAbility()
const authThemeMask = useGenerateImageVariant(miscMaskLight, miscMaskDark)
const authStores = useAuthStores()

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
  <div class="misc-wrapper">
    <div class="misc-center-content text-center mb-12">
      <!-- 👉 Title and subtitle -->
      <h4 class="text-h4 font-weight-medium mb-3">
        Du är inte behörig! 🔐
      </h4>
      <p>Du har inte behörighet att komma åt den här sidan!</p>
      <VBtn @click="back">
        Tillbaka till början
      </VBtn>
    </div>

    <!-- 👉 Image -->
    <div class="misc-avatar w-100 text-center">
      <VImg
        :src="page401"
        alt="Coming Soon"
        :max-width="170"
        class="mx-auto"
      />
    </div>

    <VImg
      :src="authThemeMask"
      class="misc-footer-img d-none d-md-block"
    />
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/misc.scss";
</style>

<route lang="yaml">
meta:
  layout: blank
  action: view
  subject: Auth
</route>
