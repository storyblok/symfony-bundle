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

namespace Storyblok\Bundle\Tests\Unit\Controller;

use Monolog\Test\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Storyblok\Bundle\Controller\WebhookController;
use Storyblok\Bundle\Tests\Double\ConfigurableHandler;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Webhook\Event;
use Storyblok\Bundle\Webhook\WebhookEventHandlerChain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WebhookControllerTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function missingXStoryblokTopicHeaderReturns400(): void
    {
        $logger = new NullLogger();
        $request = new Request();

        $controller = new WebhookController($logger, new WebhookEventHandlerChain(new \ArrayIterator(), $logger));

        $response = $controller->__invoke($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    #[Test]
    public function invalidXStoryblokTopicHeaderReturns400(): void
    {
        $logger = new NullLogger();
        $request = new Request();
        $request->headers->set('x-storyblok-topic', self::faker()->word);

        $controller = new WebhookController($logger, new WebhookEventHandlerChain(new \ArrayIterator(), $logger));

        $response = $controller->__invoke($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    #[Test]
    public function eventNotSupportedReturns200(): void
    {
        $logger = new NullLogger();
        $request = new Request();
        $request->headers->set('x-storyblok-topic', self::faker()->randomElement(Event::cases())->value);

        $controller = new WebhookController($logger, new WebhookEventHandlerChain(new \ArrayIterator(), $logger));

        $response = $controller->__invoke($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function eventSupportedButHandlerThrowsExceptionReturns200(): void
    {
        $logger = new NullLogger();
        $request = new Request();
        $request->headers->set('x-storyblok-topic', self::faker()->randomElement(Event::cases())->value);

        $controller = new WebhookController(
            $logger,
            new WebhookEventHandlerChain(new \ArrayIterator([new ConfigurableHandler(supported: true, throwException: true)]), $logger),
        );

        $response = $controller->__invoke($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function eventSupportedReturns200(): void
    {
        $logger = new NullLogger();
        $request = new Request();
        $request->headers->set('x-storyblok-topic', self::faker()->randomElement(Event::cases())->value);

        $controller = new WebhookController(
            $logger,
            new WebhookEventHandlerChain(new \ArrayIterator([new ConfigurableHandler(true)]), $logger),
        );

        $response = $controller->__invoke($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function signingWebhookWithMissingWebhookSignatureHeaderReturns400(): void
    {
        $logger = new NullLogger();
        $request = new Request();
        $request->headers->set('x-storyblok-topic', self::faker()->randomElement(Event::cases())->value);

        $controller = new WebhookController(
            $logger,
            new WebhookEventHandlerChain(new \ArrayIterator([new ConfigurableHandler(true)]), $logger),
            self::faker()->word(),
        );

        $response = $controller->__invoke($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    #[Test]
    public function signingWebhookWithInvalidWebhookSignatureHeaderReturns401(): void
    {
        $logger = new NullLogger();
        $request = new Request();
        $request->headers->set('x-storyblok-topic', self::faker()->randomElement(Event::cases())->value);
        $request->headers->set('webhook-signature', self::faker()->word());

        $controller = new WebhookController(
            $logger,
            new WebhookEventHandlerChain(new \ArrayIterator([new ConfigurableHandler(true)]), $logger),
            self::faker()->word(),
        );

        $response = $controller->__invoke($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    #[Test]
    public function validSigningWebhookReturns200(): void
    {
        $content = '[]';

        $logger = new NullLogger();
        $request = new Request(content: $content);
        $secret = self::faker()->word();

        $request->headers->set('x-storyblok-topic', self::faker()->randomElement(Event::cases())->value);
        $request->headers->set('webhook-signature', hash_hmac('sha1', $content, $secret));

        $controller = new WebhookController(
            $logger,
            new WebhookEventHandlerChain(new \ArrayIterator([new ConfigurableHandler(true)]), $logger),
            $secret,
        );

        $response = $controller->__invoke($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
