<template>
	<div>
		<div class="wmc-conversion-batch__delete-button">
			<Actions>
				<ActionButton icon="icon-delete" @click="$emit('remove')" />
			</Actions>
		</div>
		<div class="grid">
			<div class="column">
				<div class="wmc-conversion-batch__source-directory">
					<span>{{ t('workflow_media_converter', `Convert all files in this folder`) }}</span>
					<div>
						<button @click="openFilePicker('sourceFolder')">
							Choose Folder
						</button>
						<span>{{ sourceFolder }}</span>
					</div>
				</div>
				<CheckboxRadioSwitch :checked.sync="convertMediaInSubFolders">
					{{ t('workflow_media_converter', 'Convert media in sub-folders?') }}
				</CheckboxRadioSwitch>
				<div class="wmc-conversion-batch__from-format">
					<label>With this file extension/format</label>
					<select v-model="sourceExtension" class="wmc-conversion-batch__from-format-picker">
						<option value="" />
						<option
							v-for="(format, index) in formats"
							:key="`format-from-${index}`"
							:value="format.extension">
							<span>.{{ format.extension }} ({{ format.label }})</span>
						</option>
					</select>
				</div>
				<div class="wmc-conversion-batch__to-format">
					<label>Into this file extension/format</label>
					<select v-model="outputExtension" class="wmc-conversion-batch__to-format-picker">
						<option value="" />
						<option
							v-for="(format, index) in formats"
							:key="`format-to-${index}`"
							:value="format.extension">
							<span>.{{ format.extension }} ({{ format.label }})</span>
						</option>
					</select>
				</div>
			</div>
			<div class="column">
				<div class="mb">
					<p>{{ t('workflow_media_converter', 'After the source file has been converted:') }}</p>
					<select v-model="postConversionSourceRule">
						<option v-for="option in postConversionSourceRules" :key="option.id" :value="option.id">
							{{ option.label }}
						</option>
					</select>
					<div v-if="postConversionSourceRule === 'move'">
						<button @click="openFilePicker('postConversionSourceRuleMoveFolder')">
							{{ t('workflow_media_converter', 'Choose Folder') }}
						</button>
						<span>{{ postConversionSourceRuleMoveFolder }}</span>
					</div>
				</div>

				<div class="mb">
					<p>{{ t('workflow_media_converter', 'After the new output is created:') }}</p>
					<select v-model="postConversionOutputRule">
						<option v-for="option in postConversionOutputRules" :key="option.id" :value="option.id">
							{{ option.label }}
						</option>
					</select>
					<div v-if="postConversionOutputRule === 'move'">
						<button @click="openFilePicker('postConversionOutputRuleMoveFolder')">
							{{ t('workflow_media_converter', 'Choose Folder') }}
						</button>
						<span>{{ postConversionOutputRuleMoveFolder }}</span>
					</div>
				</div>

				<div class="mb">
					<p>{{ t('workflow_media_converter', 'If there is a file with the same name as the new output:') }}</p>
					<select v-model="postConversionOutputConflictRule">
						<option v-for="option in postConversionOutputConflictRules" :key="option.id" :value="option.id">
							{{ option.label }}
						</option>
					</select>
					<div v-if="postConversionOutputConflictRule === 'move'">
						<button @click="openFilePicker('postConversionOutputConflictRuleMoveFolder')">
							{{ t('workflow_media_converter', 'Choose Folder') }}
						</button>
						<span>{{ postConversionOutputConflictRuleMoveFolder }}</span>
					</div>
				</div>
			</div>
		</div>
		<div class="wmc-conversion-batch__actions">
			<button v-if="!conversionBatch.id" class="save" @click="$emit('save')">
				{{ t('workflow_media_converter', 'Save') }}
			</button>
		</div>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import postConversionRules from '../mixins/postConversionRules'
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch'
import formats from '../constants/formats'
import { FilePicker } from '@nextcloud/dialogs'

export default {
	components: { Actions, ActionButton, CheckboxRadioSwitch },

	mixins: [postConversionRules],

	props: {
		conversionBatch: {
			required: true,
			type: Object,
		},
	},

	data: () => ({
		formats,
	}),

	computed: {
		sourceFolder: {
			get() {
				return this.conversionBatch.sourceFolder
			},
			set(sourceFolder) {
				this.emitChange({ sourceFolder })
			},
		},
		convertMediaInSubFolders: {
			get() {
				return this.conversionBatch.convertMediaInSubFolders
			},
			set(convertMediaInSubFolders) {
				this.emitChange({ convertMediaInSubFolders })
			},
		},
		sourceExtension: {
			get() {
				return this.conversionBatch.sourceExtension
			},
			set(sourceExtension) {
				this.emitChange({ sourceExtension })
			},
		},
		outputExtension: {
			get() {
				return this.conversionBatch.outputExtension
			},
			set(outputExtension) {
				this.emitChange({ outputExtension })
			},
		},
		postConversionSourceRule: {
			get() {
				return this.conversionBatch.postConversionSourceRule
			},
			set(postConversionSourceRule) {
				this.emitChange({ postConversionSourceRule })
			},
		},
		postConversionSourceRuleMoveFolder: {
			get() {
				return this.conversionBatch.postConversionSourceRuleMoveFolder
			},
			set(postConversionSourceRuleMoveFolder) {
				this.emitChange({ postConversionSourceRuleMoveFolder })
			},
		},
		postConversionOutputRule: {
			get() {
				return this.conversionBatch.postConversionOutputRule
			},
			set(postConversionOutputRule) {
				this.emitChange({ postConversionOutputRule })
			},
		},
		postConversionOutputRuleMoveFolder: {
			get() {
				return this.conversionBatch.postConversionOutputRuleMoveFolder
			},
			set(postConversionOutputRuleMoveFolder) {
				this.emitChange({ postConversionOutputRuleMoveFolder })
			},
		},
		postConversionOutputConflictRule: {
			get() {
				return this.conversionBatch.postConversionOutputConflictRule
			},
			set(postConversionOutputConflictRule) {
				this.emitChange({ postConversionOutputConflictRule })
			},
		},
		postConversionOutputConflictRuleMoveFolder: {
			get() {
				return this.conversionBatch.postConversionOutputConflictRuleMoveFolder
			},
			set(postConversionOutputConflictRuleMoveFolder) {
				this.emitChange({ postConversionOutputConflictRuleMoveFolder })
			},
		},
	},

	methods: {
		emitChange(mutation) {
			if (this.conversionBatch.id) {
				return
			}

			this.$emit('change', { ...this.conversionBatch, ...mutation })
		},

		async openFilePicker(directoryKey) {
			const filepicker = new FilePicker(
				'', // title
				false, // multiSelect,
				[], // mime type filter,
				true, // modal
				1, // file picker type (1-choose,2-move,3-copy,4-copymove)
				true, // directories allowed
			)

			this[directoryKey] = await filepicker.pick()
		},
	},
}
</script>

<style lang="scss">
.wmc-conversion-batch {
	.grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		grid-gap: 4em;
	}

	&__from-format {
		margin-top: 2em;
	}

	.mb {
		margin-bottom: 1.5em !important;
	}

	.mb-1 {
		display: inline-block;
		margin-bottom: 1em !important;
	}
}

</style>
