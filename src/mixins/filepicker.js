import { FilePicker } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/style.css'

export default {
	methods: {
		async openFilePicker(directoryKey) {
			const filepicker = new FilePicker(
				'', // title
				false, // multiSelect,
				[], // mime type filter,
				true, // modal
				1, // file picker type (1-choose,2-move,3-copy,4-copymove)
				true, // directories allowed
			)

			this[directoryKey] = await filepicker.pick()
		},
	},
}
