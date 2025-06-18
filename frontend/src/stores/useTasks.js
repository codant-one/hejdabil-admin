import { defineStore } from 'pinia'
import Tasks from '@/api/tasks'

export const useTasksStores = defineStore('tasks', {
    state: () => ({
        loading: false
    }),
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        addTask(data) {
            this.setLoading(true)

            return Tasks.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateTask(data) {
            this.setLoading(true)
            
            return Tasks.update(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteTask(id) {
            this.setLoading(true)

            return Tasks.delete(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        sendComment(data) {
            this.setLoading(true)

            return Tasks.comment(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
    }
})
