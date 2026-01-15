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

use Storyblok\Bundle\ContentType\Exception\ContentTypeControllerNotFoundException;

/**
 * @internal
 */
final class ContentTypeControllerRegistry implements \Countable
{
    /**
     * @param array<string, ContentTypeControllerDefinition> $controllers
     */
    public function __construct(
        private array $controllers = [],
    ) {
    }

    /**
     * @param array<string, mixed>|ContentTypeControllerDefinition $definition
     */
    public function add(array|ContentTypeControllerDefinition $definition): void
    {
        if (\is_array($definition)) {
            $definition = ContentTypeControllerDefinition::fromArray($definition);
        }

        $this->controllers[$definition->slug ?? $definition->type] = $definition;
    }

    /**
     * @param class-string $fqcn
     */
    public function get(string $fqcn): ContentTypeControllerDefinition
    {
        $definitions = \array_values(\array_filter(
            $this->controllers,
            static fn (ContentTypeControllerDefinition $definition) => $definition->className === $fqcn,
        ));

        if (0 === \count($definitions)) {
            throw new ContentTypeControllerNotFoundException(\sprintf('ContentTypeController "%s" not found.', $fqcn));
        }

        return $definitions[0];
    }

    public function bySlug(string $slug): ContentTypeControllerDefinition
    {
        if (!\array_key_exists($slug, $this->controllers)) {
            throw new ContentTypeControllerNotFoundException(\sprintf('ContentTypeController by slug "%s" not found.', $slug));
        }

        return $this->controllers[$slug];
    }

    public function byType(string $type): ContentTypeControllerDefinition
    {
        if (!\array_key_exists($type, $this->controllers)) {
            throw new ContentTypeControllerNotFoundException(\sprintf('ContentTypeController by type "%s" not found.', $type));
        }

        $definition = $this->controllers[$type];

        if (null !== $definition->slug) {
            throw new ContentTypeControllerNotFoundException(\sprintf('ContentTypeController by type "%s" not found.', $type));
        }

        return $definition;
    }

    public function count(): int
    {
        return \count($this->controllers);
    }

    /**
     * @return array<string, ContentTypeControllerDefinition>
     */
    public function all(): array
    {
        return $this->controllers;
    }
}
