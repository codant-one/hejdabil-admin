import axios from '@axios'

class Settings {

    colors(data) {
        return axios.post(`/settings/colors/${data.id}`, data.data)
    }
    
}

const settings = new Settings();

export default settings;