import { FilePickerType, getFilePickerBuilder } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/style.css'

export default {
	methods: {
		async openFilePicker(directoryKey) {
			const filepicker = getFilePickerBuilder('Pick a file')
				.setMultiSelect(false)
				.setMimeTypeFilter([])
				.setType(FilePickerType.Choose)
				.allowDirectories(true)
				.build()

			this[directoryKey] = await filepicker.pick()
		},
	},
}
