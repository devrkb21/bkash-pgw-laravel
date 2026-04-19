<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Tests;

use Devrkb21\Bkash\Contracts\BkashClientContract;
use Devrkb21\Bkash\Exceptions\BkashException;
use Devrkb21\Bkash\Services\AuthService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class AuthServiceTest extends TestCase
{
    public function testGrantTokenStoresTokenDataInCache(): void
    {
        $jwt = $this->makeJwt(time() + 3600);

        $client = $this->createMock(BkashClientContract::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'auth/grant-token',
                $this->arrayHasKey('app_key'),
                null,
                $this->arrayHasKey('username')
            )
            ->willReturn([
                'id_token' => $jwt,
                'refresh_token' => 'refresh_1',
                'expires_in' => 3600,
            ]);

        $service = new AuthService(
            $client,
            $this->app->make(CacheRepository::class),
            (array) $this->app['config']->get('bkash', [])
        );

        $tokenData = $service->grantToken();

        $this->assertSame($jwt, $tokenData['id_token']);
        $this->assertSame('refresh_1', $tokenData['refresh_token']);
        $this->assertArrayHasKey('expires_at', $tokenData);

        $cached = $this->app->make(CacheRepository::class)->get('bkash.test.token');
        $this->assertIsArray($cached);
        $this->assertSame($jwt, $cached['id_token']);
        $this->assertSame('refresh_1', $cached['refresh_token']);
        $this->assertArrayHasKey('expires_at', $cached);
    }

    public function testRefreshTokenFallsBackToGrantTokenWhenRefreshFails(): void
    {
        $newJwt = $this->makeJwt(time() + 3600);

        $client = $this->createMock(BkashClientContract::class);
        $client->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $endpoint) use ($newJwt): array {
                if ($method === 'POST' && $endpoint === 'auth/refresh-token') {
                    throw new BkashException('refresh failed');
                }

                return [
                    'id_token' => $newJwt,
                    'refresh_token' => 'refresh_2',
                    'expires_in' => 3600,
                ];
            });

        $service = new AuthService(
            $client,
            $this->app->make(CacheRepository::class),
            (array) $this->app['config']->get('bkash', [])
        );

        $tokenData = $service->refreshToken('refresh_1');

        $this->assertSame($newJwt, $tokenData['id_token']);
        $this->assertSame('refresh_2', $tokenData['refresh_token']);
    }

    private function makeJwt(int $exp): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode(['exp' => $exp], JSON_THROW_ON_ERROR));

        return $header . '.' . $payload . '.signature';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
