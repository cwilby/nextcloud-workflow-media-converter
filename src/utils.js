import { generateUrl as nextcloudGenerateUrl, getAppRootUrl } from '@nextcloud/router'

export const APP_ID = 'workflow_media_converter'

/**
 * Gets a UUID
 *
 * @param {number} length - The length of the UUID (32 by default)
 * @return {string}
 */
export function getUniqueId(length = 32) {
	const result = []
	const characters
		= 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
	const charactersLength = characters.length
	for (let i = 0; i < length; i++) {
		result.push(characters.charAt(Math.floor(Math.random() * charactersLength)))
	}
	return result.join('')
}

/**
 * Generates a URL for the app
 *
 * @param {string} url - The URL
 * @param {object} params - The parameters
 * @param {object} options - The options
 * @return {string}
 */
export function generateUrl(url, params, options) {
	return nextcloudGenerateUrl(url, params, options)
}
