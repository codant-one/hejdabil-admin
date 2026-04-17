import axios from '@axios'

class Settings {

    get(id) {
        return axios.get(`/settings/${id}`)
    }
    
    colors(data) {
        return axios.post(`/settings/colors/${data.id}`, data.data)
    }

    billings(data) {
        return axios.post(`/settings/billings/${data.id}`, data.data)
    }

    agreements(data) {
        return axios.post(`/settings/agreements/${data.id}`, data.data)
    }
    
}

const settings = new Settings();

export default settings;