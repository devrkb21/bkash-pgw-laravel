<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Http;

use Devrkb21\Bkash\Contracts\BkashClientContract;
use Devrkb21\Bkash\Exceptions\BkashException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Throwable;

class BkashClient implements BkashClientContract
{
    private const RETRY_TIMES = 2;

    private const RETRY_SLEEP_MILLISECONDS = 100;

    private HttpFactory $http;

    private array $config;

    public function __construct(HttpFactory $http, array $config = [])
    {
        $this->http = $http;
        $this->config = $config;
    }

    public function request(
        string $method,
        string $endpoint,
        array $payload = [],
        ?string $accessToken = null,
        array $headers = []
    ): array {
        $method = strtoupper($method);

        $response = $this->http
            ->timeout($this->timeout())
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MILLISECONDS, function (Throwable $exception): bool {
                return $this->shouldRetry($exception);
            })
            ->acceptJson()
            ->asJson()
            ->withHeaders(array_merge($this->defaultHeaders($accessToken), $headers))
            ->send($method, $this->buildUrl($endpoint), $this->requestOptions($method, $payload));

        return $this->parseResponse($response);
    }

    private function parseResponse(Response $response): array
    {
        if ($response->failed()) {
            throw BkashException::fromResponse($response->status(), $response->body());
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new BkashException('bKash API returned a non-JSON or non-array response.');
        }

        return $json;
    }

    private function requestOptions(string $method, array $payload): array
    {
        if ($method === 'GET') {
            return empty($payload) ? [] : ['query' => $payload];
        }

        return ['json' => $payload];
    }

    private function defaultHeaders(?string $accessToken = null): array
    {
        $headers = [
            'x-app-key' => (string) ($this->config['app_key'] ?? ''),
        ];

        if ($accessToken !== null && $accessToken !== '') {
            $headers['Authorization'] = $accessToken;
        }

        return $headers;
    }

    private function buildUrl(string $endpoint): string
    {
        return rtrim($this->resolveBaseUrl(), '/') . '/' . ltrim($endpoint, '/');
    }

    private function resolveBaseUrl(): string
    {
        $baseUrl = trim((string) ($this->config['base_url'] ?? ''));

        if ($baseUrl === '') {
            throw new BkashException('bKash base_url is not configured.');
        }

        return $baseUrl;
    }

    private function timeout(): int
    {
        $timeout = (int) ($this->config['timeout'] ?? 30);

        return $timeout > 0 ? $timeout : 30;
    }

    private function shouldRetry(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException;
    }
}
