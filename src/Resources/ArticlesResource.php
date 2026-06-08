<?php

declare(strict_types=1);

namespace Seom\Sdk\Resources;

use Seom\Sdk\HttpClient;
use Seom\Sdk\SeomException;

class ArticlesResource
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * List articles in your workspace.
     *
     * @param  array{status?: string, format?: string, page?: int, limit?: int}  $options
     * @return array{data: list<array<string,mixed>>, meta: array{total:int,page:int,limit:int,hasMore:bool}, error: null}
     *
     * @example
     * $response = $client->articles->list(['status' => 'DONE', 'limit' => 10]);
     * foreach ($response['data'] as $job) {
     *     echo $job['article']['title'] . "\n";
     * }
     */
    public function list(array $options = []): array
    {
        return $this->http->get('/v1/articles', [
            'page'   => $options['page']   ?? null,
            'limit'  => $options['limit']  ?? null,
            'status' => $options['status'] ?? null,
            'format' => $options['format'] ?? null,
        ]);
    }

    /**
     * Get a single article by job ID, including full HTML content.
     *
     * @return array{data: array<string,mixed>, meta: null, error: null}
     *
     * @example
     * $response = $client->articles->get('job_abc123');
     * echo $response['data']['article']['htmlContent'];
     */
    public function get(string $jobId): array
    {
        return $this->http->get("/v1/articles/{$jobId}");
    }

    /**
     * Poll the status of a generation job.
     *
     * @return array{data: array{jobId:string,status:string,progress:int,currentStep:string|null}, meta: null, error: null}
     *
     * @example
     * $response = $client->articles->status('job_abc123');
     * echo $response['data']['status'] . ' ' . $response['data']['progress'] . '%';
     */
    public function status(string $jobId): array
    {
        return $this->http->get("/v1/articles/{$jobId}/status");
    }

    /**
     * Trigger article generation. Returns immediately with a jobId.
     * Use waitFor() or poll status() to track progress.
     *
     * @param  array{keyword: string, format?: string, locale?: string}  $options
     * @return array{data: array{jobId:string,status:string,keyword:string}, meta: null, error: null}
     *
     * @example
     * $response = $client->articles->generate(['keyword' => 'best SEO tools 2025']);
     * echo 'Job queued: ' . $response['data']['jobId'];
     */
    public function generate(array $options): array
    {
        if (empty($options['keyword'])) {
            throw new \InvalidArgumentException("generate() requires a 'keyword' option");
        }

        return $this->http->post('/v1/articles', $options);
    }

    /**
     * Trigger generation and wait for it to complete.
     * Polls every $pollInterval seconds until DONE or FAILED.
     *
     * @param  array{keyword: string, format?: string, locale?: string}  $options
     * @param  int  $pollInterval  Seconds between polls (default 5)
     * @param  int  $timeout       Max seconds to wait (default 600 = 10 min)
     *
     * @throws SeomException with code 'GENERATION_FAILED' if the job fails
     * @throws SeomException with code 'GENERATION_TIMEOUT' if timeout exceeded
     *
     * @example
     * $response = $client->articles->generateAndWait(
     *     ['keyword' => 'best SEO tools 2025', 'locale' => 'EN_US'],
     *     pollInterval: 5,
     *     timeout: 300
     * );
     * echo $response['data']['article']['title'];
     */
    public function generateAndWait(array $options, int $pollInterval = 5, int $timeout = 600): array
    {
        $response = $this->generate($options);
        return $this->waitFor($response['data']['jobId'], $pollInterval, $timeout);
    }

    /**
     * Wait for an existing job to complete.
     *
     * @param  int  $pollInterval  Seconds between status checks (default 5)
     * @param  int  $timeout       Max seconds to wait (default 600 = 10 min)
     *
     * @throws SeomException with code 'GENERATION_FAILED' if the job fails
     * @throws SeomException with code 'GENERATION_TIMEOUT' if timeout exceeded
     *
     * @example
     * $job    = $client->articles->generate(['keyword' => 'SEO tips']);
     * // ... do other work ...
     * $result = $client->articles->waitFor($job['data']['jobId']);
     * echo $result['data']['article']['htmlContent'];
     */
    public function waitFor(string $jobId, int $pollInterval = 5, int $timeout = 600): array
    {
        $deadline = time() + $timeout;

        while (time() < $deadline) {
            $response = $this->status($jobId);
            $status   = $response['data']['status'] ?? '';

            if ($status === 'DONE') {
                return $this->get($jobId);
            }

            if ($status === 'FAILED') {
                $msg = $response['data']['errorMsg'] ?? 'Article generation failed';
                throw new SeomException(
                    'GENERATION_FAILED',
                    $msg,
                    500,
                    'https://docs.seom.one/api/errors#GENERATION_FAILED',
                );
            }

            sleep($pollInterval);
        }

        throw new SeomException(
            'GENERATION_TIMEOUT',
            "Job {$jobId} did not complete within {$timeout}s. Poll status() manually.",
            408,
            'https://docs.seom.one/api/errors#GENERATION_TIMEOUT',
        );
    }
}
