<?php

/**
 * Example: Fetch HIGH-priority keyword opportunities and trigger generation
 * for the top 3 using parallel requests via pcntl_fork.
 * Falls back to sequential if pcntl is unavailable.
 *
 * Usage:
 *   SEOM_API_KEY=sk-seom-... php examples/keyword-research.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Seom\Sdk\SeomClient;
use Seom\Sdk\SeomException;

$client = new SeomClient(getenv('SEOM_API_KEY') ?: throw new \RuntimeException('Set SEOM_API_KEY env var'));

// 1. Fetch top HIGH-priority opportunities
$response = $client->keywords->list(['priority' => 'HIGH', 'limit' => 3]);
$keywords = $response['data'];

if (empty($keywords)) {
    echo "No HIGH-priority keywords found. Run keyword discovery first.\n";
    exit(0);
}

echo "Found " . count($keywords) . " HIGH-priority keywords:\n\n";
foreach ($keywords as $i => $kw) {
    $pos = $i + 1;
    $score = $kw['opportunityScore'];
    $diff  = $kw['difficulty'] ?? 'unknown';
    echo "  {$pos}. {$kw['keyword']}  (score: {$score}, difficulty: {$diff})\n";
}
echo "\n";

// 2. Queue all generation jobs
$jobs = [];
foreach ($keywords as $kw) {
    echo "  Queuing: \"{$kw['keyword']}\"\n";
    $job    = $client->articles->generate(['keyword' => $kw['keyword']]);
    $jobs[] = ['keyword' => $kw['keyword'], 'jobId' => $job['data']['jobId']];
}

echo "\nAll jobs queued. Waiting for results…\n\n";

// 3. Poll all jobs sequentially (PHP doesn't have async/await, use fibers if PHP 8.1+)
$results = [];
foreach ($jobs as $job) {
    try {
        $result    = $client->articles->waitFor($job['jobId'], pollInterval: 8, timeout: 600);
        $article   = $result['data']['article'] ?? [];
        $results[] = [
            'success' => true,
            'keyword' => $job['keyword'],
            'title'   => $article['title']     ?? '(no title)',
            'words'   => $article['wordCount'] ?? 0,
        ];
    } catch (SeomException $e) {
        $results[] = [
            'success' => false,
            'keyword' => $job['keyword'],
            'error'   => $e->getMessage(),
        ];
    }
}

// 4. Print results
echo "Results:\n\n";
foreach ($results as $result) {
    if ($result['success']) {
        echo "✓  \"{$result['keyword']}\"\n";
        echo "   Title: {$result['title']}\n";
        echo "   Words: {$result['words']}\n";
    } else {
        echo "✗  \"{$result['keyword']}\" — {$result['error']}\n";
    }
    echo "\n";
}
