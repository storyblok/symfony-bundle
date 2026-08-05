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

namespace Storyblok\Bundle\Tests\Unit\Maker;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Maker\Type;
use Storyblok\Bundle\Maker\TypeGuesser;

final class TypeGuesserTest extends TestCase
{
    #[DataProvider('provideGuessCases')]
    #[Test]
    public function guess(string $propertyName, Type $expected): void
    {
        self::assertSame($expected, TypeGuesser::guess($propertyName));
    }

    /**
     * @return iterable<string, array{string, Type}>
     */
    public static function provideGuessCases(): iterable
    {
        yield 'plain text stays string' => ['title', Type::String];
        yield 'ends with At is datetime' => ['publishedAt', Type::DateTimeImmutable];
        yield 'ends with Time is datetime' => ['startTime', Type::DateTimeImmutable];
        yield 'ends with Id is uuid' => ['authorId', Type::Uuid];
        yield 'ends with Uuid is uuid' => ['storyUuid', Type::Uuid];
        yield 'starts with is is boolean' => ['isActive', Type::Boolean];
        yield 'starts with has is boolean' => ['hasImage', Type::Boolean];
        yield 'island is not boolean' => ['island', Type::String];
    }

    #[Test]
    public function guessUsesGivenDefault(): void
    {
        self::assertSame(Type::RichText, TypeGuesser::guess('teaser', Type::RichText));
    }
}
