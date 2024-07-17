import Vue from 'vue'
import Vuex, { Store } from 'vuex'

import globalStore from './globalStore.js'

Vue.use(Vuex)
export default new Store(globalStore)
