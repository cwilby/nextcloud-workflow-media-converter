import { generateUrl, getAppRootUrl } from '@nextcloud/router'

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
 * Calls @nextcloud/router/generateUrl with the app root url
 * @param {string} url The url to generate
 * @param {object} params The params to pass
 * @param {import('@nextcloud/router').UrlOptions} options The options to pass
 * @return {string}
 */
export function generateImageUrl(url, params, options) {
	return generateUrl(url, params, { ...options, baseURL: getAppRootUrl(APP_ID) })
}

/**
 * Calls @nextcloud/router/generateUrl with the app root url
 * @param {string} url The url to generate
 * @param {object} params The params to pass
 * @param {import('@nextcloud/router').UrlOptions} options The options to pass
 * @return {string}
 */
export function generateControllerUrl(url, params, options) {
	url = url.startsWith('/') ? url.slice(1) : url

	return generateUrl(`/apps/workflow_media_converter/${url}`, params, options)
}
