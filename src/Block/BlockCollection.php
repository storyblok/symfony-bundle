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

namespace Storyblok\Bundle\Block;

use Storyblok\Bundle\Block\Exception\BlockNotFoundException;

/**
 * @implements \IteratorAggregate<int, BlockDefinition>
 */
final class BlockCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<class-string, BlockDefinition>
     */
    private array $blocks = [];

    /**
     * @param list<BlockDefinition|array{
     *     fqcn: class-string,
     *     name: non-empty-string,
     *     template: non-empty-string,
     * }> $blocks
     */
    public function __construct(array $blocks = [])
    {
        foreach ($blocks as $block) {
            $this->add($block);
        }
    }

    /**
     * @param array<string, mixed>|BlockDefinition $definition
     */
    public function add(array|BlockDefinition $definition): void
    {
        if (\is_array($definition)) {
            $definition = BlockDefinition::fromArray($definition);
        }

        $this->blocks[$definition->className] = $definition;
    }

    /**
     * @param class-string $fqcn
     */
    public function get(string $fqcn): BlockDefinition
    {
        if (!\array_key_exists($fqcn, $this->blocks)) {
            throw new BlockNotFoundException(\sprintf('Block "%s" not found.', $fqcn));
        }

        return $this->blocks[$fqcn];
    }

    public function byName(string $name): BlockDefinition
    {
        $definitions = \array_values(\array_filter(
            $this->blocks,
            static fn (BlockDefinition $definition) => $definition->name === $name,
        ));

        if (0 === \count($definitions)) {
            throw new BlockNotFoundException(\sprintf('Block "%s" not found.', $name));
        }

        return $definitions[0];
    }

    /**
     * @return \ArrayIterator<int, BlockDefinition>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(\array_values($this->blocks));
    }

    public function count(): int
    {
        return \count($this->blocks);
    }
}
