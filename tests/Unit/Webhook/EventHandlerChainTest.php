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

namespace Storyblok\Bundle\Tests\Unit\Webhook;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Storyblok\Bundle\Tests\Double\ConfigurableHandler;
use Storyblok\Bundle\Webhook\Event;
use Storyblok\Bundle\Webhook\Exception\UnsupportedEventException;
use Storyblok\Bundle\Webhook\Handler\WebhookHandlerInterface;
use Storyblok\Bundle\Webhook\WebhookEventHandlerChain;

final class EventHandlerChainTest extends TestCase
{
    #[Test]
    public function eventIsSupported(): void
    {
        $chain = $this->getChain(new \ArrayIterator([
            new ConfigurableHandler(supported: true),
            new ConfigurableHandler(supported: false),
            new ConfigurableHandler(supported: true),
        ]));

        self::assertTrue($chain->supports(Event::StoryPublished));
    }

    #[Test]
    public function eventIsNotSupported(): void
    {
        $chain = $this->getChain(new \ArrayIterator([
            new ConfigurableHandler(supported: false),
            new ConfigurableHandler(supported: false),
            new ConfigurableHandler(supported: false),
        ]));

        self::assertFalse($chain->supports(Event::StoryPublished));
    }

    #[Test]
    public function handleThrowsUnsupportedEventExceptionWhenNoHandlersAreRegistered(): void
    {
        $chain = $this->getChain(new \ArrayIterator());

        self::expectException(UnsupportedEventException::class);

        $chain->handle(Event::StoryPublished, []);
    }

    #[Test]
    public function handleThrowsUnsupportedEventExceptionWhenNoHandlerIsSupported(): void
    {
        $chain = $this->getChain(new \ArrayIterator([
            new ConfigurableHandler(supported: false),
            new ConfigurableHandler(supported: false),
        ]));

        self::expectException(UnsupportedEventException::class);

        $chain->handle(Event::StoryPublished, []);
    }

    /**
     * @param iterable<WebhookHandlerInterface> $handlers
     */
    public function getChain(iterable $handlers): WebhookEventHandlerChain
    {
        return new WebhookEventHandlerChain($handlers, new NullLogger());
    }
}
