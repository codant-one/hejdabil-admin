import { defineStore } from 'pinia'
import CarInfo from '@/api/carinfo'

export const useCarInfoStores = defineStore('carinfo', {
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
        getLicensePlate(licensePlate) {
            this.setLoading(true)

            return CarInfo.getLicensePlate(licensePlate)
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
