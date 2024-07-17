const state = {
	apiHost: '',
	apiKey: '',
}

const getters = {
	getAppHost: (state) => {
		return state.apiHost
	},
	getAppKey: (state) => {
		return state.apiKey
	},
}

const mutations = {

	setAppHost(state, data) {
		state.apiHost = data
	},
	setAppKey(state, data) {
		state.apiKey = data
	},

}

const actions = {

	setConfig(context, payload) {
		context.commit('setAppHost', payload.apiHost)
		context.commit('setAppKey', payload.apiKey)
	},
}

export default { state, mutations, getters, actions }
