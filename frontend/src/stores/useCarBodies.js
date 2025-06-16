import { defineStore } from 'pinia'
import Carbodies from '@/api/carbodies'

export const useCarbodiesStores = defineStore('carbodies', {
    state: () => ({
        carbodies: {},
        loading: false,
        last_page: 1,
        carbodiesTotalCount: 6
    }),
    getters:{
        getCarbodies(){
            return this.carbodies
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchCarbodies(params) {
            this.setLoading(true)
            
            return Carbodies.get(params)
                .then((response) => {
                    this.carbodies = response.data.data.carbodies.data
                    this.last_page = response.data.data.carbodies.last_page
                    this.carbodiesTotalCount = response.data.data.carbodiesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        }
    }
})
