<template>
	<div class="wmc-rules">
		<p>Convert to format</p>
		<select v-model="outputExtension">
			<option v-for="format in formats" :key="format.extension" :value="format.extension">
				(.{{ format.extension }}) {{ format.label }}
			</option>
		</select>
		<p>Then</p>
		<select v-model="postConversionSourceRule">
			<option v-for="option in postConversionSourceRules" :key="option.id" :value="option.id">
				{{ option.label }}
			</option>
		</select>
		<div v-if="postConversionSourceRule === 'move'">
			<button @click="openFilePicker('postConversionSourceRuleMoveFolder')">
				Choose Folder
			</button>
			<span>{{ postConversionSourceRuleMoveFolder }}</span>
		</div>
		<p>Once converted</p>
		<select v-model="postConversionOutputRule">
			<option v-for="option in postConversionOutputRules" :key="option.id" :value="option.id">
				{{ option.label }}
			</option>
		</select>
		<div v-if="postConversionOutputRule === 'move'">
			<button @click="openFilePicker('postConversionOutputRuleMoveFolder')">
				Choose Folder
			</button>
			<span>{{ postConversionOutputRuleMoveFolder }}</span>
		</div>
		<p>If there are conflicts,</p>
		<select v-model="postConversionOutputConflictRule">
			<option v-for="option in postConversionOutputConflictRules" :key="option.id" :value="option.id">
				{{ option.label }}
			</option>
		</select>
		<div v-if="postConversionOutputConflictRule === 'move'">
			<button @click="openFilePicker('postConversionOutputConflictRuleMoveFolder')">
				Choose Folder
			</button>
			<span>{{ postConversionOutputConflictRuleMoveFolder }}</span>
		</div>
	</div>
</template>

<script>
import { FilePicker } from '@nextcloud/dialogs'
import formats from '../../formats.js'

const defaultState = {
	outputExtension: null,
	postConversionSourceRule: 'keep',
	postConversionSourceRuleMoveFolder: null,
	postConversionOutputRule: 'keep',
	postConversionOutputRuleMoveFolder: null,
	postConversionOutputConflictRule: 'preserve',
	postConversionOutputConflictRuleMoveFolder: null,
}

export default {
	name: 'ConvertMediaOperation',

	props: {
		value: {
			default: null,
			type: String,
		},
	},

	data: () => ({
		formats,
		postConversionSourceRules: [
			{ id: 'keep', label: 'Keep the source file' },
			{ id: 'delete', label: 'Delete the source file' },
			{ id: 'move', label: 'Move the source file to this folder' },
		],
		postConversionOutputRules: [
			{ id: 'keep', label: 'Keep the output in the folder the source file was added to' },
			{ id: 'move', label: 'Move the output to a specific folder' },
		],
		postConversionOutputConflictRules: [
			{ id: 'preserve', label: 'Preserve the existing file and create a duplicate file' },
			{ id: 'overwrite', label: 'Overwrite the existing file' },
			{ id: 'move', label: 'Move the existing file to' },
		],
	}),

	computed: {
		config: {
			get() {
				try {
					return JSON.parse(this.value || JSON.stringify(defaultState))
				} catch {
					return defaultState
				}
			},
			set(value) {
				this.$emit('input', JSON.stringify(value || {}))
			},
		},

		outputExtension: {
			get() {
				return this.config.outputExtension
			},
			set(outputExtension) {
				this.config = { ...this.config, outputExtension }
			},
		},

		postConversionSourceRule: {
			get() {
				return this.config.postConversionSourceRule
			},
			set(postConversionSourceRule) {
				this.config = { ...this.config, postConversionSourceRule }
			},
		},

		postConversionSourceRuleMoveFolder: {
			get() {
				return this.config.postConversionSourceRuleMoveFolder
			},
			set(postConversionSourceRuleMoveFolder) {
				this.config = { ...this.config, postConversionSourceRuleMoveFolder }
			},
		},

		postConversionOutputRule: {
			get() {
				return this.config.postConversionOutputRule
			},
			set(postConversionOutputRule) {
				this.config = { ...this.config, postConversionOutputRule }
			},
		},

		postConversionOutputRuleMoveFolder: {
			get() {
				return this.config.postConversionOutputRuleMoveFolder
			},
			set(postConversionOutputRuleMoveFolder) {
				this.config = { ...this.config, postConversionOutputRuleMoveFolder }
			},
		},

		postConversionOutputConflictRule: {
			get() {
				return this.config.postConversionOutputConflictRule
			},
			set(postConversionOutputConflictRule) {
				this.config = { ...this.config, postConversionOutputConflictRule }
			},
		},

		postConversionOutputConflictRuleMoveFolder: {
			get() {
				return this.config.postConversionOutputConflictRuleMoveFolder
			},
			set(postConversionOutputConflictRuleMoveFolder) {
				this.config = { ...this.config, postConversionOutputConflictRuleMoveFolder }
			},
		},
	},

	methods: {
		async openFilePicker(folderKey) {
			const filepicker = new FilePicker(
				'', // title
				false, // multiSelect,
				[], // mime type filter,
				true, // modal
				1, // file picker type (1-choose,2-move,3-copy,4-copymove)
				true, // directories allowed
			)
			this[folderKey] = await filepicker.pick()
		},
	},
}
</script>

<style scoped>
	.multiselect {
		width: 100%;
		margin: auto;
		text-align: center;
	}
</style>
