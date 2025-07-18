import axios from '@axios'

class Agreements {

    get(params) {
        return axios.get('agreements', {params})
    }

    create(data) {
        return axios.post('/agreements', data)
    }

    show(id) {
        return axios.get(`/agreements/${id}`)
    }

    update(data) {
        return axios.post(`/agreements/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/agreements/${id}`)
    }

    info(){
        return axios.get(`/agreements/info/all`)
    }
    
    sendMails(data) {
        return axios.post(`/agreements/sendMails/${data.id}`, data)
    }
}

const agreements = new Agreements();

export default agreements;