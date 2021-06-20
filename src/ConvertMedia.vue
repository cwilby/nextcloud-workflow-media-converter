<template>
	<Multiselect :value="currentValue"
		:options="options"
		track-by="id"
		label="text"
		@input="(newValue) => newValue !== null && $emit('input', newValue.id)" />
</template>

<script>
import { Multiselect } from '@nextcloud/vue/dist/Components/Multiselect'

export default {
	name: 'ConvertMedia',
	components: { Multiselect },
	props: ['value'],
	data() {
		return {
			  postConversionRules: [
                {
                    id: 'keep;preserve',
                    text: t('workflow_media_converter', 'Keep original, preserve existing output')
                },
                {
                    id: 'keep;overwrite',
                    text: t('workflow_media_converter', 'Keep original, overwrite existing output')
                },
                {
                    id: 'delete;preserve',
                    text: t('workflow_media_converter', 'Delete original, preserve existing output')
                },
                {
                    id: 'delete;overwrite',
                    text: t('workflow_media_converter', 'Delete original, overwrite existing output')
                }
            ]
		}
	},
	computed: {
		currentValue() {
			return this.postConversionRules.find(option => option.id === this.value) || this.postConversionRules[0];
		}
	}
}
</script>

<style scoped>
	.multiselect {
		width: 100%;
		margin: auto;
		text-align: center;
	}
</style>
