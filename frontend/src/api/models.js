import axios from '@axios'

class Models {

    get(params) {
        return axios.get('models', {params})
    }

    create(data) {
        return axios.post('/models', data)
    }

    show(id) {
        return axios.get(`/models/${id}`)
    }

    update(data) {
        return axios.post(`/models/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/models/${id}`)
    }
    
}

const models = new Models();

export default models;