import { defineStore } from 'pinia'
import Swish from '@/api/swish'

export const useSwishStores = defineStore('swish', {
    state: () => ({
        loading: false
    }),
    getters: {
        //
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        createPayout(payeeAlias, amount, payoutRef) {
            this.setLoading(true)

            return Swish.createPayout(payeeAlias, amount, payoutRef)
                .then((response) => {
                    return Promise.resolve(response.data)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        handlePayout(payload) {
            this.setLoading(true)

            return Swish.handlePayout(payload)
                .then((response) => {
                    return Promise.resolve(response.data)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        }
    }
})
