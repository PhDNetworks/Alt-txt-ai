/**
 * Alt Text AI — Cloudflare Worker API
 *
 * Endpoints:
 *   POST /generate  — Generate alt text for an image
 *   GET  /usage     — Return current usage for a license key
 *
 * Bindings:
 *   KV: ALT_TEXT_USAGE  — Monthly usage counters per license
 *   Secret: OPENAI_API_KEY
 */

const SYSTEM_PROMPT = `You are an SEO specialist generating alt text for website images.

Rules:
- Maximum 125 characters
- Describe what's visually in the image, not what you assume
- Include relevant context if provided (industry, location)
- No phrases like "image of" or "picture showing"
- No keyword stuffing
- Be specific: "red brick house" not "building"
- If people are present, describe actions not identities
- For products, include key features visible

Output ONLY the alt text, no explanation.`;

/** Pricing tiers: name → monthly image limit */
const TIERS = {
	trial:   { limit: 25,   label: 'Trial' },
	starter: { limit: 100,  label: 'Starter' },
	pro:     { limit: 500,  label: 'Pro' },
	agency:  { limit: 2000, label: 'Agency' },
};

/* ------------------------------------------------------------------ */
/*  Helpers                                                           */
/* ------------------------------------------------------------------ */

/** Return a YYYY-MM key for the current billing period. */
function monthKey() {
	const d = new Date();
	return `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, '0')}`;
}

/** First day of next month (UTC) as ISO string — used for "resets" field. */
function nextReset() {
	const d = new Date();
	d.setUTCMonth(d.getUTCMonth() + 1, 1);
	d.setUTCHours(0, 0, 0, 0);
	return d.toISOString();
}

/** Build a KV key scoped to license + month. */
function usageKey(licenseKey) {
	return `usage:${licenseKey}:${monthKey()}`;
}

/** JSON response helper. */
function json(body, status = 200) {
	return new Response(JSON.stringify(body), {
		status,
		headers: {
			'Content-Type': 'application/json',
			'Access-Control-Allow-Origin': '*',
		},
	});
}

/** Validate a license key and return its tier. In production this would
 *  call Freemius or a DB — for now we accept any non-empty key and
 *  default to "trial". Replace this stub with real validation. */
async function validateLicense(licenseKey, kv) {
	if (!licenseKey || typeof licenseKey !== 'string' || licenseKey.length < 4) {
		return null;
	}

	// Check if we have stored tier info for this key
	const tierData = await kv.get(`tier:${licenseKey}`);
	if (tierData) {
		return JSON.parse(tierData);
	}

	// Default: treat as trial
	return { tier: 'trial', ...TIERS.trial };
}

/** Get current usage count for a license this month. */
async function getUsage(licenseKey, kv) {
	const raw = await kv.get(usageKey(licenseKey));
	return raw ? parseInt(raw, 10) : 0;
}

/** Increment usage by 1. Returns new count. */
async function incrementUsage(licenseKey, kv) {
	const key = usageKey(licenseKey);
	const current = await getUsage(licenseKey, kv);
	const next = current + 1;
	// Expire at end of month + 7 days buffer
	await kv.put(key, String(next), { expirationTtl: 40 * 86400 });
	return next;
}

/* ------------------------------------------------------------------ */
/*  OpenAI Vision Call                                                */
/* ------------------------------------------------------------------ */

async function generateAltText(base64Image, context, apiKey) {
	let userPrompt = 'Generate alt text for this image.';
	const parts = [];
	if (context.filename) parts.push(`Filename: ${context.filename}`);
	if (context.industry) parts.push(`Industry: ${context.industry}`);
	if (context.location) parts.push(`Location: ${context.location}`);
	if (parts.length) {
		userPrompt += '\n\nContext:\n' + parts.join('\n');
	}

	// Determine mime type (default jpeg)
	let mimeType = 'image/jpeg';
	if (base64Image.startsWith('data:')) {
		// Already has data URI prefix — extract it
		const match = base64Image.match(/^data:(image\/\w+);base64,/);
		if (match) mimeType = match[1];
		base64Image = base64Image.replace(/^data:image\/\w+;base64,/, '');
	}

	const response = await fetch('https://api.openai.com/v1/chat/completions', {
		method: 'POST',
		headers: {
			'Authorization': `Bearer ${apiKey}`,
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({
			model: 'gpt-4o-mini',
			max_tokens: 100,
			messages: [
				{ role: 'system', content: SYSTEM_PROMPT },
				{
					role: 'user',
					content: [
						{ type: 'text', text: userPrompt },
						{
							type: 'image_url',
							image_url: {
								url: `data:${mimeType};base64,${base64Image}`,
								detail: 'low',
							},
						},
					],
				},
			],
		}),
	});

	if (!response.ok) {
		const err = await response.text();
		throw new Error(`OpenAI API error ${response.status}: ${err}`);
	}

	const data = await response.json();
	let altText = data.choices[0].message.content.trim();

	// Enforce 125 char limit
	if (altText.length > 125) {
		altText = altText.substring(0, 122) + '...';
	}

	return altText;
}

/* ------------------------------------------------------------------ */
/*  Route Handlers                                                    */
/* ------------------------------------------------------------------ */

async function handleGenerate(request, env) {
	if (request.method !== 'POST') {
		return json({ success: false, error: 'Method not allowed' }, 405);
	}

	let body;
	try {
		body = await request.json();
	} catch {
		return json({ success: false, error: 'Invalid JSON body' }, 400);
	}

	const { license_key, image, context = {} } = body;

	if (!license_key) {
		return json({ success: false, error: 'Missing license_key' }, 401);
	}
	if (!image) {
		return json({ success: false, error: 'Missing image data' }, 400);
	}

	// Validate license
	const license = await validateLicense(license_key, env.ALT_TEXT_USAGE);
	if (!license) {
		return json({ success: false, error: 'Invalid license key' }, 401);
	}

	// Check quota
	const used = await getUsage(license_key, env.ALT_TEXT_USAGE);
	if (used >= license.limit) {
		return json({
			success: false,
			error: 'Monthly quota exceeded',
			usage: { used, limit: license.limit, resets: nextReset() },
		}, 402);
	}

	// Generate alt text
	let altText;
	try {
		altText = await generateAltText(image, context, env.OPENAI_API_KEY);
	} catch (err) {
		return json({ success: false, error: `Generation failed: ${err.message}` }, 500);
	}

	// Increment usage
	const newUsed = await incrementUsage(license_key, env.ALT_TEXT_USAGE);

	return json({
		success: true,
		alt_text: altText,
		usage: {
			used: newUsed,
			limit: license.limit,
			resets: nextReset(),
		},
	});
}

async function handleUsage(request, env) {
	const url = new URL(request.url);
	const licenseKey = url.searchParams.get('license_key');

	if (!licenseKey) {
		return json({ success: false, error: 'Missing license_key' }, 401);
	}

	const license = await validateLicense(licenseKey, env.ALT_TEXT_USAGE);
	if (!license) {
		return json({ success: false, error: 'Invalid license key' }, 401);
	}

	const used = await getUsage(licenseKey, env.ALT_TEXT_USAGE);

	return json({
		used,
		limit: license.limit,
		tier: license.tier,
		resets: nextReset(),
	});
}

/* ------------------------------------------------------------------ */
/*  Router                                                            */
/* ------------------------------------------------------------------ */

export default {
	async fetch(request, env) {
		// Handle CORS preflight
		if (request.method === 'OPTIONS') {
			return new Response(null, {
				headers: {
					'Access-Control-Allow-Origin': '*',
					'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
					'Access-Control-Allow-Headers': 'Content-Type',
				},
			});
		}

		const url = new URL(request.url);
		const path = url.pathname;

		if (path === '/generate') return handleGenerate(request, env);
		if (path === '/usage') return handleUsage(request, env);

		return json({ error: 'Not found' }, 404);
	},
};
