<template>
	<div class="wmc-personal-settings">
		<h2>
			<img src="/apps/workflow_media_converter/img/icon.svg">
			{{ t('workflow_media_converter', 'Media conversion') }}
		</h2>
		<p>Use the form below to start a batch of conversions for existing media.</p>
		<p>You can based on the same set of rules available in Flow.</p>
		<p>Any video that has already been converted with the same rules will not be included in the batch.</p>
		<hr>
		<ConversionBatchList
			:conversion-batches="conversionBatches"
			@changeConversionBatch="changeConversionBatch"
			@makeConversionBatch="makeConversionBatch"
			@saveConversionBatch="confirmSaveConversionBatch"
			@removeConversionBatch="confirmRemoveConversionBatch" />
	</div>
</template>

<script>
import debounce from 'debounce'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'

import { getUniqueId } from '../utils'
import ConversionBatchList from '../components/ConversionBatchList.vue'

export default {
	name: 'PersonalSettings',

	components: { ConversionBatchList },

	data: () => ({
		saving: false,
		state: loadState('workflow_media_converter', 'personal-config'),
		readonly: true,
		newConversionBatch: {},
		pollingInterval: null,
	}),

	computed: {
		conversionBatches() {
			return this.state.conversionBatches
		},
	},

	created() {
		this.pollingInterval = setInterval(async() => {
			if (document.hasFocus() && this.state.conversionBatches.length && this.state.conversionBatches.every(b => b.id)) {
				await this.refreshBatches()
			}
		}, 10000)
	},

	beforeDestroy() {
		clearInterval(this.pollingInterval)
	},

	methods: {
		async refreshBatches() {
			try {
				const { data: state } = await axios.get(generateUrl('/apps/workflow_media_converter/personal-settings'))

				this.state = state
			} catch (error) {
				console.error(error)
			}
		},

		async makeConversionBatch() {
			this.newConversionBatch = {
				id: null,
				status: 'queued',
				convertMediaInSubFolders: false,
				sourceFolder: null,
				sourceExtension: null,
				outputExtension: null,
				postConversionSourceRule: 'keep',
				postConversionSourceRuleMoveFolder: null,
				postConversionOutputRule: 'keep',
				postConversionOutputRuleMoveFolder: null,
				postConversionOutputConflictRule: 'preserve',
				postConversionOutputConflictRuleMoveFolder: null,
				unconverted: 0,
				converted: 0,
			}
			this.state.conversionBatches.push(this.newConversionBatch)
		},

		changeConversionBatch(conversionBatch) {
			Object.entries(conversionBatch).forEach(([key, value]) => {
				this.newConversionBatch[key] = value
			})
		},

		confirmSaveConversionBatch() {
			if (!this.validateSaveConversionBatch()) {
				OC.dialogs.alert(
					this.t('workflow_media_converter', 'Please make sure the form is valid before saving'),
					this.t('workflow_media_converter', 'Invalid data')
				)
				return
			}

			OC.dialogs.confirmDestructive(
				this.t('workflow_media_converter', 'This will batch convert any unconverted media in the specified folder.  Are you sure you want to proceed?'),
				this.t('workflow_media_converter', 'Start conversion batch'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: this.t('workflow_media_converter', 'Yes'),
					cancel: this.t('workflow_media_converter', 'Cancel'),
				},
				confirmed => confirmed && this.saveConversionBatch(),
				true
			)
		},

		validateSaveConversionBatch() {
			if (!this.newConversionBatch.outputExtension
			|| !this.newConversionBatch.sourceFolder
			|| !this.newConversionBatch.sourceExtension
			|| !this.newConversionBatch.postConversionSourceRule
			|| (this.newConversionBatch.postConversionSourceRule === 'move' && !this.newConversionBatch.postConversionSourceRuleMoveFolder)
			|| !this.newConversionBatch.postConversionOutputRule
			|| (this.newConversionBatch.postConversionOutputRule === 'move' && !this.newConversionBatch.postConversionOutputRuleMoveFolder)
			|| !this.newConversionBatch.postConversionOutputConflictRule
			|| (this.newConversionBatch.postConversionOutputConflictRule === 'move' && !this.newConversionBatch.postConversionOutputConflictRuleMoveFolder)) {
				return false
			}

			return true
		},

		async saveConversionBatch() {
			try {
				this.saving = true
				this.newConversionBatch.id = getUniqueId()
				await axios.post(generateUrl('/apps/workflow_media_converter/conversion-batches'), { batch: this.newConversionBatch })
			} catch (error) {
				console.error(error)
			} finally {
				this.saving = false
			}
		},

		confirmRemoveConversionBatch(conversionBatch) {
			OC.dialogs.confirmDestructive(
				conversionBatch.id
					? this.t('workflow_media_converter', 'All queued conversions for this batch will be cancelled, are you sure you want to proceed?')
					: this.t('workflow_media_converter', 'Are you sure you want to stop making this batch?'),
				this.t('workflow_media_converter', 'Delete conversion batch'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: this.t('workflow_media_converter', 'Delete'),
					confirmClasses: 'error',
					cancel: this.t('workflow_media_converter', 'Cancel'),
				},
				confirmed => confirmed && this.removeConversionBatch(conversionBatch),
				true
			)
		},

		async removeConversionBatch(conversionBatch) {
			try {
				this.removingConversionBatch = true

				if (conversionBatch.id) {
					await axios.delete(generateUrl(`/apps/workflow_media_converter/conversion-batches/${conversionBatch.id}`))
				}

				this.state.conversionBatches = this.state.conversionBatches.filter(j => j.id !== conversionBatch.id)
			} catch (error) {
				console.error(error)
			} finally {
				this.removingConversionBatch = false
			}
		},

		saveConfig: debounce(async function() {
			try {
				this.saving = true
				await axios.put(generateUrl('/apps/workflow_media_converter/personal-settings'), { values: this.state })
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
.wmc-personal-settings {
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
