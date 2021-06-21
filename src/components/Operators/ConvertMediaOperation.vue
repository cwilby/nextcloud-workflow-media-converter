<template>
	<div class="wmc-rules">
		<p>Convert into format</p>
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
		<p>and</p>
		<select v-model="postConversionOutputRule">
			<option v-for="option in postConversionOutputRules" :key="option.id" :value="option.id">
				{{ option.label }}
			</option>
		</select>
	</div>
</template>

<script>
import formats from '../../formats.js'

const defaultState = {
	outputExtension: null,
	postConversionSourceRule: null,
	postConversionOutputRule: null,
}

export default {
	name: 'ConvertMediaOperation',

	props: {
		value: {
			default: '{}',
			type: String,
		},
	},

	data: () => ({
		formats,
		postConversionSourceRules: [
			{ id: 'keep', label: 'Keep source file' },
			{ id: 'delete', label: 'Delete source file' },
		],
		postConversionOutputRules: [
			{ id: 'preserve', label: 'Preserve existing output' },
			{ id: 'overwrite', label: 'Overwrite existing output' },
		],
	}),

	computed: {
		config: {
			get() {
				return JSON.parse(this.value || JSON.stringify(defaultState))
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

		postConversionOutputRule: {
			get() {
				return this.config.postConversionOutputRule
			},
			set(postConversionOutputRule) {
				this.config = { ...this.config, postConversionOutputRule }
			},
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
