<?php

declare(strict_types=1);

use Devrkb21\Bkash\Contracts\WebhookServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/bkash/webhook', function (Request $request, WebhookServiceContract $webhookService): JsonResponse {
    return new JsonResponse($webhookService->process($request->all(), $request->headers->all()));
})->name('bkash.webhook');
