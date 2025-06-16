import axios from '@axios'

class Equipments {

    get(params) {
        return axios.get('equipments', {params})
    }
    
}

const equipments = new Equipments();

export default equipments;