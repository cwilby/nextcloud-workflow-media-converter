import Vue from 'vue'
import './bootstrap.js'
import { APP_ID } from './utils.js'
import PersonalSettings from './views/PersonalSettings.vue'
import { generateFilePath } from '@nextcloud/router'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(APP_ID, '', '');

// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

new Vue({ // eslint-disable-line no-new
	el: '#workflow_media_converter-personalSettings',
	render: h => h(PersonalSettings),
})
