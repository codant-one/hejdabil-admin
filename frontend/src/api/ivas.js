import axios from '@axios'

class Ivas {

    get(params) {
        return axios.get('ivas', {params})
    }
    
}

const ivas = new Ivas();

export default ivas;