import { defineStore } from 'pinia'
import Billings from '@/api/billings'

export const useBillingsStores = defineStore('billings', {
    state: () => ({
        billings: {},
        suppliers: {},
        clients: {},
        loading: false,
        last_page: 1,
        billingsTotalCount: 6
    }),
    getters:{
        getBillings(){
            return this.billings
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
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
                    let pos = this.billings.findIndex((item) => item.id === response.data.data.billing.id)
                    this.billings[pos] = response.data.data.billing
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
    }
})
