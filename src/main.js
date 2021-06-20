import ConvertToPdf from './ConvertToPdf'

OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\WorkflowPDFConverter\\Operation',
	operation: 'keep;preserve',
	options: ConvertToPdf,
	color: '#dc5047'
})
