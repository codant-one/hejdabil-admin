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
                        <VLabel style="font-weight: bold;"
                            v-if="
                                $can('view','clients') ||
                                $can('create','clients') ||
                                $can('edit','clients') ||
                                $can('delete','clients')
                            "
                        >
                            Kunder
                        </VLabel>
                        <div class="demo-space-x ml-5"
                            v-if="
                                $can('view','clients') ||
                                $can('create','clients') ||
                                $can('edit','clients') ||
                                $can('delete','clients')
                            "
                        >
                            <VCheckbox
                                v-if="$can('view','clients')"
                                v-model="assignedPermissions"
                                label="view clients"
                                value="view clients"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('create','clients')"
                                v-model="assignedPermissions"
                                label="create clients"
                                value="create clients"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('edit','clients')"
                                v-model="assignedPermissions"
                                label="edit clients"
                                value="edit clients"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('delete','clients')"
                                v-model="assignedPermissions"
                                label="delete clients"
                                value="delete clients"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;"
                            v-if="
                                $can('view','billings') ||
                                $can('create','billings') ||
                                $can('edit','billings') ||
                                $can('delete','billings')
                            "
                        >
                            Fakturor
                        </VLabel>
                        <div class="demo-space-x ml-5"
                            v-if="
                                $can('view','billings') ||
                                $can('create','billings') ||
                                $can('edit','billings') ||
                                $can('delete','billings')
                            "
                        >
                            <VCheckbox
                                v-if="$can('view','billings')"
                                v-model="assignedPermissions"
                                label="view billings"
                                value="view billings"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('create','billings')"
                                v-model="assignedPermissions"
                                label="create billings"
                                value="create billings"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('edit','billings')"
                                v-model="assignedPermissions"
                                label="edit billings"
                                value="edit billings"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('delete','billings')"
                                v-model="assignedPermissions"
                                label="delete billings"
                                value="delete billings"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;"
                            v-if="
                                $can('view','stock') ||
                                $can('create','stock') ||
                                $can('edit','stock') ||
                                $can('delete','stock') ||
                                $can('view','sold') ||
                                $can('delete','sold')
                            "
                        >
                            Mitt Fordonslager
                        </VLabel>
                        <VCardText class="pt-2 pb-0"
                            v-if="
                                $can('view','stock') ||
                                $can('create','stock') ||
                                $can('edit','stock') ||
                                $can('delete','stock') ||
                                $can('view','sold') ||
                                $can('delete','sold')
                            "
                        >
                            <div class="ml-5">
                                <VLabel style="font-weight: bold;"
                                    v-if="
                                        $can('view','stock') ||
                                        $can('create','stock') ||
                                        $can('edit','stock') ||
                                        $can('delete','stock')
                                    "
                                >
                                    I Lager
                                </VLabel>
                                <div class="demo-space-x ml-5"
                                    v-if="
                                        $can('view','stock') ||
                                        $can('create','stock') ||
                                        $can('edit','stock') ||
                                        $can('delete','stock')
                                    "
                                >
                                    <VCheckbox
                                        v-if="$can('view','stock')"
                                        v-model="assignedPermissions"
                                        label="view stock"
                                        value="view stock"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-if="$can('create','stock')"
                                        v-model="assignedPermissions"
                                        label="create stock"
                                        value="create stock"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-if="$can('edit','stock')"
                                        v-model="assignedPermissions"
                                        label="edit stock"
                                        value="edit stock"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-if="$can('delete','stock')"
                                        v-model="assignedPermissions"
                                        label="delete stock"
                                        value="delete stock"
                                        :readonly="readonly"
                                    />
                                </div>
                                <VLabel style="font-weight: bold;"
                                    v-if="
                                        $can('view','sold') ||
                                        $can('delete','sold')
                                    "
                                >
                                    Sålda Fordon
                                </VLabel>
                                <div class="demo-space-x ml-5"
                                    v-if="
                                        $can('view','sold') ||
                                        $can('delete','sold')
                                    "
                                >
                                    <VCheckbox
                                        v-if="$can('view','sold')"
                                        v-model="assignedPermissions"
                                        label="view sold"
                                        value="view sold"
                                        :readonly="readonly"
                                    />
                                    <VCheckbox
                                        v-if="$can('delete','sold')"
                                        v-model="assignedPermissions"
                                        label="delete sold"
                                        value="delete sold"
                                        :readonly="readonly"
                                    />
                                </div>                                
                            </div>
                        </VCardText>
                        <VLabel style="font-weight: bold;"
                            v-if="
                                $can('view','agreements') ||
                                $can('create','agreements') ||
                                $can('edit','agreements') ||
                                $can('delete','agreements')
                            "
                        >
                            Avtal
                        </VLabel>
                        <div class="demo-space-x ml-5"
                            v-if="
                                $can('view','agreements') ||
                                $can('create','agreements') ||
                                $can('edit','agreements') ||
                                $can('delete','agreements')
                            "
                        >
                            <VCheckbox
                                v-if="$can('view','agreements')"
                                v-model="assignedPermissions"
                                label="view agreements"
                                value="view agreements"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('create','agreements')"
                                v-model="assignedPermissions"
                                label="create agreements"
                                value="create agreements"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('edit','agreements')"
                                v-model="assignedPermissions"
                                label="edit agreements"
                                value="edit agreements"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('delete','agreements')"
                                v-model="assignedPermissions"
                                label="delete agreements"
                                value="delete agreements"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;" 
                            v-if="
                                $can('view','signed-documents') ||
                                $can('create','signed-documents') ||
                                $can('edit','signed-documents') ||
                                $can('delete','signed-documents')
                            "
                        >
                            Signera dokument
                        </VLabel>
                        <div class="demo-space-x ml-5"
                            v-if="
                                $can('view','signed-documents') ||
                                $can('create','signed-documents') ||
                                $can('edit','signed-documents') ||
                                $can('delete','signed-documents')
                            "
                        >
                            <VCheckbox
                                v-if="$can('view','signed-documents')"
                                v-model="assignedPermissions"
                                label="view signed-documents"
                                value="view signed-documents"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('create','signed-documents')"
                                v-model="assignedPermissions"
                                label="create signed-documents"
                                value="create signed-documents"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('edit','signed-documents')"
                                v-model="assignedPermissions"
                                label="edit signed-documents"
                                value="edit signed-documents"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('delete','signed-documents')"
                                v-model="assignedPermissions"
                                label="delete signed-documents"
                                value="delete signed-documents"
                                :readonly="readonly"
                            />
                        </div>
                        <VLabel style="font-weight: bold;"
                            v-if="
                                $can('view','payouts') ||
                                $can('create','payouts') ||
                                $can('edit','payouts') ||
                                $can('delete','payouts')
                            "
                        >
                            Swish
                        </VLabel>
                        <div class="demo-space-x ml-5"
                            v-if="
                                $can('view','payouts') ||
                                $can('create','payouts') ||
                                $can('edit','payouts') ||
                                $can('delete','payouts')
                            "
                        >
                            <VCheckbox
                                v-if="$can('view','payouts')"
                                v-model="assignedPermissions"
                                label="view payouts"
                                value="view payouts"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('create','payouts')"
                                v-model="assignedPermissions"
                                label="create payouts"
                                value="create payouts"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('edit','payouts')"
                                v-model="assignedPermissions"
                                label="edit payouts"
                                value="edit payouts"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('delete','payouts')"
                                v-model="assignedPermissions"
                                label="delete payouts"
                                value="delete payouts"
                                :readonly="readonly"
                            />
                        </div>                        
                        <VLabel style="font-weight: bold;"
                            v-if="
                                $can('view','notes') ||
                                $can('create','notes') ||
                                $can('edit','notes') ||
                                $can('delete','notes')
                            "
                        >
                            Mina Värderingar
                        </VLabel>
                        <div class="demo-space-x ml-5"
                            v-if="
                                $can('view','notes') ||
                                $can('create','notes') ||
                                $can('edit','notes') ||
                                $can('delete','notes')
                            "
                        >
                            <VCheckbox
                                v-if="$can('view','notes')"
                                v-model="assignedPermissions"
                                label="view notes"
                                value="view notes"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('create','notes')"
                                v-model="assignedPermissions"
                                label="create notes"
                                value="create notes"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('edit','notes')"
                                v-model="assignedPermissions"
                                label="edit notes"
                                value="edit notes"
                                :readonly="readonly"
                            />
                            <VCheckbox
                                v-if="$can('delete','notes')"
                                v-model="assignedPermissions"
                                label="delete notes"
                                value="delete notes"
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
