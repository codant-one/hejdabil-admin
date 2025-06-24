import axios from '@axios'

class Notes {

    get(params) {
        return axios.get('notes', {params})
    }

    create(data) {
        return axios.post('/notes', data)
    }

    show(id) {
        return axios.get(`/notes/${id}`)
    }

    update(data) {
        return axios.post(`/notes/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/notes/${id}`)
    }
    
}

const notes = new Notes();

export default notes;