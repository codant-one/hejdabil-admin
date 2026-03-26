import axios from '@axios'

class Dashboard {

    statisticians(params) {
        return axios.get('dashboard/statisticians', {params})
    }
    
    indicators(params) {
        return axios.get('dashboard/indicators', {params})
    }

    profit() {
        return axios.get('dashboard/profit')
    }

    measures() {
        return axios.get('dashboard/measures')
    }

    team(params) {
        return axios.get('dashboard/team', {params})
    }

    vehicles(params) {
        return axios.get('dashboard/vehicles', {params})
    }

    reminders() {
        return axios.get('dashboard/reminders')
    }
}

const dashboard = new Dashboard();

export default dashboard;