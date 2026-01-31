# Setup Guide

## Environment Variables / Secrets

| Variable | Where | Description |
|----------|-------|-------------|
| `OPENAI_API_KEY` | Cloudflare Worker secret | Your OpenAI API key |
| KV namespace ID | `worker/wrangler.toml` | Created via `wrangler kv:namespace create` |

## Step 1: Deploy the Cloudflare Worker

```bash
cd worker
npm install
wrangler login
```

Create the KV namespace:
```bash
wrangler kv:namespace create ALT_TEXT_USAGE
wrangler kv:namespace create ALT_TEXT_USAGE --preview
```

Update `wrangler.toml` with the returned namespace IDs.

Set the OpenAI API key:
```bash
wrangler secret put OPENAI_API_KEY
```

Deploy:
```bash
wrangler deploy
```

### Custom Domain

To use `api.alttextai.com`, add a custom domain in Cloudflare Workers dashboard → your worker → Settings → Triggers → Custom Domains.

## Step 2: Freemius Setup (Manual)

1. Create an account at [freemius.com](https://freemius.com)
2. Add a new WordPress plugin product called "Alt Text AI"
3. Set up pricing plans:
   - **Starter:** £9/month, 100 images
   - **Pro:** £19/month, 500 images
   - **Agency:** £39/month, 2,000 images (label as "Unlimited")
4. Enable 14-day free trial (25 images, no payment required)
5. Download the Freemius SDK
6. Extract to `plugin/freemius/` (the `start.php` file should be at `plugin/freemius/start.php`)
7. Edit `plugin/includes/class-atai-freemius.php`:
   - Replace `'id' => 'XXXXXX'` with your Freemius product ID
   - Replace `'public_key' => 'pk_XXXXXXXX'` with your Freemius public key

## Step 3: License ↔ Worker Sync

The Worker currently uses a stub license validator. For production:

**Option A — Freemius webhook:**
Set up a Freemius webhook that calls your Worker when a license is activated/upgraded/cancelled, storing tier info in KV:
```
Key: tier:{license_key}
Value: {"tier":"pro","limit":500}
```

**Option B — Freemius API validation:**
On each `/generate` call, validate the license against the Freemius API. Slower but always accurate. Cache results in KV with a short TTL.

## Step 4: Install the Plugin

1. Zip the `plugin/` directory (including `freemius/` SDK)
2. Upload to WordPress: Plugins → Add New → Upload Plugin
3. Activate
4. Go to Settings → Alt Text AI
5. The license key auto-populates when users activate via Freemius

## Step 5: Test

1. Upload a test image to Media Library
2. Open attachment details
3. Click "✨ Generate Alt Text with AI"
4. Verify alt text is generated and saved
5. Test bulk action: select multiple images in list view → Bulk Actions → Generate Alt Text (AI)
