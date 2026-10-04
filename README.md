# Weather API

Laravel API that serves current weather from OpenWeatherMap and a seeded history of about 10,000 weather records.

## Setup

```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Put an OpenWeatherMap key in `.env` as `OPENWEATHER_API_KEY`. Without one the two live endpoints return a 503. The history endpoint and the tests don't need a key.

I used SQLite while developing, but nothing in the code is SQLite specific.

## Endpoints

| Endpoint | What it does |
| --- | --- |
| `GET /api/weather/{city}` | Current weather straight from OpenWeatherMap (`"source": "external"`) |
| `GET /api/weather/{city}/cached` | Same data, cached for 10 minutes (`"source": "cache"` on a hit) |
| `GET /api/weather/history` | Paginated historical records. Optional: `city`, `from`, `to` (Y-m-d), `page`, `per_page` (1-100, default 15) |

Errors come back as JSON with a `message` (and an `errors` object for validation problems):

| Status | When |
| --- | --- |
| 404 | City not found |
| 422 | Invalid city name, bad dates, `to` before `from`, bad `page` / `per_page` |
| 502 | OpenWeatherMap returned an error or couldn't be reached |
| 503 | API key not configured |
| 504 | OpenWeatherMap timed out (5 second timeout, configurable) |


## Benchmark

Results on my machine (SQLite, 10,000 rows, 812 matching records, averaged over 20 runs):

| Scenario | Avg time (ms) | Memory (MB) |
| --- | --- | --- |
| Paginated (50), no index | 5.05 | 0.07 |
| Paginated (50), with index | 2.15 | 0.08 |
| Load all matching rows | 21.41 | 1.27 |

Adding the (city, recorded_at) index cut the paginated query from about 5 ms to about 2 ms. Loading all 812 matching rows took roughly ten times longer than fetching one page and used about sixteen times the memory, because every row becomes an Eloquent model.