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

/**
 * Refines the {@see Type} of free-text Storyblok fields (text / textarea) based on
 * naming conventions, e.g. a "published_at" text field becomes a DateTimeImmutable.
 *
 * @internal
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class TypeGuesser
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
        'Id' => Type::Uuid,
        'Uuid' => Type::Uuid,
    ];

    private function __construct()
    {
    }

    /**
     * @param string $propertyName the camelCase property name
     */
    public static function guess(string $propertyName, Type $default = Type::String): Type
    {
        foreach (self::STARTS_WITH as $needle => $type) {
            if (\str_starts_with($propertyName, $needle) && \ucfirst(\substr($propertyName, \strlen($needle))) === \substr($propertyName, \strlen($needle))) {
                return $type;
            }
        }

        foreach (self::ENDS_WITH as $needle => $type) {
            if (\str_ends_with($propertyName, $needle)) {
                return $type;
            }
        }

        return $default;
    }
}
