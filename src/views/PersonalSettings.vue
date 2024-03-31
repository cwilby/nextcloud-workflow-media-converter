<template>
	<div class="wmc-personal-settings">
		<h2>
			<img :src="iconUrl">
			{{ t('workflow_media_converter', 'Media conversion') }}
		</h2>
		<p>
			{{
				t(
					'workflow_media_converter',
					'You may create conversion batches to convert existing media based on a set of rules.'
				)
			}}
		</p>
		<hr>
		<ConversionBatchList :conversion-batches="conversionBatches"
			:threads="threads"
			@change-conversion-batch="changeConversionBatch"
			@make-conversion-batch="makeConversionBatch"
			@save-conversion-batch="confirmSaveConversionBatch"
			@remove-conversion-batch="confirmRemoveConversionBatch" />
	</div>
</template>

<script>
import debounce from 'debounce'
import { showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'

import { generateUrl, getUniqueId } from '../utils.js'
import ConversionBatchList from '../components/ConversionBatchList.vue'

export default {
	name: 'PersonalSettings',

	components: { ConversionBatchList },

	data: () => ({
		saving: false,
		state: loadState('workflow_media_converter', 'personal-config'),
		threads: loadState('workflow_media_converter', 'threadLimit'),
		readonly: true,
		newConversionBatch: {},
		pollingInterval: null,
	}),

	computed: {
		iconUrl() {
			return generateUrl('img/icon.svg')
		},
		conversionBatches() {
			return this.state.conversionBatches
		},
	},

	created() {
		this.pollingInterval = setInterval(async () => {
			if (
				this.state.conversionBatches.length
				&& this.state.conversionBatches.every((b) => b.id)
			) {
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
				const { data: state } = await axios.get(
					generateUrl('personal-settings'),
				)

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
				additionalConversionFlags: null,
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
				this.$set(this.newConversionBatch, key, value)
			})
		},

		confirmSaveConversionBatch() {
			if (!this.validateSaveConversionBatch()) {
				OC.dialogs.alert(
					this.t(
						'workflow_media_converter',
						'Please make sure the form is valid before saving',
					),
					this.t('workflow_media_converter', 'Invalid data'),
				)
				return
			}

			OC.dialogs.confirmDestructive(
				this.t(
					'workflow_media_converter',
					'This will batch convert any unconverted media in the specified folder.  Are you sure you want to proceed?',
				),
				this.t('workflow_media_converter', 'Start conversion batch'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: this.t('workflow_media_converter', 'Yes'),
					cancel: this.t('workflow_media_converter', 'Cancel'),
				},
				(confirmed) => confirmed && this.saveConversionBatch(),
				true,
			)
		},

		validateSaveConversionBatch() {
			const batch = this.newConversionBatch

			return (
				batch.outputExtension
				&& batch.sourceFolder
				&& batch.sourceExtension
				&& batch.postConversionSourceRule
				&& (batch.postConversionSourceRule === 'move'
					? batch.postConversionSourceRuleMoveFolder
					: true)
				&& batch.postConversionOutputRule
				&& (batch.postConversionOutputRule === 'move'
					? batch.postConversionOutputRuleMoveFolder
					: true)
				&& batch.postConversionOutputConflictRule
				&& (batch.postConversionOutputConflictRule === 'move'
					? batch.postConversionOutputConflictRuleMoveFolder
					: true)
			)
		},

		async saveConversionBatch() {
			try {
				this.saving = true
				this.newConversionBatch.id = getUniqueId()
				await axios.post(
					generateUrl('conversion-batches'),
					{ batch: this.newConversionBatch },
				)
			} catch (error) {
				this.conversionBatches.splice(
					this.conversionBatches.findIndex(
						(b) => b.id === this.newConversionBatch.id,
					),
					1,
				)
				this.newConversionBatch = {}
				OC.dialogs.alert(
					error.response.data,
					this.t('workflow_media_converter', 'Error saving'),
				)
			} finally {
				this.saving = false
			}
		},

		confirmRemoveConversionBatch(conversionBatch) {
			OC.dialogs.confirmDestructive(
				conversionBatch.id
					? this.t(
						'workflow_media_converter',
						'All queued conversions for this batch will be cancelled, are you sure you want to proceed?',
					  )
					: this.t(
						'workflow_media_converter',
						'Are you sure you want to stop making this batch?',
					  ),
				this.t('workflow_media_converter', 'Delete conversion batch'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: this.t('workflow_media_converter', 'Delete'),
					confirmClasses: 'error',
					cancel: this.t('workflow_media_converter', 'Cancel'),
				},
				(confirmed) => confirmed && this.removeConversionBatch(conversionBatch),
				true,
			)
		},

		async removeConversionBatch(conversionBatch) {
			try {
				this.removingConversionBatch = true

				if (conversionBatch.id) {
					await axios.delete(
						generateUrl(`conversion-batches/${conversionBatch.id}`),
					)
				}

				this.state.conversionBatches = this.state.conversionBatches.filter(
					(j) => j.id !== conversionBatch.id,
				)
			} catch (error) {
				console.error(error)
			} finally {
				this.removingConversionBatch = false
			}
		},

		saveConfig: debounce(async function() {
			try {
				this.saving = true
				await axios.put(
					generateUrl('personal-settings'),
					{ values: this.state },
				)
			} catch (e) {
				showError(
					this.t(
						'workflow_media_converter',
						'Failed to save config, please try again shortly',
					),
				)
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
	input[type='number'] {
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
		input[type='range'] {
			width: 15em;
		}
		input[type='number'] {
			margin-left: 1em;
			width: 3.33em;
			text-align: center;
		}
	}
}
</style>
