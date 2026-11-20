# Laravel SMSLive247

A modern client and Laravel Notification Channel driver for the SMSLive247 HTTP gateway.

## Features

- **Standard & Flash SMS**: Dispatch single or batch SMS messages.
- **Phone Number Normalization**: Automatically converts Nigerian numbers (`080...` or `+234...`) into international gateway format (`234...`).
- **Laravel Notification Channel**: First-party support for `toSmsLive247($notifiable)` in notification classes.
- **Balance Inquiries**: Query real-time credit balance directly via the API.
- **Connection Fallback**: Automatic stream context fallback for environments where cURL might encounter proxy or socket restrictions.

## Installation

Install via Composer:

```bash
composer require koderia-creative-lab/laravel-smslive247
```

Publish the package configuration:

```bash
php artisan vendor:publish --tag="smslive247-config"
```

## Environment Configuration

Add the following variables to your `.env` file:

```env
SMSLIVE_BASE_URL=https://smslive247.com/http/index.aspx
SMSLIVE_OWNER_EMAIL=your-account-email@example.com
SMSLIVE_SUBACCOUNT=your_subaccount_login
SMSLIVE_SUBACCOUNT_PASSWORD=your_subaccount_password
SMSLIVE_SENDER_ID=MYAPP
SMSLIVE_TIMEOUT=15
SMSLIVE_LOGGING=true
```

## Basic Usage

### Using the Facade

```php
use Koderia\SmsLive247\Facades\SmsLive247;

// 1. Send an SMS to one or multiple recipients
$response = SmsLive247::send('08012345678', 'Your verification code is 492019', 'APP_NAME');

if ($response->isSuccessful()) {
    $msgId = $response->getMessageId();
} else {
    $error = $response->getErrorMessage();
}

// 2. Send Flash SMS
SmsLive247::sendFlash('08012345678', 'Important emergency alert!');

// 3. Check Account Credit Balance
$balance = SmsLive247::queryBalance();
```

### Using in Laravel Notifications

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Koderia\SmsLive247\Channels\SmsLive247Channel;
use Koderia\SmsLive247\Messages\SmsLive247Message;

class OrderConfirmedNotification extends Notification
{
    public function via($notifiable): array
    {
        return [SmsLive247Channel::class];
    }

    public function toSmsLive247($notifiable): SmsLive247Message
    {
        return SmsLive247Message::create("Hi {$notifiable->name}, your order #{$this->orderId} is confirmed.")
            ->sender('STORE')
            ->flash(false);
    }
}
```

The recipient phone number is automatically resolved from `$notifiable->routeNotificationFor('smslive247')`, `$notifiable->phone_number`, or `$notifiable->phone`.

## Testing

Run unit and integration tests:

```bash
vendor/bin/phpunit
```
