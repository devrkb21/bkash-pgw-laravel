# devrkb21/bkash-pgw-laravel

Laravel package for bKash Tokenized Checkout V2 with auth, payment, agreement, refund, and SNS webhook handling.

## Requirements

- PHP 8.1+
- Laravel 10/11/12

## Installation

```bash
composer require devrkb21/bkash-pgw-laravel
```

Publish package configuration:

```bash
php artisan vendor:publish --tag=bkash-config
```

## Configuration

Set the following in your `.env`:

```env
BKASH_ENV=sandbox
BKASH_SANDBOX_BASE_URL=https://tokenized.sandbox.bka.sh/v2/tokenized-checkout
BKASH_PRODUCTION_BASE_URL=https://tokenized.pay.bka.sh/v2/tokenized-checkout

BKASH_APP_KEY=
BKASH_APP_SECRET=
BKASH_USERNAME=
BKASH_PASSWORD=

BKASH_TIMEOUT=30
BKASH_CACHE=true
BKASH_TOKEN_CACHE_KEY=bkash.token

BKASH_ENABLE_ROUTES=false
```

## Usage

### Facade Manager

```php
use Devrkb21\Bkash\Facades\Bkash;

$paymentService = Bkash::payment();
$authService = Bkash::auth();
$agreementService = Bkash::agreement();
$refundService = Bkash::refund();
```

### Create Payment

```php
$response = Bkash::payment()->createPayment([
    'payerReference' => 'payer_123',
    'callbackURL' => 'https://merchant.example.com/bkash/callback',
    'amount' => '100',
    'currency' => 'BDT',
    'intent' => 'sale',
    'merchantInvoiceNumber' => 'INV-1001',
]);

$paymentId = $response['paymentID'] ?? null;
$redirectUrl = $response['bkashURL'] ?? null;
```

### Execute Payment

```php
$execution = Bkash::payment()->executePayment('payment_id_here');
$trxId = $execution['trxID'] ?? null;
```

### Callback Flow

```php
$result = Bkash::payment()->handleCallback($request->all());
```

### Token Access

```php
$idToken = Bkash::auth()->getValidAccessToken();
```

## Webhook Setup

When `BKASH_ENABLE_ROUTES=true`, package webhook route is loaded:

`POST /bkash/webhook`

If you keep routes disabled, call `WebhookService` from your own route/controller and pass:

- request payload array
- request headers array

Example:

```php
use Devrkb21\Bkash\Contracts\WebhookServiceContract;

$result = app(WebhookServiceContract::class)->process(
    $request->all(),
    $request->headers->all()
);
```

The webhook service validates SNS signatures, handles subscription confirmation, and parses notification data.

## Testing

```bash
composer test
```
