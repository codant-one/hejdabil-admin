import axios from '@axios'

class Countries {

    get(params) {
        return axios.get('countries', {params})
    }

    create(data) {
        return axios.post('/countries', data)
    }

    show(id) {
        return axios.get(`/countries/${id}`)
    }

    update(data) {
        return axios.post(`/countries/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/countries/${id}`)
    }

    updateState(id) {
        return axios.get(`/countries/updateState/${id}`)
    }
    
}

const countries = new Countries();

export default countries;