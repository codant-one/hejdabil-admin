import axios from '@axios'

class HelpVideos {

    get(params) {
        return axios.get('help-videos', {params})
    }

    create(data) {
        return axios.post('/help-videos', data)
    }

    show(id) {
        return axios.get(`/help-videos/${id}`)
    }

    update(data) {
        return axios.post(`/help-videos/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/help-videos/${id}`)
    }

    updateState(id) {
        return axios.get(`/help-videos/updateState/${id}`)
    }
    
}

const helpVideos = new HelpVideos();

export default helpVideos;