import { defineStore } from 'pinia'
import Payouts from '@/api/payouts'

export const usePayoutsStores = defineStore('payouts', {
    state: () => ({
        payouts: {},
        suppliers: {},
        loading: false,
        last_page: 1,
        payoutsTotalCount: 6,
        state_id: null
    }),
    getters:{
        getPayouts(){
            return this.payouts
        },
        getStateId(){
            return this.state_id
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
         setStateId(state_id) {
            this.state_id = state_id
        },
        cleanData() {
            this.state_id = null
        },
        fetchPayouts(params) {
            this.setLoading(true)

            return Payouts.get(params)
                .then((response) => {
                    this.payouts = response.data.data.payouts.data
                    this.last_page = response.data.data.payouts.last_page
                    this.payoutsTotalCount = response.data.data.payoutsTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        addPayout(data) {
            this.setLoading(true)

            return Payouts.create(data)
                .then((response) => {
                    this.payouts.push(response.data.data.payout)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        showPayout(id) {
            this.setLoading(true)

            return Payouts.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.payout)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        deletePayout(id) {
            this.setLoading(true)

            return Payouts.delete(id)
                .then((response) => {
                    let index = this.payouts.findIndex((item) => item.id === id)
                    this.payouts.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        info() {
            this.setLoading(true)

            return Payouts.info()
                .then((response) => {
                    this.suppliers = response.data.data.suppliers
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
    }
})
