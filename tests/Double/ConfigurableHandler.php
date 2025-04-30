<?php

declare(strict_types=1);

/**
 * This file is part of storyblok/symfony-bundle.
 *
 * (c) Storyblok GmbH <info@storyblok.com>
 * in cooperation with SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\Tests\Double;

use Storyblok\Bundle\Webhook\Event;
use Storyblok\Bundle\Webhook\Handler\WebhookHandlerInterface;

final class ConfigurableHandler implements WebhookHandlerInterface
{
    public function __construct(
        private bool $supported,
        private bool $throwException = false,
    ) {
    }

    public function handle(Event $event, array $payload): void
    {
        if ($this->throwException) {
            throw new \Exception('Test exception');
        }
    }

    public function supports(Event $event): bool
    {
        return $this->supported;
    }

    public static function priority(): int
    {
        return 0;
    }
}
