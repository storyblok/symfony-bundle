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

namespace Storyblok\Bundle\Routing;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Kristian Hempel <protykhybrid@googlemail.com>
 */
enum Requirement
{
    /**
     *  \p{L} → Matches any letter (Latin, Kanji, Hiragana, Katakana, etc.)
     *  \p{N} → Matches any number (so numbers remain valid in slugs)
     *  (?:-[\p{L}\p{N}]+)* → Allows hyphenated words (e.g., hello-world)
     *  (?:\/[\p{L}\p{N}]+(?:[-_][\p{L}\p{N}]+)*)*\/? → Allows slashes for hierarchical paths
     *  Trailing slash (\/?) → Optional to allow both /slug and /slug/.
     */
    public const string SLUG = '([/_-]/)?([\p{L}\p{N}]+(?:[-_][\p{L}\p{N}]+)*(?:\/[\p{L}\p{N}]+(?:[-_][\p{L}\p{N}]+)*)*\/?)$';
}
