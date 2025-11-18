import { defineStore } from 'pinia'
import Payouts from '@/api/payouts'

export const usePayoutsStores = defineStore('payouts', {
    state: () => ({
        payouts: {},
        loading: false,
        last_page: 1,
        payoutsTotalCount: 6
    }),
    getters:{
        getPayouts(){
            return this.payouts
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
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
                    // Agregamos el nuevo payout a la lista actual para que se vea sin necesidad de recargar
                    if (response.data?.data?.payout) {
                        this.payouts.push(response.data.data.payout)
                    }

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
        }
    }
})
