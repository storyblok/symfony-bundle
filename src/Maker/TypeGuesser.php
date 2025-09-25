<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Maker;

/**
 * @internal
 */
final readonly class TypeGuesser
{
    /**
     * @var array<non-empty-string, Type>
     */
    private const array STARTS_WITH = [
        'is' => Type::Boolean,
        'has' => Type::Boolean,
    ];

    /**
     * @var array<non-empty-string, Type>
     */
    private const array ENDS_WITH = [
        'At' => Type::DateTimeImmutable,
        'Time' => Type::DateTimeImmutable,
        'time' => Type::DateTimeImmutable,
        'able' => Type::Boolean,
        'Id' => Type::Uuid,
        'id' => Type::Uuid,
    ];

    /**
     * @var array<non-empty-string, Type>
     */
    private const array CONTAINS = [
        'description' => Type::RichText,
        'content' => Type::RichText,
        'body' => Type::Blocks,
    ];

    public static function guessType(string $propertyName): Type
    {
        foreach (self::STARTS_WITH as $needle => $type) {
            if (\str_starts_with($propertyName, $needle)) {
                return $type;
            }
        }

        foreach (self::CONTAINS as $needle => $type) {
            if (\str_contains($propertyName, $needle)) {
                return $type;
            }
        }

        foreach (self::ENDS_WITH as $needle => $type) {
            if (\str_ends_with($propertyName, $needle)) {
                return $type;
            }
        }

        return Type::String;
    }

    private function __construct()
    {
    }
}
