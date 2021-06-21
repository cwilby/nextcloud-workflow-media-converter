<template>
	<div class="wmc-rules">
		<p>Convert into format</p>
		<select id="wmc-rules-output-formats">
			<option v-for="format in formats" :key="format.extension" :value="format.extension">
				(.{{ format.extension }}) {{ format.label }}
			</option>
		</select>
		<p>Then</p>
		<select id="wmc-rules-post-source">
			<option v-for="option in sourceOptions" :key="option.id" :value="option.id">
				{{ option.label }}
			</option>
		</select>
		<p>and</p>
		<select id="wmc-rules-post-source">
			<option v-for="option in outputOptions" :key="option.id" :value="option.id">
				{{ option.label }}
			</option>
		</select>
	</div>
</template>

<script>
import formats from '../../formats.js'

export default {
	name: 'ConvertMedia',

	props: {
		value: {
			default: '{}',
			type: String,
		},
	},

	data: () => ({
		formats,
		sourceOptions: [
			{ id: 'keep', label: 'Keep source file' },
			{ id: 'delete', label: 'Delete source file' },
		],
		outputOptions: [
			{ id: 'preserve', label: 'Preserve existing output' },
			{ id: 'overwrite', label: 'Overwrite existing output' },
		],
	}),

	computed: {
		config: {
			get() {
				return JSON.parse(this.value)
			},
			set(value) {
				this.$emit('input', JSON.stringify(value))
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
