import { defineStore } from 'pinia'

export const useAlertStore = defineStore('alert', {
    state: ()=> ({
        message : '',
        type: '',
    }),
    actions:{
        setAlert(message, type) {
            this.message = message,
            this.type = type

            setTimeout(() => {
                this.message = '',
                this.type = ''
            }, 5000)
        },
    },
})
