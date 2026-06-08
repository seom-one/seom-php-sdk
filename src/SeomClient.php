<?php

declare(strict_types=1);

namespace Seom\Sdk;

use Seom\Sdk\Resources\ArticlesResource;
use Seom\Sdk\Resources\KeywordsResource;
use Seom\Sdk\Resources\WorkspaceResource;

/**
 * Official Seom SDK client.
 *
 * @example
 * use Seom\Sdk\SeomClient;
 *
 * $client = new SeomClient('sk-seom-...');
 *
 * // List articles
 * $response = $client->articles->list(['status' => 'DONE']);
 *
 * // Generate and wait
 * $result = $client->articles->generateAndWait(['keyword' => 'best SEO tools']);
 * echo $result['data']['article']['title'];
 */
class SeomClient
{
    private const DEFAULT_BASE_URL = 'https://api.seom.one/api';

    /** Manage and retrieve generated articles */
    public readonly ArticlesResource $articles;

    /** Browse keyword opportunities */
    public readonly KeywordsResource $keywords;

    /** Get workspace info and plan usage */
    public readonly WorkspaceResource $workspace;

    /**
     * @param  string  $apiKey   Your workspace API key (sk-seom-...)
     * @param  string  $baseUrl  Override the API base URL (useful for self-hosting or local dev)
     *
     * @throws \InvalidArgumentException if apiKey is missing or has wrong format
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
    ) {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException(
                'SeomClient: apiKey is required. Get one from Settings → API Keys.'
            );
        }

        if (!str_starts_with($apiKey, 'sk-seom-')) {
            throw new \InvalidArgumentException(
                'SeomClient: apiKey must start with "sk-seom-". Get your key from Settings → API Keys.'
            );
        }

        $http = new HttpClient($apiKey, rtrim($baseUrl, '/'));

        $this->articles  = new ArticlesResource($http);
        $this->keywords  = new KeywordsResource($http);
        $this->workspace = new WorkspaceResource($http);
    }
}
