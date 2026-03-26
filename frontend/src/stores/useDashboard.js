import { defineStore } from 'pinia'
import Dashboard from '@/api/dashboard'

export const useDashboardStores = defineStore('dashboard', {
    state: () => ({
        statisticians: {},
        indicators: {},
        profit: {},
        loading: false,
        last_page: 1,
    }),
    getters:{
        getStatisticians(){
            return this.statisticians
        },
        getIndicators(){
            return this.indicators
        },
        getProfit(){
            return this.profit
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
            
        },
        fetchIndicators(params) {
            this.setLoading(true)
            return Dashboard.indicators(params)
                .then((response) => {
                    this.indicators = response.data.data
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        fetchProfit() {
            this.setLoading(true)
            return Dashboard.profit()
                .then((response) => {
                    this.profit = response.data.data
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })                
        }
    }
})
