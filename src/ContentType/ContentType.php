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

use Webmozart\Assert\Assert;
use function Symfony\Component\String\u;

abstract readonly class ContentType implements ContentTypeInterface
{
    public static function type(): string
    {
        $type = u(static::class)
            ->afterLast('\\')
            ->snake()
            ->toString();

        Assert::stringNotEmpty($type);

        return $type;
    }
}
