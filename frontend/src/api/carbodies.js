import axios from '@axios'

class Carbodies {

    get(params) {
        return axios.get('car-bodies', {params})
    }
    
}

const carbodies = new Carbodies();

export default carbodies;