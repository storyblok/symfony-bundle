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

namespace Storyblok\Bundle\Tests\Unit\ContentType\Listener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Storyblok\Api\Response\StoryResponse;
use Storyblok\Api\StoriesApiInterface;
use Storyblok\Bundle\ContentType\ContentTypeControllerDefinition;
use Storyblok\Bundle\ContentType\ContentTypeControllerRegistry;
use Storyblok\Bundle\ContentType\ContentTypeStorage;
use Storyblok\Bundle\ContentType\Exception\InvalidStoryException;
use Storyblok\Bundle\ContentType\Exception\StoryNotFoundException;
use Storyblok\Bundle\ContentType\Listener\ResolveControllerListener;
use Storyblok\Bundle\Routing\Route;
use Storyblok\Bundle\Tests\Double\ContentType\FailingContentType;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Double\Controller\SampleController;
use Storyblok\Bundle\Tests\Double\Controller\SampleWithSlugController;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Tests\Util\TestKernel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelInterface;

final class ResolveControllerListenerTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function resolvesController(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::once())
            ->method('bySlug')
            ->willReturn(new StoryResponse([
                'story' => [
                    'content' => [
                        'component' => SampleContentType::type(),
                    ],
                    'default_full_slug' => SampleWithSlugController::SLUG,
                ],
                'cv' => 0,
                'rels' => [],
                'links' => [],
            ]));

        $container = new Container();
        $container->set(SampleController::class, new SampleController());

        $registry = new ContentTypeControllerRegistry();
        $registry->add(new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, 'sample_content_type'));

        $storage = new ContentTypeStorage();

        $listener = new ResolveControllerListener($api, $container, $registry, $storage, new NullLogger(), 'draft');

        $request = new Request();
        $request->attributes->set('_route', Route::CONTENT_TYPE);
        $request->attributes->set('_route_params', ['slug' => self::faker()->slug()]);

        $listener($event = new ControllerEvent(
            TestKernel::create([], self::class, static fn () => ''),
            static fn () => '',
            $request,
            KernelInterface::MAIN_REQUEST,
        ));

        self::assertSame(SampleController::class, $event->getController()::class);
        self::assertSame(SampleContentType::class, $request->attributes->get('_storyblok_content_type'));
        self::assertNotNull($storage->getContentType());
    }

    #[Test]
    public function resolvesControllerBySlug(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::once())
            ->method('bySlug')
            ->willReturn(new StoryResponse([
                'story' => [
                    'content' => [
                        'component' => SampleContentType::type(),
                    ],
                    'default_full_slug' => SampleWithSlugController::SLUG,
                ],
                'cv' => 0,
                'rels' => [],
                'links' => [],
            ]));

        $container = new Container();
        $container->set(SampleController::class, new SampleController());
        $container->set(SampleWithSlugController::class, new SampleWithSlugController());

        $registry = new ContentTypeControllerRegistry();
        $registry->add(new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, 'sample_content_type'));
        $registry->add(new ContentTypeControllerDefinition(SampleWithSlugController::class, SampleContentType::class, 'sample_content_type', '/'.SampleWithSlugController::SLUG));

        $storage = new ContentTypeStorage();

        $listener = new ResolveControllerListener($api, $container, $registry, $storage, new NullLogger(), 'draft');

        $request = new Request();
        $request->attributes->set('_route', Route::CONTENT_TYPE);
        $request->attributes->set('_route_params', ['slug' => SampleWithSlugController::SLUG]);

        $listener($event = new ControllerEvent(
            TestKernel::create([], self::class, static fn () => ''),
            static fn () => '',
            $request,
            KernelInterface::MAIN_REQUEST,
        ));

        self::assertSame(SampleWithSlugController::class, $event->getController()::class);
        self::assertSame(SampleContentType::class, $request->attributes->get('_storyblok_content_type'));
        self::assertNotNull($storage->getContentType());
    }

    #[Test]
    public function isNotMainRequest(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::never())
            ->method('bySlug');

        $listener = new ResolveControllerListener($api, new Container(), new ContentTypeControllerRegistry(), $storage = new ContentTypeStorage(), new NullLogger(), 'draft');

        $listener($event = new ControllerEvent(
            TestKernel::create([], self::class, static fn () => ''),
            static fn () => '',
            $request = new Request(),
            KernelInterface::SUB_REQUEST,
        ));

        self::assertSame('Closure', $event->getController()::class);
        self::assertFalse($request->attributes->has('_storyblok_content_type'));
        self::assertNull($storage->getContentType());
    }

    #[Test]
    public function routeAttributeIsNotContentTypeRoute(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::never())
            ->method('bySlug');

        $listener = new ResolveControllerListener($api, new Container(), new ContentTypeControllerRegistry(), $storage = new ContentTypeStorage(), new NullLogger(), 'draft');

        $request = new Request();
        $request->attributes->set('_route', Route::WEBHOOK);

        $listener($event = new ControllerEvent(
            TestKernel::create([], self::class, static fn () => ''),
            static fn () => '',
            $request,
            KernelInterface::MAIN_REQUEST,
        ));

        self::assertSame('Closure', $event->getController()::class);
        self::assertFalse($request->attributes->has('_storyblok_content_type'));
        self::assertNull($storage->getContentType());
    }

    #[Test]
    public function routeParamsAttributeHasNoSlug(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::never())
            ->method('bySlug');

        $listener = new ResolveControllerListener($api, new Container(), new ContentTypeControllerRegistry(), $storage = new ContentTypeStorage(), new NullLogger(), 'draft');

        $request = new Request();
        $request->attributes->set('_route', Route::CONTENT_TYPE);
        $request->attributes->set('_route_params', []);

        $listener($event = new ControllerEvent(
            TestKernel::create([], self::class, static fn () => ''),
            static fn () => '',
            $request,
            KernelInterface::MAIN_REQUEST,
        ));

        self::assertSame('Closure', $event->getController()::class);
        self::assertFalse($request->attributes->has('_storyblok_content_type'));
        self::assertNull($storage->getContentType());
    }

    #[Test]
    public function bySlugThrowsException(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::once())
            ->method('bySlug')
            ->willThrowException(new \InvalidArgumentException());

        $listener = new ResolveControllerListener($api, new Container(), new ContentTypeControllerRegistry(), new ContentTypeStorage(), new NullLogger(), 'draft');

        $request = new Request();
        $request->attributes->set('_route', Route::CONTENT_TYPE);
        $request->attributes->set('_route_params', ['slug' => self::faker()->slug()]);

        self::expectException(StoryNotFoundException::class);

        $listener(new ControllerEvent(
            TestKernel::create([], self::class, static fn () => ''),
            static fn () => '',
            $request,
            KernelInterface::MAIN_REQUEST,
        ));
    }

    #[Test]
    public function resolvesControllerThrowsInvalidStoryExceptionWhenContentTypeCanNotBeConstructed(): void
    {
        $api = self::createMock(StoriesApiInterface::class);
        $api->expects(self::once())
            ->method('bySlug')
            ->willReturn(new StoryResponse([
                'story' => [
                    'content' => [
                        'component' => SampleContentType::type(),
                    ],
                    'default_full_slug' => SampleWithSlugController::SLUG,
                ],
                'cv' => 0,
                'rels' => [],
                'links' => [],
            ]));

        $container = new Container();
        $container->set(SampleController::class, new SampleController());

        $registry = new ContentTypeControllerRegistry();
        $registry->add(new ContentTypeControllerDefinition(SampleController::class, FailingContentType::class, 'sample_content_type'));

        $storage = new ContentTypeStorage();

        $listener = new ResolveControllerListener($api, $container, $registry, $storage, new NullLogger(), 'draft');

        $request = new Request();
        $request->attributes->set('_route', Route::CONTENT_TYPE);
        $request->attributes->set('_route_params', ['slug' => self::faker()->slug()]);

        self::expectException(InvalidStoryException::class);

        $listener(new ControllerEvent(
            TestKernel::create([], self::class, static fn () => ''),
            static fn () => '',
            $request,
            KernelInterface::MAIN_REQUEST,
        ));
    }

    #[Test]
    public function noop(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('This method should never be called. This method is only used for the route definition.');

        ResolveControllerListener::noop();
    }
}
