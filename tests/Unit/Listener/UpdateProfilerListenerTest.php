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

namespace Storyblok\Bundle\Tests\Unit\Listener;

use PHPUnit\Framework\Attributes\Test;
use Storyblok\Bundle\Listener\UpdateProfilerListener;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Tests\Util\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

final class UpdateProfilerListenerTest extends KernelTestCase
{
    use FakerTrait;

    #[Test]
    public function onKernelUpdate(): void
    {
        $kernel = self::createKernel([
            'debug' => true,
        ]);

        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = new Response();

        self::assertNull($response->headers->get('Symfony-Debug-Toolbar-Replace'));

        $event = new ResponseEvent(
            $kernel,
            $request,
            200,
            $response,
        );

        $listener = new UpdateProfilerListener($kernel);
        $listener->onKernelResponse($event);

        self::assertSame('1', $response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    #[Test]
    public function onKernelUpdateWhenRequestIsNotXmlHttpRequest(): void
    {
        $kernel = self::createKernel([
            'debug' => true,
        ]);

        $request = new Request();
        $response = new Response();

        self::assertNull($response->headers->get('Symfony-Debug-Toolbar-Replace'));

        $event = new ResponseEvent(
            $kernel,
            $request,
            200,
            $response,
        );

        $listener = new UpdateProfilerListener($kernel);
        $listener->onKernelResponse($event);

        self::assertNull($response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    #[Test]
    public function onKernelUpdateWhenDebugIsFalse(): void
    {
        $kernel = self::createKernel([
            'debug' => false,
        ]);

        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $response = new Response();

        self::assertNull($response->headers->get('Symfony-Debug-Toolbar-Replace'));

        $event = new ResponseEvent(
            $kernel,
            $request,
            200,
            $response,
        );

        $listener = new UpdateProfilerListener($kernel);
        $listener->onKernelResponse($event);

        self::assertNull($response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return TestKernel::create(
            $options,
            self::class,
            static function (ContainerBuilder $containerBuilder): void {
                $containerBuilder->prependExtensionConfig('storyblok', [
                    'base_uri' => 'https://api.storyblok.com/v1',
                    'token' => 't3$t',
                ]);
            },
        );
    }
}
