import { generateFilePath } from '@nextcloud/router'
import ConvertMediaOperation from './components/ConvertMediaOperation.vue'
import { stringValidator } from './helpers/validators.js'
import { APP_ID } from './utils.js'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(APP_ID, '', 'js/');

if (OCA.WorkflowEngine) {
	OCA.WorkflowEngine.registerCheck({
		class: 'OCA\\WorkflowMediaConverter\\Check\\PathName',
		name: t('workflowengine', 'Path name'),
		operators: () => {
			return [
				{ operator: 'matches', name: t('workflowengine', 'matches') },
				{ operator: '!matches', name: t('workflowengine', 'does not match') },
				{ operator: 'is', name: t('workflowengine', 'is') },
				{ operator: '!is', name: t('workflowengine', 'is not') },
			]
		},
		placeholder: (check) => {
			if (check.operator === 'matches' || check.operator === '!matches') {
				return '/^dummy-.+$/i'
			}
			return 'Documents/Files'
		},
		validate: stringValidator,
	});

	OCA.WorkflowEngine.registerOperator({
		id: 'OCA\\WorkflowMediaConverter\\Operation\\ConvertMediaOperation',
		operation: 'a',
		options: ConvertMediaOperation,
		color: '#5cb85c',
	})
}
