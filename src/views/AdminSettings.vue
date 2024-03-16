<template>
	<div class="wmc-admin-settings">
		<h2>
			<img :src="iconUrl">
			{{ t('workflow_media_converter', 'Media conversion') }}
		</h2>
		<NcCheckboxRadioSwitch :checked.sync="convertMediaInParallel">
			{{ t("workflow_media_converter", "Enable parallel media conversion") }}
		</NcCheckboxRadioSwitch>
		<hr>
		<p>{{ t('workflow_media_converter', 'You may set the number of threads used by FFmpeg to manage the resources used by FFmpeg.') }}</p>
		<p>{{ t('workflow_media_converter', 'This value can be set to 0 to let FFmpeg choose how many threads it should use depending on the codec.') }}</p>
		<p>{{ t('workflow_media_converter', 'Changes made here will apply to any media that is not being converted at this moment in time.') }}</p>
		<div class="wmc-threads">
			<input v-model="threadLimit"
				type="range"
				min="0"
				:max="maxThreads"
				:disabled="saving">
			<input v-model="threadLimit"
				min="0"
				:max="maxThreads"
				type="number"
				:disabled="saving">
		</div>
	</div>
</template>

<script>
import debounce from 'debounce'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'

export default {
	name: 'AdminSettings',

	components: { NcCheckboxRadioSwitch },

	data: () => ({
		saving: false,
		state: loadState('workflow_media_converter', 'admin-config'),
		readonly: true,
	}),

	computed: {
		maxThreads() {
			return this.state.maxThreads
		},
		iconUrl() {
			return generateUrl('/custom_apps/workflow_media_converter/img/icon.svg')
		},
		threadLimit: {
			get() {
				return this.state.threadLimit
			},
			async set(value) {
				this.state.threadLimit = Math.min(Math.max(value, 0), this.maxThreads)
				await this.saveConfig()
			},
		},
		convertMediaInParallel: {
			get() {
				return this.state.convertMediaInParallel
			},
			async set(value) {
				this.state.convertMediaInParallel = value
				await this.saveConfig()
			},
		},
	},

	methods: {
		saveConfig: debounce(async function() {
			try {
				this.saving = true
				await axios.put(generateUrl('/custom_apps/workflow_media_converter/admin-settings'), { values: this.state })
			} catch (e) {
				showError(this.t('workflow_media_converter', 'Failed to save config, please try again shortly'))
				console.error(e)
			} finally {
				this.saving = false
			}
		}, 600),
	},
}
</script>

<style lang="scss">
.wmc-admin-settings {
	/* Chrome, Safari, Edge, Opera */
	input::-webkit-outer-spin-button,
	input::-webkit-inner-spin-button {
		-webkit-appearance: none;
		margin: 0;
	}

	/* Firefox */
	input[type=number] {
		-moz-appearance: textfield;
	}
	img {
		width: 1em;
		height: 1em;
	}
	padding: 1em;
	p {
		margin-bottom: 1em;
	}
	.wmc-threads {
		display: flex;
		input[type=range] {
			width: 15em;
		}
		input[type=number] {
			margin-left: 1em;
			width: 3.33em;
			text-align: center;
		}
	}
}
</style>
