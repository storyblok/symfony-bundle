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

namespace Storyblok\Bundle\Tests\Unit\ContentType\Listener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Api\Response\StoryResponse;
use Storyblok\Api\StoriesApiInterface;
use Storyblok\Bundle\ContentType\Exception\StoryNotFoundException;
use Storyblok\Bundle\ContentType\Listener\StoryNotFoundExceptionListener;
use Storyblok\Bundle\Routing\Route;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Tests\Util\TestKernel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class StoryNotFoundExceptionListenerTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function redirectToParentRoute(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::once())
            ->method('bySlug')
            ->willReturn(new StoryResponse([
                'story' => [],
                'cv' => 0,
                'rels' => [],
                'links' => [],
            ]));

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(Route::CONTENT_TYPE, ['slug' => 'path/to/parent'])
            ->willReturn('/path/to/parent');

        $listener = new StoryNotFoundExceptionListener($api, $urlGenerator, 'draft');

        $request = new Request();
        $request->attributes->set('_route', Route::CONTENT_TYPE);
        $request->attributes->set('_route_params', ['slug' => 'path/to/parent/page-which-does-not-exist']);

        $listener($event = new ExceptionEvent(
            TestKernel::create([], self::class, static fn () => ''),
            $request,
            KernelInterface::MAIN_REQUEST,
            new StoryNotFoundException(),
        ));

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertSame(Response::HTTP_FOUND, $event->getResponse()->getStatusCode());
    }

    #[Test]
    public function willThrowStoryNotFoundException(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::once())
            ->method('bySlug')
            ->willThrowException(new \InvalidArgumentException());

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects(self::never())
            ->method('generate');

        $listener = new StoryNotFoundExceptionListener($api, $urlGenerator, 'draft');

        $slug = 'path/to/parent/page-which-does-not-exist';

        $request = new Request();
        $request->attributes->set('_route', Route::CONTENT_TYPE);
        $request->attributes->set('_route_params', ['slug' => $slug]);

        self::expectException(StoryNotFoundException::class);
        self::expectExceptionMessage(\sprintf('Story with slug "%s" not found.', $slug));

        $listener(new ExceptionEvent(
            TestKernel::create([], self::class, static fn () => ''),
            $request,
            KernelInterface::MAIN_REQUEST,
            new StoryNotFoundException(),
        ));
    }

    #[Test]
    public function willThrowStoryNotFoundExceptionWhenLimitReached(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::never())
            ->method('bySlug');

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects(self::never())
            ->method('generate');

        $listener = new StoryNotFoundExceptionListener($api, $urlGenerator, 'draft');

        $slug = 'root-page';

        $request = new Request();
        $request->attributes->set('_route', Route::CONTENT_TYPE);
        $request->attributes->set('_route_params', ['slug' => $slug]);

        self::expectException(StoryNotFoundException::class);
        self::expectExceptionMessage(\sprintf('Story with slug "%s" not found.', $slug));

        $listener(new ExceptionEvent(
            TestKernel::create([], self::class, static fn () => ''),
            $request,
            KernelInterface::MAIN_REQUEST,
            new StoryNotFoundException(),
        ));
    }

    #[Test]
    public function isNotMainRequest(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::never())
            ->method('bySlug');

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects(self::never())
            ->method('generate');

        $listener = new StoryNotFoundExceptionListener($api, $urlGenerator, 'draft');

        $listener(new ExceptionEvent(
            TestKernel::create([], self::class, static fn () => ''),
            new Request(),
            KernelInterface::SUB_REQUEST,
            new StoryNotFoundException(),
        ));
    }

    #[Test]
    public function isNotStoryNotFoundException(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::never())
            ->method('bySlug');

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects(self::never())
            ->method('generate');

        $listener = new StoryNotFoundExceptionListener($api, $urlGenerator, 'draft');

        $listener(new ExceptionEvent(
            TestKernel::create([], self::class, static fn () => ''),
            new Request(),
            KernelInterface::MAIN_REQUEST,
            new \InvalidArgumentException(),
        ));
    }

    #[Test]
    public function routeAttributeIsNotContentTypeRoute(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::never())
            ->method('bySlug');

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects(self::never())
            ->method('generate');

        $listener = new StoryNotFoundExceptionListener($api, $urlGenerator, 'draft');

        $request = new Request();
        $request->attributes->set('_route', Route::WEBHOOK);

        $listener(new ExceptionEvent(
            TestKernel::create([], self::class, static fn () => ''),
            $request,
            KernelInterface::MAIN_REQUEST,
            new StoryNotFoundException(),
        ));
    }

    #[Test]
    public function routeParamsAttributeDoesNotContainSlug(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::never())
            ->method('bySlug');

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects(self::never())
            ->method('generate');

        $listener = new StoryNotFoundExceptionListener($api, $urlGenerator, 'draft');

        $request = new Request();
        $request->attributes->set('_route', Route::CONTENT_TYPE);
        $request->attributes->set('_route_params', []);

        $listener(new ExceptionEvent(
            TestKernel::create([], self::class, static fn () => ''),
            $request,
            KernelInterface::MAIN_REQUEST,
            new StoryNotFoundException(),
        ));
    }
}
