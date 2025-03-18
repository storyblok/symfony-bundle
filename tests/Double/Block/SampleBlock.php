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

namespace Storyblok\Bundle\Tests\Double\Block;

use Storyblok\Bundle\Block\Attribute\AsBlock;
use Webmozart\Assert\Assert;

#[AsBlock]
final readonly class SampleBlock
{
    public string $title;
    public string $description;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
        Assert::keyExists($values, 'title');
        $this->title = $values['title'];

        Assert::keyExists($values, 'description');
        $this->description = $values['description'];
    }
}
