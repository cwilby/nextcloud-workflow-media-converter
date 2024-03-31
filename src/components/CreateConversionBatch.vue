<template>
	<div>
		<div class="wmc-conversion-batch__delete-button">
			<NcActions>
				<NcActionButton icon="icon-delete" @click="$emit('remove')" />
			</NcActions>
		</div>
		<div class="grid">
			<div class="column">
				<div class="wmc-conversion-batch__source-directory">
					<span>{{
						t('workflow_media_converter', `Convert all files in this folder`)
					}}</span>
					<div>
						<div class="wmc-conversion-batch__source-directory--picker">
							<button @click="openFilePicker('sourceFolder')">
								{{ t('workflow_media_converter', 'Choose Folder') }}
							</button>
							<NcCheckboxRadioSwitch :checked.sync="convertMediaInSubFolders">
								{{ t('workflow_media_converter', 'Convert media in sub-folders') }}
							</NcCheckboxRadioSwitch>
						</div>
						<span>{{ sourceFolder }}</span>
					</div>
				</div>
				<div class="wmc-conversion-batch__from-format">
					<label>{{
						t(
							'workflow_media_converter',
							'Find source files with this extension/format'
						)
					}}</label>
					<select v-model="sourceExtension"
						class="wmc-conversion-batch__from-format-picker">
						<option value="" />
						<option v-for="(format, index) in formats"
							:key="`format-from-${index}`"
							:value="format.extension">
							<span>.{{ format.extension }} ({{ format.label }})</span>
						</option>
					</select>
				</div>
				<div class="wmc-conversion-batch__to-format">
					<label>{{
						t(
							'workflow_media_converter',
							'Store converted output in this extension/format'
						)
					}}</label>
					<select v-model="outputExtension"
						class="wmc-conversion-batch__to-format-picker">
						<option value="" />
						<option v-for="(format, index) in formats"
							:key="`format-to-${index}`"
							:value="format.extension">
							<span>.{{ format.extension }} ({{ format.label }})</span>
						</option>
					</select>
				</div>
			</div>
			<div class="column">
				<PostConversionRules v-model="postConversionRules" />
			</div>
		</div>
		<div class="wmc-conversion-batch__FFmpeg">
			<label>{{ t('workflow_media_converter', 'Additional FFmpeg flags') }}</label>
			<div class="grid">
				<div class="column">
					<label><small>{{ t('workflow_media_converter', 'Input flags') }}</small></label>
					<input v-model="additionalInputConversionFlags" type="text" style="margin-bottom: 0.5em">
				</div>
				<div class="column">
					<label><small>{{ t('workflow_media_converter', 'Output flags') }}</small></label>
					<input v-model="additionalOutputConversionFlags" type="text" style="margin-bottom: 0.5em">
				</div>
			</div>
			<input type="text"
				:value="commandString"
				style="background-color: #eee; color: #000"
				disabled>
		</div>
		<div class="wmc-conversion-batch__actions">
			<button v-if="!conversionBatch.id" class="save" @click="$emit('save')">
				{{ t('workflow_media_converter', 'Save') }}
			</button>
		</div>
	</div>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import formats from '../constants/formats.js'
import filepicker from '../mixins/filepicker.js'
import PostConversionRules from './PostConversionRules.vue'

export default {
	components: {
		NcActions,
		NcActionButton,
		NcCheckboxRadioSwitch,
		PostConversionRules,
	},

	mixins: [filepicker],

	props: {
		conversionBatch: {
			required: true,
			type: Object,
		},
		threads: {
			required: true,
			type: Number,
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
				this.commit({ sourceFolder })
			},
		},
		convertMediaInSubFolders: {
			get() {
				return this.conversionBatch.convertMediaInSubFolders
			},
			set(convertMediaInSubFolders) {
				this.commit({ convertMediaInSubFolders })
			},
		},
		sourceExtension: {
			get() {
				return this.conversionBatch.sourceExtension
			},
			set(sourceExtension) {
				this.commit({ sourceExtension })
			},
		},
		outputExtension: {
			get() {
				return this.conversionBatch.outputExtension
			},
			set(outputExtension) {
				this.commit({ outputExtension })
			},
		},
		postConversionRules: {
			get() {
				return this.conversionBatch
			},
			set(change) {
				this.commit(change)
			},
		},
		additionalInputConversionFlags: {
			get() {
				return this.conversionBatch.additionalInputConversionFlags
			},
			set(additionalInputConversionFlags) {
				this.commit({ additionalInputConversionFlags })
			},
		},
		additionalOutputConversionFlags: {
			get() {
				return this.conversionBatch.additionalOutputConversionFlags
			},
			set(additionalOutputConversionFlags) {
				this.commit({ additionalOutputConversionFlags })
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

	methods: {
		commit(mutation) {
			if (this.conversionBatch.id) {
				return
			}

			this.$emit('change', { ...this.conversionBatch, ...mutation })
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

	&__source-directory {
		&--picker {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 1em;
		}
	}

	&__FFmpeg {
		textarea {
			width: 100%;
			height: 10em;
		}
	}
}
</style>
