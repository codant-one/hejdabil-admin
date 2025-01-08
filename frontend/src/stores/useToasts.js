import { defineStore } from 'pinia';
import { nanoid } from 'nanoid'

export const useToastsStores = defineStore('toasts', {
    state: () => ({
        items: {}
    }),
    getters:{
        getItems(){
            return this.items
        }
    },
    actions: {
        addToast(toast) {
            this.items = {
                ...toast,
                id: nanoid(),
            }
        },
        /**
         * show a toast message for failed transactions
         * @param {*} state
         * @param {*} toast
         */
        addToastError(toast) {
            this.items = {
                ...toast,
                id: nanoid(),
            }
        }
    }
})