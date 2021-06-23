<template>
	<div class="wmc-admin-settings">
		<h2>
			<img src="/apps/workflow_media_converter/img/icon.svg">
			{{ t('workflow_media_converter', 'Media conversion') }}
		</h2>
		<p>To manage the resources used by FFmpeg, you may set the number of threads provided to FFmpeg below.</p>
		<p>There are <strong>{{ maxThreads }}</strong> available CPU cores to NextCloud.  It is recommended to not exceed this limit.</p>
		<p>This value can be set to <strong>0</strong> to let FFmpeg decide how many threads it should use.</p>
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
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'

export default {
	name: 'AdminSettings',

	data: () => ({
		saving: false,
		state: loadState('workflow_media_converter', 'admin-config'),
		readonly: true,
	}),

	computed: {
		maxThreads() {
			return this.state.maxThreads
		},
		threadLimit: {
			get() {
				return this.state.threadLimit
			},
			async set(value) {
				this.state.threadLimit = value
				await this.saveConfig()
				// todo: notify user that changes will not take effect for currently queued jobs
				// it would be wicked sick to get the number of pending conversions out of IJobList and display that. :o)
			},
		},
	},

	methods: {
		validate() {
			this.threadLimit = Math.min(Math.max(this.threadLimit, 0), this.maxThreads)
		},

		saveConfig: debounce(async function() {
			try {
				this.validate()
				this.saving = true
				await axios.put(generateUrl('/apps/workflow_media_converter/admin-settings'), { values: this.state })
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
