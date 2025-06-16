import { defineStore } from 'pinia'
import Gearboxes from '@/api/gearboxes'

export const useGearboxesStores = defineStore('gearboxes', {
    state: () => ({
        gearboxes: {},
        loading: false,
        last_page: 1,
        gearboxesTotalCount: 6
    }),
    getters:{
        getGearboxes(){
            return this.gearboxes
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchGearboxes(params) {
            this.setLoading(true)
            
            return Gearboxes.get(params)
                .then((response) => {
                    this.gearboxes = response.data.data.gearboxes.data
                    this.last_page = response.data.data.gearboxes.last_page
                    this.gearboxesTotalCount = response.data.data.gearboxesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        }
    }
})
