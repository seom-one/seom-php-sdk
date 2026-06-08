<?php

/**
 * Example: Paginate through all completed articles and print a summary.
 *
 * Usage:
 *   SEOM_API_KEY=sk-seom-... php examples/list-articles.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Seom\Sdk\SeomClient;

$client = new SeomClient(getenv('SEOM_API_KEY') ?: throw new \RuntimeException('Set SEOM_API_KEY env var'));

$page  = 1;
$total = 0;

while (true) {
    $response = $client->articles->list([
        'status' => 'DONE',
        'page'   => $page,
        'limit'  => 20,
    ]);

    foreach ($response['data'] as $job) {
        $article = $job['article'] ?? [];
        $total++;
        $parts = [
            "[{$total}]",
            $article['title'] ?? $job['keyword'],
            '(' . ($article['wordCount'] ?? 0) . ' words)',
            ($job['format'] !== 'BLOG_ARTICLE') ? "[{$job['format']}]" : '',
            isset($article['wpPostId']) ? "✓ WordPress #{$article['wpPostId']}" : '○ Local draft',
        ];
        echo implode('  ', array_filter($parts)) . "\n";
    }

    if (!$response['meta']['hasMore']) {
        break;
    }
    $page++;
}

echo "\nTotal: {$total} articles\n";
