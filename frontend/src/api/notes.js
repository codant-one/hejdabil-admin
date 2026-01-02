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

    comment(data) {
        return axios.post('/notes/comment', data)
    }

    updateComment(data) {
        return axios.put(`/notes/comment/${data.comment_id}`, data)
    }

    deleteComment(data) {
        return axios.delete(`/notes/comment/${data.comment_id}`, { data: data })
    }
    
}

const notes = new Notes();

export default notes;