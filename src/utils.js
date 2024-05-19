import { generateUrl, getAppRootUrl } from '@nextcloud/router';

export const APP_ID = 'workflow_media_converter';

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

export function generateImageUrl(url, params, options) {
	return generateUrl(url, params, { ...options, baseURL: getAppRootUrl(APP_ID) });
}

export function generateControllerUrl(url, params, options) {
	url = url.startsWith('/') ? url.slice(1) : url;

	return generateUrl(`/apps/workflow_media_converter/${url}`, params, options);
}
