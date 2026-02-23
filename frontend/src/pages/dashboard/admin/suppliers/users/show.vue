<script setup>
import { useDisplay } from 'vuetify'
import permissions from './permissions.vue'
import { themeConfig } from '@themeConfig'

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();

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

</script>

<template>
    <!-- DIALOG-->
    <VDialog
        id="show-dialog"
        :model-value="props.isDrawerOpen"
        class="action-dialog"
        persistent
        >
        <!-- Dialog close btn -->
         <VBtn
            icon
            class="btn-white close-btn"
            @click="closeUserDetailDialog"
            >
            <VIcon size="16" icon="custom-close" />
        </VBtn>

        <!-- Dialog Content -->
        <VCard >
            <VCardText class="dialog-title-box">
                <div class="w-100 d-flex flex-column align-start gap-2">
                    <div class="dialog-title text-aqua">
                        {{ name }} {{ last_name }}
                    </div>
                    <div class="dialog-text">
                        {{ email }}
                    </div>
                </div>
                
            </VCardText>

            <VCardText>
                <div 
                    class="d-flex flex-wrap card-form"
                    :class="windowWidth < 1024 ? 'flex-row' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 12px;' : 'gap: 12px;'"
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
                    class="d-flex flex-wrap card-form"
                    :class="windowWidth < 1024 ? 'flex-row' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 12px;' : 'gap: 12px;'"
                >
                    <div class="dialog-text w-100 my-4">
                        <VIcon size="24" icon="custom-settings-light" />
                        Behörigheter
                    </div>
                    <div class="dialog-text w-100 mb-2">
                        Modules
                    </div>
                    <div 
                        class="d-flex flex-wrap"
                        :class="windowWidth < 1024 ? 'flex-row' : 'flex-row'"
                        :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                    >
                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','clients') ||
                                $can('create','clients') ||
                                $can('edit','clients') ||
                                $can('delete','clients')
                            "
                        >
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kunder" />
                            <div class="demo-space-x ml-5 permissions-grid">
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
                        </div>

                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','billings') ||
                                $can('create','billings') ||
                                $can('edit','billings') ||
                                $can('delete','billings')
                            "
                        >
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Fakturor" />
                            <div class="demo-space-x ml-5 permissions-grid">
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
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mitt Fordonslager" />
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: 100%;'">
                                <div class="ml-5 w-100">
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="I Lager" 
                                        v-if="
                                            $can('view','stock') ||
                                            $can('create','stock') ||
                                            $can('edit','stock') ||
                                            $can('delete','stock')
                                        "
                                    />
                                    <div class="demo-space-x mb-4 ml-5 permissions-grid"
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
                                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Sålda Fordon" 
                                        v-if="
                                            $can('view','sold') ||
                                            $can('delete','sold')
                                        "
                                    />
                                    <div class="demo-space-x ml-5 permissions-grid"
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
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Avtal" />
                            <div class="demo-space-x ml-5 permissions-grid">
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
                        </div>
                        
                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','signed-documents') ||
                                $can('create','signed-documents') ||
                                $can('edit','signed-documents') ||
                                $can('delete','signed-documents')
                            "
                        >
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Signera dokument" />
                            <div class="demo-space-x ml-5"
                                :class="windowWidth < 1024 ? 'd-flex flex-column align-start' : 'permissions-grid'"
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
                        </div>

                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','payouts') ||
                                $can('create','payouts') ||
                                $can('edit','payouts') ||
                                $can('delete','payouts')
                            "
                        >
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Swish" />
                            <div class="demo-space-x ml-5 permissions-grid">
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
                        </div>

                        <div class="permissions-card p-2" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'"
                            v-if="
                                $can('view','notes') ||
                                $can('create','notes') ||
                                $can('edit','notes') ||
                                $can('delete','notes')
                            "
                        >
                            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Mina Värderingar" />
                            <div class="demo-space-x ml-5 permissions-grid">
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
                    </div>            
                </div>
            </VCardText>
        </VCard>
    </VDialog>

    <permissions
      v-model:isDrawerOpen="isUserPermissionsDialog"
      :user="user"
      :readonly="readonly"
      @permissions="null"
      @readonly="readonly = true"/>
</template>

<style lang="scss">

    .detail-item-label {
        margin-bottom: 8px;
        font-weight: 400;
        font-size: 12px;
        line-height: 16px;
        color: #878787;
    }

    .detail-item-value {
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 12px;
        line-height: 16px;
        color: #5D5D5D;
    }

    .dialog-title {
        font-weight: 600;
        font-size: 24px;
        line-height: 100%;
        letter-spacing: 0;
        color: #5d5d5d;
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
</style>