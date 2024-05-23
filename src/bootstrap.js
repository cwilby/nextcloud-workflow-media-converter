import Vue from 'vue'
import { generateFilePath } from '@nextcloud/router'
import { APP_ID } from './utils.js'
import { translate, translatePlural } from '@nextcloud/l10n'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(APP_ID, '', 'js/');

// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

Vue.filter('ucwords', (value) =>
	value?.toLowerCase().replace(/\b[a-z]/g, (letter) => letter.toUpperCase()),
)

Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA
