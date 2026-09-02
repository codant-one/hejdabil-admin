import { defineStore } from 'pinia'
import Alerts from '@/api/alerts'

export const useAlertStore = defineStore('alert', {
    state: ()=> ({
        message : '',
        type: '',
        loading: false,
        alerts: {},
        last_page: 1,
        alertsTotalCount: 0,
    }),
    getters:{
        getAlerts(){
            return this.alerts
        }
    },
    actions:{
        setLoading(payload) {
            this.loading = payload
        },
        setAlert(message, type) {
            this.message = message,
            this.type = type

            setTimeout(() => {
                this.message = '',
                this.type = ''
            }, 5000)
        },
        fetchAlerts(params) {
            this.setLoading(true)
            
            return Alerts.get(params)
                .then((response) => {
                    this.alerts = response.data.data.alerts.data
                    this.last_page = response.data.data.alerts.last_page
                    this.alertsTotalCount = response.data.data.alertsTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },       
    },
})
