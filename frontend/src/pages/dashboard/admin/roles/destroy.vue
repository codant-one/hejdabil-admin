<script setup>

import { useRolesStores } from '@/stores/useRoles'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  role: {
    type: Object,
    required: true
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'alert',
  'data'
])

const rolesStores = useRolesStores()

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const closeRoleDeleteDialog = function() {
  emit('update:isDrawerOpen', false)
}

const deleteRole = function() {


  rolesStores.deleteRole(props.role.id)
    .then(response => {
        closeRoleDeleteDialog()

        window.scrollTo(0, 0)
      
        advisor.value.show = true
        advisor.value.type = 'success'
        advisor.value.message = 'Role deleted!'
            
        emit('alert', advisor)
        emit('data')

        setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
            emit('alert', advisor)
        }, 5000)
    }).catch(error => {
        closeRoleDeleteDialog()
    
        if(error.response.status == 403){
        advisor.value.show = true
        advisor.value.type = 'error'
        advisor.value.message = 'You do not have enough permissions'
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
        <DialogCloseBtn @click="closeRoleDeleteDialog" />

        <!-- Dialog Content -->
        <VCard title="Delete role">
          <VDivider class="mt-4"/>
            <VCardText>
              Are you sure you want to delete the role <strong>{{ role.name }}</strong>?
            </VCardText>

            <VCardText class="d-flex justify-end gap-3 flex-wrap">
                <VBtn @click="closeRoleDeleteDialog">
                    Cancel
                </VBtn>
            <VBtn
                color="secondary"
                variant="tonal"
                @click="deleteRole"
            >
                Delete
            </VBtn>
            </VCardText>
        </VCard>
    </VDialog>
</template>
