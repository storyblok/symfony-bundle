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

namespace Storyblok\Bundle\Maker;

use function Symfony\Component\String\u;

/**
 * Maps a Storyblok component schema to the list of block {@see Property properties}
 * the maker should generate.
 *
 * @internal
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class SchemaMapper
{
    /**
     * Storyblok field types that only structure the editor UI and carry no value.
     */
    private const array UI_ONLY_TYPES = ['tab', 'section'];

    /**
     * Schema keys injected by Storyblok that are handled by the bundle itself.
     */
    private const array SYSTEM_KEYS = ['_uid', 'component', '_editable'];

    /**
     * @param array<string, array<mixed>> $schema the raw component schema, keyed by field name
     *
     * @return list<Property>
     */
    public function map(array $schema): array
    {
        \uasort(
            $schema,
            static fn (array $a, array $b): int => (int) ($a['pos'] ?? 0) <=> (int) ($b['pos'] ?? 0),
        );

        $properties = [];

        foreach ($schema as $key => $field) {
            if (\in_array($key, self::SYSTEM_KEYS, true)) {
                continue;
            }

            $storyblokType = \is_string($field['type'] ?? null) ? $field['type'] : '';

            if (\in_array($storyblokType, self::UI_ONLY_TYPES, true)) {
                continue;
            }

            $property = self::mapField((string) $key, $storyblokType, $field);

            if (null !== $property) {
                $properties[] = $property;
            }
        }

        return $properties;
    }

    /**
     * @param array<mixed> $field
     */
    private static function mapField(string $key, string $storyblokType, array $field): ?Property
    {
        $name = u($key)->camel()->toString();

        if ('' === $name) {
            return null;
        }

        $required = true === ($field['required'] ?? false);
        $type = self::guessType($storyblokType, $name);

        if (null === $type) {
            return new Property(key: $key, name: $name, storyblokType: '' !== $storyblokType ? $storyblokType : null);
        }

        $nullable = !$required;

        if ($type->forcesNullable()) {
            $nullable = true;
        } elseif ($type->forcesNonNullable()) {
            $nullable = false;
        }

        return new Property(
            key: $key,
            name: $name,
            type: $type,
            nullable: $nullable,
            maxLength: Type::String === $type ? self::intOrNull($field['max_length'] ?? null) : null,
            min: Type::Blocks === $type ? self::intOrNull($field['minimum'] ?? null) : null,
            max: Type::Blocks === $type ? self::intOrNull($field['maximum'] ?? null) : null,
        );
    }

    private static function guessType(string $storyblokType, string $name): ?Type
    {
        return match ($storyblokType) {
            'text', 'textarea', 'markdown' => TypeGuesser::guess($name),
            'richtext' => Type::RichText,
            'number' => Type::Float,
            'boolean' => Type::Boolean,
            'datetime' => Type::DateTimeImmutable,
            'asset' => Type::Asset,
            'multilink' => Type::MultiLink,
            'link' => Type::Link,
            'bloks' => Type::Blocks,
            default => null,
        };
    }

    private static function intOrNull(mixed $value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value) && '' !== $value && \ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
