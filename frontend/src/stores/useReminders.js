import { defineStore } from 'pinia'
import Reminders from '@/api/reminders'

export const useRemindersStores = defineStore('reminders', {
    state: () => ({
        reminders: [],
        loading: false,
        last_page: 1
    }),
    getters:{
        //
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },  
        addReminder(data) {
            this.setLoading(true)

            return Reminders.create(data)
                .then((response) => {
                    this.reminders.push(response.data.data.reminder)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateState(id, is_done) {
            this.setLoading(true)

            return Reminders.updateState(id, { is_done })
                .then((response) => {
                    const index = this.reminders.findIndex(item => item.id === id)

                    if (index !== -1)
                        this.reminders[index] = response.data.data.reminder

                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        deleteReminder(id) {
            this.setLoading(true)

            return Reminders.delete(id)
                .then((response) => {
                    let index = this.reminders.findIndex((item) => item.id === id)
                    this.reminders.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        deleteCompleted() {
            this.setLoading(true)

            return Reminders.deleteCompleted()
                .then((response) => {
                    this.reminders = this.reminders.filter(item => !item.is_done)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        }
    }
})
