import { defineStore } from 'pinia'
import Dashboard from '@/api/dashboard'

export const useDashboardStores = defineStore('dashboard', {
    state: () => ({
        statisticians: {},
        loading: false,
        last_page: 1,
    }),
    getters:{
        getStatisticians(){
            return this.statisticians
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchStatisticians(params) {
            this.setLoading(true)
            
            return Dashboard.statisticians(params)
                .then((response) => {
                    this.statisticians = response.data.data
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        }
    }
})
