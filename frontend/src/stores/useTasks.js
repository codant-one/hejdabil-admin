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
        typeTask(id) {
            this.setLoading(true)
            
            return Tasks.type(id)
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
        updateComment(data) {
            this.setLoading(true)

            return Tasks.updateComment(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        deleteComment(data) {
            this.setLoading(true)

            return Tasks.deleteComment(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        }
    }
})
