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

namespace Storyblok\Bundle\Webhook;

use Psr\Log\LoggerInterface;
use Storyblok\Bundle\Webhook\Exception\UnsupportedEventException;
use Storyblok\Bundle\Webhook\Handler\WebhookHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final readonly class WebhookEventHandlerChain
{
    /**
     * @var list<WebhookHandlerInterface>
     */
    private array $handlers;

    /**
     * @param iterable<WebhookHandlerInterface> $handlers
     */
    public function __construct(
        #[AutowireIterator(tag: WebhookHandlerInterface::class, defaultPriorityMethod: 'priority')]
        iterable $handlers,
        private LoggerInterface $logger,
    ) {
        $this->handlers = iterator_to_array($handlers, false);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(Event $event, array $payload): void
    {
        $handlers = $this->getHandlers($event);

        $this->logger->debug(
            \sprintf('Event %s has %s supported handlers.', $event->value, \count($handlers)),
            array_map(static fn (WebhookHandlerInterface $handler): string => $handler::class, $handlers),
        );

        if ([] === $handlers) {
            throw new UnsupportedEventException(\sprintf('Event "%s" is not supported.', $event->value));
        }

        foreach ($handlers as $handler) {
            $this->logger->info(\sprintf('Event %s handled by %s', $event->value, $handler::class));

            $handler->handle($event, $payload);
        }
    }

    public function supports(Event $event): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($event)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<WebhookHandlerInterface>
     */
    private function getHandlers(Event $event): array
    {
        return array_values(array_filter(
            $this->handlers,
            static fn (WebhookHandlerInterface $handler) => $handler->supports($event),
        ));
    }
}
