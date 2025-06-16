import { defineStore } from 'pinia'
import Vehicles from '@/api/vehicles'

export const useVehiclesStores = defineStore('vehicles', {
    state: () => ({
        vehicles: {},
        loading: false,
        last_page: 1,
        vehiclesTotalCount: 6
    }),
    getters:{
        getVehicles(){
            return this.vehicles
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchVehicles(params) {
            this.setLoading(true)
            
            return Vehicles.get(params)
                .then((response) => {
                    this.vehicles = response.data.data.vehicles.data
                    this.last_page = response.data.data.vehicles.last_page
                    this.vehiclesTotalCount = response.data.data.vehiclesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addVehicle(data) {
            this.setLoading(true)

            return Vehicles.create(data)
                .then((response) => {
                    this.vehicles.push(response.data.data.vehicle)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showVehicle(id) {
            this.setLoading(true)

            return Vehicles.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateVehicle(data) {
            this.setLoading(true)
            
            return Vehicles.update(data)
                .then((response) => {
                    let pos = this.vehicles.findIndex((item) => item.id === response.data.data.vehicle.id)
                    this.vehicles[pos] = response.data.data.vehicle
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteVehicle(id) {
            this.setLoading(true)

            return Vehicles.delete(id)
                .then((response) => {
                    let index = this.vehicles.findIndex((item) => item.id === id)
                    this.vehicles.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        }
    }
})
