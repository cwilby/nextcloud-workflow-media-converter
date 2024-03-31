import ConvertMediaOperation from './components/ConvertMediaOperation.vue'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\WorkflowMediaConverter\\Operation\\ConvertMediaOperation',
	operation: 'a',
	options: ConvertMediaOperation,
	color: '#5cb85c',
})
