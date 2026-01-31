# Alt Text AI

WordPress plugin + Cloudflare Worker API for AI-powered image alt text generation.

## Structure

```
plugin/     — WordPress plugin (PHP/JS, no build tools)
worker/     — Cloudflare Worker API (Node.js)
```

## Quick Start

### 1. Deploy the Worker

```bash
cd worker
npm install
wrangler login
wrangler kv:namespace create ALT_TEXT_USAGE
# Update wrangler.toml with the KV namespace ID
wrangler secret put OPENAI_API_KEY
wrangler deploy
```

### 2. Install the Plugin

1. Zip the `plugin/` directory
2. Upload via WordPress admin → Plugins → Add New → Upload
3. Activate and go to Settings → Alt Text AI
4. Enter a license key and configure industry/location

See [SETUP.md](SETUP.md) for full setup instructions.

## Pricing

| Plan    | Price    | Images/month |
|---------|----------|-------------|
| Trial   | Free     | 25 (14 days)|
| Starter | £9/mo    | 100         |
| Pro     | £19/mo   | 500         |
| Agency  | £39/mo   | 2,000       |

## Tech Stack

- **API:** Cloudflare Workers + KV + OpenAI gpt-4o-mini
- **Plugin:** Vanilla PHP 7.4+ / jQuery, WordPress 5.8+
- **Licensing:** Freemius SDK
