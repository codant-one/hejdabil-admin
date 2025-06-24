<script setup>

import { toRaw } from 'vue'
import { useNotesStores } from '@/stores/useNotes'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { avatarText, formatNumber } from '@/@core/utils/formatters'
import AddNewNoteDrawer from './AddNewNoteDrawer.vue' 
import router from '@/router'

const notesStores = useNotesStores()
const emitter = inject("emitter")

const notes = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalNotes = ref(0)
const isRequestOngoing = ref(true)
const isAddNewNoteDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const selectedNote = ref({})

const userData = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = notes.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = notes.value.length + (currentPage.value - 1) * rowPerPage.value

  return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalNotes.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewNoteDrawerVisible.value)
        selectedNote.value = {}
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  await notesStores.fetchNotes(data)

  notes.value = notesStores.getNotes
  totalPages.value = notesStores.last_page
  totalNotes.value = notesStores.notesTotalCount

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')

  isRequestOngoing.value = false
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const editNote = noteData => {
    isAddNewNoteDrawerVisible.value = true
    selectedNote.value = { ...noteData }
}

const showDeleteDialog = noteData => {
  isConfirmDeleteDialogVisible.value = true
  selectedNote.value = { ...noteData }
}

const removeNote = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await notesStores.deleteNote(selectedNote.value.id)
  selectedNote.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Värdering raderad!' : res.data.message,
    show: true
  }

  await fetchData()

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const submitForm = async (note, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    note.data.append('_method', 'PUT')
    submitUpdate(note)
    return
  }

  submitCreate(note.data)
}


const submitCreate = noteData => {

    notesStores.addNote(noteData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Kund skapad! ',
                    show: true
                }
                fetchData()
            }
            isRequestOngoing.value = false
        })
        .catch((err) => {
            advisor.value = {
                type: 'error',
                message: err.message,
                show: true
            }
            isRequestOngoing.value = false
        })

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)
}

const submitUpdate = noteData => {

    notesStores.updateNote(noteData)
        .then((res) => {
            if (res.data.success) {
                    advisor.value = {
                    type: 'success',
                    message: 'Värdering uppdaterad!',
                    show: true
                }
                fetchData()
            }
            isRequestOngoing.value = false
        })
        .catch((err) => {
            advisor.value = {
                type: 'error',
                message: err.message,
                show: true
            }
            isRequestOngoing.value = false
        })

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)
}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await notesStores.fetchNotes(data)

  let dataArray = [];
      
  notesStores.getNotes.forEach(element => {

    let data = {
      REGNR: element.reg_num,
      EGEN_VÄRDERING: element.note ?? '',
      KUNDNAMN: element.name ?? '',
      TEL_NR: element.phone ?? '',
      E_POST: element.email ?? '',
      KOMMENTAR: element.comment ?? ''
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "notes", "csv");

  isRequestOngoing.value = false

}
</script>

<template>
  <section>
    <VRow>
      <VDialog
        v-model="isRequestOngoing"
        width="auto"
        persistent>
        <VProgressCircular
          indeterminate
          color="primary"
          class="mb-0"/>
      </VDialog>

      <VCol cols="12">
        <VAlert
          v-if="advisor.show"
          :type="advisor.type"
          class="mb-6">
            
          {{ advisor.message }}
        </VAlert>

        <VCard title="">
          <VCardText class="d-flex align-center flex-wrap gap-4">
            <div class="d-flex align-center w-100 w-md-auto">
              <span class="text-no-wrap me-3">Visa:</span>
              <VSelect
                v-model="rowPerPage"
                density="compact"
                variant="outlined"
                class="w-100"
                :items="[10, 20, 30, 50]"/>
            </div>

            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-file-export"
              class="w-100 w-md-auto"
              @click="downloadCSV">
              Exportera
            </VBtn>

            <VSpacer class="d-none d-md-block"/>

            <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto">

              <!-- 👉 Search  -->
              <div class="search">
                <VTextField
                  v-model="searchQuery"
                  placeholder="Sök"
                  density="compact"
                  clearable
                />
              </div>

              <!-- 👉 Add user button -->
              <VBtn
                v-if="$can('create','notes')"
                prepend-icon="tabler-plus"
                class="w-100 w-md-auto"
                @click="isAddNewNoteDrawerVisible = true">
                  Ny värdering
              </VBtn>
            </div>
          </VCardText>

          <v-divider />

          <v-table class="text-no-wrap">
            <!-- 👉 table head -->
            <thead>
              <tr>
                <th scope="col"> Skapad av </th>
                <th scope="col"> Reg nr </th>
                <th scope="col"> Egen värdering  </th>
                <th scope="col"> Info </th>
                <th scope="col"> E-post</th>
                <th scope="col"> Kommentar </th>
                <th scope="col"> Datum </th>
                <th scope="col" v-if="$can('edit', 'notes') || $can('delete', 'notes')"></th>
              </tr>
            </thead>
            <!-- 👉 table body -->
            <tbody>
              <tr 
                v-for="note in notes"
                :key="note.id"
                style="height: 3rem;">

                <td class="text-wrap">
                  <div class="d-flex align-center gap-x-3">
                    <VAvatar
                      :variant="note.user.avatar ? 'outlined' : 'tonal'"
                      size="38"
                      >
                      <VImg
                        v-if="note.user.avatar"
                        style="border-radius: 50%;"
                        :src="themeConfig.settings.urlStorage + note.user.avatar"
                      />
                        <span v-else>{{ avatarText(note.user.name) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ note.user.name }} {{ note.user.last_name ?? '' }} 
                      </span>
                      <span class="text-sm text-disabled">{{ note.user.email }}</span>
                    </div>
                  </div>
                </td>
                <td> {{ note.reg_num }} </td>
                <td> {{ note.note }} </td>
                <td> 
                  <div class="d-flex align-center gap-x-3">
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">
                        {{ note.name }} 
                      </span>
                      <span class="text-sm text-disabled" v-if="note.phone">Tel nr: {{ note.phone }}</span>
                    </div>
                  </div>
                </td>
                <td> {{ note.email }} </td>
                <td class="text-wrap">
                    {{ note.comment }}
                </td>  
                <td class="text-wrap">
                   {{ new Date(note.created_at).toLocaleString('sv-SE', { 
                        year: 'numeric', 
                        month: '2-digit', 
                        day: '2-digit', 
                        hour: '2-digit', 
                        minute: '2-digit',
                        hour12: false
                    }) }} 
                </td>               
                
                <!-- 👉 Acciones -->
                <td 
                  class="text-center" 
                  style="width: 3rem;" 
                  v-if="($can('edit', 'notes') || $can('delete', 'notes')) && userData.id === note.user_id">      
                  <VMenu>
                    <template #activator="{ props }">
                      <VBtn v-bind="props" icon variant="text" color="default" size="x-small">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                          <path d="M12.52 20.924c-.87 .262 -1.93 -.152 -2.195 -1.241a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.088 .264 1.502 1.323 1.242 2.192"></path>
                          <path d="M19 16v6"></path>
                          <path d="M22 19l-3 3l-3 -3"></path>
                          <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                        </svg>
                      </VBtn>
                    </template>
                    <VList>
                      <VListItem
                         v-if="$can('edit', 'notes')"
                         @click="editNote(note)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Redigera</VListItemTitle>
                      </VListItem>
                      <VListItem 
                        v-if="$can('delete','notes')"
                        @click="showDeleteDialog(note)">
                        <template #prepend>
                          <VIcon icon="tabler-trash" />
                        </template>
                        <VListItemTitle>Radera</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </td>
                <td v-else></td>
              </tr>
            </tbody>
            <!-- 👉 table footer  -->
            <tfoot v-show="!notes.length">
              <tr>
                <td
                  colspan="8"
                  class="text-center">
                  Uppgifter ej tillgängliga
                </td>
              </tr>
            </tfoot>
          </v-table>
        
          <v-divider />

          <VCardText class="d-block d-md-flex text-center align-center flex-wrap gap-4 py-3">
            <span class="text-sm text-disabled">
              {{ paginationData }}
            </span>

            <VSpacer class="d-none d-md-block"/>

            <VPagination
              v-model="currentPage"
              size="small"
              :total-visible="5"
              :length="totalPages"/>
          
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    <!-- 👉 Add New Note -->
    <AddNewNoteDrawer
      v-model:isDrawerOpen="isAddNewNoteDrawerVisible"
      :note="selectedNote"
      @note-data="submitForm"/>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Ta bort värdering">
        <VDivider class="mt-4"/>
        <VCardText>
          Är du säker att du vill ta bort värdering  <strong>{{ selectedNote.reg_num }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn @click="removeNote">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style scope>
    .search {
        width: 100% !important;
    }

    @media(min-width: 991px){
        .search {
            width: 20rem !important;
        }
    }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: notes
</route>