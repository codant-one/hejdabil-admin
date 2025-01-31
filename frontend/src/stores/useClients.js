import { defineStore } from 'pinia'
import Clients from '@/api/clients'

export const useClientsStores = defineStore('clients', {
    state: () => ({
        clients: {},
        loading: false,
        last_page: 1,
        clientsTotalCount: 6
    }),
    getters:{
        getClients(){
            return this.clients
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchClients(params) {
            this.setLoading(true)
            
            return Clients.get(params)
                .then((response) => {
                    this.clients = response.data.data.clients.data
                    this.last_page = response.data.data.clients.last_page
                    this.clientsTotalCount = response.data.data.clientsTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addClient(data) {
            this.setLoading(true)

            return Clients.create(data)
                .then((response) => {
                    this.clients.push(response.data.data.client)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showClient(id) {
            this.setLoading(true)

            return Clients.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.client)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateClient(data) {
            this.setLoading(true)
            
            return Clients.update(data)
                .then((response) => {
                    let pos = this.clients.findIndex((item) => item.id === response.data.data.client.id)
                    this.clients[pos] = response.data.data.client
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteClient(id) {
            this.setLoading(true)

            return Clients.delete(id)
                .then((response) => {
                    let index = this.clients.findIndex((item) => item.id === id)
                    this.clients.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        }
    }
})
