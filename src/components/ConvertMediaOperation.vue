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
			<label><strong>{{ t('workflow_media_converter', 'Additional FFmpeg flags') }}</strong></label>
			<div class="grid">
				<div class="column">
					<label>{{ t('workflow_media_converter', 'Input flags') }}</label>
					<input v-model="additionalInputConversionFlags" type="text">
				</div>
				<div class="column">
					<label>{{ t('workflow_media_converter', 'Output flags') }}</label>
					<input v-model="additionalOutputConversionFlags" type="text">
				</div>
			</div>
			<input type="text"
				:value="commandString"
				style="background-color: #eee; color: #000"
				disabled>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateControllerUrl } from '../utils.js'
import formats from '../constants/formats.js'
import filepicker from '../mixins/filepicker.js'
import PostConversionRules from './PostConversionRules.vue'

const defaultState = {
	outputExtension: null,
	additionalInputConversionFlags: '',
	additionalOutputConversionFlags: '',
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

		additionalInputConversionFlags: {
			get() {
				return this.config.additionalInputConversionFlags
			},
			set(additionalInputConversionFlags) {
				this.config = { additionalInputConversionFlags }
			},
		},

		additionalOutputConversionFlags: {
			get() {
				return this.config.additionalOutputConversionFlags
			},
			set(additionalOutputConversionFlags) {
				this.config = { additionalOutputConversionFlags }
			},
		},

		commandString() {
			return [
				'ffmpeg',
				parseInt(this.threads) !== 0 ? `-threads ${this.threads}` : '',
				this.additionalInputConversionFlags ? `${this.additionalInputConversionFlags}` : '',
				'-i {input}',
				this.additionalOutputConversionFlags ? `${this.additionalOutputConversionFlags}` : '',
				'{output}',
			].filter(Boolean).join(' ')
		},
	},

	async mounted() {
		const { data } = await axios.get(generateControllerUrl('admin-settings'))

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
