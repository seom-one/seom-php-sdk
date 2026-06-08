<?php

declare(strict_types=1);

namespace Seom\Sdk;

use RuntimeException;

/**
 * All API errors thrown by the Seom SDK are instances of SeomException.
 *
 * @example
 * try {
 *     $client->articles->get('does-not-exist');
 * } catch (SeomException $e) {
 *     echo $e->getErrorCode();  // 'NOT_FOUND'
 *     echo $e->getStatusCode(); // 404
 *     echo $e->getDocs();       // 'https://docs.seom.one/api/errors#NOT_FOUND'
 * }
 */
class SeomException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?string $docs = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    /** Machine-readable error code, e.g. 'NOT_FOUND', 'UNAUTHORIZED', 'RATE_LIMIT_EXCEEDED' */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /** HTTP status code returned by the API */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** Link to error documentation */
    public function getDocs(): ?string
    {
        return $this->docs ?? "https://docs.seom.one/api/errors#{$this->errorCode}";
    }

    public function __toString(): string
    {
        return "SeomException [{$this->errorCode}] (HTTP {$this->statusCode}): {$this->getMessage()}";
    }
}
