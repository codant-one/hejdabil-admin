import { defineStore } from 'pinia'
import Dashboard from '@/api/dashboard'

export const useDashboardStores = defineStore('dashboard', {
    state: () => ({
        statisticians: {},
        indicators: {},
        profit: {},
        measures: {},
        team: {},
        vehicles: {},
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
        },
        getMeasures(){
            return this.measures
        },
        getTeam(){
            return this.team
        },
        getVehicles(){
            return this.vehicles
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
        },
        fetchMeasures() {
            this.setLoading(true)
            return Dashboard.measures()
                .then((response) => {
                    this.measures = response.data.data
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        fetchTeam(params) {
            this.setLoading(true)   
            return Dashboard.team(params)
                .then((response) => {
                    this.team = response.data.data
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        fetchVehicles(params) {
            this.setLoading(true)   
            return Dashboard.vehicles(params)
                .then((response) => {
                    this.vehicles = response.data.data
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
        }
    }
})
