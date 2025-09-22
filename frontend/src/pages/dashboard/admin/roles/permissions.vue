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
                            Fakturor
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
                            Mina fordon
                        </VLabel>
                        <VCardText class="pt-2 pb-0">
                            <div class="ml-5">
                                <VLabel style="font-weight: bold;">
                                    Märke
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="view brands"
                                        value="view brands"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="create brands"
                                        value="create brands"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="edit brands"
                                        value="edit brands"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete brands"
                                        value="delete brands"
                                    />
                                </div>
                                <VLabel style="font-weight: bold;">
                                    Modell
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="view models"
                                        value="view models"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="create models"
                                        value="create models"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="edit models"
                                        value="edit models"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete models"
                                        value="delete models"
                                    />
                                </div>
                                <VLabel style="font-weight: bold;">
                                    Lagerfordon
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="view stock"
                                        value="view stock"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="create stock"
                                        value="create stock"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="edit stock"
                                        value="edit stock"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete stock"
                                        value="delete stock"
                                    />
                                </div>
                                <VLabel style="font-weight: bold;">
                                    Sålda
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="view sold"
                                        value="view sold"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete sold"
                                        value="delete sold"
                                    />
                                </div>
                                <VLabel style="font-weight: bold;">
                                    Egen värdering
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="view notes"
                                        value="view notes"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="create notes"
                                        value="create notes"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="edit notes"
                                        value="edit notes"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete notes"
                                        value="delete notes"
                                    />
                                </div>
                            </div>
                        </VCardText>
                        <VLabel style="font-weight: bold;">
                            Avtal
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="view agreements"
                                value="view agreements"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create agreements"
                                value="create agreements"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit agreements"
                                value="edit agreements"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="delete agreements"
                                value="delete agreements"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Administration
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
                                <VLabel style="font-weight: bold;">
                                    Valuta
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="view currencies"
                                        value="view currencies"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="create currencies"
                                        value="create currencies"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="edit currencies"
                                        value="edit currencies"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete currencies"
                                        value="delete currencies"
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
