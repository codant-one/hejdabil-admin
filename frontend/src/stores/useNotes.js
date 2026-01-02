import { defineStore } from 'pinia'
import Notes from '@/api/notes'

export const useNotesStores = defineStore('notes', {
    state: () => ({
        notes: {},
        suppliers: {},
        loading: false,
        last_page: 1,
        notesTotalCount: 6
    }),
    getters:{
        getNotes(){
            return this.notes
        },
        getSuppliers(){
            return this.suppliers
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },      
        fetchNotes(params) {
            this.setLoading(true)
            
            return Notes.get(params)
                .then((response) => {
                    this.notes = response.data.data.notes.data
                    this.suppliers = response.data.data.suppliers
                    this.last_page = response.data.data.notes.last_page
                    this.notesTotalCount = response.data.data.notesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addNote(data) {
            this.setLoading(true)

            return Notes.create(data)
                .then((response) => {
                    this.notes.push(response.data.data.note)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showNote(id) {
            this.setLoading(true)

            return Notes.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.note)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateNote(data) {
            this.setLoading(true)
            
            return Notes.update(data)
                .then((response) => {
                    let pos = this.notes.findIndex((item) => item.id === response.data.data.note.id)
                    this.notes[pos] = response.data.data.note
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteNote(id) {
            this.setLoading(true)

            return Notes.delete(id)
                .then((response) => {
                    let index = this.notes.findIndex((item) => item.id === id)
                    this.notes.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        sendComment(data) {
            this.setLoading(true)

            return Notes.comment(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateComment(data) {
            this.setLoading(true)

            return Notes.updateComment(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        deleteComment(data) {
            this.setLoading(true)

            return Notes.deleteComment(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        }
    }
})
