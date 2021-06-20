import ConvertMediaOperation from './components/Operators/ConvertMediaOperation.vue'
import { stringValidator } from './helpers/validators'

OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\WorkflowMediaConverter\\Operation\\ConvertMediaOperation',
	operation: 'keep;preserve',
	options: ConvertMediaOperation,
	color: '#5cb85c',
})

OCA.WorkflowEngine.registerCheck({
	class: 'OCA\\WorkflowMediaConverter\\Check\\FileExtension',
	name: toString('workflow_media_converter', 'File extension'),
	operators: [
		{ operator: 'matches', name: t('workflowengine', 'matches') },
		{ operator: '!matches', name: t('workflowengine', 'does not match') },
		{ operator: 'is', name: t('workflowengine', 'is') },
		{ operator: '!is', name: t('workflowengine', 'is not') },
	],
	validate: stringValidator,
})
