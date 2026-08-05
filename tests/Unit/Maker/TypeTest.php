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
use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Api\Domain\Type\Editable;
use Storyblok\Api\Domain\Type\MultiLink;
use Storyblok\Api\Domain\Type\RichText;
use Storyblok\Api\Domain\Value\Link;
use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Bundle\Maker\Type;

final class TypeTest extends TestCase
{
    #[DataProvider('provideRequiredExpressions')]
    #[Test]
    public function requiredExpression(Type $type, string $expected): void
    {
        self::assertSame($expected, $type->expression('field', false));
    }

    /**
     * @return iterable<string, array{Type, string}>
     */
    public static function provideRequiredExpressions(): iterable
    {
        yield 'string' => [Type::String, "self::string(\$values, 'field')"];
        yield 'richtext' => [Type::RichText, "self::RichText(\$values, 'field')"];
        yield 'integer' => [Type::Integer, "self::zeroOrInteger(\$values, 'field')"];
        yield 'float' => [Type::Float, "self::zeroOrFloat(\$values, 'field')"];
        yield 'boolean' => [Type::Boolean, "self::boolean(\$values, 'field')"];
        yield 'datetime' => [Type::DateTimeImmutable, "self::DateTimeImmutable(\$values, 'field')"];
        yield 'asset' => [Type::Asset, "self::Asset(\$values, 'field')"];
        yield 'multilink' => [Type::MultiLink, "self::MultiLink(\$values, 'field')"];
        yield 'link' => [Type::Link, "self::Link(\$values, 'field')"];
        yield 'uuid' => [Type::Uuid, "self::Uuid(\$values, 'field')"];
        yield 'editable' => [Type::Editable, "self::nullOrEditable(\$values, 'field')"];
        yield 'blocks' => [Type::Blocks, "self::Blocks(\$values, 'field')"];
    }

    #[DataProvider('provideNullableExpressions')]
    #[Test]
    public function nullableExpression(Type $type, string $expected): void
    {
        self::assertSame($expected, $type->expression('field', true));
    }

    /**
     * @return iterable<string, array{Type, string}>
     */
    public static function provideNullableExpressions(): iterable
    {
        yield 'string' => [Type::String, "self::nullOrString(\$values, 'field')"];
        yield 'richtext' => [Type::RichText, "self::nullOrRichText(\$values, 'field')"];
        yield 'asset' => [Type::Asset, "self::nullOrAsset(\$values, 'field')"];
        yield 'multilink' => [Type::MultiLink, "self::nullOrMultiLink(\$values, 'field')"];
        yield 'editable' => [Type::Editable, "self::nullOrEditable(\$values, 'field')"];
        yield 'boolean stays non-null' => [Type::Boolean, "self::boolean(\$values, 'field')"];
        yield 'integer' => [Type::Integer, "\\array_key_exists('field', \$values) ? self::zeroOrInteger(\$values, 'field') : null"];
        yield 'float' => [Type::Float, "\\array_key_exists('field', \$values) ? self::zeroOrFloat(\$values, 'field') : null"];
        yield 'datetime' => [Type::DateTimeImmutable, "\\array_key_exists('field', \$values) && '' !== \$values['field'] ? self::DateTimeImmutable(\$values, 'field') : null"];
        yield 'uuid' => [Type::Uuid, "\\array_key_exists('field', \$values) && '' !== \$values['field'] ? self::Uuid(\$values, 'field') : null"];
        yield 'link' => [Type::Link, "\\array_key_exists('field', \$values) ? self::Link(\$values, 'field') : null"];
    }

    #[DataProvider('provideTypehints')]
    #[Test]
    public function typehint(Type $type, string $expected): void
    {
        self::assertSame($expected, $type->typehint());
    }

    /**
     * @return iterable<string, array{Type, string}>
     */
    public static function provideTypehints(): iterable
    {
        yield 'string' => [Type::String, 'string'];
        yield 'richtext' => [Type::RichText, 'RichText'];
        yield 'integer' => [Type::Integer, 'int'];
        yield 'float' => [Type::Float, 'float'];
        yield 'boolean' => [Type::Boolean, 'bool'];
        yield 'datetime' => [Type::DateTimeImmutable, '\DateTimeImmutable'];
        yield 'asset' => [Type::Asset, 'Asset'];
        yield 'multilink' => [Type::MultiLink, 'MultiLink'];
        yield 'link' => [Type::Link, 'Link'];
        yield 'uuid' => [Type::Uuid, 'Uuid'];
        yield 'editable' => [Type::Editable, 'Editable'];
        yield 'blocks' => [Type::Blocks, 'array'];
    }

    #[Test]
    public function stringExpressionUsesMaxLength(): void
    {
        self::assertSame("self::string(\$values, 'field', 255)", Type::String->expression('field', false, 255));
        self::assertSame("self::nullOrString(\$values, 'field', 255)", Type::String->expression('field', true, 255));
    }

    #[Test]
    public function blocksExpressionUsesBounds(): void
    {
        self::assertSame("self::Blocks(\$values, 'field', 1, 5)", Type::Blocks->expression('field', false, null, 1, 5));
        self::assertSame("self::Blocks(\$values, 'field', null, 5)", Type::Blocks->expression('field', false, null, null, 5));
        self::assertSame("self::Blocks(\$values, 'field', 2, null)", Type::Blocks->expression('field', false, null, 2, null));
    }

    #[DataProvider('provideUseStatements')]
    #[Test]
    public function useStatement(Type $type, ?string $expected): void
    {
        self::assertSame($expected, $type->useStatement());
    }

    /**
     * @return iterable<string, array{Type, null|class-string}>
     */
    public static function provideUseStatements(): iterable
    {
        yield 'richtext' => [Type::RichText, RichText::class];
        yield 'asset' => [Type::Asset, Asset::class];
        yield 'multilink' => [Type::MultiLink, MultiLink::class];
        yield 'link' => [Type::Link, Link::class];
        yield 'uuid' => [Type::Uuid, Uuid::class];
        yield 'editable' => [Type::Editable, Editable::class];
        yield 'string has none' => [Type::String, null];
        yield 'datetime has none' => [Type::DateTimeImmutable, null];
        yield 'blocks has none' => [Type::Blocks, null];
    }
}
