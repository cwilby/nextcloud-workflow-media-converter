import Vue from 'vue'
import { generateFilePath } from '@nextcloud/router'
import './bootstrap.js'
import { APP_ID } from './utils.js'
import AdminSettings from './views/AdminSettings.vue'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(APP_ID, '', '');

// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

new Vue({ // eslint-disable-line no-new
	el: '#workflow_media_converter-adminSettings',
	render: h => h(AdminSettings),
})
