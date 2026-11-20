<?php

namespace Koderia\SmsLive247\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Koderia\SmsLive247\DTO\SmsResponse send(string|array $recipients, string $message, ?string $sender = null, int $msgType = 0)
 * @method static \Koderia\SmsLive247\DTO\SmsResponse sendFlash(string|array $recipients, string $message, ?string $sender = null)
 * @method static ?float queryBalance()
 * @method static string formatPhoneNumber(string $phone)
 * @method static array sanitizeRecipients(string|array $recipients)
 *
 * @see \Koderia\SmsLive247\SmsLive247Client
 */
class SmsLive247 extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'smslive247';
    }
}
