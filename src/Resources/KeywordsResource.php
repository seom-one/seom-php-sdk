<?php

declare(strict_types=1);

namespace Seom\Sdk\Resources;

use Seom\Sdk\HttpClient;

class KeywordsResource
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * List keyword opportunities for your workspace.
     *
     * @param  array{priority?: string, source?: string, page?: int, limit?: int}  $options
     * @return array{data: list<array<string,mixed>>, meta: array{total:int,page:int,limit:int,hasMore:bool}, error: null}
     *
     * @example
     * $response = $client->keywords->list(['priority' => 'HIGH']);
     * foreach ($response['data'] as $kw) {
     *     echo $kw['keyword'] . ' — score: ' . $kw['opportunityScore'] . "\n";
     * }
     */
    public function list(array $options = []): array
    {
        return $this->http->get('/v1/keywords', [
            'page'     => $options['page']     ?? null,
            'limit'    => $options['limit']    ?? null,
            'priority' => $options['priority'] ?? null,
            'source'   => $options['source']   ?? null,
        ]);
    }
}
