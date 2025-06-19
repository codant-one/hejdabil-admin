import { defineStore } from 'pinia'
import Documents from '@/api/documents'

export const useDocumentsStores = defineStore('documents', {
    state: () => ({
        documents: {},
        loading: false,
    }),
    getters:{
        getDocuments(){
            return this.documents
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        addDocument(data) {
            this.setLoading(true)

            return Documents.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        deleteDocument(id) {
            this.setLoading(true)

            return Documents.delete(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        sendDocument(data) {
            this.setLoading(true)

            return Documents.send(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
    }
})
