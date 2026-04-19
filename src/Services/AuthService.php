<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Services;

use Devrkb21\Bkash\Contracts\BkashClientContract;
use Devrkb21\Bkash\Contracts\AuthServiceContract;
use Devrkb21\Bkash\DTO\AuthResponse;
use Devrkb21\Bkash\Exceptions\BkashAuthException;
use Devrkb21\Bkash\Exceptions\BkashException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;

class AuthService implements AuthServiceContract
{
    private const EXPIRY_BUFFER_SECONDS = 30;

    private const FALLBACK_EXPIRY_SECONDS = 3600;

    private BkashClientContract $client;

    private CacheRepository $cache;

    private array $config;

    private ?array $runtimeTokenData = null;

    public function __construct(BkashClientContract $client, CacheRepository $cache, array $config = [])
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->config = $config;
    }

    public function grantToken(bool $forceRefresh = false): array
    {
        if (!$forceRefresh) {
            $cached = $this->getCachedTokenData();
            if ($cached !== null && !$this->isExpired($cached)) {
                return $cached;
            }
        }

        Log::info('bKash auth: fetching grant token');

        try {
            $tokenData = $this->client->request(
                'POST',
                'auth/grant-token',
                $this->authBody(),
                null,
                $this->authHeaders()
            );
        } catch (BkashException $exception) {
            Log::error('bKash auth: grant token request failed', [
                'error' => 'grant_token_failed',
                'code' => $exception->getCode(),
            ]);

            throw $exception;
        }

        $storedTokenData = $this->storeTokenData($tokenData);

        Log::info('bKash auth: grant token fetched', [
            'expires_at' => $storedTokenData['expires_at'],
        ]);

        return $storedTokenData;
    }

    public function refreshToken(?string $refreshToken = null): array
    {
        $cached = $this->getCachedTokenData();
        $refreshToken = $refreshToken ?? (string) ($cached['refresh_token'] ?? '');

        if ($refreshToken === '') {
            Log::warning('bKash auth: refresh token missing, falling back to grant token');

            return $this->grantToken(true);
        }

        Log::info('bKash auth: refreshing token');

        try {
            $tokenData = $this->client->request(
                'POST',
                'auth/refresh-token',
                [
                    ...$this->authBody(),
                    'refresh_token' => $refreshToken,
                ],
                null,
                $this->authHeaders()
            );
        } catch (BkashException $exception) {
            Log::warning('bKash auth: refresh request failed, falling back to grant token', [
                'error' => 'refresh_token_failed',
                'code' => $exception->getCode(),
            ]);

            return $this->grantToken(true);
        }

        try {
            $storedTokenData = $this->storeTokenData($tokenData, $refreshToken);
        } catch (BkashException $exception) {
            Log::warning('bKash auth: refresh response invalid, falling back to grant token', [
                'error' => 'refresh_response_invalid',
            ]);

            return $this->grantToken(true);
        }

        Log::info('bKash auth: token refreshed', [
            'expires_at' => $storedTokenData['expires_at'],
        ]);

        return $storedTokenData;
    }

    public function getValidAccessToken(): string
    {
        $tokenData = $this->getCachedTokenData();

        if ($tokenData === null) {
            Log::info('bKash auth: token cache empty, fetching new token');
            $tokenData = $this->grantToken();
        }

        if ($this->isExpired($tokenData)) {
            Log::info('bKash auth: token expired or near expiry, refreshing token');
            $tokenData = $this->refreshToken((string) ($tokenData['refresh_token'] ?? ''));
        }

        $idToken = (string) ($tokenData['id_token'] ?? '');
        if ($idToken === '') {
            Log::error('bKash auth: no id_token available after token resolution');
            throw BkashAuthException::fromMessage('bKash auth response does not include id_token.');
        }

        return $idToken;
    }

    public function getCachedTokenData(): ?array
    {
        if (!$this->shouldCache()) {
            return $this->hydrateTokenData($this->runtimeTokenData);
        }

        $tokenData = $this->cache->get($this->tokenCacheKey());

        return $this->hydrateTokenData(is_array($tokenData) ? $tokenData : null);
    }

    private function storeTokenData(array $tokenData, ?string $fallbackRefreshToken = null): array
    {
        $normalizedTokenData = $this->normalizeTokenData($tokenData, $fallbackRefreshToken);
        $authResponse = AuthResponse::fromArray($normalizedTokenData);

        $this->runtimeTokenData = $normalizedTokenData;

        if ($this->shouldCache()) {
            $ttl = max(1, $authResponse->expiresAt - time());

            $this->cache->put($this->tokenCacheKey(), [
                'id_token' => $authResponse->idToken,
                'refresh_token' => $authResponse->refreshToken,
                'expires_at' => $authResponse->expiresAt,
            ], $ttl);
        }

        return $authResponse->toArray();
    }

    private function isExpired(array $tokenData): bool
    {
        $expiresAt = (int) ($tokenData['expires_at'] ?? 0);

        if ($expiresAt === 0) {
            return true;
        }

        return $expiresAt <= (time() + self::EXPIRY_BUFFER_SECONDS);
    }

    private function resolveExpiryTimestamp(array $tokenData): int
    {
        if (isset($tokenData['expires_in']) && is_numeric($tokenData['expires_in'])) {
            return time() + (int) $tokenData['expires_in'];
        }

        $idToken = (string) ($tokenData['id_token'] ?? '');
        if ($idToken !== '') {
            $parts = explode('.', $idToken);
            if (count($parts) === 3) {
                $decoded = $this->decodeBase64Url($parts[1]);
                if ($decoded !== '') {
                    $payload = json_decode($decoded, true);
                    if (is_array($payload) && isset($payload['exp']) && is_numeric($payload['exp'])) {
                        return (int) $payload['exp'];
                    }
                }
            }
        }

        return time() + self::FALLBACK_EXPIRY_SECONDS;
    }

    private function normalizeTokenData(array $tokenData, ?string $fallbackRefreshToken = null): array
    {
        $idToken = trim((string) ($tokenData['id_token'] ?? ''));
        if ($idToken === '') {
            Log::error('bKash auth: token payload missing id_token');
            throw BkashAuthException::fromMessage('bKash auth response does not include id_token.');
        }

        $refreshToken = trim((string) ($tokenData['refresh_token'] ?? $fallbackRefreshToken ?? ''));
        if ($refreshToken === '') {
            Log::error('bKash auth: token payload missing refresh_token');
            throw BkashAuthException::fromMessage('bKash auth response does not include refresh_token.');
        }

        $expiresAt = $this->resolveExpiryTimestamp($tokenData);
        if ($expiresAt <= time()) {
            Log::error('bKash auth: token payload has invalid expiry');
            throw BkashAuthException::fromMessage('bKash auth token expiry is invalid.');
        }

        return [
            'id_token' => $idToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
            'expires_in' => max(0, $expiresAt - time()),
        ];
    }

    private function hydrateTokenData(?array $tokenData): ?array
    {
        if (!is_array($tokenData)) {
            return null;
        }

        $idToken = trim((string) ($tokenData['id_token'] ?? ''));
        $refreshToken = trim((string) ($tokenData['refresh_token'] ?? ''));
        $expiresAt = (int) ($tokenData['expires_at'] ?? 0);

        if ($idToken === '' || $refreshToken === '' || $expiresAt <= 0) {
            return null;
        }

        return [
            'id_token' => $idToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
            'expires_in' => max(0, $expiresAt - time()),
        ];
    }

    private function authBody(): array
    {
        return [
            'app_key' => (string) ($this->config['app_key'] ?? ''),
            'app_secret' => (string) ($this->config['app_secret'] ?? ''),
        ];
    }

    private function authHeaders(): array
    {
        return [
            'username' => (string) ($this->config['username'] ?? ''),
            'password' => (string) ($this->config['password'] ?? ''),
        ];
    }

    private function decodeBase64Url(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    private function tokenCacheKey(): string
    {
        $cacheKeys = $this->config['cache_keys'] ?? [];

        if (is_array($cacheKeys)) {
            $tokenKey = (string) ($cacheKeys['token'] ?? '');
            if ($tokenKey !== '') {
                return $tokenKey;
            }
        }

        return 'bkash.token';
    }

    private function shouldCache(): bool
    {
        return (bool) ($this->config['cache'] ?? true);
    }
}
