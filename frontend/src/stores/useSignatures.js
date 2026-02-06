import { defineStore } from 'pinia'
import Signatures from '@/api/signatures'

export const useSignaturesStore = defineStore('signatures', {
    state: () => ({
        signatures: [],
        loading: false,
        last_page: 1,
        signaturesTotalCount: 0,
    }),
    getters: {
        getSignatures() {
            return this.signatures
        }
    },
    actions: {
        setLoading(payload) {
            this.loading = payload
        },
        fetchSignatures(params) {
            this.setLoading(true)
            
            return Signatures.get(params)
                .then((response) => {
                    this.signatures = response.data.data
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        createSignature(data) {
            this.setLoading(true)

            return Signatures.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        showSignature(id) {
            this.setLoading(true)

            return Signatures.show(id)
                .then((response) => {
                    return Promise.resolve(response.data)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        updateSignature(id, data) {
            this.setLoading(true)
            
            return Signatures.update(id, data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        deleteSignature(id) {
            this.setLoading(true)

            return Signatures.delete(id)
                .then((response) => {
                    let index = this.signatures.findIndex((item) => item.id === id)
                    if (index > -1) {
                        this.signatures.splice(index, 1)
                    }
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        cancelSignature(id) {
            this.setLoading(true)

            return Signatures.cancel(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        getSignaturesInfo() {
            this.setLoading(true)

            return Signatures.info()
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        logView(token) {
            return Signatures.logView(token)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
        },
        checkStatus(token) {
            this.setLoading(true)

            return Signatures.checkStatus(token)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        getDetails(token) {
            this.setLoading(true)

            return Signatures.getDetails(token)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        getSignedPdf(token) {
            this.setLoading(true)

            return Signatures.getSignedPdf(token)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        getUnsignedPdf(token) {
            this.setLoading(true)

            return Signatures.getUnsignedPdf(token)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        submitSignature(token, payload) {
            this.setLoading(true)

            return Signatures.submitSignature(token, payload)
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
