import { defineStore } from 'pinia'
import Profile from '@/api/profile'

export const useProfileStores = defineStore('profile', {
    state: () => ({
        loading: false
    }),
    actions: {
        updateData(data) {

            return Profile.updateData(data)
                    .then((response) => {
                        return Promise.resolve(response.data.data)
                    }).catch(error => {
                        console.error(error.response.data)
                    })         
        },
        updatePassword(data) {

            return Profile.updatePassword(data)
                    .then((response) => {
                        return Promise.resolve(response.data.data)
                    }).catch(error => {
                        console.error(error.response.data)
                    }) 
        },
        updateCompany(data) {

            return Profile.updateCompany(data)
                    .then((response) => {
                        return Promise.resolve(response.data.data)
                    }).catch(error => {
                        console.error(error.response.data)
                    })         
        },
        updateLogo(data) {

            return Profile.updateLogo(data)
                    .then((response) => {
                        return Promise.resolve(response.data.data)
                    }).catch(error => {
                        console.error(error.response.data)
                    })         
        },

        updateSignature(data) {

            return Profile.updateSignature(data)
                    .then((response) => {
                        return Promise.resolve(response.data.data)
                    }).catch(error => {
                        console.error(error.response.data)
                    })         
        }


  },
})
