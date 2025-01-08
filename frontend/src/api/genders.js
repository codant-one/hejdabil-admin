import axios from '@axios'

class Genders {

    get() {
        return axios.get('genders')
    }
}

const genders = new Genders();

export default genders;