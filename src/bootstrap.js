import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'

Vue.filter('ucwords', (value) =>
	value?.toLowerCase().replace(/\b[a-z]/g, (letter) => letter.toUpperCase())
)

Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA
