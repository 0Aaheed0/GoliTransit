# GoliTransit Deployment Checklist

Follow these steps in order for the final live check.

## 1. Deploy the app

Use the existing Vercel setup in this repo:

- `vercel.json`
- `api/index.php`

Required environment variables:

```env
APP_NAME=GoliTransit
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY
APP_URL=https://your-live-url.vercel.app

LOG_CHANNEL=stderr
CACHE_DRIVER=array
SESSION_DRIVER=cookie
SESSION_SECURE_COOKIE=true
```

## 2. Verify the live URL

Open:

```text
https://your-live-url.vercel.app/health
```

Expected:

```json
{"status":"ok"}
```

## 3. Verify the 4 required endpoints

### `GET /health`

Expected:

```json
{"status":"ok"}
```

### `POST /api/route`

Example body:

```json
{
  "session_id": "live-demo-session",
  "start": "farmgate",
  "destination": "gulshan_2",
  "allowed_modes": ["car", "rickshaw", "walk"]
}
```

Check:

- route returns 200
- `session_id` is returned
- `path` is not empty
- `segments` is not empty

### `POST /api/anomaly`

Example body:

```json
{
  "edge_ids": ["edge_karwan_bazar_tejgaon", "edge_tejgaon_banani"],
  "multiplier": 10
}
```

Check:

- response returns 200
- `affected_edges` includes updated `current_weight`
- `reroute_summary` is present

### `GET /api/graph/snapshot`

Check:

- response returns 200
- `meta.edge_count` is present
- affected edge now shows inflated `current_weight`

## 4. Verify the frontend

Open:

```text
https://your-live-url.vercel.app/
```

Check:

- control room page loads
- graph is visible
- route button works
- anomaly button works
- snapshot refresh works

## 5. Update Postman

Open:

- `postman/GoliTransit.postman_collection.json`

Set:

- `base_url = https://your-live-url.vercel.app`

Run all 4 requests once.

## 6. Final demo flow

Do this exact order:

1. `GET /health`
2. `POST /api/route` car only
3. `POST /api/route` multi-modal
4. `POST /api/anomaly`
5. `GET /api/graph/snapshot`

## 7. Final manual checks

- live URL works from browser
- Postman collection works against live URL
- control room page loads from live URL
- route changes after anomaly
- graph snapshot shows inflated weights
