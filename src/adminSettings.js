import Vue from 'vue'
import './bootstrap.js'
import AdminSettings from './views/AdminSettings.vue'

new Vue({ // eslint-disable-line no-new
	el: '#workflow_media_converter-adminSettings',
	render: h => h(AdminSettings),
})
