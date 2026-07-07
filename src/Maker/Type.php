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

use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Api\Domain\Type\Editable;
use Storyblok\Api\Domain\Type\MultiLink;
use Storyblok\Api\Domain\Type\RichText;
use Storyblok\Api\Domain\Value\Link;
use Storyblok\Api\Domain\Value\Uuid;

/**
 * The set of PHP types the maker can generate for a block property, together with
 * the matching {@see \Storyblok\Bundle\Util\ValueObjectTrait} factory calls.
 *
 * The enum values are internal identifiers only; the mapping from Storyblok field
 * types happens in {@see SchemaMapper}.
 *
 * @internal
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
enum Type: string
{
    case String = 'string';
    case RichText = 'rich_text';
    case Integer = 'integer';
    case Float = 'float';
    case Boolean = 'boolean';
    case DateTimeImmutable = 'datetime';
    case Asset = 'asset';
    case MultiLink = 'multi_link';
    case Link = 'link';
    case Uuid = 'uuid';
    case Editable = 'editable';
    case Blocks = 'blocks';

    /**
     * The PHP type declaration used for the generated property (without a leading "?").
     */
    public function typehint(): string
    {
        return match ($this) {
            self::String => 'string',
            self::RichText => 'RichText',
            self::Integer => 'int',
            self::Float => 'float',
            self::Boolean => 'bool',
            self::DateTimeImmutable => '\DateTimeImmutable',
            self::Asset => 'Asset',
            self::MultiLink => 'MultiLink',
            self::Link => 'Link',
            self::Uuid => 'Uuid',
            self::Editable => 'Editable',
            self::Blocks => 'array',
        };
    }

    /**
     * The class to import for this type's property declaration, if any.
     *
     * @return null|class-string
     */
    public function useStatement(): ?string
    {
        return match ($this) {
            self::RichText => RichText::class,
            self::Asset => Asset::class,
            self::MultiLink => MultiLink::class,
            self::Link => Link::class,
            self::Uuid => Uuid::class,
            self::Editable => Editable::class,
            default => null,
        };
    }

    /**
     * Some types cannot be represented as non-nullable given the available
     * {@see \Storyblok\Bundle\Util\ValueObjectTrait} helpers.
     */
    public function forcesNullable(): bool
    {
        return self::Editable === $this;
    }

    /**
     * Some types are never nullable (their helper already returns a safe default).
     */
    public function forcesNonNullable(): bool
    {
        return \in_array($this, [self::Boolean, self::Blocks], true);
    }

    /**
     * Builds the right-hand side {@see \Storyblok\Bundle\Util\ValueObjectTrait} call
     * expression for a property assignment (without the trailing semicolon).
     */
    public function expression(string $key, bool $nullable, ?int $maxLength = null, ?int $min = null, ?int $max = null): string
    {
        $keyLiteral = self::export($key);
        $args = \sprintf('$values, %s', $keyLiteral);

        return match ($this) {
            self::String => \sprintf(
                'self::%s(%s%s)',
                $nullable ? 'nullOrString' : 'string',
                $args,
                null !== $maxLength ? ', '.$maxLength : '',
            ),
            self::RichText => \sprintf('self::%s(%s)', $nullable ? 'nullOrRichText' : 'RichText', $args),
            self::Asset => \sprintf('self::%s(%s)', $nullable ? 'nullOrAsset' : 'Asset', $args),
            self::MultiLink => \sprintf('self::%s(%s)', $nullable ? 'nullOrMultiLink' : 'MultiLink', $args),
            self::Editable => \sprintf('self::nullOrEditable(%s)', $args),
            self::Boolean => \sprintf('self::boolean(%s)', $args),
            self::Integer => $nullable
                ? \sprintf('\array_key_exists(%s, $values) ? self::zeroOrInteger(%s) : null', $keyLiteral, $args)
                : \sprintf('self::zeroOrInteger(%s)', $args),
            self::Float => $nullable
                ? \sprintf('\array_key_exists(%s, $values) ? self::zeroOrFloat(%s) : null', $keyLiteral, $args)
                : \sprintf('self::zeroOrFloat(%s)', $args),
            self::DateTimeImmutable => $nullable
                ? \sprintf('\array_key_exists(%s, $values) && \'\' !== $values[%s] ? self::DateTimeImmutable(%s) : null', $keyLiteral, $keyLiteral, $args)
                : \sprintf('self::DateTimeImmutable(%s)', $args),
            self::Uuid => $nullable
                ? \sprintf('\array_key_exists(%s, $values) && \'\' !== $values[%s] ? self::Uuid(%s) : null', $keyLiteral, $keyLiteral, $args)
                : \sprintf('self::Uuid(%s)', $args),
            self::Link => $nullable
                ? \sprintf('\array_key_exists(%s, $values) ? self::Link(%s) : null', $keyLiteral, $args)
                : \sprintf('self::Link(%s)', $args),
            self::Blocks => \sprintf('self::Blocks(%s%s)', $args, self::blocksBounds($min, $max)),
        };
    }

    private static function blocksBounds(?int $min, ?int $max): string
    {
        if (null === $min && null === $max) {
            return '';
        }

        return \sprintf(', %s, %s', $min ?? 'null', $max ?? 'null');
    }

    private static function export(string $key): string
    {
        return \sprintf("'%s'", \str_replace("'", "\\'", $key));
    }
}
