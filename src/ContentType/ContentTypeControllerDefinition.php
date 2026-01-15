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

namespace Storyblok\Bundle\ContentType;

use Storyblok\Api\Domain\Value\Resolver\RelationCollection;
use Storyblok\Api\Domain\Value\Resolver\ResolveLinks;
use Storyblok\Api\Request\StoryRequest;
use Webmozart\Assert\Assert;

final readonly class ContentTypeControllerDefinition
{
    /**
     * @param class-string $className
     * @param class-string $contentType
     */
    public function __construct(
        public string $className,
        public string $contentType,
        public string $type,
        public ?string $slug = null,
        public RelationCollection $resolveRelations = new RelationCollection(),
        public ResolveLinks $resolveLinks = new ResolveLinks(),
    ) {
        Assert::notWhitespaceOnly($className);
        Assert::classExists($className);

        Assert::notWhitespaceOnly($contentType);
        Assert::classExists($contentType);

        Assert::notSame($contentType, $className);

        Assert::stringNotEmpty($type);
        Assert::notWhitespaceOnly($type);

        Assert::nullOrStringNotEmpty($slug);
        Assert::nullOrNotWhitespaceOnly($slug);
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        Assert::keyExists($values, 'className');
        Assert::string($values['className']);
        Assert::classExists($values['className']);

        Assert::keyExists($values, 'contentType');
        Assert::string($values['contentType']);
        Assert::classExists($values['contentType']);

        Assert::keyExists($values, 'type');
        Assert::string($values['type']);

        Assert::keyExists($values, 'resolveRelations');
        Assert::string($values['resolveRelations']);

        Assert::keyExists($values, 'resolveLinks');
        Assert::isArray($values['resolveLinks']);

        return new self(
            className: $values['className'],
            contentType: $values['contentType'],
            type: $values['type'],
            slug: $values['slug'] ?? null,
            resolveRelations: RelationCollection::fromString($values['resolveRelations']),
            resolveLinks: ResolveLinks::fromArray($values['resolveLinks']),
        );
    }
}
