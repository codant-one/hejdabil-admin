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
const userData = ref(null)
const role_ = ref(null)

watchEffect(() => {
    if (props.isDrawerOpen) {

        if (!(Object.entries(props.role).length === 0) && props.role.constructor === Object) {
            userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
            role_.value = userData.value.roles[0].name
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
        <VCard title="Detalj roll">
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
                            label="Namn"
                            readonly
                        />
                    </VCol>
                    <VCol
                        cols="12"
                        class="text-center"
                    >
                        <VBtn
                            class="w-100 w-md-auto"
                            @click="isSelectRolesDialog = true"
                        >
                            Visa rollbehörigheter
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

        <VCard title="Behörigheter">
            <VDivider class="mt-4"/>
            <VCardText class="py-0">
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
                                :disabled="role_ !== 'SuperAdmin'"
                                v-model="permissions"
                                label="delete roles"
                                value="delete roles"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Användare
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
                    Moduler  
                </VCardTitle>
                <VCardText class="py-0">
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Kunder
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
                            Fakturor
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view billings"
                                value="view billings"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create billings"
                                value="create billings"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit billings"
                                value="edit billings"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete billings"
                                value="delete billings"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Leverantörer
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
                            Mitt Fordonslager
                        </VLabel>
                        <VCardText class="pt-2 pb-0">
                            <div class="ml-5">
                                <VLabel style="font-weight: bold;">
                                    Märke
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="permissions"
                                        label="view brands"
                                        value="view brands"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="create brands"
                                        value="create brands"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="edit brands"
                                        value="edit brands"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="delete brands"
                                        value="delete brands"
                                        :readonly="readonly"
                                    />
                                </div>
                                <VLabel style="font-weight: bold;">
                                    Modell
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="permissions"
                                        label="view models"
                                        value="view models"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="create models"
                                        value="create models"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="edit models"
                                        value="edit models"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="delete models"
                                        value="delete models"
                                        :readonly="readonly"
                                    />
                                </div>
                                <VLabel style="font-weight: bold;">
                                    I Lager
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="permissions"
                                        label="view stock"
                                        value="view stock"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="create stock"
                                        value="create stock"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="edit stock"
                                        value="edit stock"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="delete stock"
                                        value="delete stock"
                                        :readonly="readonly"
                                    />
                                </div>
                                <VLabel style="font-weight: bold;">
                                    Sålda Fordon
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="permissions"
                                        label="view sold"
                                        value="view sold"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="delete sold"
                                        value="delete sold"
                                        :readonly="readonly"
                                    />
                                </div>
                            </div>
                        </VCardText>
                        <VLabel style="font-weight: bold;">
                            Avtal
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view agreements"
                                value="view agreements"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create agreements"
                                value="create agreements"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit agreements"
                                value="edit agreements"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete agreements"
                                value="delete agreements"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Signera dokument
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view signed-documents"
                                value="view signed-documents"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create signed-documents"
                                value="create signed-documents"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit signed-documents"
                                value="edit signed-documents"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete signed-documents"
                                value="delete signed-documents"     
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Swish
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view payouts"
                                value="view payouts"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create payouts"
                                value="create payouts"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit payouts"
                                value="edit payouts"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete payouts"
                                value="delete payouts"     
                                :readonly="readonly"
                            />
                        </div>                        
                        <VLabel style="font-weight: bold;">
                            Mina Värderingar
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="permissions"
                                label="view notes"
                                value="view notes"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="create notes"
                                value="create notes"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="edit notes"
                                value="edit notes"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="permissions"
                                label="delete notes"
                                value="delete notes"
                                :readonly="readonly"
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
                                <VLabel style="font-weight: bold;">
                                    Valuta
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="permissions"
                                        label="view currencies"
                                        value="view currencies"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="create currencies"
                                        value="create currencies"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="edit currencies"
                                        value="edit currencies"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="delete currencies"
                                        value="delete currencies"
                                        :readonly="readonly"
                                    />
                                </div>
                                <VLabel style="font-weight: bold;">
                                    Land
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="permissions"
                                        label="view countries"
                                        value="view countries"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="create countries"
                                        value="create countries"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="edit countries"
                                        value="edit countries"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="permissions"
                                        label="delete countries"
                                        value="delete countries"
                                        :readonly="readonly"
                                    />
                                </div>
                            </div>
                        </VCardText>
                    </div>
                </VCardText>
            </VCardText>
            <VCardText class="d-flex flex-wrap gap-2">
                <VSpacer />
                <VBtn class="w-100 w-md-auto" @click="isSelectRolesDialog = false">
                    Acceptera
                </VBtn>
            </VCardText>
        </VCard>
    </VDialog>
    </section>
</template>
