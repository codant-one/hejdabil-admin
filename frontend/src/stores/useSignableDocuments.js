import { defineStore } from 'pinia'
import SignableDocuments from '@/api/signableDocuments'

export const useSignableDocumentsStores = defineStore('signableDocuments', {
    state: () => ({
        documents: [],
        suppliers: {},
        loading: false,
        last_page: 1,
        documentsTotalCount: 0,
    }),
    getters: {
        getDocuments() {
            return this.documents
        },
        getSuppliers(){
            return this.suppliers
        }
    },
    actions: {
        setLoading(payload) {
            this.loading = payload
        },
        fetchDocuments(params) {
            this.setLoading(true)
            
            return SignableDocuments.get(params)
                .then((response) => {
                    this.documents = response.data.data.documents.data
                    this.suppliers = response.data.data.suppliers
                    this.last_page = response.data.data.documents.last_page
                    this.documentsTotalCount = response.data.data.documentsTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        addDocument(data) {
            this.setLoading(true)

            return SignableDocuments.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        showDocument(id) {
            this.setLoading(true)

            return SignableDocuments.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.document)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        updateDocument(data) {
            this.setLoading(true)
            
            return SignableDocuments.update(data)
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

            return SignableDocuments.delete(id)
                .then((response) => {
                    let index = this.documents.findIndex((item) => item.id === id)
                    if (index > -1) {
                        this.documents.splice(index, 1)
                    }
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        requestSignature(payload) {
            return new Promise((resolve, reject) => {
                SignableDocuments.sendSignatureRequest(payload.documentId, {
                    email: payload.email,
                    x: payload.x,
                    y: payload.y,
                    page: payload.page,
                    alignment: payload.alignment || 'left'
                })
                .then(response => resolve(response))
                .catch(error => reject(error))
            })
        },
        requestStaticSignature(payload) {
            return new Promise((resolve, reject) => {
                SignableDocuments.sendStaticSignatureRequest(payload.documentId, {
                    email: payload.email,
                    alignment: payload.alignment || 'left'
                })
                .then(response => resolve(response))
                .catch(error => reject(error))
            })
        },
        resendSignature(documentId) {
            return new Promise((resolve, reject) => {
                SignableDocuments.resendSignatureRequest(documentId)
                    .then(response => resolve(response))
                    .catch(error => reject(error))
            })
        },
        sendDocument(data) {
            this.setLoading(true)

            return SignableDocuments.send(data)
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

