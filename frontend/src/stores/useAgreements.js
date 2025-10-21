import { defineStore } from 'pinia'
import Agreements from '@/api/agreements'
import vehicles from '@/api/vehicles'

export const useAgreementsStores = defineStore('agreements', {
    state: () => ({
        agreements: {},
        brands: {},
        models: {},
        gearboxes: {},
        carbodies: {},
        ivas: {},
        fuels: {},
        states: {},
        guarantyTypes: {},
        insuranceTypes: {},
        currencies: {},
        paymentTypes: {},
        agreementTypes: {},
        vehicles: {},
        clients: {},
        client_types: {},
        identifications: {},
        advances: {},
        commission_types: {},
        agreement_id: 0,
        commission_id: 0,
        offer_id: 0,
        loading: false,
        last_page: 1,
        agreementsTotalCount: 6,
        suppliers: {}
    }),
    getters:{
        getAgreements(){
            return this.agreements
        },
        getSuppliers(){
            return this.suppliers
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchAgreements(params) {
            this.setLoading(true)
            
            return Agreements.get(params)
                .then((response) => {
                    this.agreements = response.data.data.agreements.data
                    this.suppliers = response.data.data.suppliers
                    this.last_page = response.data.data.agreements.last_page
                    this.agreementsTotalCount = response.data.data.agreementsTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addAgreement(data) {
            this.setLoading(true)

            return Agreements.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showAgreement(id) {
            this.setLoading(true)

            return Agreements.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.agreement)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateAgreement(data) {
            this.setLoading(true)
            
            return Agreements.update(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteAgreement(id) {
            this.setLoading(true)

            return Agreements.delete(id)
                .then((response) => {
                    let index = this.agreements.findIndex((item) => item.id === id)
                    this.agreements.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        info() {
            this.setLoading(true)

            return Agreements.info()
                .then((response) => {
                    this.brands = response.data.data.brands
                    this.models = response.data.data.models
                    this.gearboxes = response.data.data.gearboxes
                    this.carbodies = response.data.data.carbodies
                    this.ivas = response.data.data.ivas
                    this.fuels = response.data.data.fuels
                    this.states = response.data.data.states
                    this.guarantyTypes = response.data.data.guarantyTypes
                    this.insuranceTypes = response.data.data.insuranceTypes
                    this.currencies = response.data.data.currencies
                    this.paymentTypes = response.data.data.paymentTypes
                    this.agreementTypes = response.data.data.agreementTypes
                    this.vehicles = response.data.data.vehicles
                    this.agreement_id = response.data.data.agreement_id
                    this.commission_id = response.data.data.commission_id
                    this.offer_id = response.data.data.offer_id
                    this.clients = response.data.data.clients
                    this.client_types = response.data.data.client_types
                    this.identifications = response.data.data.identifications
                    this.advances = response.data.data.advances
                    this.commission_types = response.data.data.commission_types
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        sendMails(data) {
            this.setLoading(true)
            
            return Agreements.sendMails(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },

        requestSignature(payload) {
            return new Promise((resolve, reject) => {
              // La URL sigue siendo la misma, pero ahora le añadimos un cuerpo (body) a la petición.
              // El segundo argumento de axios.post es el objeto de datos que se enviará.
              axios.post(
                `/agreements/${payload.agreementId}/send-signature-request`, 
                { email: payload.email,
                    x: payload.x,
                    y: payload.y,
                    page: payload.page
                 }
              )
              .then(response => resolve(response))
              .catch(error => reject(error))
            })
          },

          requestStaticSignature(payload) {
            return new Promise((resolve, reject) => {
              axios.post(`/agreements/${payload.agreementId}/send-static-signature-request`, payload)
                .then(response => resolve(response))
                .catch(error => reject(error))
            })
          },
    }
})
