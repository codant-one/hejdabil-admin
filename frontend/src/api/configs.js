import axios from '@axios'

class Configs {

    get(key) {
        return axios.get(`featured/${key}`)
    }

    post(data) {
        return axios.post(`featured/${data.key}`, data.params)
    }

    postLogo(data) {
        return axios.post(`featured/${data.key}/logo`, data.params)
    }
    
}

const configs = new Configs();

export default configs;