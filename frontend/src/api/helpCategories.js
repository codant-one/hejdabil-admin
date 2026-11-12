import axios from '@axios'

class HelpCategories {

    get(params) {
        return axios.get('help-categories', {params})
    }

    create(data) {
        return axios.post('/help-categories', data)
    }

    show(id) {
        return axios.get(`/help-categories/${id}`)
    }

    update(data) {
        return axios.post(`/help-categories/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/help-categories/${id}`)
    }

    updateState(id) {
        return axios.get(`/help-categories/updateState/${id}`)
    }
    
}

const helpCategories = new HelpCategories();

export default helpCategories;