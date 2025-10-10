<?php

declare(strict_types=1);

namespace App\Services\Trades;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class TradesDataService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchProfiles(): array
    {
        $response = $this->send(fn (): Response => $this->http->remote()->get('/api/v2/profiles'));

        return $response?->json('data') ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchPositions(int $profileId): array
    {
        $response = $this->send(fn (): Response => $this->http->remote()->get(
            sprintf('/api/v2/positions/%d', $profileId)
        ));

        return $response?->json('data') ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchPortfolioSnapshot(int $profileId): array
    {
        $response = $this->send(fn (): Response => $this->http->remote()->get(
            sprintf('/api/v2/profile/portfolio/%d', $profileId)
        ));

        return $response?->json('data') ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchPortfolioHistory(int $profileId): array
    {
        $response = $this->send(fn (): Response => $this->http->remote()->get(
            sprintf('/api/v2/profile/portfolio-history/%d', $profileId)
        ));

        return $response?->json('data') ?? [];
    }

    private function send(callable $callback): ?Response
    {
        try {
            /** @var Response $response */
            $response = $callback();
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Trades API request failed.', [
                'exception' => $exception,
            ]);

            return null;
        }

        if ($response->failed()) {
            $this->logFailedResponse($response);

            return null;
        }

        return $response;
    }

    private function logFailedResponse(Response $response): void
    {
        try {
            $response->throw();
        } catch (RequestException $exception) {
            report($exception);
            Log::warning('Trades API responded with a non-success status.', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
        }
    }
}
