import { defineStore } from 'pinia'
import Billings from '@/api/billings'

export const useBillingsStores = defineStore('billings', {
    state: () => ({
        billings: {},
        suppliers: {},
        clients: {},
        loading: false,
        last_page: 1,
        billingsTotalCount: 6,
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
        getBillings(){
            return this.billings
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
        fetchBillings(params) {
            this.setLoading(true)
            
            return Billings.get(params)
                .then((response) => {
                    this.suppliers = response.data.data.suppliers
                    this.clients = response.data.data.clients
                    this.billings = response.data.data.billings.data
                    this.last_page = response.data.data.billings.last_page
                    this.billingsTotalCount = response.data.data.billingsTotalCount
                    this.totalSum = response.data.data.totalSum
                    this.totalTax = response.data.data.totalTax
                    this.totalNeto = response.data.data.totalNeto
                    this.sum = response.data.data.sum
                    this.tax = response.data.data.tax
                    this.totalPending = response.data.data.totalPending
                    this.totalPaid = response.data.data.totalPaid
                    this.totalExpired = response.data.data.totalExpired
                    this.pendingTax = response.data.data.pendingTax
                    this.paidTax = response.data.data.paidTax
                    this.expiredTax = response.data.data.expiredTax
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addBilling(data) {
            this.setLoading(true)

            return Billings.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showBilling(id) {
            this.setLoading(true)

            return Billings.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.billing)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateBilling(data) {
            this.setLoading(true)
            
            return Billings.update(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteBilling(id) {
            this.setLoading(true)

            return Billings.delete(id)
                .then((response) => {
                    let index = this.billings.findIndex((item) => item.id === id)
                    this.billings.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        updateState(id) {
            this.setLoading(true)

            return Billings.updateState(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        all() {
            this.setLoading(true)

            return Billings.all()
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        sendMails(data) {
            this.setLoading(true)
            
            return Billings.sendMails(data)
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

            return Billings.credit(id)
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

            return Billings.reminder(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        info() {
            this.setLoading(true)

            return Billings.info()
                .then((response) => {
                    this.suppliers = response.data.data.suppliers
                    this.clients = response.data.data.clients
                    this.totalSum = response.data.data.totalSum
                    this.totalTax = response.data.data.totalTax
                    this.totalNeto = response.data.data.totalNeto
                    this.sum = response.data.data.sum
                    this.tax = response.data.data.tax
                    this.totalPending = response.data.data.totalPending
                    this.totalPaid = response.data.data.totalPaid
                    this.totalExpired = response.data.data.totalExpired
                    this.pendingTax = response.data.data.pendingTax
                    this.paidTax = response.data.data.paidTax
                    this.expiredTax = response.data.data.expiredTax
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
    }
})
