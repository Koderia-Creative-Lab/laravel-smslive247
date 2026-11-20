<?php

namespace Koderia\SmsLive247\Messages;

class SmsLive247Message
{
    protected string $content = '';
    protected ?string $sender = null;
    protected int $msgType = 0; // 0 = Standard SMS, 1 = Flash SMS

    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    public static function create(string $content = ''): self
    {
        return new self($content);
    }

    public function content(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function sender(string $sender): self
    {
        $this->sender = substr(trim($sender), 0, 11);
        return $this;
    }

    public function from(string $sender): self
    {
        return $this->sender($sender);
    }

    public function flash(bool $isFlash = true): self
    {
        $this->msgType = $isFlash ? 1 : 0;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function getMsgType(): int
    {
        return $this->msgType;
    }
}
