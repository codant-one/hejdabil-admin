<script setup>

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  user: {
    type: Object,
    required: false
  },
  readonly: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'permissions',
  'readonly'
])

const assignedPermissions =  ref([])
const readonly =  ref(true)

watchEffect(() => {
    if (props.isDrawerOpen && props.user) {

        if (!(Object.entries(props.user).length === 0) && props.user.constructor === Object) {

            assignedPermissions.value = props.user.assignedPermissions
        }
    } else{
        assignedPermissions.value = []
    }
    readonly.value = props.readonly
})

const closeModal = function(){
    emit('update:isDrawerOpen', false)
    emit('permissions', assignedPermissions.value)
    emit('readonly')
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
                    Moduler  
                </VCardTitle>
                <VCardText class="py-0">
                    <div class="ml-5">
                        <VLabel style="font-weight: bold;">
                            Kunder
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="view clients"
                                value="view clients"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create clients"
                                value="create clients"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit clients"
                                value="edit clients"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
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
                                v-model="assignedPermissions"
                                label="view billing"
                                value="view billing"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create billing"
                                value="create billing"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit billing"
                                value="edit billing"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="delete billing"
                                value="delete billing"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Signera dokument
                        </VLabel>
                        <div class="demo-space-x ml-5">
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="view signed documents"
                                value="view signed documents"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create signed documents"
                                value="create signed documents"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit signed documents"
                                value="edit signed documents"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="delete signed documents"
                                value="delete signed documents"
                            />
                        </div>
                        <VLabel style="font-weight: bold;">
                            Mina fordon
                        </VLabel>
                        <VCardText class="pt-2 pb-0">
                            <div class="ml-5">
                                <VLabel style="font-weight: bold;">
                                    Lagerfordon
                                </VLabel>
                                <div class="demo-space-x ml-5">
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="view stock"
                                        value="view stock"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="create stock"
                                        value="create stock"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="edit stock"
                                        value="edit stock"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete stock"
                                        value="delete stock"
                                        :readonly="readonly"
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
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete sold"
                                        value="delete sold"
                                        :readonly="readonly"
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
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="create notes"
                                        value="create notes"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="edit notes"
                                        value="edit notes"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-model="assignedPermissions"
                                        label="delete notes"
                                        value="delete notes"
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
                                v-model="assignedPermissions"
                                label="view agreements"
                                value="view agreements"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="create agreements"
                                value="create agreements"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="edit agreements"
                                value="edit agreements"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-model="assignedPermissions"
                                label="delete agreements"
                                value="delete agreements"
                                :readonly="readonly"
                            />
                        </div>
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
