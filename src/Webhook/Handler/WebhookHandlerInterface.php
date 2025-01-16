<?php

declare(strict_types=1);

/**
 * This file is part of sensiolabs-de/storyblok-bundle.
 *
 * (c) SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\Webhook\Handler;

use Storyblok\Bundle\Webhook\Event;

interface WebhookHandlerInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function handle(Event $event, array $payload): void;

    public function supports(Event $event): bool;

    public static function priority(): int;
}
