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

use Storyblok\Bundle\ContentType\Exception\ContentTypeControllerNotFoundException;

/**
 * @internal
 */
final class ContentTypeControllerRegistry implements \Countable
{
    /**
     * @param array<class-string, ContentTypeControllerDefinition> $controllers
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

        $this->controllers[$definition->className] = $definition;
    }

    /**
     * @param class-string $fqcn
     */
    public function get(string $fqcn): ContentTypeControllerDefinition
    {
        if (!\array_key_exists($fqcn, $this->controllers)) {
            throw new ContentTypeControllerNotFoundException(\sprintf('ContentTypeController "%s" not found.', $fqcn));
        }

        return $this->controllers[$fqcn];
    }

    public function bySlug(string $slug): ContentTypeControllerDefinition
    {
        $definitions = \array_values(\array_filter(
            $this->controllers,
            static fn (ContentTypeControllerDefinition $definition) => $definition->slug === $slug,
        ));

        if (0 === \count($definitions)) {
            throw new ContentTypeControllerNotFoundException(\sprintf('ContentTypeController by slug "%s" not found.', $slug));
        }

        return $definitions[0];
    }

    public function byType(string $type): ContentTypeControllerDefinition
    {
        $definitions = \array_values(\array_filter(
            $this->controllers,
            static fn (ContentTypeControllerDefinition $definition) => $definition->type === $type && null === $definition->slug,
        ));

        if (0 === \count($definitions)) {
            throw new ContentTypeControllerNotFoundException(\sprintf('ContentTypeController by type "%s" not found.', $type));
        }

        return $definitions[0];
    }

    public function count(): int
    {
        return \count($this->controllers);
    }
}
