<?php

declare(strict_types=1);

/**
 * This file is part of sensiolabs-de/storyblok-bundle.
 *
 * (c) SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\ContentType;

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

        return new self(
            className: $values['className'],
            contentType: $values['contentType'],
            type: $values['type'],
            slug: $values['slug'] ?? null,
        );
    }
}
