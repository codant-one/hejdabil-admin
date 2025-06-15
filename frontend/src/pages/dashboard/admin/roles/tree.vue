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
                v-if="$can('Skapa ','roles')"
                v-bind="props"
                prepend-icon="tabler-sitemap"
                class="w-100 w-md-auto"
                >
                Karta över tillstånd
            </VBtn>
        </template>

        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeTreeDialog " />

        <!-- Dialog Content -->
        <VCard title=" Karta över tillstånd">
            <VDivider class="mt-4"/>
            <VCardText>
                <ol id="permisos-lista">
                    <li class="n1">
                        <strong>Administratör</strong>&emsp;/&emsp;<code>Tillgång till hela applikationen</code>
                    </li>
                    <li class="n1">
                        <strong>Visa instrumentpanel</strong>&emsp;/&emsp;<code>Gör att du kan visa kontrollpanelen</code>
                    </li>
                    <ul class="tab">
                        <li class="n1">
                            <strong>Profil</strong>
                        </li>
                        <li class="tab n2">
                            <strong>Roller</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till rollmodulen</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Skapa roller</strong>&emsp;/&emsp;<code>Möjliggör skapande av roller</code>
                            </li>
                            <li class="tab n3">
                                <strong>Redigera roller</strong>&emsp;/&emsp;<code>Möjliggör redigering av roller</code>
                            </li>
                            <li class="tab n3">
                                <strong>Ta bort roller</strong>&emsp;/&emsp;<code>Möjliggör borttagning av roller</code>
                            </li>
                        </ul>
                        <li class="tab n2">
                            <strong>Användare</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till användarmodulen</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Skapa användare</strong>&emsp;/&emsp;<code>Möjliggör skapande av användare</code>
                            </li>
                            <li class="tab n3">
                                <strong>Redigera användare</strong>&emsp;/&emsp;<code>Tillåter redigering av användare</code>
                            </li>
                            <li class="tab n3">
                                <strong>Radera användare</strong>&emsp;/&emsp;<code>Tillåter borttagning av användare</code>
                            </li>
                        </ul>
                    </ul>
                    <ul class="tab">
                        <li class="n1">
                            <strong>Moduler</strong>
                        </li>
                        <li class="tab n2">
                            <strong>Leverantörer</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till leverantörsmodulen</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Skapa leverantörer</strong>&emsp;/&emsp;<code>Möjliggör skapande av leverantörer</code>
                            </li>
                            <li class="tab n3">
                                <strong>Redigera leverantörer</strong>&emsp;/&emsp;<code>Tillåter redigering av leverantörer</code>
                            </li>
                            <li class="tab n3">
                                <strong>Radera leverantörer</strong>&emsp;/&emsp;<code>Möjliggör borttagning av leverantörer</code>
                            </li>
                        </ul>
                        <li class="tab n2">
                            <strong>Kunderegister</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till klientmodulen</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Skapa kunder</strong>&emsp;/&emsp;<code>Möjliggör skapande av klienter</code>
                            </li>
                            <li class="tab n3">
                                <strong>Redigera kunder</strong>&emsp;/&emsp;<code>Möjliggör redigering av klienter</code>
                            </li>
                            <li class="tab n3">
                                <strong>Radera kunder</strong>&emsp;/&emsp;<code>Tillåter borttagning av klienter</code>
                            </li>
                        </ul>
                        <li class="tab n2">
                            <strong>Fakturor</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till faktureringsmodulen</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Skapa fakturering</strong>&emsp;/&emsp;<code>Möjliggör skapande av fakturor</code>
                            </li>
                            <li class="tab n3">
                                <strong>Redigera fakturering</strong>&emsp;/&emsp;<code>Möjliggör redigering av fakturor</code>
                            </li>
                            <li class="tab n3">
                                <strong>Radera fakturering</strong>&emsp;/&emsp;<code>Möjliggör borttagning av fakturor</code>
                            </li>
                        </ul>
                        <li class="tab n2">
                            <strong>Mina fordon</strong>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Märke</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till märkemodulen</code>
                            </li>
                            <ul class="tab">
                                <ul>
                                    <li class="tab n3">
                                        <strong>Skapa märke</strong>&emsp;/&emsp;<code>Tillåter skapande av märke</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Redigera märke</strong>&emsp;/&emsp;<code>Tillåter redigering av märke</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Radera märke</strong>&emsp;/&emsp;<code>Tillåter borttagning av märke</code>
                                    </li>
                                </ul>
                            </ul>
                        </ul>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Modell</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till modellmodulen</code>
                            </li>
                            <ul class="tab">
                                <ul>
                                    <li class="tab n3">
                                        <strong>Skapa modell</strong>&emsp;/&emsp;<code>Tillåter skapande av modell</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Redigera modell</strong>&emsp;/&emsp;<code>Tillåter redigering av modell</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Radera modell</strong>&emsp;/&emsp;<code>Tillåter borttagning av modell</code>
                                    </li>
                                </ul>
                            </ul>
                        </ul>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Lagerfordon</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till lagerfordonmodulen</code>
                            </li>
                            <ul class="tab">
                                <ul>
                                    <li class="tab n3">
                                        <strong>Skapa lagerfordon</strong>&emsp;/&emsp;<code>Tillåter skapande av lagerfordon</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Redigera lagerfordon</strong>&emsp;/&emsp;<code>Tillåter redigering av lagerfordon</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Radera lagerfordon</strong>&emsp;/&emsp;<code>Tillåter borttagning av lagerfordon</code>
                                    </li>
                                </ul>
                            </ul>
                        </ul>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Sålda</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till såldamodulen</code>
                            </li>
                            <ul class="tab">
                                <ul>
                                    <li class="tab n3">
                                        <strong>Radera sålda</strong>&emsp;/&emsp;<code>Tillåter borttagning av sålda</code>
                                    </li>
                                </ul>
                            </ul>
                        </ul>
                         <ul class="tab">
                            <li class="tab n3">
                                <strong>Egen värdering</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till lagerfordonmodulen</code>
                            </li>
                            <ul class="tab">
                                <ul>
                                    <li class="tab n3">
                                        <strong>Skapa egen värdering</strong>&emsp;/&emsp;<code>Tillåter skapande av egen värdering</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Redigera egen värdering</strong>&emsp;/&emsp;<code>Tillåter redigering av egen värdering</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Radera egen värdering</strong>&emsp;/&emsp;<code>Tillåter borttagning av egen värdering</code>
                                    </li>
                                </ul>
                            </ul>
                        </ul>
                        <li class="tab n2">
                            <strong>Avtal</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till avtalmodulen</code>
                        </li>
                        <ul class="tab">
                            <li class="tab n3">
                                <strong>Skapa avtal</strong>&emsp;/&emsp;<code>Möjliggör skapande av avtal</code>
                            </li>
                            <li class="tab n3">
                                <strong>Redigera avtal</strong>&emsp;/&emsp;<code>Möjliggör redigering av avtal</code>
                            </li>
                            <li class="tab n3">
                                <strong>Radera avtal</strong>&emsp;/&emsp;<code>Möjliggör borttagning av avtal</code>
                            </li>
                        </ul>
                        <li class="tab n2">
                            <strong>Administration</strong>
                        </li>
                        <ul class="tab">
                            <li class="tab n2">
                                <strong>Attribut</strong>
                            </li>
                            <ul class="tab">
                                <li class="tab n3">
                                    <strong>Fakturor</strong>&emsp;/&emsp;<code>Skrivskyddad åtkomst till modulen för fakturaattribut</code>
                                </li>
                                <ul class="tab">
                                    <li class="tab n3">
                                        <strong>Skapa fakturor</strong>&emsp;/&emsp;<code>Tillåter skapande av fakturaattribut</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Redigera fakturor</strong>&emsp;/&emsp;<code>Tillåter redigering av fakturans attribut</code>
                                    </li>
                                    <li class="tab n3">
                                        <strong>Radera fakturor</strong>&emsp;/&emsp;<code>Tillåter borttagning av fakturaattribut</code>
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
