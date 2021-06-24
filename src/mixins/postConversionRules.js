export default {
	data: () => ({
		postConversionSourceRules: [
			{ id: 'keep', label: t('workflow_media_converter', 'Keep the source file') },
			{ id: 'delete', label: t('workflow_media_converter', 'Delete the source file') },
			{ id: 'move', label: t('workflow_media_converter', 'Move the source file to this folder') },
		],
		postConversionOutputRules: [
			{ id: 'keep', label: t('workflow_media_converter', 'Keep the output in the folder the source file was added to') },
			{ id: 'move', label: t('workflow_media_converter', 'Move the output to a specific folder') },
		],
		postConversionOutputConflictRules: [
			{ id: 'preserve', label: t('workflow_media_converter', 'Preserve the existing file and create a duplicate file') },
			{ id: 'overwrite', label: t('workflow_media_converter', 'Overwrite the existing file') },
			{ id: 'move', label: t('workflow_media_converter', 'Move the existing file to') },
		],
	}),
}
