import axios from '@axios'

class Suppliers {

    get(params) {
        return axios.get('suppliers', {params})
    }

    create(data) {
        return axios.post('/suppliers', data)
    }

    show(id) {
        return axios.get(`/suppliers/${id}`)
    }

    update(data) {
        return axios.post(`/suppliers/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/suppliers/${id}`)
    }

    activate(id){
        return axios.get(`/suppliers/activate/${id}`)
    }
}

const suppliers = new Suppliers();

export default suppliers;