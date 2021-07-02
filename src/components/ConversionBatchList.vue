<template>
	<div>
		<div v-if="!conversionBatches.length" class="alert">
			{{ t('workflow_media_converter', `Add batches to convert existing media outside of your flow.`) }}
		</div>
		<ul>
			<li v-for="(conversionBatch, i) in conversionBatches" :key="i">
				<ConversionBatch
					:conversion-batch="conversionBatch"
					@change="$emit('changeConversionBatch', $event)"
					@remove="$emit('removeConversionBatch', conversionBatch)"
					@save="$emit('saveConversionBatch')" />
			</li>
			<li class="add">
				<button @click="$emit('makeConversionBatch')">
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
	},
}
</script>
