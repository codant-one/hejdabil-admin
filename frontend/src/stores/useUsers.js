import { defineStore } from 'pinia'
import Users from '@/api/users'

export const useUsersStores = defineStore('users', {
    state: () => ({
        users: {},
        loading: false,
        last_page: 1,
        usersTotalCount: 6
    }),
    getters:{
        getUsers(){
            return this.users
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchUsers(params) {
            this.setLoading(true)
            
            return Users.get(params)
                .then((response) => {
                    this.users = response.data.data.users.data
                    this.last_page = response.data.data.users.last_page
                    this.usersTotalCount = response.data.data.usersTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addUser(data) {
            this.setLoading(true)

            return Users.create(data)
                .then((response) => {
                    this.users.push(response.data.data.user)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateUser(data, id) {
            this.setLoading(true)
            
            return Users.update(data, id)
                .then((response) => {
                    let pos = this.users.findIndex((item) => item.id === response.data.data.user.id)
                    this.users[pos] = response.data.data.user
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteUser(id) {
            this.setLoading(true)

            return Users.delete(id)
                .then((response) => {
                    let index = this.users.findIndex((item) => item.id === id)
                    this.users.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        getUsersOnline(params) {
            this.setLoading(true)
            
            return Users.getUsersOnline(params)
                .then((response) => {
                    return Promise.resolve(response.data.data.users)
                }).catch(error => {
                    return Promise.reject(error)
                }) 
            
        },
        updatePasswordUser(data, id) {

            return Users.updatePasswordUser(data, id)
                    .then((response) => {
                        return Promise.resolve(response.data.data)
                    }).catch(error => {
                        console.error(error.response.data)
                    }) 
        }
    }
})
