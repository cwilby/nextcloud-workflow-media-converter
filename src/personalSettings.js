import Vue from 'vue'
import './bootstrap.js'
import PersonalSettings from './views/PersonalSettings.vue'

new Vue({ // eslint-disable-line no-new
	el: '#workflow_media_convert-personalSettings',
	render: h => h(PersonalSettings),
})
