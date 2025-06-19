import axios from '@axios'

class Documents {

    create(data) {
        return axios.post('/documents', data)
    }

    delete(id){
        return axios.delete(`/documents/${id}`)
    }

    send(data) {
        return axios.post('/documents/send', data)
    }
    
}

const documents = new Documents();

export default documents;