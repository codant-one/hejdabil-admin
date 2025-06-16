import { defineStore } from 'pinia'
import Equipments from '@/api/equipments'

export const useEquipmentsStores = defineStore('equipments', {
    state: () => ({
        equipments: {},
        loading: false,
        last_page: 1,
        equipmentsTotalCount: 6
    }),
    getters:{
        getEquipments(){
            return this.equipments
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchEquipments(params) {
            this.setLoading(true)
            
            return Equipments.get(params)
                .then((response) => {
                    this.equipments = response.data.data.equipments.data
                    this.last_page = response.data.data.equipments.last_page
                    this.equipmentsTotalCount = response.data.data.equipmentsTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        }
    }
})
