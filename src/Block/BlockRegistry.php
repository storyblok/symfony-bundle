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

namespace Storyblok\Bundle\Block;

use Storyblok\Bundle\Block\Exception\BlockNotFoundException;

final class BlockRegistry implements \Countable
{
    /**
     * @var array<string, BlockDefinition>
     */
    public static array $blocks = [];

    public function __construct()
    {
        // Noop! Only used by the dependency injection. Static methods are used to interact with the registry.
    }

    /**
     * @param array<string, mixed>|BlockDefinition $definition
     */
    public static function add(array|BlockDefinition $definition): void
    {
        if (\is_array($definition)) {
            $definition = BlockDefinition::fromArray($definition);
        }

        self::$blocks[$definition->name] = $definition;
    }

    /**
     * @param class-string $fqcn
     */
    public static function get(string $fqcn): BlockDefinition
    {
        $definitions = \array_values(\array_filter(
            self::$blocks,
            static fn (BlockDefinition $definition) => $definition->className === $fqcn,
        ));

        if (0 === \count($definitions)) {
            throw new BlockNotFoundException(\sprintf('Block "%s" not found.', $fqcn));
        }

        return $definitions[0];
    }

    public static function byName(string $name): BlockDefinition
    {
        if (!\array_key_exists($name, self::$blocks)) {
            throw new BlockNotFoundException(\sprintf('Block "%s" not found.', $name));
        }

        return self::$blocks[$name];
    }

    public function count(): int
    {
        return \count(self::$blocks);
    }
}
