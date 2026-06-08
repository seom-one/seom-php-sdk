<?php

/**
 * Example: Generate an article and wait for it to finish.
 * Prints the title and first 500 chars of HTML when done.
 *
 * Usage:
 *   SEOM_API_KEY=sk-seom-... php examples/generate-article.php "best SEO tools 2025"
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Seom\Sdk\SeomClient;
use Seom\Sdk\SeomException;

$apiKey  = getenv('SEOM_API_KEY') ?: throw new \RuntimeException('Set SEOM_API_KEY env var');
$keyword = $argv[1] ?? 'best SEO tools 2025';

$client = new SeomClient($apiKey);

echo "Generating article for: \"{$keyword}\"\n";
echo "Polling every 5 seconds until done…\n\n";

try {
    $response = $client->articles->generateAndWait(
        options:      ['keyword' => $keyword, 'locale' => 'EN_US', 'format' => 'BLOG_ARTICLE'],
        pollInterval: 5,    // check every 5 seconds
        timeout:      600,  // give up after 10 minutes
    );

    $article = $response['data']['article'] ?? [];

    echo "✓ Done!\n\n";
    echo 'Title:  ' . ($article['title']     ?? '(none)')   . "\n";
    echo 'Words:  ' . ($article['wordCount'] ?? 0)           . "\n";
    echo 'Image:  ' . ($article['featuredImageUrl'] ?? '(none)') . "\n\n";
    echo "--- First 500 chars of HTML ---\n";
    echo substr($article['htmlContent'] ?? '(no content)', 0, 500) . "\n";

} catch (SeomException $e) {
    echo "Failed: [{$e->getErrorCode()}] {$e->getMessage()}\n";
    echo 'Docs: ' . $e->getDocs() . "\n";
    exit(1);
}
