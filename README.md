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

BKASH_APP_KEY=
BKASH_APP_SECRET=
BKASH_USERNAME=
BKASH_PASSWORD=
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