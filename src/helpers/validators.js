/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const regexRegex = /^\/(.*)\/([gui]{0,3})$/

const validateRegex = function(string) {
	if (!string) {
		return false
	}
	return regexRegex.exec(string) !== null
}

const stringValidator = (check) => {
	if (check.operator === 'matches' || check.operator === '!matches') {
		return validateRegex(check.value)
	}
	return true
}

export { validateRegex, stringValidator }
