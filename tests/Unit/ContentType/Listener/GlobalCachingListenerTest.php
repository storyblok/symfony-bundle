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
use Safe\DateTimeImmutable;
use Storyblok\Bundle\ContentType\ContentTypeStorage;
use Storyblok\Bundle\ContentType\Listener\GlobalCachingListener;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Tests\Util\TestKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

final class GlobalCachingListenerTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function requestWithExistingCacheDirectiveWillNotModifyResponse(): void
    {
        $listener = new GlobalCachingListener(new ContentTypeStorage());
        $request = new Request();
        $request->attributes->set('_cache', ['public' => true, 'maxage' => 3600]);

        $event = new ResponseEvent(
            TestKernel::create([], self::class, static fn () => ''),
            $request,
            KernelInterface::MAIN_REQUEST,
            $response = new Response(),
        );

        $listener($event);

        self::assertTrue($event->getResponse()->headers->has('Cache-Control'));
        self::assertSame('no-cache, private', $event->getResponse()->headers->get('Cache-Control'));
    }

    #[Test]
    public function publicCacheDirective(): void
    {
        $listener = new GlobalCachingListener(new ContentTypeStorage(), public: true);

        $event = new ResponseEvent(
            TestKernel::create([], self::class, static fn () => ''),
            new Request(),
            KernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $listener($event);

        self::assertTrue($event->getResponse()->headers->has('Cache-Control'));
        self::assertSame('public', $event->getResponse()->headers->get('Cache-Control'));
    }

    #[Test]
    public function privateCacheDirective(): void
    {
        $listener = new GlobalCachingListener(new ContentTypeStorage(), public: false);

        $event = new ResponseEvent(
            TestKernel::create([], self::class, static fn () => ''),
            new Request(),
            KernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $listener($event);

        self::assertTrue($event->getResponse()->headers->has('Cache-Control'));
        self::assertSame('private', $event->getResponse()->headers->get('Cache-Control'));
    }

    #[Test]
    public function mustRevalidateCacheDirective(): void
    {
        $listener = new GlobalCachingListener(new ContentTypeStorage(), mustRevalidate: true);

        $event = new ResponseEvent(
            TestKernel::create([], self::class, static fn () => ''),
            new Request(),
            KernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $listener($event);

        self::assertTrue($event->getResponse()->headers->has('Cache-Control'));
        self::assertSame('must-revalidate, private', $event->getResponse()->headers->get('Cache-Control'));
    }

    #[Test]
    public function mustRevalidateSetsLastModifiedHeader(): void
    {
        $expected = new DateTimeImmutable();

        $storage = new ContentTypeStorage();
        $storage->setContentType(new SampleContentType(['publishedAt' => $expected->format(\DATE_ATOM)]));

        $listener = new GlobalCachingListener($storage, mustRevalidate: true);

        $event = new ResponseEvent(
            TestKernel::create([], self::class, static fn () => ''),
            new Request(),
            KernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $listener($event);

        self::assertTrue($event->getResponse()->headers->has('Cache-Control'));
        self::assertSame('must-revalidate, private', $event->getResponse()->headers->get('Cache-Control'));
        self::assertSame($expected->format(\DATE_RFC7231), $event->getResponse()->headers->get('Last-Modified'));
    }

    #[Test]
    public function maxageCacheDirective(): void
    {
        $listener = new GlobalCachingListener(new ContentTypeStorage(), maxAge: 4000);

        $event = new ResponseEvent(
            TestKernel::create([], self::class, static fn () => ''),
            new Request(),
            KernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $listener($event);

        self::assertTrue($event->getResponse()->headers->has('Cache-Control'));
        self::assertSame('max-age=4000, private', $event->getResponse()->headers->get('Cache-Control'));
    }

    #[Test]
    public function smaxageCacheDirective(): void
    {
        $listener = new GlobalCachingListener(new ContentTypeStorage(), smaxAge: 3600);

        $event = new ResponseEvent(
            TestKernel::create([], self::class, static fn () => ''),
            new Request(),
            KernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $listener($event);

        self::assertTrue($event->getResponse()->headers->has('Cache-Control'));
        self::assertSame('public, s-maxage=3600', $event->getResponse()->headers->get('Cache-Control'));
    }
}
