<?php

declare(strict_types=1);

namespace Seom\Sdk\Resources;

use Seom\Sdk\HttpClient;

class WorkspaceResource
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * Get your workspace info, current plan, and usage this month.
     *
     * @return array{data: array<string,mixed>, meta: null, error: null}
     *
     * @example
     * $response = $client->workspace->get();
     * echo 'Plan: '    . $response['data']['plan']['name'] . "\n";
     * echo 'Articles: ' . $response['data']['usage']['articlesThisMonth']
     *      . '/' . $response['data']['usage']['articlesLimit'] . "\n";
     */
    public function get(): array
    {
        return $this->http->get('/v1/workspace');
    }
}
