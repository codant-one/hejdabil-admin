import { defineStore } from 'pinia'
import SupplierInvoices from '@/api/supplierInvoices'

export const useSupplierInvoicesStores = defineStore('supplierInvoices', {
    state: () => ({
        supplierInvoices: {},
        suppliers: {},
        clients: {},
        loading: false,
        last_page: 1,
        supplierInvoicesTotalCount: 6,
        totalSum: 0,
        totalTax: 0,
        totalNeto: 0,
        sum: 0,
        tax: 0,
        totalPending: 0,
        totalPaid: 0,
        totalExpired: 0,
        pendingTax: 0,
        paidTax: 0,
        expiredTax: 0,
        state_id: null
    }),
    getters:{
        getSupplierInvoices(){
            return this.supplierInvoices
        },
        getStateId(){
            return this.state_id
        }
    },
    actions: {
        setLoading(payload) {
            this.loading = payload
        },
        setStateId(state_id) {
            this.state_id = state_id
        },
        cleanData() {
            this.state_id = null
        },
        fetchSupplierInvoices(params) {
            this.setLoading(true)
            
            return SupplierInvoices.get(params)
                .then((response) => {
                    this.supplierInvoices = response.data.data.supplierInvoices.data
                    this.last_page = response.data.data.supplierInvoices.last_page
                    this.supplierInvoicesTotalCount = response.data.data.supplierInvoicesTotalCount
                    this.totalSum = response.data.data.totalSum
                    this.totalTax = response.data.data.totalTax
                    this.totalNeto = response.data.data.totalNeto
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addSupplierInvoice(data) {
            this.setLoading(true)

            return SupplierInvoices.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showSupplierInvoice(id) {
            this.setLoading(true)

            return SupplierInvoices.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.supplierInvoice)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateSupplierInvoice(data) {
            this.setLoading(true)
            
            return SupplierInvoices.update(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        updateState(id) {
            this.setLoading(true)

            return SupplierInvoices.updateState(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        credit(id) {
            this.setLoading(true)

            return SupplierInvoices.credit(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        reminder(id) {
            this.setLoading(true)

            return SupplierInvoices.reminder(id)
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
