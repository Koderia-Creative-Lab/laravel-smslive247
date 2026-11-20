<?php

namespace Koderia\SmsLive247\DTO;

class SmsResponse
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $rawResponse,
        public readonly ?string $messageId = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $recipients = [],
        public readonly array $meta = []
    ) {}

    public static function success(string $rawResponse, ?string $messageId = null, array $recipients = [], array $meta = []): self
    {
        return new self(
            successful: true,
            rawResponse: $rawResponse,
            messageId: $messageId,
            errorCode: null,
            errorMessage: null,
            recipients: $recipients,
            meta: $meta
        );
    }

    public static function failure(string $rawResponse, ?string $errorMessage = null, ?string $errorCode = null, array $recipients = [], array $meta = []): self
    {
        return new self(
            successful: false,
            rawResponse: $rawResponse,
            messageId: null,
            errorCode: $errorCode,
            errorMessage: $errorMessage ?? $rawResponse,
            recipients: $recipients,
            meta: $meta
        );
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function failed(): bool
    {
        return !$this->successful;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getRawResponse(): string
    {
        return $this->rawResponse;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function toArray(): array
    {
        return [
            'successful'   => $this->successful,
            'raw_response' => $this->rawResponse,
            'message_id'   => $this->messageId,
            'error_code'   => $this->errorCode,
            'error_message'=> $this->errorMessage,
            'recipients'   => $this->recipients,
            'meta'         => $this->meta,
        ];
    }
}
