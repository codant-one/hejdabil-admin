import { defineStore } from 'pinia'
import Invoices from '@/api/invoices'

export const useInvoicesStores = defineStore('invoices', {
    state: () => ({
        invoices: {},
        loading: false,
        last_page: 1,
        invoicesTotalCount: 6
    }),
    getters:{
        getInvoices(){
            return this.invoices
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchInvoices(params) {
            this.setLoading(true)
            
            return Invoices.get(params)
                .then((response) => {
                    this.invoices = response.data.data.invoices.data
                    this.last_page = response.data.data.invoices.last_page
                    this.invoicesTotalCount = response.data.data.invoicesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addInvoice(data) {
            this.setLoading(true)

            return Invoices.create(data)
                .then((response) => {
                    this.invoices.push(response.data.data.invoice)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showInvoice(id) {
            this.setLoading(true)

            return Invoices.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.invoice)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateInvoice(data) {
            this.setLoading(true)
            
            return Invoices.update(data)
                .then((response) => {
                    let pos = this.invoices.findIndex((item) => item.id === response.data.data.invoice.id)
                    this.invoices[pos] = response.data.data.invoice
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteInvoice(id) {
            this.setLoading(true)

            return Invoices.delete(id)
                .then((response) => {
                    let index = this.invoices.findIndex((item) => item.id === id)
                    this.invoices.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        }
    }
})
