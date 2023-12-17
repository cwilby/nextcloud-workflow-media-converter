<template>
	<div class="wmc-conversion-batch">
		<div v-if="conversionBatch.id">
			<DisplayConversionBatch :conversion-batch="conversionBatch" :threads="threads"
				@remove="$emit('remove', conversionBatch)" />
		</div>
		<div v-else>
			<CreateConversionBatch :conversion-batch="conversionBatch" :threads="threads"
				@remove="$emit('remove', conversionBatch)"
				@change="$emit('change', $event)"
				@save="$emit('save')" />
		</div>
	</div>
</template>

<script>
import DisplayConversionBatch from './DisplayConversionBatch.vue'
import CreateConversionBatch from './CreateConversionBatch.vue'

export default {
	components: { DisplayConversionBatch, CreateConversionBatch },

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
}
</script>

<style lang="scss">
.wmc-conversion-batch {
	position: relative;
	padding: 2em;
	border: 1px solid #ededed;
	border-radius: 0.5em;
	margin-bottom: 1em;

	label {
		display: block;
	}

	small {
		display: block;
		a {
			font-weight: 500;
		}
	}

	select,
	input {
		width: 100%;
		margin-bottom: 2em;
	}

	&__delete-button {
		position: absolute;
		top: 0.5em;
		right: 1em;
	}

	&__source-directory {
		width: 100%;
		margin-bottom: 2em;
		div {
			button {
				height: 2em;
				width: 12em;
			}
		}
	}

	&__formats {
		display: grid;
		grid-template-columns: 1fr 1fr;
		grid-template-rows: 1fr;
		gap: 0 1em;
	}

	&__encoder-settings {
		&-body {
			padding: 1em;
			background-color: #fdfdff;
			border: 1px dashed #ededed;
			margin-bottom: 2em;
		}
	}

	&__post-encode-rule {
		label {
			display: block;
		}

		&--move {
			width: 100%;
			div {
				button {
					height: 2em;
					width: 12em;
				}
			}
		}
	}
}
</style>
