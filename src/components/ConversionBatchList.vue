<template>
	<div>
		<div v-if="!conversionBatches.length" class="alert">
			{{
				t(
					'workflow_media_converter',
					`Add batches to convert existing media outside of your flow.`
				)
			}}
		</div>
		<ul>
			<li v-for="(conversionBatch, i) in conversionBatches" :key="i">
				<ConversionBatch :conversion-batch="conversionBatch"
					:threads="threads"
					@change="$emit('change-conversion-batch', $event)"
					@remove="$emit('remove-conversion-batch', conversionBatch)"
					@save="$emit('save-conversion-batch')" />
			</li>
			<li class="add">
				<button @click="$emit('make-conversion-batch')">
					{{ t('workflow_media_converter', 'Add Conversion Batch') }}
				</button>
			</li>
		</ul>
	</div>
</template>

<script>
import ConversionBatch from './ConversionBatch.vue'

export default {
	components: { ConversionBatch },

	props: {
		conversionBatches: {
			required: true,
			type: Array,
		},
		threads: {
			required: true,
			type: Number,
		},
	},
}
</script>
