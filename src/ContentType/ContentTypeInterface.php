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

interface ContentTypeInterface
{
    /**
     * @param array<string, array<string,mixed>&array{
     *     content: array{
     *         component: non-empty-string,
     *     }
     * }> $values
     */
    public function __construct(array $values);

    /**
     * @return non-empty-string
     */
    public static function type(): string;
}
