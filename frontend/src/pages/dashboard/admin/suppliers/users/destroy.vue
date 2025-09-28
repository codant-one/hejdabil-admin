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
    class="v-dialog-sm"
    >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closeUserDeleteDialog" />

    <!-- Dialog Content -->
    <VCard title="Delete user">
      <VDivider class="mt-4"/>
      <VCardText>
        Är du säker att du vill ta bort användaren <strong>{{ user.email }}</strong>?
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <VBtn 
          color="secondary"
          variant="tonal"
          @click="closeUserDeleteDialog"
          >
          Avbryt
        </VBtn>
        <VBtn @click="deleteUser" >
          Radera
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
