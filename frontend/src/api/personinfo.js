import axios from '@axios'

class PersonInfo {

    getPersonInfo(personId) {
        return axios.get(`/persons/lookup/${personId}`)
    }
    
}

export default new PersonInfo()
