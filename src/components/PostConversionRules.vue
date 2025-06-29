<template>
	<div>
		<div>
			<label>
				{{
					t(
						'workflow_media_converter',
						'After the source file has been converted:'
					)
				}}
			</label>
			<select v-model="postConversionSourceRule">
				<option v-for="option in postConversionSourceRules"
					:key="option.id"
					:value="option.id">
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
		<div>
			<label>{{ t('workflow_media_converter', 'After the output is ready:') }}</label>
			<select v-model="postConversionOutputRule">
				<option v-for="option in postConversionOutputRules"
					:key="option.id"
					:value="option.id">
					{{ option.label }}
				</option>
			</select>
			<div v-if="postConversionOutputRule === 'move'" class="mb-2">
				<button @click="openFilePicker('postConversionOutputRuleMoveFolder')">
					{{ t('workflow_media_converter', 'Choose Folder') }}
				</button>
				<span>{{ postConversionOutputRuleMoveFolder }}</span>
			</div>
		</div>
		<div>
			<label>{{ t('workflow_media_converter', 'If the output file exists:') }}</label>
			<select v-model="postConversionOutputConflictRule">
				<option v-for="option in postConversionOutputConflictRules"
					:key="option.id"
					:value="option.id">
					{{ option.label }}
				</option>
			</select>
			<small v-if="postConversionOutputConflictRule === 'move'">
				{{
					t(
						'workflow_media_converter',
						'Note: a new file will be added in this folder with a number added to its name if there is already a conflict file.'
					)
				}}
			</small>
			<div v-if="postConversionOutputConflictRule === 'move'" class="mb-2">
				<button @click="openFilePicker('postConversionOutputConflictRuleMoveFolder')">
					{{ t('workflow_media_converter', 'Choose Folder') }}
				</button>
				<span>{{ postConversionOutputConflictRuleMoveFolder }}</span>
			</div>
		</div>
		<div>
			<label>
				{{ t('workflow_media_converter', 'Set file timestamp after conversion:') }}
			</label>
			<select v-model="postConversionTimestampRule">
				<option value="conversionTime">
					{{ t('workflow_media_converter', 'Use conversion time') }}
				</option>
				<option value="preserveSource">
					{{ t('workflow_media_converter', 'Use source creation time') }}
				</option>
			</select>
		</div>
	</div>
</template>

<script>
import filepicker from '../mixins/filepicker.js'

export default {
	mixins: [filepicker],

	props: {
		value: {
			default: null,
			type: Object,
		},
	},

	data: () => ({
		postConversionSourceRules: [
			{ id: 'keep', label: t('workflow_media_converter', 'Keep source file') },
			{
				id: 'delete',
				label: t('workflow_media_converter', 'Delete source file'),
			},
			{
				id: 'move',
				label: t(
					'workflow_media_converter',
					'Move source file to specific folder',
				),
			},
		],
		postConversionOutputRules: [
			{
				id: 'keep',
				label: t('workflow_media_converter', 'Store output in source folder'),
			},
			{
				id: 'move',
				label: t('workflow_media_converter', 'Move output to specific folder'),
			},
		],
		postConversionOutputConflictRules: [
			{
				id: 'preserve',
				label: t(
					'workflow_media_converter',
					'Add new file with a number added to its name',
				),
			},
			{
				id: 'overwrite',
				label: t('workflow_media_converter', 'Overwrite existing file'),
			},
			{
				id: 'move',
				label: t(
					'workflow_media_converter',
					'Move existing file to specific folder',
				),
			},
		],
	}),

	computed: {
		postConversionSourceRule: {
			get() {
				return this.value.postConversionSourceRule
			},
			set(postConversionSourceRule) {
				this.$emit('input', { postConversionSourceRule })
			},
		},

		postConversionSourceRuleMoveFolder: {
			get() {
				return this.value.postConversionSourceRuleMoveFolder
			},
			set(postConversionSourceRuleMoveFolder) {
				this.$emit('input', { postConversionSourceRuleMoveFolder })
			},
		},

		postConversionOutputRule: {
			get() {
				return this.value.postConversionOutputRule
			},
			set(postConversionOutputRule) {
				this.$emit('input', { postConversionOutputRule })
			},
		},

		postConversionOutputRuleMoveFolder: {
			get() {
				return this.value.postConversionOutputRuleMoveFolder
			},
			set(postConversionOutputRuleMoveFolder) {
				this.$emit('input', { postConversionOutputRuleMoveFolder })
			},
		},

		postConversionOutputConflictRule: {
			get() {
				return this.value.postConversionOutputConflictRule
			},
			set(postConversionOutputConflictRule) {
				this.$emit('input', { postConversionOutputConflictRule })
			},
		},

		postConversionOutputConflictRuleMoveFolder: {
			get() {
				return this.value.postConversionOutputConflictRuleMoveFolder
			},
			set(postConversionOutputConflictRuleMoveFolder) {
				this.$emit('input', { postConversionOutputConflictRuleMoveFolder })
			},
		},

		postConversionTimestampRule: {
			get() {
				return this.value.postConversionTimestampRule
			},
			set(postConversionTimestampRule) {
				this.$emit('input', { postConversionTimestampRule })
			},
		}
	},
}
</script>

<style lang="scss">
.mb {
	margin-bottom: 1em !important;
}

.mb-1 {
	display: inline-block;
	margin-bottom: 1em !important;
}
</style>
