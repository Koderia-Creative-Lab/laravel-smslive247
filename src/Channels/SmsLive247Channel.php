<?php

namespace Koderia\SmsLive247\Channels;

use Illuminate\Notifications\Notification;
use Koderia\SmsLive247\Messages\SmsLive247Message;
use Koderia\SmsLive247\SmsLive247Client;
use Koderia\SmsLive247\DTO\SmsResponse;

class SmsLive247Channel
{
    public function __construct(protected SmsLive247Client $client) {}

    /**
     * Send the given notification via SMSLive247.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return SmsResponse|null
     */
    public function send(mixed $notifiable, Notification $notification): ?SmsResponse
    {
        $message = null;

        if (method_exists($notification, 'toSmsLive247')) {
            $message = $notification->toSmsLive247($notifiable);
        } elseif (method_exists($notification, 'toSms')) {
            $message = $notification->toSms($notifiable);
        }

        if (!$message) {
            return null;
        }

        $to = $notifiable->routeNotificationFor('smslive247', $notification)
            ?? $notifiable->routeNotificationFor('sms', $notification)
            ?? $notifiable->phone_number
            ?? $notifiable->phone
            ?? $notifiable->mobile;

        if (empty($to)) {
            return null;
        }

        if (is_string($message)) {
            $message = SmsLive247Message::create($message);
        }

        if (!($message instanceof SmsLive247Message)) {
            return null;
        }

        return $this->client->send(
            recipients: $to,
            message: $message->getContent(),
            sender: $message->getSender(),
            msgType: $message->getMsgType()
        );
    }
}
