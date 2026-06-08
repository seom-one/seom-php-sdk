# seom/sdk

Official PHP SDK for the [Seom](https://seom.one) SEO Content Generation API.

## Requirements

- PHP **8.1+**
- Extensions: `ext-curl`, `ext-json` (both standard in most PHP installs)

## Installation

```bash
composer require seom-one/sdk
```

## Quick start

```php
use Seom\Sdk\SeomClient;

// Get your API key from Settings → API Keys in the Seom dashboard
$client = new SeomClient('sk-seom-...');

// List your last 10 completed articles
$response = $client->articles->list(['status' => 'DONE', 'limit' => 10]);

echo $response['meta']['total'] . " articles total\n";

foreach ($response['data'] as $job) {
    echo $job['article']['title'] . "\n";
}
```

## Authentication

Create an API key in your workspace: **Settings → API Keys → New API key**.

```php
$client = new SeomClient('sk-seom-...');
```

Using an environment variable (recommended):

```php
$client = new SeomClient(getenv('SEOM_API_KEY'));
```

## Usage

### Articles

```php
// List articles (paginated)
$response = $client->articles->list([
    'status' => 'DONE',          // QUEUED | PROCESSING | DONE | FAILED
    'format' => 'BLOG_ARTICLE',  // BLOG_ARTICLE | LINKEDIN_POST | FACEBOOK_POST | TWITTER_THREAD | INSTAGRAM_CAPTION
    'page'   => 1,
    'limit'  => 20,
]);

// Get one article with full HTML content
$response = $client->articles->get('job_abc123');
echo $response['data']['article']['htmlContent'];

// Poll generation status
$response = $client->articles->status('job_abc123');
echo $response['data']['status'] . ' — ' . $response['data']['progress'] . '%';

// Trigger generation (returns immediately)
$response = $client->articles->generate([
    'keyword' => 'best SEO tools 2025',
    'format'  => 'BLOG_ARTICLE',  // optional, defaults to BLOG_ARTICLE
    'locale'  => 'EN_US',         // VI | EN_US | EN_GB
]);
$jobId = $response['data']['jobId'];
echo "Job queued: $jobId\n";

// Generate AND wait for completion (polls automatically)
$response = $client->articles->generateAndWait(
    options:      ['keyword' => 'best SEO tools 2025', 'locale' => 'EN_US'],
    pollInterval: 5,    // check every 5 seconds (default)
    timeout:      600,  // give up after 10 minutes (default)
);
echo $response['data']['article']['title']     . "\n";
echo $response['data']['article']['wordCount'] . " words\n";
echo substr($response['data']['article']['htmlContent'], 0, 500) . "\n";

// Wait for an already-queued job
$response = $client->articles->waitFor('job_abc123');
```

### Keywords

```php
$response = $client->keywords->list([
    'priority' => 'HIGH',  // HIGH | MEDIUM | LOW
    'page'     => 1,
    'limit'    => 20,
]);

foreach ($response['data'] as $kw) {
    echo $kw['keyword'] . ' — score: ' . $kw['opportunityScore'] . "\n";
}
```

### Workspace

```php
$response = $client->workspace->get();

echo $response['data']['name']             . "\n"; // "My Workspace"
echo $response['data']['plan']['name']     . "\n"; // "Basic"

$usage = $response['data']['usage'];
echo $usage['articlesThisMonth'] . '/' . $usage['articlesLimit'] . " articles used\n";
```

## Error handling

All API errors throw a `SeomException`:

```php
use Seom\Sdk\SeomClient;
use Seom\Sdk\SeomException;

try {
    $client->articles->get('does-not-exist');
} catch (SeomException $e) {
    echo $e->getErrorCode();  // 'NOT_FOUND'
    echo $e->getMessage();    // 'Article not found...'
    echo $e->getStatusCode(); // 404
    echo $e->getDocs();       // 'https://docs.seom.one/api/errors#NOT_FOUND'
}
```

Common error codes:

| Code | HTTP | Meaning |
|---|---|---|
| `UNAUTHORIZED` | 401 | Missing or invalid API key |
| `FORBIDDEN` | 403 | Key doesn't have the required scope |
| `NOT_FOUND` | 404 | Resource doesn't exist |
| `VALIDATION_ERROR` | 400 | Invalid request body |
| `PAYMENT_REQUIRED` | 402 | Monthly article limit reached — upgrade plan |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `GENERATION_FAILED` | 500 | AI generation failed |
| `GENERATION_TIMEOUT` | 408 | `waitFor()` timed out |

## Pagination

```php
$page       = 1;
$allArticles = [];

while (true) {
    $response = $client->articles->list(['page' => $page, 'limit' => 50, 'status' => 'DONE']);
    $allArticles = array_merge($allArticles, $response['data']);

    if (!$response['meta']['hasMore']) {
        break;
    }
    $page++;
}

echo count($allArticles) . " total articles fetched\n";
```

## Laravel integration

No special setup needed — just use Composer autoloading:

```php
// In a service provider, controller, or anywhere:
use Seom\Sdk\SeomClient;

class ArticleService
{
    private SeomClient $seom;

    public function __construct()
    {
        $this->seom = new SeomClient(config('services.seom.api_key'));
    }

    public function getArticles(): array
    {
        return $this->seom->articles->list(['status' => 'DONE'])['data'];
    }
}
```

Add to `config/services.php`:

```php
'seom' => [
    'api_key' => env('SEOM_API_KEY'),
],
```

## Self-hosting / local dev

```php
$client = new SeomClient(
    apiKey:  'sk-seom-...',
    baseUrl: 'http://localhost:4000/api', // your local dev server (default: https://api.seom.one/api)
);
```

## Examples

See the [`examples/`](examples/) directory:

- [`list-articles.php`](examples/list-articles.php) — paginate through all articles
- [`generate-article.php`](examples/generate-article.php) — generate and wait with error handling
- [`keyword-research.php`](examples/keyword-research.php) — trigger multiple jobs sequentially

## API reference

Full API reference: **[docs.seom.one/api](https://docs.seom.one/api)**

## License

MIT
