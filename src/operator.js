import ConvertMediaOperation from './components/ConvertMediaOperation.vue'

OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\WorkflowMediaConverter\\Operation\\ConvertMediaOperation',
	operation: 'a',
	options: ConvertMediaOperation,
	color: '#5cb85c',
})
