import Vue from 'vue'
import './bootstrap.js'
import AdminSettings from './views/AdminSettings.vue'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

new Vue({ // eslint-disable-line no-new
	el: '#workflow_media_converter-adminSettings',
	render: h => h(AdminSettings),
})
