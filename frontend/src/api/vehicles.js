import axios from '@axios'

class Vehicles {

    get(params) {
        return axios.get('vehicles', {params})
    }

    create(data) {
        return axios.post('/vehicles', data)
    }

    show(id) {
        return axios.get(`/vehicles/${id}`)
    }

    update(data) {
        return axios.post(`/vehicles/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/vehicles/${id}`)
    }
    
}

const vehicles = new Vehicles();

export default vehicles;