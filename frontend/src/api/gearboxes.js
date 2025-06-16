import axios from '@axios'

class Gearboxes {

    get(params) {
        return axios.get('gearboxes', {params})
    }
    
}

const gearboxes = new Gearboxes();

export default gearboxes;