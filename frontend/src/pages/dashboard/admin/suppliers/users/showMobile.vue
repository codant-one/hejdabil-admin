<script setup>

const { width: windowWidth } = useWindowSize();

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  user: {
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

const isUserPermissionsDialog = ref(false)

const email = ref('')
const name = ref('')
const password = ref('')
const last_name = ref('')
const phone = ref('')
const isPhone = ref(false)
const address = ref('')
const isAddress = ref(false)
const readonly =  ref(true)
const assignedPermissions =  ref([])

watchEffect(() => {
    if (props.isDrawerOpen) {

        if (!(Object.entries(props.user).length === 0) && props.user.constructor === Object) {
            email.value = props.user.email
            password.value = props.user.password
            name.value = props.user.name
            last_name.value = props.user.last_name
            phone.value = props.user.user_detail?.personal_phone ?? '----'
            isPhone.value = (props.user.user_detail?.personal_phone === null) ? true : false
            address.value = props.user.user_detail?.personal_address ?? '----'
            isAddress.value = (props.user.user_detail?.address === null) ? true : false
            assignedPermissions.value = props.user.assignedPermissions
        }

        readonly.value = props.readonly
    }
})

const closeUserDetailDialog = function() {
    emit('update:isDrawerOpen', false)
    emit('close')
    emit('readonly')
}

const hasAssignedPermission = permission => {
    return assignedPermissions.value?.includes(permission)
}

</script>

<template>
    <!-- DIALOG-->
    <VDialog
        id="show-dialog"
        :model-value="props.isDrawerOpen"
        fullscreen
        persistent
        :scrim="false"
        transition="dialog-bottom-transition"
        class="action-dialog dialog-fullscreen"
        content-class="clients-pending-mobile-fullscreen">
        <!-- Dialog close btn -->
         <VBtn
            icon
            class="btn-white close-btn"
            @click="closeUserDetailDialog"
            >
            <VIcon size="16" icon="custom-close" />
        </VBtn>

        <!-- Dialog Content -->
        <VCard class="detail-user h-100">
            <VCardText class="dialog-title-box mb-6 pb-0 flex-0">
                <div class="w-100 d-flex flex-column align-start gap-2">
                    <div class="dialog-title text-aqua">
                        {{ name }} {{ last_name }}
                    </div>
                    <div class="dialog-text">
                        {{ email }}
                    </div>
                </div>
                
            </VCardText>

            <VCardText class="dialog-text" style="overflow-y: auto; overflow-x: hidden;">
                <div 
                    class="d-flex flex-wrap card-form flex-row gap-3"
                >
                    <div :style="windowWidth < 1024 ? 'width: calc(50% - 12px);' : 'width: calc(25% - 12px);'">
                        <VLabel class="mb-1 detail-item-label" text="Efternamn" />
                        <div class="detail-item-value"> {{ name }} {{ last_name }}</div>
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: calc(50% - 12px);' : 'width: calc(25% - 12px);'">
                        <VLabel class="mb-1 detail-item-label" text="Adress" />
                        <div class="detail-item-value"> {{ address }}</div>
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: calc(50% - 12px);' : 'width: calc(25% - 12px);'">
                        <VLabel class="mb-1 detail-item-label" text="Telefon" />
                        <div class="detail-item-value"> {{ phone }}</div>
                    </div>
                    <div :style="windowWidth < 1024 ? 'width: calc(50% - 12px);' : 'width: calc(25% - 12px);'">
                        <VLabel class="mb-1 detail-item-label" text="Lösenord" />
                        <div class="detail-item-value"> ******** </div>
                    </div>
                </div>

                <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'my-2'" />

                <div 
                    class="d-flex flex-wrap card-form flex-row gap-3 mb-6"
                >
                    <div class="dialog-text d-flex gap-2 align-center w-100 my-4">
                        <VIcon size="24" icon="custom-settings-light" class="text-aqua" />
                        <span class="title-modules">Behörigheter</span>
                    </div>
                    <div class="text-modules w-100 mb-2">
                        Modules
                    </div>
                    <div 
                        class="d-flex flex-wrap flex-row"
                        :class="windowWidth < 1024 ? 'gap-6' : 'gap-4'"
                    >
                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','clients') ||
                                $can('create','clients') ||
                                $can('edit','clients') ||
                                $can('delete','clients')
                            "
                        >
                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Kunder" />
                            <div class="permissions-grid">
                                <div
                                    v-if="$can('view','clients') && hasAssignedPermission('view clients')"
                                    class="permission-label"
                                >
                                    view clients
                                </div>
                                <div
                                    v-if="$can('create','clients') && hasAssignedPermission('create clients')"
                                    class="permission-label"
                                >
                                    create clients
                                </div>
                                <div
                                    v-if="$can('edit','clients') && hasAssignedPermission('edit clients')"
                                    class="permission-label"
                                >
                                    edit clients
                                </div>
                                <div
                                    v-if="$can('delete','clients') && hasAssignedPermission('delete clients')"
                                    class="permission-label"
                                >
                                    delete clients
                                </div>
                            </div>
                        </div>

                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','billings') ||
                                $can('create','billings') ||
                                $can('edit','billings') ||
                                $can('delete','billings')
                            "
                        >
                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Fakturor" />
                            <div class="permissions-grid">
                                <div
                                    v-if="$can('view','billings') && hasAssignedPermission('view billings')"
                                    class="permission-label"
                                >
                                    view billings
                                </div>
                                <div
                                    v-if="$can('create','billings') && hasAssignedPermission('create billings')"
                                    class="permission-label"
                                >
                                    create billings
                                </div>
                                <div
                                    v-if="$can('edit','billings') && hasAssignedPermission('edit billings')"
                                    class="permission-label"
                                >
                                    edit billings
                                </div>
                                <div
                                    v-if="$can('delete','billings') && hasAssignedPermission('delete billings')"
                                    class="permission-label"
                                >
                                    delete billings
                                </div>
                            </div>
                        </div>

                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','stock') ||
                                $can('create','stock') ||
                                $can('edit','stock') ||
                                $can('delete','stock') ||
                                $can('view','sold') ||
                                $can('delete','sold')
                            "
                        >
                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Mitt Fordonslager" />
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'">
                                <div class="ml-5 w-100">
                                    <VLabel class="mb-4 text-body-3 text-high-emphasis" text="I Lager" 
                                        v-if="
                                            $can('view','stock') ||
                                            $can('create','stock') ||
                                            $can('edit','stock') ||
                                            $can('delete','stock')
                                        "
                                    />
                                    <div class="mb-4 permissions-grid"
                                        v-if="
                                            $can('view','stock') ||
                                            $can('create','stock') ||
                                            $can('edit','stock') ||
                                            $can('delete','stock')
                                        "
                                    >
                                        <div
                                            v-if="$can('view','stock') && hasAssignedPermission('view stock')"
                                            class="permission-label"
                                        >
                                            view stock
                                        </div>
                                        <div
                                            v-if="$can('create','stock') && hasAssignedPermission('create stock')"
                                            class="permission-label"
                                        >
                                            create stock
                                        </div>
                                        <div
                                            v-if="$can('edit','stock') && hasAssignedPermission('edit stock')"
                                            class="permission-label"
                                        >
                                            edit stock
                                        </div>
                                        <div
                                            v-if="$can('delete','stock') && hasAssignedPermission('delete stock')"
                                            class="permission-label"
                                        >
                                            delete stock
                                        </div>
                                    </div>
                                    <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Sålda Fordon" 
                                        v-if="
                                            $can('view','sold') ||
                                            $can('delete','sold')
                                        "
                                    />
                                    <div class="permissions-grid"
                                        v-if="
                                            $can('view','sold') ||
                                            $can('delete','sold')
                                        "
                                    >
                                        <div
                                            v-if="$can('view','sold') && hasAssignedPermission('view sold')"
                                            class="permission-label"
                                        >
                                            view sold
                                        </div>
                                        <div
                                            v-if="$can('delete','sold') && hasAssignedPermission('delete sold')"
                                            class="permission-label"
                                        >
                                            delete sold
                                        </div>
                                    </div>                                
                                </div>
                            </div>
                        </div>


                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','agreements') ||
                                $can('create','agreements') ||
                                $can('edit','agreements') ||
                                $can('delete','agreements')
                            "
                        >
                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Avtal" />
                            <div class="permissions-grid">
                                <div
                                    v-if="$can('view','agreements') && hasAssignedPermission('view agreements')"
                                    class="permission-label"
                                >
                                    view agreements
                                </div>
                                <div
                                    v-if="$can('create','agreements') && hasAssignedPermission('create agreements')"
                                    class="permission-label"
                                >
                                    create agreements
                                </div>
                                <div
                                    v-if="$can('edit','agreements') && hasAssignedPermission('edit agreements')"
                                    class="permission-label"
                                >
                                    edit agreements
                                </div>
                                <div
                                    v-if="$can('delete','agreements') && hasAssignedPermission('delete agreements')"
                                    class="permission-label"
                                >
                                    delete agreements
                                </div>
                            </div>
                        </div>
                        
                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','signed-documents') ||
                                $can('create','signed-documents') ||
                                $can('edit','signed-documents') ||
                                $can('delete','signed-documents')
                            "
                        >
                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Signera dokument" />
                            <div class="ml-1"
                                :class="windowWidth < 1024 ? 'd-flex flex-column align-start' : 'permissions-grid'"
                            >
                                <div
                                    v-if="$can('view','signed-documents') && hasAssignedPermission('view signed-documents')"
                                    class="permission-label"
                                >
                                    view signed-documents
                                </div>
                                <div
                                    v-if="$can('create','signed-documents') && hasAssignedPermission('create signed-documents')"
                                    class="permission-label"
                                >
                                    create signed-documents
                                </div>
                                <div
                                    v-if="$can('edit','signed-documents') && hasAssignedPermission('edit signed-documents')"
                                    class="permission-label"
                                >
                                    edit signed-documents
                                </div>
                                <div
                                    v-if="$can('delete','signed-documents') && hasAssignedPermission('delete signed-documents')"
                                    class="permission-label"
                                >
                                    delete signed-documents
                                </div>
                            </div>
                        </div>

                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','payouts') ||
                                $can('create','payouts') ||
                                $can('edit','payouts') ||
                                $can('delete','payouts')
                            "
                        >
                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Swish" />
                            <div class="permissions-grid">
                                <div
                                    v-if="$can('view','payouts') && hasAssignedPermission('view payouts')"
                                    class="permission-label"
                                >
                                    view payouts
                                </div>
                                <div
                                    v-if="$can('create','payouts') && hasAssignedPermission('create payouts')"
                                    class="permission-label"
                                >
                                    create payouts
                                </div>
                                <div
                                    v-if="$can('edit','payouts') && hasAssignedPermission('edit payouts')"
                                    class="permission-label"
                                >
                                    edit payouts
                                </div>
                                <div
                                    v-if="$can('delete','payouts') && hasAssignedPermission('delete payouts')"
                                    class="permission-label"
                                >
                                    delete payouts
                                </div>
                            </div> 
                        </div>

                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','notes') ||
                                $can('create','notes') ||
                                $can('edit','notes') ||
                                $can('delete','notes')
                            "
                        >
                            <VLabel class="mb-4 text-body-3 text-high-emphasis" text="Mina Värderingar" />
                            <div class="permissions-grid">
                                <div
                                    v-if="$can('view','notes') && hasAssignedPermission('view notes')"
                                    class="permission-label"
                                >
                                    view notes
                                </div>
                                <div
                                    v-if="$can('create','notes') && hasAssignedPermission('create notes')"
                                    class="permission-label"
                                >
                                    create notes
                                </div>
                                <div
                                    v-if="$can('edit','notes') && hasAssignedPermission('edit notes')"
                                    class="permission-label"
                                >
                                    edit notes
                                </div>
                                <div
                                    v-if="$can('delete','notes') && hasAssignedPermission('delete notes')"
                                    class="permission-label"
                                >
                                    delete notes
                                </div>
                            </div>
                        </div>
                    </div>            
                </div>
            </VCardText>
        </VCard>
    </VDialog>
</template>

<style lang="scss">

    .detail-item-label {
        font-weight: 400;
        font-size: 12px;
        line-height: 100%;
        color: #878787 !important;
    }

    .detail-item-value {
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 12px;
        line-height: 100%;
        color: #5D5D5D;
    }

    .detail-user .dialog-title {
        font-weight: 700 !important;
        font-size: 24px !important;
        line-height: 100% !important;
        letter-spacing: 0 !important;
        color: #008C91 !important;
    }

    .title-modules {
        font-weight: 500;
        font-size: 14px;
        line-height: 16px;
        letter-spacing: 0;
        color: #008C91;
    }

    .text-modules {
        font-weight: 600;
        font-size: 16px;
        line-height: 100%;
        letter-spacing: 0;
        color: #878787;
    }

    .text-body-3 {
        font-weight: 700;
        font-size: 16px;
        line-height: 100%;
        letter-spacing: 0;
        color: #454545;
        padding-bottom: 2px;
    }

    .dialog-text {
        font-weight: 400;
        font-size: 16px;
        line-height: 100%;
        letter-spacing: 0;
        color: #5d5d5d;
    }

    #show-dialog .v-overlay__content {
        width: 1000px !important;
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Crea 2 columnas de igual tamaño */
        gap: 16px; /* Espacio entre elementos */
    }

    .permissions-card {
        border: 1px solid #E7E7E7;
        border-radius: 16px !important;
        padding: 16px;
    }

    .permission-label {
        margin-bottom: 8px;
        font-weight: 400;
        font-size: 14px;
        line-height: 24px;
        letter-spacing: 0;
        color: #454545;
    }
</style>