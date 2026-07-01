// DOM helpers : element creation, HTML escaping, button enabling, modal cache.

const _modals = {};

/** Enable/disable a button by id. @param {string} id @param {boolean} enabled */
export function enable(id, enabled)
{
	document.getElementById(id).disabled = (! enabled);
}

/** Lazily build and cache a Bootstrap modal from its "<name>Modal" element. */
export function getModal(name)
{
	const element = document.getElementById(`${name}Modal`);
	if (element === null)
	{
		console.log(`${name}Modal element not found`)
		return null;
	}
	if (! _modals.hasOwnProperty(name))
		_modals[name] = new bootstrap.Modal(element);
	return _modals[name];
}

// Build a DOM element from a spec : {tag, content, attrs, events}.
// content may be a string, an Element, a spec, or an array of those.
export function el({tag, content = '', attrs = {}, events = {}})
{
	const element = document.createElement(tag);
	for (const [k, v] of Object.entries(attrs))
	{
		if (typeof v === 'boolean')
		{
			if (v) element.toggleAttribute(k);
		}
		else
			element.setAttribute(k, v);
	}
	if (typeof content === 'string')
		element.innerText = content;
	else if (content instanceof Element)
		element.appendChild(content);
	else if (Array.isArray(content))
		for (const child of content)
			element.appendChild(child instanceof Element ? child : el(child));
	else
		element.appendChild(el(content));
	for (const [event, handler] of Object.entries(events))
		element.addEventListener(event, handler);
	return element;
}
