import axios from '@axios'

class Activities {

    get(params) {
        return axios.get('activities', {params})
    }

    delete(id){
        return axios.delete(`/activities/${id}`)
    }
    
    deleteAll() {
        return axios.get(`/activities/deleteAll`)
    }

    restoreItem(id) {
        return axios.post(`/activities/trash/restore/${id}`);
    }
}

const activities = new Activities();

export default activities;