import axios from '@axios'

class Currencies {

    get(params) {
        return axios.get('currencies', {params})
    }

    create(data) {
        return axios.post('/currencies', data)
    }

    show(id) {
        return axios.get(`/currencies/${id}`)
    }

    update(data) {
        return axios.post(`/currencies/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/currencies/${id}`)
    }

    updateState(id) {
        return axios.get(`/currencies/updateState/${id}`)
    }
    
}

const currencies = new Currencies();

export default currencies;