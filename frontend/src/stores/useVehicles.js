import { defineStore } from 'pinia'
import Vehicles from '@/api/vehicles'
import { set } from 'date-fns'

export const useVehiclesStores = defineStore('vehicles', {
    state: () => ({
        vehicles: {},
        brands: {},
        models: {},
        gearboxes: {},
        suppliers: {},
        loading: false,
        reg_num: '',
        common_info: {},
        last_page: 1,
        vehiclesTotalCount: 6
    }),
    getters:{
        getVehicles(){
            return this.vehicles
        },
        getBrands(){
            return this.brands
        },
        getModels(){
            return this.models
        },
        getGearboxes(){
            return this.gearboxes
        },
        getSuppliers(){
            return this.suppliers
        },
        getRegNum(){
            return this.reg_num
        },
        getCommonInfo(){
            return this.common_info
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        setRegNum(reg_num){
            this.reg_num = reg_num
        },
        setCommonInfo(common_info){
            this.common_info = common_info
        },
        fetchVehicles(params) {
            this.setLoading(true)
            
            return Vehicles.get(params)
                .then((response) => {
                    this.brands = response.data.data.brands
                    this.models = response.data.data.models
                    this.gearboxes = response.data.data.gearboxes
                    this.suppliers = response.data.data.suppliers
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
        },
        sendVehicle(data) {
            this.setLoading(true)
            
            return Vehicles.send(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        findByRegNum(data) {
            this.setLoading(true)
            
            return Vehicles.findByRegNum(data)
                .then((response) => {
                    return Promise.resolve(response.data)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        }
    }
})
