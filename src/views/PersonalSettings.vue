<template>
	<div>
		<SettingsSection title="Media Converter" description="In addition to on-the-fly conversion, you may also create batches to convert your existing media to new formats.">
			<h2>{{ t('Batch Jobs') }}</h2>
			<BatchJobList
				:jobs="state.jobs"
				:formats="state.formats"
				@addJob="addJob" />
		</SettingsSection>
	</div>
</template>

<script>
import debounce from 'debounce'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'

import { getUniqueId } from '../utils'

export default {
	data: () => ({
		refreshing: false,
		polling: null,
		state: loadState('workflow_media_converter', 'user-config'),
		loading: false,
		saving: false,
	}),

	created() {
		this.polling = setInterval(() => document.hasFocus() && this.updateStatistics(), 5000)
	},

	beforeDestroy() {
		clearInterval(this.polling)
	},

	methods: {
		addJob: debounce(function() {
			this.state.jobs.push({
				id: getUniqueId(),
				sourceDirectory: null,
				fromFormat: null,
				toFormat: null,
				postConversionSourceRule: 'keep',
				postConversionSourceRuleMoveFolder: null,
				postConversionOutputRule: 'keep',
				postConversionOutputRuleMoveFolder: null,
				postConversionOutputConflictRule: 'preserve',
				postConversionOutputConflictRuleMoveFolder: null,
			})
			this.saveConfig()
		}, 600),

		updateConversionRule: debounce(function(rule) {
			this.state.rules = this.state.rules.map(r => r.id === rule.id ? rule : r)
			this.saveConfig()
		}, 600),

		removeConversionRule(rule) {
			OC.dialogs.confirmDestructive(
				this.t('All queued and running conversions for this rule will be cancelled.'),
				this.t('Delete Rule'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: 'Delete',
					confirmClasses: 'error',
					cancel: 'Cancel',
				},
				(confirmed) => {
					if (!confirmed) return

					this.state.rules = this.state.rules.filter(r => r.id !== rule.id)

					return this.saveConfig()
				},
				true
			)
		},

		async saveConfig() {
			try {
				this.saving = true
				await axios.put(generateUrl('/apps/workflow_media_converter/config'), { values: { rules: this.state.rules } })
			} catch (e) {
				showError(this.t('Failed to save automatic media encoder config'))
				console.error(e)
			} finally {
				this.saving = false
			}
		},

		async updateStatistics() {
			try {
				this.refreshing = true
				const { data } = await axios.get(generateUrl('/apps/workflow_media_converter/statistics'))
				this.state.statistics = data
			} catch (e) {
				showError(this.t('Failed to save automatic media encoder config'))
				console.error(e)
			} finally {
				this.refreshing = false
			}
		},
	},
}
</script>
