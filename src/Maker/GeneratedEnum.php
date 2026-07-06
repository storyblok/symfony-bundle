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

/**
 * A backed enum the maker generates from the inline options of a Storyblok "option" field.
 *
 * @internal
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final readonly class GeneratedEnum
{
    /**
     * @param non-empty-string                $shortName the enum class short name, e.g. "HeroLayout"
     * @param non-empty-array<string, string> $cases     the enum cases as caseName => backed value
     */
    public function __construct(
        public string $shortName,
        public array $cases,
    ) {
    }
}
