import axios from '@axios'

class Invoices {

    get(params) {
        return axios.get('invoices', {params})
    }

    create(data) {
        return axios.post('/invoices', data)
    }

    show(id) {
        return axios.get(`/invoices/${id}`)
    }

    update(data) {
        return axios.post(`/invoices/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/invoices/${id}`)
    }
    
}

const invoices = new Invoices();

export default invoices;