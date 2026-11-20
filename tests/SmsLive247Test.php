<?php

namespace Koderia\SmsLive247\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Koderia\SmsLive247\Channels\SmsLive247Channel;
use Koderia\SmsLive247\Messages\SmsLive247Message;
use Koderia\SmsLive247\SmsLive247Client;
use Tests\TestCase;

class SmsLive247Test extends TestCase
{
    protected SmsLive247Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new SmsLive247Client([
            'base_url'            => 'https://smslive247.com/http/index.aspx',
            'owner_email'         => 'test@example.com',
            'subaccount'          => 'sub_test',
            'subaccount_password' => 'secret123',
            'default_sender'      => 'TAU_TEST',
            'timeout'             => 5,
            'logging'             => false,
        ]);
    }

    public function test_formats_nigerian_phone_numbers_correctly(): void
    {
        $this->assertEquals('2348012345678', $this->client->formatPhoneNumber('08012345678'));
        $this->assertEquals('2348012345678', $this->client->formatPhoneNumber('+2348012345678'));
        $this->assertEquals('2348012345678', $this->client->formatPhoneNumber('2348012345678'));
        $this->assertEquals('2347039525735', $this->client->formatPhoneNumber('07039525735'));
        $this->assertEquals('2347039525735', $this->client->formatPhoneNumber(' 0703-952-5735 '));
    }

    public function test_sanitizes_recipients_array_and_comma_string(): void
    {
        $fromArray = $this->client->sanitizeRecipients(['08012345678', '07039525735', '']);
        $this->assertEquals(['2348012345678', '2347039525735'], $fromArray);

        $fromString = $this->client->sanitizeRecipients('08012345678, 07039525735');
        $this->assertEquals(['2348012345678', '2347039525735'], $fromString);
    }

    public function test_send_sms_successful(): void
    {
        Http::fake([
            'https://smslive247.com/*' => Http::response('OK: 99887766', 200),
        ]);

        $response = $this->client->send('08012345678', 'Your security code is 123456', 'MY_APP');

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->failed());
        $this->assertEquals('99887766', $response->getMessageId());
        $this->assertContains('2348012345678', $response->recipients);
    }

    public function test_send_sms_handles_gateway_error(): void
    {
        Http::fake([
            'https://smslive247.com/*' => Http::response('ERR: 101: Invalid Credentials', 200),
        ]);

        $response = $this->client->send('08012345678', 'Test message');

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->failed());
        $this->assertEquals('ERR: 101: Invalid Credentials', $response->getErrorMessage());
    }

    public function test_query_balance_returns_float_value(): void
    {
        Http::fake([
            'https://smslive247.com/*' => Http::response('OK: 450.75', 200),
        ]);

        $balance = $this->client->queryBalance();

        $this->assertNotNull($balance);
        $this->assertEquals(450.75, $balance);
    }

    public function test_empty_recipient_returns_failure_early(): void
    {
        $response = $this->client->send('', 'No recipient message');

        $this->assertTrue($response->failed());
        $this->assertEquals('EMPTY_RECIPIENTS', $response->errorCode);
    }

    public function test_notification_channel_dispatches_successfully(): void
    {
        Http::fake([
            'https://smslive247.com/*' => Http::response('OK: 44332211', 200),
        ]);

        $channel = new SmsLive247Channel($this->client);

        $notifiable = new class {
            use Notifiable;
            public string $phone_number = '08098765432';
        };

        $notification = new class extends Notification {
            public function toSmsLive247($notifiable): SmsLive247Message
            {
                return SmsLive247Message::create('Your order has shipped!')
                    ->sender('LOGISTICS');
            }
        };

        $result = $channel->send($notifiable, $notification);

        $this->assertNotNull($result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('44332211', $result->getMessageId());
    }
}
