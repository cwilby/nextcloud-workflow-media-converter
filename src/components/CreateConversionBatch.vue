<template>
	<div>
		<div class="wmc-conversion-batch__delete-button">
			<Actions>
				<ActionButton icon="icon-delete" @click="$emit('remove')" />
			</Actions>
		</div>
		<div class="wmc-conversion-batch__source-directory">
			<span>{{ t('workflow_media_converter', `Convert all files in this folder`) }}</span>
			<div>
				<button @click="openFilePicker('sourceFolderPath')">
					Choose Folder
				</button>
				<span>{{ sourceFolderPath }}</span>
			</div>
			<CheckboxRadioSwitch :checked.sync="convertMediaInSubFolders">
				{{ t('workflow_media_converter', 'Convert media in sub-folders?') }}
			</CheckboxRadioSwitch>
		</div>
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
			<label>Convert them into this format</label>
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
		<p>{{ t('workflow_media_converter', 'Then after conversion') }}</p>
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
		<p>{{ t('workflow_media_converter', 'And') }}</p>
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
		<p>If the output file exists,</p>
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
		<button v-if="!conversionBatch.id" @click="$emit('save')">
			{{ t('workflow_media_converter', 'Save') }}
		</button>
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
		sourceFolderPath: {
			get() {
				return this.conversionBatch.sourceFolderPath
			},
			set(sourceFolderPath) {
				this.emitChange({ sourceFolderPath })
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
