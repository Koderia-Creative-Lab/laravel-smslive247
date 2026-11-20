<?php

namespace Koderia\SmsLive247;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Koderia\SmsLive247\DTO\SmsResponse;
use Throwable;

class SmsLive247Client
{
    protected string $baseUrl;
    protected ?string $ownerEmail;
    protected ?string $subaccount;
    protected ?string $subaccountPassword;
    protected string $defaultSender;
    protected int $timeout;
    protected bool $logging;

    public function __construct(array $config = [])
    {
        $this->baseUrl = (string) ($config['base_url'] ?? 'https://smslive247.com/http/index.aspx');
        $this->ownerEmail = $config['owner_email'] ?? null;
        $this->subaccount = $config['subaccount'] ?? null;
        $this->subaccountPassword = $config['subaccount_password'] ?? null;
        $this->defaultSender = (string) ($config['default_sender'] ?? 'SMS');
        $this->timeout = (int) ($config['timeout'] ?? 15);
        $this->logging = (bool) ($config['logging'] ?? true);
    }

    /**
     * Format a Nigerian or international phone number into standard international format.
     * e.g., '08012345678' -> '2348012345678'
     *       '+2348012345678' -> '2348012345678'
     */
    public function formatPhoneNumber(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', trim($phone));
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '234' . substr($digits, 1);
        }
        return $digits;
    }

    /**
     * Format an array or comma-delimited string of recipients.
     *
     * @param string|array $recipients
     * @return array<string>
     */
    public function sanitizeRecipients(string|array $recipients): array
    {
        if (is_string($recipients)) {
            $recipients = explode(',', $recipients);
        }

        $formatted = array_map(fn($r) => $this->formatPhoneNumber((string) $r), $recipients);
        return array_values(array_filter($formatted, fn($r) => !empty($r)));
    }

    /**
     * Send standard SMS text message.
     *
     * @param string|array $recipients
     * @param string $message
     * @param string|null $sender
     * @param int $msgType 0 = Text, 1 = Flash SMS
     * @return SmsResponse
     */
    public function send(
        string|array $recipients,
        string $message,
        ?string $sender = null,
        int $msgType = 0
    ): SmsResponse {
        $cleanRecipients = $this->sanitizeRecipients($recipients);

        if (empty($cleanRecipients)) {
            $this->logWarning("SMSLive247: skipped sending, recipient list is empty.");
            return SmsResponse::failure(
                rawResponse: '',
                errorMessage: 'No valid recipient phone numbers provided.',
                errorCode: 'EMPTY_RECIPIENTS'
            );
        }

        $sendTo = implode(',', $cleanRecipients);
        $senderId = substr(trim($sender ?? $this->defaultSender), 0, 11);
        $url = rtrim($this->baseUrl, '?');

        $params = [
            'cmd'        => 'sendquickmsg',
            'owneremail' => $this->ownerEmail,
            'subacct'    => $this->subaccount,
            'subacctpwd' => $this->subaccountPassword,
            'message'    => $message,
            'sender'     => $senderId,
            'sendto'     => $sendTo,
            'msgtype'    => $msgType,
        ];

        // 1. Primary HTTP dispatch via Laravel Http client
        try {
            $response = Http::timeout($this->timeout)
                ->withoutVerifying()
                ->get($url, $params);

            $body = trim((string) $response->body());

            if (str_starts_with($body, 'OK')) {
                $this->logInfo("SMSLive247 sent successfully to [{$sendTo}]. Response: {$body}");
                $messageId = $this->extractMessageId($body);

                return SmsResponse::success(
                    rawResponse: $body,
                    messageId: $messageId,
                    recipients: $cleanRecipients
                );
            }

            $this->logWarning("SMSLive247 returned error for [{$sendTo}]: {$body}");
            return SmsResponse::failure(
                rawResponse: $body,
                errorMessage: $body,
                recipients: $cleanRecipients
            );
        } catch (Throwable $e) {
            $this->logWarning("SMSLive247 HTTP client error ({$e->getMessage()}), attempting stream fallback...");

            // 2. Fallback stream execution for restrictive hosting environments
            return $this->streamFallback($url, $params, $cleanRecipients, $sendTo);
        }
    }

    /**
     * Send Flash SMS message.
     */
    public function sendFlash(string|array $recipients, string $message, ?string $sender = null): SmsResponse
    {
        return $this->send($recipients, $message, $sender, 1);
    }

    /**
     * Query account balance from SMSLive247.
     */
    public function queryBalance(): ?float
    {
        $url = rtrim($this->baseUrl, '?');
        $params = [
            'cmd'        => 'querybalance',
            'owneremail' => $this->ownerEmail,
            'subacct'    => $this->subaccount,
            'subacctpwd' => $this->subaccountPassword,
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withoutVerifying()
                ->get($url, $params);

            $body = trim((string) $response->body());
            if (str_starts_with($body, 'OK')) {
                // Response format: 'OK: 1250.50' or 'OK: 1250'
                $parts = explode(':', $body, 2);
                return isset($parts[1]) ? (float) trim($parts[1]) : 0.0;
            }

            $this->logWarning("SMSLive247 queryBalance error: {$body}");
            return null;
        } catch (Throwable $e) {
            $this->logWarning("SMSLive247 queryBalance failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Stream context fallback in case cURL is unavailable or blocked.
     */
    protected function streamFallback(string $url, array $params, array $recipients, string $sendTo): SmsResponse
    {
        try {
            $query = http_build_query($params);
            $streamUrl = $url . (str_contains($url, '?') ? '&' : '?') . $query;

            $ctx = stream_context_create([
                'http' => [
                    'timeout' => $this->timeout,
                    'follow_location' => 1,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $handle = @fopen($streamUrl, 'r', false, $ctx);
            if ($handle) {
                $body = trim((string) fgets($handle, 512));
                fclose($handle);

                if (str_starts_with($body, 'OK')) {
                    $this->logInfo("SMSLive247 fallback sent successfully to [{$sendTo}]. Response: {$body}");
                    $messageId = $this->extractMessageId($body);

                    return SmsResponse::success(
                        rawResponse: $body,
                        messageId: $messageId,
                        recipients: $recipients
                    );
                }

                $this->logWarning("SMSLive247 stream fallback returned error for [{$sendTo}]: {$body}");
                return SmsResponse::failure(
                    rawResponse: $body,
                    errorMessage: $body,
                    recipients: $recipients
                );
            }
        } catch (Throwable $ex) {
            $this->logWarning("SMSLive247 stream fallback failed: " . $ex->getMessage());
        }

        return SmsResponse::failure(
            rawResponse: '',
            errorMessage: 'Gateway connection failed across both HTTP client and stream fallback.',
            errorCode: 'CONNECTION_FAILED',
            recipients: $recipients
        );
    }

    protected function extractMessageId(string $responseBody): ?string
    {
        // Example: 'OK: 987654321'
        if (preg_match('/OK:\s*([A-Za-z0-9_-]+)/i', $responseBody, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function logInfo(string $message): void
    {
        if ($this->logging) {
            Log::info($message);
        }
    }

    protected function logWarning(string $message): void
    {
        if ($this->logging) {
            Log::warning($message);
        }
    }
}
