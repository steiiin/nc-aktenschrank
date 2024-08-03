import Vue from 'vue'
import App from './App.vue'
import router from './router.js'

/* Pinia */
import { createPinia, PiniaVuePlugin } from 'pinia'
Vue.use(PiniaVuePlugin)
const pinia = createPinia()

/* Localization */
Vue.mixin({ methods: { t, n } })

/* App */
export default new Vue({
	el: '#aktenschrank',
	router,
	pinia,
	render: h => h(App),
})
