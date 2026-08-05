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
use Storyblok\Bundle\Maker\Property;
use Storyblok\Bundle\Maker\SchemaMapper;
use Storyblok\Bundle\Maker\Type;

final class SchemaMapperTest extends TestCase
{
    #[DataProvider('provideFieldMappings')]
    #[Test]
    public function mapsFieldToType(string $storyblokType, Type $expected): void
    {
        $properties = (new SchemaMapper())->map([
            'field' => ['type' => $storyblokType, 'required' => true, 'pos' => 0],
        ]);

        self::assertCount(1, $properties);
        self::assertSame($expected, $properties[0]->type);
    }

    /**
     * @return iterable<string, array{string, Type}>
     */
    public static function provideFieldMappings(): iterable
    {
        yield 'text' => ['text', Type::String];
        yield 'textarea' => ['textarea', Type::String];
        yield 'markdown' => ['markdown', Type::String];
        yield 'richtext' => ['richtext', Type::RichText];
        yield 'number' => ['number', Type::Float];
        yield 'boolean' => ['boolean', Type::Boolean];
        yield 'datetime' => ['datetime', Type::DateTimeImmutable];
        yield 'asset' => ['asset', Type::Asset];
        yield 'multilink' => ['multilink', Type::MultiLink];
        yield 'link' => ['link', Type::Link];
        yield 'bloks' => ['bloks', Type::Blocks];
    }

    #[Test]
    public function derivesCamelCaseNameAndKeepsKey(): void
    {
        $properties = (new SchemaMapper())->map([
            'hero_title' => ['type' => 'text', 'required' => true],
        ]);

        self::assertSame('hero_title', $properties[0]->key);
        self::assertSame('heroTitle', $properties[0]->name);
    }

    #[Test]
    public function requiredFieldIsNotNullableAndOptionalIs(): void
    {
        $properties = self::byName((new SchemaMapper())->map([
            'required_field' => ['type' => 'text', 'required' => true, 'pos' => 0],
            'optional_field' => ['type' => 'text', 'pos' => 1],
        ]));

        self::assertFalse($properties['requiredField']->nullable);
        self::assertTrue($properties['optionalField']->nullable);
    }

    #[Test]
    public function textFieldNameIsRefinedByGuesser(): void
    {
        $properties = (new SchemaMapper())->map([
            'published_at' => ['type' => 'text'],
        ]);

        self::assertSame(Type::DateTimeImmutable, $properties[0]->type);
    }

    #[Test]
    public function stringMaxLengthIsRead(): void
    {
        $properties = (new SchemaMapper())->map([
            'title' => ['type' => 'text', 'required' => true, 'max_length' => 120],
        ]);

        self::assertSame(120, $properties[0]->maxLength);
    }

    #[Test]
    public function bloksBoundsAreRead(): void
    {
        $properties = (new SchemaMapper())->map([
            'items' => ['type' => 'bloks', 'required' => true, 'minimum' => 1, 'maximum' => 5],
        ]);

        self::assertSame(1, $properties[0]->min);
        self::assertSame(5, $properties[0]->max);
    }

    #[Test]
    public function booleanIsNeverNullableAndEditableAlwaysNullable(): void
    {
        $properties = self::byName((new SchemaMapper())->map([
            'flag' => ['type' => 'boolean', 'pos' => 0],
            'editable' => ['type' => 'text', 'required' => true, 'pos' => 1],
        ]));

        self::assertFalse($properties['flag']->nullable);
    }

    #[Test]
    public function unmappedFieldTypesBecomeUnmappedProperties(): void
    {
        $properties = self::byName((new SchemaMapper())->map([
            'seo' => ['type' => 'custom', 'field_type' => 'seo', 'pos' => 0],
            'category' => ['type' => 'option', 'pos' => 1],
            'gallery' => ['type' => 'multiasset', 'pos' => 2],
        ]));

        self::assertTrue($properties['seo']->isUnmapped());
        self::assertSame('custom', $properties['seo']->storyblokType);
        self::assertTrue($properties['category']->isUnmapped());
        self::assertTrue($properties['gallery']->isUnmapped());
    }

    #[Test]
    public function uiOnlyAndSystemFieldsAreSkipped(): void
    {
        $properties = (new SchemaMapper())->map([
            'a_tab' => ['type' => 'tab', 'pos' => 0],
            'a_section' => ['type' => 'section', 'pos' => 1],
            '_uid' => ['type' => 'text', 'pos' => 2],
            'component' => ['type' => 'text', 'pos' => 3],
            'title' => ['type' => 'text', 'required' => true, 'pos' => 4],
        ]);

        self::assertCount(1, $properties);
        self::assertSame('title', $properties[0]->key);
    }

    #[Test]
    public function fieldsAreOrderedByPosition(): void
    {
        $properties = (new SchemaMapper())->map([
            'third' => ['type' => 'text', 'required' => true, 'pos' => 30],
            'first' => ['type' => 'text', 'required' => true, 'pos' => 10],
            'second' => ['type' => 'text', 'required' => true, 'pos' => 20],
        ]);

        self::assertSame(['first', 'second', 'third'], array_map(static fn (Property $p): string => $p->key, $properties));
    }

    #[Test]
    public function optionFieldWithInlineOptionsBecomesEnum(): void
    {
        $properties = (new SchemaMapper())->map([
            'layout' => ['type' => 'option', 'required' => true, 'options' => [
                ['name' => 'Full width', 'value' => 'full'],
                ['name' => 'Boxed', 'value' => 'boxed'],
            ]],
        ], 'hero');

        $enum = $properties[0]->enum;

        self::assertFalse($properties[0]->nullable);
        self::assertNotNull($enum);
        self::assertSame('HeroLayout', $enum->shortName);
        self::assertSame(['FullWidth' => 'full', 'Boxed' => 'boxed'], $enum->cases);
    }

    #[Test]
    public function optionalOptionFieldBecomesNullableEnum(): void
    {
        $properties = (new SchemaMapper())->map([
            'theme' => ['type' => 'option', 'options' => [['name' => 'Light', 'value' => 'light']]],
        ], 'hero');

        self::assertTrue($properties[0]->isEnum());
        self::assertTrue($properties[0]->nullable);
    }

    #[Test]
    public function optionCaseNameStartingWithDigitIsPrefixed(): void
    {
        $properties = (new SchemaMapper())->map([
            'columns' => ['type' => 'option', 'required' => true, 'options' => [
                ['name' => '2 Columns', 'value' => 'two'],
            ]],
        ], 'hero');

        $enum = $properties[0]->enum;

        self::assertNotNull($enum);
        self::assertSame(['_2Columns' => 'two'], $enum->cases);
    }

    #[Test]
    public function bloksFieldRestrictedToSingleComponentUsesTypedList(): void
    {
        $properties = (new SchemaMapper())->map([
            'items' => ['type' => 'bloks', 'required' => true, 'minimum' => 3, 'maximum' => 5, 'component_whitelist' => ['text_with_bullets_item']],
        ], 'text_with_bullets');

        self::assertSame('TextWithBulletsItem', $properties[0]->itemClass);
        self::assertSame(3, $properties[0]->min);
        self::assertSame(5, $properties[0]->max);
    }

    #[Test]
    public function bloksFieldWithMultipleComponentsHasNoItemClass(): void
    {
        $properties = (new SchemaMapper())->map([
            'body' => ['type' => 'bloks', 'component_whitelist' => ['a', 'b']],
        ]);

        self::assertNull($properties[0]->itemClass);
    }

    #[Test]
    public function unrestrictedBloksFieldHasNoItemClass(): void
    {
        $properties = (new SchemaMapper())->map([
            'body' => ['type' => 'bloks'],
        ]);

        self::assertNull($properties[0]->itemClass);
    }

    #[Test]
    public function optionCaseNameFallsBackToValueWhenNameIsMissing(): void
    {
        $properties = (new SchemaMapper())->map([
            'kind' => ['type' => 'option', 'required' => true, 'options' => [
                ['value' => 'primary'],
            ]],
        ], 'hero');

        $enum = $properties[0]->enum;

        self::assertNotNull($enum);
        self::assertSame(['Primary' => 'primary'], $enum->cases);
    }

    #[Test]
    public function optionWithoutUsableOptionsIsUnmapped(): void
    {
        $properties = (new SchemaMapper())->map([
            'kind' => ['type' => 'option', 'options' => []],
        ], 'hero');

        self::assertTrue($properties[0]->isUnmapped());
        self::assertSame('option', $properties[0]->storyblokType);
    }

    #[Test]
    public function datasourceBackedOptionFieldIsUnmapped(): void
    {
        $properties = (new SchemaMapper())->map([
            'country' => ['type' => 'option', 'source' => 'internal_stories'],
        ], 'hero');

        self::assertTrue($properties[0]->isUnmapped());
    }

    /**
     * @param list<Property> $properties
     *
     * @return array<string, Property>
     */
    private static function byName(array $properties): array
    {
        $indexed = [];

        foreach ($properties as $property) {
            $indexed[$property->name] = $property;
        }

        return $indexed;
    }
}
