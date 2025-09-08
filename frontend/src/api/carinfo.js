import axios from '@axios'

class CarInfo {

    getLicensePlate(licensePlate) {
        return axios.get(`/cars/lookup/${licensePlate}`)
    }
    
}

const carInfo = new CarInfo();

export default carInfo;