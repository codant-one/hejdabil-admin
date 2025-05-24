<script setup>

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  role: {
    type: Object,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'permissions'
])

const assignedPermissions =  ref([])

watchEffect(() => {
    if (props.isDrawerOpen && props.role) {

        if (!(Object.entries(props.role).length === 0) && props.role.constructor === Object) {
            assignedPermissions.value = props.role.assignedPermissions
        }
    }
})

const closeModal = function(){
    emit('update:isDrawerOpen', false)
    emit('permissions', assignedPermissions.value)
}

</script>

<template>
    <!-- DIALOG -->
    <VDialog
        :model-value="props.isDrawerOpen"
        persistent
        max-width="1100"
        >
        <DialogCloseBtn @click="closeModal" />

        <VCard title="Behörigheter">
            <VDivider class="mt-4"/>
            <VCardText class="py-0">
                <VCardTitle>
                    Verkställande direktör  
                </VCardTitle>
                <VCardText class="py-0">
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Administratör
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="administrator"
                                value="administrator"
                            />
                        </div>
                    </div>
                </VCardText>
                <VCardTitle>
                    Allmänt  
                </VCardTitle>
                <VCardText class="py-0">
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Kontrollpanelen
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="view dashboard"
                                value="view dashboard"
                            />
                        </div>
                    </div>
                </VCardText>
                <VCardTitle>
                    Profil  
                </VCardTitle>
                <VCardText class="py-0">
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Roller
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="view roles"
                                value="view roles"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create roles"
                                value="create roles"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit roles"
                                value="edit roles"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="delete roles"
                                value="delete roles"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Användare
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="view users"
                                value="view users"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create users"
                                value="create users"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit users"
                                value="edit users"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="delete users"
                                value="delete users"
                            />
                        </div>
                    </div>
                </VCardText>
                <VCardTitle>
                    Moduler  
                </VCardTitle>
                <VCardText class="py-0">
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Leverantörer
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="view suppliers"
                                value="view suppliers"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create suppliers"
                                value="create suppliers"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit suppliers"
                                value="edit suppliers"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="delete suppliers"
                                value="delete suppliers"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Kunder
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="view clients"
                                value="view clients"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create clients"
                                value="create clients"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit clients"
                                value="edit clients"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="delete clients"
                                value="delete clients"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Fakturering
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="view billing"
                                value="view billing"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create billing"
                                value="create billing"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit billing"
                                value="edit billing"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="delete billing"
                                value="delete billing"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Förvaltning
                        </VLabel>
                        <VCardText class="pt-2 pb-0">
                            <div class="ml-5">
                                <VLabel style="font-weight: bold;">
                                    Attribut
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="view invoices"
                                        value="view invoices"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="create invoices"
                                        value="create invoices"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="edit invoices"
                                        value="edit invoices"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete invoices"
                                        value="delete invoices"
                                    />
                                </div>
                            </div>
                        </VCardText>
                    </div>
                </VCardText>
            </VCardText>
            <VCardText class="d-flex flex-wrap gap-2">
                <VSpacer />
                <VBtn class="w-100 w-md-auto" @click="closeModal">
                    Acceptera
                </VBtn>
            </VCardText>
        </VCard>
    </VDialog>
</template>
