<script setup>

import { useSuppliersStores } from '@/stores/useSuppliers'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  user: {
    type: Object,
    required: true
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'alert',
  'data'
])

const usersStores = useSuppliersStores()

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const closeUserDeleteDialog = function() {
  emit('update:isDrawerOpen', false)
}

const deleteUser = function() {
  usersStores.deleteUser(props.user.id)
    .then(response => {
      closeUserDeleteDialog()

      window.scrollTo(0, 0)
      
      advisor.value.show = true
      advisor.value.type = 'success'
      advisor.value.message = 'Användare raderad!'
          
      emit('alert', advisor)
      emit('data')

      setTimeout(() => {
        advisor.value.show = false
        advisor.value.type = ''
        advisor.value.message = ''
        emit('alert', advisor)
      }, 5000)
  }).catch(error => {

    closeUserDeleteDialog()
    
    if(error.response.status == 403){
      advisor.value.show = true
      advisor.value.type = 'error'
      advisor.value.message = 'Du har inte tillräckliga behörigheter'
    }

    setTimeout(() => {
      advisor.value.show = false
      advisor.value.type = ''
      advisor.value.message = ''
      emit('alert', advisor)
    }, 5000)
  })  
}
</script>

<template>
  <!-- DIALOG -->
  <VDialog
    :model-value="props.isDrawerOpen"
    persistent
    class="action-dialog"
    >
    <!-- Dialog close btn -->

    <VBtn
      icon
      class="btn-white close-btn"
      @click="closeUserDeleteDialog"
    >
      <VIcon size="16" icon="custom-close" />
    </VBtn>

    <!-- Dialog Content -->
    <VCard>
      <VCardText class="dialog-title-box">
        <VIcon size="32" icon="custom-filled-waste" class="action-icon" />
        <div class="dialog-title">
          Ta bort användare
        </div>
      </VCardText>
      <VCardText class="dialog-text">
        Är du säker att du vill ta bort användaren <strong>{{ user.email }}</strong>?
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
        <VBtn class="btn-light" @click="closeUserDeleteDialog">
          Avbryt
        </VBtn>
        <VBtn class="btn-gradient" @click="deleteUser" >
          Radera
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
