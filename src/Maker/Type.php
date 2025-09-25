<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Maker;

use OskarStark\Enum\Trait\Comparable;
use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Api\Domain\Type\Editable;
use Storyblok\Api\Domain\Type\MultiLink;
use Storyblok\Api\Domain\Type\RichText;
use Storyblok\Api\Domain\Value\Link;
use Storyblok\Api\Domain\Value\Uuid;

enum Type: string
{
    use Comparable;

    case String = 'string';
    case RichText = 'rich_text';
    case Integer = 'integer';
    case Float = 'float';
    case Blocks = 'blocks';
    case List = 'list';
    case Asset = 'asset';
    case Enum = 'enum';
    case DateTimeImmutable = 'datetime_immutable';
    case Uuid = 'uuid';
    case MultiLink = 'multi_link';
    case Link = 'link';
    case Boolean = 'boolean';
    case Editable = 'editable';

    /**
     * @return class-string|null
     */
    public function useStatement(): ?string
    {
        return match ($this) {
            self::RichText => RichText::class,
            self::Uuid => Uuid::class,
            self::MultiLink => MultiLink::class,
            self::Link => Link::class,
            self::Editable => Editable::class,
            self::Asset => Asset::class,
            default => null,
        };
    }
}
