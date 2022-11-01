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
