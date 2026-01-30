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

namespace Storyblok\Bundle\Tests\Unit\Routing;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Routing\Route;

final class RouteTest extends TestCase
{
    #[Test]
    public function constants(): void
    {
        self::assertSame('storyblok_webhook', Route::WEBHOOK);
        self::assertSame('storyblok_content_type', Route::CONTENT_TYPE);
        self::assertSame('storyblok_cdn', Route::CDN);
    }

    #[Test]
    public function numberOfConstants(): void
    {
        $reflection = new \ReflectionClass(Route::class);

        self::assertCount(3, $reflection->getConstants());
    }
}
