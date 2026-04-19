# 💸 Laravel bKash Tokenized Checkout V2

Production-ready Laravel package for integrating **bKash Tokenized Checkout V2 API**.

> Built for real-world usage — not just API wrapping.

---

## ✨ Features

* 🔐 Automatic token management (grant + refresh)
* 🔁 Safe retry mechanism (no duplicate payment risk)
* 💳 Full payment flow (create, execute, query, capture, void)
* 🔄 Agreement (saved wallet) support
* 🧾 Refund & transaction APIs
* 🔔 Secure SNS webhook handling (signature verified)
* 🧪 Fully tested (Auth, Payment, Webhook)
* ⚡ Clean Laravel integration (Facade + DI)

---

## 🚀 Installation

```bash
composer require devrkb21/bkash-pgw-laravel
```

---

## ⚙️ Configuration

Publish config:

```bash
php artisan vendor:publish --tag=bkash-config
```

Update `.env`:

```env
BKASH_ENV=sandbox

BKASH_APP_KEY=
BKASH_APP_SECRET=
BKASH_USERNAME=
BKASH_PASSWORD=

BKASH_TIMEOUT=30
BKASH_CACHE=true
```

---

## ⚡ Quick Start

```php
use Bkash;

$response = Bkash::payment()->createPayment([
    'payerReference' => 'user_123',
    'callbackURL' => route('bkash.callback'),
    'amount' => '100',
    'currency' => 'BDT',
    'intent' => 'sale',
    'merchantInvoiceNumber' => 'INV123456',
]);

return redirect($response->bkash_url);
```

---

## 💳 Payment Flow

### 1. Create Payment

```php
$response = Bkash::payment()->createPayment([...]);
```

---

### 2. Execute Payment (Callback)

```php
$result = Bkash::payment()->handleCallback(request()->all());
```

---

### 3. Query Payment

```php
Bkash::payment()->queryPayment($paymentId);
```

---

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

## 💰 Refund

```php
Bkash::refund()->refundTransaction([
    'paymentId' => $paymentId,
    'trxId' => $trxId,
    'refundAmount' => '10',
    'reason' => 'Customer request',
]);
```

---

## 🔔 Webhook Setup (IMPORTANT)

Enable routes in config:

```php
'enable_routes' => true,
```

Endpoint:

```bash
POST /bkash/webhook
```

### What’s handled automatically:

* SNS Subscription confirmation
* Signature verification
* Replay attack protection
* Notification parsing

---

## 🔐 Security

* ✅ Token never exposed in logs
* ✅ SNS signature fully verified
* ✅ Replay attack protection
* ✅ Strict certificate validation

---

## 🧪 Testing

```bash
composer test
```

---

## 🤔 Why this package?

Most bKash packages:

* ❌ No token lifecycle handling
* ❌ No webhook security
* ❌ Basic API wrappers

This package provides:

* ✅ Production-ready architecture
* ✅ Secure webhook verification
* ✅ Retry-safe HTTP client
* ✅ Clean Laravel integration

---

## 📦 Requirements

* PHP 8.1+
* Laravel 10/11/12

---

## 🛠 Roadmap

* [ ] Multi-gateway support (bKash + Nagad + SSLCommerz)
* [ ] Event system (PaymentSuccess, Failed)
* [ ] Queue-based webhook processing

---

## 🤝 Contributing

PRs are welcome. Please ensure tests pass before submitting.

---

## 📄 License

MIT License

---

## ⭐ Support

If this package helps you, consider giving it a star ⭐
