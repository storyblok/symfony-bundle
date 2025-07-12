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

namespace Storyblok\Bundle\Util;

use Storyblok\Api\Util\ValueObjectTrait as BaseValueObjectTrait;
use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Block\Exception\BlockNotFoundException;
use Webmozart\Assert\Assert;

/**
 * @experimental This trait is experimental and may change in future versions.
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Edo Dusi <edo@edoardodusi.com>
 */
trait ValueObjectTrait
{
    use BaseValueObjectTrait;

    /**
     * @param array<mixed>     $values
     * @param non-empty-string $key
     *
     * @return list<object>
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
