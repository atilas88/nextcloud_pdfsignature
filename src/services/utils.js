export function base64Encode(bytes) {
	const binString = String.fromCodePoint(...new TextEncoder().encode(bytes))
	return btoa(binString)
}
