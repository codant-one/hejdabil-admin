import axios from '@axios'

class Plans {

    get(params) {
        return axios.get('plans', {params})
    }

    create(data) {
        return axios.post('/plans', data)
    }

    show(id) {
        return axios.get(`/plans/${id}`)
    }

    update(data) {
        return axios.post(`/plans/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/plans/${id}`)
    }

    updateState(id) {
        return axios.get(`/plans/updateState/${id}`)
    }
    
}

const plans = new Plans();

export default plans;