<?php

namespace Devture\Bundle\NagiosBundle\Notification;

use Devture\Component\SmsSender\Gateway\BulkSmsGateway;
use Devture\Component\SmsSender\Gateway\GatewayInterface;
use Devture\Component\SmsSender\Gateway\NexmoGateway;
use Devture\Component\SmsSender\Gateway\ProSmsGateway;

/**
 * Builds the configured SMS gateway. Mirrors the gateway selection the legacy
 * ServicesProvider did from the `notifications.sms` configuration block.
 */
class SmsGatewayFactory
{
    public static function create(string $gatewayName, string $username, string $password): GatewayInterface
    {
        if ($gatewayName === '') {
            throw new \LogicException('Trying to use an SMS sender, but no SMS gateway is configured.');
        }

        return match ($gatewayName) {
            'nexmo' => new NexmoGateway($username, $password),
            'bulksms' => new BulkSmsGateway($username, $password),
            'prosms' => new ProSmsGateway($username, $password),
            default => throw new \InvalidArgumentException(sprintf('Cannot find SMS gateway: %s', $gatewayName)),
        };
    }
}
