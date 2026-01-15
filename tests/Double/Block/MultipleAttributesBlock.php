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

namespace Storyblok\Bundle\Tests\Double\Block;

use Storyblok\Bundle\Block\Attribute\AsBlock;

#[AsBlock(name: 'youtube_embed')]
#[AsBlock(name: 'vimeo_embed')]
#[AsBlock(name: 'twitter_embed')]
final readonly class MultipleAttributesBlock
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
    }
}
