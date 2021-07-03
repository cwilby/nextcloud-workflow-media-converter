<template>
	<div class="wmc-rules">
		<div class="mb">
			<p>{{ t('workflow_media_converter', 'Convert to format') }}</p>
			<select v-model="outputExtension">
				<option v-for="format in formats" :key="format.extension" :value="format.extension">
					(.{{ format.extension }}) {{ format.label }}
				</option>
			</select>
		</div>
		<PostConversionRules v-model="config" />
	</div>
</template>

<script>
import formats from '../constants/formats.js'
import filepicker from '../mixins/filepicker'
import PostConversionRules from './PostConversionRules.vue'

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

	components: { PostConversionRules },

	mixins: [filepicker],

	props: {
		value: {
			default: null,
			type: String,
		},
	},

	data: () => ({
		formats,
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
			set(mutation) {
				this.$emit('input', JSON.stringify({ ...(this.config || {}), ...mutation }))
			},
		},

		outputExtension: {
			get() {
				return this.config.outputExtension
			},
			set(outputExtension) {
				this.config = { outputExtension }
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

	select {
		width: 100%;
	}

	.mb {
		margin-bottom: 1.5em;
	}
</style>
