<script setup>


const isTreeDialog = ref(false)

const closeTreeDialog  = function(){
    isTreeDialog.value = false
}

</script>

<template>
    <!-- 👉 arbol de permisos rol -->
    <VDialog
        v-model="isTreeDialog"
        max-width="1200"
        >
        <!-- Dialog Activator -->
        <template #activator="{ props }">
            <VBtn
                v-if="$can('create','roles')"
                v-bind="props"
                prepend-icon="tabler-sitemap"
                >
                Map of permits
            </VBtn>
        </template>

        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeTreeDialog " />

        <!-- Dialog Content -->
        <VCard title="Map of permits">
            <VDivider class="mt-4"/>
            <VCardText>
                <ol id="permisos-lista">
                    <li class="n1">
                        <strong>Administrator</strong>&emsp;/&emsp;<code>Access to the entire application</code>
                    </li>
                    <li class="n1">
                        <strong>View Dashboard</strong>&emsp;/&emsp;<code>Allows you to view the control panel</code>
                    </li>
                    <ul class="tab">
                        <li class="n1">
                            <strong>Proflle</strong>
                        </li>
                        <li class="tab n2">
                            <strong>Roles</strong>&emsp;/&emsp;<code>Read-only access to the roles module</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Create roles</strong>&emsp;/&emsp;<code>Allows the creation of roles</code>
                            </li>
                            <li class="tab n3">
                                <strong>Edit roles</strong>&emsp;/&emsp;<code>Allows editing of roles</code>
                            </li>
                            <li class="tab n3">
                                <strong>Delete roles</strong>&emsp;/&emsp;<code>Allows the removal of roles</code>
                            </li>
                        </ul>
                        <li class="tab n2">
                            <strong>Users</strong>&emsp;/&emsp;<code>Read-only access to the users module</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Create users</strong>&emsp;/&emsp;<code>Allows the creation of users</code>
                            </li>
                            <li class="tab n3">
                                <strong>Edit users</strong>&emsp;/&emsp;<code>Allows editing of users</code>
                            </li>
                            <li class="tab n3">
                                <strong>Delete users</strong>&emsp;/&emsp;<code>Allows deletion of users</code>
                            </li>
                        </ul>
                    </ul>
                    <ul class="tab">
                        <li class="n1">
                            <strong>Modules</strong>
                        </li>
                        <li class="tab n2">
                            <strong>Suppliers</strong>&emsp;/&emsp;<code>Read-only access to the suppliers module</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Create suppliers</strong>&emsp;/&emsp;<code>Allows the creation of suppliers</code>
                            </li>
                            <li class="tab n3">
                                <strong>Edit suppliers</strong>&emsp;/&emsp;<code>Allows editing of suppliers</code>
                            </li>
                            <li class="tab n3">
                                <strong>Delete suppliers</strong>&emsp;/&emsp;<code>Allows the removal of suppliers</code>
                            </li>
                        </ul>
                        <li class="tab n2">
                            <strong>Clients</strong>&emsp;/&emsp;<code>Read-only access to the clients module</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Create clients</strong>&emsp;/&emsp;<code>Allows the creation of clients</code>
                            </li>
                            <li class="tab n3">
                                <strong>Edit clients</strong>&emsp;/&emsp;<code>Allows editing of clients</code>
                            </li>
                            <li class="tab n3">
                                <strong>Delete clients</strong>&emsp;/&emsp;<code>Allows deletion of clients</code>
                            </li>
                        </ul>
                        <li class="tab n2">
                            <strong>Billing</strong>&emsp;/&emsp;<code>Read-only access to the billing module</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Create billing</strong>&emsp;/&emsp;<code>Allows the creation of invoices</code>
                            </li>
                            <li class="tab n3">
                                <strong>Edit billing</strong>&emsp;/&emsp;<code>Allows editing of invoices</code>
                            </li>
                            <li class="tab n3">
                                <strong>Delete billing</strong>&emsp;/&emsp;<code>Allows deletion of invoices</code>
                            </li>
                        </ul>
                        <li class="tab n2">
                            <strong>Administration</strong>
                        </li>
                        <ul class="tab">
                            <li class="tab n2">
                                <strong>Attributes</strong>
                            </li>
                            <ul class="tab">
                                <li class="tab n3">
                                    <strong>Invoices</strong>&emsp;/&emsp;<code>Read-only access to the invoice attributes module</code>
                                </li>
                                <ul class="tab">
                                    <li class="tab n3">
                                        <strong>Create invoices</strong>&emsp;/&emsp;<code>Allows the creation of invoice attributes</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Edit invoices</strong>&emsp;/&emsp;<code>Allows editing of invoice attributes</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Delete invoices</strong>&emsp;/&emsp;<code>Allows deletion of invoice attributes</code>
                                    </li>
                                </ul>
                            </ul>
                        </ul>
                    </ul>
                </ol>
            </VCardText>
        </VCard>
    </VDialog>
</template>
