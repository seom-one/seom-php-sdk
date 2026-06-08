<?php

declare(strict_types=1);

namespace Seom\Sdk;

class HttpClient
{
    private const SDK_VERSION = '1.0.0';
    private const DEFAULT_TIMEOUT = 30;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {}

    /** @return array<string, mixed> */
    public function get(string $path, array $query = []): array
    {
        $url = $this->baseUrl . $path;

        $filtered = array_filter($query, fn ($v) => $v !== null && $v !== '');
        if (!empty($filtered)) {
            $url .= '?' . http_build_query($filtered);
        }

        return $this->request('GET', $url);
    }

    /** @return array<string, mixed> */
    public function post(string $path, array $body = []): array
    {
        return $this->request('POST', $this->baseUrl . $path, $body);
    }

    /** @return array<string, mixed> */
    private function request(string $method, string $url, ?array $body = null): array
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('The curl extension is required by seom/sdk. Enable it in php.ini.');
        }

        $ch = curl_init($url);

        $headers = [
            "Authorization: Bearer {$this->apiKey}",
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: seom-php-sdk/' . self::SDK_VERSION . ' php/' . PHP_VERSION,
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => self::DEFAULT_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        }

        $response   = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new SeomException('NETWORK_ERROR', "cURL error: {$curlError}");
        }

        if ($response === false || $response === '') {
            throw new SeomException('EMPTY_RESPONSE', 'Server returned an empty response', $statusCode);
        }

        $data = json_decode((string) $response, true, flags: JSON_THROW_ON_ERROR);

        if (isset($data['error']) && is_array($data['error'])) {
            throw new SeomException(
                $data['error']['code'] ?? 'UNKNOWN_ERROR',
                $data['error']['message'] ?? 'An unknown API error occurred',
                $statusCode,
                $data['error']['docs'] ?? null,
            );
        }

        return $data;
    }
}
