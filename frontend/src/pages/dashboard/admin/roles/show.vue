<script setup>

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  role: {
    type: Object,
    required: true
  },
  readonly: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'close',
  'readonly'
])

const isSelectRolesDialog = ref(false)
const id =  ref([])
const name =  ref([])
const readonly =  ref([])
const permissions =  ref([])

watchEffect(() => {
    if (props.isDrawerOpen) {

        if (!(Object.entries(props.role).length === 0) && props.role.constructor === Object) {
            permissions.value = props.role.assignedPermissions
            id.value = props.role.id
            name.value = props.role.name
            readonly.value = props.readonly
        }
    }
})

const closeRoleDetailDialog = function(){
    emit('update:isDrawerOpen', false)
    emit('close')
    emit('readonly')
}

</script>

<template>
    <section>
    <!-- DIALOG-->
    <VDialog
        :model-value="props.isDrawerOpen"
        max-width="600"
        persistent
        >
        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeRoleDetailDialog" />

        <!-- Dialog Content -->
        <VCard title="Detail role">
            <VDivider class="mt-4"/>
            <VCardText>
                <VRow>
                    <VCol cols="12" >
                        <VTextField
                            v-model="id"
                            label="ID"
                            readonly
                        />
                    </VCol>
                    <VCol cols="12">
                        <VTextField
                            v-model="name"
                            label="Name"
                            readonly
                        />
                    </VCol>
                    <VCol
                        cols="12"
                        class="text-center"
                    >
                        <VBtn
                            @click="isSelectRolesDialog = true"
                        >
                            View role permissions
                        </VBtn>
                    </VCol>
                </VRow>
            </VCardText>
        </VCard>
    </VDialog>

     <!-- DIALOGO DE ROLES -->
     <VDialog
        v-model="isSelectRolesDialog"
        persistent
        max-width="1100"
        >
        <DialogCloseBtn @click="isSelectRolesDialog = !isSelectRolesDialog" />

        <VCard title="Permissions">
            <VDivider class="mt-4"/>
            <VCardText>
                <VCardTitle>
                    General manager
                </VCardTitle>
                <VCardText>
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Administrator
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="administrator"
                                value="administrator"
                                :readonly="readonly"
                            />
                        </div>
                    </div>
                </VCardText>
                <VCardTitle>
                    General  
                </VCardTitle>
                <VCardText>
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Control panel
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view dashboard"
                                value="view dashboard"
                                :readonly="readonly"
                            />
                        </div>
                    </div>
                </VCardText>
                <VCardTitle>
                    Profile  
                </VCardTitle>
                <VCardText>
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Roles
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view roles"
                                value="view roles"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create roles"
                                value="create roles"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit roles"
                                value="edit roles"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete roles"
                                value="delete roles"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Users
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view users"
                                value="view users"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create users"
                                value="create users"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit users"
                                value="edit users"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete users"
                                value="delete users"
                                :readonly="readonly"
                            />
                        </div>
                    </div>
                </VCardText>
                <VCardTitle>
                    Modules  
                </VCardTitle>
                <VCardText>
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Suppliers
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view suppliers"
                                value="view suppliers"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create suppliers"
                                value="create suppliers"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit suppliers"
                                value="edit suppliers"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete suppliers"
                                value="delete suppliers"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Clients
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view clients"
                                value="view clients"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create clients"
                                value="create clients"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit clients"
                                value="edit clients"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete clients"
                                value="delete clients"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Billing
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view billing"
                                value="view billing"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create billing"
                                value="create billing"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit billing"
                                value="edit billing"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete billing"
                                value="delete billing"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Administration
                        </VLabel>
                        <VCardText class="pt-2 pb-0">
                            <div class="ml-5">
                                <VLabel style="font-weight: bold;">
                                    Attributes
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="permissions"
                                        label="view invoices"
                                        value="view invoices"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="create invoices"
                                        value="create invoices"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="edit invoices"
                                        value="edit invoices"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="delete invoices"
                                        value="delete invoices"
                                        :readonly="readonly"
                                    />
                                </div>
                            </div>
                        </VCardText>
                    </div>
                </VCardText>
            </VCardText>
            <VCardText class="d-flex flex-wrap gap-3">
                <VSpacer />
                <VBtn @click="isSelectRolesDialog = false">
                    Accept
                </VBtn>
            </VCardText>
        </VCard>
    </VDialog>
    </section>
</template>
