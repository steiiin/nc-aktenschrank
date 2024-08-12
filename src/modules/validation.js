// validation.js

const XRegExp = require('xregexp')

// #region Input Cleaner

export const cleanFilename = (value) => {
	value = value.substring(0, 60)
	const pattern = XRegExp('[^\\p{L}\\p{N} \\-_()\\[\\]\\@$€§%°]+', 'g')
	return XRegExp.replace(value, pattern, '')
}

export const cleanLabel = (value) => {
	value = value.substring(0, 60)
	const dangerousCharsPattern = /[<>&"'`\\\\/\n\r\t^]/g
    const pattern = XRegExp('[^\\p{L}\\p{S}\\p{N}\\p{P}\\p{Z}]', 'gu')
	value = value.replace(dangerousCharsPattern, '')
	return XRegExp.replace(value, pattern, '')
}

export const cleanTag = (value) => {
	value = value.substring(0, 60)
	const pattern = XRegExp('[^\\p{L}\\p{N}]+', 'g')
	return XRegExp.replace(value, pattern, '')
}

export const cleanMail = (value) => {
	value = value.substring(0, 120)
	const pattern = /[^a-zA-Z0-9!#$%&'*+/=?^_`{|}~.@-]+/gu
	return value.replace(pattern, '')
}

export const cleanPhone = (value) => {
	return value.substring(0, 60).replaceAll(/[^0-9\-/\s+()]+/g, '')
}

export const cleanUrl = (value) => {
	value = value.substring(0, 120)
	const pattern = /^[a-zA-Z0-9-._~:/?#[\]@!$&'()*+,;=]+$/
	return value.replace(pattern, '')
}

// #endregion
// #region Input Validator

export const isValidFilename = (value) => {
	const cleanValue = cleanFilename(value)
  return cleanValue === value
}

export const isValidPathValue = (value) => {

  if (typeof value !== 'string') { return false }
  if (value[0] !== '/') { return false }

  const trimmed = value.replace(/^\/(?!\/)/, '').replace(/\/$/, '')
  const segments = trimmed.split('/')

  return segments.every(segment => isValidFilename(segment))

}

// #endregion
