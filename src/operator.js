import { generateFilePath } from '@nextcloud/router'
import ConvertMediaOperation from './components/ConvertMediaOperation.vue'
import { APP_ID } from './utils.js'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(APP_ID, '', 'js/');

OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\WorkflowMediaConverter\\Operation\\ConvertMediaOperation',
	operation: 'a',
	options: ConvertMediaOperation,
	color: '#5cb85c',
})
