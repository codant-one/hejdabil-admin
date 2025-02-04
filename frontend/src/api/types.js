import axios from '@axios'

class Types {

    get() {
        return axios.get('types')
    }
}

const types = new Types();

export default types;