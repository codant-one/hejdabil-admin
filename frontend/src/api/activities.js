import axios from '@axios'

class Activities {

    get(params) {
        return axios.get('activities', {params})
    }
    
}

const activities = new Activities();

export default activities;