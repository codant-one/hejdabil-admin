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
                        <li class="n2">
                            <strong>Modules</strong>
                        </li>
                        <li class="tab n3">
                            <strong>View Roles</strong>&emsp;/&emsp;<code>Read-only access to the roles module</code>
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
                        <li class="tab n3">
                            <strong>View users</strong>&emsp;/&emsp;<code>Read-only access to the users module</code>
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
                </ol>
            </VCardText>
        </VCard>
    </VDialog>
</template>
