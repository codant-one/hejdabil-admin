import { defineStore } from 'pinia'
import SupplierInvoices from '@/api/supplierInvoices'

export const useSupplierInvoicesStores = defineStore('supplierInvoices', {
    state: () => ({
        supplierInvoices: {},
        loading: false,
        last_page: 1,
        supplierInvoicesTotalCount: 6,
        supplier_info: null,
    }),
    getters:{
        getSupplierInvoices(){
            return this.supplierInvoices
        },
        getSupplierInfo(){
            return this.supplier_info
        }
    },
    actions: {
        setLoading(payload) {
            this.loading = payload
        },
        fetchSupplierInvoices(params) {
            this.setLoading(true)
            
            return SupplierInvoices.get(params)
                .then((response) => {
                    this.supplierInvoices = response.data.data.supplierInvoices.data
                    this.last_page = response.data.data.supplierInvoices.last_page
                    this.supplierInvoicesTotalCount = response.data.data.supplierInvoicesTotalCount
                    this.supplier_info = response.data.data.supplier
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
        replaceFile(data) {
            this.setLoading(true)

            return SupplierInvoices.replaceFile(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        all(params) {
            this.setLoading(true)

            return SupplierInvoices.all(params)
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
