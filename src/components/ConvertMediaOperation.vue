<template>
	<div class="wmc-rules">
		<div class="mb">
			<p>{{ t('workflow_media_converter', 'Convert to format') }}</p>
			<select v-model="outputExtension">
				<option v-for="format in formats"
					:key="format.extension"
					:value="format.extension">
					(.{{ format.extension }}) {{ format.label }}
				</option>
			</select>
		</div>
		<PostConversionRules v-model="config" />
		<div class="wmc-conversion-batch__FFmpeg">
			<label>{{ t('workflow_media_converter', 'Additional FFmpeg flags') }}</label>
			<div><input v-model="additionalConversionFlags" type="text"></div>
		</div>
		<div><input type="text" :value="commandString" style="background-color: #eee"></div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '../utils';
import formats from '../constants/formats.js'
import filepicker from '../mixins/filepicker.js'
import PostConversionRules from './PostConversionRules.vue'

const defaultState = {
	outputExtension: null,
	additionalConversionFlags: '',
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
		threads: 0,
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
				this.$emit(
					'input',
					JSON.stringify({ ...(this.config || {}), ...mutation }),
				)
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

		additionalConversionFlags: {
			get() {
				return this.config.additionalConversionFlags
			},
			set(additionalConversionFlags) {
				this.config = { additionalConversionFlags }
			},
		},

		commandString() {
			return [
				'ffmpeg',
				parseInt(this.threads) !== 0 ? `-threads ${this.threads}` : '',
				this.additionalConversionFlags ? `${this.additionalConversionFlags}` : '',
				'-i {input}',
				'{output}',
			].filter(Boolean).join(' ')
		},
	},

	async mounted() {
		const { data } = await axios.get(generateUrl('admin-settings'))

		this.threads = data.threadLimit
	},
}
</script>

<style lang="scss">
.wmc-rules {
	.multiselect {
		width: 100%;
		margin: auto;
		text-align: center;
	}

	input, select {
		width: 100%;
	}

	.mb {
		margin-bottom: 1.5em;
	}

}
</style>
