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

namespace Storyblok\Bundle\Tests\Unit\ContentType;

use Ergebnis\DataProvider\StringProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\ContentType\UnicodeSlug;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class UnicodeSlugTest extends TestCase
{
    use FakerTrait;

    #[DataProvider('slugs')]
    #[Test]
    public function value(string $expected, string $value): void
    {
        self::assertSame($expected, (new UnicodeSlug($value))->toString());
        self::assertSame($expected, (new UnicodeSlug($value))->__toString());
        self::assertSame($expected, (string) new UnicodeSlug($value));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function slugs(): iterable
    {
        yield 'single word' => ['hello', 'hello'];
        yield 'with slash prefix' => ['hello', '/hello'];
        yield 'with trailing slash' => ['hello', 'hello/'];
        yield 'with dash' => ['hello-world', 'hello-world'];
        yield 'with underscore' => ['hello_world', 'hello_world'];
        yield 'with cyrillic characters' => ['%D0%BF%D1%80%D0%B8%D0%B2%D1%96%D1%82-%D1%81%D0%B2%D1%96%D1%82', 'привіт-світ'];
        yield 'with cyrillic nested path' => ['%D0%BC%D1%96%D0%B9-%D0%B1%D0%BB%D0%BE%D0%B3/%D0%BF%D1%80%D0%B8%D0%B2%D1%96%D1%82-%D1%81%D0%B2%D1%96%D1%82', 'мій-блог/привіт-світ'];
        yield 'with mandarin characters' => ['%E4%BD%A0%E5%A5%BD%E4%B8%96%E7%95%8C', '你好世界'];
        yield 'with hindi characters' => ['%E0%A4%B9%E0%A5%88%E0%A4%B2%E0%A5%8B-%E0%A4%B5%E0%A4%B0%E0%A5%8D%E0%A4%B2%E0%A5%8D%E0%A4%A1', 'हैलो-वर्ल्ड'];
    }

    #[DataProviderExternal(StringProvider::class, 'blank')]
    #[DataProviderExternal(StringProvider::class, 'empty')]
    #[Test]
    public function invalid(string $value): void
    {
        self::expectException(\InvalidArgumentException::class);

        new UnicodeSlug($value);
    }
}
