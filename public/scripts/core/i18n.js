// Translation lookup. Loaded once at startup via setTranslations().

/** @type {Record<string,string>} */
let _translations = {};

/** @param {Record<string,string>} translations */
export function setTranslations(translations)
{
	_translations = translations ?? {};
}

/** @param {string} key @returns {string} */
export function t(key)
{
	return _translations[key] ?? key;
}
