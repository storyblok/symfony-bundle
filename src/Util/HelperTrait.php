<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Util;

use OskarStark\Value\TrimmedNonEmptyString;
use Safe\DateTimeImmutable;
use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Api\Domain\Type\Editable;
use Storyblok\Api\Domain\Type\MultiLink;
use Storyblok\Api\Domain\Type\RichText;
use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Block\Exception\BlockNotFoundException;
use Tiptap\Editor;
use Webmozart\Assert\Assert;

trait HelperTrait
{
    /**
     * @template T of object
     *
     * @param array<mixed>     $values
     * @param non-empty-string $key
     * @param class-string<T>  $class
     *
     * @return T
     */
    final protected static function one(array $values, string $key, string $class): object
    {
        Assert::keyExists($values, $key);
        Assert::isList($values[$key]);
        Assert::count($values[$key], 1);

        return new $class($values[$key][0]);
    }

    /**
     * @template T of object
     *
     * @param array<mixed>    $values
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    final protected static function list(array $values, string $key, string $class, ?int $min = null, ?int $max = null, ?int $count = null): array
    {
        if (null !== $count && (null !== $min || null !== $max)) {
            throw new \InvalidArgumentException('You can not use $count with $min or $max.');
        }

        if (null !== $count) {
            Assert::keyExists($values, $key);
            Assert::count($values[$key], $count);
        }

        if (null !== $min) {
            Assert::keyExists($values, $key);
            Assert::minCount($values[$key], $min);
        } else {
            if (!\array_key_exists($key, $values)) {
                return [];
            }
        }

        Assert::isList($values[$key]);

        if (null !== $max) {
            Assert::maxCount($values[$key], $max);
        }

        return array_map(static fn (array $item) => new $class($item), $values[$key]);
    }

    /**
     * @template T of \BackedEnum
     *
     * @param array<mixed>     $values
     * @param non-empty-string $key
     * @param class-string<T>  $class
     * @param null|T           $default
     * @param null|array<T>    $allowedSubset Only some cases of the enum that are allowed
     *
     * @return T
     */
    final protected static function enum(array $values, string $key, string $class, ?\BackedEnum $default = null, ?array $allowedSubset = null): \BackedEnum
    {
        Assert::keyExists($values, $key);

        if (!enum_exists($class)) {
            throw new \InvalidArgumentException(\sprintf('The class "%s" is not an enum.', $class));
        }

        try {
            $enum = $class::from($values[$key]);

            if (\is_array($allowedSubset)
                && [] !== $allowedSubset
            ) {
                if (!method_exists($enum, 'equalsOneOf')) {
                    throw new \InvalidArgumentException(\sprintf(
                        'The enum "%s" does not have the method "equalsOneOf", but an allowed subset is defined.',
                        $class,
                    ));
                }

                Assert::true($enum->equalsOneOf($allowedSubset));
            }

            return $enum;
        } catch (\ValueError $e) {
            if (null !== $default) {
                return $default;
            }

            throw $e;
        }
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function DateTimeImmutable(array $values, string $key, ?\DateTimeZone $timezone = null): DateTimeImmutable
    {
        Assert::keyExists($values, $key);

        return new DateTimeImmutable($values[$key], $timezone);
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function Uuid(array $values, string $key): Uuid
    {
        return new Uuid(self::string($values, $key));
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function MultiLink(array $values, string $key): MultiLink
    {
        Assert::keyExists($values, $key);
        Assert::isArray($values[$key]);

        return new MultiLink($values[$key]);
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function Asset(array $values, string $key): Asset
    {
        Assert::keyExists($values, $key);
        Assert::isArray($values[$key]);

        return new Asset($values[$key]);
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function RichText(array $values, string $key): RichText
    {
        Assert::keyExists($values, $key);
        Assert::isArray($values[$key]);

        return new RichText($values[$key]);
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function nullOrRichText(array $values, string $key): ?RichText
    {
        $value = null;

        if (\array_key_exists($key, $values)
            && (null !== $values[$key] && [] !== $values[$key])
        ) {
            Assert::isArray($values[$key]);
            $value = new RichText($values[$key]);

            $text = \trim((new Editor())->setContent($value->toArray())->getText());

            if ('' === $text) {
                $value = null;
            }
        }

        return $value;
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function zeroOrInteger(array $values, string $key): int
    {
        $value = 0;

        if (\array_key_exists($key, $values)
            && [] !== $values[$key]
        ) {
            $value = (int) $values[$key];
        }

        return $value;
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function zeroOrFloat(array $values, string $key): float
    {
        $value = 0.0;

        if (\array_key_exists($key, $values) && [] !== $values[$key]) {
            $value = (float) $values[$key];
        }

        return $value;
    }

    /**
     * @template T of object
     *
     * @param array<mixed>     $values
     * @param non-empty-string $key
     * @param class-string<T>  $class
     *
     * @return null|T
     */
    final protected static function nullOrOne(array $values, string $key, string $class): ?object
    {
        if (\array_key_exists($key, $values)
            && \count($values[$key]) > 0
        ) {
            Assert::count($values[$key], 1);
            Assert::isList($values[$key]);

            return new $class($values[$key][0]);
        }

        return null;
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function boolean(array $values, string $key): bool
    {
        $value = false;

        if (\array_key_exists($key, $values)) {
            $value = true === $values[$key];
        }

        return $value;
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function nullOrAsset(array $values, string $key): ?Asset
    {
        if (!\array_key_exists($key, $values)) {
            return null;
        }

        try {
            return new Asset($values[$key]);
        } catch (\InvalidArgumentException|\TypeError) {
            return null;
        }
    }

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function nullOrMultiLink(array $values, string $key): ?MultiLink
    {
        if (!\array_key_exists($key, $values)) {
            return null;
        }

        $linkValues = $values[$key];

        // If the link url and id are empty, we return null
        if ((!isset($linkValues['url']) || '' === trim($linkValues['url']))
            && (!isset($linkValues['id']) || '' === trim($linkValues['id']))
        ) {
            return null;
        }

        try {
            return new MultiLink($linkValues);
        } catch (\InvalidArgumentException|\TypeError) {
            return null;
        }
    }

    /**
     * Returns null if the value is not set or a trimmed non-empty string.
     *
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function nullOrString(array $values, string $key): ?string
    {
        if (!\array_key_exists($key, $values)) {
            return null;
        }

        try {
            return TrimmedNonEmptyString::from($values[$key])->toString();
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Returns a trimmed non-empty string.
     *
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function string(array $values, string $key, ?int $maxLength = null): string
    {
        Assert::keyExists($values, $key);

        if (null !== $maxLength) {
            Assert::maxLength($values[$key], $maxLength);
        }

        return TrimmedNonEmptyString::fromString($values[$key])->toString();
    }

    /**
     * Returns null if the value is not set or an Editable.
     *
     * @param array<mixed>     $values
     * @param non-empty-string $key
     */
    final protected static function nullOrEditable(array $values, string $key): ?Editable
    {
        if (!\array_key_exists($key, $values)) {
            return null;
        }

        try {
            return new Editable($values[$key]);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @template T of object
     *
     * @param array<mixed>     $values
     * @param non-empty-string $key
     *
     * @return list<T>
     */
    final protected static function Blocks(array $values, string $key, ?int $min = null, ?int $max = null, ?int $count = null): array
    {
        if (null !== $count && (null !== $min || null !== $max)) {
            throw new \InvalidArgumentException('You can not use $count with $min or $max.');
        }

        if (null !== $count) {
            Assert::keyExists($values, $key);
            Assert::count($values[$key], $count);
        }

        if (null !== $min) {
            Assert::keyExists($values, $key);
            Assert::minCount($values[$key], $min);
        } else {
            if (!\array_key_exists($key, $values)) {
                return [];
            }
        }

        Assert::isList($values[$key]);

        if (null !== $max) {
            Assert::maxCount($values[$key], $max);
        }

        Assert::allKeyExists($values[$key], 'component');

        $blocks = [];

        foreach ($values[$key] as $value) {
            try {
                $blocks[] = new (BlockRegistry::byName($value['component'])->className)($value);
            } catch (BlockNotFoundException) {
                // Ignore the block if it is not found to not raise an exception and interrupt the construction.
            }
        }

        return $blocks;
    }
}
