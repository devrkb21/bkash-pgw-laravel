# 💸 Laravel bKash Payment Gateway (Tokenized Checkout V2)

🚀 Production-ready **Laravel bKash package** for integrating **bKash Tokenized Checkout V2 API** with secure webhook, token management, and full payment flow.

> The most complete and secure bKash integration for Laravel.

---

![PHP](https://img.shields.io/badge/PHP-8.1+-blue)
![Laravel](https://img.shields.io/badge/Laravel-10%2B-red)
![License](https://img.shields.io/badge/license-MIT-green)

---

## 🚀 Quick Start (30 seconds)

```bash
composer require devrkb21/bkash-pgw-laravel
```

```php
use Bkash;

$url = Bkash::payment()->createPayment([
    'payerReference' => 'user_123',
    'callbackURL' => route('bkash.callback'),
    'amount' => '100',
    'currency' => 'BDT',
    'intent' => 'sale',
    'merchantInvoiceNumber' => 'INV123456',
]);

return redirect($url->bkash_url);
```

👉 That’s it. User will be redirected to bKash payment page.

---

## ⚙️ Configuration

Publish config:

```bash
php artisan vendor:publish --tag=bkash-config
```

Add credentials:

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

---

### Auth APIs

#### Grant Token - POST /auth/grant-token

Request body keys:

```json
{
    "app_key": "{{bkash_app_key}}",
    "app_secret": "{{bkash_app_secret}}"
}
```

Response (normalized by the package, `expires_at` is computed):

```json
{
    "id_token": "string",
    "refresh_token": "string",
    "expires_in": 3600,
    "expires_at": 1730999999
}
```

#### Refresh Token - POST /auth/refresh-token

Request body keys:

```json
{
    "app_key": "{{bkash_app_key}}",
    "app_secret": "{{bkash_app_secret}}",
    "refresh_token": "{{bkash_refresh_token}}"
}
```

Response (normalized by the package, `expires_at` is computed):

```json
{
    "id_token": "string",
    "refresh_token": "string",
    "expires_in": 3600,
    "expires_at": 1730999999
}
```

### Payment APIs (Without Agreement)

#### Create Payment - POST /payment/create

Request body keys:

```json
{
    "payerReference": "{{payer_reference}}",
    "callbackURL": "{{callback_url}}",
    "amount": "{{amount}}",
    "currency": "{{currency}}",
    "intent": "{{intent}}",
    "merchantInvoiceNumber": "{{merchant_invoice_number}}",
    "merchantAssociationInfo": "{{merchant_association_info}}"
}
```

Response (package normalizes common fields):

```json
{
    "paymentID": "payment_123",
    "paymentId": "payment_123",
    "bkashURL": "https://tokenized.sandbox.bka.sh/checkout/..."
}
```

#### Execute Payment - POST /payment/execute

Request body keys:

```json
{
    "paymentId": "{{payment_id}}"
}
```

Response (package normalizes common fields):

```json
{
    "trxID": "trx_123"
}
```

#### Query Payment - POST /query/payment

Request body keys:

```json
{
    "paymentID": "{{payment_id}}"
}
```

Response (raw bKash payload, normalized keys are added when present):

```json
{
    "paymentID": "payment_123",
    "trxID": "trx_123",
    "...": "additional fields may be returned"
}
```

#### Capture Payment - POST /payment/capture

Request body keys:

```json
{
    "paymentId": "{{payment_id}}"
}
```

Response (raw bKash payload, normalized keys are added when present):

```json
{
    "paymentID": "payment_123",
    "trxID": "trx_123",
    "...": "additional fields may be returned"
}
```

#### Void Payment - POST /payment/void

Request body keys:

```json
{
    "paymentId": "{{payment_id}}"
}
```

Response (raw bKash payload, normalized keys are added when present):

```json
{
    "paymentID": "payment_123",
    "trxID": "trx_123",
    "...": "additional fields may be returned"
}
```

### Payment APIs (With Agreement)

#### Create Payment With Agreement - POST /payment-with-agreement/create

Request body keys:

```json
{
    "agreementId": "{{agreement_id}}",
    "callbackURL": "{{callback_url}}",
    "amount": "{{amount}}",
    "intent": "{{intent}}",
    "currency": "{{currency}}",
    "merchantInvoiceNumber": "{{merchant_invoice_number}}"
}
```

Response (package normalizes common fields):

```json
{
    "paymentID": "payment_123",
    "paymentId": "payment_123",
    "agreementID": "agreement_123",
    "agreementId": "agreement_123",
    "bkashURL": "https://tokenized.sandbox.bka.sh/checkout/..."
}
```

#### Execute Payment With Agreement - POST /payment-with-agreement/execute

Request body keys:

```json
{
    "paymentId": "{{payment_id}}",
    "agreementId": "{{agreement_id}}"
}
```

Response (package normalizes common fields):

```json
{
    "trxID": "trx_123"
}
```

#### Capture Payment With Agreement - POST /payment-with-agreement/capture

Request body keys:

```json
{
    "paymentId": "{{payment_id}}",
    "agreementId": "{{agreement_id}}"
}
```

Response (raw bKash payload, normalized keys are added when present):

```json
{
    "paymentID": "payment_123",
    "agreementID": "agreement_123",
    "trxID": "trx_123",
    "...": "additional fields may be returned"
}
```

#### Void Payment With Agreement - POST /payment-with-agreement/void

Request body keys:

```json
{
    "paymentId": "{{payment_id}}",
    "agreementId": "{{agreement_id}}"
}
```

Response (raw bKash payload, normalized keys are added when present):

```json
{
    "paymentID": "payment_123",
    "agreementID": "agreement_123",
    "trxID": "trx_123",
    "...": "additional fields may be returned"
}
```

### Agreement APIs

#### Create Agreement - POST /agreement/create

Request body keys:

```json
{
    "payerReference": "{{payer_reference}}",
    "callbackURL": "{{agreement_callback_url}}"
}
```

Response (raw bKash payload):

```json
{
    "agreementID": "agreement_123",
    "bkashURL": "https://tokenized.sandbox.bka.sh/checkout/...",
    "...": "additional fields may be returned"
}
```

#### Execute Agreement - POST /agreement/execute

Request body keys:

```json
{
    "agreementId": "{{agreement_id}}"
}
```

Response (raw bKash payload):

```json
{
    "agreementID": "agreement_123",
    "...": "additional fields may be returned"
}
```

#### Query Agreement - POST /query/agreement

Request body keys:

```json
{
    "agreementId": "{{agreement_id}}"
}
```

Response (raw bKash payload):

```json
{
    "agreementID": "agreement_123",
    "...": "additional fields may be returned"
}
```

#### Cancel Agreement - POST /agreement/cancel

Request body keys:

```json
{
    "agreementId": "{{agreement_id}}"
}
```

Response (raw bKash payload):

```json
{
    "agreementID": "agreement_123",
    "...": "additional fields may be returned"
}
```

### Transaction and Refund APIs

#### Search Transaction - POST /general/search-transaction

Request body keys:

```json
{
    "trxId": "{{trx_id}}"
}
```

Response (raw bKash payload):

```json
{
    "trxID": "trx_123",
    "...": "additional fields may be returned"
}
```

#### Refund Transaction - POST /refund/payment/transaction

Request body keys:

```json
{
    "refundAmount": "{{refund_amount}}",
    "paymentId": "{{payment_id}}",
    "trxId": "{{trx_id}}",
    "sku": "{{sku}}",
    "reason": "{{reason}}"
}
```

Response (raw bKash payload):

```json
{
    "paymentID": "payment_123",
    "trxID": "trx_123",
    "...": "additional fields may be returned"
}
```

#### Refund Status - POST /refund/payment/status

Request body keys:

```json
{
    "paymentId": "{{payment_id}}",
    "trxId": "{{trx_id}}"
}
```

Response (raw bKash payload):

```json
{
    "paymentID": "payment_123",
    "trxID": "trx_123",
    "...": "additional fields may be returned"
}
```

### Webhook Events (SNS)

Enable the built-in route by setting `BKASH_ENABLE_ROUTES=true`.

Webhook endpoint:

```
POST /bkash/webhook
```

#### SubscriptionConfirmation Event

Headers:

- Content-Type: application/json
- x-amz-sns-message-type: SubscriptionConfirmation

Request body keys:

```json
{
    "Type": "SubscriptionConfirmation",
    "MessageId": "11111111-2222-3333-4444-555555555555",
    "Token": "{{sns_token}}",
    "TopicArn": "{{sns_topic_arn}}",
    "Message": "You have chosen to subscribe to the topic.",
    "SubscribeURL": "{{sns_subscribe_url}}",
    "Timestamp": "2026-04-11T00:00:00.000Z",
    "SignatureVersion": "1",
    "Signature": "{{sns_signature}}",
    "SigningCertURL": "{{sns_signing_cert_url}}"
}
```

Response (from the package webhook service):

```json
{
    "success": true,
    "type": "SubscriptionConfirmation",
    "data": {
        "subscriptionConfirmed": true,
        "subscribeURL": "https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription"
    }
}
```

#### Notification Event

Headers:

- Content-Type: application/json
- x-amz-sns-message-type: Notification

Request body keys:

```json
{
    "Type": "Notification",
    "MessageId": "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
    "TopicArn": "{{sns_topic_arn}}",
    "Subject": "bKash Payment Notification",
    "Message": "{\"debitMSISDN\":\"{{debit_msisdn}}\",\"creditShortCode\":\"{{credit_shortcode}}\",\"amount\":\"{{amount}}\",\"trxID\":\"{{trx_id}}\",\"currency\":\"{{currency}}\",\"dateTime\":\"20260411093030\",\"transactionType\":\"{{transaction_type}}\",\"transactionStatus\":\"{{transaction_status}}\"}",
    "Timestamp": "2026-04-11T00:05:00.000Z",
    "SignatureVersion": "1",
    "Signature": "{{sns_signature}}",
    "SigningCertURL": "{{sns_signing_cert_url}}"
}
```

Response (from the package webhook service):

```json
{
    "success": true,
    "type": "Notification",
    "data": {
        "trxID": "trx_123",
        "trxId": "trx_123",
        "amount": "100.00",
        "transactionStatus": "Completed"
    }
}
```

### Checkout SDK

#### Checkout Script URL - GET

```
https://scripts.{{bkash_sdk_host}}.bka.sh/versions/{{bkash_sdk_version}}/checkout/bKash-checkout{{bkash_sdk_suffix}}.js
```

Response: JavaScript file (200/301/302/304/404).

### Payout

#### Initiate Payout - POST /payout/initiate

Request body keys:

```json
{
    "type": "B2C",
    "Rereference": "{{payer_reference}}"
}
```

Response (raw bKash payload):

```json
{
    "paymentID": "payment_123",
    "...": "additional fields may be returned"
}
```

---

## 💳 Payment Flow

### 1. Create Payment

```php
Bkash::payment()->createPayment([...]);
```

### 2. Handle Callback

```php
Bkash::payment()->handleCallback(request()->all());
```

### 3. Query Payment

```php
Bkash::payment()->queryPayment($paymentId);
```

### 4. Capture / Void

```php
Bkash::payment()->capturePayment($paymentId);
Bkash::payment()->voidPayment($paymentId);
```

---

## 🔄 Agreement (Saved Wallet)

```php
Bkash::agreement()->createAgreement([...]);
Bkash::agreement()->executeAgreement($agreementId);
```

---

## 💰 Refund API

```php
Bkash::refund()->refundTransaction([
    'paymentId' => $paymentId,
    'trxId' => $trxId,
    'refundAmount' => '10',
    'reason' => 'Customer request',
]);
```

---

## 🔔 Webhook (Secure & Automatic)

Enable in config:

```php
'enable_routes' => true,
```

Endpoint:

```
POST /bkash/webhook
```

### Automatically handled:

* SNS Subscription confirmation
* Signature verification
* Replay attack protection
* Notification parsing

---

## 🔐 Why this package?

Most Laravel bKash packages:

* ❌ No token management
* ❌ No webhook security
* ❌ Only basic API wrappers

This package provides:

* ✅ Automatic token lifecycle (grant + refresh)
* ✅ Secure SNS webhook verification
* ✅ Replay attack protection
* ✅ Retry-safe HTTP client
* ✅ Full payment + agreement + refund APIs
* ✅ Clean Laravel integration (Facade + DI)
* ✅ Test coverage

---

## 🧪 Testing

```bash
composer test
```

---

## 📦 Requirements

* PHP 8.1+
* Laravel 10 / 11 / 12

---

## 📈 SEO Keywords

Laravel bKash payment gateway, bKash Laravel package, Bangladesh payment gateway Laravel, bKash Tokenized Checkout V2, Laravel payment integration bKash

---

## 🛠 Roadmap

* [ ] Event system (PaymentSuccess, Failed)
* [ ] Queue-based webhook handling
* [ ] Multi-gateway support (bKash + Nagad + SSLCommerz)

---

## 🤝 Contributing

PRs are welcome. Please ensure tests pass.

---

## 📄 License

MIT License

---

## ⭐ Support

If this package helps you, please give it a star ⭐
It helps others discover the project.

---

## ☕ Support Development

If you find this package useful, you can support ongoing development:

👉 https://buymeacoffee.com/devrkb21

Your support helps maintain and improve this package ❤️
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20a%20Coffee-support-yellow)](https://buymeacoffee.com/devrkb21)
