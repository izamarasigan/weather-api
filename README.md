#  Weather API

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

## Running tests

```
php artisan test
```

OpenWeatherMap is faked with `Http::fake()`, so the tests don't need a key or a network connection.

## Running the benchmark

```
php artisan weather:benchmark
```

Optional: `--city=Cebu` and `--runs=50`.

## How it's put together

- **Controllers** are thin. `WeatherController` handles the two live endpoints (including the cache lookup) and `WeatherHistoryController` builds the history query.
- **`WeatherService`** is the only class that talks to OpenWeatherMap. It reads the key, URL and timeout from `config/weather.php`, turns HTTP failures into a `WeatherException`, and maps the response to the shape the API returns.
- **`WeatherException`** knows its own status code and renders itself as JSON, so error responses look the same everywhere.
- **`HistoryRequest`** validates the history query parameters.
- **`WeatherRecord`** has two query scopes (`inCity`, `recordedBetween`) that the endpoint and the benchmark both use, so the benchmark measures the same query the API runs.
- **`WeatherRecordResource`** controls the JSON output of a record.

I didn't add a repository layer. For one table and one query it would only be an extra file to maintain.

## Handling the 10,000 records

- The history endpoint always uses `paginate()`, with a maximum of 100 per page, so the full table is never loaded into PHP.
- It selects only the five columns the response needs.
- Filtering and sorting happen in SQL.
- The seeder inserts in chunks of 1,000 rows instead of creating 10,000 models one at a time.

## Database optimization

There is a composite index on `(city, recorded_at)`. Every history query filters on city and/or a date range and sorts by `recorded_at`, so the index lets the database jump to one city's rows and read them already in date order instead of scanning the whole table and sorting.

Column order matters here: `city` is an equality filter and goes first, `recorded_at` is the range filter and goes second. A query with only `from` / `to` and no city can't use the index well; I decided not to add a second index for that because the main use case is per-city history.

## Benchmark

Results on my machine (SQLite, 10,000 rows, 812 matching records, averaged over 20 runs):

| Scenario | Avg time (ms) | Memory (MB) |
| --- | --- | --- |
| Paginated (50), no index | 5.05 | 0.07 |
| Paginated (50), with index | 2.15 | 0.08 |
| Load all matching rows | 21.41 | 1.27 |

Adding the (city, recorded_at) index cut the paginated query from about 5 ms to about 2 ms. Loading all 812 matching rows took roughly ten times longer than fetching one page and used about sixteen times the memory, because every row becomes an Eloquent model.