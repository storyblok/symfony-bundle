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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Routing\Requirement;

final class RequirementTest extends TestCase
{
    #[Test]
    #[DataProvider('slugs')]
    public function slugRegexMatches(string $value): void
    {
        self::assertMatchesRegularExpression(\sprintf('#%s#u', Requirement::SLUG), $value);
    }

    /**
     * @return iterable<string, string[]>
     */
    public static function slugs(): iterable
    {
        yield 'dash' => ['valid-slug'];
        yield 'only dash' => ['/-/test'];
        yield 'underscore' => ['valid_slug'];
        yield 'only underscore' => ['/_/test'];
        yield 'complete path with underscores' => ['my/path_to/a_valid_slug'];
        yield 'complete path with dashes' => ['my/path-to/a-valid-slug'];
        yield 'with trailing slash' => ['my/test/'];
        yield 'with leading slash' => ['/my/test'];
        yield 'cyrillic characters' => ['тест/привіт-світ'];
        yield 'japanese characters' => ['こんにちは世界'];
    }
}
