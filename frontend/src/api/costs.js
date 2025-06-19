import axios from '@axios'

class Costs {

    get(params) {
        return axios.get('costs', {params})
    }

    create(data) {
        return axios.post('/costs', data)
    }

    show(id) {
        return axios.get(`/costs/${id}`)
    }

    update(data) {
        return axios.post(`/costs/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/costs/${id}`)
    }
    
}

const costs = new Costs();

export default costs;